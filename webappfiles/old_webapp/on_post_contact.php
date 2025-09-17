<?php
require_once __DIR__.'/db.php';

try {
    $mysqli = db_connect();

    // 1) Insert the message
    $stmt = $mysqli->prepare("INSERT INTO contacts (name, email, message) VALUES (?, ?, ?)");
    if (!$stmt) { throw new RuntimeException("Prepare failed: ".$mysqli->error); }
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $stmt->bind_param('sss', $name, $email, $message);
    if (!$stmt->execute()) { throw new RuntimeException("Execute failed: ".$stmt->error); }
    $contactId = (int)$mysqli->insert_id;
    $stmt->close();

    // 2) Optional single-file upload
    if (!empty($_FILES['attachment']) && (($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE)) {
        $f = $_FILES['attachment'];

        if ($f['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload failed with code '.$f['error']);
        }

        // Size limit (5 MB)
        $maxBytes = 5 * 1024 * 1024;
        if ((int)$f['size'] > $maxBytes) {
            throw new RuntimeException('File too large (max 5 MB).');
        }

        // Detect MIME
        $fi = new finfo(FILEINFO_MIME_TYPE);
        $mime = $fi->file($f['tmp_name']) ?: 'application/octet-stream';

        // Allowlist
        $allowed = [
            'image/png'        => 'png',
            'image/jpeg'       => 'jpg',
            'application/pdf'  => 'pdf',
            'text/plain'       => 'txt',
        ];
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Unsupported file type: '.$mime);
        }
        $ext = $allowed[$mime];

        // Build safe unique path
        $baseName  = bin2hex(random_bytes(8));
        $safeName  = preg_replace('/[^A-Za-z0-9._-]/', '_', $f['name']);
        $relPath   = "attachments/{$contactId}_{$baseName}.{$ext}";
        $absPath   = "/var/www/storage/{$relPath}";

        // Ensure dir exists
        if (!is_dir('/var/www/storage/attachments')) {
            if (!mkdir('/var/www/storage/attachments', 0750, true) && !is_dir('/var/www/storage/attachments')) {
                throw new RuntimeException('Failed to create attachments dir.');
            }
        }

        if (!move_uploaded_file($f['tmp_name'], $absPath)) {
            throw new RuntimeException('Failed to save uploaded file.');
        }

        // Save file record
        $stmt2 = $mysqli->prepare(
            "INSERT INTO contact_attachments (contact_id, file_path, file_name, mime_type, file_size)
             VALUES (?, ?, ?, ?, ?)"
        );
        if (!$stmt2) { throw new RuntimeException("Prepare failed: ".$mysqli->error); }
        $size = (int)$f['size'];
        $stmt2->bind_param('isssi', $contactId, $relPath, $safeName, $mime, $size);
        if (!$stmt2->execute()) { throw new RuntimeException("Execute failed: ".$stmt2->error); }
        $stmt2->close();
    }

    // 3) Back to the form
    header("Location: /contact_form.html", true, 302);
    exit;

} catch (Throwable $e) {
    error_log("contact_form POST error: ".$e->getMessage());
    http_response_code(500);
    echo "There was a problem saving your message.";
}
