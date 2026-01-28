<?php
require_once __DIR__ . '/../helpers.php';
$meta = seo_meta('Password recovery');

csrf_check();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  // Stub: in real life you'd send a token link.
  send_order_email_stub($email, 'LifeX password recovery ', "This is a demo. Implement email token flow in production.");
  flash_set('ok', 'If the email exists, a recovery message has been logged to /app/storage/mail.log');
  header('Location: ' . base_url('app/auth/forgot.php'));
  exit;
}

$ok = flash_get('ok');
include __DIR__ . '/../partials/header.php';
?>
<section class="section">
  <div class="container" style="max-width:520px">
    <h2 class="reveal">Forgot password</h2>
    <?php if ($ok): ?><div class="notice"><?= e($ok) ?></div><?php endif; ?>
    <form class="card reveal" method="post">
      <div class="pad">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label class="muted small">Email</label>
        <input class="input" type="email" name="email" required placeholder="Anik@example.com">
        <div class="actions" style="margin-top:14px">
          <button class="btn primary" type="submit">Send recovery</button>
          <a class="btn" href="<?= e(base_url('app/auth/login.php')) ?>">Back to login</a>
        </div>
      </div>
    </form>
  </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
