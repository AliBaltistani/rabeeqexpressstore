<?php

namespace Database\Seeders;

use App\Models\LoyaltyReward;
use Illuminate\Database\Seeder;

/**
 * OPTIONAL/CONFIGURABLE SEEDER
 * 
 * Creates loyalty reward tiers (discount coupons, free shipping, gift cards, etc.)
 * 
 * For production: Review and customize reward values and points costs before running.
 * 
 * Usage:
 * php artisan db:seed --class=Database\\Seeders\\LoyaltyRewardSeeder
 * 
 * Or add to DatabaseSeeder::run() if you want it to run with php artisan db:seed
 */

class LoyaltyRewardSeeder extends Seeder
{
    public function run(): void
    {
        $rewards = [
            // ── Discount Rewards ──
            [
                'slug'           => 'discount-20',
                'type'           => 'discount',
                'name'           => ['en' => 'Coupon discount 20 %', 'ar' => 'كوبون خصم 20%'],
                'description'    => [
                    'en' => 'Congratulations! You have won a discount coupon offered to you from the store to complete the required number of points.',
                    'ar' => 'مبروك لقد ربحت معنا خصم مقدم لك من متجرنا لإستكمالك عدد نقاط 2000 نقطة',
                ],
                'points_cost'    => 2000,
                'discount_value' => 20.00,
                'discount_type'  => 'percentage',
                'is_active'      => true,
                'sort_order'     => 1,
            ],
            [
                'slug'           => 'discount-10',
                'type'           => 'discount',
                'name'           => ['en' => 'Coupon discount 10 %', 'ar' => 'كوبون خصم 10%'],
                'description'    => [
                    'en' => 'Get a 10% discount coupon by redeeming your loyalty points.',
                    'ar' => 'احصل على كوبون خصم 10% باستبدال نقاط الولاء الخاصة بك.',
                ],
                'points_cost'    => 1000,
                'discount_value' => 10.00,
                'discount_type'  => 'percentage',
                'is_active'      => true,
                'sort_order'     => 2,
            ],
            [
                'slug'           => 'discount-5',
                'type'           => 'discount',
                'name'           => ['en' => 'Coupon discount 5 %', 'ar' => 'كوبون خصم 5%'],
                'description'    => [
                    'en' => 'A small discount to get you started. Redeem your points for a 5% off coupon.',
                    'ar' => 'خصم بسيط للبداية. استبدل نقاطك بكوبون خصم 5%.',
                ],
                'points_cost'    => 500,
                'discount_value' => 5.00,
                'discount_type'  => 'percentage',
                'is_active'      => true,
                'sort_order'     => 3,
            ],

            // ── Free Shipping Rewards ──
            [
                'slug'           => 'free-shipping-standard',
                'type'           => 'free_shipping',
                'name'           => ['en' => 'Free standard shipping', 'ar' => 'شحن عادي مجاني'],
                'description'    => [
                    'en' => 'Get free standard shipping on your next order by redeeming your loyalty points.',
                    'ar' => 'احصل على شحن عادي مجاني في طلبك التالي باستبدال نقاط الولاء.',
                ],
                'points_cost'    => 500,
                'discount_value' => null,
                'discount_type'  => 'fixed',
                'is_active'      => true,
                'sort_order'     => 1,
            ],
            [
                'slug'           => 'free-shipping-express',
                'type'           => 'free_shipping',
                'name'           => ['en' => 'Free express shipping', 'ar' => 'شحن سريع مجاني'],
                'description'    => [
                    'en' => 'Get free express shipping on your next order. Faster delivery, zero cost!',
                    'ar' => 'احصل على شحن سريع مجاني في طلبك التالي. توصيل أسرع بدون تكلفة!',
                ],
                'points_cost'    => 800,
                'discount_value' => null,
                'discount_type'  => 'fixed',
                'is_active'      => true,
                'sort_order'     => 2,
            ],
        ];

        foreach ($rewards as $reward) {
            LoyaltyReward::updateOrCreate(
                ['slug' => $reward['slug']],
                $reward
            );
        }
    }
}
