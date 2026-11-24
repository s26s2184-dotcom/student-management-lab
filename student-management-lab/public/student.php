<?php
require_once __DIR__ . '/../includes/auth.php';
start_app_session();
$user = require_login();

$idRaw = (string)($_GET['id'] ?? '');
$student = null;
$error = null;

if (is_vulnerable_mode()) {
    require_once __DIR__ . '/../lab/vulnerable/student_details.php';
    // Note: intentionally no (int) cast here, and no ownership check -
    // that IS the vulnerability (SQLi + IDOR). See lab/vulnerable/student_details.php
    $student = vulnerable_get_student($idRaw);
    if (is_array($student) && isset($student['__error__'])) {
        $error = $student['__error__'];
        $student = null;
    }
} else {
    require_once __DIR__ . '/../lab/secure/student_details.php';
    $id = (int)$idRaw;
    $result = secure_get_student($id, $user);
    if (!$result['allowed']) {
        http_response_code(403);
        $pageTitle = 'Forbidden';
        require __DIR__ . '/../includes/header.php';
        echo '<div class="alert alert-error">You are not authorized to view this student profile.</div>';
        require __DIR__ . '/../includes/footer.php';
        exit;
    }
    $student = $result['student'];
}

$pageTitle = 'Student Details';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <?php if ($error): ?>
    <div class="alert alert-error">DB error: <?= $error ?></div>
  <?php elseif (!$student): ?>
    <div class="alert alert-info">Student not found.</div>
  <?php else: ?>
    <h2><?= h($student['full_name']) ?> <span class="badge badge-student"><?= h($student['student_code']) ?></span></h2>
    <table>
      <tr><th>Username</th><td><?= h($student['username'] ?? 'N/A') ?></td></tr>
      <tr><th>Email</th><td><?= h($student['email'] ?? 'N/A') ?></td></tr>
      <tr><th>Date of Birth</th><td><?= h((string)($student['date_of_birth'] ?? '')) ?></td></tr>
      <tr><th>Gender</th><td><?= h($student['gender'] ?? '') ?></td></tr>
      <tr><th>Phone</th><td><?= h($student['phone'] ?? '') ?></td></tr>
      <tr><th>Address</th><td><?= h($student['address'] ?? '') ?></td></tr>
      <tr><th>Major</th><td><?= h($student['major'] ?? '') ?></td></tr>
      <tr><th>Enrollment Year</th><td><?= h((string)($student['enrollment_year'] ?? '')) ?></td></tr>
      <tr><th>GPA</th><td><?= h((string)($student['gpa'] ?? '')) ?></td></tr>
    </table>
    <p><a href="edit_student.php?id=<?= (int)($student['id'] ?? 0) ?>" class="btn">Edit this profile</a></p>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
