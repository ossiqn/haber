<?php
require_once __DIR__ . '/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$page_title = 'Ayarlar';
$current_page = 'ayarlar';

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $newpass = $_POST['new_password'] ?? '';
    $curpass = $_POST['current_password'] ?? '';

    if (!empty($fullname)) {
        $st = $db->prepare("UPDATE users SET fullname = ? WHERE id = ?");
        $st->execute([$fullname, $_SESSION['admin_user']['id']]);
        $_SESSION['admin_user']['fullname'] = $fullname;
        $msg = 'Bilgiler güncellendi!';
        $msgType = 'success';
    }

    if (!empty($newpass) && !empty($curpass)) {
        $st = $db->prepare("SELECT password FROM users WHERE id = ?");
        $st->execute([$_SESSION['admin_user']['id']]);
        $row = $st->fetch();
        
        if ($row && password_verify($curpass, $row['password'])) {
            $hash = password_hash($newpass, PASSWORD_DEFAULT);
            $st2 = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $st2->execute([$hash, $_SESSION['admin_user']['id']]);
            $msg = 'Şifre ve bilgiler güncellendi!';
            $msgType = 'success';
        } else {
            $msg = 'Mevcut şifre yanlış!';
            $msgType = 'error';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1><i class="fas fa-cog"></i> Ayarlar</h1>
    </div>

    <?php if ($msg): ?>
        <div class="toast toast-<?php echo $msgType; ?>">
            <i class="fas fa-<?php echo $msgType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo e($msg); ?>
        </div>
    <?php endif; ?>

    <div class="two-column-layout">
        <div class="column">
            <div class="form-card">
                <h3><i class="fas fa-user-circle"></i> Profil Bilgileri</h3>
                
                <form method="post" action="ayarlar.php" class="settings-form">
                    <div class="form-group">
                        <label>Ad Soyad</label>
                        <input type="text" name="fullname" value="<?php echo e($_SESSION['admin_user']['fullname'] ?? ''); ?>" placeholder="Adınız Soyadınız" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Kullanıcı Adı</label>
                        <input type="text" value="<?php echo e($_SESSION['admin_user']['username'] ?? ''); ?>" disabled class="form-control" readonly>
                        <small>Kullanıcı adı değiştirilemez</small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Bilgileri Güncelle
                    </button>
                </form>
            </div>
        </div>

        <div class="column">
            <div class="form-card">
                <h3><i class="fas fa-lock"></i> Şifre Değiştir</h3>
                
                <form method="post" action="ayarlar.php" class="settings-form">
                    <div class="form-group">
                        <label>Mevcut Şifre</label>
                        <input type="password" name="current_password" placeholder="Mevcut şifreniz" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Yeni Şifre</label>
                        <input type="password" name="new_password" placeholder="Yeni şifreniz" class="form-control">
                        <small>En az 6 karakter olmalı</small>
                    </div>

                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key"></i> Şifreyi Değiştir
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="form-card" style="margin-top: 20px;">
        <h3><i class="fas fa-info-circle"></i> Sistem Bilgisi</h3>
        
        <div class="system-info">
            <div class="info-item">
                <span class="info-label">PHP Sürümü:</span>
                <span class="info-value"><?php echo phpversion(); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Veritabanı:</span>
                <span class="info-value">MySQL</span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Uploads Klasörü:</span>
                <span class="info-value">
                    <?php
                    $files = glob(__DIR__ . '/uploads/*');
                    $size = 0;
                    if ($files) {
                        foreach ($files as $f) $size += filesize($f);
                    }
                    echo count($files ?: []) . ' dosya / ' . round($size / 1024, 1) . ' KB';
                    ?>
                </span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Sunucu Zamanı:</span>
                <span class="info-value"><?php echo date('d.m.Y H:i:s'); ?></span>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>