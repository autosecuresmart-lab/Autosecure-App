<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Vendor;
use App\Models\VendorCategory;
use App\Models\VendorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $otherCustomer;

    protected User $vendorOwner;

    protected Vehicle $vehicle;

    protected Vendor $vendor;

    protected VendorService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create();
        $this->otherCustomer = User::factory()->create();
        $this->vendorOwner = User::factory()->create();

        $this->vehicle = Vehicle::factory()->create([
            'user_id' => $this->customer->id,
            'make' => 'Toyota',
            'model' => 'Corolla',
            'plate_number' => 'ABC-123DE',
        ]);

        $category = VendorCategory::create([
            'name' => 'Mechanics',
            'slug' => 'mechanics',
            'commission_percent' => 10.0,
        ]);

        $this->vendor = Vendor::create([
            'owner_user_id' => $this->vendorOwner->id,
            'vendor_category_id' => $category->id,
            'business_name' => 'Lekki Master Mechanics',
            'slug' => 'lekki-master-mechanics',
            'phone' => '+2348033334444',
            'city' => 'Lekki',
            'state' => 'Lagos',
            'status' => Vendor::STATUS_VERIFIED,
            'is_publicly_visible' => true,
            'commission_percent' => 10.0,
        ]);

        $this->service = VendorService::create([
            'vendor_id' => $this->vendor->id,
            'vendor_category_id' => $category->id,
            'name' => 'Complete Brake Pad Replacement',
            'slug' => 'complete-brake-pad-replacement',
            'price' => 35000,
            'currency' => 'NGN',
            'duration_minutes' => 90,
            'is_active' => true,
            'is_bookable' => true,
        ]);
    }

    public function test_customer_can_create_a_booking(): void
    {
        $scheduledAt = now()->addDays(2)->setHour(10)->setMinute(0)->toIso8601String();

        $response = $this->actingAs($this->customer)->postJson('/api/v1/bookings', [
            'vendor_uuid' => $this->vendor->uuid,
            'vehicle_uuid' => $this->vehicle->uuid,
            'scheduled_at' => $scheduledAt,
            'fulfilment' => 'in_store',
            'customer_note' => 'Please inspect rear disc as well.',
            'items' => [
                [
                    'service_uuid' => $this->service->uuid,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', Booking::STATUS_PENDING)
            ->assertJsonPath('data.payment_status', Booking::PAYMENT_UNPAID)
            ->assertJsonPath('data.total', 35000)
            ->assertJsonPath('data.vehicle.plate_number', 'ABC-123DE');

        $this->assertDatabaseHas('bookings', [
            'user_id' => $this->customer->id,
            'vendor_id' => $this->vendor->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => Booking::STATUS_PENDING,
            'total' => 35000,
        ]);
    }

    public function test_cannot_book_vehicle_customer_does_not_own_or_have_access_to(): void
    {
        $otherVehicle = Vehicle::factory()->create([
            'user_id' => $this->otherCustomer->id,
        ]);

        $response = $this->actingAs($this->customer)->postJson('/api/v1/bookings', [
            'vendor_uuid' => $this->vendor->uuid,
            'vehicle_uuid' => $otherVehicle->uuid,
            'scheduled_at' => now()->addDay()->toIso8601String(),
            'items' => [
                ['service_uuid' => $this->service->uuid],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['vehicle_uuid']);
    }

    public function test_cannot_book_unverified_vendor(): void
    {
        $unverified = Vendor::create([
            'owner_user_id' => User::factory()->create()->id,
            'business_name' => 'Unverified Shop',
            'slug' => 'unverified-shop',
            'status' => Vendor::STATUS_PENDING,
            'is_publicly_visible' => false,
        ]);

        $response = $this->actingAs($this->customer)->postJson('/api/v1/bookings', [
            'vendor_uuid' => $unverified->uuid,
            'scheduled_at' => now()->addDay()->toIso8601String(),
            'items' => [
                ['service_uuid' => $this->service->uuid],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['vendor_uuid']);
    }

    public function test_customer_can_list_their_bookings(): void
    {
        Booking::create([
            'reference' => 'AS-BK-111111',
            'user_id' => $this->customer->id,
            'vendor_id' => $this->vendor->id,
            'vehicle_id' => $this->vehicle->id,
            'type' => Booking::TYPE_BOOKING,
            'status' => Booking::STATUS_PENDING,
            'scheduled_at' => now()->addDay(),
            'total' => 35000,
        ]);

        $response = $this->actingAs($this->customer)->getJson('/api/v1/bookings');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', 'AS-BK-111111');
    }

    public function test_customer_cannot_view_another_customers_booking(): void
    {
        $booking = Booking::create([
            'reference' => 'AS-BK-SECRET',
            'user_id' => $this->customer->id,
            'vendor_id' => $this->vendor->id,
            'type' => Booking::TYPE_BOOKING,
            'status' => Booking::STATUS_PENDING,
            'scheduled_at' => now()->addDay(),
            'total' => 35000,
        ]);

        $response = $this->actingAs($this->otherCustomer)->getJson("/api/v1/bookings/{$booking->uuid}");

        $response->assertForbidden();
    }

    public function test_customer_can_cancel_pending_booking(): void
    {
        $booking = Booking::create([
            'reference' => 'AS-BK-CANCEL',
            'user_id' => $this->customer->id,
            'vendor_id' => $this->vendor->id,
            'type' => Booking::TYPE_BOOKING,
            'status' => Booking::STATUS_PENDING,
            'scheduled_at' => now()->addDay(),
            'total' => 35000,
        ]);

        $response = $this->actingAs($this->customer)->postJson("/api/v1/bookings/{$booking->uuid}/cancel", [
            'reason' => 'Schedule conflict',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', Booking::STATUS_CANCELLED)
            ->assertJsonPath('data.cancellation_reason', 'Schedule conflict');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => Booking::STATUS_CANCELLED,
        ]);
    }
}
