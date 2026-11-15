<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

$db = getDbConnection();
$categories = $db->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$pageTitle = 'Equipment Categories';
$activePage = 'admin-categories';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="mb-4">Equipment Categories</h1>
<div class="card p-3 mb-4">
   <h2 class="h5">Add Category</h2>
   <form method="POST" action="<?php echo BASE_URL; ?>/actions/manage-category.php" class="row g-3 align-items-end">
       <div class="col-md-4">
           <label for="categoryName" class="form-label">Category Name</label>
           <input type="text" class="form-control" id="categoryName" name="name" maxlength="100" required>
       </div>
       <div class="col-md-6">
           <label for="categoryDescription" class="form-label">Description</label>
           <input type="text" class="form-control" id="categoryDescription" name="description" maxlength="1000">
       </div>
       <div class="col-md-2 d-grid">
           <button type="submit" class="btn btn-primary">Add Category</button>
       </div>
   </form>
</div>
<div class="card p-3">
   <div class="table-responsive">
       <table class="table table-striped align-middle">
           <thead>
               <tr>
                   <th>ID</th>
                   <th>Name</th>
                   <th>Description</th>
               </tr>
           </thead>
           <tbody>
               <?php if ($categories): ?>
                   <?php foreach ($categories as $category): ?>
                       <tr>
                           <td><?php echo e($category['category_id']); ?></td>
                           <td><?php echo e($category['name']); ?></td>
                           <td><?php echo e($category['description'] ?? ''); ?></td>
                       </tr>
                   <?php endforeach; ?>
               <?php else: ?>
                   <tr><td colspan="3">No categories yet. Add one above before creating equipment.</td></tr>
               <?php endif; ?>
           </tbody>
       </table>
   </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
