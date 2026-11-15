<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/admin/users.php');
}

$db = getDbConnection();
$userId = (int) ($_POST['user_id'] ?? 0);
$status = $_POST['account_status'] ?? 'active';
$allowed = ['active', 'inactive', 'suspended'];

if ($userId <= 0 || !in_array($status, $allowed, true)) {
    setFlash('danger', 'Invalid user or account status.');
    redirect(BASE_URL . '/admin/users.php');
}

$current = currentUser();
if ((int) $current['user_id'] === $userId && $status !== 'active') {
    setFlash('danger', 'You cannot change your own account status to inactive or suspended.');
    redirect(BASE_URL . '/admin/users.php');
}

$stmt = $db->prepare('UPDATE users SET account_status = :status WHERE user_id = :id');
$stmt->execute(['status' => $status, 'id' => $userId]);

setFlash('success', 'User account status updated successfully.');
redirect(BASE_URL . '/admin/users.php');
