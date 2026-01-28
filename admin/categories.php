<?php
require_once __DIR__ . '/inc/guard.php';
$meta = seo_meta('Categories');
csrf_check();

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  if ($name === '') {
    flash_set('err', 'Name is required.');
    header('Location: ' . base_url('admin/categories.php'));
    exit;
  }

  if ($action === 'edit' && $id > 0) {
    $pdo->prepare("UPDATE categories SET name=? WHERE category_id=?")->execute([$name, $id]);
    flash_set('ok', 'Category updated.');
  } else {
    $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$name]);
    flash_set('ok', 'Category created.');
  }
  header('Location: ' . base_url('admin/categories.php'));
  exit;
}

if ($action === 'delete' && $id > 0) {
  // Soft rule: allow delete only when no products
  $stmt = $pdo->prepare("SELECT COUNT(*) c FROM products WHERE category_id=?");
  $stmt->execute([$id]);
  if ((int)$stmt->fetch()['c'] > 0) {
    flash_set('err', 'Cannot delete: category has products.');
  } else {
    $pdo->prepare("DELETE FROM categories WHERE category_id=?")->execute([$id]);
    flash_set('ok', 'Category deleted.');
  }
  header('Location: ' . base_url('admin/categories.php'));
  exit;
}

$rows = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$edit = null;
if ($action === 'edit' && $id > 0) {
  $s = $pdo->prepare("SELECT * FROM categories WHERE category_id=?");
  $s->execute([$id]);
  $edit = $s->fetch();
}

$ok = flash_get('ok');
$err = flash_get('err');

include __DIR__ . '/inc/admin_header.php';
?>
<h2 class="admin-title">Categories</h2>
<?php if ($ok): ?><div class="notice"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="notice" style="background:rgba(255,80,80,.12)"><?= e($err) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:12px">
  <div class="pad">
    <h2 style="margin:0 0 10px"><?= $edit ? 'Edit Category' : 'Add Category' ?></h2>
    <form method="post" action="<?= e(base_url('admin/categories.php' . ($edit ? '?action=edit&id='.(int)$edit['category_id'] : '?action=add'))) ?>">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <label class="muted small">Name</label>
      <input class="input" name="name" required value="<?= e($edit['name'] ?? '') ?>">
      <div class="actions" style="margin-top:14px">
        <button class="btn primary" type="submit">Save</button>
        <?php if ($edit): ?><a class="btn" href="<?= e(base_url('admin/categories.php')) ?>">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="pad">
    <table class="table">
      <thead><tr><th>ID</th><th>Name</th><th style="width:200px">Actions</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['category_id'] ?></td>
          <td><?= e($r['name']) ?></td>
          <td class="actions">
            <a class="btn" href="<?= e(base_url('admin/categories.php?action=edit&id='.(int)$r['category_id'])) ?>">Edit</a>
            <a class="btn" href="<?= e(base_url('admin/categories.php?action=delete&id='.(int)$r['category_id'])) ?>" onclick="return confirm('Delete category?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/inc/admin_footer.php'; ?>
