<?php
require_once __DIR__ . '/db.php';

function human_filesize($bytes, $dec = 1) {
  $sz = ['B','KB','MB','GB','TB']; $factor = floor((strlen((string)$bytes)-1)/3);
  return sprintf("%.{$dec}f", $bytes / pow(1024, $factor)) . " " . $sz[$factor];
}

try {
  $mysqli = db_connect();

  $sql = "
    SELECT
      c.id, c.name, c.email, c.message, c.created_at,
      a.id AS att_id, a.file_path, a.file_name, a.mime_type, a.file_size
    FROM contacts c
    LEFT JOIN contact_attachments a ON a.contact_id = c.id
    ORDER BY c.created_at DESC, a.id ASC
  ";
  $res = $mysqli->query($sql);
  if (!$res) { throw new RuntimeException('Query failed: '.$mysqli->error); }

  // Group rows by contact and collect attachments
  $messages = [];
  while ($r = $res->fetch_assoc()) {
    $id = (int)$r['id'];
    if (!isset($messages[$id])) {
      $messages[$id] = [
        'id'         => $id,
        'name'       => $r['name'],
        'email'      => $r['email'],
        'message'    => $r['message'],
        'created_at' => $r['created_at'],
        'attachments'=> [],
      ];
    }
    if (!empty($r['att_id'])) {
      $messages[$id]['attachments'][] = [
        'file_path' => $r['file_path'],   // e.g. attachments/123_xxx.jpg
        'file_name' => $r['file_name'],   // original filename
        'mime_type' => $r['mime_type'],
        'file_size' => (int)$r['file_size'],
      ];
    }
  }
  $res->free();
  // Reindex to a flat list
  $messages = array_values($messages);

} catch (Throwable $e) {
  error_log("on_get_messages error: ".$e->getMessage());
  $error = "Error retrieving messages. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>All Messages - Azure MySQL Contact App</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
<div class="container">
  <header><h1>📋 All Messages</h1></header>

  <nav>
    <a href="index.html" class="btn">Home</a>
    <a href="contact_form.html" class="btn">Contact Form</a>
    <a href="on_get_messages.php" class="btn active">View Messages</a>
  </nav>

  <main>
    <?php if (!empty($error)): ?>
      <div class="error-message">
        <h2>❌ Error</h2>
        <p><?= htmlspecialchars($error) ?></p>
      </div>
    <?php elseif (empty($messages)): ?>
      <div class="info-message">
        <h2>📭 No Messages Yet</h2>
        <p>No messages have been submitted yet.</p>
        <a href="contact_form.html" class="btn">Send First Message</a>
      </div>
    <?php else: ?>
      <div class="messages-count">
        <p>Total messages: <strong><?= count($messages) ?></strong></p>
      </div>

      <div class="messages-list">
        <?php foreach ($messages as $m): ?>
          <div class="message-item">
            <div class="message-header">
              <h3><?= htmlspecialchars($m['name']) ?></h3>
              <span class="message-date"><?= htmlspecialchars($m['created_at']) ?></span>
            </div>
            <p class="message-email">
              📧 <a href="mailto:<?= htmlspecialchars($m['email']) ?>">
                <?= htmlspecialchars($m['email']) ?>
              </a>
            </p>
            <div class="message-content">
              <p><?= nl2br(htmlspecialchars($m['message'])) ?></p>
            </div>

            <?php if (!empty($m['attachments'])): ?>
              <div class="attachments">
                <strong>Attachments</strong>
                <ul class="attachment-list">
                  <?php foreach ($m['attachments'] as $att):
                        $href = '/' . ltrim($att['file_path'], '/'); // served via Nginx alias
                        $isImg = (strpos($att['mime_type'], 'image/') === 0);
                  ?>
                    <li class="attachment-item">
                      <?php if ($isImg): ?>
                        <a href="<?= htmlspecialchars($href) ?>" target="_blank" rel="noopener">
                          <img class="thumb"
                               src="<?= htmlspecialchars($href) ?>"
                               alt="<?= htmlspecialchars($att['file_name']) ?>"
                               style="max-width:140px;max-height:140px;display:block;border-radius:6px;margin-bottom:6px;">
                        </a>
                      <?php endif; ?>

                      <a href="<?= htmlspecialchars($href) ?>" download>
                        <?= htmlspecialchars($att['file_name']) ?>
                      </a>
                      <span class="meta">
                        (<?= htmlspecialchars($att['mime_type']) ?>,
                        <?= human_filesize($att['file_size']) ?>)
                      </span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</div>
</body>
</html>
