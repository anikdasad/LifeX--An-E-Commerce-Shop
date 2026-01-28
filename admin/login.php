<?php
// Redirect to unified login page in admin mode
header('Location: ' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']) . '/app/auth/login.php?mode=admin');
exit;
?>
