<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

$db = getDbConnection();
$requests = $db->query('SELECT r.*, u.full_name, u.student_number FROM borrowing_requests r JOIN users u ON u.user_id = r.user_id ORDER BY r.created_at DESC')->fetchAll();
$pageTitle = 'Borrowing Requests';
$activePage = 'admin-requests';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="mb-4">Borrowing Requests</h1>
<div class="card p-3">
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Requester</th>
                    <th>Student Number</th>
                    <th>Purpose</th>
                    <th>Start</th>
                    <th>Due</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($requests): ?>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?php echo e($request['full_name']); ?></td>
                            <td><?php echo e($request['student_number'] ?? '—'); ?></td>
                            <td><?php echo e($request['purpose']); ?></td>
                            <td><?php echo e(formatDateTime($request['start_datetime'])); ?></td>
                            <td><?php echo e(formatDateTime($request['due_datetime'])); ?></td>
                            <td><span class="badge <?php echo e(getStatusBadgeClass($request['status'])); ?>"><?php echo e($request['status']); ?></span></td>
                            <td><a href="request-details.php?id=<?php echo (int)$request['request_id']; ?>" class="btn btn-sm <?php echo $request['status'] === 'pending' ? 'btn-primary' : 'btn-outline-secondary'; ?>"><?php echo $request['status'] === 'pending' ? 'Review' : 'View'; ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">No requests found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
