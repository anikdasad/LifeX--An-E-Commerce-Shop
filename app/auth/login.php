<?php
require_once __DIR__ . '/../helpers.php';
$meta = seo_meta('Login');

csrf_check();

$loginMode = $_GET['mode'] ?? 'user'; // user or admin
$isAdminMode = $loginMode === 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $pass  = (string)($_POST['password'] ?? '');

  $stmt = $pdo->prepare("SELECT user_id, username, email, password_hash, role FROM users WHERE email=? LIMIT 1");
  $stmt->execute([$email]);
  $user = $stmt->fetch();

  if ($user && password_verify($pass, $user['password_hash'])) {
    // Check if admin mode and user is not admin/employee
    if ($isAdminMode && !in_array($user['role'], ['admin','employee'], true)) {
      flash_set('err', 'This account does not have admin access.');
      header('Location: ' . base_url('app/auth/login.php?mode=admin'));
      exit;
    }
    
    unset($user['password_hash']);
    $_SESSION['user'] = $user;
    
    if ($isAdminMode) {
      flash_set('ok', 'Welcome to Admin Dashboard!');
      header('Location: ' . base_url('admin/dashboard.php'));
    } else {
      flash_set('ok', 'Welcome back!');
      header('Location: ' . base_url());
    }
    exit;
  } else {
    flash_set('err', 'Invalid email or password.');
    header('Location: ' . base_url('app/auth/login.php?mode=' . $loginMode));
    exit;
  }
}

$ok = flash_get('ok');
$err = flash_get('err');
include __DIR__ . '/../partials/header.php';
?>
<section class="section">
  <div class="container" style="max-width:520px">
    <h2 class="reveal"><?= $isAdminMode ? 'Admin Login' : 'Login' ?></h2>
    
    <!-- Mode Toggle -->
    <div class="card reveal" style="margin-bottom:12px;padding:12px">
      <div style="display:flex;gap:8px">
        <a href="<?= e(base_url('app/auth/login.php?mode=user')) ?>" class="btn" style="flex:1;text-align:center;<?= !$isAdminMode ? 'background:var(--accent);color:#000' : '' ?>"> User Login</a>
        <a href="<?= e(base_url('app/auth/login.php?mode=admin')) ?>" class="btn" style="flex:1;text-align:center;<?= $isAdminMode ? 'background:var(--accent);color:#000' : '' ?>"> Admin Login</a>
      </div>
    </div>

    <?php if ($ok): ?><div class="notice"><?= e($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="notice" style="background:rgba(255,80,80,.12)"><?= e($err) ?></div><?php endif; ?>

    <form class="card reveal" method="post">
      <div class="pad">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label class="muted small">Email</label>
        <input class="input" name="email" type="email" required placeholder="anikkumardas@example.com">
        <div style="height:10px"></div>
        <label class="muted small">Password</label>
        <input class="input" name="password" type="password" required placeholder="••••••••">
        <div class="actions" style="margin-top:14px">
          <button class="btn primary" type="submit">Login</button>
          <a class="btn" href="<?= e(base_url('app/auth/register.php')) ?>">Create account</a>
          <a class="btn" href="<?= e(base_url('app/auth/forgot.php')) ?>">Forgot password</a>
        </div>
      </div>
    </form>
  </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
