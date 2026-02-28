<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'reports';
$pageTitle = 'راپۆرتەکانی خەزنکراو';

// Kurdish month names
$kurdishMonths = [
    1 => 'کانوونی دووەم', 2 => 'شوبات', 3 => 'ئازار', 4 => 'نیسان',
    5 => 'ئایار', 6 => 'حوزەیران', 7 => 'تەمموز', 8 => 'ئاب',
    9 => 'ئەیلوول', 10 => 'تشرینی یەکەم', 11 => 'تشرینی دووەم', 12 => 'کانوونی یەکەم'
];

// Get all saved reports
$db->query("SELECT * FROM monthly_reports ORDER BY year DESC, month DESC");
$savedReports = $db->resultSet();

// Group by year
$reportsByYear = [];
foreach ($savedReports as $report) {
    $reportsByYear[$report['year']][] = $report;
}

// Calculate totals per year
$yearlyTotals = [];
foreach ($reportsByYear as $yr => $reports) {
    $yearlyTotals[$yr] = [
        'sales' => array_sum(array_column($reports, 'sales_total')),
        'purchases' => array_sum(array_column($reports, 'purchases_total')),
        'profit' => array_sum(array_column($reports, 'real_profit')),
        'months' => count($reports)
    ];
}

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header no-print">
    <div>
        <h2><i class="fas fa-archive"></i> راپۆرتەکانی خەزنکراو</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item"><a href="monthly.php">راپۆرتی مانگانە</a></li>
                <li class="breadcrumb-item active">راپۆرتەکانی خەزنکراو</li>
            </ol>
        </nav>
    </div>
    <a href="backups.php" class="btn btn-outline-info">
        <i class="fas fa-history"></i> باکئەپەکان
    </a>
</div>

<?php if (empty($savedReports)): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> هیچ راپۆرتێکی خەزنکراو نیە. 
    <a href="monthly.php" class="alert-link">بڕۆ بۆ راپۆرتی مانگانە</a> - خۆکارانە سەیڤ دەبێت.
</div>
<?php else: ?>

<?php foreach ($reportsByYear as $yr => $reports): ?>
<!-- Year Card -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white;">
        <h5 class="mb-0"><i class="fas fa-calendar"></i> ساڵی <?php echo $yr; ?></h5>
        <div class="d-flex gap-3">
            <span><i class="fas fa-shopping-cart"></i> فرۆشتن: <?php echo formatMoney($yearlyTotals[$yr]['sales']); ?></span>
            <span><i class="fas fa-truck"></i> کڕین: <?php echo formatMoney($yearlyTotals[$yr]['purchases']); ?></span>
            <span class="fw-bold"><i class="fas fa-chart-line"></i> قازانج: <?php echo formatMoney($yearlyTotals[$yr]['profit']); ?></span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="text-align:center;">
                <thead style="background:#f8f9fa;">
                    <tr>
                        <th style="text-align:center;">مانگ</th>
                        <th style="text-align:center;">فرۆشتن</th>
                        <th style="text-align:center;">کڕین</th>
                        <th style="text-align:center;">داهات</th>
                        <th style="text-align:center;">خەرجی</th>
                        <th style="text-align:center;">قازانج</th>
                        <th style="text-align:center;">هێلکە</th>
                        <th style="text-align:center;">جوجکە</th>
                        <th style="text-align:center;">خەزنکرا</th>
                        <th style="text-align:center;">کردار</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                    <tr>
                        <td><strong><?php echo $kurdishMonths[$report['month']]; ?></strong></td>
                        <td class="text-success"><?php echo formatMoney($report['sales_total']); ?></td>
                        <td class="text-danger"><?php echo formatMoney($report['purchases_total']); ?></td>
                        <td class="text-success"><?php echo formatMoney($report['income_total']); ?></td>
                        <td class="text-danger"><?php echo formatMoney($report['expense_total']); ?></td>
                        <td>
                            <strong class="<?php echo $report['real_profit'] >= 0 ? 'text-primary' : 'text-warning'; ?>">
                                <?php echo formatMoney($report['real_profit']); ?>
                            </strong>
                        </td>
                        <td><?php echo number_format($report['eggs_collected']); ?></td>
                        <td><?php echo number_format($report['chicks_hatched']); ?></td>
                        <td>
                            <small class="text-muted"><?php echo date('Y/m/d', strtotime($report['updated_at'])); ?></small>
                        </td>
                        <td>
                            <a href="monthly.php?month=<?php echo $report['month']; ?>&year=<?php echo $report['year']; ?>" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot style="background:#f0f0f0; font-weight:bold;">
                    <tr>
                        <td>کۆی ساڵی <?php echo $yr; ?></td>
                        <td class="text-success"><?php echo formatMoney($yearlyTotals[$yr]['sales']); ?></td>
                        <td class="text-danger"><?php echo formatMoney($yearlyTotals[$yr]['purchases']); ?></td>
                        <td class="text-success"><?php echo formatMoney(array_sum(array_column($reports, 'income_total'))); ?></td>
                        <td class="text-danger"><?php echo formatMoney(array_sum(array_column($reports, 'expense_total'))); ?></td>
                        <td class="<?php echo $yearlyTotals[$yr]['profit'] >= 0 ? 'text-primary' : 'text-warning'; ?>">
                            <?php echo formatMoney($yearlyTotals[$yr]['profit']); ?>
                        </td>
                        <td><?php echo number_format(array_sum(array_column($reports, 'eggs_collected'))); ?></td>
                        <td><?php echo number_format(array_sum(array_column($reports, 'chicks_hatched'))); ?></td>
                        <td colspan="2"><?php echo count($reports); ?> مانگ</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<?php require_once $basePath . 'includes/footer.php'; ?>
