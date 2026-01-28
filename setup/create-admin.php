<?php
require_once __DIR__ . '/../app/helpers.php';

$email = 'admin@lifex.test';
$pass  = 'Admin@123';
$user  = 'Admin';

$stmt = $pdo->prepare("SELECT user_id FROM users WHERE email=? LIMIT 1");
$stmt->execute([$email]);
if ($stmt->fetch()) {
  echo "<h3>Admin already exists.</h3>";
  echo "<p>Email: $email</p>";
  echo "<p>Now delete the /setup folder for security.</p>";
  exit;
}

$hash = password_hash($pass, PASSWORD_DEFAULT);
$pdo->prepare("INSERT INTO users (username,email,password_hash,role,created_at) VALUES (?,?,?,?,NOW())")
    ->execute([$user,$email,$hash,'admin']);

echo "<h2>✅ Admin created</h2>";
echo "<p>Email: <b>$email</b></p>";
echo "<p>Password: <b>$pass</b></p>";
echo "<p><b>IMPORTANT:</b> Delete the <code>/setup</code> folder now.</p>";
