<?php
require_once __DIR__ . '/config.php';
if (!isLoggedIn()) { header('Location: login.php'); exit; }

$action = $_GET['action'] ?? '';

// FORM GÖNDERİLDİĞİNDE (EKLEME / DÜZENLEME)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    
    if ($postAction === 'add') {
        $name = trim($_POST['name'] ?? '');
        $color = $_POST['color'] ?? '#4361ee';
        $icon = trim($_POST['icon'] ?? 'fa-folder');
        $sort = intval($_POST['sort_order'] ?? 0);
        $slug = createSlug($name);
        
        if (!empty($name)) {
            try {
                $st = $db->prepare("INSERT INTO categories (name, slug, color, icon, sort_order) VALUES (?, ?, ?, ?, ?)");
                $st->execute([$name, $slug, $color, $icon, $sort]);
                
                $_SESSION['success_msg'] = 'Kategori başarıyla eklendi!';
                header('Location: kategoriler.php');
                exit;
            } catch (PDOException $e) {
                // Eğer aynı isimde (slug) bir kategori varsa veritabanı hata verir, biz de ekrana basarız
                $_SESSION['error_msg'] = 'Bu kategori eklenemedi! Muhtemelen aynı isme sahip bir kategori zaten var.';
                header('Location: kategoriler.php');
                exit;
            }
        } else {
            $_SESSION['error_msg'] = 'Kategori adı boş olamaz!';
            header('Location: kategoriler.php');
            exit;
        }
    } 
    
    elseif ($postAction === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $color = $_POST['color'] ?? '#4361ee';
        $icon = trim($_POST['icon'] ?? 'fa-folder');
        $sort = intval($_POST['sort_order'] ?? 0);
        $slug = createSlug($name);
        
        if ($id > 0 && !empty($name)) {
            try {
                $st = $db->prepare("UPDATE categories SET name=?, slug=?, color=?, icon=?, sort_order=? WHERE id=?");
                $st->execute([$name, $slug, $color, $icon, $sort, $id]);
                
                $_SESSION['success_msg'] = 'Kategori başarıyla güncellendi!';
                header('Location: kategoriler.php');
                exit;
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = 'Güncelleme hatası! Aynı isme sahip başka bir kategori olabilir.';
                header('Location: kategoriler.php');
                exit;
            }
        }
    }
}

// SİLME İŞLEMİ
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $db->prepare("UPDATE news SET category_id = NULL WHERE category_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
        
        $_SESSION['success_msg'] = 'Kategori sistemden silindi.';
        header('Location: kategoriler.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = 'Kategori silinirken bir hata oluştu.';
        header('Location: kategoriler.php');
        exit;
    }
}

$categories = $db->query("SELECT c.*, (SELECT COUNT(*) FROM news n WHERE n.category_id = c.id) as news_count FROM categories c ORDER BY c.sort_order ASC, c.id DESC")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Kategori Yönetimi';
$current_page = 'kategoriler';
include __DIR__ . '/includes/header.php';
?>

