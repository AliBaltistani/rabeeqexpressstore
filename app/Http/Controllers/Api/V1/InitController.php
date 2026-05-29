<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Banner;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class InitController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/init
     * Returns all storefront bootstrap data in one call.
     */
    public function __invoke(): JsonResponse
    {
        $locale = app()->getLocale();

        $currencies = Currency::where('is_active', true)
            ->orderByDesc('is_default')
            ->get()
            ->map(fn($c) => [
                'code' => $c->code,
                'name' => $c->name,
                'symbol' => $c->symbol,
                'exchangeRate' => (float) $c->exchange_rate,
                'decimalPlaces' => $c->decimal_places ?? 2,
                'isDefault' => (bool) $c->is_default,
            ]);

        $languages = Language::where('is_active', true)
            ->orderByDesc('is_default')
            ->get()
            ->map(fn($l) => [
                'code' => $l->code,
                'name' => $l->name,
                'direction' => strtolower($l->direction),
                'isDefault' => (bool) $l->is_default,
            ]);

        $defaultCurrency = $currencies->firstWhere('isDefault', true);
        $defaultLanguage = $languages->firstWhere('isDefault', true);

        return $this->success([
            'storeName' => setting('general.store_name_' . $locale, setting('general.store_name_en', 'Eseven Store')),
            'storeTagline' => setting('general.store_tagline_' . $locale),
            'logo' => $this->resolveImageUrl(setting('general.store_logo')),
            'favicon' => $this->resolveImageUrl(setting('general.store_favicon')),
            'storeEmail' => setting('general.store_email'),
            'storePhone' => setting('general.store_phone'),
            'whatsappNumber' => setting('general.store_whatsapp'),
            'storeAddress' => setting('general.store_address_' . $locale),
            'currencies' => $currencies,
            'languages' => $languages,
            'defaultCurrency' => $defaultCurrency['code'] ?? 'SAR',
            'defaultLanguage' => $defaultLanguage['code'] ?? 'en',
            'paymentMethods' => $this->getPaymentMethods(),
            'features' => [
                'guestCheckout' => (bool) setting('general.enable_guest_checkout', true),
                'wishlist' => (bool) setting('general.enable_wishlist', true),
                'reviews' => (bool) setting('general.enable_reviews', true),
                'reviewsRequireApproval' => (bool) setting('general.reviews_require_approval', true),
            ],
            'seo' => [
                'siteTitle' => setting('seo.site_title_' . $locale, setting('seo.site_title_en')),
                'metaDescription' => setting('seo.meta_description_' . $locale),
                'metaKeywords' => setting('seo.meta_keywords'),
            ],
            'socialLinks' => [
                'facebook' => setting('seo.facebook_url'),
                'twitter' => setting('seo.twitter_url'),
                'instagram' => setting('seo.instagram_url'),
                'youtube' => setting('seo.youtube_url'),
                'tiktok' => setting('seo.tiktok_url'),
            ],
            'maintenance' => [
                'enabled' => (bool) setting('general.maintenance_mode', false),
                'message' => setting('general.maintenance_message_' . $locale),
            ],
        ]);
    }

    protected function getPaymentMethods(): array
    {
        $methods = [];

        if (setting('payment.cod_enabled')) {
            $methods[] = [
                'id' => 'cod',
                'name' => 'Cash on Delivery',
                'fee' => (float) setting('payment.cod_extra_fee', 0),
            ];
        }

        if (setting('payment.bank_transfer_enabled')) {
            $methods[] = [
                'id' => 'bank_transfer',
                'name' => 'Bank Transfer',
            ];
        }

        if (setting('payment.stripe_enabled')) {
            $methods[] = [
                'id' => 'stripe',
                'name' => 'Credit/Debit Card',
            ];
        }

        if (setting('payment.paypal_enabled')) {
            $methods[] = [
                'id' => 'paypal',
                'name' => 'PayPal',
            ];
        }

        return $methods;
    }

    protected function resolveImageUrl(?string $path): ?string
    {
        if (empty($path)) return null;

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        if (\Illuminate\Support\Facades\Storage::exists($path)) {
            try {
                return \Illuminate\Support\Facades\Storage::temporaryUrl($path, now()->addDay());
            } catch (\Throwable) {
                return asset('storage/' . $path);
            }
        }

        return null;
    }
}
