<?php
require_once __DIR__ . '/_render.php';

$category = (int)($_GET['category'] ?? 0);
$q = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'newest'; // newest, oldest, price_asc, price_desc, name_asc, name_desc

$limit = 12;
$offset = 0;

$where = "p.is_active=1";
$params = [];

if ($category > 0) {
  $where .= " AND p.category_id=?";
  $params[] = $category;
}
if ($q !== '') {
  $where .= " AND (p.name LIKE ? OR COALESCE(p.description, '') LIKE ?)";
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

// Add LIMIT and OFFSET to params
$params[] = $limit;
$params[] = $offset;

$sql = "
  SELECT p.*, c.name AS category_name
  FROM products p
  LEFT JOIN categories c ON c.category_id = p.category_id
  WHERE $where
  ORDER BY $orderBy
  LIMIT ? OFFSET ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$countStmt = $pdo->prepare("SELECT COUNT(*) c FROM products p LEFT JOIN categories c ON c.category_id = p.category_id WHERE $where");
$countStmt->execute(array_slice($params, 0, -2));
$total = (int)$countStmt->fetch()['c'];

echo json_encode([
  'ok' => true,
  'html' => render_cards($rows),
  'limit' => $limit,
  'offset' => $limit,
  'hasMore' => $total > $limit,
], JSON_UNESCAPED_UNICODE);

