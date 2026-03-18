<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Istanbul');

$upload_dir = __DIR__ . '/admin/uploads';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

try {
    $host = 'localhost';
    $dbname = 'haber_sitesi';
    $user = 'root';
    $pass = '';

    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (Exception $ex) {
    die('Veritabanı baglantı hatası: ' . $ex->getMessage());
}

try {
    $db->exec("CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(255) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, fullname VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL UNIQUE, color VARCHAR(50) DEFAULT '#dc2626', icon VARCHAR(50) DEFAULT 'fa-newspaper', sort_order INT DEFAULT 0, is_active INT DEFAULT 1, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS news (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, summary TEXT, content LONGTEXT NOT NULL, image VARCHAR(255), category_id INT, is_featured INT DEFAULT 0, is_breaking INT DEFAULT 0, is_headline INT DEFAULT 0, views INT DEFAULT 0, author VARCHAR(100) DEFAULT 'Editör', tags VARCHAR(255), source VARCHAR(255), status VARCHAR(20) DEFAULT 'published', published_at DATETIME DEFAULT CURRENT_TIMESTAMP, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL)");
    
    $cols = $db->query("SHOW COLUMNS FROM news LIKE 'is_featured'")->fetchAll();
    if(empty($cols)) {
        $db->exec("ALTER TABLE news ADD COLUMN is_featured INT DEFAULT 0");
        $db->exec("ALTER TABLE news ADD COLUMN is_breaking INT DEFAULT 0");
        $db->exec("ALTER TABLE news ADD COLUMN is_headline INT DEFAULT 0");
    }
} catch (Exception $ex) {
}


function createSlug($text) {
    $turkce = ['ç','ğ','ı','ö','ş','ü','Ç','Ğ','İ','Ö','Ş','Ü',' '];
    $duz = ['c','g','i','o','s','u','c','g','i','o','s','u','-'];
    $text = str_replace($turkce, $duz, $text);
    return trim(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9\-]/', '', strtolower($text))), '-');
}

function timeAgo($date) {
    if (empty($date)) return 'Az önce';
    $diff = time() - strtotime($date);
    if ($diff < 60) return 'Az önce';
    if ($diff < 3600) return floor($diff / 60) . ' dk önce';
    if ($diff < 86400) return floor($diff / 3600) . ' saat önce';
    return date('d.m.Y', strtotime($date));
}

function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function getCategories() {
    global $db;
    return $db->query("SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order")->fetchAll();
}

function getNews($options = []) {
    global $db;
    $where = ["n.status='published'"];
    $params = [];
    $limit = isset($options['limit']) ? intval($options['limit']) : 10;
    $offset = isset($options['offset']) ? intval($options['offset']) : 0;
    $order = $options['orderBy'] ?? 'n.published_at DESC';

    if (!empty($options['category_id'])) {
        $where[] = 'n.category_id=?';
        $params[] = intval($options['category_id']);
    }
    if (!empty($options['is_featured'])) {
        $where[] = 'n.is_featured=1';
    }
    if (!empty($options['is_breaking'])) {
        $where[] = 'n.is_breaking=1';
    }
    if (!empty($options['is_headline'])) {
        $where[] = 'n.is_headline=1';
    }
    if (!empty($options['search'])) {
        $where[] = '(n.title LIKE ? OR n.summary LIKE ?)';
        $params[] = '%' . $options['search'] . '%';
        $params[] = '%' . $options['search'] . '%';
    }
    if (!empty($options['exclude_id'])) {
        $where[] = 'n.id!=?';
        $params[] = intval($options['exclude_id']);
    }

    $sql = "SELECT n.*, c.name as cat_name, c.slug as cat_slug, c.color as cat_color, c.icon as cat_icon
            FROM news n
            LEFT JOIN categories c ON n.category_id = c.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY $order
            LIMIT $limit OFFSET $offset";

    $st = $db->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function getNewsById($id) {
    global $db;
    $st = $db->prepare("SELECT n.*, c.name as cat_name, c.slug as cat_slug, c.color as cat_color, c.icon as cat_icon
                         FROM news n
                         LEFT JOIN categories c ON n.category_id = c.id
                         WHERE n.id = ?");
    $st->execute([intval($id)]);
    return $st->fetch();
}

function countNews($options = []) {
    global $db;
    $where = ["status='published'"];
    $params = [];
    
    if (!empty($options['category_id'])) {
        $where[] = 'category_id=?';
        $params[] = intval($options['category_id']);
    }
    if (!empty($options['search'])) {
        $where[] = '(title LIKE ? OR summary LIKE ?)';
        $params[] = '%' . $options['search'] . '%';
        $params[] = '%' . $options['search'] . '%';
    }
    
    $st = $db->prepare("SELECT COUNT(*) as c FROM news WHERE " . implode(' AND ', $where));
    $st->execute($params);
    return $st->fetch()['c'];
}

function newsImage($news, $class = '') {
    $imgPath = '';
    
    if (!empty($news['image'])) {
        if (file_exists(__DIR__ . '/admin/' . $news['image'])) {
            $imgPath = 'admin/' . $news['image'];
        } elseif (file_exists(__DIR__ . '/' . $news['image'])) {
            $imgPath = $news['image'];
        }
    }

    if ($imgPath !== '') {
        return '<img src="' . htmlspecialchars($imgPath, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($news['title'] ?? '', ENT_QUOTES, 'UTF-8') . '" class="' . $class . '" loading="lazy">';
    }

    $gradients = [
        'linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%)',
        'linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)',
        'linear-gradient(135deg, #2d1b69 0%, #6b21a8 50%, #a855f7 100%)',
        'linear-gradient(135deg, #134e5e 0%, #71b280 100%)',
        'linear-gradient(135deg, #0c0c1d 0%, #1a1a3e 50%, #2d2d6b 100%)',
        'linear-gradient(135deg, #141e30 0%, #243b55 100%)',
        'linear-gradient(135deg, #1e3c72 0%, #2a5298 100%)'
    ];

    $id = isset($news['id']) ? $news['id'] : 0;
    $gradient = $gradients[$id % count($gradients)];
    $letter = mb_strtoupper(mb_substr(isset($news['title']) ? $news['title'] : 'H', 0, 1, 'UTF-8'), 'UTF-8');
    $icon = isset($news['cat_icon']) ? $news['cat_icon'] : 'fa-newspaper';

    return '<div class="placeholder-img ' . $class . '" style="background:' . $gradient . '">
                <div class="placeholder-content">
                    <i class="fas ' . e($icon) . '"></i>
                    <span>' . $letter . '</span>
                </div>
            </div>';
}

function formatViews($n) {
    $n = intval($n);
    if ($n >= 1000000) return number_format($n / 1000000, 1) . 'M';
    if ($n >= 1000) return number_format($n / 1000, 1) . 'B';
    return $n;
}

function turkishDate() {
    $aylar = [
        'January' => 'Ocak', 'February' => 'Şubat', 'March' => 'Mart', 'April' => 'Nisan',
        'May' => 'Mayıs', 'June' => 'Haziran', 'July' => 'Temmuz', 'August' => 'Ağustos',
        'September' => 'Eylül', 'October' => 'Ekim', 'November' => 'Kasım', 'December' => 'Aralık'
    ];
    $gunler = [
        'Monday' => 'Pazartesi', 'Tuesday' => 'Salı', 'Wednesday' => 'Çarşamba',
        'Thursday' => 'Perşembe', 'Friday' => 'Cuma', 'Saturday' => 'Cumartesi', 'Sunday' => 'Pazar'
    ];
    return date('d') . ' ' . $aylar[date('F')] . ' ' . date('Y') . ' ' . $gunler[date('l')];
}
?>