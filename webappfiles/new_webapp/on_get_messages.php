<?php
// on_get_messages.php — lists messages with attachment links and thumbnails
declare(strict_types=1);
require_once __DIR__ . '/db.php';

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

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
)");

$messages = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All Messages - Azure MySQL Contact App</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <header><h1>🗒️ All Messages</h1></header>
    <nav>
      <a class="btn" href="index.html">Home</a>
      <a class="btn" href="contact_form.html">Contact Form</a>
      <a class="btn active" href="on_get_messages.php">View Messages</a>
    </nav>

    <div class="messages-count">Total messages: <?php echo count($messages); ?></div>

    <?php foreach ($messages as $m): ?>
      <section class="message-item">
        <div class="message-header">
          <h3><?php echo h($m['name']); ?></h3>
          <div class="message-date"><?php echo h($m['created_at']); ?></div>
        </div>
        <div class="message-email">
          <a href="mailto:<?php echo h($m['email']); ?>"><?php echo h($m['email']); ?></a>
        </div>
        <div class="message-content">
          <p><?php echo nl2br(h($m['message'])); ?></p>
        </div>

        <?php if (!empty($m['attachment_path'])): ?>
          <div class="attachments">
            <strong>Attachment</strong>
            <div class="attachment-item">
              <?php if (strpos((string)$m['attachment_mime'], 'image/') === 0): ?>
                <img src="<?php echo h($m['attachment_path']); ?>" alt="attachment" style="max-width:300px;display:block;margin:8px 0;border-radius:6px;border:1px solid #ddd">
              <?php endif; ?>
              <a href="<?php echo h($m['attachment_path']); ?>" download><?php echo h($m['attachment_name'] ?: 'Download'); ?></a>
              <?php if (!empty($m['attachment_size'])): ?>
                <small>(<?php echo number_format((int)$m['attachment_size'] / 1024, 1); ?> KB)</small>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>
  </div>
</body>
</html>
