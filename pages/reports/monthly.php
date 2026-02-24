<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'reports';
$pageTitle = 'راپۆرتی مانگانە';

// Get selected month/year - current year only
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = (int)date('Y'); // Always current year

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

// All sales this month
$db->query("SELECT s.*, c.name as customer_name 
            FROM sales s 
            LEFT JOIN customers c ON s.customer_id = c.id 
            WHERE DATE_FORMAT(s.sale_date, '%Y-%m') = :date 
            ORDER BY s.sale_date DESC");
$db->bind(':date', $selectedDate);
$allSales = $db->resultSet();

// All purchases this month
$db->query("SELECT * FROM purchases 
            WHERE DATE_FORMAT(purchase_date, '%Y-%m') = :date 
            ORDER BY purchase_date DESC");
$db->bind(':date', $selectedDate);
$allPurchases = $db->resultSet();

// Male birds this month
$db->query("SELECT * FROM male_birds WHERE DATE_FORMAT(entry_date, '%Y-%m') = :date ORDER BY entry_date DESC");
$db->bind(':date', $selectedDate);
$maleBirds = $db->resultSet();
$totalMaleBirds = array_sum(array_column($maleBirds, 'quantity'));

// Female birds this month
$db->query("SELECT * FROM female_birds WHERE DATE_FORMAT(entry_date, '%Y-%m') = :date ORDER BY entry_date DESC");
$db->bind(':date', $selectedDate);
$femaleBirds = $db->resultSet();
$totalFemaleBirds = array_sum(array_column($femaleBirds, 'quantity'));

// Transactions this month
$db->query("SELECT * FROM transactions WHERE DATE_FORMAT(transaction_date, '%Y-%m') = :date ORDER BY transaction_date DESC");
$db->bind(':date', $selectedDate);
$transactions = $db->resultSet();

$totalIncome = 0;
$totalExpense = 0;
foreach ($transactions as $trans) {
    if ($trans['transaction_type'] == 'income') {
        $totalIncome += $trans['amount'];
    } else {
        $totalExpense += $trans['amount'];
    }
}

// Warehouse status
$db->query("SELECT * FROM warehouse ORDER BY item_name");
$warehouseItems = $db->resultSet();

// Incubator data this month
$db->query("SELECT i.*, c.name as customer_name 
            FROM incubator i 
            LEFT JOIN customers c ON i.customer_id = c.id 
            WHERE DATE_FORMAT(i.entry_date, '%Y-%m') = :date 
            ORDER BY i.entry_date DESC");
$db->bind(':date', $selectedDate);
$incubatorItems = $db->resultSet();
$totalIncubatorEggs = array_sum(array_column($incubatorItems, 'egg_quantity'));
$totalHatched = array_sum(array_column($incubatorItems, 'hatched_count'));
$totalDamaged = array_sum(array_column($incubatorItems, 'damaged_count'));

require_once $basePath . 'includes/header.php';
?>

<style>
/* Print styles for PDF */
#reportContent {
    background: white;
    padding: 20px;
}
#reportContent .stat-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
}
#reportContent table {
    font-size: 12px;
}
@media print {
    .page-header, .card.mb-4:first-of-type {
        display: none !important;
    }
    #reportContent {
        padding: 0;
    }
    .card {
        break-inside: avoid;
    }
}
.bg-pink {
    background: linear-gradient(135deg, #ff6b9d 0%, #c44569 100%) !important;
    color: white;
}
</style>

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
        <button onclick="printReport('reportContent')" class="btn btn-secondary">
            <i class="fas fa-print"></i> چاپکردن
        </button>
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
                    <?php $currentYear = (int)date('Y'); ?>
                    <option value="<?php echo $currentYear; ?>" <?php echo $year == $currentYear ? 'selected' : ''; ?>><?php echo $currentYear; ?></option>
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
    
    <!-- Additional Stats Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon bg-primary">
                    <i class="fas fa-mars"></i>
                </div>
                <div class="info">
                    <h3><?php echo number_format($totalMaleBirds); ?></h3>
                    <p>هەوێردەی نێر</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon bg-pink">
                    <i class="fas fa-venus"></i>
                </div>
                <div class="info">
                    <h3><?php echo number_format($totalFemaleBirds); ?></h3>
                    <p>هەوێردەی مێ</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon bg-success">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="info">
                    <h3><?php echo formatMoney($totalIncome); ?></h3>
                    <p>داهات</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon bg-danger">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div class="info">
                    <h3><?php echo formatMoney($totalExpense); ?></h3>
                    <p>خەرجی</p>
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
    
    <!-- All Sales Table -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success-gradient">
                    <i class="fas fa-shopping-cart"></i> لیستی فرۆشتنەکان (<?php echo count($allSales); ?>)
                </div>
                <div class="card-body">
                    <?php if (count($allSales) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>کۆد</th>
                                    <th>کڕیار</th>
                                    <th>جۆر</th>
                                    <th>ژمارە</th>
                                    <th>نرخی یەکە</th>
                                    <th>کۆی گشتی</th>
                                    <th>بەروار</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allSales as $index => $sale): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $sale['sale_code']; ?></td>
                                    <td><?php echo $sale['customer_name'] ?? 'نەناسراو'; ?></td>
                                    <td><?php echo getItemTypeName($sale['item_type']); ?></td>
                                    <td><?php echo number_format($sale['quantity']); ?></td>
                                    <td><?php echo formatMoney($sale['unit_price']); ?></td>
                                    <td><strong><?php echo formatMoney($sale['total_price']); ?></strong></td>
                                    <td><?php echo $sale['sale_date']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-success">
                                    <th colspan="6">کۆی گشتی فرۆشتن</th>
                                    <th><?php echo formatMoney($salesData['total'] ?? 0); ?></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-cart"></i>
                        <p>هیچ فرۆشتنێک لەم مانگەدا نیە</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- All Purchases Table -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-danger-gradient">
                    <i class="fas fa-truck"></i> لیستی کڕینەکان (<?php echo count($allPurchases); ?>)
                </div>
                <div class="card-body">
                    <?php if (count($allPurchases) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>کۆد</th>
                                    <th>جۆر</th>
                                    <th>ژمارە</th>
                                    <th>نرخی یەکە</th>
                                    <th>کۆی گشتی</th>
                                    <th>بەروار</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allPurchases as $index => $purchase): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $purchase['purchase_code']; ?></td>
                                    <td><?php echo $purchase['item_type']; ?></td>
                                    <td><?php echo number_format($purchase['quantity']); ?></td>
                                    <td><?php echo formatMoney($purchase['unit_price']); ?></td>
                                    <td><strong><?php echo formatMoney($purchase['total_price']); ?></strong></td>
                                    <td><?php echo $purchase['purchase_date']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-danger">
                                    <th colspan="5">کۆی گشتی کڕین</th>
                                    <th><?php echo formatMoney($purchasesData['total'] ?? 0); ?></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-truck"></i>
                        <p>هیچ کڕینێک لەم مانگەدا نیە</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Warehouse Status -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning-gradient">
                    <i class="fas fa-warehouse"></i> بارودۆخی کۆگا
                </div>
                <div class="card-body">
                    <?php if (count($warehouseItems) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ناوی کاڵا</th>
                                    <th>ژمارە</th>
                                    <th>یەکە</th>
                                    <th>نرخی یەکە</th>
                                    <th>بەهای کۆ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $totalWarehouseValue = 0; ?>
                                <?php foreach ($warehouseItems as $index => $item): ?>
                                <?php $itemValue = $item['quantity'] * $item['unit_price']; $totalWarehouseValue += $itemValue; ?>
                                <tr class="<?php echo $item['quantity'] <= $item['min_quantity'] ? 'table-warning' : ''; ?>">
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $item['item_name']; ?></td>
                                    <td><?php echo number_format($item['quantity']); ?></td>
                                    <td><?php echo $item['unit']; ?></td>
                                    <td><?php echo formatMoney($item['unit_price']); ?></td>
                                    <td><strong><?php echo formatMoney($itemValue); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-info">
                                    <th colspan="5">کۆی بەهای کۆگا</th>
                                    <th><?php echo formatMoney($totalWarehouseValue); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-warehouse"></i>
                        <p>کۆگا بەتاڵە</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Transactions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info-gradient">
                    <i class="fas fa-exchange-alt"></i> مامەڵەکان (<?php echo count($transactions); ?>)
                </div>
                <div class="card-body">
                    <?php if (count($transactions) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>جۆر</th>
                                    <th>پۆل</th>
                                    <th>بڕ</th>
                                    <th>وەسف</th>
                                    <th>بەروار</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $index => $trans): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <?php if ($trans['transaction_type'] == 'income'): ?>
                                        <span class="badge bg-success"><i class="fas fa-arrow-down"></i> داهات</span>
                                        <?php else: ?>
                                        <span class="badge bg-danger"><i class="fas fa-arrow-up"></i> خەرجی</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $trans['category']; ?></td>
                                    <td><strong><?php echo formatMoney($trans['amount']); ?></strong></td>
                                    <td><?php echo $trans['description']; ?></td>
                                    <td><?php echo $trans['transaction_date']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-success">
                                    <th colspan="3">کۆی داهات</th>
                                    <th colspan="3"><?php echo formatMoney($totalIncome); ?></th>
                                </tr>
                                <tr class="table-danger">
                                    <th colspan="3">کۆی خەرجی</th>
                                    <th colspan="3"><?php echo formatMoney($totalExpense); ?></th>
                                </tr>
                                <tr class="<?php echo ($totalIncome - $totalExpense) >= 0 ? 'table-success' : 'table-danger'; ?>">
                                    <th colspan="3">باڵانس</th>
                                    <th colspan="3"><?php echo formatMoney($totalIncome - $totalExpense); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-exchange-alt"></i>
                        <p>هیچ مامەڵەیەک لەم مانگەدا نیە</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Incubator -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning-gradient">
                    <i class="fas fa-temperature-high"></i> مەفقەس (<?php echo count($incubatorItems); ?>)
                </div>
                <div class="card-body">
                    <?php if (count($incubatorItems) > 0): ?>
                    <!-- Incubator Summary -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4 text-center">
                            <h4 class="text-primary"><?php echo number_format($totalIncubatorEggs); ?></h4>
                            <small class="text-muted">کۆی هێلکە لە مەفقەس</small>
                        </div>
                        <div class="col-md-4 text-center">
                            <h4 class="text-success"><?php echo number_format($totalHatched); ?></h4>
                            <small class="text-muted">دەرچوو</small>
                        </div>
                        <div class="col-md-4 text-center">
                            <h4 class="text-danger"><?php echo number_format($totalDamaged); ?></h4>
                            <small class="text-muted">خراپ/تەلەف</small>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ناوی گرووپ</th>
                                    <th>کڕیار</th>
                                    <th>ژمارەی هێلکە</th>
                                    <th>دەرچوو</th>
                                    <th>خراپ</th>
                                    <th>دۆخ</th>
                                    <th>بەرواری دەستپێک</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($incubatorItems as $index => $inc): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($inc['group_name']); ?></td>
                                    <td><?php echo !empty($inc['customer_name']) ? htmlspecialchars($inc['customer_name']) : 'خۆمان'; ?></td>
                                    <td><?php echo number_format($inc['egg_quantity']); ?></td>
                                    <td><span class="badge bg-success"><?php echo number_format($inc['hatched_count']); ?></span></td>
                                    <td><span class="badge bg-danger"><?php echo number_format($inc['damaged_count']); ?></span></td>
                                    <td>
                                        <?php if ($inc['status'] == 'incubating'): ?>
                                        <span class="badge bg-warning text-dark">چاوەڕوانی</span>
                                        <?php else: ?>
                                        <span class="badge bg-success">دەرچووە</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $inc['entry_date']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-warning">
                                    <th colspan="3">کۆ</th>
                                    <th><?php echo number_format($totalIncubatorEggs); ?></th>
                                    <th><?php echo number_format($totalHatched); ?></th>
                                    <th><?php echo number_format($totalDamaged); ?></th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-temperature-high"></i>
                        <p>هیچ مەفقەسێک لەم مانگەدا نیە</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Report Footer -->
    <div class="text-center mt-4 pt-4 border-top">
        <p class="text-muted mb-0">
            <strong><?php echo SITE_NAME; ?></strong> - راپۆرتی مانگی <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?>
        </p>
        <small class="text-muted">ئەم راپۆرتە لە بەرواری <?php echo date('Y/m/d'); ?> کاتژمێر <?php echo date('H:i'); ?> دروستکراوە</small>
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
