<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['instructor']);

$id = (int) ($_GET['id'] ?? 0);
$db = getDbConnection();
$stmt = $db->prepare('SELECT e.*, c.name AS category_name FROM equipment e LEFT JOIN categories c ON c.category_id = e.category_id WHERE e.equipment_id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$item = $stmt->fetch();
if (!$item || !isset($item['equipment_id'])) {
    redirect(BASE_URL . '/instructor/equipment.php');
}
$item += [
    'name' => 'Equipment name unavailable',
    'photo_url' => null,
    'equipment_code' => '—',
    'category_name' => null,
    'brand' => null,
    'model' => null,
    'condition' => 'Unknown',
    'status' => 'unavailable',
    'total_quantity' => 0,
    'location' => null,
];
$availability = getEffectiveEquipmentAvailability(
    $db,
    (int) $item['equipment_id'],
    date('Y-m-d H:i:s'),
    date('Y-m-d H:i:s', time() + 60)
);
$isAvailable = $availability['available'] > 0;
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
                <tr><th>Brand</th><td><?php echo e($item['brand'] ?? '—'); ?></td></tr>
                <tr><th>Model</th><td><?php echo e($item['model'] ?? '—'); ?></td></tr>
                <tr><th>Condition</th><td><?php echo e($item['condition']); ?></td></tr>
                <tr><th>Status</th><td><span class="badge <?php echo e($isAvailable ? 'bg-success' : 'bg-danger'); ?>"><?php echo $isAvailable ? 'Available' : 'Unavailable'; ?></span></td></tr>
                <tr><th>Available Quantity</th><td><?php echo e($availability['available']); ?></td></tr>
                <tr><th>Location</th><td><?php echo e($item['location'] ?? '—'); ?></td></tr>
            </table>
            <?php if (!$isAvailable): ?>
                <div class="alert alert-warning" role="status">This item is currently unavailable. You can still open the request form to choose another item or date.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="mt-4">
        <a href="request-form.php?equipment_id=<?php echo (int) $item['equipment_id']; ?>" class="btn btn-primary">Request This Equipment</a>
        <a href="equipment.php" class="btn btn-outline-secondary ms-2">Back to Catalog</a>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
