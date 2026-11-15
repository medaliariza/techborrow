<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['instructor']);

$db = getDbConnection();
$user = currentUser();
$stmt = $db->prepare('SELECT bt.*, e.equipment_code, e.name AS equipment_name, bti.quantity_released, CASE WHEN bt.status = "returned" THEN bti.quantity_released ELSE bti.quantity_returned END AS quantity_returned, return_details.return_datetime, return_details.return_condition, return_details.return_remarks FROM borrow_transactions bt JOIN borrow_transaction_items bti ON bti.transaction_id = bt.transaction_id JOIN equipment e ON e.equipment_id = bti.equipment_id LEFT JOIN (SELECT rr.transaction_id, COALESCE(ri.equipment_id, bti_return.equipment_id) AS equipment_id, MAX(COALESCE(ri.returned_at, rr.return_datetime)) AS return_datetime, GROUP_CONCAT(DISTINCT COALESCE(ri.`condition`, rr.`condition`) SEPARATOR ", ") AS return_condition, GROUP_CONCAT(DISTINCT COALESCE(NULLIF(ri.remarks, ""), rr.remarks) SEPARATOR "; ") AS return_remarks FROM return_records rr LEFT JOIN return_items ri ON ri.return_id = rr.return_id LEFT JOIN borrow_transaction_items bti_return ON bti_return.transaction_id = rr.transaction_id AND (ri.return_item_id IS NULL OR ri.equipment_id = bti_return.equipment_id) GROUP BY rr.transaction_id, COALESCE(ri.equipment_id, bti_return.equipment_id)) return_details ON return_details.transaction_id = bt.transaction_id AND return_details.equipment_id = bti.equipment_id WHERE bt.user_id = :user_id ORDER BY bt.release_datetime DESC, bt.transaction_id DESC, e.name');
$stmt->execute(['user_id' => $user['user_id']]);
$borrowedItems = $stmt->fetchAll();
$pageTitle = 'My Borrowed Items';
$activePage = 'instructor-my-borrowed-items';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="mb-2">Borrowing Activity</h1>
<p class="text-muted mb-4">Your complete record of past borrowed equipment.</p>
<div class="card p-3">
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Released</th>
                    <th>Expected Return</th>
                    <th>Returned</th>
                    <th>Condition / Notes</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($borrowedItems): ?>
                    <?php foreach ($borrowedItems as $item): ?>
                        <?php
                        $statusClass = $item['status'] === 'returned' ? 'bg-success' : ($item['status'] === 'overdue' ? 'bg-danger' : 'bg-primary');
                        $statusLabel = $item['status'] === 'borrowed' ? 'Borrowed' : ucfirst($item['status']);
                        ?>
                        <tr>
                            <td><?php echo e($item['equipment_code'] . ' - ' . $item['equipment_name']); ?></td>
                            <td><?php echo e($item['quantity_returned'] . ' / ' . $item['quantity_released']); ?> returned</td>
                            <td><?php echo e(formatDateTime($item['release_datetime'])); ?></td>
                            <td><?php echo e(formatDateTime($item['expected_return_datetime'])); ?></td>
                            <td><?php echo e(formatDateTime($item['return_datetime'])); ?></td>
                            <td>
                                <?php if ($item['return_condition']): ?>
                                    <div><?php echo e(ucfirst($item['return_condition'])); ?></div>
                                <?php endif; ?>
                                <?php if ($item['return_remarks']): ?>
                                    <div class="small text-muted"><?php echo e($item['return_remarks']); ?></div>
                                <?php endif; ?>
                                <?php if (!$item['return_condition'] && !$item['return_remarks']): ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">No borrowing activity found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
