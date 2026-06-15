<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Tag;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * DEVELOPMENT ONLY SEEDER
 * 
 * This seeder creates fake/dummy products, categories, brands, and test users.
 * 
 * ⚠️ NEVER run this on production!
 * 
 * Usage (local development only):
 * php artisan db:seed --class=Database\\Seeders\\DummyDataSeeder
 * 
 * To run with migrations and dummy data:
 * php artisan migrate:fresh --seed (this will NOT include DummyDataSeeder)
 * 
 * To include dummy data in local development:
 * 1. Run migrations and essential seeders first
 * 2. Then manually call: php artisan db:seed --class=DummyDataSeeder
 */

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Download a placeholder image
        $imageDir = 'dummy';
        $imageName = 'placeholder.jpg';
        $imagePath = $imageDir . '/' . $imageName;
        
        if (!Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->makeDirectory($imageDir);
            $imageContent = @file_get_contents('https://picsum.photos/800/800');
            if ($imageContent !== false) {
                 Storage::disk('public')->put($imagePath, $imageContent);
            }
        }

        // Create Users
        for ($i = 1; $i <= 5; $i++) {
            User::firstOrCreate(
                ['email' => "user{$i}@example.com"],
                [
                    'name' => "Customer {$i}",
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }

        // Create Categories
        $categories = [
            ['name' => ['en' => 'Electronics', 'ar' => 'إلكترونيات'], 'slug' => 'electronics', 'desc' => ['en' => 'All electronic items', 'ar' => 'جميع الأجهزة الإلكترونية']],
            ['name' => ['en' => 'Fashion', 'ar' => 'أزياء'], 'slug' => 'fashion', 'desc' => ['en' => 'Latest fashion trends', 'ar' => 'أحدث صيحات الموضة']],
            ['name' => ['en' => 'Home & Garden', 'ar' => 'المنزل والحديقة'], 'slug' => 'home-garden', 'desc' => ['en' => 'Home appliances and garden tools', 'ar' => 'أدوات المنزل والحديقة']],
            ['name' => ['en' => 'Sports', 'ar' => 'رياضة'], 'slug' => 'sports', 'desc' => ['en' => 'Sports equipment', 'ar' => 'معدات رياضية']],
            ['name' => ['en' => 'Toys', 'ar' => 'ألعاب'], 'slug' => 'toys', 'desc' => ['en' => 'Kids toys', 'ar' => 'ألعاب أطفال']],
        ];
        
        $catIds = [];
        foreach ($categories as $catData) {
            $cat = Category::firstOrCreate(
                ['slug' => $catData['slug']],
                [
                    'name' => $catData['name'],
                    'description' => $catData['desc'],
                    'is_active' => true,
                    'image' => Storage::disk('public')->exists($imagePath) ? $imagePath : null,
                ]
            );
            $catIds[] = $cat->id;
        }

        // Create Brands
        $brands = [
            ['name' => 'Apple', 'slug' => 'apple', 'desc' => 'Apple Inc.'],
            ['name' => 'Samsung', 'slug' => 'samsung', 'desc' => 'Samsung Electronics'],
            ['name' => 'Sony', 'slug' => 'sony', 'desc' => 'Sony Corporation'],
            ['name' => 'Nike', 'slug' => 'nike', 'desc' => 'Nike Inc.'],
            ['name' => 'Adidas', 'slug' => 'adidas', 'desc' => 'Adidas AG'],
        ];

        $brandIds = [];
        foreach ($brands as $brandData) {
            $brand = Brand::firstOrCreate(
                ['slug' => $brandData['slug']],
                [
                    'name' => $brandData['name'],
                    'description' => $brandData['desc'],
                    'is_active' => true,
                    'logo' => Storage::disk('public')->exists($imagePath) ? $imagePath : null,
                ]
            );
            $brandIds[] = $brand->id;
        }

        // Create Tags
        $tags = ['Sale', 'New', 'Featured', 'Hot', 'Trendy'];
        $tagIds = [];
        foreach ($tags as $tag) {
            $t = Tag::firstOrCreate(
                ['slug' => Str::slug($tag)],
                [
                    'name' => ['en' => $tag, 'ar' => 'ع-' . $tag],
                ]
            );
            $tagIds[] = $t->id;
        }

        // Create Products
        for ($i = 1; $i <= 20; $i++) {
            $productName = "Awesome Product " . $i;
            $slug = Str::slug($productName);
            
            $product = Product::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ['en' => $productName, 'ar' => 'منتج رائع ' . $i],
                    'sku' => 'SKU-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                    'category_id' => $catIds[array_rand($catIds)],
                    'brand_id' => $brandIds[array_rand($brandIds)],
                    'price' => rand(10, 1000) + 0.99,
                    'stock_quantity' => rand(10, 100),
                    'is_active' => true,
                    'short_description' => ['en' => 'Short description for ' . $productName, 'ar' => 'وصف قصير ' . $i],
                    'description' => ['en' => 'Long description for ' . $productName . '. This product is amazing and comes with many features.', 'ar' => 'وصف طويل ' . $i . '. هذا المنتج رائع ويأتي مع العديد من الميزات.'],
                ]
            );

            // Add Tags
            if ($product->tags()->count() === 0) {
                $randomKeys = (array) array_rand($tagIds, rand(1, 3));
                $selectedTags = array_map(function($key) use ($tagIds) { return $tagIds[$key]; }, $randomKeys);
                $product->tags()->attach($selectedTags);
            }

            // Add Images
            if ($product->images()->count() === 0 && Storage::disk('public')->exists($imagePath)) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $imagePath,
                    'is_primary' => true,
                ]);
                
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $imagePath,
                    'is_primary' => false,
                ]);
            }
        }
    }
}
