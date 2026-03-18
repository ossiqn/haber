<?php
require_once __DIR__ . '/config.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$msg = '';
$msgType = '';

if (isset($_GET['logout'])) {
    session_destroy();
    $msg = 'Başarıyla çıkış yaptınız!';
    $msgType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $st = $db->prepare("SELECT * FROM users WHERE username = ?");
    $st->execute([$username]);
    $user = $st->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $user;
        header('Location: dashboard.php');
        exit;
    }
    
    $msg = 'Kullanıcı adı veya şifre hatalı!';
    $msgType = 'error';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - HaberPortal Yönetim</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --red: #dc2626;
            --red-dark: #b91c1c;
            --dark: #0a0a0f;
            --dark-2: #1a1a2e;
            --dark-3: #2d1b3d;
            --gray-900: #18181b;
            --gray-800: #27272a;
            --gray-700: #3f3f46;
            --gray-600: #52525b;
            --gray-500: #71717a;
            --gray-400: #a1a1aa;
            --gray-300: #d4d4d8;
            --gray-200: #e4e4e7;
            --gray-100: #f4f4f5;
            --white: #ffffff;
            --font: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --serif: 'Merriweather', Georgia, serif;
            --radius: 10px;
            --shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        body {
            font-family: var(--font);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--dark) 0%, var(--dark-2) 50%, var(--dark-3) 100%);
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: var(--white);
            padding: 44px 40px;
            border-radius: 24px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 32px;
        }

        .logo-mark {
            width: 48px;
            height: 48px;
            background: var(--red);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-family: var(--serif);
            font-size: 24px;
            font-weight: 900;
            box-shadow: 0 8px 16px rgba(220, 38, 38, 0.25);
        }

        .logo-text {
            font-family: var(--serif);
            font-size: 24px;
            font-weight: 900;
            color: var(--gray-900);
        }

        .logo-text em {
            color: var(--red);
            font-style: normal;
        }

        .login-title {
            text-align: center;
            font-size: 14px;
            color: var(--gray-500);
            margin-bottom: 28px;
        }

        .toast {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .toast-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .toast-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--gray-700);
        }

        .form-group label i {
            margin-right: 4px;
            color: var(--red);
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            font-size: 14px;
            font-family: var(--font);
            background: var(--gray-50);
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--red);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: var(--red);
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            font-family: var(--font);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            margin-top: 10px;
        }

        .btn-login:hover {
            background: var(--red-dark);
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(220, 38, 38, 0.25);
        }

        .login-footer {
            text-align: center;
            margin-top: 24px;
        }

        .login-footer a {
            color: var(--gray-500);
            font-size: 13px;
            text-decoration: none;
            transition: color 0.2s;
        }

        .login-footer a:hover {
            color: var(--red);
        }

        .login-footer a i {
            margin-right: 4px;
        }

        .demo-info {
            margin-top: 16px;
            padding: 12px;
            background: var(--gray-100);
            border-radius: 8px;
            font-size: 12px;
            color: var(--gray-600);
            text-align: center;
        }

        .demo-info strong {
            color: var(--red);
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 32px 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <div class="logo-mark">H</div>
                <div class="logo-text">Haber<em>Portal</em></div>
            </div>
            
            <div class="login-title">Yönetim Paneli'ne Giriş Yapın</div>

            <?php if ($msg): ?>
                <div class="toast toast-<?php echo $msgType; ?>">
                    <i class="fas fa-<?php echo $msgType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo e($msg); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Kullanıcı Adı</label>
                    <input type="text" name="username" class="form-control" placeholder="Kullanıcı adınız" required autofocus>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Şifre</label>
                    <input type="password" name="password" class="form-control" placeholder="Şifreniz" required>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Giriş Yap
                </button>
            </form>

            <div class="login-footer">
                <a href="http://localhost/index.php">
                    <i class="fas fa-arrow-left"></i>
                    Siteye Dön
                </a>
            </div>

            <div class="demo-info">
                <i class="fas fa-info-circle"></i>
                Demo: <strong>admin</strong> / <strong>admin123</strong>
            </div>
        </div>
    </div>
</body>
</html>