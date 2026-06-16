<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * IMPORTANT for Production:
     * Set unique, strong passwords in .env for each role:
     * - ADMIN_DEFAULT_PASSWORD=your-super-admin-password
     * - MANAGER_PASSWORD=your-manager-password
     * - EDITOR_PASSWORD=your-editor-password
     * - SUPPORT_PASSWORD=your-support-password
     */
    public function run(): void
    {
        $defaultPassword = env('ADMIN_DEFAULT_PASSWORD', 'password');
        $managerPassword = env('MANAGER_PASSWORD', 'password');
        $editorPassword = env('EDITOR_PASSWORD', 'password');
        $supportPassword = env('SUPPORT_PASSWORD', 'password');

        // Create Super Admin user if not already exists
        $superAdmin = Admin::firstOrCreate(
            ['email' => 'admin@rabeeq-express-store.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt($defaultPassword),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        
        // Assign Super Admin role
        $superAdmin->assignRole('Super Admin');

        // Create Manager user if not already exists
        $manager = Admin::firstOrCreate(
            ['email' => 'manager@rabeeq-express-store.com'],
            [
                'name' => 'Store Manager',
                'password' => bcrypt($managerPassword),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        
        // Assign Manager role
        $manager->assignRole('Manager');

        // Create Editor user if not already exists
        $editor = Admin::firstOrCreate(
            ['email' => 'editor@rabeeq-express-store.com'],
            [
                'name' => 'Content Editor',
                'password' => bcrypt($editorPassword),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        
        // Assign Editor role
        $editor->assignRole('Editor');

        // Create Support user if not already exists
        $support = Admin::firstOrCreate(
            ['email' => 'support@rabeeq-express-store.com'],
            [
                'name' => 'Support Agent',
                'password' => bcrypt($supportPassword),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        
        // Assign Support role
        $support->assignRole('Support');
    }
}
