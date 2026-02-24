<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

$currentPage = 'home';
$pageTitle = 'داشبۆرد';

// Get statistics
// Male birds count - sum of quantities minus dead
$db->query("SELECT SUM(quantity - COALESCE(dead_count, 0)) as total FROM male_birds WHERE status = 'active'");
$maleBirdsCount = $db->single()['total'] ?? 0;

// Female birds count - sum of quantities minus dead
$db->query("SELECT SUM(quantity - COALESCE(dead_count, 0)) as total FROM female_birds WHERE status = 'active'");
$femaleBirdsCount = $db->single()['total'] ?? 0;

// Eggs count - total eggs (matching eggs list)
$db->query("SELECT SUM(quantity) as total FROM eggs WHERE quantity > 0 AND (quantity - damaged_count) > 0");
$eggsCount = $db->single()['total'] ?? 0;

// Chicks count
$db->query("SELECT SUM(quantity - dead_count) as total FROM chicks WHERE status = 'active'");
$chicksCount = $db->single()['total'] ?? 0;

// This month sales
$db->query("SELECT SUM(total_price) as total FROM sales WHERE MONTH(sale_date) = MONTH(CURRENT_DATE()) AND YEAR(sale_date) = YEAR(CURRENT_DATE())");
$monthSales = $db->single()['total'] ?? 0;

// This month purchases
$db->query("SELECT SUM(total_price) as total FROM purchases WHERE MONTH(purchase_date) = MONTH(CURRENT_DATE()) AND YEAR(purchase_date) = YEAR(CURRENT_DATE())");
$monthPurchases = $db->single()['total'] ?? 0;

// Total customers
$db->query("SELECT COUNT(*) as count FROM customers");
$customersCount = $db->single()['count'];

// Total warehouse items
$db->query("SELECT COUNT(*) as count FROM warehouse");
$warehouseCount = $db->single()['count'];

// Incubator stats
$db->query("SELECT SUM(egg_quantity) as total FROM incubator WHERE status = 'incubating'");
$incubatingCount = $db->single()['total'] ?? 0;

// Get incubator items ready to hatch (today or past due)
$db->query("SELECT COUNT(*) as count FROM incubator WHERE status = 'incubating' AND expected_hatch_date <= CURDATE()");
$readyToHatchCount = $db->single()['count'] ?? 0;

// Active incubator groups for dashboard display
$db->query("SELECT i.*, cu.name as customer_name FROM incubator i LEFT JOIN customers cu ON i.customer_id = cu.id WHERE i.status = 'incubating' ORDER BY i.expected_hatch_date ASC LIMIT 6");
$activeIncubatorItems = $db->resultSet();

// Recent sales
$db->query("SELECT s.*, c.name as customer_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.id ORDER BY s.sale_date DESC LIMIT 5");
$recentSales = $db->resultSet();

// Recent transactions
$db->query("SELECT * FROM transactions ORDER BY transaction_date DESC LIMIT 5");
$recentTransactions = $db->resultSet();

// Monthly sales data for chart (last 6 months)
$monthlySalesData = [];
$monthlyLabels = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthlyLabels[] = date('F', strtotime("-$i months"));
    
    $db->query("SELECT SUM(total_price) as total FROM sales WHERE DATE_FORMAT(sale_date, '%Y-%m') = :month");
    $db->bind(':month', $month);
    $result = $db->single();
    $monthlySalesData[] = $result['total'] ?? 0;
}

require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-tachometer-alt"></i> داشبۆرد</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">سەرەکی</li>
            </ol>
        </nav>
    </div>
    <div class="datetime-display">
        <div class="date-box">
            <i class="fas fa-calendar-alt"></i>
            <span id="currentDate"><?php echo date('Y/m/d'); ?></span>
        </div>
        <div class="time-box">
            <i class="fas fa-clock"></i>
            <span id="currentTime">00:00:00</span>
        </div>
    </div>
</div>

<style>
.datetime-display {
    display: flex;
    gap: 18px;
    align-items: center;
    flex-wrap: wrap;
}

.date-box, .time-box {
    background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
    color: white;
    padding: 14px 24px;
    border-radius: 16px;
    font-weight: 700;
    font-size: 1.15rem;
    box-shadow: 0 8px 25px rgba(124, 58, 237, 0.4);
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
    overflow: hidden;
}

.date-box::before, .time-box::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
}

