<?php
/**
 * Dashboard - v1.2 with Revenue Chart
 * @package InspireShoes
 * @version 1.2
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$page_title = 'Dashboard';

// Stats
$totalProducts  = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$todayCount     = $pdo->query("SELECT COUNT(*) FROM invoices WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$todayRevenue   = $pdo->query("SELECT COALESCE(SUM(grand_total),0) FROM invoices WHERE DATE(created_at) = CURDATE() AND status='Paid'")->fetchColumn();
$pendingAmount  = $pdo->query("SELECT COALESCE(SUM(grand_total),0) FROM invoices WHERE status='Unpaid'")->fetchColumn();

// Recent invoices
$recentInvoices = $pdo->query("
    SELECT i.id, i.grand_total, i.status, i.created_at, c.full_name AS customer_name
    FROM invoices i
    LEFT JOIN customers c ON i.customer_id = c.id
    ORDER BY i.created_at DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Low stock
$lowStock = $pdo->query("
    SELECT * FROM products WHERE stock_qty <= 10 ORDER BY stock_qty ASC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Last 30 days revenue for chart
$chartData = $pdo->query("
    SELECT DATE(created_at) AS date,
           COALESCE(SUM(grand_total), 0) AS revenue
    FROM invoices
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
      AND status = 'Paid'
    GROUP BY DATE(created_at)
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Monthly revenue vs last month
$thisMonth = $pdo->query("SELECT COALESCE(SUM(grand_total),0) FROM invoices WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW()) AND status='Paid'")->fetchColumn();
$lastMonth = $pdo->query("SELECT COALESCE(SUM(grand_total),0) FROM invoices WHERE MONTH(created_at)=MONTH(NOW()-INTERVAL 1 MONTH) AND YEAR(created_at)=YEAR(NOW()-INTERVAL 1 MONTH) AND status='Paid'")->fetchColumn();
$growthPct = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : 0;

include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Dashboard</h1>
    <p class="text-muted">Welcome back, <strong><?php echo h($_SESSION['username']); ?></strong>!</p>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-box"></i></div>
        <div class="stat-content">
            <h3><?php echo h((string)$totalProducts); ?></h3>
            <p>Total Products</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-users"></i></div>
        <div class="stat-content">
            <h3><?php echo h((string)$totalCustomers); ?></h3>
            <p>Total Customers</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-file-invoice-dollar"></i></div>
        <div class="stat-content">
            <h3><?php echo h((string)$todayCount); ?></h3>
            <p>Invoices Today</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($todayRevenue); ?></h3>
            <p>Revenue Today</p>
        </div>
    </div>
</div>

<!-- Second row stats -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:25px;">
    <div class="stat-card" style="border-left-color:#e74c3c;">
        <div class="stat-icon" style="background:#e74c3c;"><i class="fas fa-clock"></i></div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($pendingAmount); ?></h3>
            <p>Total Pending (Unpaid)</p>
        </div>
    </div>
    <div class="stat-card" style="border-left-color:<?php echo $growthPct >= 0 ? '#27ae60' : '#e74c3c'; ?>;">
        <div class="stat-icon" style="background:<?php echo $growthPct >= 0 ? '#27ae60' : '#e74c3c'; ?>;">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo ($growthPct >= 0 ? '+' : '') . $growthPct; ?>%</h3>
            <p>vs Last Month (<?php echo formatCurrency($thisMonth); ?> this month)</p>
        </div>
    </div>
</div>

<!-- Revenue Chart -->
<div class="card" style="margin-bottom:25px;">
    <div class="card-header">
        <h3><i class="fas fa-chart-bar"></i> Revenue — Last 30 Days</h3>
        <a href="<?php echo BASE_URL; ?>/reports/sales.php" class="btn btn-sm btn-primary">Full Reports</a>
    </div>
    <div class="card-body">
        <?php if (empty($chartData)): ?>
            <div class="empty-state"><i class="fas fa-chart-bar"></i><p>No paid invoices in the last 30 days yet.</p></div>
        <?php else: ?>
            <canvas id="revenueChart" height="80"></canvas>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Invoices & Low Stock -->
<div class="dashboard-grid">
    <div class="card">
        <div class="card-header">
            <h3>Recent Invoices</h3>
            <a href="<?php echo BASE_URL; ?>/invoices/list.php" class="btn btn-sm btn-primary">View All</a>
        </div>
        <div class="card-body">
            <?php if (empty($recentInvoices)): ?>
                <div class="empty-state"><i class="fas fa-file-invoice-dollar"></i><p>No invoices yet.</p></div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>Invoice #</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentInvoices as $invoice): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/invoices/view.php?id=<?php echo h((string)$invoice['id']); ?>">
                                            <strong><?php echo generateInvoiceNumber($invoice['id']); ?></strong>
                                        </a>
                                    </td>
                                    <td><?php echo h($invoice['customer_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo formatDate($invoice['created_at']); ?></td>
                                    <td><strong><?php echo formatCurrency($invoice['grand_total']); ?></strong></td>
                                    <td>
                                        <?php
                                        $sc = match($invoice['status']) {
                                            'Paid' => 'badge-success', 'Unpaid' => 'badge-warning', 'Cancelled' => 'badge-danger', default => ''
                                        };
                                        ?>
                                        <span class="badge <?php echo $sc; ?>"><?php echo h($invoice['status']); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Low Stock Alert</h3>
            <a href="<?php echo BASE_URL; ?>/stock/manage.php" class="btn btn-sm btn-primary">Manage Stock</a>
        </div>
        <div class="card-body">
            <?php if (empty($lowStock)): ?>
                <div class="empty-state"><i class="fas fa-check-circle"></i><p>All products well stocked!</p></div>
            <?php else: ?>
                <div class="low-stock-list">
                    <?php foreach ($lowStock as $product): ?>
                        <div class="low-stock-item">
                            <div class="product-info">
                                <strong><?php echo h($product['name']); ?></strong>
                                <span class="text-muted"><?php echo h($product['brand']); ?></span>
                            </div>
                            <div>
                                <?php if ($product['stock_qty'] == 0): ?>
                                    <span class="badge badge-danger">Out of Stock</span>
                                <?php else: ?>
                                    <span class="badge badge-warning"><?php echo h((string)$product['stock_qty']); ?> left</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <h3>Quick Actions</h3>
    <div class="action-buttons">
        <a href="<?php echo BASE_URL; ?>/invoices/create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Invoice
        </a>
        <a href="<?php echo BASE_URL; ?>/products/add.php" class="btn btn-accent">
            <i class="fas fa-box"></i> Add Product
        </a>
        <a href="<?php echo BASE_URL; ?>/customers/add.php" class="btn btn-accent">
            <i class="fas fa-user-plus"></i> Add Customer
        </a>
        <a href="<?php echo BASE_URL; ?>/stock/manage.php" class="btn btn-secondary">
            <i class="fas fa-boxes"></i> Manage Stock
        </a>
        <a href="<?php echo BASE_URL; ?>/reports/sales.php" class="btn btn-secondary">
            <i class="fas fa-chart-line"></i> View Reports
        </a>
        <?php if (isAdmin()): ?>
        <a href="<?php echo BASE_URL; ?>/admin/staff.php" class="btn btn-secondary">
            <i class="fas fa-users-cog"></i> Manage Staff
        </a>
        <?php endif; ?>
    </div>
</div>

<style>
.low-stock-list { display:flex; flex-direction:column; gap:15px; }
.low-stock-item { display:flex; justify-content:space-between; align-items:center; padding:12px; background:var(--light-gray); border-radius:8px; }
.low-stock-item .product-info { display:flex; flex-direction:column; }
.low-stock-item .product-info strong { font-size:14px; }
.low-stock-item .product-info span { font-size:12px; }
.dashboard-grid { display:grid; grid-template-columns:2fr 1fr; gap:25px; }
@media (max-width:992px) { .dashboard-grid { grid-template-columns:1fr; } }
</style>

<?php if (!empty($chartData)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const labels  = <?php echo json_encode(array_column($chartData, 'date')); ?>;
const revenue = <?php echo json_encode(array_map(fn($r) => round($r['revenue'], 2), $chartData)); ?>;

new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [{
            label: 'Revenue (Rs.)',
            data: revenue,
            borderColor: '#1a2e4a',
            backgroundColor: 'rgba(26,46,74,0.08)',
            borderWidth: 2,
            pointBackgroundColor: '#f5a623',
            pointRadius: 4,
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => 'Rs. ' + ctx.parsed.y.toLocaleString()
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: val => 'Rs. ' + val.toLocaleString() }
            }
        }
    }
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
