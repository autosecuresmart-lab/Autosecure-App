<?php

namespace Database\Seeders;

use App\Models\VendorCategory;
use Illuminate\Database\Seeder;

/**
 * Initial Finder categories (proposal section 07).
 *
 * Commission is intentionally left null so the platform default in
 * config('autosecure.finder.default_commission_percent') applies until the
 * business approves per-category rates.
 */
class VendorCategorySeeder extends Seeder
{
    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $categories = [
        [
            'slug' => 'auto-parts-sellers',
            'name' => 'Auto Parts Sellers',
            'description' => 'Parts retailers and suppliers. Searchable by part, vehicle compatibility, location and price.',
            'icon' => 'parts',
            'sort_order' => 1,
        ],
        [
            'slug' => 'car-washes',
            'name' => 'Car Washes',
            'description' => 'Wash and detailing packages with bookable slots.',
            'icon' => 'wash',
            'sort_order' => 2,
        ],
        [
            'slug' => 'mechanics',
            'name' => 'Mechanics',
            'description' => 'Repair and maintenance workshops, searchable by specialty and supported vehicle types.',
            'icon' => 'mechanic',
            'sort_order' => 3,
        ],
    ];

    public function run(): void
    {
        foreach ($this->categories as $category) {
            VendorCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category + ['is_active' => true],
            );
        }

        $this->command?->info('Vendor categories: '.count($this->categories).' seeded.');
    }
}
