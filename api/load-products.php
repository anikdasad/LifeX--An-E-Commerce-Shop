<?php
require_once __DIR__ . '/_render.php';

$offset = max(0, (int)($_GET['offset'] ?? 0));
$limit  = max(1, min(24, (int)($_GET['limit'] ?? 12)));
$category = (int)($_GET['category'] ?? 0);
$q = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'newest';

$where = "p.is_active=1";
$params = [];

if ($category > 0) {
  $where .= " AND p.category_id=?";
  $params[] = $category;
}
if ($q !== '') {
  $where .= " AND (p.name LIKE ? OR p.description LIKE ?)";
  $params[] = "%$q%";
  $params[] = "%$q%";
}

// Determine ORDER BY clause based on sort parameter
$orderBy = "p.created_at DESC"; // default
switch ($sort) {
  case 'oldest':
    $orderBy = "p.created_at ASC";
    break;
  case 'price_asc':
    $orderBy = "p.price ASC";
    break;
  case 'price_desc':
    $orderBy = "p.price DESC";
    break;
  case 'name_asc':
    $orderBy = "p.name ASC";
    break;
  case 'name_desc':
    $orderBy = "p.name DESC";
    break;
  default:
    $orderBy = "p.created_at DESC";
}

$sql = "
  SELECT p.*, c.name AS category_name
  FROM products p
  LEFT JOIN categories c ON c.category_id = p.category_id
  WHERE $where
  ORDER BY $orderBy
  LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

echo json_encode([
  'ok' => true,
  'count' => count($rows),
  'html' => render_cards($rows),
], JSON_UNESCAPED_UNICODE);
