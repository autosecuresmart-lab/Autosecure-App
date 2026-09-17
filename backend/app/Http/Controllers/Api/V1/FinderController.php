<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Finder\BookingService;
use App\Services\Finder\FinderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinderController extends Controller
{
    public function __construct(
        protected FinderService $finderService,
        protected BookingService $bookingService,
    ) {}

    /**
     * List all active categories.
     * GET /api/v1/finder/categories
     */
    public function categories(): JsonResponse
    {
        $categories = $this->finderService->listCategories();

        return response()->json([
            'data' => $categories->map(fn ($cat) => [
                'id' => $cat->id,
                'uuid' => $cat->uuid,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'description' => $cat->description,
                'icon' => $cat->icon,
                'vendors_count' => $cat->vendors_count ?? 0,
            ]),
        ]);
    }

    /**
     * Search verified vendors.
     * GET /api/v1/finder/vendors
     */
    public function vendors(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:64'],
            'city' => ['nullable', 'string', 'max:64'],
            'state' => ['nullable', 'string', 'max:64'],
            'min_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'sort' => ['nullable', 'string', 'in:rating,name,newest,reviews'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $paginator = $this->finderService->searchVendors($validated);

        return response()->json([
            'data' => collect($paginator->items())->map(fn ($vendor) => [
                'id' => $vendor->id,
                'uuid' => $vendor->uuid,
                'business_name' => $vendor->business_name,
                'trading_name' => $vendor->trading_name,
                'slug' => $vendor->slug,
                'category' => $vendor->category ? [
                    'id' => $vendor->category->id,
                    'uuid' => $vendor->category->uuid,
                    'name' => $vendor->category->name,
                    'slug' => $vendor->category->slug,
                ] : null,
                'description' => $vendor->description,
                'logo_path' => $vendor->logo_path,
                'address_line' => $vendor->address_line,
                'city' => $vendor->city,
                'state' => $vendor->state,
                'latitude' => $vendor->latitude ? (float) $vendor->latitude : null,
                'longitude' => $vendor->longitude ? (float) $vendor->longitude : null,
                'rating_average' => (float) $vendor->rating_average,
                'rating_count' => (int) $vendor->rating_count,
                'is_verified' => $vendor->status === 'verified',
                'opening_hours' => $vendor->opening_hours,
                'services_preview' => $vendor->services->take(4)->map(fn ($s) => [
                    'uuid' => $s->uuid,
                    'name' => $s->name,
                    'price' => (float) $s->price,
                    'currency' => $s->currency,
                ]),
                'starting_price' => $vendor->services->min('price') !== null ? (float) $vendor->services->min('price') : null,
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
     * Get vendor profile details.
     * GET /api/v1/finder/vendors/{vendorUuid}
     */
    public function show(string $vendorUuid): JsonResponse
    {
        $vendor = $this->finderService->getVendorDetails($vendorUuid);

        return response()->json([
            'data' => [
                'id' => $vendor->id,
                'uuid' => $vendor->uuid,
                'business_name' => $vendor->business_name,
                'trading_name' => $vendor->trading_name,
                'slug' => $vendor->slug,
                'category' => $vendor->category ? [
                    'id' => $vendor->category->id,
                    'uuid' => $vendor->category->uuid,
                    'name' => $vendor->category->name,
                    'slug' => $vendor->category->slug,
                ] : null,
                'description' => $vendor->description,
                'email' => $vendor->email,
                'phone' => $vendor->phone,
                'whatsapp' => $vendor->whatsapp,
                'logo_path' => $vendor->logo_path,
                'address_line' => $vendor->address_line,
                'city' => $vendor->city,
                'state' => $vendor->state,
                'country' => $vendor->country,
                'latitude' => $vendor->latitude ? (float) $vendor->latitude : null,
                'longitude' => $vendor->longitude ? (float) $vendor->longitude : null,
                'rating_average' => (float) $vendor->rating_average,
                'rating_count' => (int) $vendor->rating_count,
                'is_verified' => $vendor->status === 'verified',
                'opening_hours' => $vendor->opening_hours,
                'cancellation_policy' => $vendor->cancellation_policy,
                'services' => $vendor->services->map(fn ($s) => [
                    'uuid' => $s->uuid,
                    'name' => $s->name,
                    'description' => $s->description,
                    'type' => $s->type,
                    'price' => (float) $s->price,
                    'currency' => $s->currency,
                    'duration_minutes' => $s->duration_minutes,
                ]),
                'recent_reviews' => $vendor->reviews->map(fn ($r) => [
                    'uuid' => $r->uuid,
                    'rating' => $r->rating,
                    'title' => $r->title,
                    'comment' => $r->comment,
                    'user_name' => $r->user ? $r->user->first_name.' '.substr($r->user->last_name ?? '', 0, 1).'.' : 'Anonymous',
                    'is_verified_booking' => $r->is_verified_booking,
                    'created_at' => $r->created_at->toIso8601String(),
                    'vendor_reply' => $r->vendor_reply,
                    'vendor_replied_at' => $r->vendor_replied_at?->toIso8601String(),
                ]),
            ],
        ]);
    }

    /**
     * Get reviews for vendor.
     * GET /api/v1/finder/vendors/{vendorUuid}/reviews
     */
    public function reviews(Request $request, string $vendorUuid): JsonResponse
    {
        $paginator = $this->finderService->getVendorReviews($vendorUuid, $request->only(['per_page', 'page']));

        return response()->json([
            'data' => collect($paginator->items())->map(fn ($r) => [
                'uuid' => $r->uuid,
                'rating' => $r->rating,
                'title' => $r->title,
                'comment' => $r->comment,
                'user_name' => $r->user ? $r->user->first_name.' '.substr($r->user->last_name ?? '', 0, 1).'.' : 'Anonymous',
                'is_verified_booking' => $r->is_verified_booking,
                'created_at' => $r->created_at->toIso8601String(),
                'vendor_reply' => $r->vendor_reply,
                'vendor_replied_at' => $r->vendor_replied_at?->toIso8601String(),
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
     * Submit a customer review.
     * POST /api/v1/finder/vendors/{vendorUuid}/reviews
     */
    public function submitReview(Request $request, string $vendorUuid): JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'comment' => ['required', 'string', 'min:5', 'max:2000'],
            'booking_uuid' => ['nullable', 'string', 'exists:bookings,uuid'],
        ]);

        $review = $this->bookingService->submitReview($request->user(), $vendorUuid, $validated);

        return response()->json([
            'message' => 'Review submitted successfully.',
            'data' => [
                'uuid' => $review->uuid,
                'rating' => $review->rating,
                'title' => $review->title,
                'comment' => $review->comment,
                'is_verified_booking' => $review->is_verified_booking,
                'created_at' => $review->created_at->toIso8601String(),
            ],
        ], 201);
    }
}
