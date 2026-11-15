<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

$db = getDbConnection();
$transactions = $db->query('SELECT bt.transaction_id, bt.release_datetime, bt.expected_return_datetime, u.full_name, item_summary.equipment_summary, item_summary.outstanding_quantity FROM borrow_transactions bt JOIN users u ON u.user_id = bt.user_id JOIN (SELECT bti.transaction_id, SUM(GREATEST(bti.quantity_released - bti.quantity_returned, 0)) AS outstanding_quantity, GROUP_CONCAT(CONCAT(e.equipment_code, " - ", e.name, " (", GREATEST(bti.quantity_released - bti.quantity_returned, 0), " remaining)") ORDER BY e.name SEPARATOR ", ") AS equipment_summary FROM borrow_transaction_items bti JOIN equipment e ON e.equipment_id = bti.equipment_id GROUP BY bti.transaction_id HAVING SUM(GREATEST(bti.quantity_released - bti.quantity_returned, 0)) > 0) item_summary ON item_summary.transaction_id = bt.transaction_id WHERE bt.status = "borrowed" ORDER BY bt.expected_return_datetime ASC')->fetchAll();
$pageTitle = 'Return Processing';
$activePage = 'admin-returns';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="mb-4">Return Processing</h1>
<div class="card p-3">
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Borrower</th>
                    <th>Equipment</th>
                    <th>Released</th>
                    <th>Expected Return</th>
                    <th>Actual Returned Date &amp; Time</th>
                    <th>Outstanding</th>
                    <th>Inspection Condition</th>
                    <th>Inspection Notes</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($transactions): ?>
                    <?php foreach ($transactions as $row): ?>
                        <tr>
                            <td><?php echo e($row['full_name']); ?></td>
                            <td><?php echo e($row['equipment_summary']); ?></td>
                            <td><?php echo e(formatDateTime($row['release_datetime'])); ?></td>
                            <td><?php echo e(formatDateTime($row['expected_return_datetime'])); ?></td>
                            <td>
                                <input class="form-control form-control-sm" type="datetime-local" name="return_datetime" form="return-<?php echo (int) $row['transaction_id']; ?>" value="<?php echo e(date('Y-m-d\\TH:i')); ?>" max="<?php echo e(date('Y-m-d\\TH:i')); ?>" required>
                            </td>
                            <td><?php echo e($row['outstanding_quantity']); ?></td>
                            <td>
                                <select class="form-select form-select-sm" name="condition" form="return-<?php echo (int) $row['transaction_id']; ?>" required>
                                    <option value="good">Good</option>
                                    <option value="damaged">Damaged</option>
                                    <option value="incomplete">Incomplete</option>
                                    <option value="maintenance">Needs maintenance</option>
                                </select>
                            </td>
                            <td>
                                <textarea class="form-control form-control-sm" name="remarks" form="return-<?php echo (int) $row['transaction_id']; ?>" rows="1" maxlength="5000" placeholder="Optional inspection notes"></textarea>
                            </td>
                            <td>
                                <form id="return-<?php echo (int) $row['transaction_id']; ?>" method="POST" action="<?php echo BASE_URL; ?>/actions/process-return.php" onsubmit="return confirm('Record the inspected return for this entire transaction?')">
                                    <input type="hidden" name="transaction_id" value="<?php echo (int)$row['transaction_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">Process Return</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9">No active returns awaiting inspection.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
