<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * SetupController — Full server management dashboard + first-time installer.
 *
 * Replaces all SSH/terminal access for Hostinger shared hosting.
 * Protected by SETUP_TOKEN in .env.
 *
 * Dashboard: GET /setup?token=YOUR_SETUP_TOKEN
 */
class SetupController extends Controller
{
    /**
     * Secret token — must match SETUP_TOKEN in .env
     */
    private function authorize(Request $request): bool
    {
        $token = $request->query('token');
        $expectedToken = env('SETUP_TOKEN') ?? config('app.setup_token', 'eseven-deploy-2026');

        return $token === $expectedToken;
    }

    /**
     * Get the token from request for embedding in dashboard links.
     */
    private function getToken(Request $request): string
    {
        return $request->query('token', '');
    }

    // =========================================================================
    //  DASHBOARD — Beautiful HTML management panel
    // =========================================================================

    public function dashboard(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $token = $this->getToken($request);

        // Quick status checks for the dashboard
        $dbOk = false;
        $dbName = '';
        try {
            DB::connection()->getPdo();
            $dbOk = true;
            $dbName = DB::connection()->getDatabaseName();
        } catch (\Exception $e) {
            // DB not connected
        }

        $storageLinked = file_exists(public_path('storage'));
        $spaDeployed = file_exists(public_path('index.html'));
        $isMaintenanceMode = app()->isDownForMaintenance();
        $env = app()->environment();
        $phpVersion = PHP_VERSION;
        $laravelVersion = app()->version();

        // Count pending jobs & failed jobs
        $pendingJobs = 0;
        $failedJobs = 0;
        try {
            if (Schema::hasTable('jobs')) {
                $pendingJobs = DB::table('jobs')->count();
            }
            if (Schema::hasTable('failed_jobs')) {
                $failedJobs = DB::table('failed_jobs')->count();
            }
        } catch (\Exception $e) {
            // Tables may not exist yet
        }

        // Get all available seeders
        $seeders = $this->getAvailableSeeders();

        $html = $this->renderDashboardHtml(
            $token, $dbOk, $dbName, $storageLinked, $spaDeployed,
            $isMaintenanceMode, $env, $phpVersion, $laravelVersion,
            $pendingJobs, $failedJobs, $seeders
        );

        return response($html, 200)->header('Content-Type', 'text/html');
    }

    // =========================================================================
    //  DEPLOYMENT — Full setup, migrate, seed, storage
    // =========================================================================

