<?php
require_once __DIR__ . '/app/helpers.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
  SELECT p.*, c.name AS category_name
  FROM products p
  LEFT JOIN categories c ON c.category_id = p.category_id
  WHERE p.product_id = ?
");
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) { http_response_code(404); echo "Product not found"; exit; }

$meta = seo_meta($p['name'], $p['description'] ?? '');
include __DIR__ . '/app/partials/header.php';

$reviewsStmt = $pdo->prepare("
  SELECT r.*, u.username
  FROM product_reviews r
  LEFT JOIN users u ON u.user_id = r.user_id
  WHERE r.product_id = ?
  ORDER BY r.created_at DESC
  LIMIT 20
");
$reviewsStmt->execute([$id]);
$reviews = $reviewsStmt->fetchAll();

$ok = flash_get('ok');
$err = flash_get('err');
?>
<section class="section">
  <div class="container">
    <h2 class="reveal"><?= e($p['name']) ?></h2>
    <p class="subtle reveal"><?= e($p['category_name'] ?? '') ?> • Stock: <?= (int)$p['stock_qty'] ?></p>

    <?php $img = $p['image_path'] ?? 'assets/images/products/placeholder.jpg'; ?>
    <div class="product-hero-img reveal">
      <img src="<?= e(preg_match('~^https?://~', $img) ? $img : base_url($img)) ?>" alt="<?= e($p['name']) ?>">
    </div>


    <?php if ($ok): ?><div class="notice"><?= e($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="notice" style="background:rgba(255,80,80,.12)"><?= e($err) ?></div><?php endif; ?>

    <div class="card reveal">
      <div class="pad">
        <div class="row">
          <div class="price" style="font-size:22px">৳<?= number_format((float)$p['price'], 2) ?></div>
          <div class="muted small">Created: <?= e(date('M d, Y', strtotime($p['created_at']))) ?></div>
        </div>
        <div class="hr"></div>
        <div style="line-height:1.7" class="muted"><?= nl2br(e($p['description'] ?? '')) ?></div>

        <div class="hr"></div>

        <form class="row" action="<?= e(base_url('app/cart/add.php')) ?>" method="post">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
          <div style="max-width:180px">
            <label class="muted small">Quantity</label>
            <input class="input" type="number" min="1" max="<?= (int)$p['stock_qty'] ?>" name="qty" value="1" />
          </div>
          <button class="btn primary" type="submit">Add to Cart</button>
        </form>
      </div>
    </div>

    <div class="section">
      <h2 class="reveal">Reviews</h2>

      <?php if (is_logged_in()): ?>
      <form class="card reveal" action="<?= e(base_url('app/reviews/add.php')) ?>" method="post" style="margin-bottom:12px">
        <div class="pad">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
          <div class="row">
            <div style="max-width:180px">
              <label class="muted small">Rating (1-5)</label>
              <input class="input" name="rating" type="number" min="1" max="5" value="5">
            </div>
            <div style="flex:1">
              <label class="muted small">Comment</label>
              <input class="input" name="comment" placeholder="Write a short review...">
            </div>
            <button class="btn primary" type="submit">Post</button>
          </div>
          <div class="muted small" style="margin-top:8px">Be respectful. Reviews help others.</div>
        </div>
      </form>
      <?php else: ?>
        <div class="notice reveal">Login to write a review.</div>
      <?php endif; ?>

      <div class="card reveal">
        <div class="pad">
          <?php if (!$reviews): ?>
            <div class="muted">No reviews yet.</div>
          <?php else: ?>
            <?php foreach ($reviews as $r): ?>
              <div style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,.08)">
                <div class="row">
                  <div style="font-weight:700"><?= e($r['username'] ?? 'User') ?></div>
                  <div class="muted small">⭐ <?= (int)$r['rating'] ?>/5 • <?= e(date('M d, Y', strtotime($r['created_at']))) ?></div>
                </div>
                <div class="muted" style="margin-top:6px"><?= e($r['comment'] ?? '') ?></div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/app/partials/footer.php'; ?>