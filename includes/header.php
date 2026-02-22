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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        }
        
        .navbar-toggler:focus {
            box-shadow: none;
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
                    
                    <!-- کەسەکان -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-users"></i> کەسەکان
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo $bp; ?>pages/customers/list.php"><i class="fas fa-user-tie"></i> کڕیاران</a></li>
                            <li><a class="dropdown-item" href="<?php echo $bp; ?>pages/suppliers/list.php"><i class="fas fa-truck-loading"></i> دابینکەران</a></li>
                        </ul>
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
