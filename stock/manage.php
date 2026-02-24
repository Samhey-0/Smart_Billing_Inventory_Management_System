<?php
/**
 * Stock Management Page
 * Manually adjust stock with reason log
 * @package InspireShoes
 * @version 1.1
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin(); // ✅ Admin only - staff cannot manage stock

$page_title = 'Stock Management';
$error = '';

// Handle stock adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_stock'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $type       = $_POST['adjustment_type'] ?? ''; // 'add' or 'subtract'
        $qty        = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
        $reason     = trim(filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_SPECIAL_CHARS));

        if (!$product_id || !$qty || $qty < 1) {
            $error = 'Please select a product and enter a valid quantity.';
        } elseif (empty($reason)) {
            $error = 'Please enter a reason for the adjustment.';
        } elseif (!in_array($type, ['add', 'subtract'])) {
            $error = 'Invalid adjustment type.';
        } else {
            // Get current stock
            $stmt = $pdo->prepare("SELECT stock_qty, name FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            if (!$product) {
                $error = 'Product not found.';
            } elseif ($type === 'subtract' && $product['stock_qty'] < $qty) {
                $error = 'Cannot subtract more than current stock (' . $product['stock_qty'] . ' units).';
            } else {
                $new_qty = $type === 'add'
                    ? $product['stock_qty'] + $qty
                    : $product['stock_qty'] - $qty;

                $pdo->beginTransaction();
                try {
                    // Update stock
                    $stmt = $pdo->prepare("UPDATE products SET stock_qty = ? WHERE id = ?");
                    $stmt->execute([$new_qty, $product_id]);

                    // Log the adjustment
                    $stmt = $pdo->prepare("
                        INSERT INTO stock_log (product_id, user_id, adjustment_type, quantity, reason, stock_before, stock_after)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $product_id,
                        $_SESSION['user_id'],
                        $type,
                        $qty,
                        $reason,
                        $product['stock_qty'],
                        $new_qty
                    ]);

                    $pdo->commit();
                    setFlashMessage('success', 'Stock adjusted successfully for "' . $product['name'] . '".');
                    header('Location: ' . BASE_URL . '/stock/manage.php');
                    exit();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Failed to adjust stock: ' . $e->getMessage();
                }
            }
        }
    }
}

// Get all products
$products = $pdo->query("SELECT id, name, brand, stock_qty FROM products ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get stock log
$stockLog = $pdo->query("
    SELECT sl.*, p.name AS product_name, u.username
    FROM stock_log sl
    JOIN products p ON sl.product_id = p.id
    JOIN users u ON sl.user_id = u.id
    ORDER BY sl.created_at DESC
    LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);

$csrf_token = csrfToken();
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-boxes"></i> Stock Management</h1>
    <a href="<?php echo BASE_URL; ?>/products/list.php" class="btn btn-secondary">
        <i class="fas fa-box"></i> View All Products
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo h($error); ?></div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:1fr 2fr; gap:20px;">

    <!-- Adjustment Form -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-edit"></i> Adjust Stock</h3></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">

                <div class="form-group">
                    <label for="product_id">Product *</label>
                    <select id="product_id" name="product_id" class="form-control" required onchange="updateCurrentStock(this)">
                        <option value="">-- Select Product --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?php echo h((string)$p['id']); ?>"
                                    data-stock="<?php echo h((string)$p['stock_qty']); ?>">
                                <?php echo h($p['name']); ?> (<?php echo h($p['brand']); ?>) — Stock: <?php echo h((string)$p['stock_qty']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="currentStockDisplay" style="display:none;">
                    <div style="padding:10px; background:var(--light-gray); border-radius:8px; text-align:center;">
                        <strong>Current Stock:</strong>
                        <span id="currentStockValue" style="font-size:24px; color:var(--primary); display:block; font-weight:700;">0</span>
                        units
                    </div>
                </div>

                <div class="form-group">
                    <label>Adjustment Type *</label>
                            <div style="display:flex; gap:15px; align-items:center;">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="radio" name="adjustment_type" value="add" checked style="margin:0;">
                            <span class="badge badge-success" style="font-size:13px; padding:8px 15px;">
                            <i class="fas fa-plus"></i> Add Stock
                            </span>
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="radio" name="adjustment_type" value="subtract" style="margin:0;">
                            <span class="badge badge-danger" style="font-size:13px; padding:8px 15px;">
            <i class="fas fa-minus"></i> Remove Stock
        </span>
    </label>
</div>
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input type="number" id="quantity" name="quantity" class="form-control" min="1" required>
                </div>

                <div class="form-group">
                    <label for="reason">Reason *</label>
                    <select id="reason_preset" class="form-control" onchange="setReason(this.value)" style="margin-bottom:8px;">
                        <option value="">-- Quick Reason --</option>
                        <option value="New stock received">New stock received</option>
                        <option value="Damaged goods">Damaged goods</option>
                        <option value="Stock count correction">Stock count correction</option>
                        <option value="Returned by customer">Returned by customer</option>
                        <option value="Transferred to another branch">Transferred to another branch</option>
                        <option value="Lost/stolen">Lost/stolen</option>
                    </select>
                    <input type="text" id="reason" name="reason" class="form-control" placeholder="Or type a custom reason..." required>
                </div>

                <button type="submit" name="adjust_stock" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-save"></i> Apply Adjustment
                </button>
            </form>
        </div>
    </div>

    <!-- Current Stock Levels -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-warehouse"></i> Current Stock Levels</h3></div>
        <div class="card-body">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Brand</th>
                            <th>Stock</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td><strong><?php echo h($p['name']); ?></strong></td>
                                <td><?php echo h($p['brand']); ?></td>
                                <td><?php echo h((string)$p['stock_qty']); ?></td>
                                <td>
                                    <?php if ($p['stock_qty'] == 0): ?>
                                        <span class="badge badge-danger">Out of Stock</span>
                                    <?php elseif ($p['stock_qty'] <= 10): ?>
                                        <span class="badge badge-warning">Low Stock</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">In Stock</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Stock Adjustment Log -->
<div class="card" style="margin-top:20px;">
    <div class="card-header"><h3><i class="fas fa-history"></i> Adjustment Log</h3></div>
    <div class="card-body">
        <?php if (empty($stockLog)): ?>
            <div class="empty-state"><i class="fas fa-history"></i><p>No adjustments yet.</p></div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Qty</th>
                            <th>Before</th>
                            <th>After</th>
                            <th>Reason</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stockLog as $log): ?>
                            <tr>
                                <td><?php echo formatDateTime($log['created_at']); ?></td>
                                <td><strong><?php echo h($log['product_name']); ?></strong></td>
                                <td>
                                    <?php if ($log['adjustment_type'] === 'add'): ?>
                                        <span class="badge badge-success"><i class="fas fa-plus"></i> Added</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><i class="fas fa-minus"></i> Removed</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo h((string)$log['quantity']); ?></td>
                                <td><?php echo h((string)$log['stock_before']); ?></td>
                                <td><strong><?php echo h((string)$log['stock_after']); ?></strong></td>
                                <td><?php echo h($log['reason']); ?></td>
                                <td><?php echo h($log['username']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function updateCurrentStock(select) {
    const option = select.options[select.selectedIndex];
    const stock  = option.dataset.stock;
    const display = document.getElementById('currentStockDisplay');
    if (select.value) {
        document.getElementById('currentStockValue').textContent = stock;
        display.style.display = 'block';
    } else {
        display.style.display = 'none';
    }
}
function setReason(val) {
    if (val) document.getElementById('reason').value = val;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
