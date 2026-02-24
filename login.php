<?php
session_start();
require_once 'includes/config.php';

// Check if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

// Admin credentials (you can change these)
define('ADMIN_EMAIL', 'basir@basir.com');
define('ADMIN_PASSWORD', '123456');
 
// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'تکایە هەموو خانەکان پڕ بکەوە';
    } elseif ($email === ADMIN_EMAIL && $password === ADMIN_PASSWORD) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_email'] = $email;
        $_SESSION['login_time'] = time();
        header('Location: index.php');
        exit;
    } else {
        $error = 'ئیمەیڵ یان وشەی نهێنی هەڵەیە';
    }
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>چوونەژوورەوە - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Noto Sans Arabic', sans-serif;
        }
         
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            padding: 20px;
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .login-container {
            width: 100%;
            max-width: 440px;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 80px rgba(0,0,0,0.35);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
         
        .login-header {
            background: linear-gradient(135deg, #059669 0%, #34d399 100%);
            padding: 50px 30px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
        }
        
        .login-header .icon {
            width: 90px;
            height: 90px;
            background: rgba(255,255,255,0.25);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.8rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: pulse 2s ease-in-out infinite;
            position: relative;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .login-header h1 {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 800;
            position: relative;
        }
        
        .login-header p {
            margin: 12px 0 0;
            opacity: 0.95;
            font-size: 1rem;
            position: relative;
        }
        
        .login-body {
            padding: 45px 35px;
        }
        
        .form-floating {
            margin-bottom: 22px;
        }
        
        .form-floating > .form-control {
            border-radius: 14px;
            border: 2px solid #e2e8f0;
            padding: 1rem 1rem 1rem 2.5rem;
            height: 62px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }
        
        .form-floating > .form-control:focus {
            border-color: #059669;
            box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.15);
            background: #fff;
        }
        
        .form-floating > label {
            padding: 1rem;
            color: #64748b;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            z-index: 5;
            font-size: 1.1rem;
        }
        
        .form-group {
            position: relative;
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            font-size: 1.15rem;
            font-weight: 700;
            border-radius: 14px;
            background: linear-gradient(135deg, #059669 0%, #34d399 100%);
            border: none;
            color: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 25px rgba(5, 150, 105, 0.35);
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: 0.5s;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(5, 150, 105, 0.5);
            background: linear-gradient(135deg, #34d399 0%, #059669 100%);
            color: white;
        }
        
        .alert {
            border-radius: 14px;
            border: none;
            padding: 16px 20px;
            font-weight: 500;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.15), rgba(248, 113, 113, 0.15));
            border-right: 4px solid #dc2626;
            color: #991b1b;
        }
        
        .login-footer {
            text-align: center;
            padding: 22px 30px 28px;
            color: #64748b;
            font-size: 0.9rem;
            border-top: 1px solid #e2e8f0;
            background: rgba(248, 250, 252, 0.5);
        }
        
        .password-toggle {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            z-index: 5;
            transition: all 0.3s ease;
        }
        
        .password-toggle:hover {
            color: #059669;
        }
        
        /* Responsive Styles */
        @media (max-width: 576px) {
            body {
                padding: 15px;
            }
            
            .login-container {
                max-width: 100%;
            }
            
            .login-card {
                border-radius: 15px;
            }
            
            .login-header {
                padding: 30px 20px;
            }
            
            .login-header .icon {
                width: 60px;
                height: 60px;
                font-size: 1.8rem;
            }
            
            .login-header h1 {
                font-size: 1.3rem;
            }
            
            .login-header p {
                font-size: 0.85rem;
            }
            
            .login-body {
                padding: 25px 20px;
            }
            
            .form-floating > .form-control {
                height: 55px;
                font-size: 0.95rem;
            }
            
            .btn-login {
                padding: 12px;
                font-size: 1rem;
            }
            
            .login-footer {
                padding: 15px 20px 20px;
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 400px) {
            .login-header {
                padding: 25px 15px;
            }
            
            .login-header .icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
            
            .login-header h1 {
                font-size: 1.1rem;
            }
            
            .login-body {
                padding: 20px 15px;
            }
            
            .form-floating > .form-control {
                height: 50px;
                font-size: 0.9rem;
            }
            
            .btn-login {
                padding: 10px;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="icon">
                    <i class="fas fa-feather-alt"></i>
                </div>
                <h1><?php echo SITE_NAME; ?></h1>
                <p>سیستەمی بەڕێوەبردنی مریشک</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" autocomplete="off">
                    <div class="form-group mb-3">
                        <div class="form-floating">
                            <input type="email" name="email" id="email" class="form-control" placeholder="ئیمەیڵ" required autofocus>
                            <label for="email"><i class="fas fa-envelope me-2"></i> ئیمەیڵ</label>
                        </div>
                    </div>
                    
                    <div class="form-group mb-4">
                        <div class="form-floating position-relative">
                            <input type="password" name="password" id="password" class="form-control" placeholder="وشەی نهێنی" required>
                            <label for="password"><i class="fas fa-lock me-2"></i> وشەی نهێنی</label>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i> چوونەژوورەوە
                    </button>
                </form>
            </div>
            
            <div class="login-footer">
                <i class="fas fa-shield-alt me-1"></i> پارێزراوە بە سیستەمی ئەمنیەت
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
