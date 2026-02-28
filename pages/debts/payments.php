<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'debts';
$pageTitle = 'پارەدانی کڕیار';

$customer_id = intval($_GET['customer_id'] ?? 0);
if ($customer_id <= 0) { header('Location: list.php'); exit; }

// Get customer info
$db->query("SELECT * FROM customers WHERE id = :id");
$db->bind(':id', $customer_id);
$customer = $db->single();
if (!$customer) { header('Location: list.php'); exit; }

// Handle payment submission
$message = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payAmount = floatval($_POST['amount'] ?? 0);
    $payDate = $_POST['payment_date'] ?? date('Y-m-d');
    $payNotes = $_POST['notes'] ?? '';
    
    if ($payAmount <= 0) {
        $message = 'تکایە بڕێکی دروست بنووسە';
        $messageType = 'danger';
    } else {
        // Insert payment
        $db->query("INSERT INTO customer_payments (customer_id, sale_id, amount, payment_date, notes) 
                    VALUES (:cid, NULL, :amount, :date, :notes)");
        $db->bind(':cid', $customer_id);
        $db->bind(':amount', $payAmount);
        $db->bind(':date', $payDate);
        $db->bind(':notes', $payNotes);
        
        if ($db->execute()) {
            // Distribute payment across unpaid sales (oldest first)
            $db->query("SELECT id, total_price, paid_amount FROM sales 
                        WHERE customer_id = :cid AND paid_amount < total_price 
                        ORDER BY sale_date ASC");
            $db->bind(':cid', $customer_id);
            $unpaidSales = $db->resultSet();
            
            $remaining = $payAmount;
            foreach ($unpaidSales as $us) {
                if ($remaining <= 0) break;
                $owed = $us['total_price'] - $us['paid_amount'];
                $apply = min($remaining, $owed);
                $db->query("UPDATE sales SET paid_amount = paid_amount + :amount WHERE id = :id");
                $db->bind(':amount', $apply);
                $db->bind(':id', $us['id']);
                $db->execute();
                $remaining -= $apply;
            }
            
            // Add to transactions
            $db->query("INSERT INTO transactions (transaction_type, category, amount, description, reference_type, reference_id, transaction_date, created_at) 
                        VALUES ('income', :category, :amount, :description, 'payment', :ref_id, :date, NOW())");
            $db->bind(':category', 'پارەدانی قەرز');
            $db->bind(':amount', $payAmount);
            $db->bind(':description', 'پارەدانی قەرز - ' . $customer['name']);
            $db->bind(':ref_id', $customer_id);
            $db->bind(':date', $payDate);
            $db->execute();
            
            $message = 'پارەدان بە سەرکەوتوویی تۆمارکرا';
            $messageType = 'success';
        } else {
            $message = 'هەڵەیەک ڕوویدا';
            $messageType = 'danger';
        }
    }
}

// Get customer sales with debt
$db->query("SELECT s.*, 
            (s.total_price - s.paid_amount) as remaining
            FROM sales s 
            WHERE s.customer_id = :cid 
            ORDER BY s.sale_date DESC");
$db->bind(':cid', $customer_id);
$sales = $db->resultSet();

// Calculate totals
$totalSales = 0;
$totalPaid = 0;
foreach ($sales as $s) {
    $totalSales += $s['total_price'];
    $totalPaid += $s['paid_amount'];
}
$totalDebt = $totalSales - $totalPaid;

require_once $basePath . 'includes/header.php';
?>

<style>
.cust-hdr { background: linear-gradient(135deg, #2c3e50, #34495e); color: #fff; border-radius: 12px; padding: 22px 28px; margin-bottom: 20px; }
.cust-hdr .ch-name { font-size: 22px; font-weight: 800; }
.cust-hdr .ch-info { font-size: 13px; opacity: .8; margin-top: 4px; }
.cust-hdr .ch-debt { font-size: 28px; font-weight: 900; color: #e74c3c; }
.cust-hdr .ch-debt.clear { color: #2ecc71; }
.stat-mini { text-align: center; padding: 14px; background: rgba(255,255,255,.1); border-radius: 8px; }
.stat-mini .sm-val { font-size: 18px; font-weight: 800; display: block; }
.stat-mini .sm-lbl { font-size: 11px; opacity: .7; }
.sale-row-unpaid { border-right: 3px solid #e74c3c !important; }
.sale-row-paid { border-right: 3px solid #27ae60 !important; }
.pay-form { border-radius: 12px; border: 2px solid #27ae60; }
.pay-form .card-header { background: #27ae60 !important; color: #fff; }
.pay-log .card { border: none; box-shadow: 0 2px 8px rgba(0,0,0,.05); border-radius: 10px; }
</style>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-money-bill-wave"></i> پارەدانی کڕیار</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item"><a href="list.php">قەرزەکان</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($customer['name']); ?></li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-right"></i> گەڕانەوە</a>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
    <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Customer Header -->
<div class="cust-hdr">
    <div class="row align-items-center">
        <div class="col-md-5">
            <div class="ch-name"><i class="fas fa-user"></i> <?php echo htmlspecialchars($customer['name']); ?></div>
            <div class="ch-info">
                <?php if (!empty($customer['phone'])): ?><i class="fas fa-phone"></i> <?php echo $customer['phone']; ?> &nbsp; <?php endif; ?>
                <?php if (!empty($customer['address'])): ?><i class="fas fa-map-marker-alt"></i> <?php echo $customer['address']; ?><?php endif; ?>
            </div>
        </div>
        <div class="col-md-7">
            <div class="row g-2 mt-2 mt-md-0">
                <div class="col-4">
                    <div class="stat-mini">
                        <span class="sm-val"><?php echo formatMoney($totalSales); ?></span>
                        <span class="sm-lbl">کۆی فرۆشتن</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="stat-mini">
                        <span class="sm-val" style="color:#2ecc71;"><?php echo formatMoney($totalPaid); ?></span>
                        <span class="sm-lbl">دراو</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="stat-mini">
                        <span class="sm-val ch-debt <?php echo $totalDebt <= 0 ? 'clear' : ''; ?>"><?php echo formatMoney($totalDebt); ?></span>
                        <span class="sm-lbl"><?php echo $totalDebt > 0 ? 'قەرزی ماوە' : 'قەرز نیە'; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Payment Form -->
    <?php if ($totalDebt > 0): ?>
    <div class="col-lg-6 mx-auto">
        <div class="card pay-form">
            <div class="card-header">
                <h6 class="mb-0 fw-bold"><i class="fas fa-plus-circle"></i> تۆمارکردنی پارەدانی نوێ</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">بڕی پارە <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <input type="number" name="amount" class="form-control" min="1" max="<?php echo $totalDebt; ?>" required placeholder="بڕی پارەدان...">
                            <span class="input-group-text"><?php echo CURRENCY; ?></span>
                        </div>
                        <small class="text-muted">قەرزی ماوە: <strong class="text-danger"><?php echo formatMoney($totalDebt); ?></strong></small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">بەروار</label>
                        <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">تێبینی</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="تێبینی..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100 btn-lg">
                        <i class="fas fa-check-circle"></i> تۆمارکردنی پارەدان
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once $basePath . 'includes/footer.php'; ?>
