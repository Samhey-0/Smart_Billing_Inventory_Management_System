<?php
/**
 * Expense List Page
 * @package InspireShoes
 * @version 1.4
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$page_title = 'Expenses';

// Filters
$month     = $_GET['month'] ?? date('Y-m');
$category  = $_GET['category'] ?? '';

$where  = "WHERE DATE_FORMAT(expense_date, '%Y-%m') = ?";
$params = [$month];

if (!empty($category)) {
    $where  .= " AND category = ?";
    $params[] = $category;
}

// Get expenses
$expenses = $pdo->prepare("
    SELECT e.*, u.username
    FROM expenses e
    JOIN users u ON e.user_id = u.id
    $where
    ORDER BY e.expense_date DESC, e.created_at DESC
");
$expenses->execute($params);
$expenses = $expenses->fetchAll(PDO::FETCH_ASSOC);

// Total expenses this period
$totalStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses $where");
$totalStmt->execute($params);
$totalExpenses = $totalStmt->fetchColumn();

// Revenue this period (paid invoices)
$revenue = $pdo->prepare("
    SELECT COALESCE(SUM(grand_total), 0)
    FROM invoices
    WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND status = 'Paid'
");
$revenue->execute([$month]);
$totalRevenue = $revenue->fetchColumn();

$netProfit = $totalRevenue - $totalExpenses;

// Expenses by category for this period
$byCategory = $pdo->prepare("
    SELECT category, SUM(amount) AS total, COUNT(*) AS count
    FROM expenses
    $where
    GROUP BY category
    ORDER BY total DESC
");
$byCategory->execute($params);
$byCategory = $byCategory->fetchAll(PDO::FETCH_ASSOC);

// All categories for filter
$categories = $pdo->query("SELECT DISTINCT category FROM expenses ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

// Available months
$months = $pdo->query("
    SELECT DISTINCT DATE_FORMAT(expense_date, '%Y-%m') AS ym,
                    DATE_FORMAT(expense_date, '%M %Y')  AS label
    FROM expenses
    ORDER BY ym DESC
    LIMIT 12
")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-receipt"></i> Expense Tracking</h1>
    <a href="<?php echo BASE_URL; ?>/expenses/add.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Expense
    </a>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:15px;">
        <form method="GET" style="display:flex; gap:15px; align-items:flex-end; flex-wrap:wrap;">
            <div class="form-group" style="margin:0; flex:1;">
                <label style="font-size:13px;">Month</label>
                <input type="month" name="month" class="form-control" value="<?php echo h($month); ?>">
            </div>
            <div class="form-group" style="margin:0; flex:1;">
                <label style="font-size:13px;">Category</label>
                <select name="category" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo h($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                            <?php echo h($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height:42px;">
                <i class="fas fa-filter"></i> Filter
            </button>
            <a href="<?php echo BASE_URL; ?>/expenses/list.php" class="btn btn-secondary" style="height:42px;">
                Clear
            </a>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid" style="margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($totalRevenue); ?></h3>
            <p>Revenue (Paid Invoices)</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon danger" style="--stat-color:#e74c3c;">
            <i class="fas fa-arrow-circle-down"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($totalExpenses); ?></h3>
            <p>Total Expenses</p>
        </div>
    </div>
    <div class="stat-card" style="border-left-color:<?php echo $netProfit >= 0 ? '#27ae60' : '#e74c3c'; ?>;">
        <div class="stat-icon" style="background:<?php echo $netProfit >= 0 ? '#27ae60' : '#e74c3c'; ?>;">
            <i class="fas fa-chart-pie"></i>
        </div>
        <div class="stat-content">
            <h3 style="color:<?php echo $netProfit >= 0 ? '#27ae60' : '#e74c3c'; ?>;">
                <?php echo formatCurrency(abs($netProfit)); ?>
            </h3>
            <p><?php echo $netProfit >= 0 ? '✅ Net Profit' : '❌ Net Loss'; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-list"></i></div>
        <div class="stat-content">
            <h3><?php echo count($expenses); ?></h3>
            <p>Expense Entries</p>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns:2fr 1fr; gap:20px;">

    <!-- Expenses Table -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-list"></i> Expense Entries</h3></div>
        <div class="card-body">
            <?php if (empty($expenses)): ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <p>No expenses found for this period.</p>
                    <a href="<?php echo BASE_URL; ?>/expenses/add.php" class="btn btn-primary">Add First Expense</a>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expenses as $exp): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($exp['expense_date'])); ?></td>
                                    <td>
                                        <span class="badge badge-info"><?php echo h($exp['category']); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo h($exp['description']); ?></strong>
                                        <?php if (!empty($exp['notes'])): ?>
                                            <br><small class="text-muted"><?php echo h($exp['notes']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo formatCurrency($exp['amount']); ?></strong></td>
                                    <td style="display:flex; gap:5px;">
                                        <a href="<?php echo BASE_URL; ?>/expenses/edit.php?id=<?php echo h((string)$exp['id']); ?>"
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/expenses/delete.php?id=<?php echo h((string)$exp['id']); ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Delete this expense?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background:var(--light-gray);">
                                <td colspan="3"><strong>Total</strong></td>
                                <td><strong><?php echo formatCurrency($totalExpenses); ?></strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- By Category -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-chart-pie"></i> By Category</h3></div>
        <div class="card-body">
            <?php if (empty($byCategory)): ?>
                <div class="empty-state"><i class="fas fa-chart-pie"></i><p>No data yet.</p></div>
            <?php else: ?>
                <?php foreach ($byCategory as $cat): ?>
                    <?php
                    $pct = $totalExpenses > 0 ? ($cat['total'] / $totalExpenses * 100) : 0;
                    ?>
                    <div style="margin-bottom:15px;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                            <span style="font-size:13px; font-weight:600;"><?php echo h($cat['category']); ?></span>
                            <span style="font-size:13px;"><?php echo formatCurrency($cat['total']); ?></span>
                        </div>
                        <div style="background:#eee; border-radius:4px; height:8px;">
                            <div style="background:var(--primary); width:<?php echo round($pct); ?>%; height:8px; border-radius:4px;"></div>
                        </div>
                        <small class="text-muted"><?php echo round($pct, 1); ?>% — <?php echo h((string)$cat['count']); ?> entries</small>
                    </div>
                <?php endforeach; ?>

                <!-- Profit Summary -->
                <div style="margin-top:20px; padding:15px; background:<?php echo $netProfit >= 0 ? '#e8f8ef' : '#fde8e8'; ?>; border-radius:8px;">
                    <div style="display:flex; justify-content:space-between;">
                        <span style="font-size:13px;">Revenue</span>
                        <span style="color:#27ae60; font-weight:600;"><?php echo formatCurrency($totalRevenue); ?></span>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span style="font-size:13px;">Expenses</span>
                        <span style="color:#e74c3c; font-weight:600;">- <?php echo formatCurrency($totalExpenses); ?></span>
                    </div>
                    <hr style="margin:8px 0;">
                    <div style="display:flex; justify-content:space-between;">
                        <span style="font-weight:700;">Net Profit</span>
                        <span style="font-weight:700; font-size:16px; color:<?php echo $netProfit >= 0 ? '#27ae60' : '#e74c3c'; ?>;">
                            <?php echo ($netProfit >= 0 ? '' : '-') . formatCurrency(abs($netProfit)); ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
