<?php
/**
 * Settings Page - Admin Only
 * @package InspireShoes
 * @version 1.3
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$page_title = 'Settings';
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $fields = [
            'shop_name', 'shop_tagline', 'shop_address', 'shop_city',
            'shop_phone', 'shop_email', 'shop_whatsapp',
            'tax_rate', 'currency_symbol', 'invoice_prefix',
            'low_stock_alert', 'receipt_footer'
        ];

        foreach ($fields as $field) {
            $value = trim($_POST[$field] ?? '');
            updateSetting($field, $value);
        }

        // Update CURRENCY_SYMBOL constant for current request
        $success = 'Settings saved successfully!';
    }
}

$s = getAllSettings();
$csrf_token = csrfToken();
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-cog"></i> Settings</h1>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo h($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo h($error); ?></div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

        <!-- Shop Information -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-store"></i> Shop Information</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Shop Name</label>
                    <input type="text" name="shop_name" class="form-control"
                           value="<?php echo h($s['shop_name'] ?? 'Inspire Shoes'); ?>">
                    <small class="text-muted">Appears on invoices, receipts and PDF exports</small>
                </div>
                <div class="form-group">
                    <label>Tagline</label>
                    <input type="text" name="shop_tagline" class="form-control"
                           value="<?php echo h($s['shop_tagline'] ?? 'Quality Footwear'); ?>">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="shop_address" class="form-control"
                           value="<?php echo h($s['shop_address'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="shop_city" class="form-control"
                           value="<?php echo h($s['shop_city'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="shop_phone" class="form-control"
                           value="<?php echo h($s['shop_phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="shop_email" class="form-control"
                           value="<?php echo h($s['shop_email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>WhatsApp Number <small class="text-muted">(for sending receipts)</small></label>
                    <input type="text" name="shop_whatsapp" class="form-control"
                           value="<?php echo h($s['shop_whatsapp'] ?? ''); ?>"
                           placeholder="e.g. 03001234567">
                </div>
            </div>
        </div>

        <!-- Billing Settings -->
        <div>
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header">
                    <h3><i class="fas fa-file-invoice-dollar"></i> Billing Settings</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Tax Rate (%)</label>
                        <input type="number" name="tax_rate" class="form-control"
                               step="0.01" min="0" max="100"
                               value="<?php echo h($s['tax_rate'] ?? '16'); ?>">
                        <small class="text-muted">Currently <?php echo h($s['tax_rate'] ?? '16'); ?>% — applied to all new invoices</small>
                    </div>
                    <div class="form-group">
                        <label>Currency Symbol</label>
                        <input type="text" name="currency_symbol" class="form-control"
                               value="<?php echo h($s['currency_symbol'] ?? 'Rs. '); ?>">
                        <small class="text-muted">e.g. Rs. or PKR or ₨</small>
                    </div>
                    <div class="form-group">
                        <label>Invoice Number Prefix</label>
                        <input type="text" name="invoice_prefix" class="form-control"
                               value="<?php echo h($s['invoice_prefix'] ?? 'INV'); ?>">
                        <small class="text-muted">e.g. INV → INV-000001 or SHO → SHO-000001</small>
                    </div>
                    <div class="form-group">
                        <label>Low Stock Alert Threshold</label>
                        <input type="number" name="low_stock_alert" class="form-control"
                               min="1" value="<?php echo h($s['low_stock_alert'] ?? '10'); ?>">
                        <small class="text-muted">Products with stock at or below this number show as low stock</small>
                    </div>
                </div>
            </div>

            <!-- Receipt Settings -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-receipt"></i> Receipt Footer Message</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <textarea name="receipt_footer" class="form-control" rows="4"
                                  placeholder="Thank you message shown at bottom of every receipt..."><?php echo h($s['receipt_footer'] ?? ''); ?></textarea>
                        <small class="text-muted">Shown at the bottom of every printed receipt and PDF invoice</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview -->
    <div class="card" style="margin-top:20px;">
        <div class="card-header"><h3><i class="fas fa-eye"></i> Invoice Header Preview</h3></div>
        <div class="card-body">
            <div style="border:2px dashed #ddd; padding:20px; border-radius:8px; background:#fff;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <div>
                        <div style="font-size:22px; font-weight:700; color:#1a2e4a;">
                            👟 <?php echo h($s['shop_name'] ?? 'Inspire Shoes'); ?>
                        </div>
                        <div style="color:#f5a623; font-size:14px;"><?php echo h($s['shop_tagline'] ?? 'Quality Footwear'); ?></div>
                        <div style="color:#666; font-size:12px; margin-top:5px; line-height:1.8;">
                            <?php echo h($s['shop_address'] ?? ''); ?><br>
                            <?php echo h($s['shop_city'] ?? ''); ?><br>
                            <?php echo h($s['shop_phone'] ?? ''); ?>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:28px; font-weight:900; color:#1a2e4a; letter-spacing:2px;">INVOICE</div>
                        <div style="color:#f5a623; font-weight:700;"><?php echo h($s['invoice_prefix'] ?? 'INV'); ?>-000001</div>
                        <div style="font-size:12px; color:#666;">Tax: <?php echo h($s['tax_rate'] ?? '16'); ?>%</div>
                    </div>
                </div>
            </div>
            <small class="text-muted" style="display:block; margin-top:10px;">
                * This preview updates after you save. The invoice prefix and tax rate apply to new invoices only.
            </small>
        </div>
    </div>

    <div style="margin-top:20px;">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> Save All Settings
        </button>
    </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
