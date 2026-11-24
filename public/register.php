<?php
require_once __DIR__ . '/../includes/auth.php';
start_app_session();

if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $fullName = trim((string)($_POST['full_name'] ?? ''));

    if ($username === '' || $password === '' || $fullName === '') {
        $error = 'All fields are required.';
    } elseif (!preg_match('/^[A-Za-z0-9_.]{3,32}$/', $username)) {
        $error = 'Username must be 3-32 chars: letters, numbers, underscore, dot.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $pdo = get_pdo();

        // Registration always uses prepared statements + password_hash():
        // this flow is not one of the lab's intentional vulnerability
        // targets in either mode.
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :u');
        $stmt->execute([':u' => $username]);
        if ($stmt->fetch()) {
            $error = 'That username is already taken.';
        } else {
            $pdo->beginTransaction();
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    'INSERT INTO users (username, password_hash, role, email) VALUES (:u, :p, "student", :e)'
                );
                $stmt->execute([':u' => $username, ':p' => $hash, ':e' => $email]);
                $userId = (int)$pdo->lastInsertId();

                $code = 'STU-' . (2000 + $userId);
                $stmt = $pdo->prepare(
                    'INSERT INTO students (user_id, student_code, full_name, enrollment_year) VALUES (:uid, :code, :name, :year)'
                );
                $stmt->execute([
                    ':uid' => $userId,
                    ':code' => $code,
                    ':name' => $fullName,
                    ':year' => (int)date('Y'),
                ]);

                $pdo->commit();
                log_audit($userId, 'register', 'New student self-registered');
                $success = 'Account created! You can now log in.';
            } catch (Throwable $e) {
                $pdo->rollBack();
                $error = 'Registration failed. Please try again.';
                error_log('register error: ' . $e->getMessage());
            }
        }
    }
}

$pageTitle = 'Register - Student Management Lab';
$user = null;
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
  <h1>Create a Student Account</h1>

  <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?> <a href="login.php">Go to login</a></div><?php endif; ?>

  <?php if (!$success): ?>
  <form method="post" action="register.php">
    <div class="field">
      <label for="full_name">Full name</label>
      <input type="text" id="full_name" name="full_name" required>
    </div>
    <div class="field">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required>
    </div>
    <div class="field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email">
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
    </div>
    <button type="submit">Register</button>
  </form>
  <?php endif; ?>

  <p class="helper-text">Already have an account? <a href="login.php">Login</a></p>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
