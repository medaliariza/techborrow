<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/admin/returns.php');
}

$db = getDbConnection();
$transactionId = (int) ($_POST['transaction_id'] ?? 0);
$condition = $_POST['condition'] ?? '';
$remarks = trim($_POST['remarks'] ?? '');
$returnDateTimeInput = $_POST['return_datetime'] ?? '';
$admin = currentUser();
$allowedConditions = ['good', 'damaged', 'incomplete', 'maintenance'];
$returnDateTime = is_string($returnDateTimeInput)
    ? DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $returnDateTimeInput)
    : false;
$dateTimeErrors = DateTimeImmutable::getLastErrors();

if ($transactionId <= 0 || !in_array($condition, $allowedConditions, true) || strlen($remarks) > 5000 || !$returnDateTime || $returnDateTime->format('Y-m-d\\TH:i') !== $returnDateTimeInput || ($dateTimeErrors !== false && ($dateTimeErrors['warning_count'] > 0 || $dateTimeErrors['error_count'] > 0)) || $returnDateTime > new DateTimeImmutable()) {
    setFlash('danger', 'Enter a valid actual return date and time that is not in the future, and select a valid condition. Notes must be under 5000 characters.');
    redirect(BASE_URL . '/admin/returns.php');
}

try {
    $db->beginTransaction();

    $transaction = $db->prepare('SELECT * FROM borrow_transactions WHERE transaction_id = :id LIMIT 1 FOR UPDATE');
    $transaction->execute(['id' => $transactionId]);
    $transactionRow = $transaction->fetch();
    if (!$transactionRow || $transactionRow['status'] !== 'borrowed') {
        throw new InvalidArgumentException('This transaction is no longer awaiting return.');
    }

    $returnStmt = $db->prepare('INSERT INTO return_records (transaction_id, user_id, inspected_by, return_datetime, `condition`, remarks) VALUES (:transaction_id, :user_id, :inspected_by, :return_datetime, :condition, :remarks)');
    $returnStmt->execute([
        'transaction_id' => $transactionId,
        'user_id' => $transactionRow['user_id'],
        'inspected_by' => $admin['user_id'],
        'return_datetime' => $returnDateTime->format('Y-m-d H:i:s'),
        'condition' => $condition,
        'remarks' => $remarks !== '' ? $remarks : 'Returned and inspected through the technical return process.',
    ]);
    $returnId = (int) $db->lastInsertId();

    $items = $db->prepare('SELECT * FROM borrow_transaction_items WHERE transaction_id = :id');
    $items->execute(['id' => $transactionId]);
    $transactionItems = $items->fetchAll();
    if (!$transactionItems) {
        throw new InvalidArgumentException('This transaction has no equipment items to return.');
    }

    $returnItemStmt = $db->prepare('INSERT INTO return_items (return_id, equipment_id, quantity_returned, returned_at, `condition`, remarks) VALUES (:return_id, :equipment_id, :quantity_returned, :returned_at, :condition, :remarks)');
    $returnedCount = 0;
    foreach ($transactionItems as $item) {
        $quantityReturned = max(0, (int) $item['quantity_released'] - (int) $item['quantity_returned']);
        if ($quantityReturned === 0) {
            continue;
        }
        $returnedCount += $quantityReturned;

        $returnItemStmt->execute([
            'return_id' => $returnId,
            'equipment_id' => $item['equipment_id'],
            'quantity_returned' => $quantityReturned,
            'returned_at' => $returnDateTime->format('Y-m-d H:i:s'),
            'condition' => $condition,
            'remarks' => $remarks !== '' ? $remarks : null,
        ]);

        $equipment = $db->prepare('SELECT * FROM equipment WHERE equipment_id = :id LIMIT 1 FOR UPDATE');
        $equipment->execute(['id' => $item['equipment_id']]);
        $equipmentRow = $equipment->fetch();
        if (!$equipmentRow) {
            throw new RuntimeException('An equipment item in this transaction could not be found.');
        }

        if ($condition === 'good') {
            $updatedAvailable = min((int) $equipmentRow['total_quantity'], (int) $equipmentRow['available_quantity'] + $quantityReturned);
            $db->prepare('UPDATE equipment SET available_quantity = :available, status = :status WHERE equipment_id = :id')->execute([
                'available' => $updatedAvailable,
                'status' => in_array($equipmentRow['status'], ['maintenance', 'unavailable', 'archived'], true) ? $equipmentRow['status'] : 'available',
                'id' => $item['equipment_id'],
            ]);
        } else {
            $db->prepare('UPDATE equipment SET status = :status WHERE equipment_id = :id')->execute([
                'status' => 'maintenance',
                'id' => $item['equipment_id'],
            ]);
        }

        $db->prepare('UPDATE borrow_transaction_items SET quantity_returned = quantity_released WHERE transaction_item_id = :id')->execute([
            'id' => $item['transaction_item_id'],
        ]);
    }

    if ($returnedCount === 0) {
        throw new InvalidArgumentException('No outstanding equipment remains to be returned.');
    }

    $db->prepare('UPDATE borrow_transactions SET status = :status WHERE transaction_id = :id')->execute([
        'status' => 'returned',
        'id' => $transactionId,
    ]);

    if ($transactionRow['request_id'] !== null) {
        $db->prepare('UPDATE borrowing_requests SET status = :status, updated_at = NOW() WHERE request_id = :id')->execute([
            'status' => 'returned',
            'id' => $transactionRow['request_id'],
        ]);
    }

    $notificationStmt = $db->prepare('INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES (:user_id, :title, :message, 0, NOW())');
    $notificationStmt->execute([
        'user_id' => $transactionRow['user_id'],
        'title' => 'Equipment returned',
        'message' => $condition === 'good'
            ? 'The equipment was returned and is ready for use.'
            : 'The returned equipment was flagged for maintenance inspection.',
    ]);

    $audit = $db->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, created_at) VALUES (:user_id, :action, :entity_type, :entity_id, :details, NOW())');
    $audit->execute([
        'user_id' => $admin['user_id'],
        'action' => 'process_equipment_return',
        'entity_type' => 'return_records',
        'entity_id' => $returnId,
        'details' => 'Transaction ' . $transactionId . '; condition: ' . $condition . '; quantity: ' . $returnedCount,
    ]);

    $db->commit();
    setFlash('success', 'Return recorded successfully.');
    redirect(BASE_URL . '/admin/returns.php');
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    setFlash('danger', $e instanceof InvalidArgumentException ? $e->getMessage() : 'An error occurred while processing the return. No inventory changes were saved.');
    redirect(BASE_URL . '/admin/returns.php');
}
