<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($_SESSION['user_id'])) {
    redirect(BASE_URL . '/dashboard.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $department = trim($_POST['department'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($fullName === '' || $email === '' || $department === '' || $password === '') {
        $errors[] = 'Please fill in all required fields.';
    }

    if (strlen($department) > 150) {
        $errors[] = 'Department must be 150 characters or fewer.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!$errors) {
        $db = getDbConnection();
        $exists = $db->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
        $exists->execute(['email' => $email]);

        if ($exists->fetch()) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $stmt = $db->prepare('INSERT INTO users (full_name, email, password_hash, role, department, account_status, created_at) VALUES (:name, :email, :password_hash, :role, :department, :status, NOW())');
            $stmt->execute([
                'name' => $fullName,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'instructor',
                'department' => $department,
                'status' => 'active',
            ]);

            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TECHBORROW | Register</title>
    <meta name="theme-color" content="#132b4b">
    <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.webmanifest">
    <link rel="icon" href="<?php echo BASE_URL; ?>/assets/images/techborrow-icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/techborrow-192.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
    <script src="<?php echo BASE_URL; ?>/assets/js/pwa.js" defer></script>
</head>
<body class="auth-body">
    <div class="container min-vh-100 d-flex align-items-center justify-content-center">
        <div class="row shadow auth-card auth-register">
            <div class="col-lg-6 auth-panel-left d-none d-lg-flex">
                <div>
                    <h1>Register</h1>
                    <p>Create an instructor account and begin requesting equipment for classes and activities.</p>
                </div>
            </div>
            <div class="col-lg-6 auth-panel-right">
                <h2 class="mb-4">Create Account</h2>

                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endforeach; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">Registration successful. <a href="index.php">Login here</a>.</div>
                <?php else: ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="department" name="department" maxlength="150" placeholder="e.g. Computer Science, Accountancy, Business Administration" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                <?php endif; ?>

                <div class="mt-3 text-center">
                    <a href="index.php">Back to login</a>
                </div>
                <div class="mt-2 text-center">
                    <a href="student-borrow.php">Student? Request equipment</a>
                </div>
                <div class="mt-2 text-center">
                    <a class="btn btn-outline-secondary btn-sm" href="<?php echo BASE_URL; ?>/download-apk.php">Download TechBorrow for Android</a>
                    <div class="form-text">Download on an Android phone, then open the file from Downloads and confirm installation. APK files cannot be installed on Windows, iPhone, or iPad.</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
