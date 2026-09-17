<?php

namespace App\Services\Finder;

use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCategory;
use App\Models\VendorService;
use App\Models\VendorVerification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class VendorHubService
{
    /**
     * Get or require the vendor profile owned by this user.
     */
    public function getVendorForUser(User $user, bool $required = false): ?Vendor
    {
        $vendor = Vendor::query()
            ->where('owner_user_id', $user->id)
            ->with(['category', 'verifications'])
            ->first();

        if ($required && ! $vendor) {
            throw new AccessDeniedHttpException('You do not have a registered vendor profile.');
        }

        return $vendor;
    }

    /**
     * Register a new vendor profile.
     */
    public function registerVendor(User $user, array $data): Vendor
    {
        if (Vendor::query()->where('owner_user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'business_name' => ['You have already registered a vendor profile.'],
            ]);
        }

        $categoryId = null;
        if (! empty($data['category_id'])) {
            $categoryId = $data['category_id'];
        } elseif (! empty($data['category_uuid'])) {
            $cat = VendorCategory::query()->where('uuid', $data['category_uuid'])->first();
            $categoryId = $cat?->id;
        }

        $baseSlug = Str::slug($data['business_name']);
        $slug = $baseSlug.'-'.Str::lower(Str::random(4));

        $vendor = Vendor::create([
            'owner_user_id' => $user->id,
            'vendor_category_id' => $categoryId,
            'business_name' => $data['business_name'],
            'trading_name' => $data['trading_name'] ?? $data['business_name'],
            'slug' => $slug,
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? $user->phone,
            'whatsapp' => $data['whatsapp'] ?? null,
            'description' => $data['description'] ?? null,
            'address_line' => $data['address_line'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => 'Nigeria',
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'service_radius_km' => $data['service_radius_km'] ?? 25,
            'opening_hours' => $data['opening_hours'] ?? null,
            'status' => Vendor::STATUS_PENDING,
            'is_publicly_visible' => false,
        ]);

        // Auto-create initial verification stage
        VendorVerification::create([
            'vendor_id' => $vendor->id,
            'stage' => VendorVerification::STAGE_APPLICATION,
            'status' => VendorVerification::STATUS_PENDING,
        ]);

        return $vendor->load(['category', 'verifications']);
    }

    /**
     * Update vendor business details.
     */
    public function updateProfile(Vendor $vendor, array $data): Vendor
    {
        $allowed = [
            'business_name',
            'trading_name',
            'email',
            'phone',
            'whatsapp',
            'description',
            'address_line',
            'city',
            'state',
            'latitude',
            'longitude',
            'service_radius_km',
            'opening_hours',
            'cancellation_policy',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        if (! empty($data['category_id'])) {
            $payload['vendor_category_id'] = $data['category_id'];
        }

        $vendor->update($payload);

        return $vendor->fresh(['category', 'verifications']);
    }

    /**
     * List services for vendor.
     */
    public function listServices(Vendor $vendor)
    {
        return VendorService::query()
            ->where('vendor_id', $vendor->id)
            ->with('category')
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a service / product for vendor.
     */
    public function createService(Vendor $vendor, array $data): VendorService
    {
        $baseSlug = Str::slug($data['name']);
        $slug = $baseSlug.'-'.Str::lower(Str::random(4));

        return VendorService::create([
            'vendor_id' => $vendor->id,
            'vendor_category_id' => $data['category_id'] ?? $vendor->vendor_category_id,
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'service',
            'price' => (float) $data['price'],
            'currency' => $data['currency'] ?? 'NGN',
            'duration_minutes' => $data['duration_minutes'] ?? 60,
            'stock_quantity' => $data['stock_quantity'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'is_bookable' => $data['is_bookable'] ?? true,
        ]);
    }

    /**
     * Update a vendor service.
     */
    public function updateService(Vendor $vendor, string $serviceUuid, array $data): VendorService
    {
        $service = VendorService::query()
            ->where('uuid', $serviceUuid)
            ->where('vendor_id', $vendor->id)
            ->firstOrFail();

        $service->update(array_filter([
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? null,
            'price' => isset($data['price']) ? (float) $data['price'] : null,
            'duration_minutes' => isset($data['duration_minutes']) ? (int) $data['duration_minutes'] : null,
            'is_active' => $data['is_active'] ?? null,
            'is_bookable' => $data['is_bookable'] ?? null,
        ], fn ($val) => $val !== null));

        return $service->fresh('category');
    }

    /**
     * Delete a vendor service.
     */
    public function deleteService(Vendor $vendor, string $serviceUuid): void
    {
        $service = VendorService::query()
            ->where('uuid', $serviceUuid)
            ->where('vendor_id', $vendor->id)
            ->firstOrFail();

        $service->delete();
    }

    /**
     * List incoming bookings for vendor.
     */
    public function listBookings(Vendor $vendor, array $filters = []): LengthAwarePaginator
    {
        $query = Booking::query()
            ->where('vendor_id', $vendor->id)
            ->with([
                'user:id,uuid,first_name,last_name,email,phone',
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
     * Update booking status by vendor.
     */
    public function updateBookingStatus(Vendor $vendor, string $bookingUuid, string $newStatus, ?string $vendorNote = null): Booking
    {
        $booking = Booking::query()
            ->where('uuid', $bookingUuid)
            ->where('vendor_id', $vendor->id)
            ->firstOrFail();

        $allowedStatuses = [
            Booking::STATUS_CONFIRMED,
            Booking::STATUS_IN_PROGRESS,
            Booking::STATUS_COMPLETED,
            Booking::STATUS_CANCELLED,
        ];

        if (! in_array($newStatus, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => ['Invalid status transition.'],
            ]);
        }

        $updateData = ['status' => $newStatus];

        if ($vendorNote !== null) {
            $updateData['vendor_note'] = $vendorNote;
        }

        if ($newStatus === Booking::STATUS_COMPLETED) {
            $updateData['completed_at'] = now();
        } elseif ($newStatus === Booking::STATUS_CANCELLED) {
            $updateData['cancelled_at'] = now();
            $updateData['cancellation_reason'] = $vendorNote ?? 'Cancelled by vendor';
        }

        $booking->update($updateData);

        return $booking->fresh(['user', 'vehicle', 'items.vendorService', 'review']);
    }

    /**
     * Reply to a customer review.
     */
    public function replyToReview(Vendor $vendor, string $reviewUuid, string $reply): Review
    {
        $review = Review::query()
            ->where('uuid', $reviewUuid)
            ->where('vendor_id', $vendor->id)
            ->firstOrFail();

        $review->update([
            'vendor_reply' => $reply,
            'vendor_replied_at' => now(),
        ]);

        return $review->fresh('user:id,first_name,last_name');
    }
}
