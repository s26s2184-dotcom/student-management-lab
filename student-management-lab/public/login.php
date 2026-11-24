<?php
require_once __DIR__ . '/../includes/auth.php';
start_app_session();

if (is_vulnerable_mode()) {
    require_once __DIR__ . '/../lab/vulnerable/login.php';
} else {
    require_once __DIR__ . '/../lab/secure/login.php';
}

if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

$error = null;
$lookupResults = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = (string)($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    $result = attempt_login($username, $password);

    if ($result['success']) {
        $u = $result['user'];
        $_SESSION['user'] = [
            'id' => (int)$u['id'],
            'username' => $u['username'],
            'role' => $u['role'],
        ];
        log_audit((int)$u['id'], 'login_success', 'User logged in (' . app_mode() . ' mode)');
        header('Location: dashboard.php');
        exit;
    }

    $error = $result['error'];
    log_audit(null, 'login_failed', 'Attempt for username: ' . $username);
}

// Vulnerable-mode-only helper endpoint used for the SQLi/sqlmap demo:
// GET/POST login.php?check_user=<name>
if (is_vulnerable_mode() && isset($_REQUEST['check_user'])) {
    $lookupResults = vulnerable_user_lookup((string)$_REQUEST['check_user']);
}

$pageTitle = 'Login - Student Management Lab';
$user = null; // no sidebar on the login page
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
  <h1>Student Management Lab</h1>
  <p class="helper-text">Sign in to continue. (<?= h(app_mode()) ?> mode)</p>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= is_vulnerable_mode() ? $error : h($error) ?></div>
  <?php endif; ?>

  <form method="post" action="login.php">
    <div class="field">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" autocomplete="username" required>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" autocomplete="current-password" required>
    </div>
    <button type="submit">Login</button>
  </form>

  <p class="helper-text">No account? <a href="register.php">Register as a student</a></p>

  <?php if (is_vulnerable_mode()): ?>
    <hr>
    <p class="helper-text">
      Seeded demo accounts (intentionally weak — see docs/vulnerabilities.md):<br>
      admin / Admin@123 &nbsp;|&nbsp; jsmith / password123 &nbsp;|&nbsp; mwong / letmein
    </p>
    <?php if ($lookupResults !== null): ?>
      <div class="card">
        <strong>check_user lookup results:</strong>
        <pre><?php
          // Intentionally unescaped in vulnerable mode to keep the
          // raw-query demo path consistent; data is from our own seed DB.
          print_r($lookupResults);
        ?></pre>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
