<?php
require_once __DIR__ . '/inc/guard.php';
require_role(['admin', 'employee']);

$range = $_GET['range'] ?? '30';
$days = (int)$range;
if (!in_array($days, [7,30,90,365], true)) $days = 30;

$from = (new DateTime())->modify("-{$days} days")->format('Y-m-d 00:00:00');

// Fetch sales data
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) s FROM orders WHERE payment_status='paid' AND created_at >= ?");
$stmt->execute([$from]);
$paidRevenue = (float)$stmt->fetch()['s'];

$stmt = $pdo->prepare("SELECT COUNT(*) c FROM orders WHERE created_at >= ?");
$stmt->execute([$from]);
$orderCount = (int)$stmt->fetch()['c'];

$statusBreakdown = $pdo->prepare("SELECT status, COUNT(*) c FROM orders WHERE created_at >= ? GROUP BY status ORDER BY c DESC");
$statusBreakdown->execute([$from]);
$statusBreakdown = $statusBreakdown->fetchAll();

$paymentBreakdown = $pdo->prepare("SELECT payment_method, COUNT(*) c FROM orders WHERE created_at >= ? GROUP BY payment_method ORDER BY c DESC");
$paymentBreakdown->execute([$from]);
$paymentBreakdown = $paymentBreakdown->fetchAll();

$topProducts = $pdo->prepare("
  SELECT p.product_id, p.name,
         SUM(oi.quantity) AS units,
         SUM(oi.quantity * oi.unit_price) AS sales
  FROM order_items oi
  JOIN products p ON p.product_id = oi.product_id
  JOIN orders o ON o.order_id = oi.order_id
  WHERE o.created_at >= ?
  GROUP BY p.product_id, p.name
  ORDER BY sales DESC
  LIMIT 10
");
$topProducts->execute([$from]);
$topProducts = $topProducts->fetchAll();

// Generate PDF HTML
$html = '<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
    .container { max-width: 1000px; margin: 0 auto; padding: 40px 20px; }
    .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #333; padding-bottom: 20px; }
    .header h1 { font-size: 28px; margin-bottom: 5px; }
    .header p { font-size: 14px; color: #666; }
    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 40px; }
    .kpi-box { border: 1px solid #ddd; border-radius: 5px; padding: 20px; text-align: center; }
    .kpi-box .label { font-size: 11px; color: #666; text-transform: uppercase; margin-bottom: 8px; }
    .kpi-box .value { font-size: 24px; font-weight: bold; }
    .section-title { font-size: 16px; font-weight: bold; margin-top: 30px; margin-bottom: 15px; border-bottom: 2px solid #333; padding-bottom: 10px; }
    .table { width: 100%; border-collapse: collapse; }
    .table th { background: #f5f5f5; padding: 10px; text-align: left; font-weight: bold; border-bottom: 2px solid #333; }
    .table td { padding: 10px; border-bottom: 1px solid #ddd; }
    .table tr:last-child td { border-bottom: 2px solid #333; }
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
    .footer { text-align: center; color: #666; font-size: 11px; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Sales Report</h1>
      <p>Last ' . $days . ' Days • Generated on ' . date('M d, Y H:i') . '</p>
    </div>

    <div class="kpi-row">
      <div class="kpi-box">
        <div class="label">Total Revenue (Paid)</div>
        <div class="value">৳' . number_format($paidRevenue, 0) . '</div>
      </div>
      <div class="kpi-box">
        <div class="label">Total Orders</div>
        <div class="value">' . $orderCount . '</div>
      </div>
      <div class="kpi-box">
        <div class="label">Avg Order Value</div>
        <div class="value">৳' . ($orderCount > 0 ? number_format($paidRevenue / $orderCount, 0) : '0') . '</div>
      </div>
      <div class="kpi-box">
        <div class="label">Generated At</div>
        <div class="value" style="font-size: 12px;">' . date('M d, Y') . '</div>
      </div>
    </div>

    <div class="two-col">
      <div>
        <h2 class="section-title">Order Status Breakdown</h2>
        <table class="table">
          <thead><tr><th>Status</th><th style="text-align:right">Count</th></tr></thead>
          <tbody>';

foreach ($statusBreakdown as $row) {
  $html .= '<tr><td>' . htmlspecialchars($row['status']) . '</td><td style="text-align:right">' . (int)$row['c'] . '</td></tr>';
}

$html .= '    </tbody>
        </table>
      </div>

      <div>
        <h2 class="section-title">Payment Methods</h2>
        <table class="table">
          <thead><tr><th>Method</th><th style="text-align:right">Count</th></tr></thead>
          <tbody>';

foreach ($paymentBreakdown as $row) {
  $html .= '<tr><td>' . htmlspecialchars($row['payment_method']) . '</td><td style="text-align:right">' . (int)$row['c'] . '</td></tr>';
}

$html .= '    </tbody>
        </table>
      </div>
    </div>

    <div>
      <h2 class="section-title">Top 10 Products</h2>
      <table class="table">
        <thead>
          <tr>
            <th>Product Name</th>
            <th style="text-align:right">Units Sold</th>
            <th style="text-align:right">Sales Amount</th>
          </tr>
        </thead>
        <tbody>';

foreach ($topProducts as $row) {
  $html .= '<tr>
        <td>' . htmlspecialchars($row['name']) . '</td>
        <td style="text-align:right">' . (int)$row['units'] . '</td>
        <td style="text-align:right">৳' . number_format((float)$row['sales'], 2) . '</td>
      </tr>';
}

$html .= '  </tbody>
      </table>
    </div>

    <div class="footer">
      <p>LifeX Admin Report • Confidential</p>
    </div>
  </div>
</body>
</html>';

// Set headers as HTML
header('Content-Type: text/html; charset=UTF-8');
header('Content-Disposition: attachment; filename="Sales_Report_' . $days . 'days_' . date('Y-m-d') . '.html"');
header('Cache-Control: max-age=0');

// Add print styles
$html = str_replace('</head>', '
<style>
  @media print {
    * { margin: 0; padding: 0; }
    body { background: white; }
    .no-print { display: none !important; }
  }
  @page {
    margin: 1cm;
  }
</style>
</head>', $html);

// Add print and view buttons
$printButton = '<div class="no-print" style="margin-bottom:20px;text-align:center;background:#f5f5f5;padding:15px;border-radius:5px">
  <button onclick="window.print()" style="padding:10px 20px;background:#0070f3;color:white;border:none;border-radius:5px;cursor:pointer;font-size:14px;margin-right:10px">🖨️ Print / Save as PDF</button>
  <button onclick="window.close()" style="padding:10px 20px;background:#666;color:white;border:none;border-radius:5px;cursor:pointer;font-size:14px">✕ Close</button>
</div>';

$html = str_replace('<body>', '<body>' . $printButton, $html);

echo $html;
?>
