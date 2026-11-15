<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/admin/release-equipment.php');
}

$db = getDbConnection();
$requestId = (int) ($_POST['request_id'] ?? 0);
$admin = currentUser();

if ($requestId <= 0) {
    setFlash('danger', 'No request selected for release.');
    redirect(BASE_URL . '/admin/release-equipment.php');
}

$request = $db->prepare('SELECT * FROM borrowing_requests WHERE request_id = :id LIMIT 1');
$request->execute(['id' => $requestId]);
$requestRow = $request->fetch();
if (!$requestRow || $requestRow['status'] !== 'approved') {
    setFlash('danger', 'Only approved requests can be released.');
    redirect(BASE_URL . '/admin/release-equipment.php');
}

$items = $db->prepare('SELECT * FROM request_items WHERE request_id = :id');
$items->execute(['id' => $requestId]);
$items = $items->fetchAll();

foreach ($items as $item) {
    $info = getEffectiveEquipmentAvailability($db, $item['equipment_id'], $requestRow['start_datetime'], $requestRow['due_datetime'], $requestId);
    if ($item['quantity_approved'] > $info['available']) {
        setFlash('danger', 'Release blocked because the equipment is no longer available for the approved request period.');
        redirect(BASE_URL . '/admin/release-equipment.php');
    }
}

try {
    $db->beginTransaction();

    $transactionStmt = $db->prepare('INSERT INTO borrow_transactions (request_id, user_id, released_by, release_datetime, expected_return_datetime, status) VALUES (:request_id, :user_id, :released_by, NOW(), :expected_return_datetime, :status)');
    $transactionStmt->execute([
        'request_id' => $requestId,
        'user_id' => $requestRow['user_id'],
        'released_by' => $admin['user_id'],
        'expected_return_datetime' => $requestRow['due_datetime'],
        'status' => 'borrowed',
    ]);
    $transactionId = $db->lastInsertId();

    $transactionItemStmt = $db->prepare('INSERT INTO borrow_transaction_items (transaction_id, equipment_id, quantity_released, quantity_returned) VALUES (:transaction_id, :equipment_id, :quantity_released, 0)');
    foreach ($items as $item) {
        $equipment = $db->prepare('SELECT * FROM equipment WHERE equipment_id = :id LIMIT 1');
        $equipment->execute(['id' => $item['equipment_id']]);
        $equipmentRow = $equipment->fetch();

        if (!$equipmentRow) {
            throw new Exception('Equipment not found.');
        }

        $newAvailable = max(0, (int) $equipmentRow['available_quantity'] - (int) $item['quantity_approved']);
        $db->prepare('UPDATE equipment SET available_quantity = :available, status = :status WHERE equipment_id = :id')
            ->execute([
                'available' => $newAvailable,
                'status' => $newAvailable > 0 ? 'available' : 'borrowed',
                'id' => $item['equipment_id'],
            ]);

        $transactionItemStmt->execute([
            'transaction_id' => $transactionId,
            'equipment_id' => $item['equipment_id'],
            'quantity_released' => (int) $item['quantity_approved'],
        ]);
    }

    $db->prepare('UPDATE borrowing_requests SET status = :status, updated_at = NOW() WHERE request_id = :id')->execute([
        'status' => 'borrowed',
        'id' => $requestId,
    ]);

    $notificationStmt = $db->prepare('INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES (:user_id, :title, :message, 0, NOW())');
    $notificationStmt->execute([
        'user_id' => $requestRow['user_id'],
        'title' => 'Equipment released',
        'message' => 'Your approved borrowing request has been released and is now active.',
    ]);

    $audit = $db->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, created_at) VALUES (:user_id, :action, :entity_type, :entity_id, :details, NOW())');
    $audit->execute([
        'user_id' => $admin['user_id'],
        'action' => 'release_equipment',
        'entity_type' => 'borrow_transactions',
        'entity_id' => $transactionId,
        'details' => 'Equipment released to borrower.',
    ]);

    $db->commit();
    setFlash('success', 'Equipment has been released and recorded as an active borrowing transaction.');
    redirect(BASE_URL . '/admin/release-equipment.php');
} catch (Exception $e) {
    $db->rollBack();
    setFlash('danger', 'Release failed due to an inventory or database problem.');
    redirect(BASE_URL . '/admin/release-equipment.php');
}
