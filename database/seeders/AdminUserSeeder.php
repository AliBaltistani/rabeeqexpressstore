<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin user if not already exists
        $superAdmin = Admin::firstOrCreate(
            ['email' => 'admin@eseven-store.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt(env('ADMIN_DEFAULT_PASSWORD', 'password')),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        
        // Assign Super Admin role
        $superAdmin->assignRole('Super Admin');

        // Create Manager user if not already exists
        $manager = Admin::firstOrCreate(
            ['email' => 'manager@eseven-store.com'],
            [
                'name' => 'Store Manager',
                'password' => bcrypt('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        
        // Assign Manager role
        $manager->assignRole('Manager');

        // Create Editor user if not already exists
        $editor = Admin::firstOrCreate(
            ['email' => 'editor@eseven-store.com'],
            [
                'name' => 'Content Editor',
                'password' => bcrypt('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        
        // Assign Editor role
        $editor->assignRole('Editor');

        // Create Support user if not already exists
        $support = Admin::firstOrCreate(
            ['email' => 'support@eseven-store.com'],
            [
                'name' => 'Support Agent',
                'password' => bcrypt('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        
        // Assign Support role
        $support->assignRole('Support');
    }
}
