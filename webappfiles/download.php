<?php
require_once __DIR__.'/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); exit('Bad request'); }

try {
  $pdo = db_connect();
  $stmt = $pdo->prepare("SELECT file_path, file_name, mime_type FROM contact_attachments WHERE id = ?");
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  if (!$row) { http_response_code(404); exit('Not found'); }

  $abs = "/var/www/storage/".$row['file_path'];
  if (!is_file($abs) || !is_readable($abs)) { http_response_code(404); exit('File missing'); }

  header('Content-Type: '.$row['mime_type']);
  header('Content-Disposition: attachment; filename="'.basename($row['file_name']).'"');
  header('Content-Length: '.filesize($abs));
  readfile($abs);
} catch (Throwable $e) {
  error_log("download error: ".$e->getMessage());
  http_response_code(500);
  echo "Server error.";
}
