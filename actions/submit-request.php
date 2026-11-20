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

$draftItems = [];
if (is_array($equipmentIds) && is_array($quantities)) {
    foreach ($equipmentIds as $index => $equipmentId) {
        $quantity = $quantities[$index] ?? '';
        $draftItems[] = [
            'equipment_id' => is_scalar($equipmentId) ? (string) $equipmentId : '',
            'quantity' => is_scalar($quantity) ? (string) $quantity : '',
        ];
    }
}
$_SESSION['request_form_draft'] = [
    'purpose' => $purpose,
    'location_room' => $locationRoom,
    'start_datetime' => is_string($start) ? $start : '',
    'due_datetime' => is_string($due) ? $due : '',
    'items' => $draftItems,
];

if (!$start || !$due || $purpose === '' || $locationRoom === '' || strlen($locationRoom) > 255 || empty($equipmentIds)) {
    setFlash('danger', 'Please fill in all request fields and select at least one item.');
    redirect(BASE_URL . '/instructor/request-form.php');
}

if (strtotime($start) >= strtotime($due)) {
    setFlash('danger', 'The start date must be before the due date.');
    redirect(BASE_URL . '/instructor/request-form.php');
}

$itemsByEquipment = [];
foreach ($equipmentIds as $index => $equipmentId) {
    $rawQuantity = $quantities[$index] ?? null;
    $qty = is_scalar($rawQuantity) ? filter_var($rawQuantity, FILTER_VALIDATE_INT) : false;
    $id = is_scalar($equipmentId) ? filter_var($equipmentId, FILTER_VALIDATE_INT) : false;
    if ($id && $qty && $qty > 0) {
        if (!isset($itemsByEquipment[$id])) {
            $itemsByEquipment[$id] = ['equipment_id' => $id, 'quantity' => 0];
        }
        $itemsByEquipment[$id]['quantity'] += $qty;
    }
}
$itemsToInsert = array_values($itemsByEquipment);

if (!$itemsToInsert) {
    setFlash('danger', 'Please enter valid quantities for the selected equipment.');
    redirect(BASE_URL . '/instructor/request-form.php');
}

foreach ($itemsToInsert as $item) {
    $info = getEffectiveEquipmentAvailability($db, $item['equipment_id'], $start, $due);
    if ($item['quantity'] > $info['available']) {
        $equipmentStmt = $db->prepare('SELECT name, equipment_code FROM equipment WHERE equipment_id = :id LIMIT 1');
        $equipmentStmt->execute(['id' => $item['equipment_id']]);
        $equipmentItem = $equipmentStmt->fetch();
        $itemName = $equipmentItem
            ? $equipmentItem['name'] . ' (' . $equipmentItem['equipment_code'] . ')'
            : 'The selected item';
        setFlash('danger', $info['available'] === 0
            ? $itemName . ' is not available for the selected dates.'
            : 'Requested quantity for ' . $itemName . ' exceeds the available quantity of ' . $info['available'] . ' for the selected dates.');
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

    $notification = $db->prepare('INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES (:user_id, :title, :message, 0, NOW())');
    $notification->execute([
        'user_id' => $user['user_id'],
        'title' => 'Borrowing request submitted',
        'message' => 'Your request was submitted and is awaiting admin review.',
    ]);

    $db->commit();
    $_SESSION['clear_instructor_request_draft'] = 'instructor-borrow-' . (int) $user['user_id'];
    unset($_SESSION['request_form_draft']);
    setFlash('success', 'Your request was submitted successfully and is pending admin approval. Submission does not mean the equipment has been approved or released.');
    redirect(BASE_URL . '/instructor/my-requests.php');
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Instructor borrowing request failed: ' . $e->getMessage());
    setFlash('danger', 'There was a problem submitting your request.');
    redirect(BASE_URL . '/instructor/request-form.php');
}
