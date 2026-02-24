<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'transactions';
$pageTitle = 'مێژووی مامەڵەکان';

// Get filter parameters
$type = isset($_GET['type']) ? $_GET['type'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Build query
$whereConditions = ["DATE(transaction_date) BETWEEN :start AND :end"];
$params = [':start' => $startDate, ':end' => $endDate];

if (!empty($type)) {
    $whereConditions[] = "transaction_type = :type";
    $params[':type'] = $type;
}

$whereClause = implode(' AND ', $whereConditions);

$db->query("SELECT * FROM transactions WHERE {$whereClause} ORDER BY transaction_date DESC");
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$transactions = $db->resultSet();

// Calculate summaries
$db->query("SELECT 
    SUM(CASE WHEN transaction_type = 'income' THEN amount ELSE 0 END) as total_income,
    SUM(CASE WHEN transaction_type = 'expense' THEN amount ELSE 0 END) as total_expense
    FROM transactions 
    WHERE DATE(transaction_date) BETWEEN :start AND :end");
$db->bind(':start', $startDate);
$db->bind(':end', $endDate);
$summary = $db->single();

$totalIncome = $summary['total_income'] ?? 0;
$totalExpense = $summary['total_expense'] ?? 0;
$netProfit = $totalIncome - $totalExpense;

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-history"></i> مێژووی مامەڵەکان</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item active">مامەڵەکان</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> زیادکردنی مامەڵە
        </a>
        <button onclick="exportToPDF('transactionsList', 'transactions-<?php echo $startDate; ?>-<?php echo $endDate; ?>')" class="btn btn-danger">
            <i class="fas fa-file-pdf"></i> داگرتن بە PDF
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">جۆری مامەڵە</label>
                <select name="type" class="form-select">
                    <option value="">هەموو</option>
                    <option value="income" <?php echo $type == 'income' ? 'selected' : ''; ?>>داهات</option>
                    <option value="expense" <?php echo $type == 'expense' ? 'selected' : ''; ?>>خەرجی</option>
                </select>
            </div>
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
                    <i class="fas fa-search"></i> گەڕان
                </button>
                <a href="list.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> ڕیسێت
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="icon bg-success">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="info">
                <h3><?php echo formatMoney($totalIncome); ?></h3>
                <p>کۆی داهات</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stat-card">
            <div class="icon bg-danger">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="info">
                <h3><?php echo formatMoney($totalExpense); ?></h3>
                <p>کۆی خەرجی</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stat-card">
            <div class="icon <?php echo $netProfit >= 0 ? 'bg-primary' : 'bg-warning'; ?>">
                <i class="fas fa-balance-scale"></i>
            </div>
            <div class="info">
                <h3><?php echo formatMoney($netProfit); ?></h3>
                <p>قازانجی پاک</p>
            </div>
        </div>
    </div>
</div>

<!-- Transactions List -->
<div class="card" id="transactionsList">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list"></i> لیستی مامەڵەکان</span>
        <span class="badge bg-primary"><?php echo count($transactions); ?> مامەڵە</span>
    </div>
    <div class="card-body">
        <?php if (count($transactions) > 0): ?>
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>جۆر</th>
                        <th>پۆل</th>
                        <th>بڕ</th>
                        <th>وەسف</th>
                        <th>بەیانی</th>
                        <th>بەروار</th>
                        <th>کردارەکان</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $index => $transaction): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <?php if ($transaction['transaction_type'] == 'income'): ?>
                            <span class="badge bg-success"><i class="fas fa-arrow-down"></i> داهات</span>
                            <?php else: ?>
                            <span class="badge bg-danger"><i class="fas fa-arrow-up"></i> خەرجی</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $transaction['category']; ?></td>
                        <td>
                            <strong class="<?php echo $transaction['transaction_type'] == 'income' ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $transaction['transaction_type'] == 'income' ? '+' : '-'; ?>
                                <?php echo formatMoney($transaction['amount']); ?>
                            </strong>
                        </td>
                        <td><?php echo $transaction['description']; ?></td>
                        <td>
                            <?php if (!empty($transaction['reference_type']) && !empty($transaction['reference_id'])): ?>
                            <small class="text-muted">
                                <?php echo getReferenceName($transaction['reference_type']); ?> 
                                #<?php echo $transaction['reference_id']; ?>
                            </small>
                            <?php else: ?>
                            <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatDate($transaction['transaction_date']); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="edit.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-outline-primary" title="دەستکاری">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="#" onclick="return confirmDelete('delete.php?id=<?php echo $transaction['id']; ?>', 'ئایا دڵنیایت لە سڕینەوەی ئەم مامەڵەیە؟')" class="btn btn-sm btn-outline-danger" title="سڕینەوە">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-exchange-alt"></i>
            <p>هیچ مامەڵەیەک نیە</p>
            <a href="add.php" class="btn btn-primary mt-3">
                <i class="fas fa-plus"></i> زیادکردنی مامەڵە
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php 
// Helper function
function getReferenceName($type) {
    $types = [
        'sale' => 'فرۆشتن',
        'purchase' => 'کڕین',
        'warehouse' => 'کۆگا'
    ];
    return $types[$type] ?? $type;
}

require_once $basePath . 'includes/footer.php'; 
?>