.time-box {
    background: linear-gradient(135deg, #059669 0%, #34d399 100%);
    box-shadow: 0 8px 25px rgba(5, 150, 105, 0.4);
    min-width: 150px;
    font-family: 'Courier New', monospace;
    font-size: 1.3rem;
    letter-spacing: 3px;
}

.date-box i, .time-box i {
    font-size: 1.1rem;
    opacity: 0.95;
}

@media (max-width: 992px) {
    .page-header {
        flex-direction: column;
        text-align: center;
    }
    
    .datetime-display {
        justify-content: center;
        width: 100%;
    }
}

@media (max-width: 576px) {
    .datetime-display {
        flex-direction: column;
        gap: 8px;
        width: 100%;
    }
    
    .date-box, .time-box {
        padding: 8px 12px;
        font-size: 0.85rem;
        width: 100%;
        justify-content: center;
    }
    
    .time-box {
        font-size: 0.95rem;
        min-width: auto;
    }
    
    .date-box i, .time-box i {
        font-size: 0.85rem;
    }
}
</style>

<script>
function updateClock() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('currentTime').textContent = hours + ':' + minutes + ':' + seconds;
}

// Update every second
setInterval(updateClock, 1000);
updateClock(); // Initial call
</script>

<!-- Incubator Active Groups -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #f97316, #ea580c); color: white;">
        <span><i class="fas fa-fire"></i> مەفقەس - گرووپە چالاکەکان</span>
        <div>
            <a href="pages/incubator/add.php" class="btn btn-sm btn-light me-1"><i class="fas fa-plus"></i> دانانەوە</a>
            <a href="pages/incubator/list.php" class="btn btn-sm btn-outline-light"><i class="fas fa-list"></i> هەموو</a>
        </div>
    </div>
    <div class="card-body">
        <?php if (count($activeIncubatorItems) > 0): ?>
        <div class="row g-3">
            <?php foreach ($activeIncubatorItems as $incItem): 
                $entryD = new DateTime($incItem['entry_date']);
                $hatchD = new DateTime($incItem['expected_hatch_date']);
                $todayD = new DateTime();
                $dLeft = (int)$todayD->diff($hatchD)->format('%r%a');
                $prog = min(100, max(0, ((17 - max(0, $dLeft)) / 17) * 100));
                $pColor = $dLeft <= 0 ? 'success' : ($dLeft <= 3 ? 'warning' : 'info');
            ?>
            <div class="col-lg-4 col-md-6">
                <div class="border rounded p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong><i class="fas fa-egg text-warning"></i> <?php echo htmlspecialchars($incItem['group_name']); ?></strong>
                        <span class="badge bg-<?php echo $pColor; ?>">
                            <?php echo $dLeft <= 0 ? 'ئامادەیە!' : $dLeft . ' ڕۆژ'; ?>
                        </span>
                    </div>
                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-<?php echo $pColor; ?> progress-bar-striped progress-bar-animated" style="width: <?php echo $prog; ?>%"></div>
                    </div>
                    <small class="text-muted">
                        <?php echo number_format($incItem['egg_quantity']); ?> هێلکە | 
                        دەرچوون: <?php echo formatDate($incItem['expected_hatch_date']); ?>
                        <?php if ($incItem['customer_name']): ?><br><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($incItem['customer_name']); ?><?php endif; ?>
                    </small>
                    <?php if ($dLeft <= 0): ?>
                    <div class="mt-2">
                        <a href="pages/incubator/hatch.php?id=<?php echo $incItem['id']; ?>" class="btn btn-success btn-sm w-100">
                            <i class="fas fa-kiwi-bird"></i> هەڵاتن
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-temperature-high" style="font-size: 3rem; color: #f9731650;"></i>
            <h5 class="mt-3 text-muted">هیچ هێلکەیەک لە مەفقەسدا نیە</h5>
            <p class="text-muted">بۆ دانانەوەی هێلکە لە مەفقەس، کلیک لە دوگمەی خوارەوە بکە</p>
            <a href="pages/incubator/add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> دانانەوەی هێلکە لە مەفقەس
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-xl col-md-6">
        <div class="stat-card">
            <div class="icon bg-primary">
                <i class="fas fa-mars"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($maleBirdsCount); ?></h3>
                <p>هەوێردەی نێر</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl col-md-6">
        <div class="stat-card">
            <div class="icon bg-danger">
                <i class="fas fa-venus"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($femaleBirdsCount); ?></h3>
                <p>هەوێردەی مێ</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl col-md-6">
        <div class="stat-card">
            <div class="icon bg-warning">
                <i class="fas fa-egg"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($eggsCount); ?></h3>
                <p>هێلکە</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl col-md-6">
        <div class="stat-card">
            <div class="icon" style="background: linear-gradient(135deg, #f97316, #ea580c);">
                <i class="fas fa-temperature-high"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($incubatingCount); ?></h3>
                <p>مەفقەس</p>
                <?php if ($readyToHatchCount > 0): ?>
                <small class="text-success fw-bold"><i class="fas fa-bell"></i> <?php echo $readyToHatchCount; ?> ئامادەی هەڵاتن</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-xl col-md-6">
        <div class="stat-card">
            <div class="icon bg-info">
                <i class="fas fa-kiwi-bird"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($chicksCount); ?></h3>
                <p>جوجکە</p>
            </div>
        </div>
    </div>
