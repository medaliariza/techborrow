<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['instructor']);

$db = getDbConnection();
$equipment = $db->query('SELECT * FROM equipment WHERE status != "archived" ORDER BY name')->fetchAll();
$draft = $_SESSION['request_form_draft'] ?? [];
unset($_SESSION['request_form_draft']);
$instructorDraftKey = 'instructor-borrow-' . (int) $_SESSION['user_id'];
$clearRequestDraft = ($_SESSION['clear_instructor_request_draft'] ?? '') === $instructorDraftKey;
unset($_SESSION['clear_instructor_request_draft']);
$draftItems = isset($draft['items']) && is_array($draft['items']) ? $draft['items'] : [];
$requestedEquipmentId = filter_var($_GET['equipment_id'] ?? null, FILTER_VALIDATE_INT);
if ($requestedEquipmentId) {
    if (!$draftItems) {
        $draftItems = [['equipment_id' => (string) $requestedEquipmentId, 'quantity' => '1']];
    } else {
        $draftItems[0]['equipment_id'] = (string) $requestedEquipmentId;
        if (empty($draftItems[0]['quantity'])) {
            $draftItems[0]['quantity'] = '1';
        }
    }
} elseif (!$draftItems) {
    $draftItems = [[
        'equipment_id' => '',
        'quantity' => '',
    ]];
}

$hasDraftPeriod = !empty($draft['start_datetime'])
    && !empty($draft['due_datetime'])
    && strtotime($draft['start_datetime']) !== false
    && strtotime($draft['due_datetime']) > strtotime($draft['start_datetime']);
$availabilityById = [];
foreach ($equipment as $equipmentItem) {
    if ($hasDraftPeriod) {
        $availability = getEffectiveEquipmentAvailability(
            $db,
            (int) $equipmentItem['equipment_id'],
            date('Y-m-d H:i:s', strtotime($draft['start_datetime'])),
            date('Y-m-d H:i:s', strtotime($draft['due_datetime']))
        );
        $availabilityById[(int) $equipmentItem['equipment_id']] = $availability['available'];
    } else {
        $blocked = in_array($equipmentItem['status'], ['maintenance', 'unavailable', 'archived'], true)
            || in_array($equipmentItem['condition'], ['damaged', 'maintenance'], true);
        $availabilityById[(int) $equipmentItem['equipment_id']] = $blocked
            ? 0
            : (int) $equipmentItem['available_quantity'];
    }
}
$selectedEquipmentId = filter_var($draftItems[0]['equipment_id'] ?? null, FILTER_VALIDATE_INT);
$selectedEquipmentUnavailable = $selectedEquipmentId
    && array_key_exists($selectedEquipmentId, $availabilityById)
    && $availabilityById[$selectedEquipmentId] < 1;
$pageTitle = 'Submit Borrowing Request';
$activePage = 'instructor-request-form';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="mb-4">Submit Borrowing Request</h1>
<?php if ($selectedEquipmentUnavailable): ?>
    <div class="alert alert-warning" role="status">
        The item you selected is currently unavailable<?php echo $hasDraftPeriod ? ' for the selected dates' : ''; ?>. Choose another available item or change your dates before submitting.
    </div>
<?php endif; ?>

<form method="POST" action="<?php echo BASE_URL; ?>/actions/submit-request.php" data-form-draft="<?php echo e($instructorDraftKey); ?>" <?php echo $clearRequestDraft ? 'data-clear-form-draft' : ''; ?>>
    <p class="form-text" data-form-draft-status role="status">Your entries are saved in this browser while you complete the request. If your connection drops, return to this page to continue. The saved draft clears after a successful submission.</p>
    <div class="form-section mb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Purpose</label>
                <input type="text" class="form-control" name="purpose" value="<?php echo e($draft['purpose'] ?? ''); ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="locationRoom">Location / Room</label>
                <input type="text" class="form-control" id="locationRoom" name="location_room" maxlength="255" value="<?php echo e($draft['location_room'] ?? ''); ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Start Date & Time</label>
                <input type="datetime-local" class="form-control" name="start_datetime" value="<?php echo e($draft['start_datetime'] ?? ''); ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Expected Return Date & Time</label>
                <input type="datetime-local" class="form-control" name="due_datetime" value="<?php echo e($draft['due_datetime'] ?? ''); ?>" required>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h4>Equipment Requested</h4>
        <div id="request-items-container">
            <?php foreach ($draftItems as $index => $draftItem): ?>
                <div class="row g-3 mb-3" data-request-row>
                    <div class="col-md-7">
                        <label class="form-label">Equipment</label>
                        <select class="form-select" name="equipment_id[]" required>
                            <option value="">Select equipment</option>
                            <?php foreach ($equipment as $equipmentItem): ?>
                                <?php $equipmentAvailable = $availabilityById[(int) $equipmentItem['equipment_id']] > 0; ?>
                                <option value="<?php echo (int) $equipmentItem['equipment_id']; ?>" <?php echo (string) ($draftItem['equipment_id'] ?? '') === (string) $equipmentItem['equipment_id'] ? 'selected' : ''; ?>>
                                    <?php echo e($equipmentItem['name']); ?> (<?php echo e($equipmentItem['equipment_code']); ?>) — <?php echo $equipmentAvailable ? 'Available: ' . (int) $availabilityById[(int) $equipmentItem['equipment_id']] : 'Unavailable'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control" name="quantity[]" min="1" value="<?php echo e($draftItem['quantity'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-2 align-self-end">
                        <button type="button" class="btn btn-outline-secondary w-100" <?php echo $index === 0 && count($draftItems) === 1 ? 'disabled' : ''; ?>>Remove</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" id="add-request-row" class="btn btn-outline-primary mt-2">Add Another Item</button>
        <div class="mt-4 text-end">
            <button type="submit" class="btn btn-primary">Submit Request</button>
        </div>
    </div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
