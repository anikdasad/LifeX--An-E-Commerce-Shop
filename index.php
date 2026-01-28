<?php
require_once __DIR__ . '/app/helpers.php';

$meta = seo_meta('Apple-style shopping experience', 'LifeX is a fast, modern PHP e-commerce website with Apple-like scroll animations, category filtering, and smooth checkout.');

$cats = $pdo->query("SELECT category_id, name FROM categories ORDER BY name")->fetchAll();

$limit = 80;
$stmt = $pdo->prepare("
  SELECT p.*, c.name AS category_name
  FROM products p
  LEFT JOIN categories c ON c.category_id = p.category_id
  WHERE p.is_active = 1
  ORDER BY p.created_at DESC
  LIMIT ?
");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

include __DIR__ . '/app/partials/header.php';
?>
<section class="hero hero-laptop" id="home">
  <div class="container">
    <div class="hero-top">
      <div class="hero-badge reveal">New • Laptop landing</div>
      <h1 class="hero-title reveal">Meet the Apple M4 Laptop.</h1>
      <p class="hero-sub reveal" style="text-align:center">Scroll to see every feature — smooth, Apple M4 Features. Purchase directly from LifeX Store below. </p>
      <div class="hero-actions reveal">
        <a class="btn primary" href="#products">Shop Products</a>
      
      </div>
    </div>

    <div class="laptop-grid">
      <div class="laptop-sticky">
        <div class="laptop-wrap">
          <!-- Real laptop photo -->
          <div class="laptop-photo">
            <img src="https://store.storeimages.cdn-apple.com/1/as-images.apple.com/is/mba13-skyblue-select-202503?wid=904&hei=840&fmt=jpeg&qlt=90&.v=M2RyY09CWXlTQUp1KzEveHR6VXNxcTQ1bzN1SitYTU83Mm9wbk1xa1lWNC9UNzNvY2N5NXJTTDQ2YkVYYmVXakJkRlpCNVhYU3AwTldRQldlSnpRa0lIV0Fmdk9rUlVsZ3hnNXZ3K3lEVlk" alt="Laptop" class="laptop-img"/>
            <div class="laptop-screen" id="laptop-screen" aria-hidden="true"><div class="screen-inner"><div class="screen-title">Liquid Retina Display</div><div class="screen-desc">Scroll to see laptop features.</div><div class="screen-pills"></div></div></div>
          </div>

          <!-- Dynamic "screen" content -->
          <div class="laptop-screen-overlay">
            <div class="screen-pills">
            </div>
          </div>
        </div>
      </div>

      <!-- Scroll steps -->
      <div class="laptop-steps">
        <div class="feature-step is-active" data-title="Liquid Retina Display"
             data-desc="Brighter, sharper, and comfortable for long sessions — reading, coding, and binge‑building."
             data-pills="13.6&quot;|500 nits|True Tone">
          <h3>14.2″ | 3024×1964 | Liquid Retina XDR Display</h3>
          <p class="muted">Scroll down — the laptop screen updates as each feature enters view.</p>
        </div>

        <div class="feature-step" data-title="All‑day Battery"
             data-desc="Work, game, and create with power that lasts. No charger anxiety — just flow."
             data-pills="Up to 18 hours|Fast charge|Low power mode">
          <h3>All‑day Battery</h3>
          <p class="muted">Built-in 72.4 watt-hour lithium‑polymer battery | 70W USB-C Power Adapter (MagSafe 3) | Perfect for classes, freelancing, and travel.</p>
        </div>

        <div class="feature-step" data-title="Pro Performance"
             data-desc="Snappy apps, smooth multitasking, and quick exports. Built for real work."
             data-pills="8‑core CPU|16GB unified|Ultra‑fast SSD">
          <h3>Pro Performance</h3>
          <p class="muted">Feels instant | like it already knows what you’ll do next.</p>
        </div>

        <div class="feature-step" data-title="Studio‑grade Audio"
             data-desc="Clear voice, rich speakers, and cleaner calls — for meetings and content."
             data-pills="4‑speaker array|3‑mic beamforming|Spatial audio">
          <h3>Studio‑grade Audio</h3>
          <p class="muted">High-fidelity six-speaker sound system | 3.5 mm headphone jack with advanced support</p>
        </div>

        <div class="feature-step" data-title="Secure by Design"
             data-desc="Encryption, safe sign‑in, and privacy‑first defaults — built into the system."
             data-pills="Device encryption|Secure login|Privacy controls">
          <h3>Design</h3>
          <p class="muted">Height: 0.61 inch (1.55 cm) | Width: 12.31 inches (31.26 cm) | Depth: 8.71 inches (22.12 cm) | Weight: 3.5 pounds (1.60 kg)</p>
        </div>

        <div class="feature-step" data-title="Ready for LifeX Store"
             data-desc="Now scroll into the product grid — your store is already loaded with items and images."
             data-pills="<?= (int)$pdo->query("SELECT COUNT(*) c FROM products WHERE is_active=1")->fetch()['c'] ?>+ products|Category filter|Load more">
          <h3>Ready for the Store</h3>
          <p class="muted"><a class="btn primary" href="#products">Explore Products</a></p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section" id="products">
  <div class="container">
    <h2 class="reveal">Products</h2>
    <p class="subtle reveal">Browse all of products. Filter by category, search, and load more—super smooth.</p>

    <form id="search-form" class="card reveal" style="margin-bottom:12px">
      <div class="pad">
        <div class="row">
          <input id="search-q" class="input" name="q" placeholder="Search products..." />
          <button class="btn primary" type="submit">Search</button>
        </div>
        <div style="margin-top:12px;display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap">
          <div class="pills" style="margin:0">
            <span class="pill active" data-category="">All</span>
            <?php foreach ($cats as $c): ?>
              <span class="pill" data-category="<?= (int)$c['category_id'] ?>"><?= e($c['name']) ?></span>
            <?php endforeach; ?>
          </div>
          <select id="sort-select" class="input" style="width:180px">
            <option value="newest">Newest First</option>
            <option value="oldest">Oldest First</option>
            <option value="price_asc">Price: Low to High</option>
            <option value="price_desc">Price: High to Low</option>
            <option value="name_asc">Name: A to Z</option>
            <option value="name_desc">Name: Z to A</option>
          </select>
        </div>
        <div class="muted small">Tip: Click a category pill, select a sort option, then use "Load More".</div>
      </div>
    </form>

    <div id="product-grid" class="grid">
      <?php foreach ($products as $p): ?>
        <?php include __DIR__ . '/app/partials/product-card.php'; ?>
      <?php endforeach; ?>
    </div>

    <div style="display:flex;align-items:center;justify-content:center;flex-direction:column;gap:8px;margin-top:16px">
      <button class="btn primary" data-loadmore data-offset="<?= (int)$limit ?>" data-limit="<?= (int)$limit ?>">Load More</button>
      <div id="loadmore-status" class="muted small"></div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/app/partials/footer.php'; ?>
