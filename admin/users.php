<?php
require_once __DIR__ . '/../app/helpers.php';
require_role(['admin']);
csrf_check();

$meta = seo_meta('Users');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $uid = (int)($_POST['user_id'] ?? 0);
  $role = $_POST['role'] ?? 'customer';
  if (!in_array($role, ['admin','employee','customer'], true)) $role = 'customer';
  $dept = trim($_POST['department'] ?? '');

  $pdo->prepare("UPDATE users SET role=? WHERE user_id=?")->execute([$role, $uid]);

  // If employee, upsert into employees table
  if ($role === 'employee') {
    $stmt = $pdo->prepare("SELECT employee_id FROM employees WHERE user_id=?");
    $stmt->execute([$uid]);
    if ($stmt->fetch()) {
      $pdo->prepare("UPDATE employees SET department=? WHERE user_id=?")->execute([$dept, $uid]);
    } else {
      $pdo->prepare("INSERT INTO employees (user_id, department) VALUES (?,?)")->execute([$uid, $dept]);
    }
  } else {
    $pdo->prepare("DELETE FROM employees WHERE user_id=?")->execute([$uid]);
  }

  flash_set('ok', 'User updated.');
  header('Location: ' . base_url('admin/users.php'));
  exit;
}

$rows = $pdo->query("
  SELECT u.user_id, u.username, u.email, u.role, u.created_at, e.department
  FROM users u
  LEFT JOIN employees e ON e.user_id=u.user_id
  ORDER BY u.user_id DESC
  LIMIT 500
")->fetchAll();

$ok = flash_get('ok');

include __DIR__ . '/inc/admin_header.php';
?>
<h2 class="admin-title">Users</h2>
<?php if ($ok): ?><div class="notice"><?= e($ok) ?></div><?php endif; ?>

<div class="card">
  <div class="pad">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Department</th><th>Created</th><th>Update</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $u): ?>
          <tr>
            <td><?= (int)$u['user_id'] ?></td>
            <td><?= e($u['username']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['role']) ?></td>
            <td><?= e($u['department'] ?? '') ?></td>
            <td class="muted small"><?= e(date('M d, Y', strtotime($u['created_at']))) ?></td>
            <td>
              <form method="post" class="row" style="justify-content:flex-start">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                <select class="input" name="role" style="max-width:150px">
                  <?php foreach (['customer','employee','admin'] as $r): ?>
                    <option value="<?= e($r) ?>" <?= $u['role']===$r?'selected':'' ?>><?= e($r) ?></option>
                  <?php endforeach; ?>
                </select>
                <input class="input" name="department" placeholder="Department (if employee)" style="max-width:200px" value="<?= e($u['department'] ?? '') ?>">
                <button class="btn" type="submit">Save</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/inc/admin_footer.php'; ?>
