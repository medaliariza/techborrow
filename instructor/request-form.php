<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['instructor']);

$db = getDbConnection();
$equipment = $db->query('SELECT * FROM equipment WHERE status != "archived" ORDER BY name')->fetchAll();
$pageTitle = 'Submit Borrowing Request';
$activePage = 'instructor-request-form';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="mb-4">Submit Borrowing Request</h1>

<form method="POST" action="<?php echo BASE_URL; ?>/actions/submit-request.php">
    <div class="form-section mb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Purpose</label>
                <input type="text" class="form-control" name="purpose" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="locationRoom">Location / Room</label>
                <input type="text" class="form-control" id="locationRoom" name="location_room" maxlength="255" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Start Date & Time</label>
                <input type="datetime-local" class="form-control" name="start_datetime" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Expected Return Date & Time</label>
                <input type="datetime-local" class="form-control" name="due_datetime" required>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h4>Equipment Requested</h4>
        <div id="request-items-container">
            <div class="row g-3 mb-3" data-request-row>
                <div class="col-md-7">
                    <label class="form-label">Equipment</label>
                    <select class="form-select" name="equipment_id[]" required>
                        <option value="">Select equipment</option>
                        <?php foreach ($equipment as $item): ?>
                            <option value="<?php echo (int)$item['equipment_id']; ?>"><?php echo e($item['name']); ?> (<?php echo e($item['equipment_code']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Quantity</label>
                    <input type="number" class="form-control" name="quantity[]" min="1" required>
                </div>
                <div class="col-md-2 align-self-end">
                    <button type="button" class="btn btn-outline-secondary w-100" disabled>Remove</button>
                </div>
            </div>
        </div>

        <button type="button" id="add-request-row" class="btn btn-outline-primary mt-2">Add Another Item</button>
        <div class="mt-4 text-end">
            <button type="submit" class="btn btn-primary">Submit Request</button>
        </div>
    </div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
