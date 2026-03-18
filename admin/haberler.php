<?php
require_once __DIR__ . '/config.php';
if (!isLoggedIn()) { header('Location: login.php'); exit; }

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($action === 'delete' && $id > 0) {
    $st = $db->prepare("SELECT image FROM news WHERE id = ?");
    $st->execute([$id]);
    $img = $st->fetchColumn();
    if ($img && file_exists(__DIR__ . '/../' . $img)) {
        @unlink(__DIR__ . '/../' . $img);
    }
    if ($img && file_exists(__DIR__ . '/' . $img)) {
        @unlink(__DIR__ . '/' . $img);
    }
    $db->prepare("DELETE FROM news WHERE id = ?")->execute([$id]);
    header('Location: haberler.php?swal=deleted');
    exit;
}

if ($action === 'toggle' && $id > 0) {
    $st = $db->prepare("SELECT status FROM news WHERE id = ?");
    $st->execute([$id]);
    $curr = $st->fetchColumn();
    $newStat = ($curr === 'published') ? 'draft' : 'published';
    $db->prepare("UPDATE news SET status = ? WHERE id = ?")->execute([$newStat, $id]);
    header('Location: haberler.php?swal=status_changed');
    exit;
}

$p = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = 15;
$offset = ($p - 1) * $limit;

$cat = isset($_GET['cat']) ? intval($_GET['cat']) : 0;
$status = $_GET['status'] ?? '';
$q = trim($_GET['q'] ?? '');

$where = ["1=1"];
$params = [];

