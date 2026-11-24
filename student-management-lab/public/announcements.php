<?php
require_once __DIR__ . '/../includes/auth.php';
start_app_session();
$user = require_login();
$pdo = get_pdo();

if (is_vulnerable_mode()) {
    require_once __DIR__ . '/../lab/vulnerable/announcements.php';
} else {
    require_once __DIR__ . '/../lab/secure/announcements.php';
}

$error = null;

// Create a new announcement (admin only) - uses prepared statements in
// both modes; content is stored as-is either way (the vulnerability is
// in how it's rendered, not how it's written).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'new_announcement') {
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        die('Only admins can post announcements.');
    }
    $title = trim((string)($_POST['title'] ?? ''));
    $content = (string)($_POST['content'] ?? '');
    if ($title !== '' && $content !== '') {
        $stmt = $pdo->prepare('INSERT INTO announcements (user_id, title, content) VALUES (?, ?, ?)');
        $stmt->execute([$user['id'], $title, $content]);
        log_audit($user['id'], 'create_announcement', $title);
    } else {
        $error = 'Title and content are required.';
    }
}

// Post a comment - stored as-is in both modes too.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'new_comment') {
    $announcementId = (int)($_POST['announcement_id'] ?? 0);
    $commentContent = (string)($_POST['comment'] ?? '');
    if ($announcementId > 0 && $commentContent !== '') {
        $stmt = $pdo->prepare('INSERT INTO comments (announcement_id, user_id, content) VALUES (?, ?, ?)');
        $stmt->execute([$announcementId, $user['id'], $commentContent]);
    }
}

$announcements = $pdo->query(
    'SELECT a.*, u.username FROM announcements a JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC'
)->fetchAll();

$commentsByAnnouncement = [];
$allComments = $pdo->query(
    'SELECT c.*, u.username FROM comments c JOIN users u ON u.id = c.user_id ORDER BY c.created_at ASC'
)->fetchAll();
foreach ($allComments as $c) {
    $commentsByAnnouncement[$c['announcement_id']][] = $c;
}

$pageTitle = 'Announcements';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>

<?php if ($user['role'] === 'admin'): ?>
<div class="card">
  <h2>New Announcement</h2>
  <form method="post" action="announcements.php">
    <input type="hidden" name="action" value="new_announcement">
    <div class="field">
      <label>Title</label>
      <input type="text" name="title" required>
    </div>
    <div class="field">
      <label>Content</label>
      <textarea name="content" required></textarea>
    </div>
    <button type="submit">Post Announcement</button>
  </form>
</div>
<?php endif; ?>

<div class="card">
  <?php foreach ($announcements as $a): ?>
    <div class="announcement">
      <h3><?= h($a['title']) ?></h3>
      <div class="meta">by <?= h($a['username']) ?> on <?= h($a['created_at']) ?></div>
      <div class="content"><?= render_announcement_output($a['content']) ?></div>

      <?php foreach (($commentsByAnnouncement[$a['id']] ?? []) as $c): ?>
        <div class="comment">
          <strong><?= h($c['username']) ?>:</strong>
          <?= render_announcement_output($c['content']) ?>
        </div>
      <?php endforeach; ?>

      <form method="post" action="announcements.php#a<?= (int)$a['id'] ?>" style="margin-top:10px;">
        <input type="hidden" name="action" value="new_comment">
        <input type="hidden" name="announcement_id" value="<?= (int)$a['id'] ?>">
        <div class="field">
          <input type="text" name="comment" placeholder="Write a comment..." required>
        </div>
        <button type="submit">Comment</button>
      </form>
    </div>
  <?php endforeach; ?>
  <?php if (!$announcements): ?><p>No announcements yet.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
