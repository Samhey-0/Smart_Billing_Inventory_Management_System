<?php
/**
 * Add Product Page - v1.2 with Supplier & Purchase Price
 * @package InspireShoes
 * @version 1.2
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin(); // ✅ Admin only - staff cannot add products

$page_title = 'Add Product';
$error = '';

// Get suppliers for dropdown
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $name           = trim(filter_input(INPUT_POST, 'name',          FILTER_SANITIZE_SPECIAL_CHARS));
        $brand          = trim(filter_input(INPUT_POST, 'brand',         FILTER_SANITIZE_SPECIAL_CHARS));
        $description    = trim(filter_input(INPUT_POST, 'description',   FILTER_SANITIZE_SPECIAL_CHARS));
        $size           = trim(filter_input(INPUT_POST, 'size',          FILTER_SANITIZE_SPECIAL_CHARS));
        $color          = trim(filter_input(INPUT_POST, 'color',         FILTER_SANITIZE_SPECIAL_CHARS));
        $price          = filter_input(INPUT_POST, 'price',          FILTER_VALIDATE_FLOAT);
        $purchase_price = filter_input(INPUT_POST, 'purchase_price', FILTER_VALIDATE_FLOAT) ?: 0;
        $stock_qty      = filter_input(INPUT_POST, 'stock_qty',      FILTER_VALIDATE_INT);
        $supplier_name  = trim(filter_input(INPUT_POST, 'supplier_name', FILTER_SANITIZE_SPECIAL_CHARS));
        $supplier_phone = trim(filter_input(INPUT_POST, 'supplier_phone',FILTER_SANITIZE_SPECIAL_CHARS));

        if (empty($name) || empty($brand) || empty($size) || empty($color)) {
            $error = 'Please fill in all required fields.';
        } elseif ($price === false || $price < 0) {
            $error = 'Please enter a valid selling price.';
        } elseif ($stock_qty === false || $stock_qty < 0) {
            $error = 'Please enter a valid stock quantity.';
        } else {
            $image_path = null;
            if (!empty($_FILES['image']['name'])) {
                $uploadDir    = __DIR__ . '/../assets/uploads/products/';
                $uploadResult = uploadFile($_FILES['image'], $uploadDir);
                if ($uploadResult['success']) {
                    $image_path = $uploadResult['filename'];
                } else {
                    $error = $uploadResult['error'];
                }
            }

            if (empty($error)) {
                $stmt = $pdo->prepare("
                    INSERT INTO products (name, description, brand, size, color, price, purchase_price, stock_qty, image_path, supplier_name, supplier_phone)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if ($stmt->execute([$name, $description, $brand, $size, $color, $price, $purchase_price, $stock_qty, $image_path, $supplier_name, $supplier_phone])) {
                    setFlashMessage('success', 'Product added successfully!');
                    header('Location: ' . BASE_URL . '/products/list.php');
                    exit();
                } else {
                    $error = 'Failed to add product.';
                }
            }
        }
    }
}

$csrf_token = csrfToken();
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Add Product</h1>
    <a href="<?php echo BASE_URL; ?>/products/list.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Products
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo h($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div>
                    <h4 style="color:var(--primary); margin-bottom:15px; padding-bottom:8px; border-bottom:2px solid var(--accent);">Product Details</h4>

                    <div class="form-group">
                        <label>Product Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Brand *</label>
                        <input type="text" name="brand" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Size *</label>
                        <select name="size" class="form-control" required>
                            <option value="">Select Size</option>
                            <?php for ($i = 36; $i <= 48; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Color *</label>
                        <input type="text" name="color" class="form-control" required placeholder="e.g. Black/White">
                    </div>
                    <div class="form-group">
                        <label>Product Image</label>
                        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <small class="text-muted">Max 2MB — JPG, PNG, WEBP</small>
                    </div>
                </div>

                <div>
                    <h4 style="color:var(--primary); margin-bottom:15px; padding-bottom:8px; border-bottom:2px solid var(--accent);">Pricing & Stock</h4>

                    <div class="form-group">
                        <label>Purchase Price (Rs.) — Cost from supplier</label>
                        <input type="number" name="purchase_price" class="form-control" step="0.01" min="0" id="pp"
                               placeholder="0.00" oninput="calcMargin()">
                    </div>
                    <div class="form-group">
                        <label>Selling Price (Rs.) *</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" required id="sp"
                               placeholder="0.00" oninput="calcMargin()">
                    </div>
                    <div id="marginBox" style="display:none; padding:12px; background:var(--light-gray); border-radius:8px; margin-bottom:15px; text-align:center;">
                        <span style="font-size:13px; color:var(--gray);">Profit Margin:</span>
                        <span id="marginVal" style="font-size:22px; font-weight:700; color:var(--primary); display:block;"></span>
                    </div>
                    <div class="form-group">
                        <label>Stock Quantity *</label>
                        <input type="number" name="stock_qty" class="form-control" min="0" required>
                    </div>

                    <h4 style="color:var(--primary); margin:20px 0 15px; padding-bottom:8px; border-bottom:2px solid var(--accent);">Supplier Info</h4>

                    <?php if (!empty($suppliers)): ?>
                    <div class="form-group">
                        <label>Quick Fill from Saved Supplier</label>
                        <select class="form-control" onchange="fillSupplier(this)">
                            <option value="">-- Select saved supplier --</option>
                            <?php foreach ($suppliers as $sup): ?>
                                <option value="<?php echo h($sup['name']); ?>"
                                        data-phone="<?php echo h($sup['phone'] ?? ''); ?>">
                                    <?php echo h($sup['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Supplier Name</label>
                        <input type="text" name="supplier_name" id="supplier_name" class="form-control" placeholder="e.g. Ali Traders">
                    </div>
                    <div class="form-group">
                        <label>Supplier Phone</label>
                        <input type="text" name="supplier_phone" id="supplier_phone" class="form-control" placeholder="03001234567">
                    </div>
                </div>
            </div>

            <div style="margin-top:20px; display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Add Product
                </button>
                <a href="<?php echo BASE_URL; ?>/products/list.php" class="btn btn-secondary btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function calcMargin() {
    const pp = parseFloat(document.getElementById('pp').value) || 0;
    const sp = parseFloat(document.getElementById('sp').value) || 0;
    const box = document.getElementById('marginBox');
    const val = document.getElementById('marginVal');
    if (sp > 0 && pp > 0) {
        const margin = ((sp - pp) / sp * 100).toFixed(1);
        const profit = (sp - pp).toFixed(2);
        val.textContent = margin + '% (Rs. ' + profit + ' per unit)';
        val.style.color = margin >= 20 ? '#27ae60' : (margin >= 10 ? '#f39c12' : '#e74c3c');
        box.style.display = 'block';
    } else {
        box.style.display = 'none';
    }
}
function fillSupplier(sel) {
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('supplier_name').value  = sel.value;
    document.getElementById('supplier_phone').value = opt.dataset.phone || '';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
