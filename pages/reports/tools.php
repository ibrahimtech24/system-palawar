<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'reports';
$pageTitle = 'راپۆرتی ئامیر و ئەداوات';

// Get filter parameters - default to current month and year
$filterMonth = isset($_GET['month']) ? intval($_GET['month']) : intval(date('n'));
$filterYear = isset($_GET['year']) && $_GET['year'] !== '' ? intval($_GET['year']) : intval(date('Y'));

// Get available years from warehouse data
$db->query("SELECT MIN(YEAR(created_at)) as min_year, MAX(YEAR(created_at)) as max_year FROM warehouse");
$yearRange = $db->single();
$minYear = $yearRange['min_year'] ? intval($yearRange['min_year']) : intval(date('Y'));
$maxYear = intval(date('Y'));
// Make sure min year doesn't exceed current year
if ($minYear > $maxYear) $minYear = $maxYear;

// Get available months for selected year (only months that have data)
$db->query("SELECT DISTINCT MONTH(created_at) as m FROM warehouse WHERE YEAR(created_at) = :year ORDER BY m");
$db->bind(':year', $filterYear);
$availableMonths = array_column($db->resultSet(), 'm');

// Default to latest month that has data
if (!isset($_GET['month']) && !empty($availableMonths)) {
    $filterMonth = intval(end($availableMonths));
} elseif (!isset($_GET['month']) && empty($availableMonths)) {
    $filterMonth = intval(date('n'));
}

// Get all warehouse items with usage data - filtered by created_at date
$whereClause = "WHERE 1=1";
if ($filterMonth > 0) {
    $whereClause .= " AND MONTH(w.created_at) = :month AND YEAR(w.created_at) = :year";
} elseif ($filterYear) {
    $whereClause .= " AND YEAR(w.created_at) = :year";
}

