<?php
/**
 * Edit Expense Page
 * @package InspireShoes
 * @version 1.4
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$page_title = 'Edit Expense';
$error = '';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    setFlashMessage('error', 'Invalid expense ID.');
    header('Location: ' . BASE_URL . '/expenses/list.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ?");
$stmt->execute([$id]);
$expense = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$expense) {
    setFlashMessage('error', 'Expense not found.');
    header('Location: ' . BASE_URL . '/expenses/list.php');
    exit();
}

$defaultCategories = [
    'Rent', 'Electricity', 'Water', 'Gas',
    'Staff Salary', 'Staff Bonus',
    'Supplier Payment', 'Stock Purchase',
    'Transport / Delivery', 'Packaging',
    'Marketing / Advertising', 'Maintenance / Repair',
    'Office Supplies', 'Internet / Phone',
    'Tax / Government Fee', 'Other'
];
$existingCategories = $pdo->query("SELECT DISTINCT category FROM expenses ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$allCategories = array_unique(array_merge($defaultCategories, $existingCategories));
sort($allCategories);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $category     = trim(filter_input(INPUT_POST, 'category',        FILTER_SANITIZE_SPECIAL_CHARS));
        $custom_cat   = trim(filter_input(INPUT_POST, 'custom_category', FILTER_SANITIZE_SPECIAL_CHARS));
        $description  = trim(filter_input(INPUT_POST, 'description',     FILTER_SANITIZE_SPECIAL_CHARS));
        $amount       = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        $expense_date = trim(filter_input(INPUT_POST, 'expense_date',    FILTER_SANITIZE_SPECIAL_CHARS));
        $notes        = trim(filter_input(INPUT_POST, 'notes',           FILTER_SANITIZE_SPECIAL_CHARS));

        if ($category === 'Other' && !empty($custom_cat)) {
            $category = $custom_cat;
        }

        if (empty($category) || empty($description)) {
            $error = 'Category and description are required.';
        } elseif ($amount === false || $amount <= 0) {
            $error = 'Please enter a valid amount.';
        } elseif (empty($expense_date)) {
            $error = 'Please select a date.';
        } else {
            $stmt = $pdo->prepare("
                UPDATE expenses SET category=?, description=?, amount=?, expense_date=?, notes=?
                WHERE id=?
            ");
            if ($stmt->execute([$category, $description, $amount, $expense_date, $notes, $id])) {
                setFlashMessage('success', 'Expense updated successfully!');
                header('Location: ' . BASE_URL . '/expenses/list.php');
                exit();
            } else {
                $error = 'Failed to update expense.';
            }
        }
    }
}

$csrf_token = csrfToken();
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-edit"></i> Edit Expense</h1>
    <a href="<?php echo BASE_URL; ?>/expenses/list.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo h($error); ?></div>
<?php endif; ?>

<div class="card" style="max-width:600px;">
    <div class="card-header"><h3>Edit Expense</h3></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">

            <div class="form-group">
                <label>Category *</label>
                <select name="category" id="categorySelect" class="form-control" required
                        onchange="toggleCustom(this.value)">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($allCategories as $cat): ?>
                        <option value="<?php echo h($cat); ?>"
                                <?php echo $expense['category'] === $cat ? 'selected' : ''; ?>>
                            <?php echo h($cat); ?>
                        </option>
                    <?php endforeach; ?>
                    <?php if (!in_array($expense['category'], $allCategories)): ?>
                        <option value="<?php echo h($expense['category']); ?>" selected>
                            <?php echo h($expense['category']); ?>
                        </option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Description *</label>
                <input type="text" name="description" class="form-control" required
                       value="<?php echo h($expense['description']); ?>">
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div class="form-group">
                    <label>Amount (Rs.) *</label>
                    <input type="number" name="amount" class="form-control"
                           step="0.01" min="0.01" required
                           value="<?php echo h((string)$expense['amount']); ?>">
                </div>
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="expense_date" class="form-control" required
                           value="<?php echo h($expense['expense_date']); ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="2"><?php echo h($expense['notes'] ?? ''); ?></textarea>
            </div>

            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
                <a href="<?php echo BASE_URL; ?>/expenses/list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
