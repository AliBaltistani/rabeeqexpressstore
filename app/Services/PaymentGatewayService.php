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
                'name'        => $locale === 'ar' ? 'تمارا — اشترِ الآن وادفع لاحقًا' : 'Tamara — Buy Now Pay Later',
                'enabled'     => (bool) setting('payment.tamara_enabled', false),
                'fee'         => null,
                'description' => $locale === 'ar' ? 'قسّم المبلغ على 3 دفعات بدون فوائد.' : 'Split into 3 interest-free payments.',
            ],
            [
                'id'          => 'tabby',
                'name'        => $locale === 'ar' ? 'تابي — ادفع على 4 دفعات' : 'Tabby — Pay in 4',
                'enabled'     => (bool) setting('payment.tabby_enabled', false),
                'fee'         => null,
                'description' => $locale === 'ar' ? 'ادفع على 4 أقساط بدون فوائد.' : 'Pay in 4 interest-free installments.',
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Stripe
    // ─────────────────────────────────────────────────────────────────────────

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
                return $stored;
            }
        }
        return config('services.stripe.secret');
    }

    /**
     * Get the Stripe publishable key — prioritize admin settings over config.
     */
    public function getStripePublishableKey(): ?string
    {
        return setting('payment.stripe_publishable_key')
            ?: config('services.stripe.key');
    }

    /**
     * Create a Stripe PaymentIntent for the given order.
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
     */
    public function createPaymentIntentForAmount(int $amountCents, string $currency): array
    {
        $stripeSecret = $this->getStripeSecret();

        if (empty($stripeSecret)) {
            throw new \RuntimeException('Stripe is not configured. Please contact the store administrator.');
        }

        try {
            \Stripe\Stripe::setApiKey($stripeSecret);

            $intent = \Stripe\PaymentIntent::create([
                'amount'                    => $amountCents,
                'currency'                  => strtolower($currency),
                'automatic_payment_methods' => ['enabled' => true],
            ]);

            return [
                'success'                  => true,
                'client_secret'            => $intent->client_secret,
                'payment_intent_id'        => $intent->id,
                'enabled_payment_methods'  => [],
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
     * Verify a Stripe PaymentIntent has status 'succeeded'.
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

    // ─────────────────────────────────────────────────────────────────────────
    // Tamara
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Resolve Tamara configuration from admin settings.
     *
     * @return array{api_token: string, notification_token: string, base_url: string}
     */
    public function getTamaraConfig(): array
    {
        $env = setting('payment.tamara_environment', 'sandbox');
        $baseUrl = $env === 'live'
            ? 'https://api.tamara.co'
            : 'https://api-sandbox.tamara.co';

        $apiToken = setting('payment.tamara_api_token');
        if ($apiToken) {
            try { $apiToken = decrypt($apiToken); } catch (\Throwable) {}
        }

        $notificationToken = setting('payment.tamara_notification_token');
        if ($notificationToken) {
            try { $notificationToken = decrypt($notificationToken); } catch (\Throwable) {}
        }

        return [
            'api_token'          => $apiToken ?? '',
            'notification_token' => $notificationToken ?? '',
            'base_url'           => $baseUrl,
            'merchant_currency'  => strtoupper((string) setting('payment.tamara_merchant_currency', 'AED')),
        ];
    }

    /**
     * Create a Tamara checkout session for the given order.
     *
     * @return array{success: bool, checkout_url: ?string, tamara_order_id: ?string, error: ?string}
     */
    public function createTamaraSession(Order $order): array
    {
        $config = $this->getTamaraConfig();

        if (empty($config['api_token'])) {
            throw new \RuntimeException('Tamara is not configured. Please contact the store administrator.');
        }

        $order->load(['items.product', 'shippingAddress', 'billingAddress', 'user']);

        $orderCurrency = strtoupper($order->currency_code ?? 'SAR');
        $targetCurrency = trim($config['merchant_currency'] ?: '') ?: 'AED';
        
        $total     = \App\Models\Currency::convert((float) $order->total, $orderCurrency, $targetCurrency);
        $shipping  = \App\Models\Currency::convert((float) $order->shipping_amount, $orderCurrency, $targetCurrency);
        $discount  = \App\Models\Currency::convert((float) $order->discount_amount, $orderCurrency, $targetCurrency);
        $tax       = \App\Models\Currency::convert((float) $order->tax_amount, $orderCurrency, $targetCurrency);

        // Build items array
        $items = [];
        foreach ($order->items as $item) {
            $unitPrice = \App\Models\Currency::convert((float) $item->unit_price, $orderCurrency, $targetCurrency);
            $itemTotal = \App\Models\Currency::convert((float) $item->total, $orderCurrency, $targetCurrency);
            $items[] = [
                'name'         => $item->product_name ?? 'Product',
                'type'         => 'physical',
                'reference_id' => (string) $item->product_id,
                'sku'          => $item->product_sku ?? (string) $item->product_id,
                'quantity'     => $item->quantity,
                'unit_price'   => ['amount' => number_format($unitPrice, 2, '.', ''), 'currency' => $targetCurrency],
                'total_amount' => ['amount' => number_format($itemTotal, 2, '.', ''), 'currency' => $targetCurrency],
            ];
        }

        // Consumer info
        $addr    = $order->shippingAddress;
        $user    = $order->user;
        $phone   = $addr?->phone ?? $order->guest_phone ?? $user?->phone ?? '';
        $email   = $user?->email ?? $order->guest_email ?? '';
        $firstName = $addr?->first_name ?? $order->guest_name ?? 'Customer';
        $lastName  = $addr?->last_name ?? '';

        $callbackBase = url('/api/v1/checkout');

        $payload = [
            'total_amount'       => ['amount' => number_format($total, 2, '.', ''), 'currency' => $targetCurrency],
            'shipping_amount'    => ['amount' => number_format($shipping, 2, '.', ''), 'currency' => $targetCurrency],
            'tax_amount'         => ['amount' => number_format($tax, 2, '.', ''), 'currency' => $targetCurrency],
            'discount'           => ['amount' => ['amount' => number_format($discount, 2, '.', ''), 'currency' => $targetCurrency], 'name' => 'Discount'],
            'order_reference_id' => (string) $order->order_number,
            'order_number'       => (string) $order->order_number,
            'items'              => $items,
            'consumer'           => [
                'first_name'   => $firstName,
                'last_name'    => $lastName ?: '-',
                'phone_number' => $phone,
                'email'        => $email,
            ],
            'country_code'       => 'SA',
            'locale'             => app()->getLocale() === 'ar' ? 'ar_SA' : 'en_US',
            'merchant_url'       => [
                'success'      => $callbackBase . '/tamara/callback?status=success&order=' . $order->order_number,
                'failure'      => $callbackBase . '/tamara/callback?status=failure&order=' . $order->order_number,
                'cancel'       => $callbackBase . '/tamara/callback?status=cancel&order=' . $order->order_number,
                'notification' => url('/webhooks/tamara'),
            ],
            'shipping_address'   => [
                'first_name'  => $firstName,
                'last_name'   => $lastName ?: '-',
                'line1'       => $addr?->address_line_1 ?? '-',
                'city'        => $addr?->city ?? '-',
                'country_code'=> 'SA',
                'phone_number'=> $phone,
            ],
        ];

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($config['api_token'])
                ->timeout(30)
                ->post($config['base_url'] . '/checkout', $payload);

            if (!$response->successful()) {
                $body = $response->json();
                Log::error('[Tamara] Session creation failed', [
                    'order'  => $order->order_number,
                    'status' => $response->status(),
                    'body'   => $body,
                ]);
                return [
                    'success'         => false,
                    'checkout_url'    => null,
                    'tamara_order_id' => null,
                    'error'           => $body['message'] ?? ('Tamara API error: ' . $response->status()),
                ];
            }

            $data = $response->json();

            return [
                'success'         => true,
                'checkout_url'    => $data['checkout_url'] ?? null,
                'tamara_order_id' => $data['order_id'] ?? null,
                'checkout_id'     => $data['checkout_id'] ?? null,
                'error'           => null,
            ];
        } catch (\Throwable $e) {
            Log::error('[Tamara] Session creation exception', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
            return [
                'success'         => false,
                'checkout_url'    => null,
                'tamara_order_id' => null,
                'error'           => 'Tamara payment initialization failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Authorise a Tamara order after it has been approved.
     * This is the commonly-missed step — order is NOT finalized without it.
     */
    public function authoriseTamaraOrder(string $tamaraOrderId): array
    {
        $config = $this->getTamaraConfig();

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($config['api_token'])
                ->timeout(30)
                ->post($config['base_url'] . "/orders/{$tamaraOrderId}/authorise");

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json(), 'error' => null];
            }

            $body = $response->json();
            Log::error('[Tamara] Authorise failed', [
                'tamara_order_id' => $tamaraOrderId,
                'status'          => $response->status(),
                'body'            => $body,
            ]);
            return ['success' => false, 'data' => null, 'error' => $body['message'] ?? 'Authorise failed'];
        } catch (\Throwable $e) {
            Log::error('[Tamara] Authorise exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get Tamara order details (for manual status sync).
     */
    public function getTamaraOrder(string $tamaraOrderId): array
    {
        $config = $this->getTamaraConfig();

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($config['api_token'])
                ->timeout(30)
                ->get($config['base_url'] . "/orders/{$tamaraOrderId}");

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json(), 'error' => null];
            }

            return ['success' => false, 'data' => null, 'error' => 'Tamara API error: ' . $response->status()];
        } catch (\Throwable $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tabby
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Resolve Tabby configuration from admin settings.
     */
    public function getTabbyConfig(): array
    {
        $env = setting('payment.tabby_environment', 'sandbox');
        $baseUrl = 'https://api.tabby.ai';

        $publicKey = setting('payment.tabby_public_key');
        if ($publicKey) {
            try { $publicKey = decrypt($publicKey); } catch (\Throwable) {}
        }

        $secretKey = setting('payment.tabby_secret_key');
        if ($secretKey) {
            try { $secretKey = decrypt($secretKey); } catch (\Throwable) {}
        }

        $webhookHeaderValue = setting('payment.tabby_webhook_header_value');
        if ($webhookHeaderValue) {
            try { $webhookHeaderValue = decrypt($webhookHeaderValue); } catch (\Throwable) {}
        }

        return [
            'public_key'           => $publicKey ?? '',
            'secret_key'           => $secretKey ?? '',
            'merchant_code'        => setting('payment.tabby_merchant_code') ?? '',
            'base_url'             => $baseUrl,
            'environment'          => $env,
            'webhook_header_name'  => setting('payment.tabby_webhook_header_name') ?? '',
            'webhook_header_value' => $webhookHeaderValue ?? '',
            'merchant_currency'    => strtoupper((string) setting('payment.tabby_merchant_currency', 'AED')),
        ];
    }

    /**
     * Create a Tabby checkout session for the given order.
     *
     * @return array{success: bool, checkout_url: ?string, payment_id: ?string, error: ?string}
     */
    public function createTabbySession(Order $order): array
    {
        $config = $this->getTabbyConfig();

        if (empty($config['public_key'])) {
            throw new \RuntimeException('Tabby is not configured. Please contact the store administrator.');
        }

        $order->load(['items.product', 'shippingAddress', 'billingAddress', 'user']);

        $orderCurrency = strtoupper($order->currency_code ?? 'SAR');
        $targetCurrency = trim($config['merchant_currency'] ?: '') ?: 'AED';
        
        $total     = \App\Models\Currency::convert((float) $order->total, $orderCurrency, $targetCurrency);
        $taxAmount = \App\Models\Currency::convert((float) $order->tax_amount, $orderCurrency, $targetCurrency);
        $discountAmount = \App\Models\Currency::convert((float) $order->discount_amount, $orderCurrency, $targetCurrency);
        $shippingAmount = \App\Models\Currency::convert((float) $order->shipping_amount, $orderCurrency, $targetCurrency);

        $addr    = $order->shippingAddress;
        $user    = $order->user;
        $phone   = $addr?->phone ?? $order->guest_phone ?? $user?->phone ?? '';
        $email   = $user?->email ?? $order->guest_email ?? '';
        $name    = ($addr?->first_name ?? $order->guest_name ?? 'Customer')
                   . ' ' . ($addr?->last_name ?? '');

        // Build items array
        $items = [];
        foreach ($order->items as $item) {
            $unitPrice = \App\Models\Currency::convert((float) $item->unit_price, $orderCurrency, $targetCurrency);
            $items[] = [
                'title'        => $item->product_name ?? 'Product',
                'quantity'     => $item->quantity,
                'unit_price'   => number_format($unitPrice, 2, '.', ''),
                'category'     => 'general',
                'reference_id' => (string) $item->product_id,
                'sku'          => $item->product_sku ?? (string) $item->product_id,
            ];
        }

        $callbackBase = url('/api/v1/checkout');

        $payload = [
            'payment'       => [
                'amount'   => number_format($total, 2, '.', ''),
                'currency' => $targetCurrency,
                'buyer'    => [
                    'phone' => $phone,
                    'email' => $email,
                    'name'  => trim($name),
                ],
                'order'    => [
                    'reference_id' => (string) $order->order_number,
                    'items'        => $items,
                    'tax_amount'   => number_format($taxAmount, 2, '.', ''),
                    'shipping_amount' => number_format($shippingAmount, 2, '.', ''),
                    'discount_amount' => number_format($discountAmount, 2, '.', ''),
                ],
                'shipping_address' => [
                    'city'    => $addr?->city ?? '-',
                    'address' => $addr?->address_line_1 ?? '-',
                    'zip'     => $addr?->postal_code ?? '00000',
                ],
            ],
            'lang'          => app()->getLocale() === 'ar' ? 'ar' : 'en',
            'merchant_code' => $config['merchant_code'],
            'merchant_urls' => [
                'success' => $callbackBase . '/tabby/callback?status=success&order=' . $order->order_number,
                'cancel'  => $callbackBase . '/tabby/callback?status=cancel&order=' . $order->order_number,
                'failure' => $callbackBase . '/tabby/callback?status=failure&order=' . $order->order_number,
            ],
        ];

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($config['public_key'])
                ->timeout(30)
                ->post($config['base_url'] . '/api/v2/checkout', $payload);

            if (!$response->successful()) {
                $body = $response->json();
                Log::error('[Tabby] Session creation failed', [
                    'order'  => $order->order_number,
                    'status' => $response->status(),
                    'body'   => $body,
                ]);
                return [
                    'success'      => false,
                    'checkout_url' => null,
                    'payment_id'   => null,
                    'error'        => $body['error'] ?? $body['message'] ?? ('Tabby API error: ' . $response->status()),
                ];
            }

            $data = $response->json();

            // Extract checkout URL from configuration.available_products.installments[0].web_url
            $checkoutUrl = $data['configuration']['available_products']['installments'][0]['web_url'] ?? null;
            $paymentId   = $data['id'] ?? $data['payment']['id'] ?? null;

            return [
                'success'      => true,
                'checkout_url' => $checkoutUrl,
                'payment_id'   => $paymentId,
                'error'        => null,
            ];
        } catch (\Throwable $e) {
            Log::error('[Tabby] Session creation exception', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
            return [
                'success'      => false,
                'checkout_url' => null,
                'payment_id'   => null,
                'error'        => 'Tabby payment initialization failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get Tabby payment details (for manual status sync).
     */
    public function getTabbyPayment(string $paymentId): array
    {
        $config = $this->getTabbyConfig();

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($config['secret_key'])
                ->timeout(30)
                ->get($config['base_url'] . "/api/v2/payments/{$paymentId}");

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json(), 'error' => null];
            }

            return ['success' => false, 'data' => null, 'error' => 'Tabby API error: ' . $response->status()];
        } catch (\Throwable $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Capture a Tabby payment (required after authorization).
     */
    public function captureTabbyPayment(string $paymentId, float $amount, string $currency = 'SAR'): array
    {
        $config = $this->getTabbyConfig();

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($config['secret_key'])
                ->timeout(30)
                ->post($config['base_url'] . "/api/v2/payments/{$paymentId}/captures", [
                    'amount'   => number_format($amount, 2, '.', ''),
                    'currency' => $currency,
                ]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json(), 'error' => null];
            }

            $body = $response->json();
            Log::error('[Tabby] Capture failed', [
                'payment_id' => $paymentId,
                'body'       => $body,
            ]);
            return ['success' => false, 'data' => null, 'error' => $body['message'] ?? 'Capture failed'];
        } catch (\Throwable $e) {
            Log::error('[Tabby] Capture exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }
}
