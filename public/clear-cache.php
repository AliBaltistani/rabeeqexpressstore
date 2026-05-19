<?php
/**
 * Emergency Cache Clear — Bypasses Laravel entirely.
 *
 * Usage: https://your-domain.com/clear-cache.php?token=eseven-deploy-2026
 * DELETE THIS FILE after your site is fully working!
 */

$token = $_GET['token'] ?? '';
if ($token !== 'eseven-deploy-2026') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$basePath = dirname(__DIR__); // project root (one level up from public/)
$results = [];

// 1. Config cache
$configCache = $basePath . '/bootstrap/cache/config.php';
if (file_exists($configCache)) {
    unlink($configCache);
    $results[] = '✅ Config cache deleted';
} else {
    $results[] = '⏭️ No config cache found';
}

// 2. Route caches
foreach (glob($basePath . '/bootstrap/cache/routes-*.php') as $file) {
    unlink($file);
    $results[] = '✅ Route cache deleted: ' . basename($file);
}
$routeCache = $basePath . '/bootstrap/cache/routes.php';
if (file_exists($routeCache)) {
    unlink($routeCache);
    $results[] = '✅ Route cache deleted: routes.php';
}

// 3. Event cache
$eventCache = $basePath . '/bootstrap/cache/events.php';
if (file_exists($eventCache)) {
    unlink($eventCache);
    $results[] = '✅ Event cache deleted';
}

// 4. Services cache
$servicesCache = $basePath . '/bootstrap/cache/services.php';
if (file_exists($servicesCache)) {
    unlink($servicesCache);
    $results[] = '✅ Services cache deleted';
}

// 5. Packages cache
$packagesCache = $basePath . '/bootstrap/cache/packages.php';
if (file_exists($packagesCache)) {
    unlink($packagesCache);
    $results[] = '✅ Packages cache deleted';
}

// 6. Compiled views
$viewPath = $basePath . '/storage/framework/views';
if (is_dir($viewPath)) {
    $files = glob($viewPath . '/*.php');
    $count = count($files);
    foreach ($files as $file) {
        @unlink($file);
    }
    $results[] = "✅ Cleared {$count} compiled views";
}

// 7. Application file cache
$cachePath = $basePath . '/storage/framework/cache/data';
if (is_dir($cachePath)) {
    clearCacheDir($cachePath);
    $results[] = '✅ Application cache cleared';
}

// 8. Filament cache (if exists)
$filamentCache = $basePath . '/storage/framework/cache/filament';
if (is_dir($filamentCache)) {
    clearCacheDir($filamentCache);
    $results[] = '✅ Filament cache cleared';
}

// Helper: recursively delete cache files
function clearCacheDir(string $dir): void
{
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        if ($item->isDir()) {
            @rmdir($item->getPathname());
        } else {
            @unlink($item->getPathname());
        }
    }
}

header('Content-Type: application/json');
echo json_encode([
    'message' => '🎉 All caches cleared! Your new configuration is now active.',
    'results' => $results,
    'next_steps' => [
        '1. Visit /setup/cache?token=eseven-deploy-2026 to rebuild caches',
        '2. Visit /admin to check if Filament login appears',
        '3. Visit /api/v1/init?lang=en&currency=SAR to verify API',
        '4. DELETE this clear-cache.php file when done!',
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
