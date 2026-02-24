<?php
/**
 * Sales Reports Page
 * Monthly/weekly revenue, best-selling products, top customers
 * @package InspireShoes
 * @version 1.1
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin(); // ✅ Admin only - staff cannot view financial reports

$page_title = 'Sales Reports';

// ─── Date Range Filter ────────────────────────────────────────────────────────
$range  = $_GET['range'] ?? 'month';
$ranges = ['week' => '7 DAY', 'month' => '30 DAY', 'year' => '365 DAY'];
$interval = $ranges[$range] ?? '30 DAY';

// ─── Summary Stats ────────────────────────────────────────────────────────────
$summary = $pdo->query("
    SELECT
        COUNT(*)                                          AS total_invoices,
        COALESCE(SUM(grand_total), 0)                    AS total_revenue,
        COALESCE(SUM(CASE WHEN status='Paid' THEN grand_total END), 0) AS paid_revenue,
        COALESCE(SUM(CASE WHEN status='Unpaid' THEN grand_total END), 0) AS unpaid_revenue,
        COALESCE(AVG(grand_total), 0)                    AS avg_invoice
    FROM invoices
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL $interval)
")->fetch(PDO::FETCH_ASSOC);

// ─── Daily Revenue for Chart ──────────────────────────────────────────────────
$dailyRevenue = $pdo->query("
    SELECT DATE(created_at) AS date,
           COALESCE(SUM(grand_total), 0) AS revenue,
           COUNT(*) AS invoice_count
    FROM invoices
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL $interval)
      AND status = 'Paid'
    GROUP BY DATE(created_at)
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ─── Best Selling Products ────────────────────────────────────────────────────
$topProducts = $pdo->query("
    SELECT ii.product_name,
           SUM(ii.quantity)   AS total_qty,
           SUM(ii.line_total) AS total_revenue
    FROM invoice_items ii
    JOIN invoices i ON ii.invoice_id = i.id
    WHERE i.created_at >= DATE_SUB(NOW(), INTERVAL $interval)
    GROUP BY ii.product_name
    ORDER BY total_qty DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// ─── Top Customers ────────────────────────────────────────────────────────────
$topCustomers = $pdo->query("
    SELECT c.full_name,
           COUNT(i.id)        AS total_invoices,
           SUM(i.grand_total) AS total_spent
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    WHERE i.created_at >= DATE_SUB(NOW(), INTERVAL $interval)
      AND i.status = 'Paid'
    GROUP BY c.id, c.full_name
    ORDER BY total_spent DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// ─── Monthly Breakdown ────────────────────────────────────────────────────────
$monthlyBreakdown = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%b %Y') AS month,
           MONTH(created_at) AS month_num,
           YEAR(created_at)  AS year_num,
           COUNT(*)           AS invoices,
           SUM(grand_total)   AS revenue
    FROM invoices
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)
    GROUP BY year_num, month_num
    ORDER BY year_num ASC, month_num ASC
")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-chart-line"></i> Sales Reports</h1>
    <div style="display:flex; gap:10px;">
        <a href="?range=week"  class="btn <?php echo $range==='week'  ? 'btn-primary' : 'btn-secondary'; ?>">This Week</a>
        <a href="?range=month" class="btn <?php echo $range==='month' ? 'btn-primary' : 'btn-secondary'; ?>">Last 30 Days</a>
        <a href="?range=year"  class="btn <?php echo $range==='year'  ? 'btn-primary' : 'btn-secondary'; ?>">This Year</a>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-file-invoice-dollar"></i></div>
        <div class="stat-content">
            <h3><?php echo number_format($summary['total_invoices']); ?></h3>
            <p>Total Invoices</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($summary['total_revenue']); ?></h3>
            <p>Total Revenue</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-check-circle"></i></div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($summary['paid_revenue']); ?></h3>
            <p>Collected (Paid)</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($summary['unpaid_revenue']); ?></h3>
            <p>Pending (Unpaid)</p>
        </div>
    </div>
</div>

<!-- Revenue Chart -->
<div class="card">
    <div class="card-header"><h3><i class="fas fa-chart-bar"></i> Daily Revenue</h3></div>
    <div class="card-body">
        <canvas id="revenueChart" height="100"></canvas>
    </div>
</div>

<!-- Best Products & Top Customers side by side -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

    <!-- Best Selling Products -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-star"></i> Best Selling Products</h3></div>
        <div class="card-body">
            <?php if (empty($topProducts)): ?>
                <div class="empty-state"><i class="fas fa-box"></i><p>No sales data yet.</p></div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Units Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProducts as $i => $p): ?>
                                <tr>
                                    <td><strong><?php echo $i + 1; ?></strong></td>
                                    <td><?php echo h($p['product_name']); ?></td>
                                    <td><span class="badge badge-info"><?php echo h((string)$p['total_qty']); ?></span></td>
                                    <td><strong><?php echo formatCurrency($p['total_revenue']); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top Customers -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-users"></i> Top Customers</h3></div>
        <div class="card-body">
            <?php if (empty($topCustomers)): ?>
                <div class="empty-state"><i class="fas fa-users"></i><p>No data yet.</p></div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Invoices</th>
                                <th>Total Spent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topCustomers as $i => $c): ?>
                                <tr>
                                    <td><strong><?php echo $i + 1; ?></strong></td>
                                    <td><?php echo h($c['full_name']); ?></td>
                                    <td><span class="badge badge-success"><?php echo h((string)$c['total_invoices']); ?></span></td>
                                    <td><strong><?php echo formatCurrency($c['total_spent']); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Monthly Breakdown -->
<div class="card" style="margin-top:20px;">
    <div class="card-header"><h3><i class="fas fa-calendar-alt"></i> Monthly Breakdown</h3></div>
    <div class="card-body">
        <?php if (empty($monthlyBreakdown)): ?>
            <div class="empty-state"><i class="fas fa-calendar"></i><p>No data yet.</p></div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Invoices</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthlyBreakdown as $m): ?>
                            <tr>
                                <td><strong><?php echo h($m['month']); ?></strong></td>
                                <td><?php echo h((string)$m['invoices']); ?></td>
                                <td><strong><?php echo formatCurrency($m['revenue']); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const labels  = <?php echo json_encode(array_column($dailyRevenue, 'date')); ?>;
const revenue = <?php echo json_encode(array_map(fn($r) => round($r['revenue'], 2), $dailyRevenue)); ?>;

new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Revenue (PKR)',
            data: revenue,
            backgroundColor: 'rgba(26, 46, 74, 0.7)',
            borderColor: '#1a2e4a',
            borderWidth: 1,
            borderRadius: 4
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
                ticks: {
                    callback: val => 'Rs. ' + val.toLocaleString()
                }
            }
        }
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
