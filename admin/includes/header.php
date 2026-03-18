<?php
if (!isset($page_title)) $page_title = 'Yönetim Paneli';
if (!isset($current_page)) $current_page = '';
$newsCount = $db->query("SELECT COUNT(*) FROM news")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $page_title; ?> - HaberPortal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <?php if(in_array($current_page, ['haberler', 'haber-ekle', 'haber-duzenle'])): ?>
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <?php endif; ?>

    <style>
        :root {
            --primary: #4361ee;
            --red: #dc2626;
            --dark-bg: #111116;
            --dark-text: #1e293b;
            --light: #f8fafc;
            --border: #e2e8f0;
            --text: #334155;
            --text-light: #64748b;
            --g400: #94a3b8;
            --g600: #475569;
            --sidebar-w: 260px;
            --ease: cubic-bezier(.4,0,.2,1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--light); color: var(--text); font-size: 14px; -webkit-font-smoothing: antialiased; }
        a { text-decoration: none; color: inherit; }
        .layout { display: flex; min-height: 100vh; }

        .sidebar { width: var(--sidebar-w); background: var(--dark-bg); position: fixed; top: 0; left: 0; bottom: 0; display: flex; flex-direction: column; overflow-y: auto; z-index: 1000; transition: transform .3s var(--ease); }
        .sidebar-logo { padding: 24px; display: flex; align-items: center; gap: 12px; }
        .sidebar-logo .mark { width: 40px; height: 40px; background: var(--red); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 20px; font-weight: 900; font-family: Georgia, serif; }
        .sidebar-logo span { font-size: 20px; font-weight: 900; color: #fff; letter-spacing: -0.5px;}
        .sidebar-logo span em { color: var(--red); font-style: normal; }
        
        .side-group { padding: 10px 0; display: flex; flex-direction: column; }
        .side-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; padding: 16px 24px 8px; color: var(--g600); font-weight: 700; }
        
        .side-link { display: flex; align-items: center; gap: 14px; padding: 12px 24px; font-size: 15px; font-weight: 500; color: var(--g400); border-left: 3px solid transparent; transition: all .2s var(--ease); }
        .side-link:hover { background: rgba(255,255,255,.03); color: #fff; }

        .side-link.on { background: linear-gradient(90deg, rgba(220,38,38,0.1) 0%, transparent 100%); color: var(--red); border-left-color: var(--red); font-weight: 600; }
        .side-link i { width: 20px; text-align: center; font-size: 16px; }
        .side-link.on i { color: var(--red); }
        
        .badge-count { margin-left: auto; background: var(--red); color: #fff; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 50px; }

        .main { flex: 1; margin-left: var(--sidebar-w); display: flex; flex-direction: column; min-width: 0; }
        .topbar { background: #fff; padding: 15px 30px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 90; }
        .menu-toggle { display: none; background: none; border: none; font-size: 20px; color: var(--dark-text); cursor: pointer; }
        .user-menu { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 38px; height: 38px; border-radius: 10px; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; }
        .user-info { display: flex; flex-direction: column; text-align: right; }
        .user-name { font-weight: 600; color: var(--dark-text); font-size: 13px; }
        .user-role { font-size: 11px; color: var(--text-light); }
        
        .content { padding: 30px; flex: 1; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;}
        .page-title { font-size: 24px; font-weight: 700; color: var(--dark-text); }
        
        .sidebar-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 90; display: none; }
        @media (max-width: 992px) { .sidebar { transform: translateX(-100%); } .sidebar.show { transform: translateX(0); } .sidebar-overlay.show { display: block; } .main { margin-left: 0; } .menu-toggle { display: block; } }
        @media (max-width: 768px) { .content { padding: 20px; } }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="mark">H</div>
            <span>Haber<em>Portal</em></span>
        </div>

        <nav class="side-group">
            <div class="side-label">GENEL</div>
            <a href="dashboard.php" class="side-link <?php echo $current_page === 'dashboard' ? 'on' : ''; ?>">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <a href="haberler.php" class="side-link <?php echo $current_page === 'haberler' ? 'on' : ''; ?>">
                <i class="fas fa-newspaper"></i> Haberler
                <span class="badge-count"><?php echo $newsCount; ?></span>
            </a>
            <a href="haberekle.php" class="side-link <?php echo $current_page === 'haber-ekle' ? 'on' : ''; ?>">
                <i class="fas fa-plus-circle"></i> Yeni Haber
            </a>

            <div class="side-label">İÇERİK</div>
            <a href="kategoriler.php" class="side-link <?php echo $current_page === 'kategoriler' ? 'on' : ''; ?>">
                <i class="fas fa-folder-open"></i> Kategoriler
            </a>

            <div class="side-label">SİSTEM</div>
            <a href="ayarlar.php" class="side-link <?php echo $current_page === 'ayarlar' ? 'on' : ''; ?>">
                <i class="fas fa-cog"></i> Ayarlar
            </a>
            <a href="../index.php" target="_blank" class="side-link">
                <i class="fas fa-external-link-alt"></i> Siteyi Gör
            </a>
            <a href="login.php?logout=1" class="side-link" onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?')">
                <i class="fas fa-sign-out-alt"></i> Çıkış Yap
            </a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <div style="flex:1"></div>
            <div class="user-menu">
                <div class="user-info">
                    <span class="user-name"><?php echo e($_SESSION['admin_user']['fullname'] ?? 'Yönetici'); ?></span>
                    <span class="user-role">Admin</span>
                </div>
                <div class="avatar"><?php echo mb_substr(($_SESSION['admin_user']['fullname'] ?? 'A'), 0, 1, 'UTF-8'); ?></div>
            </div>
        </header>

        <div class="content">