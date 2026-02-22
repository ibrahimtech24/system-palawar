<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'reports';
$pageTitle = 'راپۆرتی مانگانە';

// Get selected month/year
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$selectedDate = sprintf('%04d-%02d', $year, $month);

// Monthly sales
$db->query("SELECT SUM(total_price) as total, COUNT(*) as count FROM sales WHERE DATE_FORMAT(sale_date, '%Y-%m') = :date");
$db->bind(':date', $selectedDate);
$salesData = $db->single();

// Monthly purchases
$db->query("SELECT SUM(total_price) as total, COUNT(*) as count FROM purchases WHERE DATE_FORMAT(purchase_date, '%Y-%m') = :date");
$db->bind(':date', $selectedDate);
$purchasesData = $db->single();

// Eggs collected
$db->query("SELECT SUM(quantity) as total FROM eggs WHERE DATE_FORMAT(collection_date, '%Y-%m') = :date");
$db->bind(':date', $selectedDate);
$eggsData = $db->single();

// Chicks hatched
$db->query("SELECT SUM(quantity) as total FROM chicks WHERE DATE_FORMAT(hatch_date, '%Y-%m') = :date");
$db->bind(':date', $selectedDate);
$chicksData = $db->single();

// Sales by item type
$db->query("SELECT item_type, SUM(total_price) as total, SUM(quantity) as qty FROM sales WHERE DATE_FORMAT(sale_date, '%Y-%m') = :date GROUP BY item_type");
$db->bind(':date', $selectedDate);
$salesByType = $db->resultSet();

// Daily sales for chart
$dailySales = [];
$dailyLabels = [];
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

for ($day = 1; $day <= $daysInMonth; $day++) {
    $dailyLabels[] = $day;
    $dayDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
    
    $db->query("SELECT SUM(total_price) as total FROM sales WHERE DATE(sale_date) = :date");
    $db->bind(':date', $dayDate);
    $result = $db->single();
    $dailySales[] = $result['total'] ?? 0;
}

