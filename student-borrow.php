<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDbConnection();
$equipment = $db->query('SELECT equipment_id, equipment_code, name FROM equipment WHERE status NOT IN ("maintenance", "unavailable", "archived") ORDER BY name')->fetchAll();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TECHBORROW | Student Borrow Request</title>
    <meta name="theme-color" content="#0d6efd">
    <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.webmanifest">
    <link rel="icon" href="<?php echo BASE_URL; ?>/assets/images/techborrow-icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/techborrow-192.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <script src="<?php echo BASE_URL; ?>/assets/js/pwa.js" defer></script>
</head>
<body class="auth-body">
    <main class="container py-4">
        <div class="card p-3 p-md-4 mx-auto" style="max-width: 900px;">
            <h1 class="h3 mb-2">Student Borrow Request</h1>
            <p class="text-muted mb-4">Complete your details and select the equipment you need. A technical administrator will review your request.</p>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo e($flash['type']); ?>" role="alert"><?php echo e($flash['message']); ?></div>
            <?php endif; ?>

            <form method="POST" action="<?php echo BASE_URL; ?>/actions/submit-student-request.php" enctype="multipart/form-data">
                <section class="form-section mb-4">
                    <h2 class="h5 mb-3">Student Information</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="fullName">Full Name</label>
                            <input class="form-control" id="fullName" name="full_name" maxlength="150" autocomplete="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="studentNumber">Student Number</label>
                            <input class="form-control" id="studentNumber" name="student_number" maxlength="50" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" maxlength="150" autocomplete="email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="department">Department / Program</label>
                            <input class="form-control" id="department" name="department" maxlength="150" placeholder="e.g. Computer Science" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="studentPhoto">Portrait photo</label>
                            <input type="file" class="form-control" id="studentPhoto" name="student_photo" accept="image/*" required>
                            <div class="form-text">Choose an existing image from your gallery or take a new photo. JPEG, PNG, or WebP; maximum 5 MB. Admins use it only for manual verification.</div>
                        </div>
                    </div>
                </section>

                <section class="form-section mb-4">
                    <h2 class="h5 mb-3">Request Details</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="purpose">Purpose</label>
                            <input class="form-control" id="purpose" name="purpose" maxlength="255" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="locationRoom">Location / Room</label>
                            <input class="form-control" id="locationRoom" name="location_room" maxlength="255" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="startDate">Start Date &amp; Time</label>
                            <input type="datetime-local" class="form-control" id="startDate" name="start_datetime" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="dueDate">Expected Return Date &amp; Time</label>
                            <input type="datetime-local" class="form-control" id="dueDate" name="due_datetime" required>
                        </div>
                    </div>
                </section>

                <section class="form-section mb-4">
                    <h2 class="h5 mb-3">Equipment Requested</h2>
                    <div id="request-items-container">
                        <div class="row g-3 mb-3" data-request-row>
                            <div class="col-md-8">
                                <label class="form-label">Equipment</label>
                                <select class="form-select" name="equipment_id[]" required>
                                    <option value="">Select equipment</option>
                                    <?php foreach ($equipment as $item): ?>
                                        <option value="<?php echo (int) $item['equipment_id']; ?>"><?php echo e($item['name']); ?> (<?php echo e($item['equipment_code']); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control" name="quantity[]" min="1" required>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="add-request-row" class="btn btn-outline-primary">Add Another Item</button>
                </section>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="photoConsent" name="photo_consent" value="1" required>
                    <label class="form-check-label" for="photoConsent">I consent to my portrait and student information being used by authorized administrators to review and process this borrowing request.</label>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-between">
                    <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-outline-secondary">Back to Login</a>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </main>
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
