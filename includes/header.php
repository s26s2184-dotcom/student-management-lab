<?php
/** @var array|null $user Expected to be set by the including page via current_user(). */
$pageTitle = $pageTitle ?? 'Student Management Lab';
$mode = app_mode();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="mode-banner mode-<?= h($mode) ?>">
  LAB MODE: <strong><?= strtoupper(h($mode)) ?></strong>
  &mdash; Educational security testing environment. Local use only.
</div>
<div class="layout">
  <?php if (!empty($user)): ?>
  <aside class="sidebar">
    <div class="brand">🎓 SML</div>
    <nav>
      <a href="dashboard.php">Dashboard</a>
      <a href="students.php">Students</a>
      <a href="announcements.php">Announcements</a>
      <a href="upload.php">Profile Upload</a>
      <?php if (($user['role'] ?? '') === 'admin'): ?>
      <a href="admin_users.php">User Management</a>
      <?php endif; ?>
    </nav>
  </aside>
  <?php endif; ?>
  <div class="main">
    <?php if (!empty($user)): ?>
    <header class="topbar">
      <div class="topbar-title"><?= h($pageTitle) ?></div>
      <div class="topbar-user">
        Signed in as <strong><?= h($user['username']) ?></strong> (<?= h($user['role']) ?>)
        &nbsp;|&nbsp; <a href="logout.php">Logout</a>
      </div>
    </header>
    <?php endif; ?>
    <main class="content">
