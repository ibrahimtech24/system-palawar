<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'warehouse';
$pageTitle = 'سەرفکردنی ئامیر و ئەداوات';

// Get all warehouse items for dropdown
$db->query("SELECT * FROM warehouse ORDER BY item_name");
$warehouseItems = $db->resultSet();

$message = '';
$messageType = '';

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Get the usage record to restore quantity
    $db->query("SELECT * FROM tool_usage WHERE id = :id");
    $db->bind(':id', $id);
    $usage = $db->single();
    
    if ($usage) {
        // Restore the quantity back to warehouse
        $db->query("UPDATE warehouse SET quantity = quantity + :qty WHERE id = :wid");
        $db->bind(':qty', $usage['quantity_used']);
        $db->bind(':wid', $usage['warehouse_id']);
        $db->execute();
        
        // Delete the usage record
        $db->query("DELETE FROM tool_usage WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();
        
        $message = 'تۆمارەکە بە سەرکەوتوویی سڕایەوە و بڕەکە گەڕایەوە بۆ مەخزەن';
        $messageType = 'success';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $warehouse_id = intval($_POST['warehouse_id'] ?? 0);
    $quantity_used = intval($_POST['quantity_used'] ?? 0);
    $usage_date = $_POST['usage_date'] ?? date('Y-m-d');
    $purpose = $_POST['purpose'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if ($warehouse_id <= 0 || $quantity_used <= 0 || empty($usage_date)) {
        $message = 'تکایە هەموو خانە پێویستەکان پڕ بکەوە';
        $messageType = 'danger';
    } else {
        // Check available quantity
        $db->query("SELECT * FROM warehouse WHERE id = :id");
        $db->bind(':id', $warehouse_id);
        $item = $db->single();
        
        if (!$item) {
            $message = 'ئەم ئامیرە بوونی نییە';
            $messageType = 'danger';
        } elseif ($item['quantity'] < $quantity_used) {
            $message = 'بڕی سەرفکراو زیاترە لە بڕی بەردەست (' . number_format($item['quantity']) . ' ' . $item['unit'] . ')';
            $messageType = 'danger';
        } else {
            // Insert usage record
            $db->query("INSERT INTO tool_usage (warehouse_id, quantity_used, usage_date, purpose, notes, created_at) 
                        VALUES (:wid, :qty, :udate, :purpose, :notes, NOW())");
            $db->bind(':wid', $warehouse_id);
            $db->bind(':qty', $quantity_used);
            $db->bind(':udate', $usage_date);
            $db->bind(':purpose', $purpose);
            $db->bind(':notes', $notes);
            $db->execute();
            
            // Reduce quantity from warehouse
            $db->query("UPDATE warehouse SET quantity = quantity - :qty WHERE id = :id");
            $db->bind(':qty', $quantity_used);
            $db->bind(':id', $warehouse_id);
            $db->execute();
            
            $message = 'سەرفکردنەکە بە سەرکەوتوویی تۆمارکرا';
            $messageType = 'success';
        }
    }
}

// Get filter parameters
$filterMonth = $_GET['month'] ?? date('m');
$filterYear = $_GET['year'] ?? date('Y');

// Get usage records with filters
$db->query("SELECT tu.*, w.item_name, w.unit, w.unit_price 
            FROM tool_usage tu 
            LEFT JOIN warehouse w ON tu.warehouse_id = w.id 
            WHERE MONTH(tu.usage_date) = :month AND YEAR(tu.usage_date) = :year
            ORDER BY tu.usage_date DESC");
$db->bind(':month', $filterMonth);
$db->bind(':year', $filterYear);
$usageRecords = $db->resultSet();

// Calculate total expense
$totalExpense = 0;
foreach ($usageRecords as $record) {
    $totalExpense += ($record['quantity_used'] * $record['unit_price']);
}

// Get all-time total expense
$db->query("SELECT SUM(tu.quantity_used * w.unit_price) as total 
            FROM tool_usage tu 
            LEFT JOIN warehouse w ON tu.warehouse_id = w.id");
$allTimeExpense = $db->single()['total'] ?? 0;

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-clipboard-list"></i> سەرفکردنی ئامیر و ئەداوات</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item"><a href="list.php">ئامیر و ئەداوات</a></li>
                <li class="breadcrumb-item active">سەرفکردن</li>
            </ol>
        </nav>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
    <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Add Usage Form -->
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <i class="fas fa-plus-circle"></i> تۆمارکردنی سەرفکردن
    </div>
    <div class="card-body">
        <form method="POST" class="needs-validation" novalidate>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">ئامیر / ئەداوات <span class="text-danger">*</span></label>
                    <select name="warehouse_id" class="form-select" required>
                        <option value="">هەڵبژاردن...</option>
                        <?php foreach ($warehouseItems as $item): ?>
                        <option value="<?php echo $item['id']; ?>" data-qty="<?php echo $item['quantity']; ?>" data-unit="<?php echo $item['unit']; ?>">
                            <?php echo $item['item_name']; ?> (<?php echo number_format($item['quantity']); ?> <?php echo $item['unit']; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">بڕی سەرفکراو <span class="text-danger">*</span></label>
                    <input type="number" name="quantity_used" class="form-control" min="1" required>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">بەروار <span class="text-danger">*</span></label>
                    <input type="date" name="usage_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">مەبەست</label>
                    <input type="text" name="purpose" class="form-control" placeholder="بۆ چی سەرفکرا...">
                </div>
                
                <div class="col-md-9">
                    <label class="form-label">تێبینی</label>
                    <input type="text" name="notes" class="form-control" placeholder="تێبینی دڵخوازانە...">
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="fas fa-save"></i> تۆمارکردن
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-1">خەرجی ئەم مانگە</h6>
                        <h3 class="mb-0"><?php echo number_format($totalExpense); ?> <?php echo CURRENCY; ?></h3>
                    </div>
                    <div class="stat-icon bg-white bg-opacity-25">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-1">کۆی خەرجی گشتی</h6>
                        <h3 class="mb-0"><?php echo number_format($allTimeExpense); ?> <?php echo CURRENCY; ?></h3>
                    </div>
                    <div class="stat-icon bg-white bg-opacity-25">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-1">ژمارەی سەرفکردن</h6>
                        <h3 class="mb-0"><?php echo count($usageRecords); ?></h3>
                    </div>
                    <div class="stat-icon bg-white bg-opacity-25">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label mb-1">مانگ</label>
                <select name="month" class="form-select form-select-sm">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo $filterMonth == $m ? 'selected' : ''; ?>>
                        <?php echo $m; ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label mb-1">ساڵ</label>
                <select name="year" class="form-select form-select-sm">
                    <?php for ($y = 2024; $y <= date('Y') + 1; $y++): ?>
                    <option value="<?php echo $y; ?>" <?php echo $filterYear == $y ? 'selected' : ''; ?>>
                        <?php echo $y; ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-filter"></i> فلتەر
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Usage Records Table -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-history"></i> مێژووی سەرفکردن - مانگی <?php echo $filterMonth; ?>/<?php echo $filterYear; ?>
    </div>
    <div class="card-body">
        <?php if (empty($usageRecords)): ?>
        <div class="text-center py-5">
            <i class="fas fa-clipboard fa-4x text-muted mb-3"></i>
            <h5>هیچ تۆمارێکی سەرفکردن نییە</h5>
            <p class="text-muted">لە ئەم مانگەدا هیچ ئامیرێک سەرفنەکراوە</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table id="usageTable" class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ناوی ئامیر</th>
                        <th>بڕی سەرفکراو</th>
                        <th>نرخی یەکە</th>
                        <th>کۆی خەرجی</th>
                        <th>بەروار</th>
                        <th>مەبەست</th>
                        <th>کردارەکان</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usageRecords as $index => $record): 
                        $recordExpense = $record['quantity_used'] * $record['unit_price'];
                    ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><strong><?php echo $record['item_name'] ?? 'سڕاوەتەوە'; ?></strong></td>
                        <td><?php echo number_format($record['quantity_used']); ?> <?php echo $record['unit'] ?? ''; ?></td>
                        <td><?php echo number_format($record['unit_price'] ?? 0); ?> <?php echo CURRENCY; ?></td>
                        <td><strong class="text-danger"><?php echo number_format($recordExpense); ?> <?php echo CURRENCY; ?></strong></td>
                        <td><?php echo date('Y/m/d', strtotime($record['usage_date'])); ?></td>
                        <td><?php echo $record['purpose'] ?? '-'; ?></td>
                        <td>
                            <a href="#" onclick="return confirmDelete('usage.php?delete=<?php echo $record['id']; ?>&month=<?php echo $filterMonth; ?>&year=<?php echo $filterYear; ?>', 'ئایا دڵنیایت لە سڕینەوەی ئەم تۆمارە؟ بڕەکە دەگەڕێتەوە بۆ مەخزەن')" class="btn btn-outline-danger btn-sm" title="سڕینەوە">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-warning">
                        <td colspan="4" class="text-end"><strong>کۆی خەرجی گشتی:</strong></td>
                        <td colspan="4"><strong class="text-danger"><?php echo number_format($totalExpense); ?> <?php echo CURRENCY; ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once $basePath . 'includes/footer.php'; ?>
<script>
$(document).ready(function() {
    $('#usageTable').DataTable({
        language: {
            search: "گەڕان:",
            lengthMenu: "نیشاندانی _MENU_ ڕیز",
            info: "نیشاندانی _START_ تا _END_ لە _TOTAL_",
            infoEmpty: "",
            emptyTable: "هیچ داتایەک نییە",
            zeroRecords: "هیچ ئەنجامێک نەدۆزرایەوە",
            paginate: { previous: "پێشتر", next: "دواتر" }
        },
        order: [[5, 'desc']],
        pageLength: 25
    });
});
</script>
