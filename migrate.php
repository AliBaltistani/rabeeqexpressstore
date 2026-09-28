<?php
/**
 * ┌──────────────────────────────────────────────────────────────┐
 * │         Rabeq Express Store — Database Migration Runner       │
 * │                                                              │
 * │  Use this when you need to run pending migrations on live    │
 * │  server WITHOUT re-running the full setup.php               │
 * │                                                              │
 * │  URL: https://yourdomain.com/migrate.php?token=YOUR_SECRET   │
 * │                                                              │
 * │  ⚠️  DELETE THIS FILE AFTER USE!                            │
 * └──────────────────────────────────────────────────────────────┘
 */

// ─── CHANGE THIS to match your setup.php token ───────────────────────────────
define('SECRET_TOKEN', 'CHANGE_ME_TO_SOMETHING_RANDOM_12345');
// ─────────────────────────────────────────────────────────────────────────────

if (!isset($_GET['token']) || $_GET['token'] !== SECRET_TOKEN) {
    http_response_code(403);
    die('<h2 style="color:red">403 Unauthorized</h2><p>Pass ?token=YOUR_SECRET</p>');
}

set_time_limit(180);

echo '<!DOCTYPE html><html><head>
    <title>Rabeq — Migration Runner</title>
    <style>
        body  { font-family: monospace; background: #1a1a2e; color: #eee; padding: 30px; }
        h1    { color: #e94560; }
        h3    { color: #fff; background: #16213e; padding: 10px; border-left: 4px solid #e94560; }
        pre   { background: #0f3460; padding: 15px; border-radius: 5px; white-space: pre-wrap; word-break: break-word; }
        .ok   { color: #00ff88; font-weight: bold; }
        .err  { color: #ff4444; }
        .warn { color: #ffaa00; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th,td { border: 1px solid #0f3460; padding: 6px 12px; text-align: left; }
        th    { background: #0f3460; }
        .ran  { color: #00ff88; }
        .pending { color: #ffaa00; }
    </style>
</head><body>';

echo '<h1>🗃️ Rabeq Express Store — Migration Runner</h1>';

// ── Bootstrap Laravel ─────────────────────────────────────────────────────────
try {
    require __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo '<p class="ok">✅ Laravel bootstrapped successfully.</p>';
} catch (\Throwable $e) {
    echo '<p class="err">❌ Bootstrap failed: ' . htmlspecialchars($e->getMessage()) . '</p></body></html>';
    exit;
}

// ── Step 1: Show current migration status ─────────────────────────────────────
echo '<h3>Step 1: Current Migration Status</h3>';
try {
    \Artisan::call('migrate:status');
    $statusOutput = \Artisan::output();

    // Parse and display as a table
    $lines = array_filter(explode("\n", $statusOutput));
    echo '<table><tr><th>Status</th><th>Migration</th><th>Batch</th></tr>';
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || str_starts_with($line, '+') || str_starts_with($line, '|  Migration')) {
            continue;
        }
        // Highlight Filament import/export tables
        $isFilament = str_contains($line, 'import') || str_contains($line, 'export') || str_contains($line, 'failed_import');
        $isRan = str_contains(strtolower($line), 'ran');
        $rowClass = $isRan ? 'ran' : 'pending';
        $highlight = $isFilament ? ' style="background:#1a2a1a"' : '';
        $icon = $isRan ? '✅' : '⏳';
        $label = $isFilament ? ' <strong style="color:#ffaa00">[Filament]</strong>' : '';

        echo "<tr{$highlight}><td class=\"{$rowClass}\">{$icon}</td><td>{$line}{$label}</td><td></td></tr>";
    }
    echo '</table>';
    echo '<pre>' . htmlspecialchars($statusOutput) . '</pre>';
} catch (\Throwable $e) {
    echo '<pre class="err">Could not fetch status: ' . htmlspecialchars($e->getMessage()) . '</pre>';
}

// ── Step 2: Run pending migrations ───────────────────────────────────────────
echo '<h3>Step 2: Running Pending Migrations</h3>';
try {
    \Artisan::call('migrate', ['--force' => true]);
    $output = \Artisan::output();
    $noNew = str_contains($output, 'Nothing to migrate') || trim($output) === '';

    if ($noNew) {
        echo '<pre class="ok">✅ All migrations are already up to date. Nothing to run.</pre>';
    } else {
        echo '<pre class="ok">' . htmlspecialchars($output) . '</pre>';
    }
} catch (\Throwable $e) {
    echo '<pre class="err">❌ Migration failed: ' . htmlspecialchars($e->getMessage()) . '</pre>';
}

// ── Step 3: Verify Filament tables exist ─────────────────────────────────────
echo '<h3>Step 3: Filament Import / Export Table Check</h3>';
$filamentTables = ['imports', 'exports', 'failed_import_rows'];
echo '<table><tr><th>Table</th><th>Status</th><th>Row Count</th></tr>';
foreach ($filamentTables as $table) {
    try {
        $exists = \Illuminate\Support\Facades\Schema::hasTable($table);
        $count = $exists ? \Illuminate\Support\Facades\DB::table($table)->count() : 'N/A';
        $status = $exists
            ? '<span class="ok">✅ Exists</span>'
            : '<span class="err">❌ Missing — run migrations above</span>';
        echo "<tr><td><code>{$table}</code></td><td>{$status}</td><td>{$count}</td></tr>";
    } catch (\Throwable $e) {
        echo "<tr><td><code>{$table}</code></td><td class=\"err\">❌ Error: " . htmlspecialchars($e->getMessage()) . "</td><td>—</td></tr>";
    }
}
echo '</table>';

// ── Step 4: Clear caches ─────────────────────────────────────────────────────
echo '<h3>Step 4: Clearing Application Cache</h3>';
try {
    \Artisan::call('optimize:clear');
    echo '<pre class="ok">✅ ' . htmlspecialchars(\Artisan::output()) . '</pre>';
} catch (\Throwable $e) {
    echo '<pre class="warn">⚠️ Cache clear warning: ' . htmlspecialchars($e->getMessage()) . '</pre>';
}

// ── Done ──────────────────────────────────────────────────────────────────────
echo '<br><p class="ok" style="font-size:1.4em">✅ Migration check complete!</p>';
echo '<p class="warn" style="background:#330000;padding:15px;border-radius:5px">
    ⚠️ <strong>DELETE this migrate.php file from your server via File Manager immediately!</strong>
</p>';

echo '</body></html>';
