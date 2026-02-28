<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'debts';
$pageTitle = 'قەرزەکان';

// Get all customers with debt info
$db->query("SELECT c.id, c.name, c.phone, c.address,
            COALESCE(SUM(s.total_price), 0) as total_sales,
            COALESCE(SUM(s.paid_amount), 0) as total_paid,
            COALESCE(SUM(s.total_price), 0) - COALESCE(SUM(s.paid_amount), 0) as total_debt,
            COUNT(s.id) as sale_count
            FROM customers c
            LEFT JOIN sales s ON s.customer_id = c.id
            GROUP BY c.id
            HAVING total_debt > 0
            ORDER BY total_debt DESC");
$debtors = $db->resultSet();

$grandDebt = 0;
$totalPaidAll = 0;
foreach ($debtors as &$d) {
    $grandDebt += $d['total_debt'];
    $totalPaidAll += $d['total_paid'];
}
unset($d);

require_once $basePath . 'includes/header.php';
?>

<style>
.debt-card { border-radius: 12px; border: none; box-shadow: 0 2px 12px rgba(0,0,0,.06); overflow: hidden; }
.debt-card .card-body { padding: 20px; }
.debt-stat { text-align: center; padding: 20px; border-radius: 12px; }
.debt-stat .ds-val { font-size: 28px; font-weight: 800; display: block; }
.debt-stat .ds-label { font-size: 12px; font-weight: 600; margin-top: 4px; display: block; opacity: .8; }
.dt-name { font-size: 15px; font-weight: 700; color: #2c3e50; }
.dt-phone { font-size: 12px; color: #95a5a6; }
.dt-debt { font-size: 16px; font-weight: 800; color: #e74c3c; }
.dt-paid { font-size: 14px; font-weight: 700; color: #27ae60; }
.badge-count { font-size: 11px; padding: 4px 10px; border-radius: 20px; }
</style>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-file-invoice-dollar"></i> قەرزی کڕیاران</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item active">قەرزەکان</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="debt-stat bg-danger text-white">
            <span class="ds-val"><?php echo formatMoney($grandDebt); ?></span>
            <span class="ds-label"><i class="fas fa-exclamation-triangle"></i> کۆی قەرزی ماوە</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="debt-stat bg-warning text-dark">
            <span class="ds-val"><?php echo count($debtors); ?></span>
            <span class="ds-label"><i class="fas fa-users"></i> ژمارەی قەرزداران</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="debt-stat bg-success text-white">
            <span class="ds-val"><?php echo formatMoney($totalPaidAll); ?></span>
            <span class="ds-label"><i class="fas fa-check-circle"></i> کۆی دراو</span>
        </div>
    </div>
</div>

<!-- Debtors Table -->
<div class="card debt-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center" style="padding:16px 20px;">
        <h6 class="mb-0 fw-bold"><i class="fas fa-list text-danger"></i> لیستی قەرزداران</h6>
        <span class="badge bg-danger badge-count"><?php echo count($debtors); ?> کڕیار</span>
    </div>
    <!-- Filter -->
    <div class="card-body border-bottom py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label fw-bold" style="font-size:13px;"><i class="fas fa-search"></i> گەڕان</label>
                <input type="text" id="searchFilter" class="form-control" placeholder="ناو یان ژمارەی مۆبایل...">
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (count($debtors) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="debtsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>کڕیار</th>
                        <th>کۆی فرۆشتن</th>
                        <th>دراو</th>
                        <th class="text-danger">قەرزی ماوە</th>
                        <th>فرۆشتنەکان</th>
                        <th>کردارەکان</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($debtors as $i => $debtor): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td>
                            <div class="dt-name"><?php echo htmlspecialchars($debtor['name']); ?></div>
                            <?php if (!empty($debtor['phone'])): ?>
                            <div class="dt-phone"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($debtor['phone']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo formatMoney($debtor['total_sales']); ?></strong></td>
                        <td class="dt-paid"><?php echo formatMoney($debtor['total_paid']); ?></td>
                        <td class="dt-debt"><?php echo formatMoney($debtor['total_debt']); ?></td>
                        <td><span class="badge bg-secondary badge-count"><?php echo $debtor['sale_count']; ?></span></td>
                        <td>
                            <a href="payments.php?customer_id=<?php echo $debtor['id']; ?>" class="btn btn-sm btn-success" title="پارەدان">
                                <i class="fas fa-money-bill-wave"></i> پارەدان
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-check-circle fa-3x mb-3 text-success opacity-50"></i>
            <p class="mb-0 fs-5">هیچ قەرزێک نیە!</p>
            <p class="text-muted">هەموو کڕیاران قەرزیان دانەوە</p>
        </div>
        <?php endif; ?>
    </div>
</div>


<?php require_once $basePath . 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    var table = $('#debtsTable').DataTable({
        language: {
            search: "گەڕان:",
            lengthMenu: "نیشاندانی _MENU_ ڕیز",
            info: "نیشاندانی _START_ تا _END_ لە _TOTAL_",
            infoEmpty: "",
            infoFiltered: "",
            zeroRecords: "هیچ ئەنجامێک نەدۆزرایەوە",
            paginate: { previous: "پێشوو", next: "دواتر" }
        },
        order: [[4, 'desc']],
        pageLength: 25,
        responsive: true,
        dom: 'rtip'
    });

    // Custom search box
    $('#searchFilter').on('keyup', function() {
        table.search(this.value).draw();
    });
});
</script>
