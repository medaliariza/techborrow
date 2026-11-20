<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['instructor']);

$db = getDbConnection();
$user = currentUser();
$stmt = $db->prepare('SELECT * FROM borrowing_requests WHERE user_id = :id ORDER BY created_at DESC');
$stmt->execute(['id' => $user['user_id']]);
$requests = $stmt->fetchAll();
$clearRequestDraft = ($_SESSION['clear_instructor_request_draft'] ?? '') === 'instructor-borrow-' . (int) $user['user_id'];
unset($_SESSION['clear_instructor_request_draft']);
$pageTitle = 'My Requests';
$activePage = 'instructor-my-requests';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="mb-4">My Borrowing Requests</h1>
<?php if ($clearRequestDraft): ?>
    <div data-clear-form-draft="instructor-borrow-<?php echo (int) $user['user_id']; ?>" hidden></div>
<?php endif; ?>
<div class="card p-3">
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Purpose</th>
                    <th>Location / Room</th>
                    <th>Start</th>
                    <th>Due</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($requests): ?>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?php echo e($request['purpose']); ?></td>
                            <td><?php echo e($request['activity_name']); ?></td>
                            <td><?php echo e(formatDateTime($request['start_datetime'])); ?></td>
                            <td><?php echo e(formatDateTime($request['due_datetime'])); ?></td>
                            <td><span class="badge <?php echo e(getStatusBadgeClass($request['status'])); ?>"><?php echo e($request['status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No requests found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
