<?php
/**
 * Supplier Management Page
 * @package InspireShoes
 * @version 1.2
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
if (!isAdmin()) {
    setFlashMessage('error', 'Access denied.');
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

$page_title = 'Suppliers';
$error = '';

// Handle add supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_supplier'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $name    = trim(filter_input(INPUT_POST, 'name',    FILTER_SANITIZE_SPECIAL_CHARS));
        $phone   = trim(filter_input(INPUT_POST, 'phone',   FILTER_SANITIZE_SPECIAL_CHARS));
        $email   = trim(filter_input(INPUT_POST, 'email',   FILTER_SANITIZE_EMAIL));
        $address = trim(filter_input(INPUT_POST, 'address', FILTER_SANITIZE_SPECIAL_CHARS));
        $edit_id = filter_input(INPUT_POST, 'edit_id', FILTER_VALIDATE_INT);

        if (empty($name)) {
            $error = 'Supplier name is required.';
        } else {
            if ($edit_id) {
                $stmt = $pdo->prepare("UPDATE suppliers SET name=?, phone=?, email=?, address=? WHERE id=?");
                $stmt->execute([$name, $phone, $email, $address, $edit_id]);
                setFlashMessage('success', 'Supplier updated successfully.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO suppliers (name, phone, email, address) VALUES (?,?,?,?)");
                $stmt->execute([$name, $phone, $email, $address]);
                setFlashMessage('success', 'Supplier added successfully.');
            }
            header('Location: ' . BASE_URL . '/admin/suppliers.php');
            exit();
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $did = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
    if ($did) {
        $pdo->prepare("DELETE FROM suppliers WHERE id=?")->execute([$did]);
        setFlashMessage('success', 'Supplier deleted.');
    }
    header('Location: ' . BASE_URL . '/admin/suppliers.php');
    exit();
}

// Edit mode
$editSupplier = null;
if (isset($_GET['edit'])) {
    $eid = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($eid) {
        $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id=?");
        $stmt->execute([$eid]);
        $editSupplier = $stmt->fetch();
    }
}

$suppliers = $pdo->query("SELECT s.*, COUNT(p.id) AS product_count FROM suppliers s LEFT JOIN products p ON p.supplier_name = s.name GROUP BY s.id ORDER BY s.name")->fetchAll(PDO::FETCH_ASSOC);

$csrf_token = csrfToken();
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-truck"></i> Supplier Management</h1>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo h($error); ?></div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:1fr 2fr; gap:20px;">

    <!-- Add/Edit Form -->
    <div class="card">
        <div class="card-header">
            <h3><?php echo $editSupplier ? 'Edit Supplier' : 'Add Supplier'; ?></h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">
                <?php if ($editSupplier): ?>
                    <input type="hidden" name="edit_id" value="<?php echo h((string)$editSupplier['id']); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Supplier Name *</label>
                    <input type="text" name="name" class="form-control" required
                           value="<?php echo h($editSupplier['name'] ?? ''); ?>" placeholder="e.g. Ali Traders">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?php echo h($editSupplier['phone'] ?? ''); ?>" placeholder="03001234567">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?php echo h($editSupplier['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="2"><?php echo h($editSupplier['address'] ?? ''); ?></textarea>
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="submit" name="save_supplier" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $editSupplier ? 'Update' : 'Add Supplier'; ?>
                    </button>
                    <?php if ($editSupplier): ?>
                        <a href="<?php echo BASE_URL; ?>/admin/suppliers.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Suppliers List -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-list"></i> All Suppliers</h3></div>
        <div class="card-body">
            <?php if (empty($suppliers)): ?>
                <div class="empty-state"><i class="fas fa-truck"></i><p>No suppliers yet.</p></div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Supplier</th>
                                <th>Phone</th>
                                <th>Products</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suppliers as $s): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo h($s['name']); ?></strong><br>
                                        <small class="text-muted"><?php echo h($s['email'] ?? ''); ?></small>
                                    </td>
                                    <td><?php echo h($s['phone'] ?? '-'); ?></td>
                                    <td><span class="badge badge-info"><?php echo h((string)$s['product_count']); ?></span></td>
                                    <td style="display:flex; gap:5px;">
                                        <a href="?edit=<?php echo h((string)$s['id']); ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?php echo h((string)$s['id']); ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Delete this supplier?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
