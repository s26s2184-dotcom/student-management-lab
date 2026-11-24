<?php
require_once __DIR__ . '/../includes/auth.php';
start_app_session();
$user = require_login();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$error = null;
$success = null;
$student = null;

if (is_vulnerable_mode()) {
    require_once __DIR__ . '/../lab/vulnerable/edit_student.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        vulnerable_save_student($id, [
            'full_name' => (string)($_POST['full_name'] ?? ''),
            'phone' => (string)($_POST['phone'] ?? ''),
            'address' => (string)($_POST['address'] ?? ''),
            'major' => (string)($_POST['major'] ?? ''),
            'gpa' => (float)($_POST['gpa'] ?? 0),
        ]);
        $success = 'Profile updated.';
    }
    $student = vulnerable_load_student_for_edit($id);
} else {
    require_once __DIR__ . '/../lab/secure/edit_student.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ok = secure_save_student($id, [
            'full_name' => (string)($_POST['full_name'] ?? ''),
            'phone' => (string)($_POST['phone'] ?? ''),
            'address' => (string)($_POST['address'] ?? ''),
            'major' => (string)($_POST['major'] ?? ''),
            'gpa' => (float)($_POST['gpa'] ?? 0),
        ], $user);
        if (!$ok) {
            http_response_code(403);
            $pageTitle = 'Forbidden';
            require __DIR__ . '/../includes/header.php';
            echo '<div class="alert alert-error">You are not authorized to edit this student profile.</div>';
            require __DIR__ . '/../includes/footer.php';
            exit;
        }
        $success = 'Profile updated.';
    }

    $result = secure_load_student_for_edit($id, $user);
    if (!$result['allowed']) {
        http_response_code(403);
        $pageTitle = 'Forbidden';
        require __DIR__ . '/../includes/header.php';
        echo '<div class="alert alert-error">You are not authorized to edit this student profile.</div>';
        require __DIR__ . '/../includes/footer.php';
        exit;
    }
    $student = $result['student'];
}

$pageTitle = 'Edit Student';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>
  <?php if (!$student): ?>
    <div class="alert alert-info">Student not found.</div>
  <?php else: ?>
    <form method="post" action="edit_student.php?id=<?= (int)$id ?>">
      <input type="hidden" name="id" value="<?= (int)$id ?>">
      <div class="field">
        <label>Full name</label>
        <input type="text" name="full_name" value="<?= h($student['full_name'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Phone</label>
        <input type="text" name="phone" value="<?= h($student['phone'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Address</label>
        <input type="text" name="address" value="<?= h($student['address'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Major</label>
        <input type="text" name="major" value="<?= h($student['major'] ?? '') ?>">
      </div>
      <div class="field">
        <label>GPA</label>
        <input type="number" step="0.01" min="0" max="4" name="gpa" value="<?= h((string)($student['gpa'] ?? '')) ?>">
      </div>
      <button type="submit">Save</button>
    </form>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
