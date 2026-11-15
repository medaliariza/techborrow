<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/instructor/profile.php');
}

$db = getDbConnection();
$user = currentUser();
$userId = (int) $user['user_id'];
$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$department = trim($_POST['department'] ?? '');
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($fullName === '' || strlen($fullName) > 150 || strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('danger', 'Enter a valid name and email address.');
    redirect(BASE_URL . '/instructor/profile.php');
}

if ($user['role'] === 'instructor' && ($department === '' || strlen($department) > 150)) {
    setFlash('danger', 'Enter a department with no more than 150 characters.');
    redirect(BASE_URL . '/instructor/profile.php');
}

if ($newPassword !== '' && (strlen($newPassword) < 8 || $newPassword !== $confirmPassword)) {
    setFlash('danger', 'New passwords must match and contain at least 8 characters.');
    redirect(BASE_URL . '/instructor/profile.php');
}

$emailCheck = $db->prepare('SELECT user_id FROM users WHERE email = :email AND user_id != :user_id LIMIT 1');
$emailCheck->execute(['email' => $email, 'user_id' => $userId]);
if ($emailCheck->fetch()) {
    setFlash('danger', 'That email address is already used by another account.');
    redirect(BASE_URL . '/instructor/profile.php');
}

try {
    $fields = ['full_name = :full_name', 'email = :email'];
    $parameters = ['full_name' => $fullName, 'email' => $email, 'user_id' => $userId];

    if ($user['role'] === 'instructor') {
        $fields[] = 'department = :department';
        $parameters['department'] = $department;
    }

    if ($newPassword !== '') {
        $fields[] = 'password_hash = :password_hash';
        $parameters['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    $update = $db->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE user_id = :user_id');
    $update->execute($parameters);

    setFlash('success', 'Your profile was updated successfully.');
} catch (PDOException $e) {
    setFlash('danger', $e->getCode() === '23000' ? 'That email address is already used by another account.' : 'Your profile could not be updated. Please try again.');
}

redirect(BASE_URL . '/instructor/profile.php');