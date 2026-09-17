<?php

namespace App\Services\Payments;

use App\Services\Payments\Contracts\PaymentGatewayInterface;
use App\Services\Payments\Gateways\MockPaymentGateway;
use App\Services\Payments\Gateways\PaystackPaymentGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * @var array<string, PaymentGatewayInterface>
     */
    protected array $drivers = [];

    public function driver(?string $name = null): PaymentGatewayInterface
    {
        $name = ! empty($name) ? $name : (config('autosecure.payments.driver') ?: (env('PAYMENT_DRIVER') ?: 'mock'));
        $name = is_string($name) && ! empty($name) ? $name : 'mock';

        if (! isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->createDriver($name);
        }

        return $this->drivers[$name];
    }

    protected function createDriver(?string $name): PaymentGatewayInterface
    {
        return match ($name) {
            'mock', 'null', 'test', 'sandbox' => new MockPaymentGateway(),
            'paystack' => new PaystackPaymentGateway(),
            default => new MockPaymentGateway(),
        };
    }
}
