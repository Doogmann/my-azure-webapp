<?php
// database_setup.php — creates the schema if it doesn't exist.
require_once __DIR__ . '/db.php';

$pdo = db();

$pdo->exec("CREATE TABLE IF NOT EXISTS messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    attachment_path VARCHAR(1024) NULL,
    attachment_name VARCHAR(255) NULL,
    attachment_mime VARCHAR(127) NULL,
    attachment_size BIGINT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

echo 'OK';
