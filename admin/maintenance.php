<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

$db = getDbConnection();
$equipment = $db->query('SELECT equipment_id, equipment_code, name FROM equipment ORDER BY name')->fetchAll();
$editingId = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
$editingRecord = null;
if ($editingId > 0) {
    $editQuery = $db->prepare('SELECT * FROM maintenance_records WHERE maintenance_id = :id LIMIT 1');
    $editQuery->execute(['id' => $editingId]);
    $editingRecord = $editQuery->fetch();
}
$maintenance = $db->query('SELECT m.*, e.equipment_code, e.name AS equipment_name, u.full_name AS reported_by_name FROM maintenance_records m JOIN equipment e ON e.equipment_id = m.equipment_id JOIN users u ON u.user_id = m.reported_by ORDER BY m.reported_at DESC')->fetchAll();
$pageTitle = 'Maintenance';
$activePage = 'admin-maintenance';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="mb-4">Maintenance Records</h1>

<div class="card p-3 mb-4">
    <h2 class="h5"><?php echo $editingRecord ? 'Update Maintenance Record' : 'Report Equipment Issue'; ?></h2>
    <?php if (!$equipment): ?>
        <p class="text-muted mb-0">Add equipment to the inventory before reporting a maintenance issue.</p>
    <?php else: ?>
        <form method="POST" action="<?php echo BASE_URL; ?>/actions/manage-maintenance.php">
            <input type="hidden" name="action" value="<?php echo $editingRecord ? 'update' : 'create'; ?>">
            <?php if ($editingRecord): ?>
                <input type="hidden" name="maintenance_id" value="<?php echo (int) $editingRecord['maintenance_id']; ?>">
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="equipmentId">Equipment</label>
                    <select class="form-select" id="equipmentId" name="equipment_id" required>
                        <option value="">Select equipment</option>
                        <?php foreach ($equipment as $item): ?>
                            <option value="<?php echo (int) $item['equipment_id']; ?>" <?php echo (int) ($editingRecord['equipment_id'] ?? 0) === (int) $item['equipment_id'] ? 'selected' : ''; ?>>
                                <?php echo e($item['equipment_code'] . ' - ' . $item['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="issueDescription">Issue description</label>
                    <textarea class="form-control" id="issueDescription" name="issue_description" rows="2" maxlength="5000" required><?php echo e($editingRecord['issue_description'] ?? ''); ?></textarea>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <label class="form-label" for="maintenanceStatus">Status</label>
                    <select class="form-select" id="maintenanceStatus" name="status" required>
                        <?php foreach (['reported', 'in_progress', 'completed'] as $status): ?>
                            <option value="<?php echo e($status); ?>" <?php echo ($editingRecord['status'] ?? 'reported') === $status ? 'selected' : ''; ?>><?php echo e(ucwords(str_replace('_', ' ', $status))); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <label class="form-label" for="assignedTo">Assigned to</label>
                    <input class="form-control" id="assignedTo" name="assigned_to" maxlength="150" value="<?php echo e($editingRecord['assigned_to'] ?? ''); ?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <label class="form-label" for="repairCost">Repair cost</label>
                    <input class="form-control" id="repairCost" name="repair_cost" type="number" min="0" max="99999999.99" step="0.01" value="<?php echo e($editingRecord['repair_cost'] ?? '0.00'); ?>" required>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button class="btn btn-primary" type="submit"><?php echo $editingRecord ? 'Save Maintenance Record' : 'Log Maintenance Issue'; ?></button>
                    <?php if ($editingRecord): ?>
                        <a class="btn btn-outline-secondary" href="maintenance.php">Cancel</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<div class="card p-3">
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Equipment Code</th>
                    <th>Issue</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Reported</th>
                    <th>Completed</th>
                    <th>Repair Cost</th>
                    <th>Reported By</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($maintenance): ?>
                    <?php foreach ($maintenance as $record): ?>
                        <tr>
                            <td><?php echo e($record['equipment_code'] . ' - ' . $record['equipment_name']); ?></td>
                            <td><?php echo e($record['issue_description']); ?></td>
                            <td><?php echo e(ucwords(str_replace('_', ' ', $record['status']))); ?></td>
                            <td><?php echo e($record['assigned_to'] ?: 'Unassigned'); ?></td>
                            <td><?php echo e(formatDateTime($record['reported_at'])); ?></td>
                            <td><?php echo e(formatDateTime($record['completed_at'])); ?></td>
                            <td>PHP <?php echo e(number_format((float) $record['repair_cost'], 2)); ?></td>
                            <td><?php echo e($record['reported_by_name']); ?></td>
                            <td><a class="btn btn-sm btn-outline-primary" href="maintenance.php?edit_id=<?php echo (int) $record['maintenance_id']; ?>">Edit</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9">No maintenance records.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