</div>

<!-- Financial Stats -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="icon bg-success">
                <i class="fas fa-cash-register"></i>
            </div>
            <div class="info">
                <h3><?php echo formatMoney($monthSales); ?></h3>
                <p>فرۆشتنی ئەم مانگە</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="icon bg-danger">
                <i class="fas fa-truck"></i>
            </div>
            <div class="info">
                <h3><?php echo formatMoney($monthPurchases); ?></h3>
                <p>کڕینی ئەم مانگە</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="icon bg-primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($customersCount); ?></h3>
                <p>کڕیاران</p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stat-card">
            <div class="icon bg-warning">
                <i class="fas fa-warehouse"></i>
            </div>
            <div class="info">
                <h3><?php echo number_format($warehouseCount); ?></h3>
                <p>کاڵای مەخزەن</p>
            </div>
        </div>
    </div>
</div>



<div class="row g-4">
    <!-- Sales Chart -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-success-gradient">
                <i class="fas fa-chart-line"></i> فرۆشتنی ٦ مانگی کۆتایی
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Inventory Distribution -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-info-gradient">
                <i class="fas fa-chart-pie"></i> دابەشبوونی ئاژەڵ
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="inventoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <!-- Recent Sales -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-cash-register"></i> فرۆشتنە دوایینەکان</span>
                <a href="pages/sales/list.php" class="btn btn-sm btn-success"><i class="fas fa-list"></i> هەموو</a>
            </div>
            <div class="card-body">
                <?php if (count($recentSales) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>کۆد</th>
                                <th>جۆر</th>
                                <th>کڕیار</th>
                                <th>بڕ</th>
                                <th>بەروار</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentSales as $sale): ?>
                            <tr>
                                <td><code><?php echo $sale['sale_code']; ?></code></td>
                                <td><span class="badge bg-primary"><?php echo getItemTypeName($sale['item_type']); ?></span></td>
                                <td><?php echo $sale['customer_name'] ?? 'نەزانراو'; ?></td>
                                <td class="text-success fw-bold"><?php echo formatMoney($sale['total_price']); ?></td>
                                <td><?php echo formatDate($sale['sale_date']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h4>هیچ فرۆشتنێک نیە</h4>
                    <p>تا ئێستا هیچ فرۆشتنێک تۆمار نەکراوە</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Transactions -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-history"></i> مامەڵە دوایینەکان</span>
                <a href="pages/transactions/list.php" class="btn btn-sm btn-primary"><i class="fas fa-list"></i> هەموو</a>
            </div>
            <div class="card-body">
                <?php if (count($recentTransactions) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>جۆر</th>
                                <th>پۆل</th>
                                <th>بڕ</th>
                                <th>بەروار</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTransactions as $trans): ?>
                            <tr>
                                <td>
                                    <?php if ($trans['transaction_type'] === 'income'): ?>
                                    <span class="badge bg-success"><i class="fas fa-arrow-down"></i> داهات</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger"><i class="fas fa-arrow-up"></i> خەرجی</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $trans['category'] ?? '-'; ?></td>
                                <td class="<?php echo $trans['transaction_type'] === 'income' ? 'text-success' : 'text-danger'; ?> fw-bold">
                                    <?php echo $trans['transaction_type'] === 'income' ? '+' : '-'; ?><?php echo formatMoney($trans['amount']); ?>
                                </td>
                                <td><?php echo formatDate($trans['transaction_date']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h4>هیچ مامەڵەیەک نیە</h4>
                    <p>تا ئێستا هیچ مامەڵەیەک تۆمار نەکراوە</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize Charts when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($monthlyLabels); ?>,
            datasets: [{
                label: 'فرۆشتن',
                data: <?php echo json_encode($monthlySalesData); ?>,
                borderColor: 'rgba(39, 174, 96, 1)',
                backgroundColor: 'rgba(39, 174, 96, 0.2)',
                fill: true,
                tension: 0.4,
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' د.ع';
                        }
                    }
                }
            }
        }
    });
    
    // Inventory Chart
    const invCtx = document.getElementById('inventoryChart').getContext('2d');
    new Chart(invCtx, {
        type: 'doughnut',
        data: {
            labels: ['هەوێردەی نێر', 'هەوێردەی مێ', 'هێلکە', 'جوجکە'],
            datasets: [{
                data: [
                    <?php echo $maleBirdsCount; ?>,
                    <?php echo $femaleBirdsCount; ?>,
                    <?php echo $eggsCount; ?>,
                    <?php echo $chicksCount; ?>
                ],
                backgroundColor: [
                    'rgba(52, 152, 219, 0.8)',
                    'rgba(231, 76, 60, 0.8)',
                    'rgba(241, 196, 15, 0.8)',
                    'rgba(26, 188, 156, 0.8)'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            family: "'Noto Sans Arabic', sans-serif"
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
