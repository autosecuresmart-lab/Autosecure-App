<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Finder\VendorHubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorHubController extends Controller
{
    public function __construct(
        protected VendorHubService $vendorHubService,
    ) {}

    /**
     * Register as a vendor.
     * POST /api/v1/vendor-hub/register
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:120'],
            'trading_name' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'exists:vendor_categories,id'],
            'category_uuid' => ['nullable', 'string', 'exists:vendor_categories,uuid'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:2000'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:64'],
            'state' => ['nullable', 'string', 'max:64'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'service_radius_km' => ['nullable', 'integer', 'min:1', 'max:200'],
            'opening_hours' => ['nullable', 'array'],
        ]);

        $vendor = $this->vendorHubService->registerVendor($request->user(), $validated);

        return response()->json([
            'message' => 'Vendor profile registered successfully. Awaiting verification review.',
            'data' => [
                'uuid' => $vendor->uuid,
                'business_name' => $vendor->business_name,
                'status' => $vendor->status,
                'is_publicly_visible' => $vendor->is_publicly_visible,
                'category' => $vendor->category ? [
                    'uuid' => $vendor->category->uuid,
                    'name' => $vendor->category->name,
                ] : null,
                'created_at' => $vendor->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Get vendor profile for current user.
     * GET /api/v1/vendor-hub/profile
     */
    public function profile(Request $request): JsonResponse
    {
        $vendor = $this->vendorHubService->getVendorForUser($request->user(), true);

        return response()->json([
            'data' => [
                'id' => $vendor->id,
                'uuid' => $vendor->uuid,
                'business_name' => $vendor->business_name,
                'trading_name' => $vendor->trading_name,
                'slug' => $vendor->slug,
                'email' => $vendor->email,
                'phone' => $vendor->phone,
                'whatsapp' => $vendor->whatsapp,
                'description' => $vendor->description,
                'address_line' => $vendor->address_line,
                'city' => $vendor->city,
                'state' => $vendor->state,
                'country' => $vendor->country,
                'latitude' => $vendor->latitude ? (float) $vendor->latitude : null,
                'longitude' => $vendor->longitude ? (float) $vendor->longitude : null,
                'service_radius_km' => $vendor->service_radius_km,
                'opening_hours' => $vendor->opening_hours,
                'status' => $vendor->status,
                'is_publicly_visible' => $vendor->is_publicly_visible,
                'is_bookable' => $vendor->isBookable(),
                'verified_at' => $vendor->verified_at?->toIso8601String(),
                'rating_average' => (float) $vendor->rating_average,
                'rating_count' => (int) $vendor->rating_count,
                'category' => $vendor->category ? [
                    'id' => $vendor->category->id,
                    'uuid' => $vendor->category->uuid,
                    'name' => $vendor->category->name,
                ] : null,
                'verifications' => $vendor->verifications->map(fn ($v) => [
                    'stage' => $v->stage,
                    'status' => $v->status,
                    'notes' => $v->notes,
                    'reviewed_at' => $v->reviewed_at?->toIso8601String(),
                ]),
            ],
        ]);
    }

    /**
     * Update vendor profile.
     * PUT /api/v1/vendor-hub/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $vendor = $this->vendorHubService->getVendorForUser($request->user(), true);

        $validated = $request->validate([
            'business_name' => ['nullable', 'string', 'max:120'],
            'trading_name' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'exists:vendor_categories,id'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:2000'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:64'],
            'state' => ['nullable', 'string', 'max:64'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'service_radius_km' => ['nullable', 'integer', 'min:1', 'max:200'],
            'opening_hours' => ['nullable', 'array'],
            'cancellation_policy' => ['nullable', 'string', 'max:1000'],
        ]);

        $updated = $this->vendorHubService->updateProfile($vendor, $validated);

        return response()->json([
            'message' => 'Vendor profile updated successfully.',
            'data' => [
                'uuid' => $updated->uuid,
                'business_name' => $updated->business_name,
                'status' => $updated->status,
                'is_publicly_visible' => $updated->is_publicly_visible,
            ],
        ]);
    }

    /**
     * List vendor services.
     * GET /api/v1/vendor-hub/services
     */
    public function services(Request $request): JsonResponse
    {
        $vendor = $this->vendorHubService->getVendorForUser($request->user(), true);
        $services = $this->vendorHubService->listServices($vendor);

        return response()->json([
            'data' => $services->map(fn ($s) => [
                'id' => $s->id,
                'uuid' => $s->uuid,
                'name' => $s->name,
                'slug' => $s->slug,
                'description' => $s->description,
                'type' => $s->type,
                'price' => (float) $s->price,
                'currency' => $s->currency,
                'duration_minutes' => $s->duration_minutes,
                'stock_quantity' => $s->stock_quantity,
                'is_active' => $s->is_active,
                'is_bookable' => $s->is_bookable,
            ]),
        ]);
    }

    /**
     * Create vendor service.
     * POST /api/v1/vendor-hub/services
     */
    public function storeService(Request $request): JsonResponse
    {
        $vendor = $this->vendorHubService->getVendorForUser($request->user(), true);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['nullable', 'integer', 'exists:vendor_categories,id'],
            'type' => ['nullable', 'string', 'in:service,product'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_bookable' => ['nullable', 'boolean'],
        ]);

        $service = $this->vendorHubService->createService($vendor, $validated);

        return response()->json([
            'message' => 'Service created successfully.',
            'data' => [
                'uuid' => $service->uuid,
                'name' => $service->name,
                'price' => (float) $service->price,
                'currency' => $service->currency,
                'is_active' => $service->is_active,
            ],
        ], 201);
    }

    /**
     * Update vendor service.
     * PUT /api/v1/vendor-hub/services/{serviceUuid}
     */
    public function updateService(Request $request, string $serviceUuid): JsonResponse
    {
        $vendor = $this->vendorHubService->getVendorForUser($request->user(), true);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['nullable', 'string', 'in:service,product'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'is_active' => ['nullable', 'boolean'],
            'is_bookable' => ['nullable', 'boolean'],
        ]);

        $service = $this->vendorHubService->updateService($vendor, $serviceUuid, $validated);

        return response()->json([
            'message' => 'Service updated successfully.',
            'data' => [
                'uuid' => $service->uuid,
                'name' => $service->name,
                'price' => (float) $service->price,
                'is_active' => $service->is_active,
            ],
        ]);
    }

    /**
     * Delete vendor service.
     * DELETE /api/v1/vendor-hub/services/{serviceUuid}
     */
    public function destroyService(Request $request, string $serviceUuid): JsonResponse
    {
        $vendor = $this->vendorHubService->getVendorForUser($request->user(), true);
        $this->vendorHubService->deleteService($vendor, $serviceUuid);

        return response()->json([
            'message' => 'Service deleted successfully.',
        ]);
    }

    /**
     * List incoming vendor bookings.
     * GET /api/v1/vendor-hub/bookings
     */
    public function bookings(Request $request): JsonResponse
    {
        $vendor = $this->vendorHubService->getVendorForUser($request->user(), true);

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,confirmed,in_progress,completed,cancelled'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $paginator = $this->vendorHubService->listBookings($vendor, $validated);

        return response()->json([
            'data' => collect($paginator->items())->map(fn ($booking) => [
                'uuid' => $booking->uuid,
                'reference' => $booking->reference,
                'type' => $booking->type,
                'status' => $booking->status,
                'payment_status' => $booking->payment_status,
                'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
                'customer' => [
                    'name' => $booking->user->first_name.' '.$booking->user->last_name,
                    'phone' => $booking->user->phone,
                    'email' => $booking->user->email,
                ],
                'vehicle' => $booking->vehicle ? [
                    'make' => $booking->vehicle->make,
                    'model' => $booking->vehicle->model,
                    'year' => $booking->vehicle->year,
                    'plate_number' => $booking->vehicle->plate_number,
                ] : null,
                'total' => (float) $booking->total,
                'currency' => $booking->currency,
                'items' => $booking->items->map(fn ($i) => [
                    'name' => $i->name,
                    'quantity' => $i->quantity,
                    'line_total' => (float) $i->line_total,
                ]),
                'customer_note' => $booking->customer_note,
                'vendor_note' => $booking->vendor_note,
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
     * Update booking status.
     * PATCH /api/v1/vendor-hub/bookings/{bookingUuid}/status
     */
    public function updateBookingStatus(Request $request, string $bookingUuid): JsonResponse
    {
        $vendor = $this->vendorHubService->getVendorForUser($request->user(), true);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:confirmed,in_progress,completed,cancelled'],
            'vendor_note' => ['nullable', 'string', 'max:500'],
        ]);

        $booking = $this->vendorHubService->updateBookingStatus(
            $vendor,
            $bookingUuid,
            $validated['status'],
            $validated['vendor_note'] ?? null,
        );

        return response()->json([
            'message' => "Booking status updated to {$booking->status}.",
            'data' => [
                'uuid' => $booking->uuid,
                'reference' => $booking->reference,
                'status' => $booking->status,
                'completed_at' => $booking->completed_at?->toIso8601String(),
                'cancelled_at' => $booking->cancelled_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Reply to review.
     * POST /api/v1/vendor-hub/reviews/{reviewUuid}/reply
     */
    public function replyToReview(Request $request, string $reviewUuid): JsonResponse
    {
        $vendor = $this->vendorHubService->getVendorForUser($request->user(), true);

        $validated = $request->validate([
            'reply' => ['required', 'string', 'min:3', 'max:1500'],
        ]);

        $review = $this->vendorHubService->replyToReview($vendor, $reviewUuid, $validated['reply']);

        return response()->json([
            'message' => 'Reply posted successfully.',
            'data' => [
                'uuid' => $review->uuid,
                'vendor_reply' => $review->vendor_reply,
                'vendor_replied_at' => $review->vendor_replied_at->toIso8601String(),
            ],
        ]);
    }
}
