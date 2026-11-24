<?php
require_once __DIR__ . '/../includes/auth.php';
start_app_session();
$user = require_login();
$pdo = get_pdo();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    if (is_vulnerable_mode()) {
        require_once __DIR__ . '/../lab/vulnerable/upload.php';
        $result = vulnerable_handle_upload($_FILES['profile_image'], $user['id']);
    } else {
        require_once __DIR__ . '/../lab/secure/upload.php';
        $result = secure_handle_upload($_FILES['profile_image'], $user['id']);
    }

    if ($result['success']) {
        $success = 'Profile image uploaded.';
        log_audit($user['id'], 'upload', $result['path'] ?? ($result['stored_filename'] ?? ''));
    } else {
        $error = $result['error'];
    }
}

$stmt = $pdo->prepare('SELECT profile_image FROM students WHERE user_id = ?');
$stmt->execute([$user['id']]);
$row = $stmt->fetch();
$profileImage = $row['profile_image'] ?? null;

$pageTitle = 'Profile Upload';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <h2>Upload Profile Image</h2>

  <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>

  <?php if ($profileImage): ?>
    <p>
      <?php if (is_vulnerable_mode()): ?>
        <img class="profile-pic" src="<?= h($profileImage) ?>" alt="Profile image">
      <?php else: ?>
        <img class="profile-pic" src="serve_upload.php?file=<?= urlencode($profileImage) ?>" alt="Profile image">
      <?php endif; ?>
    </p>
  <?php endif; ?>

  <form method="post" action="upload.php" enctype="multipart/form-data">
    <div class="field">
      <label>Choose an image</label>
      <input type="file" name="profile_image" required>
    </div>
    <button type="submit">Upload</button>
  </form>

  <?php if (is_vulnerable_mode()): ?>
    <p class="helper-text">
      Vulnerable mode: only a weak extension blocklist is enforced, no MIME
      check, and the original filename is kept. See docs/vulnerabilities.md.
      (Note: Apache is configured to never execute scripts from this
      directory, so this cannot be used to obtain remote code execution.)
    </p>
  <?php else: ?>
    <p class="helper-text">
      Secure mode: allowlist of image extensions, finfo MIME verification,
      2MB size limit, random filename, stored outside the web root.
    </p>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