// Top customers this month
$db->query("SELECT c.name, SUM(s.total_price) as total 
            FROM sales s 
            JOIN customers c ON s.customer_id = c.id 
            WHERE DATE_FORMAT(s.sale_date, '%Y-%m') = :date 
            GROUP BY c.id 
            ORDER BY total DESC 
            LIMIT 5");
$db->bind(':date', $selectedDate);
$topCustomers = $db->resultSet();

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-calendar-alt"></i> راپۆرتی مانگانە</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item active">راپۆرتی مانگانە</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <button onclick="exportToPDF('reportContent', 'monthly-report-<?php echo $selectedDate; ?>')" class="btn btn-danger">
            <i class="fas fa-file-pdf"></i> داگرتن بە PDF
        </button>
    </div>
</div>

<!-- Month Selector -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">مانگ</label>
                <select name="month" class="form-select">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo $month == $m ? 'selected' : ''; ?>>
                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">ساڵ</label>
                <select name="year" class="form-select">
                    <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> پیشاندان
                </button>
            </div>
        </form>
    </div>
</div>

<div id="reportContent">
    <!-- Report Header -->
    <div class="text-center mb-4">
        <h3><?php echo SITE_NAME; ?></h3>
        <h4>راپۆرتی مانگانە - <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h4>
        <p class="text-muted">بەرواری چاپ: <?php echo date('Y/m/d H:i'); ?></p>
    </div>
    
    <!-- Summary Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon bg-success">
                    <i class="fas fa-cash-register"></i>
                </div>
                <div class="info">
                    <h3><?php echo formatMoney($salesData['total'] ?? 0); ?></h3>
                    <p>کۆی فرۆشتن (<?php echo $salesData['count'] ?? 0; ?>)</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon bg-danger">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="info">
                    <h3><?php echo formatMoney($purchasesData['total'] ?? 0); ?></h3>
                    <p>کۆی کڕین (<?php echo $purchasesData['count'] ?? 0; ?>)</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon bg-warning">
                    <i class="fas fa-egg"></i>
                </div>
                <div class="info">
                    <h3><?php echo number_format($eggsData['total'] ?? 0); ?></h3>
                    <p>هێلکەی کۆکراوە</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon bg-info">
                    <i class="fas fa-kiwi-bird"></i>
                </div>
                <div class="info">
                    <h3><?php echo number_format($chicksData['total'] ?? 0); ?></h3>
                    <p>جوجکەی دەرچوو</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Profit/Loss -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header <?php echo ($salesData['total'] ?? 0) >= ($purchasesData['total'] ?? 0) ? 'bg-success-gradient' : 'bg-danger-gradient'; ?>">
                    <i class="fas fa-chart-line"></i> قازانج / زیان
                </div>
                <div class="card-body text-center">
                    <?php 
                    $profit = ($salesData['total'] ?? 0) - ($purchasesData['total'] ?? 0);
                    $profitClass = $profit >= 0 ? 'text-success' : 'text-danger';
                    ?>
                    <h2 class="<?php echo $profitClass; ?>">
                        <?php echo $profit >= 0 ? '+' : ''; ?><?php echo formatMoney($profit); ?>
                    </h2>
                    <p class="text-muted">جیاوازی فرۆشتن و کڕین</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info-gradient">
                    <i class="fas fa-percentage"></i> ڕێژەی قازانج
                </div>
                <div class="card-body text-center">
                    <?php 
                    $profitPercentage = ($purchasesData['total'] ?? 0) > 0 
                        ? (($salesData['total'] ?? 0) - ($purchasesData['total'] ?? 0)) / ($purchasesData['total'] ?? 1) * 100 
                        : 0;
                    ?>
                    <h2 class="<?php echo $profitPercentage >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo number_format($profitPercentage, 1); ?>%
                    </h2>
                    <p class="text-muted">ڕێژەی قازانج بەرامبەر کڕین</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Daily Sales Chart -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-bar"></i> فرۆشتنی ڕۆژانە
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="dailySalesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sales by Type -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i> فرۆشتن بە جۆر
                </div>
                <div class="card-body">
                    <?php if (count($salesByType) > 0): ?>
                    <div class="chart-container" style="height: 250px;">
                        <canvas id="salesByTypeChart"></canvas>
                    </div>
                    <hr>
                    <ul class="list-unstyled">
                        <?php foreach ($salesByType as $type): ?>
                        <li class="d-flex justify-content-between mb-2">
                            <span><?php echo getItemTypeName($type['item_type']); ?></span>
                            <strong><?php echo formatMoney($type['total']); ?></strong>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-chart-pie"></i>
                        <p>هیچ داتایەک نیە</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top Customers -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-trophy"></i> باشترین کڕیاران لەم مانگەدا
                </div>
                <div class="card-body">
                    <?php if (count($topCustomers) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ناوی کڕیار</th>
                                    <th>کۆی کڕین</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topCustomers as $index => $customer): ?>
                                <tr>
                                    <td>
                                        <?php if ($index === 0): ?>
                                        <span class="badge bg-warning"><i class="fas fa-trophy"></i></span>
                                        <?php else: ?>
                                        <?php echo $index + 1; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo $customer['name']; ?></strong></td>
                                    <td><?php echo formatMoney($customer['total']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <p>هیچ کڕیارێک لەم مانگەدا نیە</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Daily Sales Chart
    const dailyCtx = document.getElementById('dailySalesChart');
    if (dailyCtx) {
        new Chart(dailyCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($dailyLabels); ?>,
                datasets: [{
                    label: 'فرۆشتن',
                    data: <?php echo json_encode($dailySales); ?>,
                    backgroundColor: 'rgba(39, 174, 96, 0.8)',
                    borderColor: 'rgba(39, 174, 96, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Sales by Type Chart
    const typeCtx = document.getElementById('salesByTypeChart');
    if (typeCtx) {
        new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_map(function($t) { return getItemTypeName($t['item_type']); }, $salesByType)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($salesByType, 'total')); ?>,
                    backgroundColor: [
                        'rgba(52, 152, 219, 0.8)',
                        'rgba(231, 76, 60, 0.8)',
                        'rgba(241, 196, 15, 0.8)',
                        'rgba(26, 188, 156, 0.8)',
                        'rgba(155, 89, 182, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
});
</script>

<?php require_once $basePath . 'includes/footer.php'; ?>
