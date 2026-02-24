<?php
/**
 * Edit Customer Page
 * 
 * Edit existing customer
 * 
 * @package InspireShoes
 * @version 1.0
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Require login
requireLogin();

$page_title = 'Edit Customer';

// Get customer ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    setFlashMessage('error', 'Invalid customer ID.');
    header('Location: list.php');
    exit();
}

// Get customer
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    setFlashMessage('error', 'Customer not found.');
    header('Location: list.php');
    exit();
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SECURITY: CSRF token validation
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $full_name = trim(filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS));
        $address = trim(filter_input(INPUT_POST, 'address', FILTER_SANITIZE_SPECIAL_CHARS));
        $city = trim(filter_input(INPUT_POST, 'city', FILTER_SANITIZE_SPECIAL_CHARS));

        // Validation
        if (empty($full_name) || empty($email) || empty($phone)) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check if email already exists (excluding current customer)
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                $error = 'Email already exists.';
            } else {
                // Update customer
                $stmt = $pdo->prepare("
                    UPDATE customers 
                    SET full_name = ?, email = ?, phone = ?, address = ?, city = ?
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$full_name, $email, $phone, $address, $city, $id])) {
                    setFlashMessage('success', 'Customer updated successfully!');
                    header('Location: list.php');
                    exit();
                } else {
                    $error = 'Failed to update customer. Please try again.';
                }
            }
        }
    }
}

// Generate CSRF token
$csrf_token = csrfToken();

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Edit Customer</h1>
    <a href="list.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Customers
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo h($error); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">
            
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo h($customer['full_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo h($customer['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone *</label>
                <input type="text" id="phone" name="phone" class="form-control" value="<?php echo h($customer['phone']); ?>" required>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" class="form-control" rows="2"><?php echo h($customer['address'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city" class="form-control" value="<?php echo h($customer['city'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Customer
                </button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
