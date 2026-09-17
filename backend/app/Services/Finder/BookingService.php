<?php

namespace App\Services\Finder;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Vendor;
use App\Models\VendorService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BookingService
{
    /**
     * Create a new booking for a customer.
     */
    public function createBooking(User $customer, array $data): Booking
    {
        return DB::transaction(function () use ($customer, $data) {
            $vendor = Vendor::query()->where('uuid', $data['vendor_uuid'])->first();
            if (! $vendor || ! $vendor->isBookable()) {
                throw ValidationException::withMessages([
                    'vendor_uuid' => ['The selected vendor is not currently available for bookings.'],
                ]);
            }

            $vehicle = null;
            if (! empty($data['vehicle_uuid'])) {
                $vehicle = Vehicle::query()
                    ->where('uuid', $data['vehicle_uuid'])
                    ->where(function (Builder $query) use ($customer) {
                        $query->where('user_id', $customer->id)
                            ->orWhereHas('accessGrants', function (Builder $q) use ($customer) {
                                $q->where('grantee_user_id', $customer->id)
                                    ->where(function (Builder $sq) {
                                        $sq->whereNull('expires_at')
                                            ->orWhere('expires_at', '>', now());
                                    });
                            });
                    })
                    ->first();

                if (! $vehicle) {
                    throw ValidationException::withMessages([
                        'vehicle_uuid' => ['You do not have access to the specified vehicle.'],
                    ]);
                }
            }

            // Resolve items
            $rawItems = $data['items'] ?? [];
            if (empty($rawItems)) {
                throw ValidationException::withMessages([
                    'items' => ['At least one service must be selected.'],
                ]);
            }

            $serviceUuids = array_column($rawItems, 'service_uuid');
            $services = VendorService::query()
                ->where('vendor_id', $vendor->id)
                ->whereIn('uuid', $serviceUuids)
                ->where('is_active', true)
                ->where('is_bookable', true)
                ->get()
                ->keyBy('uuid');

            if ($services->count() !== count($serviceUuids)) {
                throw ValidationException::withMessages([
                    'items' => ['One or more selected services are unavailable.'],
                ]);
            }

            $subtotal = 0.00;
            $preparedItems = [];

            foreach ($rawItems as $itemInput) {
                $srv = $services->get($itemInput['service_uuid']);
                $qty = max(1, (int) ($itemInput['quantity'] ?? 1));
                $unitPrice = (float) $srv->price;
                $lineTotal = $unitPrice * $qty;
                $subtotal += $lineTotal;

                $preparedItems[] = [
                    'vendor_service_id' => $srv->id,
                    'name' => $srv->name,
                    'description' => $srv->description,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            $commissionPercent = $vendor->effectiveCommissionPercent();
            $commissionAmount = round($subtotal * ($commissionPercent / 100), 2);
            $total = $subtotal;

            $booking = Booking::create([
                'reference' => Booking::generateReference(),
                'user_id' => $customer->id,
                'vehicle_id' => $vehicle?->id,
                'vendor_id' => $vendor->id,
                'type' => Booking::TYPE_BOOKING,
                'status' => Booking::STATUS_PENDING,
                'payment_status' => Booking::PAYMENT_UNPAID,
                'fulfilment' => $data['fulfilment'] ?? 'in_store',
                'scheduled_at' => $data['scheduled_at'],
                'customer_note' => $data['customer_note'] ?? null,
                'subtotal' => $subtotal,
                'discount' => 0.00,
                'coins_redeemed' => 0.00,
                'commission_percent' => $commissionPercent,
                'commission_amount' => $commissionAmount,
                'total' => $total,
                'currency' => 'NGN',
            ]);

            foreach ($preparedItems as $pItem) {
                $booking->items()->create($pItem);
            }

            return $booking->load(['vendor', 'vehicle', 'items.vendorService']);
        });
    }

    /**
     * List bookings for the authenticated customer.
     */
    public function listCustomerBookings(User $customer, array $filters = []): LengthAwarePaginator
    {
        $query = Booking::query()
            ->where('user_id', $customer->id)
            ->with([
                'vendor:id,uuid,business_name,trading_name,logo_path,phone,city,state,address_line,rating_average',
                'vehicle:id,uuid,make,model,year,plate_number',
                'items.vendorService',
                'review',
            ]);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 50);

        return $query->latest('scheduled_at')->paginate($perPage);
    }

    /**
     * Get booking details with authorization check.
     */
    public function getBookingDetails(User $user, string $bookingUuid): Booking
    {
        $booking = Booking::query()
            ->where('uuid', $bookingUuid)
            ->with([
                'vendor:id,uuid,business_name,trading_name,logo_path,phone,email,address_line,city,state,latitude,longitude,rating_average,owner_user_id',
                'vehicle:id,uuid,make,model,year,plate_number,color',
                'items.vendorService',
                'review',
            ])
            ->firstOrFail();

        // Must be customer or vendor owner
        if ($booking->user_id !== $user->id && $booking->vendor->owner_user_id !== $user->id) {
            throw new AccessDeniedHttpException('You do not have permission to view this booking.');
        }

        return $booking;
    }

    /**
     * Cancel a booking.
     */
    public function cancelBooking(User $user, string $bookingUuid, ?string $reason = null): Booking
    {
        $booking = $this->getBookingDetails($user, $bookingUuid);

        if (! $booking->canBeCancelled()) {
            throw ValidationException::withMessages([
                'booking' => ["Cannot cancel a booking that is {$booking->status}."],
            ]);
        }

        $booking->update([
            'status' => Booking::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason ?? 'Cancelled by user',
        ]);

        return $booking->fresh(['vendor', 'vehicle', 'items']);
    }

    /**
     * Submit a customer review for a completed booking or verified vendor.
     */
    public function submitReview(User $user, string $vendorUuid, array $data): Review
    {
        $vendor = Vendor::query()->where('uuid', $vendorUuid)->firstOrFail();

        $booking = null;
        if (! empty($data['booking_uuid'])) {
            $booking = Booking::query()
                ->where('uuid', $data['booking_uuid'])
                ->where('user_id', $user->id)
                ->where('vendor_id', $vendor->id)
                ->first();

            if (! $booking) {
                throw ValidationException::withMessages([
                    'booking_uuid' => ['Invalid booking reference for this vendor.'],
                ]);
            }

            if ($booking->status !== Booking::STATUS_COMPLETED) {
                throw ValidationException::withMessages([
                    'booking_uuid' => ['Reviews can only be submitted for completed bookings.'],
                ]);
            }

            if (Review::query()->where('booking_id', $booking->id)->exists()) {
                throw ValidationException::withMessages([
                    'booking_uuid' => ['You have already submitted a review for this booking.'],
                ]);
            }
        }

        $review = Review::create([
            'user_id' => $user->id,
            'vendor_id' => $vendor->id,
            'booking_id' => $booking?->id,
            'rating' => (int) $data['rating'],
            'title' => $data['title'] ?? null,
            'comment' => $data['comment'],
            'is_verified_booking' => $booking !== null,
            'is_published' => true,
        ]);

        $vendor->recalculateRating();

        return $review->load('user:id,first_name,last_name');
    }

    /**
     * Mark booking as paid and generate vendor settlement record.
     */
    public function markAsPaid(Booking $booking, \App\Models\Payment $payment): Booking
    {
        return DB::transaction(function () use ($booking, $payment) {
            $booking->update([
                'payment_status' => Booking::PAYMENT_PAID,
                'status' => $booking->status === Booking::STATUS_PENDING ? Booking::STATUS_CONFIRMED : $booking->status,
            ]);

            // Calculate vendor settlement
            $grossAmount = (float) $booking->total;
            $commissionPercent = (float) ($booking->commission_percent ?: ($booking->vendor?->effectiveCommissionPercent() ?? 7.5));
            $commissionAmount = round($grossAmount * ($commissionPercent / 100), 2);
            $vendorAmount = round($grossAmount - $commissionAmount, 2);

            \App\Models\VendorSettlement::updateOrCreate(
                [
                    'booking_id' => $booking->id,
                ],
                [
                    'vendor_id' => $booking->vendor_id,
                    'payment_id' => $payment->id,
                    'gross_amount' => $grossAmount,
                    'commission_percent' => $commissionPercent,
                    'commission_amount' => $commissionAmount,
                    'vendor_amount' => $vendorAmount,
                    'currency' => $booking->currency ?: 'NGN',
                    'status' => \App\Models\VendorSettlement::STATUS_PENDING,
                    'metadata' => [
                        'payment_reference' => $payment->reference,
                        'booking_reference' => $booking->reference,
                        'paid_at' => now()->toIso8601String(),
                    ],
                ]
            );

            return $booking->fresh(['vendor', 'vehicle', 'items', 'settlement']);
        });
    }

    /**
     * Reverse/refund a booking payment and update settlements.
     */
    public function refundBooking(Booking $booking, \App\Models\Payment $payment, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($booking, $payment, $reason) {
            $booking->update([
                'payment_status' => Booking::PAYMENT_REFUNDED,
                'status' => Booking::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason ?? 'Payment refunded',
            ]);

            // Reverse settlements
            \App\Models\VendorSettlement::query()
                ->where('booking_id', $booking->id)
                ->update([
                    'status' => \App\Models\VendorSettlement::STATUS_REVERSED,
                ]);

            return $booking->fresh(['vendor', 'vehicle', 'items', 'settlement']);
        });
    }
}

