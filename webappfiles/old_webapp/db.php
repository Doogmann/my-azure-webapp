<?php
// db.php — mysqli connector for Azure MySQL Flexible Server (SSL, utf8mb4, strict mode)
function db_connect() {
    // Read /etc/webapp/mysql.env without auto-casting
    $env = parse_ini_file("/etc/webapp/mysql.env", false, INI_SCANNER_RAW);
    if (!$env) {
        die("Cannot read /etc/webapp/mysql.env");
    }

    // Support either DB_* or MYSQL_* names
    $host = $env['DB_HOST']    ?? $env['MYSQL_HOST'] ?? '';
    $db   = $env['DB_NAME']    ?? $env['MYSQL_DB']   ?? '';
    $user = $env['DB_USER']    ?? $env['MYSQL_USER'] ?? '';
    $pass = $env['DB_PASS']    ?? $env['MYSQL_PASS'] ?? '';

    if ($host === '' || $db === '' || $user === '') {
        die("Missing DB settings in /etc/webapp/mysql.env");
    }

    // Safety: strip any brackets or inline port if present
    $host = preg_replace('/^\[|\]$/', '', $host);
    $host = preg_replace('/:3306$/', '', $host);

    $mysqli = mysqli_init();
    if (!$mysqli) {
        die("mysqli_init() failed");
    }

    // Use system CA bundle; Azure requires SSL
    mysqli_ssl_set(
        $mysqli,
        null, // key
        null, // cert
        '/etc/ssl/certs/ca-certificates.crt', // CA bundle
        null, // capath
        null  // cipher
    );

    // Optional: native ints/floats
    @mysqli_options($mysqli, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);

    $flags = MYSQLI_CLIENT_SSL; // do NOT append host:port; pass port separately
    if (!mysqli_real_connect($mysqli, $host, $user, $pass, $db, 3306, null, $flags)) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    $mysqli->set_charset('utf8mb4');
    $mysqli->query("SET sql_mode = 'STRICT_ALL_TABLES'");
    return $mysqli;
}
