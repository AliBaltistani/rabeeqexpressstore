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
     * Return the DEFAULT DISPLAY currency code.
     * This is the currency shown first to new visitors (admin-controlled via is_default).
     * ⚠️  Do NOT use this for price conversion math — use store_currency_code() instead.
     */
    function currency_code(): string
    {
        $defaultCurrency = \App\Models\Currency::getDefault();
        return $defaultCurrency?->code
            ?? setting('general.default_currency', 'SAR');
    }
}

if (!function_exists('store_currency_symbol')) {
    /**
     * Return the SYMBOL of the store/base currency — the currency in which all
     * product prices are physically stored in the database.
     *
     * Use this in admin price fields and anywhere you need to label an entered
     * price, to avoid confusion when the display default differs from the base.
     */
    function store_currency_symbol(): string
    {
        $code = store_currency_code();
        $currency = \App\Models\Currency::where('code', $code)->first();
        return $currency?->symbol ?? $code;
    }
}

if (!function_exists('store_currency_code')) {
    /**
     * Return the STORE/BASE currency code — the currency in which all product
     * prices are physically stored in the database.
     *
     * This is INDEPENDENT of which currency is set as the display default.
     * All currency conversion math must convert FROM this value.
     *
     * Default: 'SAR' (set via Admin → Currencies → Store Currency).
     * Only change this if you also re-enter all product prices in the new currency.
     */
    function store_currency_code(): string
    {
        return setting('general.store_currency', 'SAR');
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
