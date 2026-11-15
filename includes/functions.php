<?php

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const BASE_URL = '/techborrow';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect($path)
{
    header('Location: ' . $path);
    exit;
}

function setFlash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlash()
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function currentUser()
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $db = getDbConnection();
    $stmt = $db->prepare('SELECT * FROM users WHERE user_id = :id LIMIT 1');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    return $stmt->fetch();
}

function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        redirect(BASE_URL . '/index.php');
    }
}

function requireRole($allowedRoles)
{
    $user = currentUser();
    if (!$user || !in_array($user['role'], (array) $allowedRoles, true)) {
        redirect(BASE_URL . '/dashboard.php');
    }
}

function getRoleLabel($role)
{
    $labels = [
        'admin' => 'Admin',
        'instructor' => 'Instructor',
        'student' => 'Student',
    ];

    return $labels[$role] ?? ucfirst($role);
}

function getStatusBadgeClass($status)
{
    if (in_array($status, ['available', 'approved'], true)) {
        return 'bg-success';
    }

    if ($status === 'unavailable') {
        return 'bg-danger';
    }

    return 'bg-info';
}

function formatDateTime($value)
{
    if (empty($value)) {
        return '—';
    }

    return date('M d, Y h:i A', strtotime($value));
}

function getDashboardStats($db)
{
    $stats = [];

    $stats['total_equipment'] = (int) $db->query('SELECT COUNT(*) FROM equipment')->fetchColumn();
    $stats['available_equipment'] = (int) $db->query('SELECT COUNT(*) FROM equipment WHERE status = "available"')->fetchColumn();
    $stats['pending_requests'] = (int) $db->query('SELECT COUNT(*) FROM borrowing_requests WHERE status = "pending"')->fetchColumn();
    $stats['overdue'] = (int) $db->query('SELECT COUNT(*) FROM borrow_transactions WHERE status = "borrowed" AND expected_return_datetime < NOW()')->fetchColumn();
    $stats['total_users'] = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();

    return $stats;
}

function getEffectiveEquipmentAvailability($db, $equipmentId, $start, $due, $excludeRequestId = 0)
{
    $equipment = $db->prepare('SELECT total_quantity, available_quantity, `condition`, status FROM equipment WHERE equipment_id = :equipment_id LIMIT 1');
    $equipment->execute(['equipment_id' => $equipmentId]);
    $equipmentRow = $equipment->fetch();

    if (!$equipmentRow) {
        return ['available' => 0, 'total' => 0, 'message' => 'Equipment not found.'];
    }

    if (in_array($equipmentRow['status'], ['maintenance', 'unavailable', 'archived'], true) || in_array($equipmentRow['condition'], ['damaged', 'maintenance'], true)) {
        return [
            'available' => 0,
            'total' => (int) $equipmentRow['total_quantity'],
            'message' => 'This equipment is not currently available.',
        ];
    }

    $reservedStmt = $db->prepare('SELECT COALESCE(SUM(ri.quantity_requested), 0) FROM request_items ri JOIN borrowing_requests br ON br.request_id = ri.request_id WHERE ri.equipment_id = :equipment_id AND br.status IN ("approved", "borrowed") AND br.request_id != :exclude_request AND br.start_datetime < :due AND br.due_datetime > :start');
    $reservedStmt->execute([
        'equipment_id' => $equipmentId,
        'exclude_request' => $excludeRequestId,
        'due' => $due,
        'start' => $start,
    ]);
    $reservedQty = (int) $reservedStmt->fetchColumn();

    $realAvailable = max(0, (int) $equipmentRow['total_quantity'] - $reservedQty);

    return [
        'available' => $realAvailable,
        'total' => (int) $equipmentRow['total_quantity'],
        'message' => $realAvailable > 0 ? '' : 'This equipment is unavailable for the selected date range.',
    ];
}
