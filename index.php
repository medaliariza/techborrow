<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($_SESSION['user_id']) && currentUser()) {
    redirect(BASE_URL . '/dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    } else {
        $db = getDbConnection();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash']) && $user['account_status'] === 'active') {
            $_SESSION['user_id'] = (int) $user['user_id'];
            redirect(BASE_URL . '/dashboard.php');
        }

        $errors[] = 'Invalid login credentials or inactive account.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TECHBORROW | Login</title>
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
        <div class="row shadow auth-card">
            <div class="col-lg-6 auth-panel-left d-none d-lg-flex">
                <div>
                    <h1>TECHBORROW</h1>
                    <p>Borrow, track, return, and manage technical equipment with clarity and accountability.</p>
                </div>
            </div>
            <div class="col-lg-6 auth-panel-right">
                <h2 class="mb-4">Sign In</h2>

                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endforeach; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>

                <div class="mt-3 text-center">
                    <a href="register.php">Create a new account</a>
                </div>
                <div class="mt-2 text-center">
                    <a class="btn btn-outline-primary btn-sm" href="student-borrow.php">Student? Request equipment</a>
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
