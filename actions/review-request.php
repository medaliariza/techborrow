<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/admin/requests.php');
}

$db = getDbConnection();
$requestId = (int) ($_POST['request_id'] ?? 0);
$decision = $_POST['decision'] ?? '';
$reason = trim($_POST['reason'] ?? '');
$admin = currentUser();

if ($requestId <= 0 || !in_array($decision, ['approve', 'reject'], true)) {
    setFlash('danger', 'Select a valid request and decision.');
    redirect(BASE_URL . '/admin/requests.php');
}

$status = $decision === 'approve' ? 'approved' : 'unavailable';
try {
    $db->beginTransaction();

    $request = $db->prepare('SELECT * FROM borrowing_requests WHERE request_id = :id LIMIT 1 FOR UPDATE');
    $request->execute(['id' => $requestId]);
    $requestRow = $request->fetch();
    if (!$requestRow) {
        throw new InvalidArgumentException('Request not found.');
    }
    if ($requestRow['status'] !== 'pending') {
        throw new InvalidArgumentException('This request has already been reviewed and cannot be decided again.');
    }

    $existingReview = $db->prepare('SELECT review_id FROM request_reviews WHERE request_id = :id LIMIT 1');
    $existingReview->execute(['id' => $requestId]);
    if ($existingReview->fetch()) {
        throw new InvalidArgumentException('This request already has a recorded decision.');
    }

    $itemStmt = $db->prepare('SELECT * FROM request_items WHERE request_id = :id');
    $itemStmt->execute(['id' => $requestId]);
    $items = $itemStmt->fetchAll();

    if (!$items) {
        throw new InvalidArgumentException('This request has no equipment items to review.');
    }

    if ($decision === 'approve') {
        foreach ($items as $item) {
            $info = getEffectiveEquipmentAvailability($db, $item['equipment_id'], $requestRow['start_datetime'], $requestRow['due_datetime'], $requestId);
            if ($item['quantity_requested'] > $info['available']) {
                throw new InvalidArgumentException('Approval blocked: one or more items exceed available inventory for the request period.');
            }
        }
    }

    $stmt = $db->prepare('UPDATE borrowing_requests SET status = :status, updated_at = NOW() WHERE request_id = :id AND status = "pending"');
    $stmt->execute(['status' => $status, 'id' => $requestId]);
    if ($stmt->rowCount() !== 1) {
        throw new InvalidArgumentException('This request has already been reviewed.');
    }

    if ($decision === 'approve') {
        foreach ($items as $item) {
            $result = $db->prepare('UPDATE request_items SET quantity_approved = :approved WHERE request_item_id = :id');
            $result->execute(['approved' => (int) $item['quantity_requested'], 'id' => $item['request_item_id']]);
        }
    }

    $reviewStmt = $db->prepare('INSERT INTO request_reviews (request_id, reviewed_by, decision, reason, reviewed_at) VALUES (:request_id, :reviewed_by, :decision, :reason, NOW())');
    $reviewStmt->execute([
        'request_id' => $requestId,
        'reviewed_by' => $admin['user_id'],
        'decision' => $decision,
        'reason' => $reason,
    ]);

    $notificationStmt = $db->prepare('INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES (:user_id, :title, :message, 0, NOW())');
    $notificationStmt->execute([
        'user_id' => $requestRow['user_id'],
        'title' => $decision === 'approve' ? 'Request approved' : 'Request unavailable',
        'message' => $decision === 'approve' ? 'Your borrowing request has been approved and can be released by the admin.' : 'Your borrowing request is unavailable. Reason: ' . ($reason !== '' ? $reason : 'No reason provided.'),
    ]);

    $audit = $db->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, created_at) VALUES (:user_id, :action, :entity_type, :entity_id, :details, NOW())');
    $audit->execute([
        'user_id' => $admin['user_id'],
        'action' => $decision === 'approve' ? 'approve_request' : 'reject_request',
        'entity_type' => 'borrowing_requests',
        'entity_id' => $requestId,
        'details' => $decision === 'approve' ? 'Approved borrowing request.' : 'Rejected borrowing request: ' . $reason,
    ]);

    $db->commit();
    setFlash('success', 'Request review saved successfully.');
    redirect(BASE_URL . '/admin/requests.php');
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    setFlash('danger', $e instanceof InvalidArgumentException ? $e->getMessage() : 'Unable to save the request decision.');
    redirect(BASE_URL . '/admin/requests.php');
}
