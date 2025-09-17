<?php
// on_post_contact.php — handles form submission and saves to DB + disk.
declare(strict_types=1);
require_once __DIR__ . '/db.php';

// Ensure DB exists
require_once __DIR__ . '/database_setup.php';

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

$errors = [];
if ($name === '')   $errors[] = 'Name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
if ($message === '') $errors[] = 'Message is required.';

$attachmentPath = null;
$attachmentName = null;
$attachmentMime = null;
$attachmentSize = null;

$uploadRoot = '/var/www/storage/attachments';
@mkdir($uploadRoot, 0775, true);

if (!empty($_FILES['attachment']['name'])) {
    $f = $_FILES['attachment'];
    if ($f['error'] === UPLOAD_ERR_OK) {
        $attachmentSize = (int)$f['size'];
        // 10MB limit by default
        if ($attachmentSize > 10 * 1024 * 1024) {
            $errors[] = 'Attachment is larger than 10 MB.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($f['tmp_name']) ?: 'application/octet-stream';
            $allowed = [
                'image/jpeg', 'image/png', 'image/gif',
                'application/pdf', 'text/plain'
            ];
            if (!in_array($mime, $allowed, true)) {
                $errors[] = 'Unsupported file type.';
            } else {
                $subdir = date('Y/m');
                $dir = rtrim($uploadRoot, '/') . '/' . $subdir;
                if (!is_dir($dir)) { @mkdir($dir, 0775, true); }

                $rand = bin2hex(random_bytes(16));
                $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
                $safeExt = preg_replace('/[^A-Za-z0-9.]+/', '', $ext);
                $filename = "{$rand}" . ($safeExt ? ".{$safeExt}" : '');
                $dest = $dir . '/' . $filename;

                if (!move_uploaded_file($f['tmp_name'], $dest)) {
                    $errors[] = 'Failed to store the uploaded file.';
                } else {
                    $attachmentPath = "/attachments/{$subdir}/{$filename}"; // served by Nginx alias
                    $attachmentName = basename($f['name']);
                    $attachmentMime = $mime;
                    @chmod($dest, 0664);
                }
            }
        }
    } elseif ($f['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Upload error code: ' . (int)$f['error'];
    }
}

if ($errors) {
    http_response_code(422);
    echo '<link rel="stylesheet" href="style.css" />';
    echo '<div class="container"><div class="error-message"><h3>Form errors</h3><ul>';
    foreach ($errors as $e) echo '<li>' . h($e) . '</li>';
    echo '</ul><div class="actions"><a class="btn" href="contact_form.html">Back</a></div></div></div>';
    exit;
}

$pdo = db();
$stmt = $pdo->prepare("INSERT INTO messages (name, email, message, attachment_path, attachment_name, attachment_mime, attachment_size)
VALUES (:name, :email, :message, :path, :aname, :mime, :size)");

$stmt->execute([
    ':name' => $name,
    ':email' => $email,
    ':message' => $message,
    ':path' => $attachmentPath,
    ':aname' => $attachmentName,
    ':mime' => $attachmentMime,
    ':size' => $attachmentSize,
]);

header('Location: on_get_messages.php');
exit;
