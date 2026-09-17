<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Finder\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
    ) {}

    /**
     * List current user's bookings.
     * GET /api/v1/bookings
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,confirmed,in_progress,completed,cancelled'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $paginator = $this->bookingService->listCustomerBookings($request->user(), $validated);

        return response()->json([
            'data' => collect($paginator->items())->map(fn ($booking) => [
                'id' => $booking->id,
                'uuid' => $booking->uuid,
                'reference' => $booking->reference,
                'type' => $booking->type,
                'status' => $booking->status,
                'payment_status' => $booking->payment_status,
                'fulfilment' => $booking->fulfilment,
                'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
                'subtotal' => (float) $booking->subtotal,
                'discount' => (float) $booking->discount,
                'total' => (float) $booking->total,
                'currency' => $booking->currency,
                'vendor' => $booking->vendor ? [
                    'uuid' => $booking->vendor->uuid,
                    'business_name' => $booking->vendor->business_name,
                    'trading_name' => $booking->vendor->trading_name,
                    'logo_path' => $booking->vendor->logo_path,
                    'phone' => $booking->vendor->phone,
                    'city' => $booking->vendor->city,
                    'state' => $booking->vendor->state,
                    'address_line' => $booking->vendor->address_line,
                    'rating_average' => (float) $booking->vendor->rating_average,
                ] : null,
                'vehicle' => $booking->vehicle ? [
                    'uuid' => $booking->vehicle->uuid,
                    'make' => $booking->vehicle->make,
                    'model' => $booking->vehicle->model,
                    'year' => $booking->vehicle->year,
                    'plate_number' => $booking->vehicle->plate_number,
                ] : null,
                'items_count' => $booking->items->count(),
                'has_reviewed' => $booking->review !== null,
                'created_at' => $booking->created_at->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Create a new booking.
     * POST /api/v1/bookings
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vendor_uuid' => ['required', 'string', 'exists:vendors,uuid'],
            'vehicle_uuid' => ['nullable', 'string', 'exists:vehicles,uuid'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'fulfilment' => ['nullable', 'string', 'in:in_store,mobile,delivery,pickup'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_uuid' => ['required', 'string', 'exists:vendor_services,uuid'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $booking = $this->bookingService->createBooking($request->user(), $validated);

        return response()->json([
            'message' => 'Booking created successfully.',
            'data' => [
                'id' => $booking->id,
                'uuid' => $booking->uuid,
                'reference' => $booking->reference,
                'status' => $booking->status,
                'payment_status' => $booking->payment_status,
                'scheduled_at' => $booking->scheduled_at->toIso8601String(),
                'subtotal' => (float) $booking->subtotal,
                'total' => (float) $booking->total,
                'currency' => $booking->currency,
                'vendor' => [
                    'uuid' => $booking->vendor->uuid,
                    'business_name' => $booking->vendor->business_name,
                    'phone' => $booking->vendor->phone,
                ],
                'vehicle' => $booking->vehicle ? [
                    'uuid' => $booking->vehicle->uuid,
                    'plate_number' => $booking->vehicle->plate_number,
                ] : null,
                'items' => $booking->items->map(fn ($item) => [
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                ]),
            ],
        ], 201);
    }

    /**
     * Show booking details.
     * GET /api/v1/bookings/{bookingUuid}
     */
    public function show(Request $request, string $bookingUuid): JsonResponse
    {
        $booking = $this->bookingService->getBookingDetails($request->user(), $bookingUuid);

        return response()->json([
            'data' => [
                'id' => $booking->id,
                'uuid' => $booking->uuid,
                'reference' => $booking->reference,
                'type' => $booking->type,
                'status' => $booking->status,
                'payment_status' => $booking->payment_status,
                'fulfilment' => $booking->fulfilment,
                'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
                'completed_at' => $booking->completed_at?->toIso8601String(),
                'cancelled_at' => $booking->cancelled_at?->toIso8601String(),
                'cancellation_reason' => $booking->cancellation_reason,
                'customer_note' => $booking->customer_note,
                'vendor_note' => $booking->vendor_note,
                'subtotal' => (float) $booking->subtotal,
                'discount' => (float) $booking->discount,
                'total' => (float) $booking->total,
                'currency' => $booking->currency,
                'can_cancel' => $booking->canBeCancelled(),
                'vendor' => [
                    'uuid' => $booking->vendor->uuid,
                    'business_name' => $booking->vendor->business_name,
                    'trading_name' => $booking->vendor->trading_name,
                    'logo_path' => $booking->vendor->logo_path,
                    'phone' => $booking->vendor->phone,
                    'email' => $booking->vendor->email,
                    'address_line' => $booking->vendor->address_line,
                    'city' => $booking->vendor->city,
                    'state' => $booking->vendor->state,
                    'latitude' => $booking->vendor->latitude ? (float) $booking->vendor->latitude : null,
                    'longitude' => $booking->vendor->longitude ? (float) $booking->vendor->longitude : null,
                    'rating_average' => (float) $booking->vendor->rating_average,
                ],
                'vehicle' => $booking->vehicle ? [
                    'uuid' => $booking->vehicle->uuid,
                    'make' => $booking->vehicle->make,
                    'model' => $booking->vehicle->model,
                    'year' => $booking->vehicle->year,
                    'color' => $booking->vehicle->color,
                    'plate_number' => $booking->vehicle->plate_number,
                ] : null,
                'items' => $booking->items->map(fn ($item) => [
                    'uuid' => $item->vendorService?->uuid,
                    'name' => $item->name,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                ]),
                'review' => $booking->review ? [
                    'uuid' => $booking->review->uuid,
                    'rating' => $booking->review->rating,
                    'title' => $booking->review->title,
                    'comment' => $booking->review->comment,
                    'created_at' => $booking->review->created_at->toIso8601String(),
                ] : null,
                'created_at' => $booking->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Cancel a booking.
     * POST /api/v1/bookings/{bookingUuid}/cancel
     */
    public function cancel(Request $request, string $bookingUuid): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $booking = $this->bookingService->cancelBooking($request->user(), $bookingUuid, $validated['reason'] ?? null);

        return response()->json([
            'message' => 'Booking cancelled successfully.',
            'data' => [
                'uuid' => $booking->uuid,
                'reference' => $booking->reference,
                'status' => $booking->status,
                'cancelled_at' => $booking->cancelled_at->toIso8601String(),
                'cancellation_reason' => $booking->cancellation_reason,
            ],
        ]);
    }
}
