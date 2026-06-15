<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * 
     * PRODUCTION SEEDERS (Safe for real server):
     * - RolePermissionSeeder: Creates admin roles and permissions
     * - AdminUserSeeder: Creates admin accounts (credentials in .env)
     * - CurrencySeeder: Creates multi-currency support (SAR, AED, BHD, KWD, QAR)
     * - LanguageSeeder: Creates languages (EN, AR)
     * - ShippingMethodSeeder: Creates shipping methods (Standard, Express, International)
     * - ShippingCarrierSeeder: Creates carrier integrations (SMSA, Aramex, DHL)
     * - CmsPagesSeeder: Creates legal pages (About, Privacy, Terms, Return Policy)
     * 
     * DEVELOPMENT ONLY:
     * - DummyDataSeeder: Creates fake products/users for testing (NEVER run on production)
     * - HomeSectionSeeder: Creates homepage sections (run manually when needed)
     * - LoyaltyRewardSeeder: Creates loyalty rewards (configure manually for production)
     * - CountrySeeder: Creates country list (optional, run if needed)
     */
    public function run(): void
    {
        // Seed in correct order: roles first, then admins, then other configuration data
        $this->call([
            // RolePermissionSeeder::class,
            AdminUserSeeder::class,
            CurrencySeeder::class,
            LanguageSeeder::class,
            ShippingMethodSeeder::class,
            ShippingCarrierSeeder::class,
            CmsPagesSeeder::class,
            CountrySeeder::class, // Optional, run if you want to pre-populate countries
        ]);
    }
}
