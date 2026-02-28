<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'reports';
$pageTitle = 'باکئەپەکانی راپۆرت';

// Kurdish month names
$kurdishMonths = [
    1 => 'کانوونی دووەم', 2 => 'شوبات', 3 => 'ئازار', 4 => 'نیسان',
    5 => 'ئایار', 6 => 'حوزەیران', 7 => 'تەمموز', 8 => 'ئاب',
    9 => 'ئەیلوول', 10 => 'تشرینی یەکەم', 11 => 'تشرینی دووەم', 12 => 'کانوونی یەکەم'
];

// Handle restore request
$restoreMessage = '';
if (isset($_POST['restore_backup']) && isset($_POST['backup_id'])) {
    $backupId = (int)$_POST['backup_id'];
    
    // Get the backup data
    $db->query("SELECT * FROM monthly_report_backups WHERE id = :id");
    $db->bind(':id', $backupId);
    $backup = $db->single();
    
    if ($backup) {
        // Update the main report with backup data
        $db->query("UPDATE monthly_reports SET 
            sales_total = :sales,
            purchases_total = :purchases,
            income_total = :income,
            expense_total = :expense,
            warehouse_cost = :warehouse,
            eggs_collected = :eggs,
            chicks_hatched = :chicks,
            male_birds_qty = :male_birds,
            female_birds_qty = :female_birds,
            incubator_eggs = :incubator,
            real_profit = :profit,
            customer_debt = :debt,
            report_data = :report_data
            WHERE month = :month AND year = :year");
        
        $db->bind(':month', $backup['month']);
        $db->bind(':year', $backup['year']);
        $db->bind(':sales', $backup['sales_total']);
        $db->bind(':purchases', $backup['purchases_total']);
        $db->bind(':income', $backup['income_total']);
        $db->bind(':expense', $backup['expense_total']);
        $db->bind(':warehouse', $backup['warehouse_expense']);
        $db->bind(':eggs', $backup['eggs_collected']);
        $db->bind(':chicks', $backup['chicks_hatched']);
        $db->bind(':male_birds', $backup['male_birds_count']);
        $db->bind(':female_birds', $backup['female_birds_count']);
        $db->bind(':incubator', $backup['incubator_count']);
        $db->bind(':profit', $backup['real_profit']);
        $db->bind(':debt', $backup['debt_total']);
        $db->bind(':report_data', $backup['report_data']);
        
        if ($db->execute()) {
            $restoreMessage = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> باکئەپ گەڕایەوە بە سەرکەوتوویی!</div>';
        }
    }
}

// Get filter parameters
$filterMonth = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$filterYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Get all backups
$sql = "SELECT * FROM monthly_report_backups WHERE 1=1";
if ($filterMonth > 0) {
    $sql .= " AND month = :month";
}
if ($filterYear > 0) {
    $sql .= " AND year = :year";
}
$sql .= " ORDER BY backup_date DESC LIMIT 100";

$db->query($sql);
if ($filterMonth > 0) {
    $db->bind(':month', $filterMonth);
}
if ($filterYear > 0) {
    $db->bind(':year', $filterYear);
}
$backups = $db->resultSet();

// Get available years for filter
$db->query("SELECT DISTINCT year FROM monthly_report_backups ORDER BY year DESC");
$availableYears = array_column($db->resultSet(), 'year');

// Get backup statistics
$db->query("SELECT COUNT(*) as total, MIN(backup_date) as oldest, MAX(backup_date) as newest FROM monthly_report_backups");
$stats = $db->single();

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header no-print">
    <div>
        <h2><i class="fas fa-history"></i> باکئەپەکانی راپۆرت</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item"><a href="monthly.php">راپۆرتی مانگانە</a></li>
                <li class="breadcrumb-item active">باکئەپەکان</li>
            </ol>
        </nav>
    </div>
    <a href="history.php" class="btn btn-outline-primary">
        <i class="fas fa-archive"></i> راپۆرتە خەزنکراوەکان
    </a>
</div>

<?php echo $restoreMessage; ?>

<!-- Stats -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white;">
            <div class="card-body py-3">
                <h3 class="mb-0"><?php echo number_format($stats['total'] ?? 0); ?></h3>
                <small>کۆی باکئەپەکان</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-0" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color:white;">
            <div class="card-body py-3">
                <h3 class="mb-0"><?php echo $stats['oldest'] ? date('Y/m/d', strtotime($stats['oldest'])) : '-'; ?></h3>
                <small>کۆنترین باکئەپ</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-0" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color:white;">
            <div class="card-body py-3">
                <h3 class="mb-0"><?php echo $stats['newest'] ? date('Y/m/d', strtotime($stats['newest'])) : '-'; ?></h3>
                <small>نوێترین باکئەپ</small>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-3 align-items-end justify-content-center">
            <div class="col-auto">
                <label class="form-label mb-1">مانگ</label>
                <select name="month" class="form-select">
                    <option value="0">هەموو</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo $filterMonth == $m ? 'selected' : ''; ?>>
                        <?php echo $kurdishMonths[$m]; ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label mb-1">ساڵ</label>
                <select name="year" class="form-select">
                    <option value="0">هەموو</option>
                    <?php foreach ($availableYears as $y): ?>
                    <option value="<?php echo $y; ?>" <?php echo $filterYear == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> فلتەر</button>
            </div>
        </form>
    </div>
</div>

<?php if (empty($backups)): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> هیچ باکئەپێک نیە.
</div>
<?php else: ?>

<!-- Backups Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead style="background:#f8f9fa;">
                    <tr>
                        <th>#</th>
                        <th>مانگ/ساڵ</th>
                        <th>فرۆشتن</th>
                        <th>کڕین</th>
                        <th>قازانج</th>
                        <th>هێلکە</th>
                        <th>جوجکە</th>
                        <th>بەرواری باکئەپ</th>
                        <th>هۆکار</th>
                        <th>کردار</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $i => $backup): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td>
                            <strong><?php echo $kurdishMonths[$backup['month']]; ?></strong>
                            <small class="text-muted"><?php echo $backup['year']; ?></small>
                        </td>
                        <td class="text-success"><?php echo formatMoney($backup['sales_total']); ?></td>
                        <td class="text-danger"><?php echo formatMoney($backup['purchases_total']); ?></td>
                        <td class="<?php echo $backup['real_profit'] >= 0 ? 'text-primary' : 'text-warning'; ?>">
                            <?php echo formatMoney($backup['real_profit']); ?>
                        </td>
                        <td><?php echo number_format($backup['eggs_collected']); ?></td>
                        <td><?php echo number_format($backup['chicks_hatched']); ?></td>
                        <td>
                            <small>
                                <?php echo date('Y/m/d', strtotime($backup['backup_date'])); ?>
                                <span class="text-muted"><?php echo date('H:i', strtotime($backup['backup_date'])); ?></span>
                            </small>
                        </td>
                        <td>
                            <span class="badge <?php echo $backup['backup_reason'] == 'auto_update' ? 'bg-info' : 'bg-secondary'; ?>">
                                <?php echo $backup['backup_reason'] == 'auto_update' ? 'نوێکردنەوە' : $backup['backup_reason']; ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" class="d-inline" onsubmit="return confirm('دڵنیایت دەتەوێت ئەم باکئەپە بگەڕێنیتەوە؟');">
                                <input type="hidden" name="backup_id" value="<?php echo $backup['id']; ?>">
                                <button type="submit" name="restore_backup" class="btn btn-sm btn-outline-warning" title="گەڕانەوە">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </form>
                            <a href="monthly.php?month=<?php echo $backup['month']; ?>&year=<?php echo $backup['year']; ?>" 
                               class="btn btn-sm btn-outline-primary" title="بینینی راپۆرتی ئێستا">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>

<div class="mt-4 text-muted text-center">
    <small><i class="fas fa-info-circle"></i> باکئەپەکان خۆکارانە دروست دەبن کاتێک داتاکان گۆڕانکاری تێدا دەکرێت</small>
</div>

<?php require_once $basePath . 'includes/footer.php'; ?>
