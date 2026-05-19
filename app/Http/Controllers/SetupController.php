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
        $expectedToken = env('SETUP_TOKEN') ?? 'eseven-deploy-2026';

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
     * Create storage symlink.
     */
    public function storageLink(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            if (file_exists(public_path('storage'))) {
                return response()->json(['message' => 'Storage link already exists']);
            }

            Artisan::call('storage:link');
            return response()->json([
                'message' => 'Storage link created',
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
