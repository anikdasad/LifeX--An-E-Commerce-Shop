<?php
require_once __DIR__ . '/inc/guard.php';
$meta = seo_meta('Products');
csrf_check();

$role = current_user()['role'] ?? 'employee';

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

$cats = $pdo->query("SELECT category_id, name FROM categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $desc = trim($_POST['description'] ?? '');
  $price = (float)($_POST['price'] ?? 0);
  $stock = (int)($_POST['stock_qty'] ?? 0);
  $catId = (int)($_POST['category_id'] ?? 0);
  $isActive = isset($_POST['is_active']) ? 1 : 0;

  if ($name === '' || $price <= 0) {
    flash_set('err', 'Name and price are required.');
    header('Location: ' . base_url('admin/products.php' . ($id ? '?action=edit&id='.$id : '?action=add')));
    exit;
  }

  $imagePath = null;
  if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
    $file = $_FILES['image'];
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($file['type'], $allowed)) {
      flash_set('err', 'Only image files (JPEG, PNG, WebP, GIF) are allowed.');
      header('Location: ' . base_url('admin/products.php' . ($id ? '?action=edit&id='.$id : '?action=add')));
      exit;
    }
    $dir = __DIR__ . '/../assets/images/products';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fname = 'product_' . time() . '.' . $ext;
    $path = $dir . '/' . $fname;
    if (move_uploaded_file($file['tmp_name'], $path)) {
      $imagePath = 'assets/images/products/' . $fname;
    }
  }

  if ($action === 'edit' && $id > 0) {
    if ($imagePath) {
      $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, stock_qty=?, category_id=?, is_active=?, image_path=? WHERE product_id=?");
      $stmt->execute([$name, $desc, $price, $stock, $catId ?: null, $isActive, $imagePath, $id]);
    } else {
      $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, stock_qty=?, category_id=?, is_active=? WHERE product_id=?");
      $stmt->execute([$name, $desc, $price, $stock, $catId ?: null, $isActive, $id]);
    }
    flash_set('ok', 'Product updated.');
  } else {
    if (!$imagePath) $imagePath = 'assets/images/products/placeholder.jpg';
    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock_qty, category_id, is_active, image_path, created_at) VALUES (?,?,?,?,?,?,?,NOW())");
    $stmt->execute([$name, $desc, $price, $stock, $catId ?: null, $isActive, $imagePath]);
    flash_set('ok', 'Product created.');
  }

  header('Location: ' . base_url('admin/products.php'));
  exit;
}

if ($action === 'delete' && $id > 0) {
  if ($role !== 'admin') { http_response_code(403); echo "Only admin can delete."; exit; }
  
  // Check if product has orders
  $checkStmt = $pdo->prepare("SELECT COUNT(*) c FROM order_items WHERE product_id=?");
  $checkStmt->execute([$id]);
  $hasOrders = (int)$checkStmt->fetch()['c'] > 0;
  
  if ($hasOrders) {
    // Soft delete - mark as inactive instead of deleting (to preserve order history)
    $pdo->prepare("UPDATE products SET is_active=0 WHERE product_id=?")->execute([$id]);
    flash_set('ok', 'Product deactivated (cannot delete - has associated orders).');
  } else {
    // Hard delete if no orders exist
    $pdo->prepare("DELETE FROM products WHERE product_id=?")->execute([$id]);
    flash_set('ok', 'Product deleted.');
  }
  
  header('Location: ' . base_url('admin/products.php'));
  exit;
}

$ok = flash_get('ok');
$err = flash_get('err');

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$total = (int)$pdo->query("SELECT COUNT(*) c FROM products")->fetch()['c'];
$pages = max(1, (int)ceil($total / $perPage));

