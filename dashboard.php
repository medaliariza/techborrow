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

redirect(BASE_URL . '/instructor/dashboard.php');
