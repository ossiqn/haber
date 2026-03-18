<?php
require_once __DIR__ . '/config.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: kategoriler.php');
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$name = trim($_POST['name'] ?? '');
$color = $_POST['color'] ?? '#dc2626';
$icon = trim($_POST['icon'] ?? 'fa-newspaper');
$sort = intval($_POST['sort_order'] ?? 0);
$slug = createSlug($name);

if (empty($name)) {
    $_SESSION['form_error'] = 'Kategori adı zorunludur!';
    header('Location: kategoriler.php' . ($id ? '?edit_id=' . $id : ''));
    exit;
}

try {
    if ($id > 0) {
        $st = $db->prepare("UPDATE categories SET name = ?, slug = ?, color = ?, icon = ?, sort_order = ? WHERE id = ?");
        $st->execute([$name, $slug, $color, $icon, $sort, $id]);
    } else {
        $st = $db->prepare("INSERT INTO categories(name, slug, color, icon, sort_order) VALUES(?,?,?,?,?)");
        $st->execute([$name, $slug, $color, $icon, $sort]);
    }
    
    header('Location: kategoriler.php?msg=saved');
    exit;
    
} catch (PDOException $e) {
    $_SESSION['form_error'] = 'Veritabanı hatası: ' . $e->getMessage();
    header('Location: kategoriler.php' . ($id ? '?edit_id=' . $id : ''));
    exit;
}