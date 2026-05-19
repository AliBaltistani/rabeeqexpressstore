<?php
/**
 * Emergency Cache Clear — Bypasses Laravel entirely.
 * 
 * Usage: https://your-domain.com/clear-cache.php?token=eseven-deploy-2026
 * DELETE THIS FILE after use!
 */

if (($_GET['token'] ?? '') !== 'eseven-deploy-2026') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$basePath = dirname(__DIR__); // project root
$results = [];

// 1. Delete config cache
$configCache = $basePath . '/bootstrap/cache/config.php';
if (file_exists($configCache)) {
    unlink($configCache);
    $results[] = '✅ Config cache deleted';
} else {
    $results[] = '⏭️ No config cache found';
}

// 2. Delete route cache
foreach (glob($basePath . '/bootstrap/cache/routes-*.php') as $file) {
    unlink($file);
    $results[] = '✅ Route cache deleted: ' . basename($file);
}
$routeCache = $basePath . '/bootstrap/cache/routes.php';
if (file_exists($routeCache)) {
    unlink($routeCache);
    $results[] = '✅ Route cache deleted: routes.php';
}

// 3. Delete compiled views
$viewPath = $basePath . '/storage/framework/views';
if (is_dir($viewPath)) {
    $files = glob($viewPath . '/*.php');
    $count = count($files);
    foreach ($files as $file) {
        unlink($file);
    }
    $results[] = "✅ Cleared {$count} compiled views";
}

// 4. Delete event cache
$eventCache = $basePath . '/bootstrap/cache/events.php';
if (file_exists($eventCache)) {
    unlink($eventCache);
    $results[] = '✅ Event cache deleted';
}

// 5. Delete services cache
$servicesCache = $basePath . '/bootstrap/cache/services.php';
if (file_exists($servicesCache)) {
    unlink($servicesCache);
    $results[] = '✅ Services cache deleted';
}

// 6. Delete packages cache
$packagesCache = $basePath . '/bootstrap/cache/packages.php';
if (file_exists($packagesCache)) {
    unlink($packagesCache);
    $results[] = '✅ Packages cache deleted';
}

header('Content-Type: application/json');
echo json_encode([
    'message' => '🎉 All caches cleared! Your new .env is now active.',
    'results' => $results,
    'next_steps' => [
        '1. Visit /setup/run?token=eseven-deploy-2026 to run full setup',
        '2. DELETE this clear-cache.php file from public/ after use!',
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