    /**
     * Run ALL setup steps (full first-time installation).
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
            'message' => '🚀 Full setup completed!',
            'results' => $results,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
        ]);
    }

    // =========================================================================
    //  DATABASE — Migrations
    // =========================================================================

    public function migrate(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            return response()->json([
                'message' => '✅ Migration completed',
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function migrateStatus(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            Artisan::call('migrate:status');
            $output = trim(Artisan::output());

            // Parse the output into structured data
            $lines = explode("\n", $output);
            $migrations = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '---') || str_starts_with($line, 'Migration')) {
                    continue;
                }
                if (str_contains($line, 'Ran') || str_contains($line, 'Pending')) {
                    $migrations[] = $line;
                }
            }

            return response()->json([
                'message' => '📋 Migration Status',
                'output' => $output,
                'count' => count($migrations),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function migrateRollback(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $steps = (int) $request->query('steps', 1);

        try {
            Artisan::call('migrate:rollback', ['--force' => true, '--step' => $steps]);
            return response()->json([
                'message' => "✅ Rolled back {$steps} migration batch(es)",
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function migrateFresh(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Extra safety — require explicit confirmation parameter
        if ($request->query('confirm') !== 'yes-destroy-all-data') {
            return response()->json([
                'error' => '⚠️ This will DESTROY ALL DATA. Add &confirm=yes-destroy-all-data to proceed.',
            ], 422);
        }

        try {
            Artisan::call('migrate:fresh', ['--force' => true]);
            return response()->json([
                'message' => '✅ Database wiped and re-migrated',
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    //  DATABASE — Seeders
    // =========================================================================

    public function seed(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            Artisan::call('db:seed', ['--force' => true]);
            return response()->json([
                'message' => '✅ Seeding completed',
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

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
            \Database\Seeders\ShippingCarrierSeeder::class,
            \Database\Seeders\ShippingMethodSeeder::class,
            \Database\Seeders\CmsPagesSeeder::class,
            \Database\Seeders\HomeSectionSeeder::class,
            \Database\Seeders\LoyaltyRewardSeeder::class,
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
            'message' => '✅ Production seeding completed',
            'results' => $results,
        ]);
    }

    public function seedClass(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $class = $request->query('class');
        if (!$class) {
            return response()->json([
                'error' => 'Provide ?class=SeederName (e.g., class=AdminUserSeeder)',
                'available' => $this->getAvailableSeeders(),
            ], 422);
        }

        // Resolve full class name
        $fullClass = "Database\\Seeders\\{$class}";
        if (!class_exists($fullClass)) {
            return response()->json([
                'error' => "Seeder class '{$fullClass}' not found",
                'available' => $this->getAvailableSeeders(),
            ], 404);
        }

        try {
            Artisan::call('db:seed', [
                '--class' => $fullClass,
                '--force' => true,
            ]);
            return response()->json([
                'message' => "✅ {$class} executed",
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    //  CACHE — Optimize, cache, clear
    // =========================================================================

    public function optimize(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $results = [];

        try {
            Artisan::call('optimize');
            $results[] = '✅ Optimized (config, routes, views cached)';
            $results[] = trim(Artisan::output());
        } catch (\Exception $e) {
            $results[] = '❌ Optimize failed: ' . $e->getMessage();
        }

        try {
            Artisan::call('filament:assets');
            $results[] = '✅ Filament assets published';
        } catch (\Exception $e) {
            $results[] = '⚠️ Filament assets: ' . $e->getMessage();
        }

        try {
            Artisan::call('icons:cache');
            $results[] = '✅ Icons cached';
        } catch (\Exception $e) {
            $results[] = '⚠️ Icons: ' . $e->getMessage();
        }

        return response()->json([
            'message' => '✅ Optimization completed',
            'results' => $results,
        ]);
    }

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
            'message' => '✅ Caching completed',
            'results' => $results,
        ]);
    }

    public function clear(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $results = [];

        try {
            Artisan::call('optimize:clear');
            $results[] = '✅ All optimizations cleared';
            $results[] = trim(Artisan::output());

            Artisan::call('cache:clear');
            $results[] = '✅ Application cache cleared';
        } catch (\Exception $e) {
            $results[] = '❌ Error: ' . $e->getMessage();
        }

        return response()->json([
            'message' => '✅ All caches cleared',
            'results' => $results,
        ]);
    }

    // =========================================================================
    //  STORAGE LINK
    // =========================================================================

    public function storageLink(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $publicStoragePath = public_path('storage');
        $storagePath = storage_path('app/public');
        $results = [];

        $results['public_path'] = public_path();
        $results['storage_target'] = $storagePath;
        $results['link_path'] = $publicStoragePath;
        $results['target_exists'] = is_dir($storagePath);

        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
            $results['created_target'] = true;
        }

        if (is_link($publicStoragePath)) {
            $currentTarget = readlink($publicStoragePath);
            $results['status'] = 'symlink_exists';
            $results['current_target'] = $currentTarget;
            $results['target_valid'] = is_dir($publicStoragePath);

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

        // Attempt 3: Manual copy fallback for shared hosting
        return response()->json([
            'message' => '❌ Could not create symlink automatically. Use Hostinger File Manager.',
            'manual_instructions' => [
                'option_1' => "In Hostinger File Manager, create a symbolic link in public/ named 'storage' pointing to ../storage/app/public",
                'option_2' => "ln -s {$storagePath} {$publicStoragePath}",
            ],
            'details' => $results,
        ], 500);
    }

    // =========================================================================
    //  QUEUE MANAGEMENT
    // =========================================================================

    public function queueWork(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $maxJobs = min((int) $request->query('max', 10), 50);

        try {
            Artisan::call('queue:work', [
                '--max-jobs' => $maxJobs,
                '--stop-when-empty' => true,
                '--timeout' => 60,
            ]);
            return response()->json([
                'message' => "✅ Processed up to {$maxJobs} queue jobs",
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function queueRetry(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            Artisan::call('queue:retry', ['id' => ['all']]);
            return response()->json([
                'message' => '✅ All failed jobs retried',
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function queueFlush(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            Artisan::call('queue:flush');
            return response()->json([
                'message' => '✅ All failed jobs deleted',
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function queueStatus(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = [];

        try {
            $data['pending_jobs'] = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 'Table not found';
            $data['failed_jobs'] = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 'Table not found';

            if (Schema::hasTable('failed_jobs') && DB::table('failed_jobs')->count() > 0) {
                $data['recent_failures'] = DB::table('failed_jobs')
                    ->orderByDesc('failed_at')
                    ->limit(5)
                    ->get(['id', 'queue', 'failed_at', 'exception'])
                    ->map(fn($j) => [
                        'id' => $j->id,
                        'queue' => $j->queue,
                        'failed_at' => $j->failed_at,
                        'error' => \Illuminate\Support\Str::limit($j->exception, 200),
                    ]);
            }
        } catch (\Exception $e) {
            $data['error'] = $e->getMessage();
        }

        return response()->json([
            'message' => '📋 Queue Status',
            'data' => $data,
        ]);
    }

    // =========================================================================
    //  MAINTENANCE MODE
    // =========================================================================

    public function down(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $secret = $request->query('secret', 'eseven-bypass-' . date('Ymd'));

        try {
            Artisan::call('down', [
                '--secret' => $secret,
                '--retry' => 60,
            ]);
            return response()->json([
                'message' => '🔒 Maintenance mode ENABLED',
                'bypass_url' => url("/{$secret}"),
                'hint' => "Visit the bypass URL to access the site while in maintenance mode",
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function up(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            Artisan::call('up');
            return response()->json([
                'message' => '✅ Maintenance mode DISABLED — site is live!',
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    //  DIAGNOSTICS — Status, Env, Logs, Permissions
    // =========================================================================

    public function status(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $checks = [];

        $checks['php_version'] = PHP_VERSION;
        $checks['laravel_version'] = app()->version();
        $checks['environment'] = app()->environment();
        $checks['debug_mode'] = config('app.debug') ? '⚠️ ON (should be OFF in production)' : '✅ OFF';
        $checks['app_url'] = config('app.url');
        $checks['maintenance_mode'] = app()->isDownForMaintenance() ? '🔒 ON' : '✅ OFF';

        // Database
        try {
            DB::connection()->getPdo();
            $checks['database'] = '✅ Connected (' . DB::connection()->getDatabaseName() . ')';
            $checks['tables_count'] = count(DB::select('SHOW TABLES'));
        } catch (\Exception $e) {
            $checks['database'] = '❌ ' . $e->getMessage();
        }

        // Storage link
        $checks['storage_link'] = file_exists(public_path('storage')) ? '✅ Exists' : '❌ Missing';
        $checks['spa_index'] = file_exists(public_path('index.html')) ? '✅ Exists' : '❌ Missing (Vue SPA not deployed)';
        $checks['filament_assets'] = is_dir(public_path('css/filament')) || is_dir(public_path('js/filament')) ? '✅ Published' : '⚠️ May need publishing';

        // Directory permissions
        $checks['storage_writable'] = is_writable(storage_path()) ? '✅ Writable' : '❌ Not writable';
        $checks['cache_writable'] = is_writable(base_path('bootstrap/cache')) ? '✅ Writable' : '❌ Not writable';

        // Disk space
        $free = @disk_free_space(base_path());
        $total = @disk_total_space(base_path());
        if ($free !== false && $total !== false) {
            $checks['disk_free'] = round($free / 1024 / 1024, 1) . ' MB';
            $checks['disk_total'] = round($total / 1024 / 1024 / 1024, 2) . ' GB';
        }

        // PHP extensions
        $requiredExtensions = ['pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'fileinfo', 'gd', 'curl', 'json', 'zip'];
        $extensions = [];
        foreach ($requiredExtensions as $ext) {
            $extensions[$ext] = extension_loaded($ext) ? '✅' : '❌';
        }
        $checks['php_extensions'] = $extensions;

        // PHP limits
        $checks['php_limits'] = [
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time') . 's',
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ];

        return response()->json($checks);
    }

    public function envCheck(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Show env values with sensitive data masked
        $keys = [
            'APP_NAME', 'APP_ENV', 'APP_DEBUG', 'APP_URL', 'FRONTEND_URL',
            'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE',
            'SESSION_DRIVER', 'SESSION_LIFETIME', 'SESSION_DOMAIN', 'SESSION_SECURE_COOKIE',
            'CACHE_STORE', 'QUEUE_CONNECTION', 'FILESYSTEM_DISK',
            'MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_FROM_ADDRESS',
            'LOG_CHANNEL', 'LOG_LEVEL',
            'SANCTUM_STATEFUL_DOMAINS',
            'SETUP_TOKEN',
        ];

        $env = [];
        foreach ($keys as $key) {
            $val = env($key);
            if ($val === null) {
                $env[$key] = '(not set)';
            } elseif (is_bool($val)) {
                $env[$key] = $val ? 'true' : 'false';
            } else {
                $env[$key] = (string) $val;
            }
        }

        // Masked sensitive keys
        $sensitiveKeys = ['APP_KEY', 'DB_PASSWORD', 'MAIL_PASSWORD', 'STRIPE_KEY', 'STRIPE_SECRET', 'PAYPAL_SECRET'];
        foreach ($sensitiveKeys as $key) {
            $val = env($key);
            if ($val) {
                $env[$key] = substr($val, 0, 4) . str_repeat('*', max(0, strlen($val) - 4));
            } else {
                $env[$key] = '(not set)';
            }
        }

        return response()->json([
            'message' => '🔍 Environment Variables (sensitive values masked)',
            'env' => $env,
            'config_cached' => file_exists(base_path('bootstrap/cache/config.php')) ? 'Yes — env() may return null! Use config() instead.' : 'No',
        ]);
    }

    public function logs(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $lines = (int) $request->query('lines', 80);
        $lines = min($lines, 500);

        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile)) {
            return response()->json(['message' => '📋 No log file found', 'path' => $logFile]);
        }

        $fileSize = filesize($logFile);
        $fileSizeHuman = round($fileSize / 1024, 1) . ' KB';

        // Read last N lines efficiently
        $content = '';
        try {
            $fp = fopen($logFile, 'r');
            if ($fp) {
                // Read last 64KB or whole file
                $readBytes = min($fileSize, 64 * 1024);
                fseek($fp, -$readBytes, SEEK_END);
                $content = fread($fp, $readBytes);
                fclose($fp);

                // Get last N lines
                $allLines = explode("\n", $content);
                $lastLines = array_slice($allLines, -$lines);
                $content = implode("\n", $lastLines);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not read log: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'message' => "📋 Last {$lines} lines of laravel.log",
            'file_size' => $fileSizeHuman,
            'log' => $content,
        ]);
    }

    public function clearLogs(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $logFile = storage_path('logs/laravel.log');

        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
            return response()->json(['message' => '✅ Log file cleared']);
        }

        return response()->json(['message' => '⏭️ No log file to clear']);
    }

    public function fixPermissions(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $results = [];
        $dirs = [
            storage_path(),
            storage_path('app'),
            storage_path('app/public'),
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
                $results[] = "📁 Created: " . str_replace(base_path(), '', $dir);
            }
            if (@chmod($dir, 0775)) {
                $results[] = "✅ 0775: " . str_replace(base_path(), '', $dir);
            } else {
                $results[] = "⚠️ Could not chmod: " . str_replace(base_path(), '', $dir);
            }
        }

        return response()->json([
            'message' => '✅ Permissions fixed',
            'results' => $results,
        ]);
    }

    // =========================================================================
    //  FILAMENT
    // =========================================================================

    public function filamentUpgrade(Request $request)
    {
        if (!$this->authorize($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $results = [];

        try {
            Artisan::call('filament:upgrade');
            $results[] = '✅ Filament upgraded: ' . trim(Artisan::output());
        } catch (\Exception $e) {
            $results[] = '⚠️ filament:upgrade: ' . $e->getMessage();
        }

        try {
            Artisan::call('filament:assets');
            $results[] = '✅ Filament assets published';
        } catch (\Exception $e) {
            $results[] = '⚠️ filament:assets: ' . $e->getMessage();
        }

        try {
            Artisan::call('icons:cache');
            $results[] = '✅ Icons cached';
        } catch (\Exception $e) {
            $results[] = '⚠️ icons:cache: ' . $e->getMessage();
        }

        return response()->json([
            'message' => '✅ Filament upgrade completed',
            'results' => $results,
        ]);
    }

    // =========================================================================
    //  HELPERS
    // =========================================================================

    private function getAvailableSeeders(): array
    {
        $seeders = [];
        $seederPath = database_path('seeders');

        if (is_dir($seederPath)) {
            $files = glob($seederPath . '/*Seeder.php');
            foreach ($files as $file) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                if ($name !== 'DatabaseSeeder') {
                    $seeders[] = $name;
                }
            }
        }

        return $seeders;
    }

    // =========================================================================
    //  DASHBOARD HTML RENDERER
    // =========================================================================

    private function renderDashboardHtml(
        string $token, bool $dbOk, string $dbName, bool $storageLinked,
        bool $spaDeployed, bool $isMaintenanceMode, string $env,
        string $phpVersion, string $laravelVersion,
        int $pendingJobs, int $failedJobs, array $seeders
    ): string {
        $seederOptions = '';
        foreach ($seeders as $s) {
            $seederOptions .= "<option value=\"{$s}\">{$s}</option>";
        }

        $statusDot = fn(bool $ok) => $ok
            ? '<span style="color:#22c55e">● OK</span>'
            : '<span style="color:#ef4444">● Issue</span>';

        $maintenanceBadge = $isMaintenanceMode
            ? '<span class="badge badge-warning">🔒 Maintenance Mode</span>'
            : '<span class="badge badge-ok">✅ Live</span>';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Eseven Store — Server Management</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    background: #0a0a0f;
    color: #e2e8f0;
    min-height: 100vh;
    padding: 0;
  }
  .header {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4c1d95 100%);
    padding: 2rem 2rem 1.5rem;
    border-bottom: 1px solid rgba(139,92,246,0.3);
  }
  .header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 0.5rem;
  }
  .header .subtitle {
    color: #a5b4fc;
    font-size: 0.9rem;
  }
  .status-bar {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-top: 1rem;
    font-size: 0.85rem;
  }
  .status-item {
    background: rgba(255,255,255,0.08);
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    backdrop-filter: blur(10px);
  }
  .container { max-width: 1200px; margin: 0 auto; padding: 1.5rem; }

  .section {
    margin-bottom: 1.5rem;
    background: #111118;
    border: 1px solid #1e1e2e;
    border-radius: 12px;
    overflow: hidden;
  }
  .section-header {
    background: #16161f;
    padding: 0.75rem 1.25rem;
    font-size: 0.95rem;
    font-weight: 600;
    color: #a5b4fc;
    border-bottom: 1px solid #1e1e2e;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  .section-body {
    padding: 1rem 1.25rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
  }

  .btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.55rem 1rem;
    border: 1px solid #2d2d3d;
    border-radius: 8px;
    background: #1a1a28;
    color: #e2e8f0;
    font-size: 0.82rem;
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
    white-space: nowrap;
  }
  .btn:hover {
    background: #252538;
    border-color: #8b5cf6;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(139,92,246,0.15);
  }
  .btn:active { transform: translateY(0); }
  .btn.loading {
    opacity: 0.6;
    pointer-events: none;
  }
  .btn.loading::after {
    content: '';
    width: 14px;
    height: 14px;
    border: 2px solid #8b5cf6;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
    margin-left: 0.3rem;
  }

  .btn-primary { background: #4c1d95; border-color: #6d28d9; color: #fff; }
  .btn-primary:hover { background: #5b21b6; border-color: #8b5cf6; }

  .btn-success { background: #064e3b; border-color: #059669; color: #6ee7b7; }
  .btn-success:hover { background: #065f46; border-color: #34d399; }

  .btn-danger { background: #450a0a; border-color: #991b1b; color: #fca5a5; }
  .btn-danger:hover { background: #7f1d1d; border-color: #ef4444; }

  .btn-warning { background: #451a03; border-color: #b45309; color: #fcd34d; }
  .btn-warning:hover { background: #78350f; border-color: #f59e0b; }

  .badge { padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.78rem; font-weight: 600; }
  .badge-ok { background: rgba(34,197,94,0.15); color: #4ade80; }
  .badge-warning { background: rgba(245,158,11,0.15); color: #fbbf24; }
  .badge-danger { background: rgba(239,68,68,0.15); color: #f87171; }

  .seeder-row {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    width: 100%;
  }
  .seeder-row select {
    flex: 1;
    background: #1a1a28;
    color: #e2e8f0;
    border: 1px solid #2d2d3d;
    border-radius: 8px;
    padding: 0.55rem 0.8rem;
    font-size: 0.82rem;
    font-family: inherit;
    max-width: 300px;
  }
  .seeder-row select:focus { outline: none; border-color: #8b5cf6; }

  #output-panel {
    margin-top: 1.5rem;
    background: #0d0d14;
    border: 1px solid #1e1e2e;
    border-radius: 12px;
    overflow: hidden;
    display: none;
  }
  #output-panel.visible { display: block; }
  #output-header {
    background: #16161f;
    padding: 0.6rem 1rem;
    font-size: 0.85rem;
    color: #94a3b8;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #1e1e2e;
  }
  #output-content {
    padding: 1rem;
    font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
    font-size: 0.8rem;
    line-height: 1.6;
    max-height: 500px;
    overflow-y: auto;
    white-space: pre-wrap;
    word-break: break-word;
  }
  #output-content .ok { color: #4ade80; }
  #output-content .err { color: #f87171; }
  #output-content .warn { color: #fbbf24; }
  #output-content .info { color: #60a5fa; }

  .first-time-banner {
    background: linear-gradient(135deg, #1e3a5f 0%, #1e1b4b 100%);
    border: 1px solid #3b82f6;
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
  }
  .first-time-banner h3 { color: #93c5fd; margin-bottom: 0.5rem; font-size: 1rem; }
  .first-time-banner p { color: #94a3b8; font-size: 0.85rem; line-height: 1.6; }
  .first-time-banner ol { color: #94a3b8; font-size: 0.85rem; line-height: 2; padding-left: 1.2rem; margin-top: 0.5rem; }
  .first-time-banner code {
    background: rgba(0,0,0,0.3);
    padding: 0.15rem 0.4rem;
    border-radius: 4px;
    color: #a5b4fc;
    font-size: 0.8rem;
  }

  @keyframes spin { to { transform: rotate(360deg); } }

  @media (max-width: 768px) {
    .header { padding: 1.5rem 1rem 1rem; }
    .header h1 { font-size: 1.3rem; }
    .container { padding: 1rem; }
    .status-bar { gap: 0.5rem; }
    .status-item { font-size: 0.75rem; padding: 0.3rem 0.5rem; }
    .btn { font-size: 0.78rem; padding: 0.5rem 0.75rem; }
    .section-body { gap: 0.4rem; }
  }
</style>
</head>
<body>

<div class="header">
  <h1>⚡ Eseven Store — Server Manager</h1>
  <div class="subtitle">Complete server management without SSH • Protected by token</div>
  <div class="status-bar">
    <span class="status-item">🌐 {$env}</span>
    <span class="status-item">🐘 PHP {$phpVersion}</span>
    <span class="status-item">🔷 Laravel {$laravelVersion}</span>
    <span class="status-item">{$statusDot($dbOk)} DB: {$dbName}</span>
    <span class="status-item">{$statusDot($storageLinked)} Storage</span>
    <span class="status-item">{$statusDot($spaDeployed)} SPA</span>
    <span class="status-item">{$maintenanceBadge}</span>
    <span class="status-item">📮 Jobs: {$pendingJobs} pending / {$failedJobs} failed</span>
  </div>
</div>

<div class="container">

  <!-- First-time installation guide -->
  <div class="first-time-banner">
    <h3>🚀 First-Time Installation Guide</h3>
    <p>Just uploaded the .zip to your server? Follow these steps in order:</p>
    <ol>
      <li>Click <strong>Fix Permissions</strong> → ensures all directories are writable</li>
      <li>Click <strong>🚀 Full Setup</strong> → migrates database, creates storage link, caches everything</li>
      <li>Click <strong>📦 Production Seed</strong> → seeds essential data (admin user, currencies, etc.)</li>
      <li>Click <strong>Optimize</strong> → caches config, routes, views for max performance</li>
      <li>Visit your site at <code>{$_SERVER['HTTP_HOST']}</code> — you're live! 🎉</li>
    </ol>
  </div>

  <!-- DEPLOY -->
  <div class="section">
    <div class="section-header">🚀 Deploy & Setup</div>
    <div class="section-body">
      <button class="btn btn-primary" onclick="run('run')">🚀 Full Setup (Migrate + Link + Cache)</button>
      <button class="btn btn-success" onclick="run('storage-link')">🔗 Storage Link</button>
      <button class="btn" onclick="run('fix-permissions')">🔒 Fix Permissions</button>
      <button class="btn" onclick="run('filament-upgrade')">🔷 Filament Upgrade</button>
    </div>
  </div>

  <!-- DATABASE -->
  <div class="section">
    <div class="section-header">🗄️ Database</div>
    <div class="section-body">
      <button class="btn btn-primary" onclick="run('migrate')">▶ Migrate</button>
      <button class="btn" onclick="run('migrate-status')">📋 Status</button>
      <button class="btn btn-warning" onclick="run('migrate-rollback')">↩ Rollback (1)</button>
      <button class="btn btn-danger" onclick="if(confirm('⚠️ This will DROP ALL TABLES and re-migrate. All data will be lost!')) run('migrate-fresh&confirm=yes-destroy-all-data')">💀 Fresh (Destroys Data)</button>
    </div>
  </div>

  <!-- SEEDERS -->
  <div class="section">
    <div class="section-header">🌱 Seeders</div>
    <div class="section-body">
      <button class="btn btn-primary" onclick="run('production-seed')">📦 Production Seed (All Essential)</button>
      <button class="btn btn-warning" onclick="if(confirm('Run ALL seeders including dummy data?')) run('seed')">🌱 Full Seed (All)</button>
      <div class="seeder-row">
        <select id="seeder-select">
          <option value="">— Select individual seeder —</option>
          {$seederOptions}
        </select>
        <button class="btn" onclick="runSeeder()">▶ Run Selected</button>
      </div>
    </div>
  </div>

  <!-- CACHE -->
  <div class="section">
    <div class="section-header">⚡ Cache & Optimization</div>
    <div class="section-body">
      <button class="btn btn-primary" onclick="run('optimize')">⚡ Optimize (Cache All)</button>
      <button class="btn btn-success" onclick="run('cache')">📦 Cache (Config + Routes + Views)</button>
      <button class="btn btn-warning" onclick="run('clear')">🧹 Clear All Caches</button>
    </div>
  </div>

  <!-- QUEUE -->
  <div class="section">
    <div class="section-header">📮 Queue Management</div>
    <div class="section-body">
      <button class="btn btn-primary" onclick="run('queue-work&max=10')">▶ Process 10 Jobs</button>
      <button class="btn" onclick="run('queue-work&max=50')">▶ Process 50 Jobs</button>
      <button class="btn" onclick="run('queue-status')">📋 Queue Status</button>
      <button class="btn btn-warning" onclick="run('queue-retry')">🔄 Retry Failed</button>
      <button class="btn btn-danger" onclick="if(confirm('Delete ALL failed jobs?')) run('queue-flush')">🗑️ Flush Failed</button>
    </div>
  </div>

  <!-- MAINTENANCE -->
  <div class="section">
    <div class="section-header">🔧 Maintenance Mode</div>
    <div class="section-body">
      <button class="btn btn-warning" onclick="run('down')">🔒 Enable Maintenance</button>
      <button class="btn btn-success" onclick="run('up')">✅ Disable Maintenance (Go Live)</button>
    </div>
  </div>

  <!-- DIAGNOSTICS -->
  <div class="section">
    <div class="section-header">🔍 Diagnostics</div>
    <div class="section-body">
      <button class="btn btn-primary" onclick="run('status')">📊 Server Status</button>
      <button class="btn" onclick="run('env-check')">🔑 Env Variables</button>
      <button class="btn" onclick="run('logs')">📄 View Logs (Last 80 lines)</button>
      <button class="btn" onclick="run('logs&lines=300')">📄 View Logs (300 lines)</button>
      <button class="btn btn-warning" onclick="run('clear-logs')">🧹 Clear Logs</button>
    </div>
  </div>

  <!-- OUTPUT -->
  <div id="output-panel">
    <div id="output-header">
      <span id="output-title">Output</span>
      <button class="btn" onclick="document.getElementById('output-panel').classList.remove('visible')" style="padding:0.3rem 0.6rem;font-size:0.75rem;">✕ Close</button>
    </div>
    <div id="output-content"></div>
  </div>

</div>

<script>
const TOKEN = '{$token}';
const BASE = window.location.origin + '/setup/';

async function run(action) {
  const panel = document.getElementById('output-panel');
  const content = document.getElementById('output-content');
  const title = document.getElementById('output-title');

  panel.classList.add('visible');
  title.textContent = '⏳ Running: /setup/' + action.split('&')[0] + '...';
  content.innerHTML = '<span class="info">Loading...</span>';

  // Find the clicked button and add loading state
  const btns = document.querySelectorAll('.btn');
  let clickedBtn = null;
  btns.forEach(b => {
    if (b.classList.contains('loading')) b.classList.remove('loading');
  });
  if (event && event.target) {
    clickedBtn = event.target.closest('.btn');
    if (clickedBtn) clickedBtn.classList.add('loading');
  }

  try {
    const sep = action.includes('?') ? '&' : '?';
    const url = BASE + action + (action.includes('token=') ? '' : sep + 'token=' + TOKEN);
    const res = await fetch(url);
    const data = await res.json();

    title.textContent = (res.ok ? '✅' : '❌') + ' /setup/' + action.split('&')[0] + ' — ' + (res.ok ? 'Success' : 'Error');
    content.innerHTML = formatResponse(data, res.ok);
  } catch (err) {
    title.textContent = '❌ Error';
    content.innerHTML = '<span class="err">Network error: ' + err.message + '</span>';
  } finally {
    if (clickedBtn) clickedBtn.classList.remove('loading');
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
}

function runSeeder() {
  const sel = document.getElementById('seeder-select');
  if (!sel.value) { alert('Select a seeder first'); return; }
  run('seed-class?class=' + sel.value);
}

function formatResponse(data, ok) {
  let html = '';

  if (data.message) {
    const cls = ok ? 'ok' : 'err';
    html += '<div class="' + cls + '" style="font-size:0.9rem;margin-bottom:0.75rem;font-weight:600;">' + esc(data.message) + '</div>';
  }

  if (data.results && Array.isArray(data.results)) {
    data.results.forEach(r => {
      const cls = r.startsWith('✅') ? 'ok' : r.startsWith('❌') ? 'err' : r.startsWith('⚠️') ? 'warn' : 'info';
      html += '<div class="' + cls + '">' + esc(r) + '</div>';
    });
    html += '<br>';
  }

  if (data.output) {
    html += '<div class="info" style="margin-top:0.5rem;">' + esc(data.output) + '</div>';
  }

  if (data.log) {
    html += '<div style="margin-top:0.5rem;color:#94a3b8;font-size:0.75rem;max-height:400px;overflow-y:auto;">' + esc(data.log) + '</div>';
  }

  if (data.error) {
    html += '<div class="err" style="margin-top:0.5rem;">Error: ' + esc(data.error) + '</div>';
  }

  // Render any other keys as key-value pairs
  const skipKeys = ['message', 'results', 'output', 'log', 'error'];
  const remaining = Object.keys(data).filter(k => !skipKeys.includes(k));
  if (remaining.length > 0) {
    html += '<div style="margin-top:0.75rem;border-top:1px solid #1e1e2e;padding-top:0.75rem;">';
    remaining.forEach(key => {
      const val = typeof data[key] === 'object' ? JSON.stringify(data[key], null, 2) : data[key];
      html += '<div style="margin-bottom:0.3rem;"><span class="info">' + esc(key) + ':</span> ' + esc(String(val)) + '</div>';
    });
    html += '</div>';
  }

  return html || '<span class="info">No output</span>';
}

function esc(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}
</script>

</body>
</html>
HTML;
    }
}
