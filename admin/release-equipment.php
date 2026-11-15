<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

$db = getDbConnection();
$requests = $db->query('SELECT r.*, u.full_name, u.student_number FROM borrowing_requests r JOIN users u ON u.user_id = r.user_id WHERE r.status = "approved" ORDER BY r.created_at DESC')->fetchAll();
$pageTitle = 'Release Equipment';
$activePage = 'admin-release';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="mb-4">Release Equipment</h1>
<div class="card p-3">
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Requester</th>
                    <th>Purpose</th>
                    <th>Approved</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($requests): ?>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td>
                                <?php echo e($request['full_name']); ?>
                                <?php if (!empty($request['student_number'])): ?>
                                    <div class="small text-muted">Student #<?php echo e($request['student_number']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($request['student_face_path'])): ?>
                                    <img src="<?php echo BASE_URL; ?>/admin/student-photo.php?request_id=<?php echo (int) $request['request_id']; ?>" alt="Student portrait for manual identity verification" class="rounded border mt-2" style="width: 56px; height: 56px; object-fit: cover;">
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($request['purpose']); ?></td>
                            <td><?php echo e(formatDateTime($request['updated_at'] ?? $request['created_at'])); ?></td>
                            <td>
                                <form method="POST" action="<?php echo BASE_URL; ?>/actions/release-item.php">
                                    <input type="hidden" name="request_id" value="<?php echo (int)$request['request_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">Release</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4">No approved requests awaiting release.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
