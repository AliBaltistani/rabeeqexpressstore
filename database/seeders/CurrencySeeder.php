<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            [
                'name' => 'Saudi Riyal',
                'code' => 'SAR',
                'symbol' => 'ر.س',
                'exchange_rate' => 1.0,
                'is_default' => true,
                'is_active' => true,
                'decimal_places' => 2,
            ],
            [
                'name' => 'United Arab Emirates Dirham',
                'code' => 'AED',
                'symbol' => 'د.إ',
                'exchange_rate' => 0.92,
                'is_default' => false,
                'is_active' => true,
                'decimal_places' => 2,
            ],
            [
                'name' => 'Bahraini Dinar',
                'code' => 'BHD',
                'symbol' => 'ب.د',
                'exchange_rate' => 1.06,
                'is_default' => false,
                'is_active' => true,
                'decimal_places' => 3,
            ],
            [
                'name' => 'Kuwaiti Dinar',
                'code' => 'KWD',
                'symbol' => 'د.ك',
                'exchange_rate' => 1.22,
                'is_default' => false,
                'is_active' => true,
                'decimal_places' => 3,
            ],
            [
                'name' => 'Qatari Riyal',
                'code' => 'QAR',
                'symbol' => 'ر.ق',
                'exchange_rate' => 0.98,
                'is_default' => false,
                'is_active' => true,
                'decimal_places' => 2,
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
