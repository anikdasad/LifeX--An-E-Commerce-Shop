<?php
// expects $p array with product fields
$img = $p['image_path'] ?? 'assets/images/products/placeholder.jpg';
?>
<article class="card scale product-card">
  <a class="product-thumb" href="<?= e(base_url('product.php?id=' . (int)$p['product_id'])) ?>">
    <img src="<?= e(preg_match('~^https?://~', $img) ? $img : base_url($img)) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
  </a>
  <div class="pad">
    <div class="row">
      <div>
        <div style="font-weight:800"><?= e($p['name']) ?></div>
        <div class="muted small"><?= e($p['category_name'] ?? '') ?></div>
      </div>
      <div class="price">৳<?= number_format((float)$p['price'], 2) ?></div>
    </div>
    <div class="hr"></div>
    <div class="muted small" style="line-height:1.5">
      <?= e(mb_substr($p['description'] ?? '', 0, 120)) ?><?= (mb_strlen($p['description'] ?? '') > 120) ? '…' : '' ?>
    </div>
    <div class="actions" style="margin-top:12px">
      <a class="btn" href="<?= e(base_url('product.php?id=' . (int)$p['product_id'])) ?>">View</a>
      <form action="<?= e(base_url('app/cart/add.php')) ?>" method="post" style="margin:0">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
        <input type="hidden" name="qty" value="1">
        <button class="btn primary" type="submit">Add to Cart</button>
      </form>
    </div>
  </div>
</article>