if ($cat > 0) { $where[] = "n.category_id = ?"; $params[] = $cat; }
if (in_array($status, ['published', 'draft'])) { $where[] = "n.status = ?"; $params[] = $status; }
if ($q !== '') { $where[] = "(n.title LIKE ? OR n.summary LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }

$whereSql = implode(' AND ', $where);

$totalSt = $db->prepare("SELECT COUNT(*) as c FROM news n WHERE $whereSql");
$totalSt->execute($params);
$totalCount = $totalSt->fetch()['c'];
$totalPages = ceil($totalCount / $limit);

$sql = "SELECT n.*, c.name as cat_name, c.color as cat_color FROM news n LEFT JOIN categories c ON n.category_id = c.id WHERE $whereSql ORDER BY n.id DESC LIMIT $limit OFFSET $offset";
$st = $db->prepare($sql);
$st->execute($params);
$newsList = $st->fetchAll();

$categories = getCategories();

$page_title = 'Haberler';
$current_page = 'haberler';
include __DIR__ . '/includes/header.php';
?>

<style>
.filter-card { background: #fff; border-radius: 12px; border: 1px solid var(--border); padding: 20px; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
.filter-form { display: grid; grid-template-columns: 1fr auto auto auto; gap: 15px; align-items: center; }
.form-control { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: var(--dark-text); outline: none; }
.form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1); }
.btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; border: none; cursor: pointer; transition: 0.2s; text-decoration: none; }
.btn-primary { background: var(--primary); color: #fff; }
.btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
.btn-dark { background: var(--dark-text); color: #fff; }
.table-card { background: #fff; border-radius: 12px; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
.table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; min-width: 800px; }
.data-table th { background: #f8fafc; padding: 16px 20px; text-align: left; font-size: 12px; font-weight: 700; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); }
.data-table td { padding: 16px 20px; border-bottom: 1px solid var(--border); vertical-align: middle; font-size: 14px; }
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover { background: #f8fafc; }
.news-cell { display: flex; align-items: center; gap: 15px; }
.news-thumb { width: 60px; height: 45px; border-radius: 6px; overflow: hidden; flex-shrink: 0; border: 1px solid var(--border); }
.news-thumb img, .news-thumb .placeholder-img { width: 100%; height: 100%; object-fit: cover; }
.news-title { font-weight: 600; color: var(--dark-text); margin-bottom: 4px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.news-title a { color: inherit; }
.news-title a:hover { color: var(--primary); }
.cat-badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; color: #fff; }
.status-badge { padding: 5px 12px; border-radius: 50px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
.status-pub { background: #dcfce7; color: #166534; }
.status-draft { background: #fef9c3; color: #854d0e; }
.action-btns { display: flex; gap: 6px; justify-content: flex-end; }
.btn-icon { width: 34px; height: 34px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; border: none; cursor: pointer; transition: 0.2s; color: #fff; }
.btn-toggle { background: #f59e0b; }
.btn-edit { background: var(--primary); }
.btn-delete { background: var(--danger); }
.pagination { display: flex; gap: 8px; justify-content: center; margin-top: 30px; }
.page-link { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-weight: 600; font-size: 14px; background: #fff; border: 1px solid var(--border); color: var(--dark-text); transition: 0.2s; }
.page-link:hover { border-color: var(--primary); color: var(--primary); }
.page-link.active { background: var(--primary); color: #fff; border-color: var(--primary); }
@media (max-width: 992px) { .filter-form { grid-template-columns: 1fr; } }
</style>

<div class="page-header">
    <h1 class="page-title">Haberler</h1>
    <a href="haberekle.php" class="btn btn-primary"><i class="fas fa-plus"></i> Yeni Haber</a>
</div>

<div class="filter-card">
    <form method="GET" class="filter-form">
        <input type="text" name="q" class="form-control" placeholder="Haber başlığı veya özetinde ara..." value="<?php echo e($q); ?>">
        <select name="cat" class="form-control">
            <option value="">Tüm Kategoriler</option>
            <?php foreach($categories as $c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo $cat == $c['id'] ? 'selected' : ''; ?>><?php echo e($c['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" class="form-control">
            <option value="">Tüm Durumlar</option>
            <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Yayında Olanlar</option>
            <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Taslak Olanlar</option>
        </select>
        <div style="display:flex; gap:10px;">
            <button type="submit" class="btn btn-dark"><i class="fas fa-search"></i> Ara</button>
            <?php if($cat || $status || $q): ?>
                <a href="haberler.php" class="btn" style="background:#e2e8f0; color:#334155;"><i class="fas fa-times"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="table-card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Haber İçeriği</th>
                    <th>İstatistik / Tarih</th>
                    <th>Durum</th>
                    <th style="text-align: right;">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($newsList)): ?>
                <tr>
                    <td colspan="4" style="text-align:center; padding:40px; color:var(--text-light);">
                        <i class="fas fa-inbox" style="font-size:40px; margin-bottom:15px; opacity:0.5; display:block;"></i>
                        Kriterlere uygun kayıt bulunamadı.
                    </td>
                </tr>
                <?php else: foreach($newsList as $n): ?>
                <tr>
                    <td>
                        <div class="news-cell">
                            <div class="news-thumb"><?php echo newsImage($n); ?></div>
                            <div>
                                <div class="news-title"><a href="haberduzenle.php?id=<?php echo $n['id']; ?>"><?php echo e($n['title']); ?></a></div>
                                <?php if($n['cat_name']): ?>
                                    <span class="cat-badge" style="background: <?php echo $n['cat_color']; ?>"><?php echo e($n['cat_name']); ?></span>
                                <?php endif; ?>
                                <?php if($n['is_breaking']) echo '<span class="cat-badge" style="background:var(--red); margin-left:5px;">Son Dakika</span>'; ?>
                                <?php if($n['is_headline']) echo '<span class="cat-badge" style="background:#3b82f6; margin-left:5px;">Manşet</span>'; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="color:var(--text-light); font-size:12px; margin-bottom:4px;"><i class="far fa-eye"></i> <?php echo formatViews($n['views']); ?> Görüntülenme</div>
                        <div style="color:var(--text-light); font-size:12px;"><i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y H:i', strtotime($n['created_at'])); ?></div>
                    </td>
                    <td>
                        <?php if($n['status'] === 'published'): ?>
                            <span class="status-badge status-pub"><i class="fas fa-check-circle"></i> Yayında</span>
                        <?php else: ?>
                            <span class="status-badge status-draft"><i class="fas fa-file-alt"></i> Taslak</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="../index.php?page=detail&id=<?php echo $n['id']; ?>" target="_blank" class="btn-icon" style="background:#64748b;" title="Görüntüle"><i class="fas fa-external-link-alt"></i></a>
                            <button onclick="toggleStatus(<?php echo $n['id']; ?>)" class="btn-icon btn-toggle" title="Durumu Değiştir"><i class="fas fa-exchange-alt"></i></button>
                            <a href="haberduzenle.php?id=<?php echo $n['id']; ?>" class="btn-icon btn-edit" title="Düzenle"><i class="fas fa-pen"></i></a>
                            <button onclick="confirmDelete(<?php echo $n['id']; ?>)" class="btn-icon btn-delete" title="Sil"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if($totalPages > 1): ?>
<div class="pagination">
    <?php for($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?p=<?php echo $i; ?><?php echo $cat ? "&cat=$cat" : ''; ?><?php echo $status ? "&status=$status" : ''; ?><?php echo $q ? "&q=$q" : ''; ?>" class="page-link <?php echo $p == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<script>
function toggleStatus(id) {
    Swal.fire({ title: 'İşleniyor...', allowOutsideClick: false, showConfirmButton: false, didOpen: () => { Swal.showLoading(); } });
    window.location.href = 'haberler.php?action=toggle&id=' + id;
}

function confirmDelete(id) {
    Swal.fire({
        title: 'Emin misiniz?',
        text: 'Bu haberi tamamen silmek istediğinize emin misiniz? Bu işlem geri alınamaz.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Evet, Sil',
        cancelButtonText: 'İptal',
        backdrop: `rgba(17,17,22,0.8)`
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Siliniyor...', allowOutsideClick: false, showConfirmButton: false, didOpen: () => { Swal.showLoading(); } });
            window.location.href = 'haberler.php?action=delete&id=' + id;
        }
    });
}

const urlParams = new URLSearchParams(window.location.search);
const swalType = urlParams.get('swal');
if(swalType) {
    let title = 'Başarılı!';
    let text = '';
    if(swalType === 'deleted') text = 'Haber sistemden kalıcı olarak silindi.';
    if(swalType === 'status_changed') text = 'Haberin yayın durumu değiştirildi.';
    Swal.fire({ icon: 'success', title: title, text: text, showConfirmButton: false, timer: 2000, timerProgressBar: true });
    window.history.replaceState(null, null, window.location.pathname);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>