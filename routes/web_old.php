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

Route::prefix('setup')->group(function () {
    Route::get('run', [SetupController::class, 'run']);
    Route::get('migrate', [SetupController::class, 'migrate']);
    Route::get('seed', [SetupController::class, 'seed']);
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
        'message' => 'Rabeq Express Store API is running. Vue SPA not deployed yet.',
        'admin' => url('/admin'),
        'api' => url('/api/v1/init'),
        'setup' => url('/setup/status?token=YOUR_TOKEN'),
    ]);
})->where('any', '^(?!api|admin|livewire|sanctum|storage|setup|css|js|fonts|build|favicon|robots).*$');
