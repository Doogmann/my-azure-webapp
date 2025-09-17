<?php
// db.php — PDO connection helper for Azure MySQL Flexible Server
// Reads configuration from environment variables set by cloud-init or your shell.
// Required envs: DB_HOST, DB_NAME, DB_USER, DB_PASS
// Optional: DB_SSL_REQUIRED (default "1"), DB_PORT (default 3306)

declare(strict_types=1);

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $host = getenv('DB_HOST') ?: 'localhost';
    $db   = getenv('DB_NAME') ?: 'contact_app';
    $user = getenv('DB_USER') ?: 'mysqladmin';
    $pass = getenv('DB_PASS') ?: '';
    $port = (int)(getenv('DB_PORT') ?: 3306);

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $sslRequired = getenv('DB_SSL_REQUIRED');
    if ($sslRequired === false || $sslRequired === '') { $sslRequired = '1'; } // default on
    if ($sslRequired === '1' || strtolower($sslRequired) === 'true') {
        // Use system CA bundle. Azure uses DigiCert which is in the bundle.
        $ca = '/etc/ssl/certs/ca-certificates.crt';
        if (is_readable($ca)) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
            // Many distros need this to skip hostname validation (private FQDN) on first boot.
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        } else {
            // Fallback to SSL without CA verification
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }
    }

    $pdo = new PDO($dsn, $user, $pass, $options);
    return $pdo;
}
