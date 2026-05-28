<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ShippingMethod;
use Illuminate\Support\Collection;

final class ShippingEngineService
{
    /**
     * Resolve available shipping methods for a given destination.
     *
     * @return Collection<int, array{id: int, slug: string, name: string, carrier_type: string, price: array, estimated_delivery: string}>
     */
    public function resolveForDestination(string $country, ?string $city = null): Collection
    {
        $countryCode = $this->normalizeCountryCode($country);
        $locale = app()->getLocale();

        $methods = ShippingMethod::active()
            ->forCountry($countryCode)
            ->orderBy('sort_order')
            ->get();

        // If no specific methods found, always return standard fallback
        if ($methods->isEmpty()) {
            $methods = ShippingMethod::active()
                ->byCarrier('standard')
                ->get();
        }

        return $methods->map(fn(ShippingMethod $method) => [
            'id'                 => $method->id,
            'slug'               => $method->slug,
            'name'               => $method->getTranslation('name', $locale),
            'carrier_type'       => $method->carrier_type,
            'price'              => [
                'raw'       => (float) $method->base_cost,
                'formatted' => currency_symbol() . ' ' . number_format((float) $method->base_cost, 2),
            ],
            'estimated_delivery' => $method->getEstimatedDeliveryLabel(),
        ]);
    }

    /**
     * Check if SMSA Express is available for a given route.
     */
    public function isSmsaAvailable(string $originCountry, string $destinationCountry): bool
    {
        $smsaRoutes = [
            'SA' => ['SA', 'BH', 'AE', 'KW', 'OM', 'QA'],
            'BH' => ['SA', 'BH'],
        ];

        $origin = strtoupper($originCountry);
        $dest   = strtoupper($destinationCountry);

        return isset($smsaRoutes[$origin]) && in_array($dest, $smsaRoutes[$origin], true);
    }

    /**
     * Normalize free-text country names to ISO codes.
     */
    private function normalizeCountryCode(string $country): string
    {
        $map = [
            'saudi arabia'          => 'SA',
            'ksa'                   => 'SA',
            'المملكة العربية السعودية' => 'SA',
            'bahrain'               => 'BH',
            'البحرين'                => 'BH',
            'united arab emirates'  => 'AE',
            'uae'                   => 'AE',
            'الإمارات'               => 'AE',
            'kuwait'                => 'KW',
            'الكويت'                 => 'KW',
            'oman'                  => 'OM',
            'عمان'                   => 'OM',
            'qatar'                 => 'QA',
            'قطر'                    => 'QA',
        ];

        $normalized = strtolower(trim($country));

        // Already an ISO code
        if (strlen($normalized) === 2) {
            return strtoupper($normalized);
        }

        return $map[$normalized] ?? strtoupper(substr($country, 0, 2));
    }
}
