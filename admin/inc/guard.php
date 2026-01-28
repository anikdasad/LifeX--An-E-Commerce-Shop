<?php
require_once __DIR__ . '/../../app/helpers.php';
require_role(['admin','employee']);

function admin_active(string $file): string {
  $cur = basename($_SERVER['PHP_SELF'] ?? '');
  return $cur === $file ? 'active' : '';
}
