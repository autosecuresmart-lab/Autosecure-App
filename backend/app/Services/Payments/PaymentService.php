<?php

namespace App\Services\Payments;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Finder\BookingService;
use App\Services\Subscriptions\SubscriptionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PaymentService
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager,
        protected SubscriptionService $subscriptionService,
        protected BookingService $bookingService,
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * Initialize a payment intent.
     *
     * @param  User  $user
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function initialize(User $user, array $data): array
    {
        // 1. Idempotency check
        if (! empty($data['idempotency_key'])) {
            $existing = Payment::query()
                ->where('user_id', $user->id)
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();

            if ($existing) {
                if ($existing->isSuccessful()) {
                    return [
                        'status' => 'already_successful',
                        'payment' => $this->formatPayment($existing),
                        'instructions' => 'Payment already verified and completed.',
                    ];
                }

                // If still pending, re-generate gateway instructions or return existing
                $gateway = $this->gatewayManager->driver($existing->gateway);
                $initData = $gateway->initialize($existing, $data);

                return [
                    'status' => 'pending',
                    'payment' => $this->formatPayment($existing),
                    'checkout' => $initData,
                ];
            }
        }

        return DB::transaction(function () use ($user, $data) {
            $purpose = $data['purpose'] ?? Payment::PURPOSE_SUBSCRIPTION;
            $amount = 0.00;
            $currency = 'NGN';
            $bookingId = null;
            $subscriptionPlanId = null;
            $payload = [];

            if ($purpose === Payment::PURPOSE_SUBSCRIPTION) {
                $planUuid = $data['plan_uuid'] ?? null;
                $plan = SubscriptionPlan::query()->where('uuid', $planUuid)->first();

                if (! $plan || $plan->is_free) {
                    throw ValidationException::withMessages([
                        'plan_uuid' => ['Invalid or free subscription plan specified.'],
                    ]);
                }

                $amount = (float) $plan->price;
                $currency = $plan->currency ?: 'NGN';
                $subscriptionPlanId = $plan->id;
                $payload = [
                    'subscription_plan_id' => $plan->id,
                    'plan_uuid' => $plan->uuid,
                    'plan_name' => $plan->name,
                    'plan_slug' => $plan->slug,
                    'duration_days' => $plan->duration_days,
                ];
            } elseif ($purpose === Payment::PURPOSE_BOOKING || $purpose === Payment::PURPOSE_ORDER) {
                $bookingUuid = $data['booking_uuid'] ?? null;
                $booking = Booking::query()
                    ->where('uuid', $bookingUuid)
                    ->where('user_id', $user->id)
                    ->first();

                if (! $booking) {
                    throw ValidationException::withMessages([
                        'booking_uuid' => ['Booking not found or not accessible.'],
                    ]);
                }

                if ($booking->payment_status === Booking::PAYMENT_PAID) {
                    throw ValidationException::withMessages([
                        'booking_uuid' => ['This booking has already been paid for.'],
                    ]);
                }

                $amount = (float) $booking->total;
                $currency = $booking->currency ?: 'NGN';
                $bookingId = $booking->id;
                $payload = [
                    'booking_id' => $booking->id,
                    'booking_uuid' => $booking->uuid,
                    'booking_reference' => $booking->reference,
                    'vendor_id' => $booking->vendor_id,
                ];
            } else {
                throw ValidationException::withMessages([
                    'purpose' => ['Unsupported payment purpose specified.'],
                ]);
            }

            $reference = Payment::generateReference();
            $driverName = config('autosecure.payments.driver', env('PAYMENT_DRIVER', 'mock'));

            $payment = Payment::create([
                'reference' => $reference,
                'user_id' => $user->id,
                'purpose' => $purpose,
                'booking_id' => $bookingId,
                'subscription_id' => null,
                'amount' => $amount,
                'currency' => $currency,
                'status' => Payment::STATUS_PENDING,
                'channel' => $data['channel'] ?? 'card',
                'gateway' => $driverName,
                'gateway_reference' => null,
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'payload' => $payload,
            ]);

            $gateway = $this->gatewayManager->driver($driverName);
            $checkoutInfo = $gateway->initialize($payment, $data);

            $payment->update([
                'gateway_reference' => $checkoutInfo['gateway_reference'] ?? $reference,
            ]);

            $this->auditLogger->log(
                action: 'payment.initialized',
                auditable: $payment,
                actor: $user,
                context: [
                    'reference' => $payment->reference,
                    'amount' => $amount,
                    'purpose' => $purpose,
                    'gateway' => $driverName,
                ]
            );

            return [
                'status' => 'initialized',
                'payment' => $this->formatPayment($payment),
                'checkout' => $checkoutInfo,
            ];
        });
    }

    /**
     * Server-side verification of payment reference.
     */
    public function verify(string $reference): Payment
    {
        $payment = Payment::query()
            ->where('reference', $reference)
            ->orWhere('gateway_reference', $reference)
            ->first();

        if (! $payment) {
            throw ValidationException::withMessages([
                'reference' => ['Payment reference not found.'],
            ]);
        }

        // If already successful, return idempotently
        if ($payment->isSuccessful()) {
            return $payment->load(['user', 'booking.vendor', 'subscription.plan']);
        }

        $gateway = $this->gatewayManager->driver($payment->gateway);
        $result = $gateway->verify($reference);

        if ($result['status'] === Payment::STATUS_SUCCESSFUL) {
            return DB::transaction(function () use ($payment, $result) {
                $payment->update([
                    'status' => Payment::STATUS_SUCCESSFUL,
                    'paid_at' => now(),
                    'channel' => $result['channel'] ?? $payment->channel,
                    'gateway_reference' => $result['gateway_reference'] ?? $payment->gateway_reference,
                    'payload' => array_merge($payment->payload ?? [], ['verification' => $result['raw'] ?? []]),
                ]);

                // Fulfill Purpose
                if ($payment->purpose === Payment::PURPOSE_SUBSCRIPTION) {
                    $this->subscriptionService->activateFromPayment($payment);
                } elseif ($payment->purpose === Payment::PURPOSE_BOOKING || $payment->purpose === Payment::PURPOSE_ORDER) {
                    if ($payment->booking) {
                        $this->bookingService->markAsPaid($payment->booking, $payment);
                    }
                }

                $this->auditLogger->log(
                    action: 'payment.successful',
                    auditable: $payment,
                    actor: $payment->user,
                    context: [
                        'reference' => $payment->reference,
                        'amount' => (float) $payment->amount,
                        'purpose' => $payment->purpose,
                    ]
                );

                return $payment->fresh(['user', 'booking.vendor', 'subscription.plan']);
            });
        }

        // Mark as failed
        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'failed_at' => now(),
            'failure_reason' => $result['raw']['gateway_response'] ?? 'Payment failed or declined',
            'payload' => array_merge($payment->payload ?? [], ['verification' => $result['raw'] ?? []]),
        ]);

        $this->auditLogger->log(
            action: 'payment.failed',
            auditable: $payment,
            actor: $payment->user,
            context: [
                'reference' => $payment->reference,
                'reason' => $payment->failure_reason,
            ]
        );

        return $payment->fresh(['user', 'booking', 'subscription']);
    }

    /**
     * Process a refund for a successful payment.
     */
    public function refund(Payment $payment, ?string $reason = null, ?User $actor = null): Payment
    {
        if (! $payment->isSuccessful()) {
            throw ValidationException::withMessages([
                'payment' => ['Only successful payments can be refunded.'],
            ]);
        }

        return DB::transaction(function () use ($payment, $reason, $actor) {
            $gateway = $this->gatewayManager->driver($payment->gateway);
            $refundResult = $gateway->refund($payment, (float) $payment->amount, $reason);

            $payment->update([
                'status' => Payment::STATUS_REFUNDED,
                'payload' => array_merge($payment->payload ?? [], [
                    'refund' => $refundResult,
                    'refund_reason' => $reason,
                    'refunded_at' => now()->toIso8601String(),
                ]),
            ]);

            // Reversal logic for booking or subscription
            if ($payment->booking) {
                $this->bookingService->refundBooking($payment->booking, $payment, $reason);
            }

            if ($payment->subscription) {
                $payment->subscription->update([
                    'status' => \App\Models\Subscription::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'cancellation_reason' => $reason ?? 'Payment refunded',
                ]);
            }

            $this->auditLogger->log(
                action: 'payment.refunded',
                auditable: $payment,
                actor: $actor ?? $payment->user,
                context: [
                    'reference' => $payment->reference,
                    'amount' => (float) $payment->amount,
                    'reason' => $reason,
                ]
            );

            return $payment->fresh(['user', 'booking', 'subscription']);
        });
    }

    /**
     * List user payments with pagination and filters.
     */
    public function listUserPayments(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Payment::query()
            ->where('user_id', $user->id)
            ->with(['booking.vendor', 'subscription.plan']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['purpose'])) {
            $query->where('purpose', $filters['purpose']);
        }

        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 50);

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get single payment details with ownership check.
     */
    public function getPaymentDetails(User $user, string $paymentUuid): Payment
    {
        $payment = Payment::query()
            ->where('uuid', $paymentUuid)
            ->with(['booking.vendor', 'subscription.plan'])
            ->firstOrFail();

        if ($payment->user_id !== $user->id) {
            throw new AccessDeniedHttpException('You do not have permission to view this payment receipt.');
        }

        return $payment;
    }

    /**
     * Process an incoming gateway webhook.
     */
    public function processWebhook(Request $request): Payment
    {
        $driverName = config('autosecure.payments.driver', env('PAYMENT_DRIVER', 'mock'));
        $gateway = $this->gatewayManager->driver($driverName);

        $parsed = $gateway->validateWebhook($request);
        $reference = $parsed['reference'];

        return $this->verify($reference);
    }

    /**
     * Format a payment model for API output.
     *
     * @param  Payment  $payment
     * @return array<string, mixed>
     */
    public function formatPayment(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'uuid' => $payment->uuid,
            'reference' => $payment->reference,
            'purpose' => $payment->purpose,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'status' => $payment->status,
            'channel' => $payment->channel,
            'gateway' => $payment->gateway,
            'gateway_reference' => $payment->gateway_reference,
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'failed_at' => $payment->failed_at?->toIso8601String(),
            'failure_reason' => $payment->failure_reason,
            'booking' => $payment->booking ? [
                'uuid' => $payment->booking->uuid,
                'reference' => $payment->booking->reference,
                'status' => $payment->booking->status,
                'vendor_name' => $payment->booking->vendor?->business_name,
            ] : null,
            'subscription' => $payment->subscription ? [
                'uuid' => $payment->subscription->uuid,
                'status' => $payment->subscription->status,
                'plan_name' => $payment->subscription->plan?->name,
            ] : null,
            'created_at' => $payment->created_at->toIso8601String(),
        ];
    }
}
