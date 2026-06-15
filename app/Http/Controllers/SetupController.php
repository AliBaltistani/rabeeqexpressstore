<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * SetupController — Run deployment commands via a secure web route.
 *
 * Usage:
 *   GET /setup/run?token=YOUR_SETUP_TOKEN
 *   GET /setup/migrate?token=YOUR_SETUP_TOKEN
 *   GET /setup/seed?token=YOUR_SETUP_TOKEN
 *   GET /setup/cache?token=YOUR_SETUP_TOKEN
 *   GET /setup/clear?token=YOUR_SETUP_TOKEN
 *   GET /setup/storage-link?token=YOUR_SETUP_TOKEN
 *   GET /setup/status?token=YOUR_SETUP_TOKEN
 */
class SetupController extends Controller
{
    /**
     * Secret token — must match SETUP_TOKEN in .env
     */
    private function authorize(Request $request): bool
    {
        $token = $request->query('token');
        // Use env() directly since this runs before config caching takes effect,
        // and env() may return null when config IS cached, so use a hardcoded fallback.
        $expectedToken = env('SETUP_TOKEN') ?? 'rabeq-deploy-2026';

        return $token === $expectedToken;
    }

    /**
     * Run ALL setup steps (full deployment).
     */
    public function run(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $results = [];

        // 1. Test database connection
        try {
            DB::connection()->getPdo();
            $results[] = '✅ Database connection successful (' . DB::connection()->getDatabaseName() . ')';
        } catch (\Exception $e) {
            $results[] = '❌ Database connection FAILED: ' . $e->getMessage();
            return response()->json(['results' => $results], 500);
        }

        // 2. Run migrations
        try {
            Artisan::call('migrate', ['--force' => true]);
            $results[] = '✅ Migrations: ' . trim(Artisan::output());
        } catch (\Exception $e) {
            $results[] = '❌ Migration failed: ' . $e->getMessage();
        }

        // 3. Storage link
        try {
            if (!file_exists(public_path('storage'))) {
                Artisan::call('storage:link');
                $results[] = '✅ Storage link created';
            } else {
                $results[] = '⏭️ Storage link already exists';
            }
        } catch (\Exception $e) {
            $results[] = '❌ Storage link failed: ' . $e->getMessage();
        }

        // 4. Cache configuration
        try {
            Artisan::call('config:cache');
            $results[] = '✅ Config cached';
        } catch (\Exception $e) {
            $results[] = '❌ Config cache failed: ' . $e->getMessage();
        }

        // 5. Cache routes
        try {
            Artisan::call('route:cache');
            $results[] = '✅ Routes cached';
        } catch (\Exception $e) {
            $results[] = '❌ Route cache failed: ' . $e->getMessage();
        }

        // 6. Cache views
        try {
            Artisan::call('view:cache');
            $results[] = '✅ Views cached';
        } catch (\Exception $e) {
            $results[] = '❌ View cache failed: ' . $e->getMessage();
        }

        // 7. Cache events
        try {
            Artisan::call('event:cache');
            $results[] = '✅ Events cached';
        } catch (\Exception $e) {
            $results[] = '❌ Event cache failed: ' . $e->getMessage();
        }

        // 8. Filament assets
        try {
            Artisan::call('filament:assets');
            $results[] = '✅ Filament assets published';
        } catch (\Exception $e) {
            $results[] = '❌ Filament assets failed: ' . $e->getMessage();
        }

        // 9. Icons cache
        try {
            Artisan::call('icons:cache');
            $results[] = '✅ Icons cached';
        } catch (\Exception $e) {
            $results[] = '⚠️ Icons cache skipped: ' . $e->getMessage();
        }

        return response()->json([
            'message' => 'Setup completed!',
            'results' => $results,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
        ]);
    }

