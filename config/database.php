<?php
// Database connection settings.
// Reads cloud environment variables (Render, Railway, InfinityFree, etc.)
// with default fallbacks for a fresh local XAMPP install.

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'skillswap');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            // TiDB Cloud requires SSL — use system CA certs in Docker
            if (strpos(DB_HOST, 'tidbcloud.com') !== false) {
                $caCert = '/etc/ssl/certs/ca-certificates.crt';
                if (file_exists($caCert)) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = $caCert;
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
                }
            }

            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                $options
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}
