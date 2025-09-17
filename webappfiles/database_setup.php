<?php
// database_setup.php — initialize DB schema using values from /etc/webapp/mysql.env
// Works with either MYSQL_* or DB_* keys

function pdo_db_from_env() {
    $env = parse_ini_file("/etc/webapp/mysql.env", false, INI_SCANNER_RAW);
    if (!$env) {
        die("Cannot read /etc/webapp/mysql.env");
    }

    // Support both naming schemes
    $host = $env['MYSQL_HOST'] ?? $env['DB_HOST'] ?? '';
    $db   = $env['MYSQL_DB']   ?? $env['DB_NAME'] ?? '';
    $user = $env['MYSQL_USER'] ?? $env['DB_USER'] ?? '';
    $pass = $env['MYSQL_PASS'] ?? $env['DB_PASS'] ?? '';

    if ($host === '' || $db === '' || $user === '') {
        die("Missing DB settings in /etc/webapp/mysql.env");
    }

    // Safety: remove any accidental [] or inline :3306
    $host = preg_replace('/^\[|\]$/', '', $host);
    $host = preg_replace('/:3306$/', '', $host);

    $dsn  = "mysql:host={$host};dbname={$db};charset=utf8mb4;port=3306";
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Azure Flexible Server requires SSL; use system CA bundle
        PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ];
    return new PDO($dsn, $user, $pass, $opts);
}

try {
    $pdo = pdo_db_from_env();

    // Create the messages table used by the app
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            email VARCHAR(320) NOT NULL,
            message TEXT NOT NULL,
            attachment_blob VARCHAR(512) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo \"OK\\n\";
} catch (Throwable $e) {
    error_log('DB setup failed: '.$e->getMessage());
    die('Database connection failed. Please check configuration.');
}