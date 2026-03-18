<?php
require_once __DIR__ . '/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: haberler.php');
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$title = trim($_POST['title'] ?? '');
$summary = trim($_POST['summary'] ?? '');
$content = $_POST['content'] ?? '';
$cat = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? intval($_POST['category_id']) : null;
$author = trim($_POST['author'] ?? 'Editör');
$feat = isset($_POST['is_featured']) ? 1 : 0;
$brk = isset($_POST['is_breaking']) ? 1 : 0;
$head = isset($_POST['is_headline']) ? 1 : 0;
$status = $_POST['status'] ?? 'draft';
$tags = trim($_POST['tags'] ?? '');
$source = trim($_POST['source'] ?? '');
$slug = createSlug($title);
$image = $_POST['existing_image'] ?? '';

if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        $fn = 'news_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
        $upload_path = __DIR__ . '/uploads/' . $fn;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $image = 'uploads/' . $fn;
        }
    }
}

if (empty($title) || empty($content)) {
    $_SESSION['form_error'] = 'Başlık ve içerik alanları zorunludur!';
    header('Location: haberekle.php');
    exit;
}

try {
    if ($id > 0) {
        $st = $db->prepare("UPDATE news SET 
            title = ?, slug = ?, summary = ?, content = ?, image = ?,
            category_id = ?, is_featured = ?, is_breaking = ?, is_headline = ?, 
            author = ?, tags = ?, source = ?, status = ?, updated_at = NOW() 
            WHERE id = ?");
        
        $st->execute([
            $title, $slug, $summary, $content, $image, 
            $cat, $feat, $brk, $head, $author, $tags, $source, $status, $id
        ]);
        
        header('Location: haberler.php?msg=saved');
    } else {
        $st = $db->prepare("INSERT INTO news (
            title, slug, summary, content, image, category_id,
            is_featured, is_breaking, is_headline, author, tags, source, status,
            published_at, created_at
        ) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?, NOW(), NOW())");
        
        $st->execute([
            $title, $slug, $summary, $content, $image, $cat,
            $feat, $brk, $head, $author, $tags, $source, $status
        ]);
        
        header('Location: haberler.php?msg=saved');
    }
    exit;
    
} catch (PDOException $e) {
    $_SESSION['form_error'] = 'Veritabanı hatası: ' . $e->getMessage();
    header('Location: haberekle.php');
    exit;
}