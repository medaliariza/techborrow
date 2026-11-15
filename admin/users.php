<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

$db = getDbConnection();
$users = $db->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();
$pageTitle = 'User Management';
$activePage = 'admin-users';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="mb-4">User Accounts</h1>
<div class="card p-3">
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Student Number</th>
                    <th>Account Type</th>
                    <th>Department / Designation</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users): ?>
                    <?php foreach ($users as $item): ?>
                        <tr>
                            <td><?php echo e($item['full_name']); ?></td>
                            <td><?php echo e($item['email']); ?></td>
                            <td><?php echo e($item['student_number'] ?? '—'); ?></td>
                            <td><?php echo e(getRoleLabel($item['role'])); ?></td>
                            <td><?php echo e($item['role'] === 'admin' ? 'Technical Personnel' : ($item['role'] === 'student' ? 'Student' : ($item['department'] ?: 'Not provided'))); ?></td>
                            <td><span class="badge bg-primary"><?php echo e($item['account_status']); ?></span></td>
                            <td>
                                <form method="POST" action="<?php echo BASE_URL; ?>/actions/update-user-status.php" class="d-flex gap-2 align-items-center">
                                    <input type="hidden" name="user_id" value="<?php echo (int) $item['user_id']; ?>">
                                    <select class="form-select form-select-sm" name="account_status">
                                        <?php foreach (['active', 'inactive', 'suspended'] as $status): ?>
                                            <option value="<?php echo e($status); ?>" <?php echo $item['account_status'] === $status ? 'selected' : ''; ?>>
                                                <?php echo e(ucfirst($status)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
