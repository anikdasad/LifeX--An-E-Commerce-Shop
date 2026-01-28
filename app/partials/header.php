<?php
require_once __DIR__ . '/../helpers.php';
$u = current_user();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($meta['title'] ?? 'LifeX') ?></title>
  <meta name="description" content="<?= e($meta['desc'] ?? 'LifeX e-commerce') ?>">
  <link rel="stylesheet" href="<?= e(base_url('assets/css/apple.css')) ?>">
  <link rel="stylesheet" href="<?= e(base_url('assets/css/admin.css')) ?>">
  <script defer src="<?= e(base_url('assets/js/apple-scroll.js')) ?>"></script>
  <script defer src="<?= e(base_url('assets/js/load-more.js')) ?>"></script>
  <script defer src="<?= e(base_url('assets/js/filter.js')) ?>"></script>
  <script defer src="<?= e(base_url('assets/js/ui.js')) ?>"></script>
</head>
<body>
<header class="topbar">
  <div class="topbar-inner">
    <a class="brand" href="<?= e(base_url()) ?>">LifeX</a>
    <nav class="nav">
      <a href="<?= e(base_url()) ?>#products">Products</a>
      <a href="<?= e(base_url('cart.php')) ?>" class="cart-link" title="Shopping Cart">
        <svg class="cart-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="20" cy="21" r="1"></circle>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        <span class="badge"><?= cart_count() ?></span>
      </a>
      <?php if (!$u || ($u['role'] ?? '') === 'customer'): ?>
        <!-- Single login button for both users and admins -->
      <?php else: ?>
        <a href="<?= e(base_url('admin/dashboard.php')) ?>">Admin Dashboard</a>
      <?php endif; ?>
      <?php if ($u): ?>
        <a href="<?= e(base_url('profile.php')) ?>">Profile</a>
        <a href="<?= e(base_url('track.php')) ?>">Track Order</a>
        <a href="<?= e(base_url('orders.php')) ?>">My Orders</a>
        <?php if (in_array($u['role'], ['admin','employee'], true)): ?>
          <a href="<?= e(base_url('admin/dashboard.php')) ?>">Admin</a>
        <?php endif; ?>
        <a href="<?= e(base_url('app/auth/logout.php')) ?>">Logout</a>
      <?php else: ?>
        <a href="<?= e(base_url('app/auth/login.php')) ?>">Login</a>
        <a class="btn-mini" href="<?= e(base_url('app/auth/register.php')) ?>">Register</a>
      <?php endif; ?>
          <button class="theme-toggle" id="theme-toggle" type="button" aria-label="Toggle theme"><span class="theme-icon" aria-hidden="true">🌙</span></button>
    </nav>
  </div>
</header>
<main class="page">
