<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

$db = getDbConnection();
$editingId = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
$editingItem = null;
if ($editingId > 0) {
    $editQuery = $db->prepare('SELECT * FROM announcements WHERE announcement_id = :id LIMIT 1');
    $editQuery->execute(['id' => $editingId]);
    $editingItem = $editQuery->fetch();
}
$announcements = $db->query('SELECT a.*, u.full_name AS created_by_name FROM announcements a JOIN users u ON u.user_id = a.created_by ORDER BY a.created_at DESC')->fetchAll();
$pageTitle = 'Announcements';
$activePage = 'admin-announcements';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="mb-4">Announcements</h1>

<div class="card p-3 mb-4">
    <h4><?php echo $editingItem ? 'Edit Announcement' : 'Publish New Announcement'; ?></h4>
    <form action="<?php echo BASE_URL; ?>/actions/create-announcement.php" method="POST">
        <input type="hidden" name="action" value="<?php echo $editingItem ? 'update' : 'create'; ?>">
        <?php if ($editingItem): ?>
            <input type="hidden" name="announcement_id" value="<?php echo (int) $editingItem['announcement_id']; ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label for="title" class="form-label">Title</label>
            <input type="text" class="form-control" id="title" name="title" required maxlength="200" value="<?php echo e($editingItem['title'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label for="message" class="form-label">Message</label>
            <textarea class="form-control" id="message" name="message" rows="4" required><?php echo e($editingItem['message'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $editingItem ? 'Save Changes' : 'Publish'; ?></button>
        <?php if ($editingItem): ?>
            <a href="announcements.php" class="btn btn-outline-secondary">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<div class="card p-3">
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Message</th>
                    <th>Created By</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($announcements): ?>
                    <?php foreach ($announcements as $item): ?>
                        <tr>
                            <td><?php echo e($item['title']); ?></td>
                            <td><?php echo e($item['message']); ?></td>
                            <td><?php echo e($item['created_by_name']); ?></td>
                            <td><?php echo e(formatDateTime($item['created_at'])); ?></td>
                            <td class="text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="announcements.php?edit_id=<?php echo (int) $item['announcement_id']; ?>">Edit</a>
                                <form class="d-inline" action="<?php echo BASE_URL; ?>/actions/create-announcement.php" method="POST" onsubmit="return confirm('Delete this announcement for every account?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="announcement_id" value="<?php echo (int) $item['announcement_id']; ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No announcements published.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
