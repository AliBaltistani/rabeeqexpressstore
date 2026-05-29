<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use Illuminate\Database\Seeder;

class ShippingMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'slug'                => 'standard-delivery',
                'name'                => ['en' => 'Standard Delivery', 'ar' => 'التوصيل العادي'],
                'description'         => ['en' => 'Delivered within 5-7 business days', 'ar' => 'يتم التوصيل خلال 5-7 أيام عمل'],
                'base_cost'           => 15.00,
                'carrier_type'        => 'standard',
                'is_active'           => true,
                'supported_countries' => null, // all countries
                'estimated_days_min'  => 5,
                'estimated_days_max'  => 7,
                'sort_order'          => 10,
            ],
            [
                'slug'                => 'smsa-express',
                'name'                => ['en' => 'SMSA Express', 'ar' => 'سمسا إكسبريس'],
                'description'         => ['en' => 'Express delivery via SMSA within 1-3 days', 'ar' => 'توصيل سريع عبر سمسا خلال 1-3 أيام'],
                'base_cost'           => 35.00,
                'carrier_type'        => 'smsa',
                'is_active'           => true,
                'supported_countries' => ['SA', 'BH', 'AE', 'KW', 'OM', 'QA'],
                'estimated_days_min'  => 1,
                'estimated_days_max'  => 3,
                'sort_order'          => 5,
            ],
            [
                'slug'                => 'local-pickup',
                'name'                => ['en' => 'Local Pickup', 'ar' => 'الاستلام من المتجر'],
                'description'         => ['en' => 'Pick up from our store location', 'ar' => 'الاستلام من موقع متجرنا'],
                'base_cost'           => 0.00,
                'carrier_type'        => 'local',
                'is_active'           => true,
                'supported_countries' => ['BH'],
                'estimated_days_min'  => 0,
                'estimated_days_max'  => 1,
                'sort_order'          => 1,
            ],
        ];

        foreach ($methods as $method) {
            ShippingMethod::updateOrCreate(
                ['slug' => $method['slug']],
                $method
            );
        }
    }
}
