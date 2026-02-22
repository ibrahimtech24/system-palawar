<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'reports';
$pageTitle = 'راپۆرتی کڕیاران';

// Get specific customer if ID provided
$customerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get all customers for dropdown
$db->query("SELECT id, name FROM customers ORDER BY name");
$allCustomers = $db->resultSet();

if ($customerId > 0) {
    // Get customer info
    $db->query("SELECT * FROM customers WHERE id = :id");
    $db->bind(':id', $customerId);
    $customer = $db->single();
    
    if (!$customer) {
        setMessage('error', 'کڕیارەکە نەدۆزرایەوە');
        redirect('customers.php');
    }
    
    // Get customer's sales
    $db->query("SELECT * FROM sales WHERE customer_id = :id ORDER BY sale_date DESC");
    $db->bind(':id', $customerId);
    $customerSales = $db->resultSet();
    
    // Calculate stats
    $totalSales = array_sum(array_column($customerSales, 'total_price'));
    $salesCount = count($customerSales);
    
    // Monthly sales for this customer
    $monthlySales = [];
    $monthlyLabels = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthlyLabels[] = date('M Y', strtotime("-$i months"));
        
        $db->query("SELECT SUM(total_price) as total FROM sales WHERE customer_id = :id AND DATE_FORMAT(sale_date, '%Y-%m') = :month");
        $db->bind(':id', $customerId);
        $db->bind(':month', $month);
        $result = $db->single();
        $monthlySales[] = $result['total'] ?? 0;
    }
} else {
    // Get all customers stats
    $db->query("SELECT c.*, 
                (SELECT COUNT(*) FROM sales WHERE customer_id = c.id) as sales_count,
                (SELECT COALESCE(SUM(total_price), 0) FROM sales WHERE customer_id = c.id) as total_purchases,
                (SELECT MAX(sale_date) FROM sales WHERE customer_id = c.id) as last_sale
                FROM customers c ORDER BY total_purchases DESC");
    $customers = $db->resultSet();
}

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-users"></i> راپۆرتی کڕیاران</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item active">راپۆرتی کڕیاران</li>
            </ol>
        </nav>
    </div>
    <div>
        <button onclick="exportToPDF('reportContent', 'customers-report')" class="btn btn-danger">
            <i class="fas fa-file-pdf"></i> داگرتن بە PDF
        </button>
    </div>
</div>

<!-- Customer Selector -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">هەڵبژاردنی کڕیار</label>
                <select name="id" class="form-select">
                    <option value="">هەموو کڕیاران</option>
                    <?php foreach ($allCustomers as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $customerId == $c['id'] ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
                    <?php endforeach; ?>
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
    <?php if ($customerId > 0): ?>
    <!-- Single Customer Report -->
    <div class="text-center mb-4">
        <h3>راپۆرتی کڕیار: <?php echo $customer['name']; ?></h3>
        <p class="text-muted">بەرواری چاپ: <?php echo date('Y/m/d H:i'); ?></p>
    </div>
    
    <!-- Customer Info Card -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-user-circle fa-4x text-primary"></i>
                    </div>
                    <h4><?php echo $customer['name']; ?></h4>
                    <p class="text-muted">
                        <?php if ($customer['phone']): ?>
                        <i class="fas fa-phone"></i> <?php echo $customer['phone']; ?><br>
                        <?php endif; ?>
                        <?php if ($customer['address']): ?>
                        <i class="fas fa-map-marker-alt"></i> <?php echo $customer['address']; ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card h-100">
                <div class="icon bg-success">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="info">
                    <h3><?php echo $salesCount; ?></h3>
                    <p>ژمارەی کڕین</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card h-100">
                <div class="icon bg-primary">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="info">
                    <h3><?php echo formatMoney($totalSales); ?></h3>
                    <p>کۆی کڕین</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Monthly Chart -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-chart-line"></i> کڕینی ٦ مانگی کۆتایی
        </div>
        <div class="card-body">
            <div class="chart-container" style="height: 250px;">
                <canvas id="customerSalesChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Sales History -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-history"></i> مێژووی کڕین
        </div>
        <div class="card-body">
            <?php if (count($customerSales) > 0): ?>
            <div class="table-responsive">
                <table class="table data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>کۆد</th>
                            <th>جۆری کاڵا</th>
                            <th>ژمارە</th>
                            <th>کۆی نرخ</th>
                            <th>بەروار</th>
                            <th>بارودۆخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customerSales as $index => $sale): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><code><?php echo $sale['sale_code']; ?></code></td>
                            <td><?php echo getItemTypeName($sale['item_type']); ?></td>
                            <td><?php echo $sale['quantity']; ?></td>
                            <td><?php echo formatMoney($sale['total_price']); ?></td>
                            <td><?php echo formatDate($sale['sale_date']); ?></td>
                            <td><?php echo getStatusBadge($sale['payment_status']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark">
                            <td colspan="4" class="text-end"><strong>کۆی گشتی:</strong></td>
                            <td colspan="3"><strong><?php echo formatMoney($totalSales); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <p>هیچ کڕینێک نیە</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('customerSalesChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($monthlyLabels); ?>,
                    datasets: [{
                        label: 'کڕین',
                        data: <?php echo json_encode($monthlySales); ?>,
                        borderColor: 'rgba(52, 152, 219, 1)',
                        backgroundColor: 'rgba(52, 152, 219, 0.2)',
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
    });
    </script>
    
    <?php else: ?>
    <!-- All Customers Report -->
    <div class="text-center mb-4">
        <h3>راپۆرتی هەموو کڕیاران</h3>
        <p class="text-muted">بەرواری چاپ: <?php echo date('Y/m/d H:i'); ?></p>
    </div>
    
    <div class="card">
        <div class="card-header">
            <i class="fas fa-list"></i> لیستی کڕیاران (<?php echo count($customers ?? []); ?>)
        </div>
        <div class="card-body">
            <?php if (!empty($customers)): ?>
            <div class="table-responsive">
                <table class="table data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ناو</th>
                            <th>تەلەفۆن</th>
                            <th>ژمارەی کڕین</th>
                            <th>کۆی کڕین</th>
                            <th>دوایین کڕین</th>
                            <th class="no-print">کردار</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $index => $c): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><strong><?php echo $c['name']; ?></strong></td>
                            <td><?php echo $c['phone'] ?: '-'; ?></td>
                            <td><?php echo $c['sales_count']; ?></td>
                            <td><?php echo formatMoney($c['total_purchases']); ?></td>
                            <td><?php echo $c['last_sale'] ? formatDate($c['last_sale']) : '-'; ?></td>
                            <td class="no-print">
                                <a href="?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-info btn-action">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <p>هیچ کڕیارێک نیە</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once $basePath . 'includes/footer.php'; ?>
