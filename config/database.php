<?php

function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        // Check if running locally on XAMPP or live on InfinityFree
        $isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']);

        if ($isLocal) {
            // Local XAMPP settings
            $host = '127.0.0.1';
            $db   = 'techborrow_db';
            $user = 'root';
            $pass = '';
        } else {
            // Live InfinityFree settings (Replace with your exact details)
            $host = 'sql200.infinityfree.com'; // Your MySQL Host Name
            $db   = 'if0_43446197_techborrow_db'; // Your MySQL DB Name
            $user = 'if0_43446197';            // Your MySQL User Name
            $pass = 'techborrow2026';   // Your InfinityFree Account Password
        }

        $charset = 'utf8mb4';
        $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            die('Database connection failed. Please create the database and import the SQL file from the database folder.');
        }
    }

    return $pdo;
}
