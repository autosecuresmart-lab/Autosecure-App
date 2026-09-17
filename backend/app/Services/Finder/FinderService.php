<?php

namespace App\Services\Finder;

use App\Models\Review;
use App\Models\Vendor;
use App\Models\VendorCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FinderService
{
    /**
     * List all categories with active publicly listed vendor counts.
     */
    public function listCategories(): Collection
    {
        return VendorCategory::query()
            ->withCount(['vendors' => function (Builder $query) {
                $query->where('status', Vendor::STATUS_VERIFIED)
                    ->where('is_publicly_visible', true)
                    ->where(function (Builder $q) {
                        $q->whereNull('subscription_expires_at')
                            ->orWhere('subscription_expires_at', '>', now());
                    });
            }])
            ->orderBy('name')
            ->get();
    }

    /**
     * Search and filter verified, publicly visible vendors.
     */
    public function searchVendors(array $filters = []): LengthAwarePaginator
    {
        $query = Vendor::query()
            ->publiclyListed()
            ->with(['category', 'services' => function ($q) {
                $q->where('is_active', true)->where('is_bookable', true);
            }]);

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['category'])) {
            $cat = $filters['category'];
            $query->where(function (Builder $q) use ($cat) {
                $q->where('vendor_category_id', $cat)
                    ->orWhereHas('category', function (Builder $cq) use ($cat) {
                        $cq->where('slug', $cat)->orWhere('name', $cat);
                    });
            });
        }

        if (! empty($filters['city'])) {
            $query->where('city', 'like', "%{$filters['city']}%");
        }

        if (! empty($filters['state'])) {
            $query->where('state', 'like', "%{$filters['state']}%");
        }

        if (! empty($filters['min_rating'])) {
            $query->where('rating_average', '>=', (float) $filters['min_rating']);
        }

        // Sorting
        $sort = $filters['sort'] ?? 'rating';
        match ($sort) {
            'name' => $query->orderBy('business_name', 'asc'),
            'newest' => $query->orderByDesc('created_at'),
            'reviews' => $query->orderByDesc('rating_count')->orderByDesc('rating_average'),
            default => $query->orderByDesc('rating_average')->orderByDesc('rating_count'),
        };

        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 50);

        return $query->paginate($perPage);
    }

    /**
     * Get vendor public profile by UUID with category, active services, and recent reviews.
     */
    public function getVendorDetails(string $uuid): Vendor
    {
        return Vendor::query()
            ->publiclyListed()
            ->where('uuid', $uuid)
            ->with([
                'category',
                'services' => function ($q) {
                    $q->where('is_active', true)->where('is_bookable', true);
                },
                'reviews' => function ($q) {
                    $q->where('is_published', true)
                        ->with('user:id,first_name,last_name,email')
                        ->latest()
                        ->limit(10);
                },
            ])
            ->firstOrFail();
    }

    /**
     * Get paginated reviews for a vendor.
     */
    public function getVendorReviews(string $vendorUuid, array $filters = []): LengthAwarePaginator
    {
        $vendor = Vendor::query()->where('uuid', $vendorUuid)->firstOrFail();

        $perPage = min(max((int) ($filters['per_page'] ?? 10), 1), 50);

        return Review::query()
            ->where('vendor_id', $vendor->id)
            ->where('is_published', true)
            ->with('user:id,first_name,last_name')
            ->latest()
            ->paginate($perPage);
    }
}
