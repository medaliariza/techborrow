<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$user = currentUser();
if (!$user) {
    redirect(BASE_URL . '/index.php');
}

if ($user['role'] === 'admin') {
    redirect(BASE_URL . '/admin/dashboard.php');
}

if ($user['role'] === 'instructor') {
    redirect(BASE_URL . '/instructor/dashboard.php');
}

if ($user['role'] === 'student') {
    redirect(BASE_URL . '/student-borrow.php');
}

unset($_SESSION['user_id']);
redirect(BASE_URL . '/index.php');
