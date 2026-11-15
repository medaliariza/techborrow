<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['instructor']);

$db = getDbConnection();
$user = currentUser();
$announcements = $db->query('SELECT a.*, u.full_name AS created_by_name FROM announcements a JOIN users u ON u.user_id = a.created_by ORDER BY a.created_at DESC LIMIT 5')->fetchAll();
$stats = [
    'pending' => $db->query('SELECT COUNT(*) FROM borrowing_requests WHERE user_id = ' . (int)$user['user_id'] . ' AND status = "pending"')->fetchColumn(),
    'approved' => $db->query('SELECT COUNT(*) FROM borrowing_requests WHERE user_id = ' . (int)$user['user_id'] . ' AND status = "approved"')->fetchColumn(),
    'borrowed' => $db->query('SELECT COUNT(*) FROM borrow_transactions WHERE user_id = ' . (int)$user['user_id'] . ' AND status = "borrowed"')->fetchColumn(),
    'returned' => $db->query('SELECT COUNT(*) FROM borrow_transactions WHERE user_id = ' . (int)$user['user_id'] . ' AND status = "returned"')->fetchColumn(),
];
$recentNotifications = $db->prepare('SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5');
$recentNotifications->execute(['user_id' => $user['user_id']]);
$recentNotifications = $recentNotifications->fetchAll();
$recentRequests = $db->prepare('SELECT * FROM borrowing_requests WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5');
$recentRequests->execute(['user_id' => $user['user_id']]);
$recentRequests = $recentRequests->fetchAll();
$pageTitle = 'Instructor Dashboard';
$activePage = 'instructor-dashboard';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="mb-4">Instructor Dashboard</h1>

<div class="row g-4 stats-grid">
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted">Pending Requests</div>
            <div class="metric-value"><?php echo e($stats['pending']); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted">Approved</div>
            <div class="metric-value"><?php echo e($stats['approved']); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted">Borrowed</div>
            <div class="metric-value"><?php echo e($stats['borrowed']); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted">Returned</div>
            <div class="metric-value"><?php echo e($stats['returned']); ?></div>
        </div>
    </div>
</div>

<div class="row mt-4 g-4">
    <div class="col-lg-7">
        <div class="card p-3 mb-4">
            <h4>Announcements</h4>
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
                <div class="empty-state mt-3">No announcements yet.</div>
            <?php endif; ?>
        </div>

        <div class="card p-3">
            <h4>My Recent Requests</h4>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentRequests): ?>
                            <?php foreach ($recentRequests as $request): ?>
                                <tr>
                                    <td><?php echo e($request['purpose']); ?></td>
                                    <td><span class="badge <?php echo e(getStatusBadgeClass($request['status'])); ?>"><?php echo e($request['status']); ?></span></td>
                                    <td><?php echo e(formatDateTime($request['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3">No requests submitted yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card p-3">
            <h4>Notifications</h4>
            <?php if ($recentNotifications): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($recentNotifications as $notification): ?>
                        <li class="list-group-item">
                            <strong><?php echo e($notification['title']); ?></strong><br>
                            <small><?php echo e($notification['message']); ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state mt-3">No notifications.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
