<?php

namespace Tests\Feature\Api;

use App\Models\Review;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCategory;
use App\Models\VendorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $vendorOwner;

    protected VendorCategory $category;

    protected Vendor $verifiedVendor;

    protected Vendor $pendingVendor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->vendorOwner = User::factory()->create();

        $this->category = VendorCategory::create([
            'name' => 'Mechanics',
            'slug' => 'mechanics',
            'description' => 'Certified automobile repair technicians',
            'icon' => 'wrench',
            'commission_percent' => 10.0,
        ]);

        // Verified and publicly listed vendor
        $this->verifiedVendor = Vendor::create([
            'owner_user_id' => $this->vendorOwner->id,
            'vendor_category_id' => $this->category->id,
            'business_name' => 'Apex Precision AutoCare',
            'trading_name' => 'Apex Auto',
            'slug' => 'apex-precision-autocare',
            'email' => 'apex@example.com',
            'phone' => '+2348011112222',
            'description' => 'Expert German & Japanese car diagnostics and repair',
            'address_line' => '14 Admiralty Way',
            'city' => 'Lekki',
            'state' => 'Lagos',
            'status' => Vendor::STATUS_VERIFIED,
            'is_publicly_visible' => true,
            'verified_at' => now(),
            'rating_average' => 4.8,
            'rating_count' => 15,
        ]);

        // Unverified / Pending vendor
        $this->pendingVendor = Vendor::create([
            'owner_user_id' => User::factory()->create()->id,
            'vendor_category_id' => $this->category->id,
            'business_name' => 'Pending Garage',
            'slug' => 'pending-garage',
            'status' => Vendor::STATUS_PENDING,
            'is_publicly_visible' => false,
        ]);

        // Add services to verified vendor
        VendorService::create([
            'vendor_id' => $this->verifiedVendor->id,
            'vendor_category_id' => $this->category->id,
            'name' => 'Full Engine Diagnostic',
            'slug' => 'full-engine-diagnostic',
            'description' => 'Comprehensive ECU & sensor fault scanning',
            'type' => 'service',
            'price' => 25000,
            'currency' => 'NGN',
            'duration_minutes' => 60,
            'is_active' => true,
            'is_bookable' => true,
        ]);
    }

    public function test_can_list_categories(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/finder/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'uuid', 'name', 'slug', 'description', 'icon', 'vendors_count'],
                ],
            ]);

        $this->assertEquals(1, $response->json('data.0.vendors_count'));
    }

    public function test_only_verified_vendors_are_returned_in_search(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/finder/vendors');

        $response->assertOk();
        $vendors = $response->json('data');

        $this->assertCount(1, $vendors);
        $this->assertEquals('Apex Precision AutoCare', $vendors[0]['business_name']);
        $this->assertTrue($vendors[0]['is_verified']);
    }

    public function test_can_search_vendors_by_keyword(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/finder/vendors?search=Diagnostic');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));

        $emptyResponse = $this->actingAs($this->user)->getJson('/api/v1/finder/vendors?search=NonExistentKeyword');
        $emptyResponse->assertOk();
        $this->assertCount(0, $emptyResponse->json('data'));
    }

    public function test_can_filter_vendors_by_category(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/finder/vendors?category=mechanics');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_view_vendor_profile_with_services_and_reviews(): void
    {
        Review::create([
            'user_id' => $this->user->id,
            'vendor_id' => $this->verifiedVendor->id,
            'rating' => 5,
            'title' => 'Top notch service',
            'comment' => 'They diagnosed my car quickly and transparently.',
            'is_verified_booking' => true,
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/finder/vendors/{$this->verifiedVendor->uuid}");

        $response->assertOk()
            ->assertJsonPath('data.business_name', 'Apex Precision AutoCare')
            ->assertJsonCount(1, 'data.services')
            ->assertJsonCount(1, 'data.recent_reviews')
            ->assertJsonPath('data.recent_reviews.0.title', 'Top notch service');
    }

    public function test_cannot_view_unverified_vendor_profile_via_finder(): void
    {
        $response = $this->actingAs($this->user)->getJson("/api/v1/finder/vendors/{$this->pendingVendor->uuid}");

        $response->assertNotFound();
    }

    public function test_can_submit_customer_review_and_recalculate_ratings(): void
    {
        $response = $this->actingAs($this->user)->postJson("/api/v1/finder/vendors/{$this->verifiedVendor->uuid}/reviews", [
            'rating' => 5,
            'title' => 'Excellent work',
            'comment' => 'Very satisfied with the speed and honesty of the mechanics.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.title', 'Excellent work');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->user->id,
            'vendor_id' => $this->verifiedVendor->id,
            'rating' => 5,
        ]);

        $this->verifiedVendor->refresh();
        $this->assertEquals(1, $this->verifiedVendor->rating_count);
        $this->assertEquals(5.0, (float) $this->verifiedVendor->rating_average);
    }
}
