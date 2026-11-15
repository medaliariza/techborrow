<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

$db = getDbConnection();
$categories = $db->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$sortColumns = [
    'code' => 'e.equipment_code',
    'name' => 'e.name',
    'category' => 'c.name',
    'condition' => 'e.`condition`',
    'status' => 'e.status',
    'quantity' => 'e.total_quantity',
];
$sort = $_GET['sort'] ?? 'name';
if (!isset($sortColumns[$sort])) {
    $sort = 'name';
}
$sortDirection = strtoupper($_GET['direction'] ?? 'ASC');
if (!in_array($sortDirection, ['ASC', 'DESC'], true)) {
    $sortDirection = 'ASC';
}
$sortUrl = static function ($column) use ($sort, $sortDirection) {
    $query = $_GET;
    unset($query['edit_id']);
    $query['sort'] = $column;
    $query['direction'] = $sort === $column && $sortDirection === 'ASC' ? 'DESC' : 'ASC';
    return 'equipment.php?' . http_build_query($query);
};
$sortIndicator = static function ($column) use ($sort, $sortDirection) {
    if ($sort !== $column) {
        return '';
    }
    return $sortDirection === 'ASC' ? ' &uarr;' : ' &darr;';
};
$editingId = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
$editingItem = null;

if ($editingId > 0) {
    $stmt = $db->prepare('SELECT * FROM equipment WHERE equipment_id = :id LIMIT 1');
    $stmt->execute(['id' => $editingId]);
    $editingItem = $stmt->fetch();
}

$equipment = $db->query('SELECT e.*, c.name AS category_name FROM equipment e LEFT JOIN categories c ON c.category_id = e.category_id ORDER BY ' . $sortColumns[$sort] . ' ' . $sortDirection . ', e.name ASC')->fetchAll();
$pageTitle = 'Equipment Management';
$activePage = 'admin-equipment';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="mb-4">Equipment Inventory</h1>

