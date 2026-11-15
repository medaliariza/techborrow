<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

$requestId = filter_input(INPUT_GET, 'request_id', FILTER_VALIDATE_INT);
if (!$requestId || $requestId < 1) {
    http_response_code(404);
    exit;
}

$db = getDbConnection();
$stmt = $db->prepare('SELECT student_face_path FROM borrowing_requests WHERE request_id = :id LIMIT 1');
$stmt->execute(['id' => $requestId]);
$relativePath = $stmt->fetchColumn();
if (!$relativePath || !preg_match('~^uploads/student-faces/[a-f0-9]{32}\\.(jpg|png|webp)$~', $relativePath)) {
    http_response_code(404);
    exit;
}

$uploadRoot = realpath(__DIR__ . '/../uploads/student-faces');
$imagePath = realpath(__DIR__ . '/../' . $relativePath);
if ($uploadRoot === false || $imagePath === false || strpos($imagePath, $uploadRoot . DIRECTORY_SEPARATOR) !== 0 || !is_file($imagePath)) {
    http_response_code(404);
    exit;
}

$fileInfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $fileInfo->file($imagePath);
if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($imagePath));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
readfile($imagePath);
