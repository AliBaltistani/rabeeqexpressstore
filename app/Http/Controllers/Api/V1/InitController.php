<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Banner;
use App\Models\CmsPage;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Setting;
use Carbon\Carbon;
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
            'storeName' => setting('general.store_name_' . $locale, setting('general.store_name_en', 'Rabeq Express Store')),
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
                'guestCheckout'         => (bool) setting('general.enable_guest_checkout', true),
                'wishlist'              => (bool) setting('general.enable_wishlist', true),
                'reviews'               => (bool) setting('general.enable_reviews', true),
                'reviewsRequireApproval'=> (bool) setting('general.reviews_require_approval', true),
                // OTP / Auth method
                'otpMode'               => setting('auth.otp_mode', 'email'),
                'emailOtpEnabled'       => (bool) setting('auth.email_otp_enabled', true),
                'phoneOtpEnabled'       => (bool) setting('auth.phone_otp_enabled', false),
                // Social login providers
                'socialLogin' => [
                    'google'   => [
                        'enabled'  => (bool) setting('social.google_enabled', false),
                        'clientId' => setting('social.google_enabled') ? setting('social.google_client_id') : null,
                    ],
                    'facebook' => [
                        'enabled'  => (bool) setting('social.facebook_enabled', false),
                        'clientId' => setting('social.facebook_enabled') ? setting('social.facebook_client_id') : null,
                    ],
                    'apple'    => [
                        'enabled'  => (bool) setting('social.apple_enabled', false),
                        'clientId' => setting('social.apple_enabled') ? setting('social.apple_client_id') : null,
                    ],
                ],
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
                'snapchat' => setting('seo.snapchat_url'),
            ],
            'storeDescription' => setting('general.store_description_' . $locale, setting('general.store_description_en', '')),
            'appLinks' => [
                'appstore' => setting('general.appstore_url', ''),
                'googleplay' => setting('general.googleplay_url', ''),
            ],
            'footerPages' => CmsPage::where('status', 'active')
                ->where('show_in_footer', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn($p) => [
                    'slug' => $p->slug,
                    'title' => $p->getTranslation('title', $locale, false) ?: $p->getTranslation('title', 'en'),
                ])
                ->values(),
            'headerPages' => CmsPage::where('status', 'active')
                ->where('show_in_header', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn($p) => [
                    'slug' => $p->slug,
                    'title' => $p->getTranslation('title', $locale, false) ?: $p->getTranslation('title', 'en'),
                ])
                ->values(),
            'maintenance' => [
                'enabled' => (bool) setting('general.maintenance_mode', false),
                'message' => setting('general.maintenance_message_' . $locale),
            ],
            'googleMapsApiKey' => setting('general.google_maps_api_key', ''),
            'promoBar' => $this->getPromoBar($locale),
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

    protected function getPromoBar(string $locale): array
    {
        $enabled     = (bool) setting('promo_bar.enabled', false);
        $startsAt    = setting('promo_bar.starts_at');
        $endsAt      = setting('promo_bar.ends_at');
        $countdownEnd = setting('promo_bar.countdown_end');

        // Schedule check
        $now = now();
        if ($startsAt && $now->lt(Carbon::parse($startsAt))) {
            $enabled = false;
        }
        if ($endsAt && $now->gt(Carbon::parse($endsAt))) {
            $enabled = false;
        }

        // Parse multi-message JSON arrays
        $itemsEn = null;
        $itemsAr = null;
        $rawEn = setting('promo_bar.items_en');
        $rawAr = setting('promo_bar.items_ar');
        if (!empty($rawEn)) {
            $decoded = json_decode($rawEn, true);
            if (is_array($decoded) && count($decoded) > 0) {
                $itemsEn = $decoded;
            }
        }
        if (!empty($rawAr)) {
            $decoded = json_decode($rawAr, true);
            if (is_array($decoded) && count($decoded) > 0) {
                $itemsAr = $decoded;
            }
        }

        $message = $locale === 'ar'
            ? setting('promo_bar.message_ar', setting('promo_bar.message_en', ''))
            : setting('promo_bar.message_en', '');

        $items = $locale === 'ar' ? ($itemsAr ?? $itemsEn) : ($itemsEn ?? $itemsAr);

        return [
            'enabled'       => $enabled,
            'mode'          => setting('promo_bar.mode', 'marquee'),
            'style'         => setting('promo_bar.style', 'filled'),
            'bgColor'       => setting('promo_bar.bg_color', '#cc0000'),
            'textColor'     => setting('promo_bar.text_color', '#ffffff'),
            'gradientFrom'  => setting('promo_bar.gradient_from', '#cc0000'),
            'gradientTo'    => setting('promo_bar.gradient_to', '#ff6600'),
            'message'       => $message,
            'items'         => $items,
            'icon'          => setting('promo_bar.icon', ''),
            'linkUrl'       => setting('promo_bar.link_url', ''),
            'linkTarget'    => setting('promo_bar.link_target', '_self'),
            'marqueeSpeed'  => setting('promo_bar.marquee_speed', 'medium'),
            'fontSize'      => setting('promo_bar.font_size', 'sm'),
            'fontWeight'    => setting('promo_bar.font_weight', 'semibold'),
            'barHeight'     => setting('promo_bar.bar_height', 'normal'),
            'dismissible'   => (bool) setting('promo_bar.dismissible', true),
            'dismissHours'  => (int) setting('promo_bar.dismiss_hours', 24),
            'showCountdown' => (bool) setting('promo_bar.show_countdown', false),
            'countdownEnd'  => $countdownEnd,
            'showOnMobile'  => (bool) setting('promo_bar.show_on_mobile', true),
        ];
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
