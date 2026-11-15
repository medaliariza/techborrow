<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = currentUser();
$pageTitle = 'My Profile';
$activePage = $user['role'] === 'admin' ? 'admin-profile' : 'instructor-profile';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="mb-4">My Profile</h1>

<div class="card p-4">
    <form method="POST" action="<?php echo BASE_URL; ?>/actions/update-profile.php">
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label" for="fullName">Full Name</label>
                <input type="text" class="form-control" id="fullName" name="full_name" maxlength="150" value="<?php echo e($user['full_name']); ?>" required autocomplete="name">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" maxlength="150" value="<?php echo e($user['email']); ?>" required autocomplete="email">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="newPassword">New Password</label>
                <input type="password" class="form-control" id="newPassword" name="new_password" minlength="8" autocomplete="new-password">
                <div class="form-text">Leave blank to keep your current password.</div>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="confirmPassword">Confirm New Password</label>
                <input type="password" class="form-control" id="confirmPassword" name="confirm_password" minlength="8" autocomplete="new-password">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="role">Role</label>
                <input type="text" class="form-control" id="role" value="<?php echo e(getRoleLabel($user['role'])); ?>" readonly>
            </div>
            <?php if ($user['role'] === 'instructor'): ?>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="department">Department</label>
                    <input type="text" class="form-control" id="department" name="department" maxlength="150" value="<?php echo e($user['department'] ?? ''); ?>" required>
                </div>
            <?php endif; ?>
            <div class="col-12 col-md-6">
                <label class="form-label" for="accountStatus">Account Status</label>
                <input type="text" class="form-control" id="accountStatus" value="<?php echo e(ucfirst($user['account_status'])); ?>" readonly>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">Save Profile</button>
            </div>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