    /**
     * Run only migrations.
     */
    public function migrate(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            return response()->json([
                'message' => 'Migration completed',
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Run database seeders.
     */
    public function seed(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            Artisan::call('db:seed', ['--force' => true]);
            return response()->json([
                'message' => 'Seeding completed',
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Run ONLY production-safe seeders (no factories/faker).
     */
    public function productionSeed(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $seeders = [
            \Database\Seeders\RolePermissionSeeder::class,
            \Database\Seeders\AdminUserSeeder::class,
            \Database\Seeders\CurrencySeeder::class,
            \Database\Seeders\LanguageSeeder::class,
            \Database\Seeders\CountrySeeder::class,
        ];

        $results = [];

        foreach ($seeders as $seeder) {
            try {
                Artisan::call('db:seed', [
                    '--class' => $seeder,
                    '--force' => true,
                ]);
                $results[] = '✅ ' . class_basename($seeder) . ': ' . trim(Artisan::output());
            } catch (\Exception $e) {
                $results[] = '❌ ' . class_basename($seeder) . ': ' . $e->getMessage();
            }
        }

        return response()->json([
            'message' => 'Production seeding completed',
            'results' => $results,
        ]);
    }

    /**
     * Cache all configs, routes, views.
     */
    public function cache(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $results = [];

        try {
            Artisan::call('config:cache');
            $results[] = '✅ Config cached';

            Artisan::call('route:cache');
            $results[] = '✅ Routes cached';

            Artisan::call('view:cache');
            $results[] = '✅ Views cached';

            Artisan::call('event:cache');
            $results[] = '✅ Events cached';

            Artisan::call('filament:assets');
            $results[] = '✅ Filament assets published';
        } catch (\Exception $e) {
            $results[] = '❌ Error: ' . $e->getMessage();
        }

        return response()->json([
            'message' => 'Caching completed',
            'results' => $results,
        ]);
    }

    /**
     * Clear all caches (useful for debugging).
     */
    public function clear(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $results = [];

        try {
            Artisan::call('config:clear');
            $results[] = '✅ Config cache cleared';

            Artisan::call('route:clear');
            $results[] = '✅ Route cache cleared';

            Artisan::call('view:clear');
            $results[] = '✅ View cache cleared';

            Artisan::call('cache:clear');
            $results[] = '✅ Application cache cleared';
        } catch (\Exception $e) {
            $results[] = '❌ Error: ' . $e->getMessage();
        }

        return response()->json([
            'message' => 'All caches cleared',
            'results' => $results,
        ]);
    }

    /**
     * Create storage symlink (with shared-hosting fallback).
     */
    public function storageLink(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $publicStoragePath = public_path('storage');
        $storagePath = storage_path('app/public');
        $results = [];

        // Diagnostic info
        $results['public_path'] = public_path();
        $results['storage_target'] = $storagePath;
        $results['link_path'] = $publicStoragePath;
        $results['target_exists'] = is_dir($storagePath);

        // Ensure the target directory exists
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
            $results['created_target'] = true;
        }

        // Check if link/dir already exists
        if (is_link($publicStoragePath)) {
            $currentTarget = readlink($publicStoragePath);
            $results['status'] = 'symlink_exists';
            $results['current_target'] = $currentTarget;
            $results['target_valid'] = is_dir($publicStoragePath);

            // If it points to wrong place, remove and recreate
            if (realpath($currentTarget) !== realpath($storagePath)) {
                unlink($publicStoragePath);
                $results['removed_bad_link'] = true;
            } else {
                return response()->json([
                    'message' => '✅ Storage symlink already exists and is correct',
                    'details' => $results,
                ]);
            }
        } elseif (is_dir($publicStoragePath)) {
            $results['status'] = 'directory_exists_not_symlink';
            return response()->json([
                'message' => '⚠️ public/storage exists as a real directory (not a symlink). Remove it first if you want a symlink.',
                'details' => $results,
            ]);
        }

        // Attempt 1: Artisan storage:link
        try {
            Artisan::call('storage:link');
            $output = trim(Artisan::output());
            if (is_link($publicStoragePath) || is_dir($publicStoragePath)) {
                return response()->json([
                    'message' => '✅ Storage link created via artisan',
                    'output' => $output,
                    'details' => $results,
                ]);
            }
        } catch (\Exception $e) {
            $results['artisan_error'] = $e->getMessage();
        }

        // Attempt 2: PHP symlink() directly
        try {
            if (function_exists('symlink')) {
                @symlink($storagePath, $publicStoragePath);
                if (is_link($publicStoragePath)) {
                    return response()->json([
                        'message' => '✅ Storage link created via PHP symlink()',
                        'details' => $results,
                    ]);
                }
            }
            $results['symlink_available'] = function_exists('symlink');
        } catch (\Exception $e) {
            $results['symlink_error'] = $e->getMessage();
        }

        // Attempt 3: Provide manual instructions
        return response()->json([
            'message' => '❌ Could not create symlink automatically. Use Hostinger File Manager or SSH.',
            'manual_instructions' => [
                'option_1_ssh' => "ln -s {$storagePath} {$publicStoragePath}",
                'option_2_file_manager' => 'In Hostinger File Manager, navigate to public/ and create a symbolic link named "storage" pointing to ../storage/app/public',
                'option_3_htaccess' => 'Add a rewrite rule to serve storage files directly (see details)',
            ],
            'details' => $results,
        ], 500);
    }

    /**
     * Server status and diagnostics.
     */
    public function status(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $checks = [];

        // PHP version
        $checks['php_version'] = PHP_VERSION;
        $checks['laravel_version'] = app()->version();
        $checks['environment'] = app()->environment();
        $checks['debug_mode'] = config('app.debug');
        $checks['app_url'] = config('app.url');

        // Database
        try {
            DB::connection()->getPdo();
            $checks['database'] = '✅ Connected (' . DB::connection()->getDatabaseName() . ')';
        } catch (\Exception $e) {
            $checks['database'] = '❌ ' . $e->getMessage();
        }

        // Storage link
        $checks['storage_link'] = file_exists(public_path('storage')) ? '✅ Exists' : '❌ Missing';

        // Directory permissions
        $checks['storage_writable'] = is_writable(storage_path()) ? '✅ Writable' : '❌ Not writable';
        $checks['cache_writable'] = is_writable(base_path('bootstrap/cache')) ? '✅ Writable' : '❌ Not writable';

        // SPA index.html
        $checks['spa_index'] = file_exists(public_path('index.html')) ? '✅ Exists' : '❌ Missing (Vue SPA not deployed)';

        // PHP extensions
        $requiredExtensions = ['pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'fileinfo', 'gd'];
        $extensions = [];
        foreach ($requiredExtensions as $ext) {
            $extensions[$ext] = extension_loaded($ext) ? '✅' : '❌';
        }
        $checks['php_extensions'] = $extensions;

        return response()->json($checks);
    }
}