$stmt = $pdo->prepare("
  SELECT p.*, c.name AS category_name
  FROM products p
  LEFT JOIN categories c ON c.category_id=p.category_id
  ORDER BY p.product_id DESC
  LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$editRow = null;
if (($action === 'edit') && $id > 0) {
  $s = $pdo->prepare("SELECT * FROM products WHERE product_id=?");
  $s->execute([$id]);
  $editRow = $s->fetch();
}

include __DIR__ . '/inc/admin_header.php';
?>

<h2 class="admin-title">Products</h2>
<?php if ($ok): ?><div class="notice"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="notice" style="background:rgba(255,80,80,.12)"><?= e($err) ?></div><?php endif; ?>

<div class="actions" style="margin-bottom:12px">
  <a class="btn primary" href="<?= e(base_url('admin/products.php?action=add')) ?>">+ Add product</a>
</div>

<?php if ($action === 'add' || ($action === 'edit' && $editRow)): 
  $p = $editRow ?: ['name'=>'','description'=>'','price'=>'','stock_qty'=>0,'category_id'=>'','is_active'=>1];
?>
  <div class="card" style="margin-bottom:12px">
    <div class="pad">
      <h2 style="margin:0 0 10px"><?= $action === 'edit' ? 'Edit Product' : 'Add Product' ?></h2>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label class="muted small">Name</label>
        <input class="input" name="name" required value="<?= e((string)$p['name']) ?>">
        <div style="height:10px"></div>
        <label class="muted small">Description</label>
        <textarea class="input" name="description"><?= e((string)$p['description']) ?></textarea>
        <div style="height:10px"></div>
        <div class="grid" style="grid-template-columns:repeat(3,1fr)">
          <div>
            <label class="muted small">Price</label>
            <input class="input" name="price" type="number" step="0.01" required value="<?= e((string)$p['price']) ?>">
          </div>
          <div>
            <label class="muted small">Stock</label>
            <input class="input" name="stock_qty" type="number" min="0" required value="<?= (int)$p['stock_qty'] ?>">
          </div>
          <div>
            <label class="muted small">Category</label>
            <select name="category_id" class="input">
              <option value="">-- None --</option>
              <?php foreach ($cats as $c): ?>
                <option value="<?= (int)$c['category_id'] ?>" <?= ((int)$p['category_id']===(int)$c['category_id'])?'selected':'' ?>>
                  <?= e($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div style="height:10px"></div>
        <?php if ($action === 'edit' && $editRow && isset($editRow['image_path'])): ?>
          <div style="margin-bottom:10px">
            <label class="muted small">Current Image</label>
            <div style="max-width:150px; margin:8px 0">
              <img src="<?= e(preg_match('~^https?://~', $editRow['image_path']) ? $editRow['image_path'] : base_url($editRow['image_path'])) ?>" alt="<?= e($editRow['name']) ?>" style="width:100%; height:auto; border-radius:4px">
            </div>
          </div>
        <?php endif; ?>
        <label class="muted small">Product Image</label>
        <input class="input" type="file" name="image" accept="image/*">
        <div class="muted small">Supported: JPEG, PNG, WebP, GIF (optional <?= $action === 'edit' ? 'for updates' : '' ?>)</div>

        <div style="height:10px"></div>
        <label class="muted small">
          <input type="checkbox" name="is_active" <?= ((int)$p['is_active']===1)?'checked':'' ?>> Active
        </label>

        <div class="actions" style="margin-top:14px">
          <button class="btn primary" type="submit">Save</button>
          <a class="btn" href="<?= e(base_url('admin/products.php')) ?>">Cancel</a>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<div class="card">
  <div class="pad">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Category</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Active</th>
          <th style="width:210px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= (int)$r['product_id'] ?></td>
            <td><?= e($r['name']) ?></td>
            <td><?= e($r['category_name'] ?? '—') ?></td>
            <td>৳<?= number_format((float)$r['price'],2) ?></td>
            <td><?= (int)$r['stock_qty'] ?></td>
            <td><?= (int)$r['is_active'] ? 'Yes' : 'No' ?></td>
            <td class="actions">
              <a class="btn" href="<?= e(base_url('admin/products.php?action=edit&id='.(int)$r['product_id'])) ?>">Edit</a>
              <?php if ($role === 'admin'): ?>
                <a class="btn" href="<?= e(base_url('admin/products.php?action=delete&id='.(int)$r['product_id'])) ?>" onclick="return confirm('Delete product?')">Delete</a>
              <?php endif; ?>
              <a class="btn" href="<?= e(base_url('product.php?id='.(int)$r['product_id'])) ?>">View</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="actions" style="margin-top:12px">
      <?php for ($i=1;$i<=$pages;$i++): ?>
        <a class="btn <?= $i===$page?'primary':'' ?>" href="<?= e(base_url('admin/products.php?page='.$i)) ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/inc/admin_footer.php'; ?>
