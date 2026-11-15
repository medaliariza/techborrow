<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/admin/maintenance.php');
}

$db = getDbConnection();
$admin = currentUser();
$action = $_POST['action'] ?? '';
$equipmentId = (int) ($_POST['equipment_id'] ?? 0);
$maintenanceId = (int) ($_POST['maintenance_id'] ?? 0);
$issue = trim($_POST['issue_description'] ?? '');
$status = $_POST['status'] ?? 'reported';
$assignedTo = trim($_POST['assigned_to'] ?? '');
$costInput = trim($_POST['repair_cost'] ?? '0');
$allowedStatuses = ['reported', 'in_progress', 'completed'];

try {
    if (!in_array($action, ['create', 'update'], true)) {
        throw new InvalidArgumentException('Choose a valid maintenance action.');
    }
    if ($equipmentId <= 0 || $issue === '' || strlen($issue) > 5000) {
        throw new InvalidArgumentException('Select equipment and enter an issue description of at most 5000 characters.');
    }
    if (!in_array($status, $allowedStatuses, true)) {
        throw new InvalidArgumentException('Choose a valid maintenance status.');
    }
    if ($action === 'update' && $maintenanceId <= 0) {
        throw new InvalidArgumentException('Select a valid maintenance record to update.');
    }
    if (!is_numeric($costInput) || (float) $costInput < 0 || (float) $costInput > 99999999.99) {
        throw new InvalidArgumentException('Repair cost must be between 0 and 99,999,999.99.');
    }

    $equipmentCheck = $db->prepare('SELECT equipment_id FROM equipment WHERE equipment_id = :id LIMIT 1');
    $equipmentCheck->execute(['id' => $equipmentId]);
    if (!$equipmentCheck->fetch()) {
        throw new InvalidArgumentException('The selected equipment could not be found.');
    }

    $db->beginTransaction();
    $completedAt = null;

    if ($action === 'update') {
        $recordCheck = $db->prepare('SELECT completed_at FROM maintenance_records WHERE maintenance_id = :id LIMIT 1 FOR UPDATE');
        $recordCheck->execute(['id' => $maintenanceId]);
        $existingRecord = $recordCheck->fetch();
        if (!$existingRecord) {
            throw new InvalidArgumentException('The maintenance record could not be found.');
        }
        if ($status === 'completed') {
            $completedAt = $existingRecord['completed_at'] ?: date('Y-m-d H:i:s');
        }

        $save = $db->prepare('UPDATE maintenance_records SET equipment_id = :equipment_id, issue_description = :issue_description, status = :status, assigned_to = :assigned_to, repair_cost = :repair_cost, completed_at = :completed_at WHERE maintenance_id = :maintenance_id');
        $save->execute([
            'equipment_id' => $equipmentId,
            'issue_description' => $issue,
            'status' => $status,
            'assigned_to' => $assignedTo !== '' ? $assignedTo : null,
            'repair_cost' => number_format((float) $costInput, 2, '.', ''),
            'completed_at' => $completedAt,
            'maintenance_id' => $maintenanceId,
        ]);
        $savedId = $maintenanceId;
        $auditAction = 'update_maintenance_record';
        $flashMessage = 'Maintenance record updated.';
    } else {
        if ($status === 'completed') {
            $completedAt = date('Y-m-d H:i:s');
        }

        $save = $db->prepare('INSERT INTO maintenance_records (equipment_id, reported_by, issue_description, status, assigned_to, repair_cost, reported_at, completed_at) VALUES (:equipment_id, :reported_by, :issue_description, :status, :assigned_to, :repair_cost, NOW(), :completed_at)');
        $save->execute([
            'equipment_id' => $equipmentId,
            'reported_by' => $admin['user_id'],
            'issue_description' => $issue,
            'status' => $status,
            'assigned_to' => $assignedTo !== '' ? $assignedTo : null,
            'repair_cost' => number_format((float) $costInput, 2, '.', ''),
            'completed_at' => $completedAt,
        ]);
        $savedId = (int) $db->lastInsertId();
        $auditAction = 'create_maintenance_record';
        $flashMessage = 'Maintenance issue logged.';
    }

    $audit = $db->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, created_at) VALUES (:user_id, :action, :entity_type, :entity_id, :details, NOW())');
    $audit->execute([
        'user_id' => $admin['user_id'],
        'action' => $auditAction,
        'entity_type' => 'maintenance_records',
        'entity_id' => $savedId,
        'details' => 'Status: ' . $status . '; Equipment ID: ' . $equipmentId,
    ]);

    $db->commit();
    setFlash('success', $flashMessage);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    setFlash('danger', $e instanceof InvalidArgumentException ? $e->getMessage() : 'The maintenance record could not be saved. Please try again.');
}

redirect(BASE_URL . '/admin/maintenance.php');