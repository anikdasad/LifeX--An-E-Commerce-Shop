<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../config/db.php';

function e(string $str): string {
  return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function base_url(string $path = ''): string {
  // If you rename the folder, update this.
  $base = '/LifeX';
  return $base . ($path ? '/' . ltrim($path, '/') : '');
}

function is_logged_in(): bool {
  return !empty($_SESSION['user']);
}

function current_user(): ?array {
  return $_SESSION['user'] ?? null;
}

function require_login(): void {
  if (!is_logged_in()) {
    header('Location: ' . base_url('app/auth/login.php'));
    exit;
  }
}

function require_role(array $roles): void {
  require_login();
  $role = $_SESSION['user']['role'] ?? '';
  if (!in_array($role, $roles, true)) {
    http_response_code(403);
    echo "Forbidden";
    exit;
  }
}

function csrf_token(): string {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
  }
  return $_SESSION['csrf_token'];
}

function csrf_check(): void {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
      http_response_code(400);
      echo "Invalid CSRF token.";
      exit;
    }
  }
}

function flash_set(string $key, string $msg): void {
  $_SESSION['flash'][$key] = $msg;
}

function flash_get(string $key): ?string {
  if (!empty($_SESSION['flash'][$key])) {
    $msg = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $msg;
  }
  return null;
}

function cart_get(): array {
  return $_SESSION['cart'] ?? [];
}

function cart_set(array $cart): void {
  $_SESSION['cart'] = $cart;
}

function cart_count(): int {
  $c = 0;
  foreach (cart_get() as $qty) $c += (int)$qty;
  return $c;
}

function cart_total(PDO $pdo): float {
  $cart = cart_get();
  if (!$cart) return 0.0;
  $ids = array_keys($cart);
  $in = implode(',', array_fill(0, count($ids), '?'));
  $stmt = $pdo->prepare("SELECT product_id, price FROM products WHERE product_id IN ($in)");
  $stmt->execute($ids);
  $sum = 0.0;
  foreach ($stmt->fetchAll() as $row) {
    $pid = (int)$row['product_id'];
    $sum += ((float)$row['price']) * ((int)$cart[$pid]);
  }
  return $sum;
}

function seo_meta(string $title, string $desc = ''): array {
  $title = trim($title);
  if ($desc === '') {
    $desc = $title . " — Buy premium products on LifeX. Fast checkout, secure shopping.";
  }
  return [
    'title' => $title . " | LifeX",
    'desc'  => mb_substr(trim($desc), 0, 160),
  ];
}

function send_order_email_stub(string $toEmail, string $subject, string $body): void {
  // Demo: in local XAMPP mail() usually isn't configured.
  // We log to a file instead.
  $logDir = __DIR__ . '/../storage';
  if (!is_dir($logDir)) mkdir($logDir, 0777, true);
  $line = "[" . date('Y-m-d H:i:s') . "] TO:$toEmail | $subject
$body

";
  file_put_contents($logDir . '/mail.log', $line, FILE_APPEND);
}
