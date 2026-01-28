<?php
require_once __DIR__ . '/guard.php';
$meta = $meta ?? seo_meta('Admin');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($meta['title'] ?? 'Admin | LifeX') ?></title>
  <link rel="stylesheet" href="<?= e(base_url('assets/css/apple.css')) ?>">
  <link rel="stylesheet" href="<?= e(base_url('assets/css/admin.css')) ?>">
</head>
<body>
<header class="topbar">
  <div class="topbar-inner">
    <a class="brand" href="<?= e(base_url('admin/dashboard.php')) ?>">LifeX Admin</a>
    <nav class="nav">
      <a href="<?= e(base_url()) ?>">Store</a>
      <a href="<?= e(base_url('app/auth/logout.php')) ?>">Logout</a>
    </nav>
  </div>
</header>
<main class="page">
<div class="container admin-wrap">
  <aside class="sidebar">
    <a class="<?= admin_active('dashboard.php') ?>" href="<?= e(base_url('admin/dashboard.php')) ?>">Dashboard</a>
    <a class="<?= admin_active('products.php') ?>" href="<?= e(base_url('admin/products.php')) ?>">Products</a>
    <a class="<?= admin_active('categories.php') ?>" href="<?= e(base_url('admin/categories.php')) ?>">Categories</a>
    <a class="<?= admin_active('orders.php') ?>" href="<?= e(base_url('admin/orders.php')) ?>">Orders</a>
    <a class="<?= admin_active('payments.php') ?>" href="<?= e(base_url('admin/payments.php')) ?>">Payments</a>
    <a class="<?= admin_active('reports.php') ?>" href="<?= e(base_url('admin/reports.php')) ?>">Reports</a>
    <?php if ((current_user()['role'] ?? '') === 'admin'): ?>
      <a class="<?= admin_active('users.php') ?>" href="<?= e(base_url('admin/users.php')) ?>">Users</a>
    <?php endif; ?>
  </aside>
  <section>