<div class="card p-4 mb-4">
    <h4><?php echo $editingItem ? 'Edit Equipment' : 'Add Equipment'; ?></h4>
    <?php if (!$editingItem): ?>
        <p class="text-muted">Add each physical unit separately so its brand, model, and serial number can be recorded individually. Each submission adds one unit.</p>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data" action="<?php echo BASE_URL; ?>/actions/manage-equipment.php">
        <?php if ($editingItem): ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="equipment_id" value="<?php echo (int) $editingItem['equipment_id']; ?>">
        <?php else: ?>
            <input type="hidden" name="action" value="create">
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Equipment Code</label>
                <input type="text" class="form-control" name="equipment_code" value="<?php echo e($editingItem['equipment_code'] ?? ''); ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" name="name" value="<?php echo e($editingItem['name'] ?? ''); ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select class="form-select" name="category_id" required>
                    <option value="">Select category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo (int) $category['category_id']; ?>" <?php echo ($editingItem && (int) $editingItem['category_id'] === (int) $category['category_id']) ? 'selected' : ''; ?>>
                            <?php echo e($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!$categories): ?>
                    <div class="form-text text-danger">Add a category before saving equipment. <a href="categories.php">Create category</a>.</div>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <?php if ($editingItem): ?>
                    <label class="form-label">Inventory Quantity</label>
                    <div class="form-control bg-light" aria-readonly="true">
                        <?php echo (int) $editingItem['available_quantity']; ?> available of <?php echo (int) $editingItem['total_quantity']; ?>
                    </div>
                <?php else: ?>
                    <label class="form-label">Quantity</label>
                    <div class="form-control bg-light" aria-readonly="true">1 unit</div>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <label class="form-label">Condition</label>
                <select class="form-select" name="condition" required>
                    <?php foreach (['excellent', 'good', 'fair', 'damaged', 'maintenance'] as $condition): ?>
                        <option value="<?php echo e($condition); ?>" <?php echo ($editingItem && $editingItem['condition'] === $condition) ? 'selected' : ''; ?>>
                            <?php echo e(ucfirst($condition)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Brand</label>
                <input type="text" class="form-control" name="brand" value="<?php echo e($editingItem['brand'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Model</label>
                <input type="text" class="form-control" name="model" value="<?php echo e($editingItem['model'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Serial Number</label>
                <input type="text" class="form-control" name="serial_number" value="<?php echo e($editingItem['serial_number'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <label for="photo" class="form-label">Equipment Photo</label>
                <input type="file" class="form-control" id="photo" name="photo" accept="image/jpeg,image/png,image/gif,image/webp">
                <div class="form-text">Optional. JPG, PNG, GIF, or WEBP; maximum 5 MB.</div>
                <?php if ($editingItem && !empty($editingItem['photo_url'])): ?>
                    <div class="d-flex align-items-center gap-3 mt-2">
                        <img src="<?php echo e(BASE_URL . '/' . ltrim($editingItem['photo_url'], '/')); ?>" alt="<?php echo e($editingItem['name']); ?>" style="width: 72px; height: 72px; object-fit: cover;" class="rounded border">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="remove_photo" value="1">
                            Remove current photo
                        </label>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <label class="form-label"> </label>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary"><?php echo $editingItem ? 'Update Equipment' : 'Add Equipment'; ?></button>
                    <?php if ($editingItem): ?>
                        <a href="equipment.php" class="btn btn-outline-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="card p-3">
    <form method="GET" class="row g-2 align-items-end mb-3">
        <?php if ($editingId > 0): ?>
            <input type="hidden" name="edit_id" value="<?php echo $editingId; ?>">
        <?php endif; ?>
        <div class="col-12 col-sm-5 col-md-4">
            <label for="sort" class="form-label">Sort equipment by</label>
            <select class="form-select" id="sort" name="sort">
                <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name</option>
                <option value="code" <?php echo $sort === 'code' ? 'selected' : ''; ?>>Equipment code</option>
                <option value="category" <?php echo $sort === 'category' ? 'selected' : ''; ?>>Category</option>
                <option value="condition" <?php echo $sort === 'condition' ? 'selected' : ''; ?>>Condition</option>
                <option value="status" <?php echo $sort === 'status' ? 'selected' : ''; ?>>Status</option>
                <option value="quantity" <?php echo $sort === 'quantity' ? 'selected' : ''; ?>>Quantity</option>
            </select>
        </div>
        <div class="col-12 col-sm-4 col-md-3">
            <label for="direction" class="form-label">Order</label>
            <select class="form-select" id="direction" name="direction">
                <option value="ASC" <?php echo $sortDirection === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                <option value="DESC" <?php echo $sortDirection === 'DESC' ? 'selected' : ''; ?>>Descending</option>
            </select>
        </div>
        <div class="col-12 col-sm-3 col-md-2">
            <button type="submit" class="btn btn-outline-primary w-100">Sort</button>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th aria-sort="<?php echo $sort === 'code' ? strtolower($sortDirection) . 'ending' : 'none'; ?>"><a href="<?php echo e($sortUrl('code')); ?>">Code<?php echo $sortIndicator('code'); ?></a></th>
                    <th>Photo</th>
                    <th aria-sort="<?php echo $sort === 'name' ? strtolower($sortDirection) . 'ending' : 'none'; ?>"><a href="<?php echo e($sortUrl('name')); ?>">Name<?php echo $sortIndicator('name'); ?></a></th>
                    <th aria-sort="<?php echo $sort === 'category' ? strtolower($sortDirection) . 'ending' : 'none'; ?>"><a href="<?php echo e($sortUrl('category')); ?>">Category<?php echo $sortIndicator('category'); ?></a></th>
                    <th aria-sort="<?php echo $sort === 'condition' ? strtolower($sortDirection) . 'ending' : 'none'; ?>"><a href="<?php echo e($sortUrl('condition')); ?>">Condition<?php echo $sortIndicator('condition'); ?></a></th>
                    <th aria-sort="<?php echo $sort === 'status' ? strtolower($sortDirection) . 'ending' : 'none'; ?>"><a href="<?php echo e($sortUrl('status')); ?>">Status<?php echo $sortIndicator('status'); ?></a></th>
                    <th aria-sort="<?php echo $sort === 'quantity' ? strtolower($sortDirection) . 'ending' : 'none'; ?>"><a href="<?php echo e($sortUrl('quantity')); ?>">Qty<?php echo $sortIndicator('quantity'); ?></a></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($equipment): ?>
                    <?php foreach ($equipment as $item): ?>
                        <tr>
                            <td><?php echo e($item['equipment_code']); ?></td>
                            <td>
                                <?php if (!empty($item['photo_url'])): ?>
                                    <img src="<?php echo e(BASE_URL . '/' . ltrim($item['photo_url'], '/')); ?>" alt="<?php echo e($item['name']); ?>" style="width: 56px; height: 56px; object-fit: cover;" class="rounded border">
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($item['name']); ?></td>
                            <td><?php echo e($item['category_name'] ?? 'Uncategorized'); ?></td>
                            <td><?php echo e($item['condition']); ?></td>
                            <td><span class="badge <?php echo e(getStatusBadgeClass($item['status'])); ?>"><?php echo e($item['status']); ?></span></td>
                            <td><?php echo e($item['total_quantity']); ?></td>
                            <td class="table-actions">
                                <a href="equipment.php?<?php echo e(http_build_query(array_merge($_GET, ['edit_id' => (int) $item['equipment_id']]))); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="<?php echo BASE_URL; ?>/actions/manage-equipment.php" class="d-inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="equipment_id" value="<?php echo (int) $item['equipment_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this equipment? Items with request or maintenance history will be archived instead of permanently deleted.')">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8">No equipment records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
