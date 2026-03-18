<?php
require_once __DIR__ . '/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$page_title = 'Dashboard';
$current_page = 'dashboard';

$total_news = $db->query("SELECT COUNT(*) as c FROM news")->fetch()['c'];
$total_views = $db->query("SELECT COALESCE(SUM(views),0) as c FROM news")->fetch()['c'];
$total_cats = $db->query("SELECT COUNT(*) as c FROM categories")->fetch()['c'];
$published_news = $db->query("SELECT COUNT(*) as c FROM news WHERE status='published'")->fetch()['c'];
$draft_news = $db->query("SELECT COUNT(*) as c FROM news WHERE status='draft'")->fetch()['c'];

$recent_news = getNews(['limit' => 5]);
$popular_news = getNews(['limit' => 5, 'orderBy' => 'n.views DESC']);
$categories = getCategories();

include __DIR__ . '/includes/header.php';
?>

<style>
    /* Sadece Dashboarda Özel Stiller */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background: #fff; border-radius: 16px; padding: 24px; border: 1px solid var(--border); position: relative; overflow: hidden; transition: transform 0.2s; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
    .stat-card::after { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; }
    .stat-card:nth-child(1)::after { background: var(--red); }
    .stat-card:nth-child(2)::after { background: #10b981; }
    .stat-card:nth-child(3)::after { background: #3b82f6; }
    .stat-card:nth-child(4)::after { background: #f59e0b; }
    .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 16px; }
    .stat-card:nth-child(1) .stat-icon { background: rgba(220, 38, 38, 0.1); color: var(--red); }
    .stat-card:nth-child(2) .stat-icon { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .stat-card:nth-child(3) .stat-icon { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    .stat-card:nth-child(4) .stat-icon { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .stat-value { font-size: 32px; font-weight: 800; color: var(--dark-text); line-height: 1.2; }
    .stat-label { font-size: 13px; color: var(--text-light); margin-top: 4px; font-weight: 500; }
    
    .dash-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    .card { background: #fff; border-radius: 16px; border: 1px solid var(--border); overflow: hidden; }
    .card-header { padding: 18px 24px; border-bottom: 1px solid var(--border); font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 10px; color: var(--dark-text); }
    .card-header i { color: var(--red); }
    .card-body { padding: 0 20px; }
    
    .news-item { display: flex; align-items: center; gap: 15px; padding: 16px 0; border-bottom: 1px solid var(--border); }
    .news-item:last-child { border-bottom: none; }
    .news-thumb { width: 60px; height: 45px; border-radius: 8px; overflow: hidden; flex-shrink: 0; border: 1px solid var(--border); }
    .news-thumb img, .news-thumb .placeholder-img { width: 100%; height: 100%; object-fit: cover; }
    .news-info flex { 1; min-width: 0; }
    .news-info h4 { font-size: 14px; font-weight: 600; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .news-info h4 a:hover { color: var(--red); }
    .news-meta { font-size: 12px; color: var(--text-light); display: flex; align-items: center; gap: 10px; }
    
    .cat-progress { margin: 16px 0; }
    .cat-p-head { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px; font-weight: 600; color: var(--dark-text); }
    .bar-track { height: 6px; background: var(--border); border-radius: 3px; overflow: hidden; }
    .bar-fill { height: 100%; border-radius: 3px; }
    @media (max-width: 1024px) { .dash-grid { grid-template-columns: 1fr; } }
</style>

<div class="page-header">
    <h1 class="page-title">Sistem Özeti</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-newspaper"></i></div>
        <div class="stat-value"><?php echo $total_news; ?></div>
        <div class="stat-label">Toplam Haber</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-eye"></i></div>
        <div class="stat-value"><?php echo formatViews($total_views); ?></div>
        <div class="stat-label">Toplam Görüntülenme</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-folder"></i></div>
        <div class="stat-value"><?php echo $total_cats; ?></div>
        <div class="stat-label">Kategoriler</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-value"><?php echo $published_news; ?> <span style="font-size: 16px; color: var(--g400);">/ <?php echo $draft_news; ?></span></div>
        <div class="stat-label">Yayında / Taslak</div>
    </div>
</div>

<div class="dash-grid">
    <div class="card">
        <div class="card-header"><i class="fas fa-clock"></i> Son Eklenen Haberler</div>
        <div class="card-body">
            <?php if (empty($recent_news)): ?>
                <div style="padding: 30px; text-align: center; color: var(--text-light);">Henüz haber eklenmemiş</div>
            <?php else: foreach ($recent_news as $news): ?>
                <div class="news-item">
                    <div class="news-thumb"><?php echo newsImage($news); ?></div>
                    <div class="news-info" style="flex:1; overflow:hidden;">
                        <h4><a href="haberduzenle.php?id=<?php echo $news['id']; ?>"><?php echo e($news['title']); ?></a></h4>
                        <div class="news-meta">
                            <span><i class="far fa-calendar"></i> <?php echo timeAgo($news['published_at']); ?></span>
                            <?php if ($news['status'] === 'draft'): ?>
                                <span style="color: #f59e0b; font-weight: 600;">Taslak</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <div>
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header"><i class="fas fa-fire"></i> En Çok Okunanlar</div>
            <div class="card-body">
                <?php if (empty($popular_news)): ?>
                    <div style="padding: 30px; text-align: center; color: var(--text-light);">Yeterli veri yok</div>
                <?php else: foreach ($popular_news as $news): ?>
                    <div class="news-item">
                        <div class="news-thumb"><?php echo newsImage($news); ?></div>
                        <div class="news-info" style="flex:1; overflow:hidden;">
                            <h4><a href="haberduzenle.php?id=<?php echo $news['id']; ?>"><?php echo e($news['title']); ?></a></h4>
                            <div class="news-meta" style="color: var(--primary); font-weight: 600;">
                                <i class="fas fa-chart-line"></i> <?php echo formatViews($news['views']); ?> görüntülenme
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="fas fa-chart-bar"></i> Kategori Dağılımı</div>
            <div class="card-body" style="padding: 10px 20px 20px;">
                <?php foreach ($categories as $cat):
                    $st = $db->prepare("SELECT COUNT(*) as c FROM news WHERE category_id = ?");
                    $st->execute([$cat['id']]);
                    $count = $st->fetch()['c'];
                    $percentage = $total_news > 0 ? round(($count / $total_news) * 100) : 0;
                ?>
                    <div class="cat-progress">
                        <div class="cat-p-head">
                            <span><i class="fas <?php echo $cat['icon']; ?>" style="color: <?php echo $cat['color']; ?>; width: 20px;"></i> <?php echo e($cat['name']); ?></span>
                            <span style="color: var(--text-light);"><?php echo $count; ?> (%<?php echo $percentage; ?>)</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?php echo $percentage; ?>%; background: <?php echo $cat['color']; ?>"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>