<?php
require_once __DIR__ . '/config.php';
if (!isLoggedIn()) { header('Location: login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $content = $_POST['content'] ?? '';
    $cat = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? intval($_POST['category_id']) : null;
    $status = $_POST['status'] ?? 'draft';
    $feat = isset($_POST['is_featured']) ? 1 : 0;
    $head = isset($_POST['is_headline']) ? 1 : 0;
    $brk = isset($_POST['is_breaking']) ? 1 : 0;
    $slug = createSlug($title);
    $image = '';

    if (empty($title) || empty($content)) {
        $_SESSION['error_msg'] = 'Başlık ve içerik alanları boş bırakılamaz!';
    } else {
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $fn = 'news_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                $upload_path = __DIR__ . '/uploads/' . $fn;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image = 'uploads/' . $fn;
                } else {
                    $_SESSION['error_msg'] = 'Görsel yüklenemedi. Klasör izinlerini kontrol edin.';
                }
            } else {
                $_SESSION['error_msg'] = 'Sadece JPG, PNG, GIF ve WEBP formatları desteklenir.';
            }
        }

        if (!isset($_SESSION['error_msg'])) {
            try {
                $st = $db->prepare("INSERT INTO news (title, slug, summary, content, image, category_id, is_featured, is_breaking, is_headline, status, published_at, created_at) VALUES(?,?,?,?,?,?,?,?,?,?, NOW(), NOW())");
                $st->execute([$title, $slug, $summary, $content, $image, $cat, $feat, $brk, $head, $status]);
                
                $_SESSION['success_msg'] = 'Haber başarıyla yayınlandı!';
                header('Location: haberler.php');
                exit;
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = 'Kayıt Hatası: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'Yeni Haber Ekle';
$current_page = 'haber-ekle';
$categories = getCategories();

include __DIR__ . '/includes/header.php';
?>

<style>
.form-grid { display: grid; grid-template-columns: 2.2fr 1fr; gap: 24px; }
.card { background: #fff; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); overflow: hidden; margin-bottom: 24px; }
.card-header { padding: 18px 24px; border-bottom: 1px solid var(--border); font-weight: 700; background: var(--light); color: var(--dark-text); font-size: 15px; display: flex; align-items: center; gap: 8px; }
.card-body { padding: 24px; }

.form-group { margin-bottom: 20px; }
.form-label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 8px; color: var(--text); }
.form-control { width: 100%; padding: 12px 16px; border: 2px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 14px; color: var(--dark-text); transition: all 0.2s; background: #fff; }
.form-control:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1); }

.ck-editor__editable { min-height: 500px !important; font-size: 15px; line-height: 1.6; color: var(--dark-text); border-radius: 0 0 8px 8px !important; border: 2px solid var(--border) !important; border-top: none !important; }
.ck-toolbar { border: 2px solid var(--border) !important; border-radius: 8px 8px 0 0 !important; background: var(--light) !important; padding: 8px !important; }
.ck.ck-editor__editable:not(.ck-editor__nested-editable).ck-focused { border-color: var(--primary) !important; box-shadow: none !important; }

.upload-box { border: 2px dashed #cbd5e1; padding: 30px 20px; text-align: center; border-radius: 12px; position: relative; background: var(--light); transition: all 0.2s; cursor: pointer; }
.upload-box:hover { border-color: var(--primary); background: rgba(67, 97, 238, 0.02); }
.upload-box i { font-size: 32px; color: var(--primary); margin-bottom: 12px; }
.upload-box .file-name { margin-top: 5px; font-size: 13px; color: var(--text-light); font-weight: 600; }
.upload-input { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }

.custom-checkbox { display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; transition: 0.2s; }
.custom-checkbox:hover { background: var(--light); }
.custom-checkbox input { width: 18px; height: 18px; accent-color: var(--primary); cursor: pointer; }
.custom-checkbox span { font-size: 14px; font-weight: 500; color: var(--text); }

@media (max-width: 1024px) { .form-grid { grid-template-columns: 1fr; } }
</style>

<form id="newsForm" method="POST" enctype="multipart/form-data">
    <div class="form-grid">
        <div class="form-col-main">
            <div class="card">
                <div class="card-header"><i class="fas fa-pen-nib" style="color: var(--primary);"></i> Haber İçeriği</div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Haber Başlığı *</label>
                        <input type="text" name="title" class="form-control" placeholder="Etkileyici bir başlık girin..." required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kısa Özet (Spot)</label>
                        <textarea name="summary" class="form-control" rows="3" placeholder="Haberin ana fikrini özetleyin..."></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Detaylı İçerik *</label>
                        <textarea name="content" id="editor"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-col-side">
            <div class="card">
                <div class="card-header"><i class="fas fa-sliders-h" style="color: var(--primary);"></i> Yayın Ayarları</div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Kategori Seçimi *</label>
                        <select name="category_id" class="form-control" required>
                            <option value="">Kategori Seçin...</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo e($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Yayın Durumu</label>
                        <select name="status" class="form-control">
                            <option value="published">Hemen Yayınla</option>
                            <option value="draft">Taslak Olarak Kaydet</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kapak Görseli *</label>
                        <div class="upload-box" id="uploadBox">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <div class="file-name" id="fileName">Görsel seçmek için tıklayın</div>
                            <input type="file" name="image" id="imageInput" class="upload-input" accept="image/*" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 24px;">
                        <label class="form-label">Özel Etiketler</label>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <label class="custom-checkbox">
                                <input type="checkbox" name="is_featured" value="1">
                                <span><i class="fas fa-star" style="color:#f59e0b; width:18px;"></i> Öne Çıkan</span>
                            </label>
                            <label class="custom-checkbox">
                                <input type="checkbox" name="is_headline" value="1">
                                <span><i class="fas fa-bullhorn" style="color:#3b82f6; width:18px;"></i> Manşet</span>
                            </label>
                            <label class="custom-checkbox">
                                <input type="checkbox" name="is_breaking" value="1">
                                <span><i class="fas fa-bolt" style="color:var(--red); width:18px;"></i> Son Dakika</span>
                            </label>
                        </div>
                    </div>
                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 15px;">
                            <i class="fas fa-paper-plane"></i> Haberi Kaydet ve Yayınla
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
let theEditor;
if (document.querySelector('#editor')) {
    ClassicEditor.create(document.querySelector('#editor'), { 
        toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|', 'undo', 'redo'] 
    })
    .then(editor => { theEditor = editor; })
    .catch(error => { console.error(error); });
}

document.getElementById('imageInput').addEventListener('change', function(e) {
    const fileName = e.target.files[0] ? e.target.files[0].name : 'Görsel seçmek için tıklayın';
    const uploadBox = document.getElementById('uploadBox');
    const fileNameDiv = document.getElementById('fileName');
    
    if(e.target.files[0]) {
        fileNameDiv.innerHTML = `<span style="color:#10b981;">${fileName}</span>`;
        uploadBox.style.borderColor = '#10b981';
        uploadBox.style.background = 'rgba(16, 185, 129, 0.05)';
    } else {
        fileNameDiv.innerHTML = fileName;
        uploadBox.style.borderColor = '#cbd5e1';
        uploadBox.style.background = 'var(--light)';
    }
});

document.getElementById('newsForm').addEventListener('submit', function() {
    if(theEditor) { document.querySelector('#editor').value = theEditor.getData(); }
    Swal.fire({ 
        title: 'Haber İşleniyor...', 
        text: 'Lütfen bekleyin',
        allowOutsideClick: false, 
        showConfirmButton: false, 
        didOpen: () => { Swal.showLoading(); } 
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>