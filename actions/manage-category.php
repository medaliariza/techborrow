<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/admin/categories.php');
}

$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($name === '' || strlen($name) > 100 || strlen($description) > 1000) {
    setFlash('danger', 'Enter a category name (up to 100 characters) and a description no longer than 1000 characters.');
    redirect(BASE_URL . '/admin/categories.php');
}

$db = getDbConnection();
$duplicate = $db->prepare('SELECT category_id FROM categories WHERE name = :name LIMIT 1');
$duplicate->execute(['name' => $name]);
if ($duplicate->fetch()) {
    setFlash('danger', 'A category with that name already exists.');
    redirect(BASE_URL . '/admin/categories.php');
}

try {
    $stmt = $db->prepare('INSERT INTO categories (name, description) VALUES (:name, :description)');
    $stmt->execute([
        'name' => $name,
        'description' => $description !== '' ? $description : null,
    ]);
    setFlash('success', 'Category added. You can now add equipment in this category.');
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        setFlash('danger', 'A category with that name already exists.');
    } else {
        throw $e;
    }
}

redirect(BASE_URL . '/admin/categories.php');
