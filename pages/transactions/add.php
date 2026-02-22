<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$currentPage = 'transactions';
$pageTitle = 'زیادکردنی مامەڵە';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['transaction_type'] ?? '';
    $category = $_POST['category'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $description = $_POST['description'] ?? '';
    $date = $_POST['transaction_date'] ?? date('Y-m-d');
    
    if (empty($type) || empty($category) || $amount <= 0) {
        $message = 'تکایە هەموو خانەکان پڕ بکەوە';
        $messageType = 'danger';
    } else {
        $db->query("INSERT INTO transactions (transaction_type, category, amount, description, transaction_date, created_at) 
                    VALUES (:type, :category, :amount, :description, :date, NOW())");
        $db->bind(':type', $type);
        $db->bind(':category', $category);
        $db->bind(':amount', $amount);
        $db->bind(':description', $description);
        $db->bind(':date', $date);
        
        if ($db->execute()) {
            header('Location: list.php?success=1');
            exit;
        } else {
            $message = 'هەڵەیەک ڕوویدا، تکایە دووبارە هەوڵ بدەوە';
            $messageType = 'danger';
        }
    }
}

require_once $basePath . 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h2><i class="fas fa-plus-circle"></i> زیادکردنی مامەڵە</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $basePath; ?>index.php">سەرەکی</a></li>
                <li class="breadcrumb-item"><a href="list.php">مامەڵەکان</a></li>
                <li class="breadcrumb-item active">زیادکردن</li>
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

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-exchange-alt"></i> فۆڕمی زیادکردنی مامەڵە
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <!-- Transaction Type -->
                        <div class="col-md-6">
                            <label class="form-label">جۆری مامەڵە <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="transaction_type" id="typeIncome" value="income" required>
                                    <label class="form-check-label text-success" for="typeIncome">
                                        <i class="fas fa-arrow-down"></i> داهات
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="transaction_type" id="typeExpense" value="expense">
                                    <label class="form-check-label text-danger" for="typeExpense">
                                        <i class="fas fa-arrow-up"></i> خەرجی
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Category -->
                        <div class="col-md-6">
                            <label class="form-label">پۆل <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" id="categorySelect" required>
                                <option value="">پۆل هەڵبژێرە...</option>
                            </select>
                        </div>
                        
                        <!-- Amount -->
                        <div class="col-md-6">
                            <label class="form-label">بڕ (<?php echo CURRENCY; ?>) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" min="0" step="0.01" required>
                        </div>
                        
                        <!-- Date -->
                        <div class="col-md-6">
                            <label class="form-label">بەروار <span class="text-danger">*</span></label>
                            <input type="date" name="transaction_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label">وەسف</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="وەسفی مامەڵەکە..."></textarea>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> گەڕانەوە
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> تۆمارکردن
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const incomeCategories = [
    'فرۆشتنی هێلکە',
    'فرۆشتنی جوجکە',
    'فرۆشتنی باڵندە',
    'داهاتی تر'
];

const expenseCategories = [
    'کڕینی خۆراک',
    'کڕینی دەرمان',
    'کڕینی کەرەستە',
    'کڕینی باڵندە',
    'کرێی کارگێڕی',
    'کارەبا و ئاو',
    'گواستنەوە',
    'چاککردنەوە',
    'خەرجی تر'
];

document.querySelectorAll('input[name="transaction_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const select = document.getElementById('categorySelect');
        const categories = this.value === 'income' ? incomeCategories : expenseCategories;
        
        select.innerHTML = '<option value="">پۆل هەڵبژێرە...</option>';
        categories.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat;
            option.textContent = cat;
            select.appendChild(option);
        });
    });
});
</script>

<?php require_once $basePath . 'includes/footer.php'; ?>
