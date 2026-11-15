<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

$id = (int) ($_GET['id'] ?? 0);
$db = getDbConnection();
$request = $db->prepare('SELECT r.*, u.full_name, u.email, u.role, u.student_number, u.department, (SELECT MAX(COALESCE(ri.returned_at, rr.return_datetime)) FROM borrow_transactions bt JOIN return_records rr ON rr.transaction_id = bt.transaction_id LEFT JOIN return_items ri ON ri.return_id = rr.return_id WHERE bt.request_id = r.request_id) AS actual_return_datetime FROM borrowing_requests r JOIN users u ON u.user_id = r.user_id WHERE r.request_id = :id LIMIT 1');
$request->execute(['id' => $id]);
$request = $request->fetch();
if (!$request) {
    redirect(BASE_URL . '/admin/requests.php');
}
$items = $db->prepare('SELECT ri.*, e.name AS equipment_name, e.equipment_code FROM request_items ri JOIN equipment e ON e.equipment_id = ri.equipment_id WHERE ri.request_id = :id');
$items->execute(['id' => $id]);
$items = $items->fetchAll();
$reviewStmt = $db->prepare('SELECT rr.*, u.full_name AS reviewer_name FROM request_reviews rr JOIN users u ON u.user_id = rr.reviewed_by WHERE rr.request_id = :id ORDER BY rr.reviewed_at DESC LIMIT 1');
$reviewStmt->execute(['id' => $id]);
$review = $reviewStmt->fetch();
$pageTitle = 'Request Details';
$activePage = 'admin-requests';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="mb-4">Review Request</h1>

<div class="card p-4 mb-4">
    <div class="row g-3">
        <div class="col-md-6"><strong>Requester:</strong> <?php echo e($request['full_name']); ?></div>
        <div class="col-md-6"><strong>Email:</strong> <?php echo e($request['email']); ?></div>
        <?php if ($request['role'] === 'student'): ?>
            <div class="col-md-6"><strong>Student Number:</strong> <?php echo e($request['student_number']); ?></div>
            <div class="col-md-6"><strong>Department / Program:</strong> <?php echo e($request['department']); ?></div>
            <?php if (!empty($request['student_face_path'])): ?>
                <div class="col-12">
                    <strong>Student Portrait (admin review only):</strong><br>
                    <img src="<?php echo BASE_URL; ?>/admin/student-photo.php?request_id=<?php echo (int) $request['request_id']; ?>" alt="Student portrait for identity verification" class="rounded border mt-2" style="max-width: 240px; max-height: 240px; object-fit: cover;">
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <div class="col-md-6"><strong>Purpose:</strong> <?php echo e($request['purpose']); ?></div>
        <div class="col-md-6"><strong>Location / Room:</strong> <?php echo e($request['activity_name']); ?></div>
        <div class="col-md-6"><strong>Start:</strong> <?php echo e(formatDateTime($request['start_datetime'])); ?></div>
        <div class="col-md-6"><strong>Due:</strong> <?php echo e(formatDateTime($request['due_datetime'])); ?></div>
        <div class="col-md-6"><strong>Actual Returned Date &amp; Time:</strong> <?php echo $request['actual_return_datetime'] ? e(formatDateTime($request['actual_return_datetime'])) : 'Not returned yet'; ?></div>
    </div>
</div>

<div class="card p-4 mb-4">
    <h4>Requested Items</h4>
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Equipment</th>
                <th>Code</th>
                <th>Qty Requested</th>
                <th>Qty Approved</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo e($item['equipment_name']); ?></td>
                    <td><?php echo e($item['equipment_code']); ?></td>
                    <td><?php echo e($item['quantity_requested']); ?></td>
                    <td><?php echo e($item['quantity_approved'] ?? 0); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($request['status'] === 'pending' && !$review): ?>
<div class="card p-4">
    <form method="POST" action="<?php echo BASE_URL; ?>/actions/review-request.php">
        <input type="hidden" name="request_id" value="<?php echo (int)$request['request_id']; ?>">
        <div class="mb-3">
            <label class="form-label">Decision</label>
            <select class="form-select" name="decision" required>
                <option value="approve">Approve</option>
                <option value="reject">Mark unavailable</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Admin Notes / Unavailable Reason</label>
            <textarea class="form-control" name="reason" rows="4" placeholder="Add notes or reason for unavailability"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Submit Decision</button>
    </form>
</div>
<?php else: ?>
<div class="card p-4">
    <h4>Decision Recorded</h4>
    <p class="mb-1">Request status: <span class="badge <?php echo e(getStatusBadgeClass($request['status'])); ?>"><?php echo e($request['status']); ?></span></p>
    <?php if ($review): ?>
        <p class="mb-1">Decision: <strong><?php echo e(ucfirst($review['decision'])); ?></strong> by <?php echo e($review['reviewer_name']); ?> on <?php echo e(formatDateTime($review['reviewed_at'])); ?></p>
        <?php if (!empty($review['reason'])): ?>
            <p class="mb-0">Notes: <?php echo e($review['reason']); ?></p>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
