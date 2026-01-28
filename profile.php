<?php
require_once __DIR__ . '/app/helpers.php';
require_login();

$u = current_user();
$userId = (int)$u['user_id'];

// Fetch user profile
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$ok = flash_get('ok');
$err = flash_get('err');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  // Update profile info
  if ($action === 'update_profile') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');

    if ($username === '' || $email === '') {
      flash_set('err', 'Username and email are required.');
      header('Location: ' . base_url('profile.php'));
      exit;
    }

    // Check if email is already in use by another user
    $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email=? AND user_id!=?");
    $checkStmt->execute([$email, $userId]);
    if ($checkStmt->fetch()) {
      flash_set('err', 'Email is already in use.');
      header('Location: ' . base_url('profile.php'));
      exit;
    }

    // Handle profile picture upload
    $profilePic = null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['size'] > 0) {
      $file = $_FILES['profile_pic'];
      $allowed = ['image/jpeg', 'image/png', 'image/webp'];
      if (!in_array($file['type'], $allowed)) {
        flash_set('err', 'Only JPEG, PNG, and WebP images are allowed.');
        header('Location: ' . base_url('profile.php'));
        exit;
      }
      $dir = __DIR__ . '/assets/images/profiles';
      if (!is_dir($dir)) mkdir($dir, 0755, true);
      $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
      $fname = 'profile_' . $userId . '_' . time() . '.' . $ext;
      $path = $dir . '/' . $fname;
      if (move_uploaded_file($file['tmp_name'], $path)) {
        $profilePic = 'assets/images/profiles/' . $fname;
      }
    }

    // Update user
    if ($profilePic) {
      $pdo->prepare("UPDATE users SET username=?, email=?, phone=?, address=?, city=?, profile_pic=? WHERE user_id=?")
          ->execute([$username, $email, $phone, $address, $city, $profilePic, $userId]);
    } else {
      $pdo->prepare("UPDATE users SET username=?, email=?, phone=?, address=?, city=? WHERE user_id=?")
          ->execute([$username, $email, $phone, $address, $city, $userId]);
    }

    $_SESSION['user']['username'] = $username;
    $_SESSION['user']['email'] = $email;
    flash_set('ok', 'Profile updated successfully.');
    header('Location: ' . base_url('profile.php'));
    exit;
  }

  // Change password
  if ($action === 'change_password') {
    $currentPwd = $_POST['current_password'] ?? '';
    $newPwd = $_POST['new_password'] ?? '';
    $confirmPwd = $_POST['confirm_password'] ?? '';

    if ($newPwd === '' || $newPwd !== $confirmPwd) {
      flash_set('err', 'Passwords do not match or are empty.');
      header('Location: ' . base_url('profile.php'));
      exit;
    }

    if (strlen($newPwd) < 6) {
      flash_set('err', 'Password must be at least 6 characters.');
      header('Location: ' . base_url('profile.php'));
      exit;
    }

    if (!password_verify($currentPwd, $user['password_hash'])) {
      flash_set('err', 'Current password is incorrect.');
      header('Location: ' . base_url('profile.php'));
      exit;
    }

    $hashedPwd = password_hash($newPwd, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password_hash=? WHERE user_id=?")->execute([$hashedPwd, $userId]);

    flash_set('ok', 'Password changed successfully.');
    header('Location: ' . base_url('profile.php'));
    exit;
  }
}

$meta = seo_meta('My Profile');
include __DIR__ . '/app/partials/header.php';
?>
<section class="section">
  <div class="container" style="max-width:800px">
    <h2 class="reveal">My Profile</h2>
    <?php if ($ok): ?><div class="notice" style="margin-bottom:16px"><?= e($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="notice" style="background:rgba(255,80,80,.12);margin-bottom:16px"><?= e($err) ?></div><?php endif; ?>

    <!-- Profile Information -->
    <div class="card reveal" style="margin-bottom:16px">
      <div class="pad">
        <h3 style="margin:0 0 16px 0">Profile Information</h3>
        
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="update_profile">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

          <!-- Profile Picture -->
          <div style="margin-bottom:16px;text-align:center">
            <?php 
              $profilePic = $user['profile_pic'] ?? 'assets/images/profiles/default-avatar.png';
            ?>
            <div style="width:100px;height:100px;margin:0 auto 12px;border-radius:50%;overflow:hidden;border:2px solid var(--line);background:var(--card);display:flex;align-items:center;justify-content:center">
              <img src="<?= e(preg_match('~^https?://~', $profilePic) ? $profilePic : base_url($profilePic)) ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover">
            </div>
            <input type="file" name="profile_pic" accept="image/*" class="input" style="max-width:200px;margin:0 auto">
            <div class="muted small">Recommended: Square image (200x200px or larger)</div>
          </div>

          <div style="margin-bottom:12px">
            <label class="muted small">Username</label>
            <input type="text" name="username" class="input" required value="<?= e($user['username']) ?>">
          </div>

          <div style="margin-bottom:12px">
            <label class="muted small">Email</label>
            <input type="email" name="email" class="input" required value="<?= e($user['email']) ?>">
          </div>

          <div style="margin-bottom:12px">
            <label class="muted small">Mobile Number</label>
            <input type="tel" name="phone" class="input" value="<?= e($user['phone'] ?? '') ?>">
          </div>

          <div style="margin-bottom:12px">
            <label class="muted small">Address</label>
            <input type="text" name="address" class="input" value="<?= e($user['address'] ?? '') ?>">
          </div>

          <div style="margin-bottom:16px">
            <label class="muted small">City</label>
            <input type="text" name="city" class="input" value="<?= e($user['city'] ?? '') ?>">
          </div>

          <div class="actions">
            <button type="submit" class="btn primary">Save Changes</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Change Password -->
    <div class="card reveal">
      <div class="pad">
        <h3 style="margin:0 0 16px 0">Change Password</h3>
        
        <form method="post">
          <input type="hidden" name="action" value="change_password">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

          <div style="margin-bottom:12px">
            <label class="muted small">Current Password</label>
            <input type="password" name="current_password" class="input" required>
          </div>

          <div style="margin-bottom:12px">
            <label class="muted small">New Password</label>
            <input type="password" name="new_password" class="input" required minlength="6">
          </div>

          <div style="margin-bottom:16px">
            <label class="muted small">Confirm New Password</label>
            <input type="password" name="confirm_password" class="input" required minlength="6">
          </div>

          <div class="actions">
            <button type="submit" class="btn primary">Update Password</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Account Info -->
    <div class="card reveal" style="margin-top:16px">
      <div class="pad">
        <h3 style="margin:0 0 12px 0">Account Information</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <div class="muted small">User ID</div>
            <div style="font-weight:800"><?= (int)$user['user_id'] ?></div>
          </div>
          <div>
            <div class="muted small">Role</div>
            <div style="font-weight:800"><?= e(ucfirst($user['role'])) ?></div>
          </div>
          <div>
            <div class="muted small">Member Since</div>
            <div style="font-weight:800"><?= e(date('M d, Y', strtotime($user['created_at']))) ?></div>
          </div>
          <div>
            <div class="muted small">Status</div>
            <div style="font-weight:800;color:#4caf50">Active</div>
          </div>
        </div>
      </div>
    </div>

    <div style="margin-top:16px">
      <a href="<?= e(base_url('orders.php')) ?>" class="btn">View My Orders</a>
    </div>
  </div>
</section>
<?php include __DIR__ . '/app/partials/footer.php'; ?>
