<?php
/**
 * Create Invoice Page
 * POS-style invoice creation with cart functionality
 *
 * @package InspireShoes
 * @version 1.0
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$page_title = 'Create Invoice';
$error = '';

// Get customers for dropdown
$customers = $pdo->query("SELECT * FROM customers ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

// Get products for dropdown
$products = $pdo->query("SELECT * FROM products WHERE stock_qty > 0 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_invoice'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $customer_id = filter_input(INPUT_POST, 'customer_id', FILTER_VALIDATE_INT);
        $tax_rate    = filter_input(INPUT_POST, 'tax_rate',    FILTER_VALIDATE_FLOAT) ?: 16;
        $discount    = filter_input(INPUT_POST, 'discount',    FILTER_VALIDATE_FLOAT) ?: 0;
        $notes       = trim(filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_SPECIAL_CHARS));
        $items       = json_decode($_POST['cart_items'] ?? '[]', true);

        if (!$customer_id) {
            $error = 'Please select a customer.';
        } elseif (empty($items)) {
            $error = 'Please add at least one item to the invoice.';
        } else {
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += $item['quantity'] * $item['price'];
            }
            $tax_amount  = round($subtotal * ($tax_rate / 100), 2);
            $grand_total = $subtotal + $tax_amount - $discount;

            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    INSERT INTO invoices (customer_id, user_id, subtotal, tax_rate, tax_amount, discount, grand_total, status, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Unpaid', ?)
                ");
                $stmt->execute([$customer_id, $_SESSION['user_id'], $subtotal, $tax_rate, $tax_amount, $discount, $grand_total, $notes]);
                $invoice_id = $pdo->lastInsertId();

                foreach ($items as $item) {
                    $line_total = $item['quantity'] * $item['price'];
                    $stmt = $pdo->prepare("
                        INSERT INTO invoice_items (invoice_id, product_id, product_name, size, quantity, unit_price, line_total)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $invoice_id,
                        $item['id'],
                        $item['name'],
                        $item['size'],
                        $item['quantity'],
                        $item['price'],
                        $line_total
                    ]);

                    $stmt = $pdo->prepare("UPDATE products SET stock_qty = stock_qty - ? WHERE id = ?");
                    $stmt->execute([$item['quantity'], $item['id']]);
                }

                $pdo->commit();
                setFlashMessage('success', 'Invoice created successfully!');
                header('Location: ' . BASE_URL . '/invoices/view.php?id=' . $invoice_id);
                exit();

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Failed to create invoice: ' . $e->getMessage();
            }
        }
    }
}

$csrf_token = csrfToken();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Create Invoice</h1>
    <a href="<?php echo BASE_URL; ?>/invoices/list.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Invoices
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo h($error); ?>
    </div>
<?php endif; ?>

<div class="invoice-container">

    <!-- Customer Selection -->
    <div class="card">
        <div class="card-header"><h3>Customer Information</h3></div>
        <div class="card-body">
            <form method="POST" id="invoiceForm">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">
                <input type="hidden" name="cart_items" id="cartItems" value="">

                <div class="form-group">
                    <label for="customer_id">Select Customer *</label>
                    <select id="customer_id" name="customer_id" class="form-control" required>
                        <option value="">-- Select Customer --</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?php echo h((string)$customer['id']); ?>">
                                <?php echo h($customer['full_name']); ?> - <?php echo h($customer['phone'] ?? $customer['email'] ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
        </div>
    </div>

    <!-- Product Selection -->
    <div class="card">
        <div class="card-header"><h3>Add Products</h3></div>
        <div class="card-body">
            <div class="product-search">
                <select id="productSelect" class="form-control">
                    <option value="">-- Select Product --</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo h((string)$product['id']); ?>"
                                data-name="<?php echo h($product['name']); ?>"
                                data-price="<?php echo h((string)$product['price']); ?>"
                                data-size="<?php echo h($product['size'] ?? ''); ?>"
                                data-stock="<?php echo h((string)$product['stock_qty']); ?>">
                            <?php echo h($product['name']); ?>
                            <?php if (!empty($product['size'])): ?> - Size <?php echo h($product['size']); ?><?php endif; ?>
                            - Rs<?php echo h(number_format($product['price'], 2)); ?>
                            (Stock: <?php echo h((string)$product['stock_qty']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="addToCart" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add to Cart
                </button>
            </div>
        </div>
    </div>

    <!-- Cart -->
    <div class="card">
        <div class="card-header"><h3>Invoice Items</h3></div>
        <div class="card-body">
            <div class="table-container">
                <table id="cartTable">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Size</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="cartBody">
                        <tr id="emptyCart">
                            <td colspan="6" class="text-center text-muted">No items in cart yet</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Totals & Submit -->
    <div class="card">
        <div class="card-body">
            <div class="invoice-totals">
                <div class="form-group">
                    <label for="tax_rate">Tax Rate (%)</label>
                    <input type="number" id="tax_rate" name="tax_rate" class="form-control" value="16" min="0" step="0.1">
                </div>
                <div class="form-group">
                    <label for="discount">Discount (Rs)</label>
                    <input type="number" id="discount" name="discount" class="form-control" value="0" min="0" step="0.01">
                </div>

                <div class="totals-display">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span id="subtotalDisplay">Rs0.00</span>
                    </div>
                    <div class="total-row">
                        <span>Tax:</span>
                        <span id="taxDisplay">Rs0.00</span>
                    </div>
                    <div class="total-row">
                        <span>Discount:</span>
                        <span id="discountDisplay">-Rs0.00</span>
                    </div>
                    <div class="total-row grand-total">
                        <span>Grand Total:</span>
                        <span id="grandTotalDisplay">Rs0.00</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                </div>

                <button type="submit" name="create_invoice" class="btn btn-success btn-lg" style="width:100%;">
                    <i class="fas fa-save"></i> Create Invoice
                </button>
            </form>
        </div>
    </div>

</div><!-- end invoice-container -->

<style>
.invoice-container { display: grid; gap: 20px; }
.product-search { display: flex; gap: 10px; }
.product-search select { flex: 1; }
.invoice-totals { max-width: 400px; margin-left: auto; }
.totals-display { margin: 20px 0; padding: 15px; background: var(--light-gray); border-radius: 8px; }
.total-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border); }
.total-row:last-child { border-bottom: none; }
.total-row.grand-total { font-size: 18px; font-weight: bold; color: var(--primary); border-top: 2px solid var(--primary); margin-top: 10px; padding-top: 15px; }
</style>

<script>
let cart = [];

function updateCartDisplay() {
    const cartBody = document.getElementById('cartBody');
    if (cart.length === 0) {
        cartBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No items in cart yet</td></tr>';
        calculateTotals();
        return;
    }
    cartBody.innerHTML = '';
    cart.forEach((item, index) => {
        const lineTotal = item.quantity * item.price;
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong>${item.name}</strong></td>
            <td>${item.size || '-'}</td>
            <td>$${item.price.toFixed(2)}</td>
            <td>
                <input type="number" min="1" max="${item.stock}" value="${item.quantity}"
                       onchange="updateQuantity(${index}, this.value)"
                       style="width:65px; padding:4px; border:1px solid #ddd; border-radius:4px;">
            </td>
            <td><strong>$${lineTotal.toFixed(2)}</strong></td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeFromCart(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        cartBody.appendChild(row);
    });
    calculateTotals();
}

function updateQuantity(index, value) {
    let qty = parseInt(value);
    if (qty < 1) qty = 1;
    if (qty > cart[index].stock) qty = cart[index].stock;
    cart[index].quantity = qty;
    updateCartDisplay();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartDisplay();
}

function calculateTotals() {
    let subtotal = 0;
    cart.forEach(item => subtotal += item.quantity * item.price);
    const taxRate   = parseFloat(document.getElementById('tax_rate').value) || 0;
    const discount  = parseFloat(document.getElementById('discount').value) || 0;
    const tax       = subtotal * (taxRate / 100);
    const grandTotal = subtotal + tax - discount;
    document.getElementById('subtotalDisplay').textContent  = 'Rs' + subtotal.toFixed(2);
    document.getElementById('taxDisplay').textContent       = 'Rs' + tax.toFixed(2);
    document.getElementById('discountDisplay').textContent  = '-Rs' + discount.toFixed(2);
    document.getElementById('grandTotalDisplay').textContent = 'Rs' + grandTotal.toFixed(2);
}

document.getElementById('addToCart').addEventListener('click', function () {
    const select = document.getElementById('productSelect');
    const option = select.options[select.selectedIndex];
    if (!select.value) { alert('Please select a product'); return; }

    const product = {
        id:       parseInt(select.value),
        name:     option.dataset.name,
        price:    parseFloat(option.dataset.price),
        size:     option.dataset.size,
        stock:    parseInt(option.dataset.stock),
        quantity: 1
    };

    const existingIndex = cart.findIndex(i => i.id === product.id && i.size === product.size);
    if (existingIndex >= 0) {
        if (cart[existingIndex].quantity < cart[existingIndex].stock) {
            cart[existingIndex].quantity++;
        } else {
            alert('Maximum stock reached for this product');
            return;
        }
    } else {
        cart.push(product);
    }
    updateCartDisplay();
    select.value = '';
});

document.getElementById('tax_rate').addEventListener('input', calculateTotals);
document.getElementById('discount').addEventListener('input', calculateTotals);

document.getElementById('invoiceForm').addEventListener('submit', function (e) {
    if (cart.length === 0) {
        e.preventDefault();
        alert('Please add at least one item to the invoice');
        return;
    }
    if (!document.getElementById('customer_id').value) {
        e.preventDefault();
        alert('Please select a customer');
        return;
    }
    document.getElementById('cartItems').value = JSON.stringify(cart);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>