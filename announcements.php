<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDbConnection();
$announcements = $db->query('SELECT a.*, u.full_name AS created_by_name FROM announcements a JOIN users u ON u.user_id = a.created_by ORDER BY a.created_at DESC')->fetchAll();
$pageTitle = 'Announcements';
$activePage = 'announcements';
include __DIR__ . '/includes/header.php';
?>

<h1 class="mb-4">Announcements</h1>

<div class="card p-3">
    <?php if ($announcements): ?>
        <?php foreach ($announcements as $announcement): ?>
            <div class="border rounded p-3 mb-3">
                <h5 class="mb-1"><?php echo e($announcement['title']); ?></h5>
                <small class="text-muted d-block mb-2">By <?php echo e($announcement['created_by_name']); ?> • <?php echo e(formatDateTime($announcement['created_at'])); ?></small>
                <p class="mb-0"><?php echo nl2br(e($announcement['message'])); ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">No announcements have been published yet.</div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>