$db->query("SELECT w.*, 
            COALESCE(SUM(tu.quantity_used), 0) as total_used,
            COALESCE(SUM(tu.quantity_used * w.unit_price), 0) as total_expense
            FROM warehouse w
            LEFT JOIN tool_usage tu ON w.id = tu.warehouse_id
            $whereClause
            GROUP BY w.id
            ORDER BY total_expense DESC");
if ($filterMonth > 0) {
    $db->bind(':month', $filterMonth);
    $db->bind(':year', $filterYear);
} elseif ($filterYear) {
    $db->bind(':year', $filterYear);
}
$items = $db->resultSet();

// Total values
$totalStockValue = 0;
$totalUsedExpense = 0;
$totalPurchaseCost = 0;
foreach ($items as $item) {
    $totalStockValue += ($item['quantity'] * $item['unit_price']);
    $totalUsedExpense += $item['total_expense'];
    $totalPurchaseCost += ($item['quantity'] * $item['unit_price']);
}

// Get monthly usage breakdown for chart
$monthlyData = [];
for ($m = 1; $m <= 12; $m++) {
    $db->query("SELECT COALESCE(SUM(tu.quantity_used * w.unit_price), 0) as total 
                FROM tool_usage tu 
                LEFT JOIN warehouse w ON tu.warehouse_id = w.id 
                WHERE MONTH(tu.usage_date) = :month AND YEAR(tu.usage_date) = :year");
    $db->bind(':month', $m);
    $db->bind(':year', $filterYear);
    $monthlyData[$m] = $db->single()['total'] ?? 0;
}

// Get top 5 most consumed items
$db->query("SELECT w.item_name, w.unit, w.unit_price,
            SUM(tu.quantity_used) as total_used,
            SUM(tu.quantity_used * w.unit_price) as total_expense
            FROM tool_usage tu 
            LEFT JOIN warehouse w ON tu.warehouse_id = w.id 
            WHERE YEAR(tu.usage_date) = :year
            GROUP BY tu.warehouse_id
            ORDER BY total_expense DESC
            LIMIT 5");
$db->bind(':year', $filterYear);
$topItems = $db->resultSet();

// Get recent usage records
$db->query("SELECT tu.*, w.item_name, w.unit, w.unit_price 
            FROM tool_usage tu 
            LEFT JOIN warehouse w ON tu.warehouse_id = w.id 
            ORDER BY tu.usage_date DESC LIMIT 10");
$recentUsage = $db->resultSet();

// Get usage details per item for selected period
if ($filterMonth > 0) {
    $db->query("SELECT tu.*, w.item_name, w.unit, w.unit_price 
                FROM tool_usage tu 
                LEFT JOIN warehouse w ON tu.warehouse_id = w.id 
                WHERE MONTH(tu.usage_date) = :month AND YEAR(tu.usage_date) = :year
                ORDER BY tu.usage_date DESC");
    $db->bind(':month', $filterMonth);
    $db->bind(':year', $filterYear);
    $periodUsage = $db->resultSet();
} else {
    $db->query("SELECT tu.*, w.item_name, w.unit, w.unit_price 
                FROM tool_usage tu 
                LEFT JOIN warehouse w ON tu.warehouse_id = w.id 
                WHERE YEAR(tu.usage_date) = :year
                ORDER BY tu.usage_date DESC");
    $db->bind(':year', $filterYear);
    $periodUsage = $db->resultSet();
}

$kurdishMonths = ['', 'کانوونی دووەم', 'شوبات', 'ئازار', 'نیسان', 'ئایار', 'حوزەیران', 'تەممووز', 'ئاب', 'ئەیلوول', 'تشرینی یەکەم', 'تشرینی دووەم', 'کانوونی یەکەم'];

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header no-print">
    <div>
        <h2><i class="fas fa-tools"></i> راپۆرتی ئامیر و ئەداوات</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item active">راپۆرتی ئامیر و ئەداوات</li>
            </ol>
        </nav>
    </div>
    <div>
        <button onclick="window.print()" class="btn btn-secondary no-print">
            <i class="fas fa-print"></i> چاپکردن
        </button>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4 no-print" style="border:none; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-radius: 12px; overflow:hidden;">
    <div class="card-body py-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
        <form method="GET" action="" class="row g-3 align-items-end justify-content-center" id="filterForm">
            <div class="col-auto">
                <label class="form-label mb-1 fw-bold" style="font-size:13px;"><i class="fas fa-calendar-alt text-primary"></i> مانگ</label>
                <select name="month" class="form-select" style="min-width:160px; border-radius:8px; border:2px solid #dee2e6; font-weight:600;">
                    <?php foreach ($availableMonths as $m): ?>
                    <option value="<?php echo $m; ?>" <?php echo $filterMonth == $m ? 'selected' : ''; ?>>
                        <?php echo $kurdishMonths[$m]; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label mb-1 fw-bold" style="font-size:13px;"><i class="fas fa-calendar text-primary"></i> ساڵ</label>
                <select name="year" class="form-select" style="min-width:100px; border-radius:8px; border:2px solid #dee2e6; font-weight:600;">
                    <?php for ($y = $maxYear; $y >= $minYear; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $filterYear == $y ? 'selected' : ''; ?>>
                        <?php echo $y; ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary" style="border-radius:8px; padding: 8px 20px; font-weight:700;">
                    <i class="fas fa-filter"></i> فلتەر
                </button>
            </div>
        </form>
    </div>
</div>

<div id="reportContent">
    <!-- Report Header -->
    <div class="text-center mb-4">
        <h3><?php echo SITE_NAME; ?></h3>
        <h4>راپۆرتی ئامیر و ئەداوات و خەرجی سەرفکردن</h4>
        <p class="text-muted">
            <?php 
            if ($filterMonth > 0) {
                echo $kurdishMonths[intval($filterMonth)] . ' / ' . $filterYear;
            } else {
                echo 'ساڵی ' . $filterYear;
            }
            ?> 
            - بەرواری چاپ: <?php echo date('Y/m/d H:i'); ?>
        </p>
    </div>
    
    <!-- Summary Stats -->
    <div class="row g-4 mb-4 no-print">
        <div class="col-md-6">
            <div class="stat-card">
                <div class="icon bg-primary">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="info">
                    <h3><?php echo count($items); ?></h3>
                    <p>کۆی ئامیرەکان</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="stat-card">
                <div class="icon bg-danger">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="info">
                    <h3><?php echo number_format($totalPurchaseCost); ?></h3>
                    <p>کۆی خەرجی کڕین (<?php echo CURRENCY; ?>)</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- All Items Table -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <i class="fas fa-list-alt"></i> ئامیرەکان و خەرجی کڕین
        </div>
        <div class="card-body">
            <?php if (empty($items)): ?>
            <div class="text-center py-4">
                <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                <p class="text-muted">هیچ داتایەک نییە بۆ ئەم ماوەیە</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="toolsTable" style="text-align:center;">
                    <thead>
                        <tr>
                            <th style="text-align:center;">#</th>
                            <th style="text-align:center;">ناوی ئامیر</th>
                            <th style="text-align:center;">ژمارە</th>
                            <th style="text-align:center;">یەکە</th>
                            <th style="text-align:center;">نرخی یەکە</th>
                            <th style="text-align:center;">خەرجی کڕین</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $index => $item): 
                            $purchaseCost = $item['quantity'] * $item['unit_price'];
                        ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><strong><?php echo $item['item_name']; ?></strong></td>
                            <td><?php echo number_format($item['quantity']); ?></td>
                            <td><?php echo $item['unit']; ?></td>
                            <td><?php echo number_format($item['unit_price']); ?> <?php echo CURRENCY; ?></td>
                            <td><strong class="text-danger"><?php echo number_format($purchaseCost); ?> <?php echo CURRENCY; ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="font-weight:bold; background:#f8f9fa;">
                            <td colspan="5" style="text-align:center;">کۆی گشتی</td>
                            <td style="text-align:center;"><strong class="text-danger"><?php echo number_format($totalPurchaseCost); ?> <?php echo CURRENCY; ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once $basePath . 'includes/footer.php'; ?>
<style>
@media print {
    @page { margin: 10mm; size: A4; }
    .no-print, .sidebar, .page-header, .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate { display: none !important; }
    .main-content { margin: 0 !important; padding: 0 !important; }
    .card { border: none !important; box-shadow: none !important; }
    .card-header { background: #f0f0f0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { direction: rtl; font-family: 'Noto Sans Arabic', Arial, sans-serif; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
}
</style>
<script>
$(document).ready(function() {
    // Auto-submit filter on change
    $('#filterForm select').on('change', function() {
        $('#filterForm').submit();
    });

    if ($.fn.DataTable.isDataTable('#toolsTable')) {
        $('#toolsTable').DataTable().destroy();
    }
    $('#toolsTable').DataTable({
        language: {
            search: "گەڕان:",
            lengthMenu: 'نیشاندانی <select class="form-select form-select-sm d-inline-block" style="width:70px;">'+
                        '<option value="10">10</option>'+
                        '<option value="15">15</option>'+
                        '<option value="25">25</option>'+
                        '<option value="50">50</option>'+        
                        '</select> ڕیز',
            info: "نیشاندانی _START_ تا _END_ لە _TOTAL_",
            infoFiltered: "",
            infoEmpty: "",
            emptyTable: "هیچ داتایەک نییە",
            zeroRecords: "هیچ ئەنجامێک نەدۆزرایەوە",
            paginate: { previous: "پێشتر", next: "دواتر" }
        },
        order: [[0, 'asc']],
        pageLength: 15
    });
});
</script>
