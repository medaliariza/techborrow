<?php

function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $requestHost = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
        $isLocal = in_array($requestHost, ['localhost', '127.0.0.1'], true);

        if ($isLocal) {
            $host = '127.0.0.1';
            $db   = 'techborrow_db';
            $user = 'root';
            $pass = '';
        } else {
            $credentialsFile = __DIR__ . '/database.local.php';

            if (!is_file($credentialsFile)) {
                die('Live database credentials are not configured. Create config/database.local.php using the example file.');
            }

            $credentials = require $credentialsFile;
            if (!is_array($credentials)) {
                die('Live database credentials are invalid. Check config/database.local.php.');
            }

            $host = trim((string) ($credentials['host'] ?? ''));
            $db = trim((string) ($credentials['database'] ?? ''));
            $user = trim((string) ($credentials['username'] ?? ''));
            $pass = (string) ($credentials['password'] ?? '');
            if ($host === '' || $db === '' || $user === '' || $pass === '') {
                die('Live database credentials are incomplete. Check config/database.local.php.');
            }
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
