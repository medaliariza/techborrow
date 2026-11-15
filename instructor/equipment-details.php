<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['instructor']);

$id = (int) ($_GET['id'] ?? 0);
$db = getDbConnection();
$stmt = $db->prepare('SELECT e.*, c.name AS category_name FROM equipment e LEFT JOIN categories c ON c.category_id = e.category_id WHERE e.equipment_id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$item = $stmt->fetch();
if (!$item) {
    redirect(BASE_URL . '/instructor/equipment.php');
}
$pageTitle = 'Equipment Details';
$activePage = 'instructor-equipment';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="mb-4">Equipment Details</h1>

<div class="card p-4">
    <div class="row">
        <div class="col-md-5">
            <?php if (!empty($item['photo_url'])): ?>
                <img src="<?php echo e(BASE_URL . '/' . ltrim($item['photo_url'], '/')); ?>" alt="<?php echo e($item['name']); ?>" class="img-fluid rounded border">
            <?php else: ?>
                <div class="bg-light rounded p-4 text-center">
                    <strong><?php echo e($item['name']); ?></strong>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-md-7">
            <table class="table table-borderless">
                <tr><th>Code</th><td><?php echo e($item['equipment_code']); ?></td></tr>
                <tr><th>Category</th><td><?php echo e($item['category_name'] ?? 'Uncategorized'); ?></td></tr>
                <tr><th>Brand</th><td><?php echo e($item['brand']); ?></td></tr>
                <tr><th>Model</th><td><?php echo e($item['model']); ?></td></tr>
                <tr><th>Condition</th><td><?php echo e($item['condition']); ?></td></tr>
                <tr><th>Status</th><td><span class="badge <?php echo e(getStatusBadgeClass($item['status'])); ?>"><?php echo e($item['status']); ?></span></td></tr>
                <tr><th>Total Quantity</th><td><?php echo e($item['total_quantity']); ?></td></tr>
                <tr><th>Location</th><td><?php echo e($item['location']); ?></td></tr>
            </table>
        </div>
    </div>
    <div class="mt-4">
        <a href="request-form.php" class="btn btn-primary">Request This Equipment</a>
        <a href="equipment.php" class="btn btn-outline-secondary ms-2">Back to Catalog</a>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
