<?php
require_once __DIR__ . '/../includes/auth.php';
start_app_session();
$user = require_login();
$pdo = get_pdo();

$studentCount = (int)$pdo->query('SELECT COUNT(*) AS c FROM students')->fetch()['c'];
$courseCount = (int)$pdo->query('SELECT COUNT(*) AS c FROM courses')->fetch()['c'];
$announcementCount = (int)$pdo->query('SELECT COUNT(*) AS c FROM announcements')->fetch()['c'];

$recent = $pdo->query(
    'SELECT s.full_name, s.student_code, s.enrollment_year, s.created_at
     FROM students s ORDER BY s.created_at DESC, s.id DESC LIMIT 5'
)->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="stats-grid">
  <div class="stat-card"><div class="num"><?= $studentCount ?></div><div class="label">Registered Students</div></div>
  <div class="stat-card"><div class="num"><?= $courseCount ?></div><div class="label">Courses</div></div>
  <div class="stat-card"><div class="num"><?= $announcementCount ?></div><div class="label">Announcements</div></div>
</div>

<div class="card">
  <h2>Recent Student Registrations</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Student Code</th><th>Enrollment Year</th><th>Registered</th></tr></thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
        <tr>
          <td><?= h($r['full_name']) ?></td>
          <td><?= h($r['student_code']) ?></td>
          <td><?= h((string)$r['enrollment_year']) ?></td>
          <td><?= h($r['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$recent): ?><tr><td colspan="4">No students yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
