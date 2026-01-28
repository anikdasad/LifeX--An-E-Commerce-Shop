<?php
require_once __DIR__ . '/../helpers.php';
$meta = seo_meta('Register');

csrf_check();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $pass  = (string)($_POST['password'] ?? '');
  $pass2 = (string)($_POST['password2'] ?? '');

  if ($pass !== $pass2) {
    flash_set('err', 'Passwords do not match.');
    header('Location: ' . base_url('app/auth/register.php'));
    exit;
  }
  if (mb_strlen($pass) < 6) {
    flash_set('err', 'Password must be at least 6 characters.');
    header('Location: ' . base_url('app/auth/register.php'));
    exit;
  }

  $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email=? LIMIT 1");
  $stmt->execute([$email]);
  if ($stmt->fetch()) {
    flash_set('err', 'Email already registered.');
    header('Location: ' . base_url('app/auth/register.php'));
    exit;
  }

  $hash = password_hash($pass, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("INSERT INTO users (username,email,password_hash,role,created_at) VALUES (?,?,?,?,NOW())");
  $stmt->execute([$username, $email, $hash, 'customer']);

  flash_set('ok', 'Account created. Please login.');
  header('Location: ' . base_url('app/auth/login.php'));
  exit;
}

$ok = flash_get('ok');
$err = flash_get('err');
include __DIR__ . '/../partials/header.php';
?>
<section class="section">
  <div class="container" style="max-width:520px">
    <h2 class="reveal">Create Account</h2>
    <?php if ($ok): ?><div class="notice"><?= e($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="notice" style="background:rgba(255,80,80,.12)"><?= e($err) ?></div><?php endif; ?>

    <form class="card reveal" method="post">
      <div class="pad">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label class="muted small">Username</label>
        <input class="input" name="username" required placeholder="Anik Kumar Das">
        <div style="height:10px"></div>
        <label class="muted small">Email</label>
        <input class="input" name="email" type="email" required placeholder="anik@example.com">
        <div style="height:10px"></div>
        <label class="muted small">Password</label>
        <input class="input" name="password" type="password" required placeholder="Min 6 chars">
        <div style="height:10px"></div>
        <label class="muted small">Confirm password</label>
        <input class="input" name="password2" type="password" required>
        <div class="actions" style="margin-top:14px">
          <button class="btn primary" type="submit">Register</button>
          <a class="btn" href="<?= e(base_url('app/auth/login.php')) ?>">I already have an account</a>
        </div>
      </div>
    </form>
  </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
