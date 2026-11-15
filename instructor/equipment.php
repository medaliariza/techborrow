<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['instructor']);

$db = getDbConnection();
$searchTerm = trim($_GET['search'] ?? '');
$query = 'SELECT e.*, c.name AS category_name FROM equipment e LEFT JOIN categories c ON c.category_id = e.category_id WHERE e.status != "archived"';
$params = [];
if ($searchTerm !== '') {
    $query .= ' AND (e.name LIKE :search OR e.equipment_code LIKE :search OR c.name LIKE :search)';
    $params['search'] = '%' . $searchTerm . '%';
}
$query .= ' ORDER BY e.name';
$stmt = $db->prepare($query);
$stmt->execute($params);
$equipment = $stmt->fetchAll();
$pageTitle = 'Equipment Catalog';
$activePage = 'instructor-equipment';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="mb-4">Equipment Catalog</h1>

<div class="card p-3 mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-9">
            <label class="form-label">Search equipment</label>
            <input type="text" class="form-control" name="search" value="<?php echo e($searchTerm); ?>">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100">Search</button>
        </div>
    </form>
</div>

<div class="card p-3">
    <div class="table-responsive d-none d-md-block">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Condition</th>
                    <th>Status</th>
                    <th>Available</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($equipment): ?>
                    <?php foreach ($equipment as $item): ?>
                        <tr>
                            <td>
                                <?php if (!empty($item['photo_url'])): ?>
                                    <img src="<?php echo e(BASE_URL . '/' . ltrim($item['photo_url'], '/')); ?>" alt="<?php echo e($item['name']); ?>" style="width: 56px; height: 56px; object-fit: cover;" class="rounded border">
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($item['equipment_code']); ?></td>
                            <td><?php echo e($item['name']); ?></td>
                            <td><?php echo e($item['category_name'] ?? 'Uncategorized'); ?></td>
                            <td><?php echo e($item['condition']); ?></td>
                            <td><span class="badge <?php echo e(getStatusBadgeClass($item['status'])); ?>"><?php echo e($item['status']); ?></span></td>
                            <td><?php echo e($item['total_quantity']); ?></td>
                            <td>
                                <a href="equipment-details.php?id=<?php echo (int)$item['equipment_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8">No equipment found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="equipment-mobile-list d-md-none">
        <?php if ($equipment): ?>
            <?php foreach ($equipment as $item): ?>
                <article class="equipment-mobile-item">
                    <div class="d-flex gap-3 align-items-start">
                        <?php if (!empty($item['photo_url'])): ?>
                            <img src="<?php echo e(BASE_URL . '/' . ltrim($item['photo_url'], '/')); ?>" alt="<?php echo e($item['name']); ?>" class="equipment-mobile-photo rounded border">
                        <?php else: ?>
                            <div class="equipment-mobile-photo equipment-mobile-photo-empty rounded border text-muted" aria-hidden="true">—</div>
                        <?php endif; ?>
                        <div class="min-w-0 flex-grow-1">
                            <h2 class="h6 mb-1"><?php echo e($item['name']); ?></h2>
                            <div class="small text-muted"><?php echo e($item['equipment_code']); ?></div>
                            <span class="badge mt-2 <?php echo e(getStatusBadgeClass($item['status'])); ?>"><?php echo e($item['status']); ?></span>
                        </div>
                    </div>
                    <dl class="equipment-mobile-details mb-3">
                        <div>
                            <dt>Category</dt>
                            <dd><?php echo e($item['category_name'] ?? 'Uncategorized'); ?></dd>
                        </div>
                        <div>
                            <dt>Condition</dt>
                            <dd><?php echo e($item['condition']); ?></dd>
                        </div>
                        <div>
                            <dt>Available</dt>
                            <dd><?php echo e($item['total_quantity']); ?></dd>
                        </div>
                    </dl>
                    <a href="equipment-details.php?id=<?php echo (int)$item['equipment_id']; ?>" class="btn btn-outline-primary w-100">View equipment</a>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted mb-0">No equipment found.</p>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
