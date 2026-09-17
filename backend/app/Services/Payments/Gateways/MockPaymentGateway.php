<?php

namespace App\Services\Payments\Gateways;

use App\Models\Payment;
use App\Services\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MockPaymentGateway implements PaymentGatewayInterface
{
    public function initialize(Payment $payment, array $options = []): array
    {
        $gatewayRef = 'MOCK-GW-'.Str::upper(Str::random(10));
        $accessCode = 'ac_'.Str::lower(Str::random(12));

        return [
            'reference' => $payment->reference,
            'gateway_reference' => $gatewayRef,
            'checkout_url' => "https://checkout.autosecure.ng/pay/{$payment->reference}?code={$accessCode}",
            'access_code' => $accessCode,
            'channel' => $options['channel'] ?? 'card',
            'instructions' => 'Mock sandbox payment gateway initialized for testing.',
        ];
    }

    public function verify(string $reference): array
    {
        // Fail if reference contains 'FAIL' or 'REJECT'
        $isFailure = str_contains(strtoupper($reference), 'FAIL') || str_contains(strtoupper($reference), 'REJECT');

        return [
            'status' => $isFailure ? Payment::STATUS_FAILED : Payment::STATUS_SUCCESSFUL,
            'reference' => $reference,
            'gateway_reference' => 'MOCK-VERIFY-'.Str::upper(Str::random(8)),
            'amount' => 0.0, // Overwritten by actual payment record amount in PaymentService
            'currency' => 'NGN',
            'channel' => 'card',
            'paid_at' => $isFailure ? null : now()->toIso8601String(),
            'raw' => [
                'gateway' => 'mock',
                'gateway_response' => $isFailure ? 'Simulated transaction declined' : 'Approved',
                'simulated_at' => now()->toIso8601String(),
            ],
        ];
    }

    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null): array
    {
        $refundRef = 'REF-MOCK-'.Str::upper(Str::random(8));

        return [
            'status' => Payment::STATUS_REFUNDED,
            'refund_reference' => $refundRef,
            'amount' => $amount ?? (float) $payment->amount,
            'raw' => [
                'gateway' => 'mock',
                'reason' => $reason ?? 'Customer requested refund',
                'processed_at' => now()->toIso8601String(),
            ],
        ];
    }

    public function validateWebhook(Request $request): array
    {
        $payload = $request->all();
        $reference = $payload['reference'] ?? ($payload['data']['reference'] ?? 'UNKNOWN');
        $status = ($payload['event'] ?? '') === 'charge.success' || ($payload['status'] ?? '') === 'successful'
            ? Payment::STATUS_SUCCESSFUL
            : Payment::STATUS_FAILED;

        return [
            'event' => $payload['event'] ?? 'charge.completed',
            'reference' => $reference,
            'status' => $status,
            'amount' => (float) ($payload['amount'] ?? 0),
            'currency' => $payload['currency'] ?? 'NGN',
            'raw' => $payload,
        ];
    }
}
