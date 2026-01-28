<?php
require_once __DIR__ . '/app/helpers.php';
require_login();

$orderId = (int)($_GET['id'] ?? 0);
$u = current_user();

$stmt = $pdo->prepare("SELECT o.*, u.username, u.email FROM orders o LEFT JOIN users u ON u.user_id=o.user_id WHERE o.order_id=?");
$stmt->execute([$orderId]);
$o = $stmt->fetch();

if (!$o) { 
  http_response_code(404); 
  echo "Order not found"; 
  exit; 
}

// Permission check
$role = $u['role'] ?? '';
if ((int)$o['user_id'] !== (int)$u['user_id'] && !in_array($role, ['admin','employee'], true)) {
  http_response_code(403); 
  echo "Forbidden"; 
  exit;
}

$itemsStmt = $pdo->prepare("
  SELECT oi.*, p.name
  FROM order_items oi
  LEFT JOIN products p ON p.product_id = oi.product_id
  WHERE oi.order_id=?
");
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

$txnStmt = $pdo->prepare("
  SELECT payment_tx_id, gateway, status
  FROM payment_transactions
  WHERE order_id=?
  ORDER BY payment_tx_id DESC
  LIMIT 1
");
$txnStmt->execute([$orderId]);
$transaction = $txnStmt->fetch();

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Invoice_' . str_pad($orderId, 8, '0', STR_PAD_LEFT) . '_' . date('Y-m-d') . '.pdf"');
header('Pragma: public');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

// Generate PDF content
$html = '<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
    .container { max-width: 800px; margin: 0 auto; padding: 40px 20px; }
    .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #333; padding-bottom: 20px; }
    .header h1 { font-size: 28px; margin-bottom: 10px; }
    .invoice-info { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
    .info-box { padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .info-box h3 { font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 8px; }
    .info-box p { font-size: 14px; margin: 4px 0; }
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
    .items-table th { background: #f5f5f5; padding: 12px; text-align: left; font-weight: bold; border-bottom: 2px solid #333; }
    .items-table td { padding: 12px; border-bottom: 1px solid #ddd; }
    .items-table tr:last-child td { border-bottom: 2px solid #333; }
    .total-row { display: grid; grid-template-columns: 1fr 200px; gap: 20px; justify-content: flex-end; margin-bottom: 20px; }
    .total-box { text-align: right; }
    .total-box .label { font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 4px; }
    .total-box .amount { font-size: 24px; font-weight: bold; }
    .footer { text-align: center; color: #666; font-size: 12px; border-top: 1px solid #ddd; padding-top: 20px; margin-top: 40px; }
    .payment-info { background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>INVOICE</h1>
      <p style="color: #666; font-size: 14px;">Invoice #' . str_pad($o['order_id'], 6, '0', STR_PAD_LEFT) . '</p>
    </div>

    <div class="invoice-info">
      <div class="info-box">
        <h3>Bill To</h3>
        <p><strong>' . htmlspecialchars($o['username'] ?? 'Customer') . '</strong></p>
        <p>' . htmlspecialchars($o['email'] ?? '') . '</p>
      </div>
      <div class="info-box">
        <h3>Order Details</h3>
        <p><strong>Order Date:</strong> ' . date('M d, Y H:i', strtotime($o['created_at'])) . '</p>
        <p><strong>Status:</strong> ' . htmlspecialchars($o['status']) . '</p>
        <p><strong>Payment:</strong> ' . htmlspecialchars($o['payment_method']) . '</p>
      </div>
    </div>

    <div class="invoice-info">
      <div class="info-box">
        <h3>Shipping Address</h3>
        <p>' . htmlspecialchars($o['ship_name']) . '</p>
        <p>' . htmlspecialchars($o['ship_address']) . '</p>
        <p>' . htmlspecialchars($o['ship_city']) . '</p>
        <p>Phone: ' . htmlspecialchars($o['ship_phone']) . '</p>
      </div>
      <div class="info-box">
        <h3>Payment Info</h3>
        <p><strong>Status:</strong> ' . ucfirst($o['payment_status']) . '</p>
        <p><strong>Method:</strong> ' . htmlspecialchars($o['payment_method']) . '</p>';
        
if ($transaction) {
  $html .= '<p><strong>Transaction ID:</strong> ' . (int)$transaction['payment_tx_id'] . '</p>';
}

$html .= '  </div>
    </div>

    <table class="items-table">
      <thead>
        <tr>
          <th>Product</th>
          <th style="width: 100px; text-align: right;">Price</th>
          <th style="width: 80px; text-align: center;">Qty</th>
          <th style="width: 120px; text-align: right;">Subtotal</th>
        </tr>
      </thead>
      <tbody>';

$subtotal = 0;
foreach ($items as $it) {
  $sub = (float)$it['unit_price'] * (int)$it['quantity'];
  $subtotal += $sub;
  $html .= '<tr>
      <td>' . htmlspecialchars($it['name'] ?? '') . '</td>
      <td style="text-align: right;">৳' . number_format((float)$it['unit_price'], 2) . '</td>
      <td style="text-align: center;">' . (int)$it['quantity'] . '</td>
      <td style="text-align: right;">৳' . number_format($sub, 2) . '</td>
    </tr>';
}

$html .= '  </tbody>
    </table>

    <div class="total-row">
      <div></div>
      <div class="total-box">
        <div class="label">Total Amount</div>
        <div class="amount">৳' . number_format((float)$o['total_amount'], 2) . '</div>
      </div>
    </div>

    <div class="payment-info">
      <p style="font-size: 12px; margin: 0;"><strong>Payment Status:</strong> ' . (strtolower($o['payment_status']) === 'paid' ? '✓ PAID' : 'UNPAID') . '</p>
    </div>

    <div class="footer">
      <p>Thank you for your order! • LifeX Store</p>
      <p style="margin-top: 10px; font-size: 11px;">This is an electronically generated invoice. No signature required.</p>
    </div>
  </div>
</body>
</html>';

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Invoice_#' . str_pad($o['order_id'], 6, '0', STR_PAD_LEFT) . '.pdf"');
header('Pragma: public');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

// Use a simple PDF library or convert HTML to PDF
// For now, output as HTML that can be printed to PDF
echo $html;
?>
