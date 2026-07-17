<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ─── Seed the Store Base Currency setting (prices are stored in SAR) ───
        // This is independent of which currency is set as the display default.
        // Store base currency = the currency product prices are entered in.
        if (!Setting::get('general.store_currency')) {
            Setting::set('general.store_currency', 'SAR');
        }

        // ─── Exchange rates are relative to SAR (the base, rate = 1.0) ───
        // Rule: exchange_rate = how many units of THIS currency equal 1 SAR.
        // Example: 1 SAR ≈ 3.75 AED → AED exchange_rate = 3.75
        //          1 SAR ≈ 0.099 BHD → BHD exchange_rate = 0.099
        $currencies = [
            [
                'name'          => 'Saudi Riyal',
                'code'          => 'SAR',
                'symbol'        => 'ر.س',
                'exchange_rate' => 1.0,       // base currency — always 1.0
                'is_default'    => true,
                'is_active'     => true,
                'decimal_places'=> 2,
            ],
            [
                'name'          => 'United Arab Emirates Dirham',
                'code'          => 'AED',
                'symbol'        => 'د.إ',
                'exchange_rate' => 3.75,      // 1 SAR ≈ 3.75 AED
                'is_default'    => false,
                'is_active'     => true,
                'decimal_places'=> 2,
            ],
            [
                'name'          => 'Bahraini Dinar',
                'code'          => 'BHD',
                'symbol'        => 'ب.د',
                'exchange_rate' => 0.099,     // 1 SAR ≈ 0.099 BHD
                'is_default'    => false,
                'is_active'     => true,
                'decimal_places'=> 3,
            ],
            [
                'name'          => 'Kuwaiti Dinar',
                'code'          => 'KWD',
                'symbol'        => 'د.ك',
                'exchange_rate' => 0.082,     // 1 SAR ≈ 0.082 KWD
                'is_default'    => false,
                'is_active'     => true,
                'decimal_places'=> 3,
            ],
            [
                'name'          => 'Qatari Riyal',
                'code'          => 'QAR',
                'symbol'        => 'ر.ق',
                'exchange_rate' => 1.025,     // 1 SAR ≈ 1.025 QAR
                'is_default'    => false,
                'is_active'     => true,
                'decimal_places'=> 2,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::firstOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }
    }
}
