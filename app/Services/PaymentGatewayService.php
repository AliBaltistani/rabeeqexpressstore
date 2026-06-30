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
        $locale = app()->getLocale();
        $stripeEnabled = (bool) setting('payment.stripe_enabled', false)
            || !empty(config('services.stripe.secret'));

        $codFee = (float) setting('payment.cod_extra_fee', 0);

        $codLabel = $locale === 'ar'
            ? (setting('payment.cod_label_ar') ?: 'الدفع عند الاستلام')
            : (setting('payment.cod_label_en') ?: 'Cash on Delivery');

        $bankLabel = $locale === 'ar'
            ? (setting('payment.bank_label_ar') ?: 'تحويل بنكي')
            : (setting('payment.bank_label_en') ?: 'Bank Transfer');

        $codDesc = $locale === 'ar'
            ? (setting('payment.cod_description_ar') ?: 'ادفع عند استلام طلبك.')
            : (setting('payment.cod_description_en') ?: 'Pay when you receive your order.');

        return [
            [
                'id'          => 'cod',
                'name'        => $codLabel,
                'enabled'     => (bool) setting('payment.cod_enabled', true),
                'fee'         => $codFee > 0
                    ? currency_symbol() . ' ' . number_format($codFee, 2)
                    : null,
                'description' => $codDesc,
            ],
            [
                'id'          => 'stripe',
                'name'        => 'Credit / Debit Card',
                'enabled'     => $stripeEnabled,
                'fee'         => null,
                'description' => 'Pay securely with Visa, Mastercard, or Amex.',
            ],
            [
                'id'          => 'bank_transfer',
                'name'        => $bankLabel,
                'enabled'     => (bool) setting('payment.bank_enabled', true),
                'fee'         => null,
                'description' => 'Transfer to our bank account. Order confirmed upon receipt.',
            ],
            [
                'id'          => 'paypal',
                'name'        => 'PayPal',
                'enabled'     => (bool) setting('payment.paypal_enabled', false),
                'fee'         => null,
                'description' => 'Pay with your PayPal account.',
            ],
            [
                'id'          => 'tamara',
                'name'        => 'Tamara — Buy Now Pay Later',
                'enabled'     => false,
                'fee'         => null,
                'description' => 'Split into 3 interest-free payments.',
            ],
            [
                'id'          => 'tabby',
                'name'        => 'Tabby — Pay in 4',
                'enabled'     => false,
                'fee'         => null,
                'description' => 'Pay in 4 interest-free installments.',
            ],
        ];
    }

    /**
     * Get the Stripe secret key — prioritize admin settings over config.
     */
    public function getStripeSecret(): ?string
    {
        $stored = setting('payment.stripe_secret_key');
        if ($stored) {
            try {
                return decrypt($stored);
            } catch (\Throwable) {
                // Value may not be encrypted (set via config), use as-is
                return $stored;
            }
        }
        return config('services.stripe.secret');
    }

    /**
     * Get the Stripe publishable key — prioritize admin settings over config.
     * Note: publishable keys are NOT encrypted by the admin page.
     */
    public function getStripePublishableKey(): ?string
    {
        return setting('payment.stripe_publishable_key')
            ?: config('services.stripe.key');
    }

    /**
     * Create a Stripe PaymentIntent for the given order.
     *
     * @return array{success: bool, client_secret: ?string, payment_intent_id: ?string, error: ?string}
     */
    public function createStripeIntent(Order $order): array
    {
        $stripeSecret = $this->getStripeSecret();

        if (empty($stripeSecret)) {
            Log::error('Stripe payment attempted but Stripe secret key is not configured.', [
                'order' => $order->order_number,
            ]);
            throw new \RuntimeException(
                'Stripe is not configured. Please contact the store administrator.'
            );
        }

        try {
            \Stripe\Stripe::setApiKey($stripeSecret);

            $intent = \Stripe\PaymentIntent::create([
                'amount'                    => (int) round((float) $order->total * 100),
                'currency'                  => strtolower($order->currency_code ?? 'sar'),
                'automatic_payment_methods' => ['enabled' => true],
                'metadata'                  => [
                    'order_number' => $order->order_number,
                    'order_id'     => $order->id,
                ],
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
        $stripeSecret = $this->getStripeSecret();

        if (empty($stripeSecret)) {
            Log::error('Stripe confirmation attempted but Stripe secret key is not configured.', [
                'order'             => $order->order_number,
                'paymentIntentId'   => $paymentIntentId,
            ]);
            return ['success' => false, 'error' => 'Stripe is not configured on the server.'];
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

                return ['success' => true, 'stripeStatus' => 'succeeded', 'error' => null];
            }

            return [
                'success'      => false,
                'stripeStatus' => $intent->status,
                'error'        => 'Payment not yet completed. Stripe status: ' . $intent->status,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe confirmation failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a raw Stripe PaymentIntent for a given amount (in cents) and currency.
     * Used in the payment-first flow before the order is created.
     *
     * Uses explicit payment_method_types (fetched from Dashboard via PaymentMethodConfigurations)
     * instead of automatic_payment_methods. This is critical: with automatic_payment_methods,
     * Stripe dynamically filters visible methods — often promoting Link to cover other options.
     * Explicit types guarantee every listed method appears as its own tab in the Payment Element.
     *
     * @return array{success: bool, client_secret: ?string, payment_intent_id: ?string, enabled_payment_methods: string[], error: ?string}
     */
    public function createPaymentIntentForAmount(int $amountCents, string $currency): array
    {
        $stripeSecret = $this->getStripeSecret();

        if (empty($stripeSecret)) {
            throw new \RuntimeException('Stripe is not configured. Please contact the store administrator.');
        }

        try {
            \Stripe\Stripe::setApiKey($stripeSecret);

            // Fetch the complete list of enabled payment method types from the Stripe Dashboard.
            // We pass these explicitly so every enabled method is shown as a tab.
            $enabledTypes = $this->getEnabledStripePaymentMethodTypes();

            $intent = \Stripe\PaymentIntent::create([
                'amount'               => $amountCents,
                'currency'             => strtolower($currency),
                'payment_method_types' => $enabledTypes,
            ]);

            return [
                'success'                  => true,
                'client_secret'            => $intent->client_secret,
                'payment_intent_id'        => $intent->id,
                'enabled_payment_methods'  => $enabledTypes,
                'error'                    => null,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe PaymentIntent (amount-based) creation failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success'           => false,
                'client_secret'     => null,
                'payment_intent_id' => null,
                'error'             => $e->getMessage(),
            ];
        }
    }

    /**
     * Query the Stripe PaymentMethodConfigurations API to discover which payment
     * method types are actually enabled (available = true) in the Stripe Dashboard.
     *
     * Returns an ordered array of payment_method_type strings, e.g.:
     *   ['card', 'link']
     *
     * The Stripe PaymentMethodConfigurations object has individual sub-objects for
     * each method (card, link, apple_pay, google_pay, klarna, …). Each sub-object
     * has an `available` boolean that combines capability status + display_preference.
     *
     * Note: apple_pay and google_pay are NOT separately controllable via this API —
     * they are automatically enabled when `card` is enabled and the customer's
     * device/browser supports them. They should NOT be listed in payment_method_types
     * as they are sub-methods of `card`.
     *
     * Falls back to ['card', 'link'] if the API call fails (always safe for Stripe).
     *
     * @return string[]
     */
    public function getEnabledStripePaymentMethodTypes(): array
    {
        // Ordered by priority in the Payment Element tab strip.
        // Wallets (apple_pay, google_pay) are NOT separate payment_method_types —
        // they surface automatically through the `card` type when eligible.
        $candidateTypes = ['card', 'link', 'klarna', 'afterpay_clearpay', 'affirm', 'ideal', 'sepa_debit', 'bancontact', 'p24', 'giropay'];

        try {
            $configs = \Stripe\PaymentMethodConfiguration::all(['limit' => 1]);

            if (empty($configs->data)) {
                Log::warning('[Stripe] PaymentMethodConfigurations returned empty — using safe fallback.');
                return ['card', 'link'];
            }

            $config = $configs->data[0];
            $enabled = [];

            foreach ($candidateTypes as $type) {
                // The config object exposes each type as a property.
                if (isset($config->$type) && ($config->$type->available ?? false) === true) {
                    $enabled[] = $type;
                }
            }

            // Always guarantee 'card' — it is the foundation of all wallet methods
            if (!in_array('card', $enabled, true)) {
                array_unshift($enabled, 'card');
            }

            Log::info('[Stripe] Enabled payment method types from Dashboard:', $enabled);

            return $enabled ?: ['card', 'link'];
        } catch (\Throwable $e) {
            Log::warning('[Stripe] Could not fetch PaymentMethodConfigurations, using fallback.', [
                'error' => $e->getMessage(),
            ]);
            return ['card', 'link'];
        }
    }

    /**
     * Verify a Stripe PaymentIntent has status 'succeeded'.
     * Used in placeOrder to confirm payment before creating the order.
     *
     * @return array{success: bool, error: ?string}
     */
    public function verifyStripePaymentIntent(string $paymentIntentId): array
    {
        $stripeSecret = $this->getStripeSecret();

        if (empty($stripeSecret)) {
            return ['success' => false, 'error' => 'Stripe is not configured.'];
        }

        try {
            \Stripe\Stripe::setApiKey($stripeSecret);

            $intent = \Stripe\PaymentIntent::retrieve($paymentIntentId);

            if ($intent->status === 'succeeded') {
                return ['success' => true, 'error' => null];
            }

            return [
                'success' => false,
                'error'   => 'Payment was not confirmed by Stripe. Status: ' . $intent->status,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe PI verification failed', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
