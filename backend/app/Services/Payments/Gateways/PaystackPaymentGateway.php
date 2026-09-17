<?php

namespace App\Services\Payments\Gateways;

use App\Models\Payment;
use App\Services\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PaystackPaymentGateway implements PaymentGatewayInterface
{
    protected string $secretKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key', env('PAYSTACK_SECRET_KEY', ''));
        $this->baseUrl = config('services.paystack.base_url', 'https://api.paystack.co');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->secretKey);
    }

    public function initialize(Payment $payment, array $options = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Paystack secret key is not configured.');
        }

        // Amount in Kobo for NGN (1 NGN = 100 kobo)
        $amountInKobo = (int) round(((float) $payment->amount) * 100);

        $response = Http::withToken($this->secretKey)
            ->baseUrl($this->baseUrl)
            ->post('/transaction/initialize', [
                'email' => $payment->user->email,
                'amount' => $amountInKobo,
                'reference' => $payment->reference,
                'callback_url' => $options['callback_url'] ?? null,
                'metadata' => array_merge([
                    'payment_uuid' => $payment->uuid,
                    'purpose' => $payment->purpose,
                    'user_uuid' => $payment->user->uuid,
                ], $options['metadata'] ?? []),
            ]);

        if (! $response->successful() || ! ($response->json('status') === true)) {
            throw new RuntimeException('Failed to initialize Paystack transaction: '.($response->json('message') ?? 'Unknown error'));
        }

        $data = $response->json('data', []);

        return [
            'reference' => $payment->reference,
            'gateway_reference' => $data['reference'] ?? $payment->reference,
            'checkout_url' => $data['authorization_url'] ?? null,
            'access_code' => $data['access_code'] ?? null,
            'channel' => 'paystack',
            'instructions' => 'Proceed to Paystack secure checkout.',
        ];
    }

    public function verify(string $reference): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Paystack secret key is not configured.');
        }

        $response = Http::withToken($this->secretKey)
            ->baseUrl($this->baseUrl)
            ->get("/transaction/verify/{$reference}");

        if (! $response->successful()) {
            throw new RuntimeException('Paystack verification request failed.');
        }

        $data = $response->json('data', []);
        $gatewayStatus = $data['status'] ?? 'failed';

        $status = match ($gatewayStatus) {
            'success' => Payment::STATUS_SUCCESSFUL,
            'ongoing', 'pending' => Payment::STATUS_PENDING,
            default => Payment::STATUS_FAILED,
        };

        return [
            'status' => $status,
            'reference' => $reference,
            'gateway_reference' => (string) ($data['id'] ?? $reference),
            'amount' => isset($data['amount']) ? ((float) $data['amount']) / 100 : 0.0,
            'currency' => $data['currency'] ?? 'NGN',
            'channel' => $data['channel'] ?? 'card',
            'paid_at' => $data['paid_at'] ?? null,
            'raw' => $response->json(),
        ];
    }

    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Paystack secret key is not configured.');
        }

        $amountInKobo = $amount !== null ? (int) round($amount * 100) : null;

        $payload = [
            'transaction' => $payment->gateway_reference ?: $payment->reference,
            'customer_note' => $reason ?? 'Customer requested refund',
        ];

        if ($amountInKobo !== null) {
            $payload['amount'] = $amountInKobo;
        }

        $response = Http::withToken($this->secretKey)
            ->baseUrl($this->baseUrl)
            ->post('/refund', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to process Paystack refund: '.($response->json('message') ?? 'Unknown error'));
        }

        $data = $response->json('data', []);

        return [
            'status' => Payment::STATUS_REFUNDED,
            'refund_reference' => (string) ($data['id'] ?? 'REF-'.Str::upper(Str::random(8))),
            'amount' => $amount ?? (float) $payment->amount,
            'raw' => $response->json(),
        ];
    }

    public function validateWebhook(Request $request): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Paystack secret key is not configured.');
        }

        $signature = $request->header('x-paystack-signature');
        $body = $request->getContent();

        if (hash_hmac('sha512', $body, $this->secretKey) !== $signature) {
            throw new RuntimeException('Invalid Paystack webhook signature.');
        }

        $payload = $request->json()->all();
        $event = $payload['event'] ?? '';
        $data = $payload['data'] ?? [];
        $reference = $data['reference'] ?? '';

        $status = match ($event) {
            'charge.success' => Payment::STATUS_SUCCESSFUL,
            default => Payment::STATUS_FAILED,
        };

        return [
            'event' => $event,
            'reference' => $reference,
            'status' => $status,
            'amount' => isset($data['amount']) ? ((float) $data['amount']) / 100 : 0.0,
            'currency' => $data['currency'] ?? 'NGN',
            'raw' => $payload,
        ];
    }
}
