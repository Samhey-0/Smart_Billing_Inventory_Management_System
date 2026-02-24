<?php
/**
 * Receipt Page - Printable receipt view
 * @package InspireShoes
 * @version 1.0
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// ✅ FIXED: Added login check - receipt should not be public
requireLogin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) die('Invalid invoice ID.');

$stmt = $pdo->prepare("
    SELECT i.*, c.full_name AS customer_name, c.email AS customer_email,
           c.phone AS customer_phone, c.address AS customer_address, c.city AS customer_city,
           u.username AS user_name
    FROM invoices i
    LEFT JOIN customers c ON i.customer_id = c.id
    LEFT JOIN users u ON i.user_id = u.id
    WHERE i.id = ?
");
$stmt->execute([$id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) die('Invoice not found.');

$stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo generateInvoiceNumber($invoice['id']); ?></title>

    <!-- ✅ FIXED: Use CSS_URL constant instead of relative path -->
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/print.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root { --primary: #1a2e4a; --accent: #f5a623; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.5; color: #333; background: #f5f5f5; padding: 20px; }
        .receipt { max-width: 800px; margin: 0 auto; background: #fff; padding: 40px; box-shadow: 0 2px 12px rgba(0,0,0,0.1); border-radius: 8px; }
        .receipt-header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid var(--primary); }
        .receipt-header h1 { font-size: 28px; color: var(--primary); margin-bottom: 5px; }
        .receipt-header h1 i { color: var(--accent); }
        .receipt-header .tagline { color: #666; font-size: 13px; }
        .receipt-info { display: flex; justify-content: space-between; margin-bottom: 30px; gap: 20px; }
        .receipt-info > div { flex: 1; }
        .receipt-info h4 { color: var(--primary); margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        .receipt-info p { margin-bottom: 5px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th { background: var(--primary); color: white; padding: 10px; text-align: left; font-size: 13px; }
        table td { padding: 10px; border-bottom: 1px solid #eee; font-size: 13px; }
        table td:last-child, table th:last-child { text-align: right; }
        .totals { max-width: 280px; margin-left: auto; margin-top: 10px; }
        .total-row { display: flex; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid #eee; }
        .total-row:last-child { border-bottom: none; }
        .total-row.grand-total { font-size: 17px; font-weight: bold; color: var(--primary); border-top: 2px solid var(--primary); margin-top: 8px; padding-top: 12px; }
        .receipt-footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
        .receipt-footer .thank-you { font-size: 16px; color: var(--primary); font-weight: bold; margin-bottom: 8px; }
        .print-btn { position: fixed; top: 20px; right: 20px; padding: 12px 24px; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.2); display: flex; align-items: center; gap: 8px; }
        .print-btn:hover { background: #2c4a6e; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .status-Paid { background: #d4edda; color: #155724; }
        .status-Unpaid { background: #fff3cd; color: #856404; }
        .status-Cancelled { background: #f8d7da; color: #721c24; }
        @media print {
            .print-btn { display: none; }
            body { background: #fff; padding: 0; }
            .receipt { box-shadow: none; border-radius: 0; padding: 20px; }
        }
    </style>
</head>
<body>

<button class="print-btn" onclick="window.print()">
    <i class="fas fa-print"></i> Print Receipt
</button>

<div class="receipt">

    <!-- Header -->
    <div class="receipt-header">
        <h1><i class="fas fa-shoe-prints"></i> Inspire Shoes</h1>
        <p class="tagline">Premium Footwear &amp; Accessories</p>
        <p>Block 1, Near Gol Chowk Sargodha</p>
        <p>Phone: +92 (555) 123-4567 | Email: info@inspireshoes.com</p>
    </div>

    <!-- Invoice & Customer Info -->
    <div class="receipt-info">
        <div>
            <h4>Invoice Details</h4>
            <p><strong>Invoice #:</strong> <?php echo generateInvoiceNumber($invoice['id']); ?></p>
            <p><strong>Date:</strong> <?php echo formatDate($invoice['created_at']); ?></p>
            <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($invoice['created_at'])); ?></p>
            <p><strong>Status:</strong>
                <span class="status-badge status-<?php echo h($invoice['status']); ?>">
                    <?php echo h($invoice['status']); ?>
                </span>
            </p>
            <p><strong>Served by:</strong> <?php echo h($invoice['user_name'] ?? '-'); ?></p>
        </div>
        <div>
            <h4>Customer Information</h4>
            <p><strong>Name:</strong> <?php echo h($invoice['customer_name'] ?? 'Walk-in Customer'); ?></p>
            <p><strong>Phone:</strong> <?php echo h($invoice['customer_phone'] ?? '-'); ?></p>
            <p><strong>Email:</strong> <?php echo h($invoice['customer_email'] ?? '-'); ?></p>
            <?php if (!empty($invoice['customer_address'])): ?>
                <p><strong>Address:</strong> <?php echo h($invoice['customer_address']); ?><?php echo !empty($invoice['customer_city']) ? ', ' . h($invoice['customer_city']) : ''; ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Items Table -->
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Size</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo h($item['product_name']); ?></td>
                    <td><?php echo h($item['size'] ?? '-'); ?></td>
                    <td><?php echo h((string)$item['quantity']); ?></td>
                    <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                    <td>$<?php echo number_format($item['line_total'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals">
        <div class="total-row">
            <span>Subtotal:</span>
            <span>$<?php echo number_format($invoice['subtotal'], 2); ?></span>
        </div>
        <div class="total-row">
            <span>Tax (<?php echo h((string)$invoice['tax_rate']); ?>%):</span>
            <span>$<?php echo number_format($invoice['tax_amount'], 2); ?></span>
        </div>
        <?php if ($invoice['discount'] > 0): ?>
            <div class="total-row">
                <span>Discount:</span>
                <span>-$<?php echo number_format($invoice['discount'], 2); ?></span>
            </div>
        <?php endif; ?>
        <div class="total-row grand-total">
            <span>Grand Total:</span>
            <span>$<?php echo number_format($invoice['grand_total'], 2); ?></span>
        </div>
    </div>

    <!-- Notes -->
    <?php if (!empty($invoice['notes'])): ?>
        <div style="margin-top:20px; padding:12px; background:#f9f9f9; border-radius:6px;">
            <strong>Notes:</strong> <?php echo h($invoice['notes']); ?>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="receipt-footer">
        <p class="thank-you">Thank you for shopping at Inspire Shoes!</p>
        <p>Products can be returned within 30 days with receipt.</p>
        <p>Please keep this receipt for warranty purposes.</p>
        <p style="margin-top:10px;">&copy; <?php echo date('Y'); ?> Inspire Shoes. All rights reserved.</p>
    </div>

</div>
</body>
</html>