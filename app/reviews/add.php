<?php
require_once __DIR__ . '/../helpers.php';
require_login();
csrf_check();

$pid = (int)($_POST['product_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 5);
$rating = max(1, min(5, $rating));
$comment = trim($_POST['comment'] ?? '');

if ($comment === '') {
  flash_set('err', 'Please write a comment.');
  header('Location: ' . base_url('product.php?id=' . $pid));
  exit;
}

$stmt = $pdo->prepare("INSERT INTO product_reviews (product_id, user_id, rating, comment, created_at) VALUES (?,?,?,?,NOW())");
$stmt->execute([$pid, (int)current_user()['user_id'], $rating, $comment]);

flash_set('ok', 'Review posted.');
header('Location: ' . base_url('product.php?id=' . $pid));
exit;
