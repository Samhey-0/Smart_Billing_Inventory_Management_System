<?php
/**
 * Add Expense Page
 * @package InspireShoes
 * @version 1.4
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$page_title = 'Add Expense';
$error = '';

// Default expense categories
$defaultCategories = [
    'Rent', 'Electricity', 'Water', 'Gas',
    'Staff Salary', 'Staff Bonus',
    'Supplier Payment', 'Stock Purchase',
    'Transport / Delivery', 'Packaging',
    'Marketing / Advertising', 'Maintenance / Repair',
    'Office Supplies', 'Internet / Phone',
    'Tax / Government Fee', 'Other'
];

// Get existing custom categories
$existingCategories = $pdo->query("SELECT DISTINCT category FROM expenses ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$allCategories = array_unique(array_merge($defaultCategories, $existingCategories));
sort($allCategories);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $category     = trim(filter_input(INPUT_POST, 'category',     FILTER_SANITIZE_SPECIAL_CHARS));
        $custom_cat   = trim(filter_input(INPUT_POST, 'custom_category', FILTER_SANITIZE_SPECIAL_CHARS));
        $description  = trim(filter_input(INPUT_POST, 'description',  FILTER_SANITIZE_SPECIAL_CHARS));
        $amount       = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        $expense_date = trim(filter_input(INPUT_POST, 'expense_date', FILTER_SANITIZE_SPECIAL_CHARS));
        $notes        = trim(filter_input(INPUT_POST, 'notes',        FILTER_SANITIZE_SPECIAL_CHARS));

        // Use custom category if "Other" selected
        if ($category === 'Other' && !empty($custom_cat)) {
            $category = $custom_cat;
        }

        if (empty($category) || empty($description)) {
            $error = 'Category and description are required.';
        } elseif ($amount === false || $amount <= 0) {
            $error = 'Please enter a valid amount greater than 0.';
        } elseif (empty($expense_date)) {
            $error = 'Please select an expense date.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO expenses (user_id, category, description, amount, expense_date, notes)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if ($stmt->execute([$_SESSION['user_id'], $category, $description, $amount, $expense_date, $notes])) {
                setFlashMessage('success', 'Expense of ' . formatCurrency($amount) . ' added successfully!');
                header('Location: ' . BASE_URL . '/expenses/list.php');
                exit();
            } else {
                $error = 'Failed to save expense.';
            }
        }
    }
}

$csrf_token = csrfToken();
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-plus-circle"></i> Add Expense</h1>
    <a href="<?php echo BASE_URL; ?>/expenses/list.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Expenses
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo h($error); ?></div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:2fr 1fr; gap:20px;">

    <div class="card">
        <div class="card-header"><h3>Expense Details</h3></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">

                <div class="form-group">
                    <label>Category *</label>
                    <select name="category" id="categorySelect" class="form-control" required
                            onchange="toggleCustom(this.value)">
                        <option value="">-- Select Category --</option>
                        <?php foreach ($allCategories as $cat): ?>
                            <option value="<?php echo h($cat); ?>"><?php echo h($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="customCatGroup" style="display:none;">
                    <label>Custom Category Name *</label>
                    <input type="text" name="custom_category" id="custom_category"
                           class="form-control" placeholder="e.g. Security Guard Salary">
                </div>

                <div class="form-group">
                    <label>Description *</label>
                    <input type="text" name="description" class="form-control" required
                           placeholder="e.g. June rent payment to landlord">
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="form-group">
                        <label>Amount (Rs.) *</label>
                        <input type="number" name="amount" class="form-control"
                               step="0.01" min="0.01" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>Expense Date *</label>
                        <input type="date" name="expense_date" class="form-control"
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Notes <small class="text-muted">(optional)</small></label>
                    <textarea name="notes" class="form-control" rows="2"
                              placeholder="Any additional details..."></textarea>
                </div>

                <div style="display:flex; gap:10px;">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Save Expense
                    </button>
                    <a href="<?php echo BASE_URL; ?>/expenses/list.php" class="btn btn-secondary btn-lg">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Reference -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-info-circle"></i> Common Categories</h3></div>
        <div class="card-body">
            <?php
            $icons = [
                'Rent' => 'fas fa-building', 'Electricity' => 'fas fa-bolt',
                'Staff Salary' => 'fas fa-users', 'Supplier Payment' => 'fas fa-truck',
                'Transport / Delivery' => 'fas fa-car', 'Marketing / Advertising' => 'fas fa-bullhorn',
                'Maintenance / Repair' => 'fas fa-tools', 'Internet / Phone' => 'fas fa-wifi',
                'Tax / Government Fee' => 'fas fa-file-alt', 'Other' => 'fas fa-ellipsis-h',
            ];
            foreach ($icons as $cat => $icon): ?>
                <div onclick="setCategory('<?php echo h($cat); ?>')"
                     style="padding:10px 12px; margin-bottom:6px; background:var(--light-gray); border-radius:8px; cursor:pointer; display:flex; align-items:center; gap:10px; transition:background 0.2s;"
                     onmouseover="this.style.background='#e0e7ef'" onmouseout="this.style.background='var(--light-gray)'">
                    <i class="<?php echo $icon; ?>" style="color:var(--primary); width:16px;"></i>
                    <span style="font-size:13px;"><?php echo h($cat); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function toggleCustom(val) {
    const group = document.getElementById('customCatGroup');
    const input = document.getElementById('custom_category');
    if (val === 'Other') {
        group.style.display = 'block';
        input.required = true;
    } else {
        group.style.display = 'none';
        input.required = false;
    }
}
function setCategory(cat) {
    const sel = document.getElementById('categorySelect');
    for (let i = 0; i < sel.options.length; i++) {
        if (sel.options[i].value === cat) {
            sel.selectedIndex = i;
            toggleCustom(cat);
            return;
        }
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
