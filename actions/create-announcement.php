<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/admin/announcements.php');
}

$db = getDbConnection();
$action = $_POST['action'] ?? 'create';
$announcementId = (int) ($_POST['announcement_id'] ?? 0);

if ($action === 'delete') {
    if ($announcementId <= 0) {
        setFlash('danger', 'Select a valid announcement to delete.');
        redirect(BASE_URL . '/admin/announcements.php');
    }

    $delete = $db->prepare('DELETE FROM announcements WHERE announcement_id = :id');
    $delete->execute(['id' => $announcementId]);
    setFlash($delete->rowCount() > 0 ? 'success' : 'warning', $delete->rowCount() > 0 ? 'Announcement deleted.' : 'Announcement was not found.');
    redirect(BASE_URL . '/admin/announcements.php');
}

if (!in_array($action, ['create', 'update'], true) || ($action === 'update' && $announcementId <= 0)) {
    setFlash('danger', 'Invalid announcement action.');
    redirect(BASE_URL . '/admin/announcements.php');
}

$title = trim($_POST['title'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($title === '' || strlen($title) > 200 || $message === '') {
    setFlash('danger', 'Announcement title and message are required.');
    redirect(BASE_URL . '/admin/announcements.php');
}

if ($action === 'update') {
    $exists = $db->prepare('SELECT announcement_id FROM announcements WHERE announcement_id = :id LIMIT 1');
    $exists->execute(['id' => $announcementId]);
    if (!$exists->fetch()) {
        setFlash('danger', 'Announcement not found.');
        redirect(BASE_URL . '/admin/announcements.php');
    }

    $stmt = $db->prepare('UPDATE announcements SET title = :title, message = :message WHERE announcement_id = :id');
    $stmt->execute(['title' => $title, 'message' => $message, 'id' => $announcementId]);
    setFlash('success', 'Announcement updated for all accounts.');
} else {
    $user = currentUser();
    $stmt = $db->prepare('INSERT INTO announcements (title, message, created_by, created_at) VALUES (:title, :message, :created_by, NOW())');
    $stmt->execute([
        'title' => $title,
        'message' => $message,
        'created_by' => $user['user_id'],
    ]);
    setFlash('success', 'Announcement published successfully.');
}

redirect(BASE_URL . '/admin/announcements.php');
