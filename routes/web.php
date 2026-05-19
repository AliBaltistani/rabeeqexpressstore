<?php

use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Deployment Setup Routes (protected by secret token)
|--------------------------------------------------------------------------
|
| Usage: /setup/run?token=YOUR_SETUP_TOKEN
| Set SETUP_TOKEN in your .env file.
|
*/

/*
|--------------------------------------------------------------------------
| Emergency Config Clear (no auth — needed when cached config breaks env())
|--------------------------------------------------------------------------
*/
Route::get('setup/emergency-clear', function (\Illuminate\Http\Request $request) {
    // Hardcoded token check — works even when config is cached
    if ($request->query('token') !== 'eseven-deploy-2026') {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $results = [];

    // Delete cached config file directly (bypasses Artisan bootstrap)
    $cachedConfig = base_path('bootstrap/cache/config.php');
    if (file_exists($cachedConfig)) {
        unlink($cachedConfig);
        $results[] = '✅ Config cache file deleted';
    } else {
        $results[] = '⏭️ No config cache file found';
    }

    // Also clear route and view caches if they exist
    foreach (['routes-v7.php', 'routes.php'] as $routeCache) {
        $path = base_path('bootstrap/cache/' . $routeCache);
        if (file_exists($path)) {
            unlink($path);
            $results[] = '✅ Route cache deleted: ' . $routeCache;
        }
    }

    // Clear application cache files
    $cachePath = storage_path('framework/cache/data');
    if (is_dir($cachePath)) {
        $results[] = '✅ Application cache directory exists (files will be stale-expired)';
    }

    // Clear compiled views
    $viewCachePath = storage_path('framework/views');
    if (is_dir($viewCachePath)) {
        $files = glob($viewCachePath . '/*.php');
        $count = count($files);
        foreach ($files as $file) {
            unlink($file);
        }
        $results[] = "✅ Cleared {$count} compiled views";
    }

    return response()->json([
        'message' => 'Emergency cache clear completed! Refresh your site now.',
        'results' => $results,
        'next_step' => 'Visit /setup/run?token=eseven-deploy-2026 to run full setup',
    ]);
});

Route::prefix('setup')->group(function () {
    Route::get('run', [SetupController::class, 'run']);
    Route::get('migrate', [SetupController::class, 'migrate']);
    Route::get('seed', [SetupController::class, 'seed']);
    Route::get('production-seed', [SetupController::class, 'productionSeed']);
    Route::get('cache', [SetupController::class, 'cache']);
    Route::get('clear', [SetupController::class, 'clear']);
    Route::get('storage-link', [SetupController::class, 'storageLink']);
    Route::get('status', [SetupController::class, 'status']);
});

/*
|--------------------------------------------------------------------------
| Vue SPA Catch-All Route
|--------------------------------------------------------------------------
|
| All requests that don't match API, admin, livewire, or static file paths
| are forwarded to the Vue SPA's index.html for client-side routing.
|
*/

Route::get('/{any}', function () {
    $indexPath = public_path('index.html');

    if (File::exists($indexPath)) {
        return response(File::get($indexPath), 200)
            ->header('Content-Type', 'text/html');
    }

    // Fallback if Vue SPA is not yet deployed
    return response()->json([
        'message' => 'Eseven Store API is running. Vue SPA not deployed yet.',
        'admin' => url('/admin'),
        'api' => url('/api/v1/init'),
        'setup' => url('/setup/status?token=YOUR_TOKEN'),
    ]);
})->where('any', '^(?!api|admin|livewire|sanctum|storage|setup|css|js|fonts|build|favicon|robots).*$');
