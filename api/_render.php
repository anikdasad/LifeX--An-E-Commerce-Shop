<?php
require_once __DIR__ . '/../app/helpers.php';

function render_cards(array $products): string {
  ob_start();
  foreach ($products as $p) {
    include __DIR__ . '/../app/partials/product-card.php';
  }
  return ob_get_clean();
}
