<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Country;

/**
 * OPTIONAL SEEDER
 * 
 * Creates list of supported countries (UAE, Saudi Arabia, Kuwait, Qatar, Bahrain, Oman).
 * 
 * Usage:
 * php artisan db:seed --class=Database\\Seeders\\CountrySeeder
 * 
 * Or add to DatabaseSeeder::run() if you want it to run with php artisan db:seed
 */

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            [
                'name_en' => 'United Arab Emirates',
                'name_ar' => 'الإمارات العربية المتحدة',
                'code' => 'AE',
                'phone_code' => '+971',
                'is_active' => true,
            ],
            [
                'name_en' => 'Saudi Arabia',
                'name_ar' => 'المملكة العربية السعودية',
                'code' => 'SA',
                'phone_code' => '+966',
                'is_active' => true,
            ],
            [
                'name_en' => 'Kuwait',
                'name_ar' => 'الكويت',
                'code' => 'KW',
                'phone_code' => '+965',
                'is_active' => true,
            ],
            [
                'name_en' => 'Bahrain',
                'name_ar' => 'البحرين',
                'code' => 'BH',
                'phone_code' => '+973',
                'is_active' => true,
            ],
            [
                'name_en' => 'Oman',
                'name_ar' => 'عُمان',
                'code' => 'OM',
                'phone_code' => '+968',
                'is_active' => true,
            ],
            [
                'name_en' => 'Qatar',
                'name_ar' => 'قطر',
                'code' => 'QA',
                'phone_code' => '+974',
                'is_active' => true,
            ],
            [
                'name_en' => 'Egypt',
                'name_ar' => 'مصر',
                'code' => 'EG',
                'phone_code' => '+20',
                'is_active' => true,
            ],
            [
                'name_en' => 'United Kingdom',
                'name_ar' => 'المملكة المتحدة',
                'code' => 'GB',
                'phone_code' => '+44',
                'is_active' => false,
            ],
            [
                'name_en' => 'United States',
                'name_ar' => 'الولايات المتحدة',
                'code' => 'US',
                'phone_code' => '+1',
                'is_active' => false,
            ],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(['code' => $country['code']], $country);
        }
    }
}
