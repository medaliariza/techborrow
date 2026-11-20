<?php
$apkPath = __DIR__ . '/TechBorrow.apk';

if (!is_file($apkPath) || !is_readable($apkPath)) {
    http_response_code(404);
    exit('TechBorrow APK is not available.');
}

header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="TechBorrow.apk"');
header('Content-Length: ' . filesize($apkPath));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
readfile($apkPath);
