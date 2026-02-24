<?php
/**
 * Invoice PDF Export
 * Generates a clean PDF-ready invoice page
 * @package InspireShoes
 * @version 1.1
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

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
    <title>Invoice <?php echo generateInvoiceNumber($invoice['id']); ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size:13px; color:#222; background:#f0f0f0; }
        .page { max-width:800px; margin:20px auto; background:#fff; padding:50px; box-shadow:0 4px 20px rgba(0,0,0,0.15); }

        /* Header */
        .inv-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:40px; padding-bottom:25px; border-bottom:3px solid #1a2e4a; }
        .company-info h1 { font-size:26px; color:#1a2e4a; margin-bottom:4px; }
        .company-info h1 span { color:#f5a623; }
        .company-info p { color:#666; font-size:12px; line-height:1.7; }
        .inv-badge { text-align:right; }
        .inv-badge .inv-title { font-size:32px; font-weight:900; color:#1a2e4a; letter-spacing:2px; }
        .inv-badge .inv-num { font-size:16px; color:#f5a623; font-weight:700; }
        .inv-badge .inv-status { display:inline-block; padding:4px 14px; border-radius:20px; font-size:12px; font-weight:700; margin-top:6px; }
        .status-Paid      { background:#d4edda; color:#155724; }
        .status-Unpaid    { background:#fff3cd; color:#856404; }
        .status-Cancelled { background:#f8d7da; color:#721c24; }

        /* Info Grid */
        .inv-info { display:grid; grid-template-columns:1fr 1fr; gap:30px; margin-bottom:35px; }
        .inv-info-box h4 { font-size:11px; text-transform:uppercase; letter-spacing:1px; color:#999; margin-bottom:10px; border-bottom:1px solid #eee; padding-bottom:6px; }
        .inv-info-box p { margin-bottom:5px; font-size:13px; line-height:1.6; }
        .inv-info-box strong { color:#1a2e4a; }

        /* Table */
        table { width:100%; border-collapse:collapse; margin-bottom:30px; }
        thead tr { background:#1a2e4a; color:#fff; }
        thead th { padding:12px 14px; text-align:left; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; }
        tbody tr:nth-child(even) { background:#f9f9f9; }
        tbody td { padding:11px 14px; border-bottom:1px solid #eee; }
        tfoot td { padding:10px 14px; font-weight:600; }
        .text-right { text-align:right; }

        /* Totals */
        .totals-section { display:flex; justify-content:flex-end; margin-bottom:30px; }
        .totals-box { width:280px; }
        .total-line { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #eee; font-size:13px; }
        .total-line:last-child { border-bottom:none; }
        .total-line.grand { font-size:16px; font-weight:900; color:#1a2e4a; border-top:2px solid #1a2e4a; padding-top:12px; margin-top:4px; }

        /* Footer */
        .inv-footer { text-align:center; margin-top:40px; padding-top:20px; border-top:1px solid #eee; color:#999; font-size:11px; line-height:2; }
        .inv-footer .thank { font-size:14px; color:#1a2e4a; font-weight:700; }

        /* Notes */
        .notes-box { background:#fffbf0; border-left:4px solid #f5a623; padding:12px 16px; margin-bottom:25px; border-radius:0 8px 8px 0; }
        .notes-box strong { color:#1a2e4a; }

        /* Buttons - hidden on print */
        .action-bar { position:fixed; top:20px; right:20px; display:flex; gap:10px; z-index:100; }
        .btn-pdf { padding:12px 22px; border:none; border-radius:8px; cursor:pointer; font-size:14px; font-weight:600; display:flex; align-items:center; gap:8px; }
        .btn-print { background:#1a2e4a; color:#fff; }
        .btn-back  { background:#6c757d; color:#fff; text-decoration:none; }

        @media print {
            body { background:#fff; }
            .page { margin:0; padding:30px; box-shadow:none; max-width:100%; }
            .action-bar { display:none; }
        }
    </style>
</head>
<body>

<!-- Action Buttons -->
<div class="action-bar">
    <a href="<?php echo BASE_URL; ?>/invoices/view.php?id=<?php echo h((string)$id); ?>" class="btn-pdf btn-back">
        ← Back
    </a>
    <button class="btn-pdf btn-print" onclick="window.print()">
        🖨 Print / Save PDF
    </button>
</div>

<div class="page">

    <!-- Header -->
    <div class="inv-header">
        <div class="company-info">
            <h1>👟 Inspire <span>Shoes</span></h1>
            <p>
                Block 1, Near Gol Chowk<br>
                Sargodha, Pakistan<br>
                Phone: +92 (555) 123-4567<br>
                Email: info@inspireshoes.pk
            </p>
        </div>
        <div class="inv-badge">
            <div class="inv-title">INVOICE</div>
            <div class="inv-num"><?php echo generateInvoiceNumber($invoice['id']); ?></div>
            <div>
                <span class="inv-status status-<?php echo h($invoice['status']); ?>">
                    <?php echo h($invoice['status']); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Invoice & Customer Info -->
    <div class="inv-info">
        <div class="inv-info-box">
            <h4>Invoice Details</h4>
            <p><strong>Invoice #:</strong> <?php echo generateInvoiceNumber($invoice['id']); ?></p>
            <p><strong>Date:</strong> <?php echo formatDate($invoice['created_at']); ?></p>
            <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($invoice['created_at'])); ?></p>
            <p><strong>Served by:</strong> <?php echo h($invoice['user_name'] ?? '-'); ?></p>
        </div>
        <div class="inv-info-box">
            <h4>Bill To</h4>
            <p><strong><?php echo h($invoice['customer_name'] ?? 'Walk-in Customer'); ?></strong></p>
            <?php if (!empty($invoice['customer_phone'])): ?>
                <p>📞 <?php echo h($invoice['customer_phone']); ?></p>
            <?php endif; ?>
            <?php if (!empty($invoice['customer_email'])): ?>
                <p>✉ <?php echo h($invoice['customer_email']); ?></p>
            <?php endif; ?>
            <?php if (!empty($invoice['customer_address'])): ?>
                <p>📍 <?php echo h($invoice['customer_address']); ?><?php echo !empty($invoice['customer_city']) ? ', ' . h($invoice['customer_city']) : ''; ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Items Table -->
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>Size</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><strong><?php echo h($item['product_name']); ?></strong></td>
                    <td><?php echo h($item['size'] ?? '-'); ?></td>
                    <td class="text-right">Rs. <?php echo number_format($item['unit_price'], 2); ?></td>
                    <td class="text-right"><?php echo h((string)$item['quantity']); ?></td>
                    <td class="text-right"><strong>Rs. <?php echo number_format($item['line_total'], 2); ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals-section">
        <div class="totals-box">
            <div class="total-line">
                <span>Subtotal</span>
                <span>Rs. <?php echo number_format($invoice['subtotal'], 2); ?></span>
            </div>
            <div class="total-line">
                <span>Tax (<?php echo h((string)$invoice['tax_rate']); ?>%)</span>
                <span>Rs. <?php echo number_format($invoice['tax_amount'], 2); ?></span>
            </div>
            <?php if ($invoice['discount'] > 0): ?>
                <div class="total-line">
                    <span>Discount</span>
                    <span>- Rs. <?php echo number_format($invoice['discount'], 2); ?></span>
                </div>
            <?php endif; ?>
            <div class="total-line grand">
                <span>Grand Total</span>
                <span>Rs. <?php echo number_format($invoice['grand_total'], 2); ?></span>
            </div>
        </div>
    </div>

    <!-- Notes -->
    <?php if (!empty($invoice['notes'])): ?>
        <div class="notes-box">
            <strong>Notes:</strong> <?php echo h($invoice['notes']); ?>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="inv-footer">
        <p class="thank">Thank you for shopping at Inspire Shoes!</p>
        <p>Items can be returned within 30 days with this invoice.</p>
        <p>Please keep this invoice for your records and warranty purposes.</p>
        <p style="margin-top:10px;">© <?php echo date('Y'); ?> Inspire Shoes — All Rights Reserved</p>
    </div>

</div>

<script>
// Auto-prompt print dialog if ?print=1 in URL
if (new URLSearchParams(window.location.search).get('print') === '1') {
    window.onload = () => window.print();
}
</script>

</body>
</html>
