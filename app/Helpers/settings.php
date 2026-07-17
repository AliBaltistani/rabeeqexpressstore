<?php

use App\Models\Setting;

if (!function_exists('setting')) {
    /**
     * Global helper to retrieve a setting value.
     *
     * Usage:
     *   setting('general.store_name_en')           → returns value or null
     *   setting('general.store_name_en', 'Default') → returns value or 'Default'
     *
     * @param  string  $key      Dot-notated setting key (e.g. 'general.timezone')
     * @param  mixed   $default  Fallback value if the setting does not exist
     * @return mixed
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (!function_exists('currency_symbol')) {
    /**
     * Return the default currency symbol from the currencies table.
     * Falls back to the code from settings, then to 'SAR'.
     * NOTE: No static cache — the admin can change the default currency at any time
     * and we must always reflect the current DB value.
     */
    function currency_symbol(): string
    {
        $defaultCurrency = \App\Models\Currency::getDefault();
        return $defaultCurrency?->symbol
            ?? setting('general.default_currency', 'SAR');
    }
}

if (!function_exists('currency_code')) {
    /**
     * Return the default currency code.
     * NOTE: No static cache — the admin can change the default currency at any time
     * and we must always reflect the current DB value.
     */
    function currency_code(): string
    {
        $defaultCurrency = \App\Models\Currency::getDefault();
        return $defaultCurrency?->code
            ?? setting('general.default_currency', 'SAR');
    }
}

if (!function_exists('admin_date_format')) {
    /**
     * Convert the admin date format setting to PHP date format.
     */
    function admin_date_format(bool $withTime = false): string
    {
        $format = setting('general.date_format', 'DD/MM/YYYY');

        $phpFormat = match ($format) {
            'MM/DD/YYYY' => 'm/d/Y',
            'YYYY-MM-DD' => 'Y-m-d',
            default       => 'd/m/Y',   // DD/MM/YYYY
        };

        return $withTime ? "{$phpFormat} H:i" : $phpFormat;
    }
}
