<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'reports';
$pageTitle = 'راپۆرتی فرۆشتن';

// Get date range
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get sales data
$db->query("SELECT s.*, c.name as customer_name 
            FROM sales s 
            LEFT JOIN customers c ON s.customer_id = c.id 
            WHERE DATE(s.sale_date) BETWEEN :start AND :end 
            ORDER BY s.sale_date DESC");
$db->bind(':start', $startDate);
$db->bind(':end', $endDate);
$sales = $db->resultSet();

// Calculate totals
$totalAmount = array_sum(array_column($sales, 'total_price'));
$totalQuantity = array_sum(array_column($sales, 'quantity'));

// Sales by type
$db->query("SELECT item_type, SUM(total_price) as total, SUM(quantity) as qty, COUNT(*) as count 
            FROM sales 
            WHERE DATE(sale_date) BETWEEN :start AND :end 
            GROUP BY item_type 
            ORDER BY total DESC");
$db->bind(':start', $startDate);
$db->bind(':end', $endDate);
$salesByType = $db->resultSet();

// Daily sales
$db->query("SELECT DATE(sale_date) as date, SUM(total_price) as total 
            FROM sales 
            WHERE DATE(sale_date) BETWEEN :start AND :end 
            GROUP BY DATE(sale_date) 
            ORDER BY date");
$db->bind(':start', $startDate);
$db->bind(':end', $endDate);
$dailySales = $db->resultSet();

$dailyLabels = array_column($dailySales, 'date');
$dailyValues = array_column($dailySales, 'total');

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-chart-line"></i> راپۆرتی فرۆشتن</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item active">راپۆرتی فرۆشتن</li>
            </ol>
        </nav>
    </div>
    <div>
        <button onclick="exportToPDF('reportContent', 'sales-report-<?php echo $startDate; ?>-<?php echo $endDate; ?>')" class="btn btn-danger">
            <i class="fas fa-file-pdf"></i> داگرتن بە PDF
        </button>
    </div>
</div>

<!-- Date Range Selector -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">لە بەروار</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">بۆ بەروار</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
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
        <h4>راپۆرتی فرۆشتن</h4>
        <p class="text-muted">
            لە <?php echo formatDate($startDate); ?> بۆ <?php echo formatDate($endDate); ?>
            <br>بەرواری چاپ: <?php echo date('Y/m/d H:i'); ?>
        </p>
    </div>
    
    <!-- Summary Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="icon bg-success">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="info">
                    <h3><?php echo count($sales); ?></h3>
                    <p>ژمارەی فرۆشتن</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card">
                <div class="icon bg-primary">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="info">
                    <h3><?php echo formatMoney($totalAmount); ?></h3>
                    <p>کۆی داهات</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card">
                <div class="icon bg-warning">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="info">
                    <h3><?php echo number_format($totalQuantity); ?></h3>
                    <p>کۆی دانە</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4 mb-4">
        <!-- Daily Sales Chart -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success-gradient">
                    <i class="fas fa-chart-area"></i> فرۆشتنی ڕۆژانە
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sales by Type -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i> بە پێی جۆر
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 200px;">
                        <canvas id="typeChart"></canvas>
                    </div>
                    <hr>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($salesByType as $type): ?>
                        <li class="d-flex justify-content-between mb-2">
                            <span><?php echo getItemTypeName($type['item_type']); ?> (<?php echo $type['count']; ?>)</span>
                            <strong><?php echo formatMoney($type['total']); ?></strong>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sales Table -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-list"></i> وردەکاری فرۆشتنەکان
        </div>
        <div class="card-body">
            <?php if (count($sales) > 0): ?>
            <div class="table-responsive">
                <table class="table data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>کۆد</th>
                            <th>کڕیار</th>
                            <th>جۆر</th>
                            <th>ژمارە</th>
                            <th>نرخی یەکە</th>
                            <th>کۆی نرخ</th>
                            <th>بەروار</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $index => $sale): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><code><?php echo $sale['sale_code']; ?></code></td>
                            <td><?php echo $sale['customer_name'] ?: 'نەزانراو'; ?></td>
                            <td><?php echo getItemTypeName($sale['item_type']); ?></td>
                            <td><?php echo $sale['quantity']; ?></td>
                            <td><?php echo formatMoney($sale['unit_price']); ?></td>
                            <td><strong><?php echo formatMoney($sale['total_price']); ?></strong></td>
                            <td><?php echo formatDate($sale['sale_date']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark">
                            <td colspan="6" class="text-end"><strong>کۆی گشتی:</strong></td>
                            <td colspan="2"><strong><?php echo formatMoney($totalAmount); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-chart-bar"></i>
                <p>هیچ فرۆشتنێک لەم ماوەیەدا نیە</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Daily Chart
    const dailyCtx = document.getElementById('dailyChart');
    if (dailyCtx) {
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dailyLabels); ?>,
                datasets: [{
                    label: 'فرۆشتن',
                    data: <?php echo json_encode($dailyValues); ?>,
                    borderColor: 'rgba(39, 174, 96, 1)',
                    backgroundColor: 'rgba(39, 174, 96, 0.2)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
    
    // Type Chart
    const typeCtx = document.getElementById('typeChart');
    if (typeCtx) {
        new Chart(typeCtx, {
            type: 'pie',
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
