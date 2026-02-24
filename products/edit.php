<?php
/**
 * Edit Product Page
 * @package InspireShoes
 * @version 1.0
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin(); // ✅ Admin only - staff cannot edit products

$page_title = 'Edit Product';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    setFlashMessage('error', 'Invalid product ID.');
    header('Location: ' . BASE_URL . '/products/list.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    setFlashMessage('error', 'Product not found.');
    header('Location: ' . BASE_URL . '/products/list.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $name        = trim(filter_input(INPUT_POST, 'name',        FILTER_SANITIZE_SPECIAL_CHARS));
        $brand       = trim(filter_input(INPUT_POST, 'brand',       FILTER_SANITIZE_SPECIAL_CHARS));
        $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS));
        $size        = trim(filter_input(INPUT_POST, 'size',        FILTER_SANITIZE_SPECIAL_CHARS));
        $color       = trim(filter_input(INPUT_POST, 'color',       FILTER_SANITIZE_SPECIAL_CHARS));
        $price       = filter_input(INPUT_POST, 'price',     FILTER_VALIDATE_FLOAT);
        $stock_qty   = filter_input(INPUT_POST, 'stock_qty', FILTER_VALIDATE_INT);

        if (empty($name) || empty($brand) || empty($size) || empty($color)) {
            $error = 'Please fill in all required fields.';
        } elseif ($price === false || $price < 0) {
            $error = 'Please enter a valid price.';
        } elseif ($stock_qty === false || $stock_qty < 0) {
            $error = 'Please enter a valid stock quantity.';
        } else {
            $image_path = $product['image_path'];

            if (!empty($_FILES['image']['name'])) {
                $uploadDir    = __DIR__ . '/../assets/uploads/products/';
                $uploadResult = uploadFile($_FILES['image'], $uploadDir);

                if ($uploadResult['success']) {
                    if ($product['image_path']) {
                        deleteFile($uploadDir . $product['image_path']);
                    }
                    $image_path = $uploadResult['filename'];
                } else {
                    $error = $uploadResult['error'];
                }
            }

            if (empty($error)) {
                $stmt = $pdo->prepare("
                    UPDATE products
                    SET name = ?, description = ?, brand = ?, size = ?, color = ?,
                        price = ?, stock_qty = ?, image_path = ?
                    WHERE id = ?
                ");

                if ($stmt->execute([$name, $description, $brand, $size, $color, $price, $stock_qty, $image_path, $id])) {
                    setFlashMessage('success', 'Product updated successfully!');
                    header('Location: ' . BASE_URL . '/products/list.php');
                    exit();
                } else {
                    $error = 'Failed to update product. Please try again.';
                }
            }
        }
    }
}

$csrf_token = csrfToken();
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Edit Product</h1>
    <a href="<?php echo BASE_URL; ?>/products/list.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Products
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo h($error); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">

            <div class="form-group">
                <label for="name">Product Name *</label>
                <input type="text" id="name" name="name" class="form-control" value="<?php echo h($product['name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="brand">Brand *</label>
                <input type="text" id="brand" name="brand" class="form-control" value="<?php echo h($product['brand']); ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="3"><?php echo h($product['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="size">Size *</label>
                <select id="size" name="size" class="form-control" required>
                    <option value="">Select Size</option>
                    <?php for ($i = 38; $i <= 47; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $product['size'] == $i ? 'selected' : ''; ?>>
                            <?php echo $i; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="color">Color *</label>
                <input type="text" id="color" name="color" class="form-control" value="<?php echo h($product['color']); ?>" required>
            </div>

            <div class="form-group">
                <label for="price">Price ($) *</label>
                <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" value="<?php echo h((string)$product['price']); ?>" required>
            </div>

            <div class="form-group">
                <label for="stock_qty">Stock Quantity *</label>
                <input type="number" id="stock_qty" name="stock_qty" class="form-control" min="0" value="<?php echo h((string)$product['stock_qty']); ?>" required>
            </div>

            <div class="form-group">
                <label for="image">Product Image</label>
                <?php if (!empty($product['image_path'])): ?>
                    <div class="mb-2">
                        <!-- ✅ FIXED: Use ASSETS_URL constant instead of relative path -->
                        <img src="<?php echo ASSETS_URL; ?>/uploads/products/<?php echo h($product['image_path']); ?>"
                             alt="Current Product Image"
                             style="max-width:200px; border-radius:8px; border:1px solid var(--border);">
                        <p class="text-muted" style="font-size:12px; margin-top:5px;">Current image — upload a new one to replace it.</p>
                    </div>
                <?php endif; ?>
                <input type="file" id="image" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                <small class="text-muted">Max size: 2MB. Allowed formats: JPG, PNG, WEBP</small>
            </div>

            <div class="form-group" style="display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Product
                </button>
                <a href="<?php echo BASE_URL; ?>/products/list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
