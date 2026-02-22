<?php
// Start session and check authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $rootPath = isset($basePath) ? $basePath : '';
    header('Location: ' . $rootPath . 'login.php');
    exit;
}
$bp = isset($basePath) ? $basePath : '';
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $bp; ?>css/style.css">
    
    <style>
        /* Navbar Improvements */
        .navbar-custom {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 0;
            box-shadow: 0 2px 15px rgba(0,0,0,0.2);
        }
        
        .navbar-custom .navbar-brand {
            padding: 15px 20px;
            font-size: 1.3rem;
            font-weight: 700;
            background: rgba(0,0,0,0.1);
        }
        
        .navbar-custom .navbar-brand i {
            color: #ffc107;
        }
        
        .navbar-custom .nav-link {
            padding: 18px 15px !important;
            font-size: 0.9rem;
            font-weight: 500;
            color: rgba(255,255,255,0.9) !important;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .navbar-custom .nav-link:hover,
        .navbar-custom .nav-link.active {
            background: rgba(255,255,255,0.1);
            border-bottom-color: #ffc107;
            color: #fff !important;
        }
        
        .navbar-custom .nav-link i {
            margin-left: 5px;
        }
        
        .navbar-custom .dropdown-menu {
            border: none;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            padding: 10px;
            min-width: 200px;
        }
        
        .navbar-custom .dropdown-item {
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .navbar-custom .dropdown-item:hover {
            background: #f0f7ff;
            color: #1e3c72;
        }
        
        .navbar-custom .dropdown-item i {
            width: 20px;
            margin-left: 8px;
            color: #2a5298;
        }
        
        .user-menu {
            background: rgba(255,255,255,0.1);
            border-radius: 50px;
            padding: 5px 15px !important;
            margin: 10px;
        }
        
        .user-menu:hover {
            background: rgba(255,255,255,0.2) !important;
        }
        
        /* Mobile Responsive */
        @media (max-width: 991px) {
            .navbar-custom {
                padding: 0;
            }
            
            .navbar-custom .navbar-brand {
                padding: 12px 15px;
                font-size: 1.1rem;
            }
            
            .navbar-custom .navbar-collapse {
                background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
                padding: 15px;
                max-height: 80vh;
                overflow-y: auto;
            }
            
            .navbar-custom .nav-link {
                padding: 12px 15px !important;
                border-radius: 8px;
                margin-bottom: 5px;
                border-bottom: none;
            }
            
            .navbar-custom .nav-link:hover,
            .navbar-custom .nav-link.active {
                background: rgba(255,255,255,0.15);
            }
            
            .navbar-custom .dropdown-menu {
                background: rgba(255,255,255,0.1);
                box-shadow: none;
                border-radius: 8px;
                margin-top: 5px;
                margin-bottom: 10px;
            }
            
            .navbar-custom .dropdown-item {
                color: rgba(255,255,255,0.9);
            }
            
            .navbar-custom .dropdown-item:hover {
                background: rgba(255,255,255,0.1);
                color: #fff;
            }
            
            .navbar-custom .dropdown-item i {
                color: rgba(255,255,255,0.7);
            }
            
            .user-menu {
                margin: 10px 0;
            }
        }
        
        .navbar-toggler {
            border: none;
            padding: 10px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
        }
        
        .navbar-toggler:focus {
            box-shadow: none;
            background: rgba(255,255,255,0.2);
        }
        
        .navbar-toggler-icon {
            width: 1.2em;
            height: 1.2em;
        }
        
        /* ===== COMPREHENSIVE RESPONSIVE STYLES ===== */
        
        /* Table Responsive */
        .table-responsive {
            -webkit-overflow-scrolling: touch;
            overflow-x: auto;
        }
        
        /* Cards Responsive */
        .card {
            margin-bottom: 1rem;
        }
        
        /* Stat Cards Responsive */
        .stat-card {
            transition: all 0.3s ease;
        }
        
        /* Page Header Responsive */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        /* ===== TABLET (768px - 992px) ===== */
        @media (max-width: 992px) {
            .main-content {
                padding: 1rem;
            }
            
            .page-header {
                flex-direction: column;
                text-align: center;
            }
            
            .page-header > div {
                width: 100%;
            }
            
            .page-header .btn {
                width: 100%;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-card .icon {
                width: 50px;
                height: 50px;
                font-size: 1.3rem;
            }
            
            .stat-card .info h3 {
                font-size: 1.4rem;
            }
            
            .quick-actions {
                flex-direction: column;
            }
            
            .quick-action-btn {
                width: 100%;
            }
        }
        
        /* ===== SMALL TABLET / LARGE PHONE (576px - 768px) ===== */
        @media (max-width: 768px) {
            body {
                font-size: 0.95rem;
            }
            
            .main-content {
                padding: 0.75rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .card-header {
                padding: 0.75rem 1rem;
                font-size: 0.95rem;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .row.g-3 > .col-md-4,
            .row.g-3 > .col-md-6,
            .row.g-4 > .col-md-4,
            .row.g-4 > .col-md-6 {
                width: 100% !important;
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            .input-group-lg .form-control,
            .input-group-lg .input-group-text {
                font-size: 1rem;
                padding: 0.5rem 0.75rem;
            }
            
            .form-control-lg {
                font-size: 1rem;
                padding: 0.5rem 0.75rem;
            }
            
            .btn-group {
                display: flex;
                flex-direction: column;
                gap: 0.25rem;
            }
            
            .btn-group .btn {
                width: 100%;
                border-radius: 0.375rem !important;
            }
            
            .btn-group-sm > .btn {
                padding: 0.25rem 0.4rem;
                font-size: 0.75rem;
            }
            
            .stat-card {
                flex-direction: column;
                text-align: center;
                padding: 1rem;
            }
            
            .stat-card .icon {
                margin-bottom: 0.75rem;
            }
            
            h1, .h1 { font-size: 1.5rem; }
            h2, .h2 { font-size: 1.3rem; }
            h3, .h3 { font-size: 1.1rem; }
            h4, .h4 { font-size: 1rem; }
            
            .table {
                font-size: 0.85rem;
            }
            
            .table th,
            .table td {
                padding: 0.5rem 0.4rem;
                vertical-align: middle;
            }
            
            .breadcrumb {
                font-size: 0.8rem;
                flex-wrap: wrap;
            }
            
            .alert {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }
            
            .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }
        }
        
        /* ===== MOBILE PHONE (max 576px) ===== */
        @media (max-width: 576px) {
            body {
                font-size: 0.9rem;
            }
            
            .container-fluid {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            .main-content {
                padding: 0.5rem;
            }
            
            .card {
                border-radius: 8px;
            }
            
            .card-body {
                padding: 0.75rem;
            }
            
            .card-header {
                padding: 0.6rem 0.75rem;
                font-size: 0.9rem;
            }
            
            .row.g-3,
            .row.g-4 {
                --bs-gutter-x: 0.5rem;
                --bs-gutter-y: 0.75rem;
            }
            
            .form-label {
                font-size: 0.85rem;
                margin-bottom: 0.25rem;
            }
            
            .form-control,
            .form-select {
                font-size: 0.9rem;
                padding: 0.5rem 0.6rem;
            }
            
            .input-group-text {
                font-size: 0.85rem;
                padding: 0.5rem 0.6rem;
            }
            
            .btn {
                font-size: 0.85rem;
                padding: 0.5rem 0.75rem;
            }
            
            .btn-lg {
                font-size: 0.9rem;
                padding: 0.5rem 1rem;
            }
            
            .btn-sm {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }
            
            .d-flex.justify-content-end,
            .d-flex.gap-2 {
                flex-direction: column;
            }
            
            .d-flex.justify-content-end .btn,
            .d-flex.gap-2 > .btn,
            .d-flex.gap-2 > a.btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .table {
                font-size: 0.8rem;
            }
            
            .table th,
            .table td {
                padding: 0.4rem 0.3rem;
            }
            
            /* Hide less important columns on mobile */
            .table th:nth-child(n+5):not(:last-child),
            .table td:nth-child(n+5):not(:last-child) {
                display: none;
            }
            
            .stat-card {
                padding: 0.75rem;
            }
            
            .stat-card .icon {
                width: 45px;
                height: 45px;
                font-size: 1.1rem;
            }
            
            .stat-card .info h3 {
                font-size: 1.2rem;
            }
            
            .stat-card .info p {
                font-size: 0.8rem;
                margin-bottom: 0;
            }
            
            .page-header h2 {
                font-size: 1.2rem;
            }
            
            .empty-state {
                padding: 2rem 1rem;
            }
            
            .empty-state i {
                font-size: 2.5rem;
            }
            
            .empty-state h4 {
                font-size: 1rem;
            }
            
            /* Action buttons in tables */
            .btn-action {
                padding: 0.3rem 0.5rem;
                font-size: 0.75rem;
            }
            
            /* Datatables responsive */
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                text-align: center !important;
                margin-bottom: 0.5rem;
            }
            
            .dataTables_wrapper .dataTables_length label,
            .dataTables_wrapper .dataTables_filter label {
                display: flex;
                flex-direction: column;
                gap: 0.25rem;
            }
            
            .dataTables_wrapper .dataTables_length select,
            .dataTables_wrapper .dataTables_filter input {
                width: 100% !important;
                margin: 0 !important;
            }
            
            .dataTables_wrapper .dataTables_paginate {
                text-align: center !important;
                margin-top: 0.5rem;
            }
            
            .dataTables_wrapper .dataTables_paginate .paginate_button {
                padding: 0.25rem 0.5rem !important;
                margin: 0.1rem !important;
            }
            
            .dataTables_wrapper .dataTables_info {
                text-align: center !important;
                font-size: 0.75rem;
            }
        }
        
        /* ===== EXTRA SMALL PHONE (max 400px) ===== */
        @media (max-width: 400px) {
            .navbar-brand {
                font-size: 0.9rem !important;
            }
            
            .navbar-brand i {
                font-size: 1rem !important;
            }
            
            .card-header {
                font-size: 0.85rem;
            }
            
            .stat-card .icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            
            .stat-card .info h3 {
                font-size: 1.1rem;
            }
            
            .table {
                font-size: 0.75rem;
            }
            
            /* Hide more columns on very small screens */
            .table th:nth-child(n+4):not(:last-child),
            .table td:nth-child(n+4):not(:last-child) {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
        <div class="container-fluid px-0">
            <a class="navbar-brand" href="<?php echo $bp; ?>index.php">
                <i class="fas fa-feather-alt"></i> <?php echo SITE_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto">
                    <!-- سەرەکی -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage ?? '') === 'home' ? 'active' : ''; ?>" href="<?php echo $bp; ?>index.php">
                            <i class="fas fa-home"></i> سەرەکی
                        </a>
                    </li>
                    
                    <!-- مەخزەن -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo ($currentPage ?? '') === 'warehouse' ? 'active' : ''; ?>" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-warehouse"></i> مەخزەن
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo $bp; ?>pages/warehouse/list.php"><i class="fas fa-list"></i> لیست</a></li>
                            <li><a class="dropdown-item" href="<?php echo $bp; ?>pages/warehouse/add.php"><i class="fas fa-plus"></i> زیادکردن</a></li>
                        </ul>
                    </li>
                    
                    <!-- هەوێردە -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo ($currentPage ?? '') === 'birds' ? 'active' : ''; ?>" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-dove"></i> هەوێردە
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo $bp; ?>pages/birds/male_list.php"><i class="fas fa-mars"></i> نێرەکان</a></li>
                            <li><a class="dropdown-item" href="<?php echo $bp; ?>pages/birds/female_list.php"><i class="fas fa-venus"></i> مێیەکان</a></li>
                        </ul>
                    </li>
                    
                    <!-- هێلکە و جوجکە -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo ($currentPage ?? '') === 'production' ? 'active' : ''; ?>" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-egg"></i> بەرهەم
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo $bp; ?>pages/eggs/list.php"><i class="fas fa-egg"></i> هێلکەکان</a></li>
                            <li><a class="dropdown-item" href="<?php echo $bp; ?>pages/chicks/list.php"><i class="fas fa-kiwi-bird"></i> جوجکەکان</a></li>
                        </ul>
                    </li>
                    
                    <!-- فرۆشتن -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo ($currentPage ?? '') === 'sales' ? 'active' : ''; ?>" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-shopping-cart"></i> فرۆشتن
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo $bp; ?>pages/sales/list.php"><i class="fas fa-list"></i> لیست</a></li>
                            <li><a class="dropdown-item" href="<?php echo $bp; ?>pages/sales/add.php"><i class="fas fa-plus"></i> فرۆشتنی نوێ</a></li>
                        </ul>
                    </li>
                    
                    <!-- کڕین -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo ($currentPage ?? '') === 'purchases' ? 'active' : ''; ?>" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-truck"></i> کڕین
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo $bp; ?>pages/purchases/list.php"><i class="fas fa-list"></i> لیست</a></li>
                            <li><a class="dropdown-item" href="<?php echo $bp; ?>pages/purchases/add.php"><i class="fas fa-plus"></i> کڕینی نوێ</a></li>
                        </ul>
                    </li>
                    
                    <!-- کڕیاران -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $bp; ?>pages/customers/list.php">
                            <i class="fas fa-user-tie"></i> کڕیاران
                        </a>
                    </li>
                    
                    <!-- راپۆرت -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo ($currentPage ?? '') === 'reports' ? 'active' : ''; ?>" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-bar"></i> راپۆرت
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo $bp; ?>pages/reports/monthly.php"><i class="fas fa-calendar-alt"></i> مانگانە</a></li>
                            <li><a class="dropdown-item" href="<?php echo $bp; ?>pages/reports/sales.php"><i class="fas fa-chart-line"></i> فرۆشتن</a></li>
                            <li><a class="dropdown-item" href="<?php echo $bp; ?>pages/transactions/list.php"><i class="fas fa-history"></i> مێژوو</a></li>
                        </ul>
                    </li>
                </ul>
                
                <!-- دەرچوون -->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link user-menu" href="<?php echo $bp; ?>logout.php" onclick="return confirm('دەرچوون؟');">
                            <i class="fas fa-sign-out-alt"></i> دەرچوون
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="main-content">
        <?php
        $message = getMessage();
        if ($message): ?>
        <div class="alert alert-<?php echo $message['type'] === 'success' ? 'success' : ($message['type'] === 'error' ? 'danger' : 'warning'); ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $message['type'] === 'success' ? 'check-circle' : ($message['type'] === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <?php echo $message['text']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
