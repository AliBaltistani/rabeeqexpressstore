<?php

namespace Database\Seeders;

use App\Models\HomeSection;
use Illuminate\Database\Seeder;

/**
 * OPTIONAL/MANUAL SEEDER
 * 
 * Creates homepage section templates (hero slider, promo banners, featured products, etc.)
 * 
 * Usage:
 * php artisan db:seed --class=Database\\Seeders\\HomeSectionSeeder
 * 
 * Or add to DatabaseSeeder::run() if you want it to run with php artisan db:seed
 */

class HomeSectionSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            [
                'type' => 'hero_slider',
                'title' => ['en' => 'Hero Slider', 'ar' => 'السلايدر الرئيسي'],
                'is_active' => true,
                'sort_order' => 1,
                'config' => [],
            ],
            [
                'type' => 'promo_banners',
                'title' => ['en' => 'Promo Banners', 'ar' => 'بنرات ترويجية'],
                'is_active' => true,
                'sort_order' => 2,
                'config' => [],
            ],
            [
                'type' => 'featured_products',
                'title' => ['en' => 'Featured Products', 'ar' => 'منتجات مميزة'],
                'is_active' => true,
                'sort_order' => 3,
                'config' => ['limit' => 10],
            ],
            [
                'type' => 'best_sellers',
                'title' => ['en' => 'Best Sellers', 'ar' => 'الأكثر مبيعاً'],
                'is_active' => true,
                'sort_order' => 4,
                'config' => ['limit' => 10],
            ],
            [
                'type' => 'new_arrivals',
                'title' => ['en' => 'New Arrivals', 'ar' => 'وصل حديثاً'],
                'is_active' => true,
                'sort_order' => 5,
                'config' => ['limit' => 10],
            ],
            [
                'type' => 'reviews',
                'title' => ['en' => 'Customer Reviews', 'ar' => 'آراء العملاء'],
                'is_active' => true,
                'sort_order' => 6,
                'config' => [
                    'reviews' => [
                        ['name' => 'سلوى الحوطي', 'avatar' => 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_female.png', 'rating' => 5, 'text' => 'الشوز مريح جدا'],
                        ['name' => 'Gharam .', 'avatar' => 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_female.png', 'rating' => 5, 'text' => 'Very comfortable shoes and fast delivery!'],
                        ['name' => 'محمد العتيبي', 'avatar' => 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_male.png', 'rating' => 5, 'text' => 'جودة ممتازة وسعر مناسب'],
                        ['name' => 'Sarah K.', 'avatar' => 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_female.png', 'rating' => 5, 'text' => 'Amazing quality, will order again!'],
                        ['name' => 'عبدالله الشمري', 'avatar' => 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_male.png', 'rating' => 4, 'text' => 'الحذاء جميل والتوصيل سريع'],
                        ['name' => 'Nora A.', 'avatar' => 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_female.png', 'rating' => 5, 'text' => 'Best store for shoes in Saudi!'],
                        ['name' => 'فهد القحطاني', 'avatar' => 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_male.png', 'rating' => 5, 'text' => 'تجربة رائعة'],
                        ['name' => 'Lina M.', 'avatar' => 'https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_female.png', 'rating' => 5, 'text' => 'عجبتني'],
                    ],
                ],
            ],
        ];

        // foreach ($sections as $section) {
        //     HomeSection::updateOrCreate(
        //         ['type' => $section['type'], 'sort_order' => $section['sort_order']],
        //         $section
        //     );
        // }
    }
}
