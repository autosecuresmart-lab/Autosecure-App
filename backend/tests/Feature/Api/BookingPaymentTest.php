<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCategory;
use App\Models\VendorService;
use App\Models\VendorSettlement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $vendorOwner;
    protected Vendor $vendor;
    protected VendorService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create();
        $this->vendorOwner = User::factory()->create();

        $category = VendorCategory::create([
            'slug' => 'auto-mechanic',
            'name' => 'Auto Mechanic',
            'icon' => 'wrench',
            'is_active' => true,
        ]);

        $this->vendor = Vendor::create([
            'owner_user_id' => $this->vendorOwner->id,
            'primary_category_id' => $category->id,
            'slug' => 'apex-precision-care',
            'business_name' => 'Apex Precision Care',
            'trading_name' => 'Apex AutoCare',
            'phone' => '+2348011223344',
            'email' => 'apex@autosecure.ng',
            'address_line' => '10 Admiralty Way, Lekki',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'verification_status' => Vendor::STATUS_VERIFIED,
            'is_active' => true,
            'is_accepting_bookings' => true,
            'commission_percent' => 7.5,
        ]);

        $this->service = VendorService::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Full Engine Diagnostics & Tune-up',
            'price' => 20000.0,
            'currency' => 'NGN',
            'is_active' => true,
            'is_bookable' => true,
        ]);
    }

    public function test_can_initialize_booking_payment(): void
    {
        $booking = Booking::create([
            'reference' => Booking::generateReference(),
            'user_id' => $this->customer->id,
            'vendor_id' => $this->vendor->id,
            'type' => Booking::TYPE_BOOKING,
            'status' => Booking::STATUS_PENDING,
            'payment_status' => Booking::PAYMENT_UNPAID,
            'fulfilment' => 'in_store',
            'scheduled_at' => now()->addDays(2),
            'subtotal' => 20000.0,
            'total' => 20000.0,
            'commission_percent' => 7.5,
            'commission_amount' => 1500.0,
            'currency' => 'NGN',
        ]);

        $response = $this->actingAs($this->customer)->postJson('/api/v1/payments/initialize', [
            'purpose' => 'booking',
            'booking_uuid' => $booking->uuid,
            'channel' => 'card',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.payment.amount', 20000)
            ->assertJsonPath('data.payment.purpose', 'booking')
            ->assertJsonPath('data.payment.status', 'pending');
    }

    public function test_can_verify_booking_payment_and_generate_vendor_settlement(): void
    {
        $booking = Booking::create([
            'reference' => Booking::generateReference(),
            'user_id' => $this->customer->id,
            'vendor_id' => $this->vendor->id,
            'type' => Booking::TYPE_BOOKING,
            'status' => Booking::STATUS_PENDING,
            'payment_status' => Booking::PAYMENT_UNPAID,
            'fulfilment' => 'in_store',
            'scheduled_at' => now()->addDays(2),
            'subtotal' => 40000.0,
            'total' => 40000.0,
            'commission_percent' => 7.5,
            'commission_amount' => 3000.0,
            'currency' => 'NGN',
        ]);

        $init = $this->actingAs($this->customer)->postJson('/api/v1/payments/initialize', [
            'purpose' => 'booking',
            'booking_uuid' => $booking->uuid,
        ]);

        $reference = $init->json('data.payment.reference');

        $verify = $this->actingAs($this->customer)->postJson('/api/v1/payments/verify', [
            'reference' => $reference,
        ]);

        $verify->assertOk()
            ->assertJsonPath('data.status', 'successful');

        $freshBooking = $booking->fresh();
        $this->assertEquals(Booking::PAYMENT_PAID, $freshBooking->payment_status);
        $this->assertEquals(Booking::STATUS_CONFIRMED, $freshBooking->status);

        // Check VendorSettlement record
        $settlement = VendorSettlement::where('booking_id', $booking->id)->first();
        $this->assertNotNull($settlement);
        $this->assertEquals($this->vendor->id, $settlement->vendor_id);
        $this->assertEquals(40000.0, (float) $settlement->gross_amount);
        $this->assertEquals(7.5, (float) $settlement->commission_percent);
        $this->assertEquals(3000.0, (float) $settlement->commission_amount);
        $this->assertEquals(37000.0, (float) $settlement->vendor_amount);
        $this->assertEquals(VendorSettlement::STATUS_PENDING, $settlement->status);
    }

    public function test_can_list_and_view_payment_history_and_receipt(): void
    {
        $booking = Booking::create([
            'reference' => Booking::generateReference(),
            'user_id' => $this->customer->id,
            'vendor_id' => $this->vendor->id,
            'type' => Booking::TYPE_BOOKING,
            'status' => Booking::STATUS_PENDING,
            'payment_status' => Booking::PAYMENT_UNPAID,
            'scheduled_at' => now()->addDays(2),
            'subtotal' => 20000.0,
            'total' => 20000.0,
            'currency' => 'NGN',
        ]);

        $init = $this->actingAs($this->customer)->postJson('/api/v1/payments/initialize', [
            'purpose' => 'booking',
            'booking_uuid' => $booking->uuid,
        ]);
        $paymentUuid = $init->json('data.payment.uuid');
        $reference = $init->json('data.payment.reference');

        $this->actingAs($this->customer)->postJson('/api/v1/payments/verify', [
            'reference' => $reference,
        ]);

        // List
        $list = $this->actingAs($this->customer)->getJson('/api/v1/payments');
        $list->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $paymentUuid);

        // Show single receipt
        $show = $this->actingAs($this->customer)->getJson("/api/v1/payments/{$paymentUuid}");
        $show->assertOk()
            ->assertJsonPath('data.uuid', $paymentUuid)
            ->assertJsonPath('data.amount', 20000)
            ->assertJsonPath('data.booking.vendor_name', 'Apex Precision Care');

        // Other user cannot access receipt
        $stranger = User::factory()->create();
        $unauthorized = $this->actingAs($stranger)->getJson("/api/v1/payments/{$paymentUuid}");
        $unauthorized->assertForbidden();
    }

    public function test_can_process_booking_payment_refund(): void
    {
        $booking = Booking::create([
            'reference' => Booking::generateReference(),
            'user_id' => $this->customer->id,
            'vendor_id' => $this->vendor->id,
            'type' => Booking::TYPE_BOOKING,
            'status' => Booking::STATUS_PENDING,
            'payment_status' => Booking::PAYMENT_UNPAID,
            'scheduled_at' => now()->addDays(2),
            'subtotal' => 20000.0,
            'total' => 20000.0,
            'currency' => 'NGN',
        ]);

        $init = $this->actingAs($this->customer)->postJson('/api/v1/payments/initialize', [
            'purpose' => 'booking',
            'booking_uuid' => $booking->uuid,
        ]);
        $paymentUuid = $init->json('data.payment.uuid');
        $reference = $init->json('data.payment.reference');

        $this->actingAs($this->customer)->postJson('/api/v1/payments/verify', [
            'reference' => $reference,
        ]);

        // Request refund
        $refund = $this->actingAs($this->customer)->postJson("/api/v1/payments/{$paymentUuid}/refund", [
            'reason' => 'Vendor cancelled appointment due to power outage',
        ]);

        $refund->assertOk()
            ->assertJsonPath('data.status', 'refunded');

        $freshBooking = $booking->fresh();
        $this->assertEquals(Booking::PAYMENT_REFUNDED, $freshBooking->payment_status);
        $this->assertEquals(Booking::STATUS_CANCELLED, $freshBooking->status);

        $settlement = VendorSettlement::where('booking_id', $booking->id)->first();
        $this->assertEquals(VendorSettlement::STATUS_REVERSED, $settlement->status);
    }
}
