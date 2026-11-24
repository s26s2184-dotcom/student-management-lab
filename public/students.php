<?php
require_once __DIR__ . '/../includes/auth.php';
start_app_session();
$user = require_login();
$pdo = get_pdo();

$q = (string)($_GET['q'] ?? '');
$results = null;
$searchError = null;

if ($q !== '') {
    if (is_vulnerable_mode()) {
        require_once __DIR__ . '/../lab/vulnerable/search.php';
        $results = vulnerable_student_search($q);
        if (isset($results['__error__'])) {
            $searchError = $results['__error__'];
            $results = [];
        }
    } else {
        require_once __DIR__ . '/../lab/secure/search.php';
        $results = secure_student_search($q);
    }
} else {
    // Default listing: always via a prepared statement (not a vuln target).
    $results = $pdo->query('SELECT id, student_code, full_name, major, enrollment_year FROM students ORDER BY id')->fetchAll();
}

$pageTitle = 'Students';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <form class="search-bar" method="get" action="students.php">
    <input type="search" name="q" placeholder="Search by name or student code..."
           value="<?= is_vulnerable_mode() ? $q : h($q) ?>">
    <button type="submit">Search</button>
  </form>

  <?php if (is_vulnerable_mode()): ?>
    <p class="helper-text">
      You searched for: <?= $q /* VULNERABLE: intentionally NOT escaped — reflected XSS demo */ ?>
    </p>
    <?php if ($searchError): ?>
      <div class="alert alert-error">DB error: <?= $searchError /* intentionally unescaped for error-based SQLi demo */ ?></div>
    <?php endif; ?>
  <?php elseif ($q !== ''): ?>
    <p class="helper-text">You searched for: <?= h($q) ?></p>
  <?php endif; ?>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Student Code</th><th>Name</th><th>Major</th><th>Enrollment Year</th><th></th></tr></thead>
      <tbody>
        <?php foreach (($results ?: []) as $s): ?>
        <tr>
          <td><?= h($s['student_code']) ?></td>
          <td><?= h($s['full_name']) ?></td>
          <td><?= h($s['major'] ?? '') ?></td>
          <td><?= h((string)($s['enrollment_year'] ?? '')) ?></td>
          <td><a href="student.php?id=<?= (int)$s['id'] ?>">View</a> | <a href="edit_student.php?id=<?= (int)$s['id'] ?>">Edit</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$results): ?><tr><td colspan="5">No students found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
