<?php
// download.php — optional controlled download by ID (if you prefer over direct alias links)
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo "Bad Request"; exit; }

$pdo = db();
$stmt = $pdo->prepare("SELECT attachment_path, attachment_name, attachment_mime FROM messages WHERE id = :id");
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();
if (!$row || empty($row['attachment_path'])) { http_response_code(404); echo "Not Found"; exit; }

$path = $row['attachment_path'];
$real = '/var/www/storage' . parse_url($path, PHP_URL_PATH); // normalize path
if (!is_file($real)) { http_response_code(404); echo "Not Found"; exit; }

$mime = $row['attachment_mime'] ?: 'application/octet-stream';
$fname = $row['attachment_name'] ?: basename($real);

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . rawurlencode($fname) . '"');
header('Content-Length: ' . filesize($real));
readfile($real);
