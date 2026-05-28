<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

final class PaymentGatewayService
{
    /**
     * Get all available payment gateways.
     *
     * @return array<int, array{id: string, name: string, enabled: bool, fee: ?string, description: ?string}>
     */
    public function getAvailableGateways(): array
    {
        return [
            [
                'id'          => 'stripe',
                'name'        => 'Credit / Debit Card',
                'enabled'     => !empty(config('services.stripe.secret')),
                'fee'         => null,
                'description' => 'Pay securely with Visa, Mastercard, or Amex.',
            ],
            [
                'id'          => 'cod',
                'name'        => 'Cash on Delivery',
                'enabled'     => (bool) setting('payment.cod_enabled', true),
                'fee'         => setting('payment.cod_fee', 0) > 0
                    ? currency_symbol() . ' ' . number_format((float) setting('payment.cod_fee', 0), 2)
                    : null,
                'description' => 'Pay when you receive your order.',
            ],
            [
                'id'          => 'bank_transfer',
                'name'        => 'Bank Transfer',
                'enabled'     => (bool) setting('payment.bank_transfer_enabled', true),
                'fee'         => null,
                'description' => 'Transfer to our bank account. Order confirmed upon receipt.',
            ],
            [
                'id'          => 'tamara',
                'name'        => 'Tamara — Buy Now Pay Later',
                'enabled'     => false, // Stub: enable when integrated
                'fee'         => null,
                'description' => 'Split into 3 interest-free payments.',
            ],
            [
                'id'          => 'tabby',
                'name'        => 'Tabby — Pay in 4',
                'enabled'     => false, // Stub: enable when integrated
                'fee'         => null,
                'description' => 'Pay in 4 interest-free installments.',
            ],
        ];
    }

    /**
     * Create a Stripe PaymentIntent for the given order.
     *
     * @return array{success: bool, client_secret: ?string, payment_intent_id: ?string, error: ?string}
     */
    public function createStripeIntent(Order $order): array
    {
        $stripeSecret = config('services.stripe.secret');

        if (empty($stripeSecret)) {
            // Simulated mode: mark order as paid directly
            Log::info('Stripe not configured, simulating payment', ['order' => $order->order_number]);

            $order->update([
                'payment_status'    => 'paid',
                'payment_gateway'   => 'stripe',
                'status'            => 'processing',
            ]);

            return [
                'success'           => true,
                'client_secret'     => null,
                'payment_intent_id' => 'sim_' . $order->order_number,
                'requires_action'   => false,
                'error'             => null,
            ];
        }

        try {
            \Stripe\Stripe::setApiKey($stripeSecret);

            $intent = \Stripe\PaymentIntent::create([
                'amount'   => (int) round((float) $order->total * 100),
                'currency' => strtolower($order->currency_code ?? 'sar'),
                'metadata' => [
                    'order_number' => $order->order_number,
                    'order_id'     => $order->id,
                ],
                'automatic_payment_methods' => ['enabled' => true],
            ]);

            $order->update([
                'payment_gateway'   => 'stripe',
                'payment_intent_id' => $intent->id,
            ]);

            return [
                'success'           => true,
                'client_secret'     => $intent->client_secret,
                'payment_intent_id' => $intent->id,
                'requires_action'   => $intent->status === 'requires_action',
                'error'             => null,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe PaymentIntent creation failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            return [
                'success'           => false,
                'client_secret'     => null,
                'payment_intent_id' => null,
                'requires_action'   => false,
                'error'             => 'Payment initialization failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Confirm a Stripe PaymentIntent and mark order as paid.
     *
     * @return array{success: bool, error: ?string}
     */
    public function confirmStripePayment(Order $order, string $paymentIntentId): array
    {
        $stripeSecret = config('services.stripe.secret');

        if (empty($stripeSecret)) {
            // Simulated confirmation
            $order->update([
                'payment_status'  => 'paid',
                'status'          => 'processing',
                'transaction_id'  => $paymentIntentId,
            ]);

            return ['success' => true, 'error' => null];
        }

        try {
            \Stripe\Stripe::setApiKey($stripeSecret);

            $intent = \Stripe\PaymentIntent::retrieve($paymentIntentId);

            if ($intent->status === 'succeeded') {
                $order->update([
                    'payment_status'  => 'paid',
                    'status'          => 'processing',
                    'transaction_id'  => $paymentIntentId,
                ]);

                return ['success' => true, 'error' => null];
            }

            return [
                'success' => false,
                'error'   => 'Payment not yet completed. Status: ' . $intent->status,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe confirmation failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
