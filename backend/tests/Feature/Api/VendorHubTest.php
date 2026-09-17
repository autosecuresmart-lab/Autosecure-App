<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCategory;
use App\Models\VendorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorHubTest extends TestCase
{
    use RefreshDatabase;

    protected User $vendorUser;

    protected User $customerUser;

    protected VendorCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendorUser = User::factory()->create();
        $this->customerUser = User::factory()->create();

        $this->category = VendorCategory::create([
            'name' => 'Auto Detailers & Car Wash',
            'slug' => 'car-wash',
            'description' => 'Vehicle cleaning and detailing',
            'commission_percent' => 8.0,
        ]);
    }

    public function test_user_can_register_as_vendor(): void
    {
        $response = $this->actingAs($this->vendorUser)->postJson('/api/v1/vendor-hub/register', [
            'business_name' => 'Sparkle Hydro Spa',
            'category_id' => $this->category->id,
            'phone' => '+2348099998888',
            'city' => 'Victoria Island',
            'state' => 'Lagos',
            'description' => 'Premium ceramic wash and interior sanitization',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.business_name', 'Sparkle Hydro Spa')
            ->assertJsonPath('data.status', Vendor::STATUS_PENDING)
            ->assertJsonPath('data.is_publicly_visible', false);

        $this->assertDatabaseHas('vendors', [
            'owner_user_id' => $this->vendorUser->id,
            'business_name' => 'Sparkle Hydro Spa',
            'status' => Vendor::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('vendor_verifications', [
            'status' => 'pending',
        ]);
    }

    public function test_vendor_can_view_and_update_profile(): void
    {
        $vendor = Vendor::create([
            'owner_user_id' => $this->vendorUser->id,
            'vendor_category_id' => $this->category->id,
            'business_name' => 'Sparkle Hydro Spa',
            'slug' => 'sparkle-hydro-spa',
            'status' => Vendor::STATUS_VERIFIED,
            'is_publicly_visible' => true,
        ]);

        $getRes = $this->actingAs($this->vendorUser)->getJson('/api/v1/vendor-hub/profile');
        $getRes->assertOk()
            ->assertJsonPath('data.business_name', 'Sparkle Hydro Spa');

        $updateRes = $this->actingAs($this->vendorUser)->putJson('/api/v1/vendor-hub/profile', [
            'description' => 'Updated high-pressure steam wash services.',
            'city' => 'Lekki Phase 1',
        ]);

        $updateRes->assertOk()
            ->assertJsonPath('data.business_name', 'Sparkle Hydro Spa');

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'city' => 'Lekki Phase 1',
        ]);
    }

    public function test_vendor_can_manage_services(): void
    {
        $vendor = Vendor::create([
            'owner_user_id' => $this->vendorUser->id,
            'vendor_category_id' => $this->category->id,
            'business_name' => 'Sparkle Hydro Spa',
            'slug' => 'sparkle-hydro-spa',
            'status' => Vendor::STATUS_VERIFIED,
            'is_publicly_visible' => true,
        ]);

        // Create service
        $createRes = $this->actingAs($this->vendorUser)->postJson('/api/v1/vendor-hub/services', [
            'name' => 'Underbody Steam Wash',
            'price' => 15000,
            'currency' => 'NGN',
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('data.name', 'Underbody Steam Wash')
            ->assertJsonPath('data.price', 15000);

        $serviceUuid = $createRes->json('data.uuid');

        // List services
        $listRes = $this->actingAs($this->vendorUser)->getJson('/api/v1/vendor-hub/services');
        $listRes->assertOk()->assertJsonCount(1, 'data');

        // Update service
        $updateRes = $this->actingAs($this->vendorUser)->putJson("/api/v1/vendor-hub/services/{$serviceUuid}", [
            'price' => 18000,
        ]);
        $updateRes->assertOk()->assertJsonPath('data.price', 18000);

        // Delete service
        $delRes = $this->actingAs($this->vendorUser)->deleteJson("/api/v1/vendor-hub/services/{$serviceUuid}");
        $delRes->assertOk();

        $this->assertSoftDeleted('vendor_services', ['uuid' => $serviceUuid]);
    }

    public function test_vendor_can_view_incoming_bookings_and_update_status(): void
    {
        $vendor = Vendor::create([
            'owner_user_id' => $this->vendorUser->id,
            'vendor_category_id' => $this->category->id,
            'business_name' => 'Sparkle Hydro Spa',
            'slug' => 'sparkle-hydro-spa',
            'status' => Vendor::STATUS_VERIFIED,
            'is_publicly_visible' => true,
        ]);

        $booking = Booking::create([
            'reference' => 'AS-BK-INCOMING',
            'user_id' => $this->customerUser->id,
            'vendor_id' => $vendor->id,
            'type' => Booking::TYPE_BOOKING,
            'status' => Booking::STATUS_PENDING,
            'scheduled_at' => now()->addDays(2),
            'total' => 15000,
        ]);

        $listRes = $this->actingAs($this->vendorUser)->getJson('/api/v1/vendor-hub/bookings');
        $listRes->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', 'AS-BK-INCOMING');

        $statusRes = $this->actingAs($this->vendorUser)->patchJson("/api/v1/vendor-hub/bookings/{$booking->uuid}/status", [
            'status' => Booking::STATUS_CONFIRMED,
            'vendor_note' => 'Bay 2 reserved for your arrival.',
        ]);

        $statusRes->assertOk()
            ->assertJsonPath('data.status', Booking::STATUS_CONFIRMED);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => Booking::STATUS_CONFIRMED,
            'vendor_note' => 'Bay 2 reserved for your arrival.',
        ]);
    }

    public function test_vendor_can_reply_to_review(): void
    {
        $vendor = Vendor::create([
            'owner_user_id' => $this->vendorUser->id,
            'vendor_category_id' => $this->category->id,
            'business_name' => 'Sparkle Hydro Spa',
            'slug' => 'sparkle-hydro-spa',
            'status' => Vendor::STATUS_VERIFIED,
            'is_publicly_visible' => true,
        ]);

        $review = Review::create([
            'user_id' => $this->customerUser->id,
            'vendor_id' => $vendor->id,
            'rating' => 5,
            'comment' => 'Great car wash experience!',
            'is_published' => true,
        ]);

        $replyRes = $this->actingAs($this->vendorUser)->postJson("/api/v1/vendor-hub/reviews/{$review->uuid}/reply", [
            'reply' => 'Thank you for choosing Sparkle Hydro Spa! Look forward to seeing you again.',
        ]);

        $replyRes->assertOk()
            ->assertJsonPath('data.vendor_reply', 'Thank you for choosing Sparkle Hydro Spa! Look forward to seeing you again.');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'vendor_reply' => 'Thank you for choosing Sparkle Hydro Spa! Look forward to seeing you again.',
        ]);
    }
}
