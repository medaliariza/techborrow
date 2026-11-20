<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDbConnection();
$equipment = $db->query('SELECT equipment_id, equipment_code, name FROM equipment WHERE status NOT IN ("maintenance", "unavailable", "archived") ORDER BY name')->fetchAll();
$flash = getFlash();
$clearStudentDraft = $flash && $flash['type'] === 'success';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TECHBORROW | Student Borrow Request</title>
    <meta name="theme-color" content="#132b4b">
    <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.webmanifest">
    <link rel="icon" href="<?php echo BASE_URL; ?>/assets/images/techborrow-icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/techborrow-192.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
    <script src="<?php echo BASE_URL; ?>/assets/js/pwa.js" defer></script>
</head>
<body class="student-request-page">
    <header class="student-site-header">
        <div class="container student-site-nav">
            <a class="student-brand" href="<?php echo BASE_URL; ?>/index.php">
                <img src="<?php echo BASE_URL; ?>/assets/images/techborrow-192.png" alt="" width="42" height="42">
                <span>TechBorrow<small>Campus equipment</small></span>
            </a>
            <a class="student-signin-link" href="<?php echo BASE_URL; ?>/index.php">Already have an account? <strong>Sign in</strong></a>
        </div>
    </header>

    <main class="student-request-shell">
        <div class="container">
            <section class="student-request-hero">
                <div class="student-hero-copy">
                    <span class="student-eyebrow">STUDENT EQUIPMENT REQUEST</span>
                    <h1>Make room for <span>what's next.</span></h1>
                    <p>Tell us what you need for class or a project. Our team will review your request and let you know when it's ready.</p>
                </div>
                <ol class="student-progress" aria-label="Request process">
                    <li class="is-current"><span>1</span> Request</li>
                    <li><span>2</span> Review</li>
                    <li><span>3</span> Pick up</li>
                </ol>
            </section>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo e($flash['type']); ?> student-flash" role="alert"><?php echo e($flash['message']); ?></div>
            <?php endif; ?>

            <div class="row g-4 g-xl-5 align-items-start">
                <div class="col-xl-8">
                    <form class="student-request-form" method="POST" action="<?php echo BASE_URL; ?>/actions/submit-student-request.php" enctype="multipart/form-data" data-form-draft="student-borrow" <?php echo $clearStudentDraft ? 'data-clear-form-draft' : ''; ?>>
                        <div class="student-draft-note" data-form-draft-status role="status">
                            <span class="student-note-icon" aria-hidden="true">i</span>
                            <span>Your progress is saved in this browser while you complete the form. Use a device you trust; the draft clears after a successful submission.</span>
                        </div>

                        <section class="student-form-card">
                            <div class="student-section-heading">
                                <span class="student-step-number">01</span>
                                <div>
                                    <span class="student-section-kicker">ABOUT YOU</span>
                                    <h2>Student information</h2>
                                    <p>Use the details registered with your school.</p>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="fullName">Full name</label>
                                    <input class="form-control" id="fullName" name="full_name" maxlength="150" autocomplete="name" placeholder="e.g. Jordan Lee" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="studentNumber">Student number</label>
                                    <input class="form-control" id="studentNumber" name="student_number" maxlength="50" placeholder="Your school ID" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="email">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" maxlength="150" autocomplete="email" placeholder="you@example.com" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="department">Department / program</label>
                                    <input class="form-control" id="department" name="department" maxlength="150" placeholder="e.g. Computer Science" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="studentPhoto">Student photo</label>
                                    <input type="file" class="form-control student-file-input" id="studentPhoto" name="student_photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                                    <div class="form-text">JPEG, PNG, or WebP, up to 5 MB. Authorized admins use it only to verify the request.</div>
                                </div>
                            </div>
                        </section>

                        <section class="student-form-card">
                            <div class="student-section-heading">
                                <span class="student-step-number">02</span>
                                <div>
                                    <span class="student-section-kicker">YOUR PLAN</span>
                                    <h2>Request details</h2>
                                    <p>Share when and where you'll use the equipment.</p>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="purpose">Purpose</label>
                                    <input class="form-control" id="purpose" name="purpose" maxlength="255" placeholder="Class activity, project, or event" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="locationRoom">Location / room</label>
                                    <input class="form-control" id="locationRoom" name="location_room" maxlength="255" placeholder="Where will you use it?" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="startDate">Pickup date and time</label>
                                    <input type="datetime-local" class="form-control" id="startDate" name="start_datetime" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="dueDate">Expected return</label>
                                    <input type="datetime-local" class="form-control" id="dueDate" name="due_datetime" required>
                                </div>
                            </div>
                        </section>

                        <section class="student-form-card">
                            <div class="student-section-heading">
                                <span class="student-step-number">03</span>
                                <div>
                                    <span class="student-section-kicker">WHAT YOU NEED</span>
                                    <h2>Choose equipment</h2>
                                    <p>Availability is checked for your selected dates.</p>
                                </div>
                            </div>
                            <div id="request-items-container">
                                <div class="row g-3 student-equipment-row" data-request-row>
                                    <div class="col-md-7">
                                        <label class="form-label">Equipment
                                            <select class="form-select" name="equipment_id[]" required>
                                                <option value="">Select an item</option>
                                                <?php foreach ($equipment as $item): ?>
                                                    <option value="<?php echo (int) $item['equipment_id']; ?>"><?php echo e($item['name']); ?> (<?php echo e($item['equipment_code']); ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Quantity
                                            <input type="number" class="form-control" name="quantity[]" min="1" placeholder="1" required>
                                        </label>
                                    </div>
                                    <div class="col-md-2 align-self-end">
                                        <button type="button" class="btn btn-outline-secondary student-remove-item w-100" disabled aria-label="Remove equipment item">Remove</button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" id="add-request-row" class="btn btn-outline-primary student-add-item">+ Add another item</button>
                        </section>

                        <div class="student-consent">
                            <input class="form-check-input" type="checkbox" id="photoConsent" name="photo_consent" value="1" required>
                            <label class="form-check-label" for="photoConsent">I consent to authorized administrators using my photo and student information to review this request.</label>
                        </div>

                        <div class="student-form-actions">
                            <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-link student-back-link">Back to sign in</a>
                            <button type="submit" class="btn btn-primary student-submit-button">Send request <span aria-hidden="true">&rarr;</span></button>
                        </div>
                    </form>
                </div>

                <aside class="col-xl-4">
                    <div class="student-aside-card">
                        <span class="student-aside-label">GOOD TO KNOW</span>
                        <h2>What happens next?</h2>
                        <ol class="student-next-steps">
                            <li><span>1</span><div><strong>We review your request</strong><p>An administrator checks the dates and availability.</p></div></li>
                            <li><span>2</span><div><strong>Wait for approval</strong><p>Submitting this form does not mean the item is approved or reserved.</p></div></li>
                            <li><span>3</span><div><strong>Pick up and return</strong><p>Collect equipment only after approval and follow the return time.</p></div></li>
                        </ol>
                        <div class="student-privacy-note">
                            <strong>Your information stays protected</strong>
                            <p>Your photo is used for manual request verification by authorized administrators.</p>
                        </div>
                    </div>
                    <p class="student-aside-footer">Need to manage equipment instead? <a href="<?php echo BASE_URL; ?>/index.php">Sign in to TechBorrow</a>.</p>
                </aside>
            </div>
        </div>
    </main>
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/form-draft.js"></script>
</body>
</html>
