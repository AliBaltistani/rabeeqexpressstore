<?php
/**
 * ┌─────────────────────────────────────────────────────┐
 * │         Rabeq Express Store — One-Time Setup        │
 * │                                                     │
 * │  Run ONCE via browser after uploading to Hostinger  │
 * │  URL: https://yourdomain.com/setup.php?token=...    │
 * │                                                     │
 * │  ⚠️  DELETE THIS FILE IMMEDIATELY AFTER RUNNING!   │
 * └─────────────────────────────────────────────────────┘
 */

// ─── CHANGE THIS to a secret token only you know ───────────────────────────
define('SECRET_TOKEN', 'CHANGE_ME_TO_SOMETHING_RANDOM_12345');
// ───────────────────────────────────────────────────────────────────────────

// Security check
if (!isset($_GET['token']) || $_GET['token'] !== SECRET_TOKEN) {
    http_response_code(403);
    die('<h2 style="color:red">403 Unauthorized</h2><p>Pass your secret token: <code>?token=YOUR_SECRET</code></p>');
}

set_time_limit(120);

echo '<!DOCTYPE html><html><head>
    <title>Rabeq Store Setup</title>
    <style>
        body { font-family: monospace; background: #1a1a2e; color: #eee; padding: 30px; }
        h1 { color: #e94560; }
        h3 { color: #0f3460; background: #16213e; padding: 10px; border-left: 4px solid #e94560; }
        pre { background: #0f3460; padding: 15px; border-radius: 5px; overflow: auto; }
        .success { color: #00ff88; font-weight: bold; font-size: 1.4em; }
        .warning { color: #ff4444; font-weight: bold; font-size: 1.2em; background: #330000; padding: 15px; border-radius: 5px; }
    </style>
</head><body>';

echo '<h1>🚀 Rabeq Express Store — Server Setup</h1>';

// Bootstrap Laravel
try {
    require __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo '<p>✅ Laravel bootstrapped successfully.</p>';
} catch (\Throwable $e) {
    echo '<p style="color:red">❌ Bootstrap failed: ' . $e->getMessage() . '</p></body></html>';
    exit;
}

// ─── 1. Generate App Key ────────────────────────────────────────────────────
echo '<h3>Step 1: Generating Application Key</h3>';
Artisan::call('key:generate', ['--force' => true]);
echo '<pre>' . htmlspecialchars(Artisan::output()) . '</pre>';

// ─── 2. Run Migrations ──────────────────────────────────────────────────────
echo '<h3>Step 2: Running Database Migrations</h3>';
try {
    Artisan::call('migrate', ['--force' => true]);
    echo '<pre>' . htmlspecialchars(Artisan::output()) . '</pre>';
} catch (\Throwable $e) {
    echo '<pre style="color:red">Migration error: ' . $e->getMessage() . '</pre>';
}

// ─── 3. Seed Database (uncomment if needed) ─────────────────────────────────
// echo '<h3>Step 3: Seeding Database</h3>';
// try {
//     Artisan::call('db:seed', ['--force' => true]);
//     echo '<pre>' . htmlspecialchars(Artisan::output()) . '</pre>';
// } catch (\Throwable $e) {
//     echo '<pre style="color:red">Seeder error: ' . $e->getMessage() . '</pre>';
// }

// ─── 4. Storage Link ────────────────────────────────────────────────────────
echo '<h3>Step 3: Creating Storage Symlink</h3>';
try {
    Artisan::call('storage:link');
    echo '<pre>' . htmlspecialchars(Artisan::output()) . '</pre>';
} catch (\Throwable $e) {
    echo '<pre style="color:orange">Storage link note: ' . $e->getMessage() . '</pre>';
}

// ─── 5. Optimize (cache config, routes, views) ──────────────────────────────
echo '<h3>Step 4: Optimizing Application</h3>';
Artisan::call('config:cache');
Artisan::call('route:cache');
Artisan::call('view:cache');
echo '<pre>✅ Config, Route, and View caches created.</pre>';

// ─── Done ───────────────────────────────────────────────────────────────────
echo '<br><p class="success">✅ Setup Complete! Your Rabeq Express Store is ready.</p>';
echo '<p class="warning">⚠️ DELETE this setup.php file from your server NOW via File Manager!</p>';

echo '</body></html>';
