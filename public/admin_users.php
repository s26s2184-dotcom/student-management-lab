<?php
require_once __DIR__ . '/../includes/auth.php';
start_app_session();
$user = require_admin();
$pdo = get_pdo();

$error = null;
$success = null;

// Toggle active/inactive - always via prepared statements (admin
// management is not one of the lab's intentional vulnerability targets).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_user_id'])) {
    $targetId = (int)$_POST['toggle_user_id'];
    if ($targetId === (int)$user['id']) {
        $error = "You can't deactivate your own account.";
    } else {
        $stmt = $pdo->prepare('UPDATE users SET is_active = NOT is_active WHERE id = ?');
        $stmt->execute([$targetId]);
        $success = 'User status updated.';
        log_audit($user['id'], 'toggle_user_active', "target user #{$targetId}");
    }
}

$users = $pdo->query(
    'SELECT u.id, u.username, u.role, u.email, u.is_active, u.created_at, s.full_name
     FROM users u LEFT JOIN students s ON s.user_id = u.id
     ORDER BY u.id'
)->fetchAll();

$pageTitle = 'User Management';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <h2>Registered Users</h2>

  <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>

  <div class="table-wrap">
    <table>
      <thead><tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Email</th><th>Status</th><th>Joined</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><?= (int)$u['id'] ?></td>
          <td><?= h($u['username']) ?></td>
          <td><?= h($u['full_name'] ?? '-') ?></td>
          <td><span class="badge badge-<?= h($u['role']) ?>"><?= h($u['role']) ?></span></td>
          <td><?= h($u['email'] ?? '') ?></td>
          <td><?= $u['is_active'] ? 'Active' : 'Inactive' ?></td>
          <td><?= h($u['created_at']) ?></td>
          <td>
            <form method="post" action="admin_users.php" style="display:inline">
              <input type="hidden" name="toggle_user_id" value="<?= (int)$u['id'] ?>">
              <button type="submit" class="btn-secondary"><?= $u['is_active'] ? 'Deactivate' : 'Activate' ?></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
