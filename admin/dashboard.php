<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

$db = getDbConnection();
$stats = getDashboardStats($db);
$announcements = $db->query('SELECT a.*, u.full_name AS created_by_name FROM announcements a JOIN users u ON u.user_id = a.created_by ORDER BY a.created_at DESC LIMIT 5')->fetchAll();
$recentRequests = $db->query('SELECT r.request_id, u.full_name, r.purpose, r.status, r.created_at FROM borrowing_requests r JOIN users u ON u.user_id = r.user_id ORDER BY r.created_at DESC LIMIT 5')->fetchAll();
$overdueItems = $db->query('SELECT bt.transaction_id, bt.expected_return_datetime, u.full_name, e.name FROM borrow_transactions bt JOIN users u ON u.user_id = bt.user_id JOIN borrow_transaction_items bti ON bti.transaction_id = bt.transaction_id JOIN equipment e ON e.equipment_id = bti.equipment_id WHERE bt.status = "borrowed" AND bt.expected_return_datetime < NOW() GROUP BY bt.transaction_id LIMIT 5')->fetchAll();
$pageTitle = 'Admin Dashboard';
$activePage = 'admin-dashboard';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="mb-4">Technical Personnel Dashboard</h1>

<div class="row g-4 stats-grid">
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted">Total Equipment</div>
            <div class="metric-value"><?php echo e($stats['total_equipment']); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted">Available Items</div>
            <div class="metric-value"><?php echo e($stats['available_equipment']); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted">Pending Requests</div>
            <div class="metric-value"><?php echo e($stats['pending_requests']); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted">Overdue</div>
            <div class="metric-value"><?php echo e($stats['overdue']); ?></div>
        </div>
    </div>
</div>

<div class="row mt-4 g-4">
    <div class="col-lg-7">
        <div class="card p-3 mb-4">
            <h4>Latest Announcements</h4>
            <?php if ($announcements): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($announcements as $announcement): ?>
                        <li class="list-group-item">
                            <strong><?php echo e($announcement['title']); ?></strong>
                            <div><?php echo e($announcement['message']); ?></div>
                            <small class="text-muted">By <?php echo e($announcement['created_by_name']); ?> • <?php echo e(formatDateTime($announcement['created_at'])); ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state mt-3">No announcements.</div>
            <?php endif; ?>
        </div>

        <div class="card p-3">
            <h4>Recent Borrowing Requests</h4>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Requester</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentRequests): ?>
                            <?php foreach ($recentRequests as $request): ?>
                                <tr>
                                    <td><?php echo e($request['full_name']); ?></td>
                                    <td><?php echo e($request['purpose']); ?></td>
                                    <td><span class="badge <?php echo e(getStatusBadgeClass($request['status'])); ?>"><?php echo e($request['status']); ?></span></td>
                                    <td><?php echo e(formatDateTime($request['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4">No requests found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card p-3">
            <h4>Overdue Borrowings</h4>
            <?php if ($overdueItems): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($overdueItems as $item): ?>
                        <li class="list-group-item">
                            <strong><?php echo e($item['full_name']); ?></strong><br>
                            <?php echo e($item['name']); ?><br>
                            <small>Due: <?php echo e(formatDateTime($item['expected_return_datetime'])); ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state mt-3">No overdue items.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
