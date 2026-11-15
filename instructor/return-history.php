<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole(['instructor']);

redirect(BASE_URL . '/instructor/my-borrowed-items.php');
