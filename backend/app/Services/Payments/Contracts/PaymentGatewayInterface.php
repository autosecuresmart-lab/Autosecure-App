<?php

namespace App\Services\Payments\Contracts;

use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Initialize a payment intent with the gateway.
     *
     * @param  Payment  $payment
     * @param  array<string, mixed>  $options
     * @return array{
     *     reference: string,
     *     gateway_reference: string,
     *     checkout_url: ?string,
     *     access_code: ?string,
     *     channel: string,
     *     instructions?: ?string
     * }
     */
    public function initialize(Payment $payment, array $options = []): array;

    /**
     * Verify a transaction reference with the gateway.
     *
     * @param  string  $reference
     * @return array{
     *     status: string,
     *     reference: string,
     *     gateway_reference: string,
     *     amount: float,
     *     currency: string,
     *     channel: string,
     *     paid_at: ?string,
     *     raw: array<string, mixed>
     * }
     */
    public function verify(string $reference): array;

    /**
     * Refund a payment.
     *
     * @param  Payment  $payment
     * @param  float|null  $amount
     * @param  string|null  $reason
     * @return array{
     *     status: string,
     *     refund_reference: string,
     *     amount: float,
     *     raw: array<string, mixed>
     * }
     */
    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null): array;

    /**
     * Validate and parse a webhook payload.
     *
     * @param  Request  $request
     * @return array{
     *     event: string,
     *     reference: string,
     *     status: string,
     *     amount: float,
     *     currency: string,
     *     raw: array<string, mixed>
     * }
     */
    public function validateWebhook(Request $request): array;
}