<style>
.btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; border: none; cursor: pointer; transition: 0.2s; }
.btn-primary { background: var(--primary); color: #fff; box-shadow: 0 4px 10px rgba(67, 97, 238, 0.2); }
.btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
.btn-cancel { background: var(--border); color: var(--text); }
.btn-cancel:hover { background: #cbd5e1; }
.btn-icon { padding: 8px; border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border: none; cursor: pointer; transition: 0.2s; }
.btn-edit { background: var(--secondary); color: var(--primary); }
.btn-edit:hover { background: var(--primary); color: #fff; }
.btn-delete { background: #fee2e2; color: var(--danger); }
.btn-delete:hover { background: var(--danger); color: #fff; }

.card { background: #fff; border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); overflow: hidden; margin-bottom: 30px; }
.table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; min-width: 600px; }
.data-table th { background: var(--light); padding: 16px 20px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); }
.data-table td { padding: 16px 20px; border-bottom: 1px solid var(--border); vertical-align: middle; }
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover { background: #f8fafc; }

.cat-box { display: flex; align-items: center; gap: 12px; }
.cat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
.cat-info { display: flex; flex-direction: column; }
.cat-name { font-weight: 600; color: var(--dark-text); font-size: 15px; }
.cat-slug { font-size: 12px; color: var(--text-light); font-family: monospace; }
.count-badge { background: var(--light); border: 1px solid var(--border); padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; color: var(--text); }

.modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); z-index: 9999; display: flex; align-items: center; justify-content: center; opacity: 0; visibility: hidden; transition: 0.3s; }
.modal-overlay.active { opacity: 1; visibility: visible; }
.modal { background: #fff; width: 100%; max-width: 500px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); transform: scale(0.95) translateY(20px); transition: 0.3s cubic-bezier(0.4,0,0.2,1); opacity: 0; }
.modal-overlay.active .modal { transform: scale(1) translateY(0); opacity: 1; }
.modal-header { padding: 24px 30px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
.modal-title { font-size: 18px; font-weight: 700; color: var(--dark-text); margin: 0; }
.close-btn { background: none; border: none; font-size: 20px; color: var(--text-light); cursor: pointer; transition: 0.2s; padding: 0; }
.close-btn:hover { color: var(--danger); transform: rotate(90deg); }
.modal-body { padding: 30px; }

.form-group { margin-bottom: 20px; }
.form-label { display: block; font-size: 13px; font-weight: 600; color: var(--dark-text); margin-bottom: 8px; }
.form-control { width: 100%; padding: 12px 16px; border: 2px solid var(--border); border-radius: 10px; font-size: 14px; color: var(--text); transition: 0.2s; outline: none; background: #fff; }
.form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 4px var(--secondary); }
.input-group { display: flex; gap: 15px; }
.color-picker { -webkit-appearance: none; width: 50px; height: 46px; border: 2px solid var(--border); border-radius: 10px; padding: 2px; cursor: pointer; background: #fff; }
.color-picker::-webkit-color-swatch-wrapper { padding: 0; }
.color-picker::-webkit-color-swatch { border: none; border-radius: 6px; }
.modal-footer { display: flex; justify-content: flex-end; gap: 12px; margin-top: 30px; }

@media (max-width: 768px) {
    .data-table thead { display: none; }
    .data-table tbody tr { display: block; padding: 15px; border-bottom: 1px solid var(--border); }
    .data-table tbody td { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border: none; }
    .data-table tbody td::before { content: attr(data-label); font-size: 12px; font-weight: 600; color: var(--text-light); text-transform: uppercase; }
    .cat-box { text-align: right; justify-content: flex-end; }
    .modal { margin: 20px; height: auto; max-height: calc(100vh - 40px); overflow-y: auto; }
}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Kategori Yönetimi</h1>
    </div>
    <button class="btn btn-primary" onclick="openModal('add')">
        <i class="fas fa-plus"></i> Yeni Kategori
    </button>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Sıra</th>
                    <th>Kategori Bilgisi</th>
                    <th style="text-align: center;">Haber Sayısı</th>
                    <th style="text-align: right;">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($categories)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 40px; color: var(--text-light);">
                        <i class="fas fa-folder-open" style="font-size: 40px; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p>Henüz hiçbir kategori eklenmemiş.</p>
                    </td>
                </tr>
                <?php else: foreach($categories as $cat): ?>
                <tr>
                    <td data-label="Sıra">
                        <span style="font-weight: 600; color: var(--text-light);">#<?php echo $cat['sort_order']; ?></span>
                    </td>
                    <td data-label="Kategori">
                        <div class="cat-box">
                            <div class="cat-icon" style="background: <?php echo e($cat['color']); ?>20; color: <?php echo e($cat['color']); ?>;">
                                <i class="fas <?php echo e($cat['icon']); ?>"></i>
                            </div>
                            <div class="cat-info">
                                <span class="cat-name"><?php echo e($cat['name']); ?></span>
                                <span class="cat-slug">/<?php echo e($cat['slug']); ?></span>
                            </div>
                        </div>
                    </td>
                    <td data-label="Haber Sayısı" style="text-align: center;">
                        <span class="count-badge"><?php echo $cat['news_count']; ?> Haber</span>
                    </td>
                    <td data-label="İşlemler" style="text-align: right;">
                        <button class="btn-icon btn-edit" onclick='openModal("edit", <?php echo json_encode($cat, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' title="Düzenle">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="btn-icon btn-delete" onclick="confirmDelete(<?php echo $cat['id']; ?>)" title="Sil">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="catModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Yeni Kategori Ekle</h3>
            <button type="button" class="close-btn" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form id="catForm" method="POST" action="">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="catId" value="">
                
                <div class="form-group">
                    <label class="form-label">Kategori Adı *</label>
                    <input type="text" name="name" id="catName" class="form-control" placeholder="Örn: Teknoloji" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">İkon ve Renk</label>
                    <div class="input-group">
                        <input type="color" name="color" id="catColor" class="color-picker" value="#4361ee">
                        <input type="text" name="icon" id="catIcon" class="form-control" placeholder="fa-newspaper" value="fa-folder" style="flex: 1;">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Sıralama Numarası</label>
                    <input type="number" name="sort_order" id="catSort" class="form-control" value="0" min="0">
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" onclick="closeModal()">İptal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const catModal = document.getElementById('catModal');
const catForm = document.getElementById('catForm');

function openModal(type, data = null) {
    catModal.classList.add('active');
    if(type === 'add') {
        document.getElementById('modalTitle').innerText = 'Yeni Kategori Ekle';
        document.getElementById('formAction').value = 'add';
        catForm.reset();
        document.getElementById('catId').value = '';
        document.getElementById('catColor').value = '#4361ee';
    } else if(type === 'edit' && data) {
        document.getElementById('modalTitle').innerText = 'Kategori Düzenle';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('catId').value = data.id;
        document.getElementById('catName').value = data.name;
        document.getElementById('catColor').value = data.color;
        document.getElementById('catIcon').value = data.icon;
        document.getElementById('catSort').value = data.sort_order;
    }
}

function closeModal() {
    catModal.classList.remove('active');
}

catForm.onsubmit = function() {
    document.getElementById('catModal').classList.remove('active');
    Swal.fire({ title: 'İşlem Yapılıyor...', allowOutsideClick: false, showConfirmButton: false, didOpen: () => { Swal.showLoading(); } });
    return true; // Formun kendi sayfasına POST atmasına izin veriyoruz (action="")
};

function confirmDelete(id) {
    Swal.fire({
        title: 'Emin Misiniz?',
        text: 'Bu kategori silinecek. Haberler "Kategorisiz" olarak güncellenecek.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Evet, Sil',
        cancelButtonText: 'İptal',
        backdrop: `rgba(15,23,42,0.6)`
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Siliniyor...', allowOutsideClick: false, showConfirmButton: false, didOpen: () => { Swal.showLoading(); } });
            window.location.href = 'kategoriler.php?action=delete&id=' + id;
        }
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>