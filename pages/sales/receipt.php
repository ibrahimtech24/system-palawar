<?php
$basePath = '../../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/database.php';
require_once $basePath . 'includes/functions.php';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: list.php');
    exit;
}

// Get sale with customer info
$db->query("SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address 
            FROM sales s 
            LEFT JOIN customers c ON s.customer_id = c.id 
            WHERE s.id = :id");
$db->bind(':id', $id);
$sale = $db->single();

if (!$sale) {
    header('Location: list.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وەسڵی فرۆشتن - <?php echo $sale['sale_code']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;500;600;700&display=swap');
        
        * {
            font-family: 'Noto Sans Arabic', sans-serif;
        }
        
        body {
            background: #f5f5f5;
            padding: 20px;
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .receipt {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .receipt-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .receipt-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .receipt-header p {
            margin: 10px 0 0;
            opacity: 0.9;
        }
        
        .receipt-body {
            padding: 30px;
        }
        
        .receipt-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px dashed #eee;
        }
        
        .receipt-info-item h6 {
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }
        
        .receipt-info-item p {
            margin: 0;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .receipt-table {
            width: 100%;
            margin-bottom: 30px;
        }
        
        .receipt-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: right;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        .receipt-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .receipt-total {
            background: #f8f9fa;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 2px solid #28a745;
        }
        
        .receipt-total h4 {
            margin: 0;
            color: #333;
        }
        
        .receipt-total .amount {
            font-size: 1.8rem;
            font-weight: 700;
            color: #28a745;
        }
        
        .receipt-footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 0.9rem;
            border-top: 1px solid #eee;
        }
        
        .btn-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .btn-actions, .no-print {
                display: none !important;
            }
            
            .receipt {
                box-shadow: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Action Buttons -->
        <div class="btn-actions no-print">
            <button onclick="downloadPDF()" class="btn btn-danger btn-lg">
                <i class="fas fa-file-pdf"></i> داگرتن بە PDF
            </button>
            <button onclick="window.print()" class="btn btn-primary btn-lg">
                <i class="fas fa-print"></i> چاپکردن
            </button>
            <a href="list.php" class="btn btn-secondary btn-lg">
                <i class="fas fa-arrow-right"></i> گەڕانەوە
            </a>
        </div>
        
        <!-- Receipt -->
        <div class="receipt" id="receiptContent">
            <div class="receipt-header">
                <h1><i class="fas fa-receipt"></i> وەسڵی فرۆشتن</h1>
                <p><?php echo SITE_NAME; ?></p>
            </div>
            
            <div class="receipt-body">
                <div class="receipt-info">
                    <div class="receipt-info-item">
                        <h6>کۆدی وەسڵ</h6>
                        <p><?php echo $sale['sale_code']; ?></p>
                    </div>
                    <div class="receipt-info-item">
                        <h6>بەروار</h6>
                        <p><?php echo date('Y/m/d', strtotime($sale['sale_date'])); ?></p>
                    </div>
                    <div class="receipt-info-item">
                        <h6>کڕیار</h6>
                        <p><?php echo $sale['customer_name'] ?? 'نەناسراو'; ?></p>
                    </div>
                </div>
                
                <?php if ($sale['customer_phone'] || $sale['customer_address']): ?>
                <div class="customer-details mb-4">
                    <h6 class="text-muted mb-2">زانیاری کڕیار</h6>
                    <?php if ($sale['customer_phone']): ?>
                    <p class="mb-1"><i class="fas fa-phone"></i> <?php echo $sale['customer_phone']; ?></p>
                    <?php endif; ?>
                    <?php if ($sale['customer_address']): ?>
                    <p class="mb-0"><i class="fas fa-map-marker-alt"></i> <?php echo $sale['customer_address']; ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th>کاڵا</th>
                            <th>ژمارە</th>
                            <th>نرخی یەکە</th>
                            <th>کۆ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong><?php echo getItemTypeName($sale['item_type']); ?></strong>
                            </td>
                            <td><?php echo number_format($sale['quantity']); ?></td>
                            <td><?php echo number_format($sale['unit_price']); ?> <?php echo CURRENCY; ?></td>
                            <td><strong><?php echo number_format($sale['total_price']); ?> <?php echo CURRENCY; ?></strong></td>
                        </tr>
                    </tbody>
                </table>
                
                <?php if ($sale['notes']): ?>
                <div class="notes mb-3">
                    <h6 class="text-muted">تێبینی:</h6>
                    <p><?php echo nl2br(htmlspecialchars($sale['notes'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="receipt-total">
                <h4>کۆی گشتی:</h4>
                <span class="amount"><?php echo number_format($sale['total_price']); ?> <?php echo CURRENCY; ?></span>
            </div>
            
            <div class="receipt-footer">
                <p>سوپاس بۆ کڕینتان</p>
                <small>ئەم وەسڵە لە <?php echo date('Y/m/d H:i'); ?> دەرچووە</small>
            </div>
        </div>
    </div>
    
    <script>
        function downloadPDF() {
            const element = document.getElementById('receiptContent');
            const opt = {
                margin: 10,
                filename: 'receipt-<?php echo $sale['sale_code']; ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 2,
                    useCORS: true,
                    letterRendering: true
                },
                jsPDF: { 
                    unit: 'mm', 
                    format: 'a4', 
                    orientation: 'portrait' 
                }
            };
            
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
