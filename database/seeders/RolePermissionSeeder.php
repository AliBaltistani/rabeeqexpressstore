<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles for admin guard (skip if already exists)
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'admin']);
        $manager = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'admin']);
        $editor = Role::firstOrCreate(['name' => 'Editor', 'guard_name' => 'admin']);
        $support = Role::firstOrCreate(['name' => 'Support', 'guard_name' => 'admin']);

        // Create permissions (core module permissions)
        $permissions = [
            // Dashboard
            'dashboard.view',

            // Products
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',

            // Categories
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',

            // Brands
            'brands.view',
            'brands.create',
            'brands.edit',
            'brands.delete',

            // Orders
            'orders.view',
            'orders.edit',
            'orders.delete',
            'orders.export',

            // Customers
            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',

            // Coupons
            'coupons.view',
            'coupons.create',
            'coupons.edit',
            'coupons.delete',

            // Blog
            'blog.view',
            'blog.create',
            'blog.edit',
            'blog.delete',

            // CMS Pages
            'pages.view',
            'pages.create',
            'pages.edit',
            'pages.delete',

            // Settings
            'settings.view',
            'settings.edit',

            // Admins & Roles
            'admins.view',
            'admins.create',
            'admins.edit',
            'admins.delete',
            'roles.manage',

            // Reviews
            'reviews.view',
            'reviews.approve',
            'reviews.reject',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }

        // Assign all permissions to Super Admin
        $superAdmin->syncPermissions(Permission::where('guard_name', 'admin')->get());

        // Assign specific permissions to Manager
        $managerPermissions = [
            'dashboard.view',
            'products.view', 'products.create', 'products.edit',
            'categories.view', 'categories.create', 'categories.edit',
            'orders.view', 'orders.edit', 'orders.export',
            'customers.view',
            'coupons.view', 'coupons.create', 'coupons.edit',
        ];
        $manager->syncPermissions($managerPermissions);

        // Assign specific permissions to Editor
        $editorPermissions = [
            'dashboard.view',
            'blog.view', 'blog.create', 'blog.edit', 'blog.delete',
            'pages.view', 'pages.create', 'pages.edit', 'pages.delete',
            'products.view',
            'reviews.view', 'reviews.approve', 'reviews.reject',
        ];
        $editor->syncPermissions($editorPermissions);

        // Assign specific permissions to Support
        $supportPermissions = [
            'dashboard.view',
            'orders.view',
            'customers.view',
            'reviews.view',
        ];
        $support->syncPermissions($supportPermissions);
    }
}
