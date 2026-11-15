<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/dashboard.php');
}

$db = getDbConnection();
$id = (int) ($_POST['notification_id'] ?? 0);
$user = currentUser();

if ($id > 0) {
    $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE notification_id = :id AND user_id = :user_id');
    $stmt->execute(['id' => $id, 'user_id' => $user['user_id']]);
}

redirect(BASE_URL . '/dashboard.php');
