<?php

require_once __DIR__ . '/functions.php';

$activePage = $activePage ?? '';
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'TECHBORROW'; ?></title>
    <meta name="theme-color" content="#0d6efd">
    <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.webmanifest">
    <link rel="icon" href="<?php echo BASE_URL; ?>/assets/images/techborrow-icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/techborrow-192.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <script src="<?php echo BASE_URL; ?>/assets/js/pwa.js" defer></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary top-navbar px-3 px-lg-4 py-2">
        <a class="navbar-brand fw-bold" href="<?php echo BASE_URL; ?>/dashboard.php">TECHBORROW</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-lg-auto align-items-lg-center gap-2 top-navbar-actions">
                <li class="nav-item text-light">
                    <span class="fw-semibold top-navbar-user"><?php echo e($user['full_name'] ?? 'User'); ?></span>
                </li>
                <li class="nav-item">
                    <button type="button" class="btn btn-outline-light btn-sm d-none" data-install-app>Install App</button>
                </li>
                <li class="nav-item">
                    <a class="btn btn-light btn-sm" href="<?php echo BASE_URL; ?>/logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/sidebar.php'; ?>
            <main class="col-md-10 px-4 py-4 main-content">
                <?php
                $flash = getFlash();
                if ($flash):
                    ?>
                    <div class="alert alert-<?php echo e($flash['type']); ?> alert-dismissible fade show" role="alert">
                        <?php echo e($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php
                endif;
                ?>
