<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['instructor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/instructor/request-form.php');
}

$db = getDbConnection();
$start = $_POST['start_datetime'] ?? null;
$due = $_POST['due_datetime'] ?? null;
$purpose = trim($_POST['purpose'] ?? '');
$locationRoom = trim($_POST['location_room'] ?? $_POST['activity_name'] ?? '');
$equipmentIds = $_POST['equipment_id'] ?? [];
$quantities = $_POST['quantity'] ?? [];
$user = currentUser();

if (!$start || !$due || $purpose === '' || $locationRoom === '' || strlen($locationRoom) > 255 || empty($equipmentIds)) {
    setFlash('danger', 'Please fill in all request fields and select at least one item.');
    redirect(BASE_URL . '/instructor/request-form.php');
}

if (strtotime($start) >= strtotime($due)) {
    setFlash('danger', 'The start date must be before the due date.');
    redirect(BASE_URL . '/instructor/request-form.php');
}

$itemsToInsert = [];
foreach ($equipmentIds as $index => $equipmentId) {
    $qty = (int) ($quantities[$index] ?? 0);
    if ($qty > 0) {
        $itemsToInsert[] = ['equipment_id' => (int) $equipmentId, 'quantity' => $qty];
    }
}

if (!$itemsToInsert) {
    setFlash('danger', 'Please enter valid quantities for the selected equipment.');
    redirect(BASE_URL . '/instructor/request-form.php');
}

foreach ($itemsToInsert as $item) {
    $info = getEffectiveEquipmentAvailability($db, $item['equipment_id'], $start, $due);
    if ($item['quantity'] > $info['available']) {
        setFlash('danger', 'Requested quantity exceeds available inventory for one or more equipment items during the selected dates.');
        redirect(BASE_URL . '/instructor/request-form.php');
    }
}

try {
    $db->beginTransaction();

    $stmt = $db->prepare('INSERT INTO borrowing_requests (user_id, purpose, activity_name, start_datetime, due_datetime, status, created_at) VALUES (:user_id, :purpose, :activity_name, :start_datetime, :due_datetime, :status, NOW())');
    $stmt->execute([
        'user_id' => $user['user_id'],
        'purpose' => $purpose,
        'activity_name' => $locationRoom,
        'start_datetime' => $start,
        'due_datetime' => $due,
        'status' => 'pending',
    ]);
    $requestId = $db->lastInsertId();

    $itemStmt = $db->prepare('INSERT INTO request_items (request_id, equipment_id, quantity_requested, quantity_approved) VALUES (:request_id, :equipment_id, :quantity_requested, :quantity_approved)');
    foreach ($itemsToInsert as $item) {
        $itemStmt->execute([
            'request_id' => $requestId,
            'equipment_id' => $item['equipment_id'],
            'quantity_requested' => $item['quantity'],
            'quantity_approved' => 0,
        ]);
    }

    $db->commit();

    $notification = $db->prepare('INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES (:user_id, :title, :message, 0, NOW())');
    $notification->execute([
        'user_id' => $user['user_id'],
        'title' => 'Borrowing request submitted',
        'message' => 'Your request was submitted and is awaiting admin review.',
    ]);

    setFlash('success', 'Borrowing request submitted successfully.');
    redirect(BASE_URL . '/instructor/my-requests.php');
} catch (Exception $e) {
    $db->rollBack();
    setFlash('danger', 'There was a problem submitting your request.');
    redirect(BASE_URL . '/instructor/request-form.php');
}
