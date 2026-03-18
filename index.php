<?php
require_once __DIR__ . '/config.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'home';

if ($page === 'detail' && !empty($_GET['id'])) {
    $viewId = intval($_GET['id']);
    $sessionKey = 'viewed_' . $viewId;
    if (!isset($_SESSION[$sessionKey])) {
        $_SESSION[$sessionKey] = true;
        $db->exec("UPDATE news SET views = views + 1 WHERE id = " . $viewId);
    }
}

$categories = getCategories();

$pageTitle = 'HaberPortal - Son Dakika Haberler';
$metaDesc = 'Turkiyenin en guncel haber portali. Son dakika haberleri, ekonomi, spor, teknoloji.';

if ($page === 'detail') {
    $detailNews = getNewsById(isset($_GET['id']) ? $_GET['id'] : 0);
    if ($detailNews) {
        $pageTitle = e($detailNews['title']) . ' | HaberPortal';
        $metaDesc = e($detailNews['summary']);
    }
} elseif ($page === 'category') {
    $catSlug = isset($_GET['slug']) ? $_GET['slug'] : '';
    foreach ($categories as $c) {
        if ($c['slug'] === $catSlug) {
            $pageTitle = $c['name'] . ' Haberleri | HaberPortal';
            $metaDesc = $c['name'] . ' kategorisindeki son haberler.';
        }
    }
} elseif ($page === 'search') {
    $searchQ = isset($_GET['q']) ? $_GET['q'] : '';
    $pageTitle = 'Arama: ' . e($searchQ) . ' | HaberPortal';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="description" content="<?php echo $metaDesc; ?>">
    <meta name="theme-color" content="#dc2626">
    <title><?php echo $pageTitle; ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --red: #dc2626;
            --red-dark: #b91c1c;
            --red-light: #fef2f2;
            --dark: #0a0a0f;
            --dark2: #111118;
            --g900: #18181b;
            --g800: #27272a;
            --g700: #3f3f46;
            --g600: #52525b;
            --g500: #71717a;
            --g400: #a1a1aa;
            --g300: #d4d4d8;
            --g200: #e4e4e7;
            --g100: #f4f4f5;
            --g50: #fafafa;
            --white: #ffffff;
            --font: 'Inter', system-ui, -apple-system, sans-serif;
            --serif: 'Merriweather', Georgia, serif;
            --shadow-sm: 0 1px 2px rgba(0,0,0,.05);
            --shadow: 0 1px 3px rgba(0,0,0,.1);
            --shadow-md: 0 4px 6px rgba(0,0,0,.07);
            --shadow-lg: 0 10px 25px rgba(0,0,0,.1);
            --radius: 10px;
            --radius-lg: 14px;
            --ease: cubic-bezier(.4, 0, .2, 1);
            --max-w: 1320px;
        }

        html {
            scroll-behavior: smooth;
            -webkit-text-size-adjust: 100%;
            overflow-x: hidden;
            width: 100%;
        }

        body {
            font-family: var(--font);
            color: var(--g900);
            background: var(--g100);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
            width: 100%;
            position: relative;
        }

        a {
            text-decoration: none;
            color: inherit;
            transition: color 0.2s var(--ease);
        }

        img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        ul { list-style: none; }

        .wrap {
            max-width: var(--max-w);
            margin: 0 auto;
            padding: 0 20px;
            width: 100%;
        }

        .topbar {
            background: var(--dark);
            color: var(--g500);
            font-size: 12px;
            padding: 7px 0;
            width: 100%;
        }

        .topbar .wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar-l {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .topbar-l i { color: var(--red); margin-right: 4px; }

        .topbar-r {
            display: flex;
            gap: 6px;
        }

        .topbar-r a {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            color: var(--g500);
            background: rgba(255,255,255,.06);
            font-size: 12px;
            transition: all 0.2s var(--ease);
        }

        .topbar-r a:hover {
            background: var(--red);
            color: var(--white);
        }

        .header {
            background: var(--white);
            padding: 14px 0;
            border-bottom: 1px solid var(--g200);
            position: relative;
            z-index: 100;
            width: 100%;
        }

        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .logo-mark {
            width: 44px;
            height: 44px;
            background: var(--red);
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-family: var(--serif);
            font-size: 22px;
            font-weight: 900;
            box-shadow: 0 3px 12px rgba(220,38,38,.3);
        }

        .logo-text {
            font-family: var(--serif);
            font-size: 24px;
            font-weight: 900;
            color: var(--g900);
            line-height: 1.1;
        }

        .logo-text em {
            color: var(--red);
            font-style: normal;
        }

        .logo-sub {
            font-family: var(--font);
            font-size: 9px;
            color: var(--g500);
            text-transform: uppercase;
            letter-spacing: 2.5px;
            font-weight: 600;
        }

        .search-form {
            position: relative;
            width: 320px;
            flex-shrink: 0;
        }

        .search-form input {
            width: 100%;
            padding: 10px 42px 10px 16px;
            border: 2px solid var(--g200);
            border-radius: 100px;
            font-size: 13px;
            font-family: var(--font);
            background: var(--g50);
            transition: all 0.2s var(--ease);
        }

        .search-form input:focus {
            outline: none;
            border-color: var(--red);
            box-shadow: 0 0 0 4px rgba(220,38,38,.06);
        }

        .search-form button {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--red);
            color: var(--white);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 12px;
        }

        .hamburger {
            display: none;
            background: none;
            border: none;
            font-size: 22px;
            color: var(--g800);
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .hamburger:hover { background: var(--g100); }

        .nav {
            background: var(--g900);
            position: sticky;
            top: 0;
            z-index: 999;
            width: 100%;
        }

        .nav-list {
            display: flex;
            align-items: center;
            overflow-x: auto;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
            gap: 0;
        }

        .nav-list::-webkit-scrollbar { display: none; }

        .nav-list a {
            color: rgba(255,255,255,.8);
            padding: 13px 16px;
            font-size: 12.5px;
            font-weight: 600;
            white-space: nowrap;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            position: relative;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s var(--ease);
        }

        .nav-list a:hover,
        .nav-list a.on {
            color: var(--white);
            background: rgba(255,255,255,.1);
        }

        .nav-list a.on::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 12px;
            right: 12px;
            height: 3px;
            background: var(--red);
            border-radius: 3px 3px 0 0;
        }

        .nav-list a .nav-icon {
            font-size: 11px;
            opacity: 0.7;
        }

        .mobile-nav-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.6);
            z-index: 9998;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .mobile-nav-overlay.show {
            display: block;
            opacity: 1;
        }

        .mobile-nav {
            position: fixed;
            top: 0;
            right: -300px;
            width: 300px;
            height: 100%;
            background: var(--white);
            z-index: 9999;
            overflow-y: auto;
            transition: right 0.3s var(--ease);
            box-shadow: -5px 0 25px rgba(0,0,0,.15);
        }

        .mobile-nav.show {
            right: 0;
        }

        .mobile-nav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid var(--g200);
        }

        .mobile-nav-header h3 {
            font-size: 16px;
            font-weight: 800;
        }

        .mobile-nav-close {
            background: none;
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: var(--g600);
            padding: 4px;
        }

        .mobile-nav-items {
            padding: 12px 0;
        }

        .mobile-nav-items a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            font-size: 14px;
            font-weight: 600;
            color: var(--g800);
            border-bottom: 1px solid var(--g100);
            transition: all 0.15s;
        }

        .mobile-nav-items a:hover,
        .mobile-nav-items a.on {
            background: var(--red-light);
            color: var(--red);
        }

        .mobile-nav-items a i {
            width: 20px;
            text-align: center;
            font-size: 14px;
        }

        .mobile-search {
            padding: 16px 20px;
            border-bottom: 1px solid var(--g200);
        }

        .mobile-search input {
            width: 100%;
            padding: 11px 14px;
            border: 2px solid var(--g200);
            border-radius: 10px;
            font-size: 14px;
            font-family: var(--font);
        }

        .mobile-search input:focus {
            outline: none;
            border-color: var(--red);
        }

        .ticker {
            background: var(--dark2);
            overflow: hidden;
            border-bottom: 2px solid var(--red);
            width: 100%;
        }

        .ticker-flex { display: flex; align-items: stretch; }

        .ticker-tag {
            background: var(--red);
            color: var(--white);
            padding: 9px 18px;
            font-weight: 800;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            display: flex;
            align-items: center;
            gap: 7px;
            white-space: nowrap;
            position: relative;
            z-index: 2;
            flex-shrink: 0;
        }

        .ticker-tag::after {
            content: '';
            position: absolute;
            right: -12px;
            top: 0;
            bottom: 0;
            width: 24px;
            background: var(--red);
            transform: skewX(-14deg);
        }

        .ticker-dot {
            width: 7px;
            height: 7px;
            background: var(--white);
            border-radius: 50%;
            animation: blinker 1.5s ease infinite;
        }

        @keyframes blinker {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .ticker-scroll {
            flex: 1;
            overflow: hidden;
            display: flex;
            align-items: center;
            padding-left: 22px;
        }

        .ticker-track {
            display: flex;
            animation: marquee 40s linear infinite;
            gap: 40px;
        }

        .ticker-track:hover { animation-play-state: paused; }

        .ticker-item {
            color: rgba(255,255,255,.85);
            font-size: 12.5px;
            font-weight: 500;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ticker-item::before {
            content: '';
            width: 5px;
            height: 5px;
            background: var(--red);
            border-radius: 50%;
            flex-shrink: 0;
        }

        .ticker-item a:hover { color: var(--red); }

        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        .hero { padding: 24px 0 8px; }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 14px;
        }

        .hero-card {
            position: relative;
            border-radius: var(--radius-lg);
            overflow: hidden;
            display: block;
        }

        .hero-card img,
        .hero-card .placeholder-img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hero-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,.88) 0%, rgba(0,0,0,.2) 50%, transparent 100%);
            z-index: 1;
            transition: background 0.3s;
        }

        .hero-card:hover::after {
            background: linear-gradient(to top, rgba(0,0,0,.92) 0%, rgba(0,0,0,.35) 50%, transparent 100%);
        }

        .hero-main { min-height: 480px; }
        .hero-small { min-height: 232px; }

        .hero-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 26px;
            z-index: 2;
            color: var(--white);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 11px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: var(--white);
        }

        .hero-info .badge { margin-bottom: 12px; }

        .hero-info h2 {
            font-family: var(--serif);
            font-weight: 900;
            line-height: 1.3;
        }

        .hero-main .hero-info h2 { font-size: 26px; }
        .hero-small .hero-info h2 { font-size: 16px; }
        .hero-small .hero-info { padding: 18px; }

        .hero-info .summary {
            font-size: 13.5px;
            opacity: 0.8;
            margin-top: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .hero-meta {
            margin-top: 12px;
            font-size: 11.5px;
            opacity: 0.6;
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }

        .hero-meta i { margin-right: 3px; }

        .hero-side {
            display: grid;
            grid-template-rows: 1fr 1fr;
            gap: 14px;
        }

        .sec-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 22px;
        }

        .sec-head h2 {
            font-family: var(--serif);
            font-size: 20px;
            font-weight: 900;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sec-head h2 .bar {
            width: 4px;
            height: 20px;
            background: var(--red);
            border-radius: 2px;
        }

        .sec-head .line {
            flex: 1;
            height: 1px;
            background: var(--g200);
        }

        .sec-head a {
            color: var(--red);
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .sec-head a:hover { text-decoration: underline; }

        .grid-3, .grid-2 {
            display: grid;
            gap: 18px;
        }

        .grid-3 { grid-template-columns: repeat(3, 1fr); }
        .grid-2 { grid-template-columns: repeat(2, 1fr); }

        .card {
            position: relative;
            border-radius: var(--radius-lg);
            overflow: hidden;
            display: block;
            aspect-ratio: 16/9;
            border: 1px solid var(--g200);
            transition: all 0.25s var(--ease);
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--g300);
        }

        .card img,
        .card .placeholder-img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s var(--ease);
        }

        .card:hover img,
        .card:hover .placeholder-img {
            transform: scale(1.05);
        }

        .card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,.88) 0%, rgba(0,0,0,.2) 50%, transparent 100%);
            z-index: 1;
            transition: background 0.3s;
        }

        .card:hover::after {
            background: linear-gradient(to top, rgba(0,0,0,.92) 0%, rgba(0,0,0,.35) 50%, transparent 100%);
        }

        .card-body {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            z-index: 2;
            color: var(--white);
        }

        .card .badge {
            position: absolute;
            top: 15px;
            left: 15px;
            z-index: 3;
        }

        .card h3 {
            font-family: var(--serif);
            font-size: 18px;
            font-weight: 900;
            line-height: 1.3;
            margin-bottom: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }

        .card .card-meta {
            font-size: 11px;
            opacity: 0.7;
            display: flex;
            gap: 12px;
        }

        .card .card-meta i { margin-right: 3px; }

        .layout {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 32px;
            padding: 28px 0;
        }

        .widget {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 22px;
            border: 1px solid var(--g200);
            margin-bottom: 22px;
        }

        .widget-head {
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--g100);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .widget-head i { color: var(--red); font-size: 14px; }

        .rank-row {
            display: flex;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--g100);
            align-items: flex-start;
        }

        .rank-row:last-child { border-bottom: none; }

        .rank-n {
            width: 28px;
            height: 28px;
            background: var(--g100);
            color: var(--g600);
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 12px;
            flex-shrink: 0;
        }

        .rank-n.top { background: var(--red); color: var(--white); }

        .rank-row h4 {
            font-size: 13px;
            font-weight: 600;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }

        .rank-row h4 a:hover { color: var(--red); }

        .rank-row .sm { font-size: 11px; color: var(--g500); margin-top: 3px; }

        .cat-link {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--g100);
            font-size: 13px;
            font-weight: 600;
            color: var(--g700);
        }

        .cat-link:last-child { border-bottom: none; }
        .cat-link:hover { color: var(--red); }

        .cat-link .cnt {
            font-size: 11px;
            font-weight: 700;
            padding: 2px 9px;
            border-radius: 50px;
            color: var(--white);
        }

        .detail-cover {
            position: relative;
            height: 420px;
            border-radius: var(--radius-lg);
            overflow: hidden;
            margin-bottom: 28px;
        }

        .detail-cover img,
        .detail-cover .placeholder-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .crumbs {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: var(--g500);
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .crumbs a { color: var(--red); font-weight: 500; }
        .crumbs a:hover { text-decoration: underline; }
        .crumbs i { font-size: 8px; }

        .art-head { margin-bottom: 28px; }

        .art-head h1 {
            font-family: var(--serif);
            font-size: 32px;
            font-weight: 900;
            line-height: 1.3;
            margin-bottom: 16px;
            word-break: break-word;
        }

        .art-head .lead {
            font-size: 17px;
            color: var(--g600);
            line-height: 1.7;
            padding-left: 18px;
            border-left: 4px solid var(--red);
            word-break: break-word;
        }

        .art-info {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 14px 0;
            margin: 22px 0;
            border-top: 1px solid var(--g200);
            border-bottom: 1px solid var(--g200);
            font-size: 13px;
            color: var(--g500);
            flex-wrap: wrap;
        }

        .art-info i { color: var(--red); margin-right: 4px; }

        .art-body {
            font-size: 17px;
            line-height: 1.9;
            color: var(--g800);
            word-break: break-word;
        }

        .art-body p { margin-bottom: 18px; }

        .tags {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid var(--g200);
        }

        .tags a {
            background: var(--g100);
            padding: 5px 13px;
            border-radius: 100px;
            font-size: 12px;
            color: var(--g600);
            font-weight: 600;
            border: 1px solid var(--g200);
        }

        .tags a:hover { background: var(--red); color: var(--white); border-color: var(--red); }

        .share-bar {
            display: flex;
            gap: 8px;
            margin: 24px 0;
            padding: 18px 0;
            border-top: 1px solid var(--g200);
            border-bottom: 1px solid var(--g200);
            align-items: center;
            flex-wrap: wrap;
        }

        .share-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 14px;
            transition: transform 0.2s;
        }

        .share-btn:hover { transform: translateY(-2px); }

        .page-head {
            padding: 28px 0;
            background: var(--white);
            border-bottom: 1px solid var(--g200);
            margin-bottom: 24px;
        }

        .page-head h1 {
            font-family: var(--serif);
            font-size: 28px;
            font-weight: 900;
            word-break: break-word;
        }

        .page-head .sub { color: var(--g500); font-size: 14px; margin-top: 5px; }

        .pages {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin: 28px 0;
            flex-wrap: wrap;
        }

        .pages a, .pages .now {
            padding: 9px 15px;
            border-radius: var(--radius);
            font-size: 13px;
            font-weight: 600;
        }

        .pages a {
            background: var(--white);
            color: var(--g700);
            border: 1px solid var(--g200);
        }

        .pages a:hover { background: var(--red); color: var(--white); border-color: var(--red); }
        .pages .now { background: var(--red); color: var(--white); }

        .placeholder-img {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .placeholder-content {
            text-align: center;
            color: rgba(255,255,255,.1);
        }

        .placeholder-content i { font-size: 24px; display: block; margin-bottom: 2px; }
        .placeholder-content span { font-family: var(--serif); font-size: 38px; font-weight: 900; }

        .hero-card .placeholder-content i { font-size: 32px; }
        .hero-card .placeholder-content span { font-size: 60px; }
        .card .placeholder-content i { font-size: 24px; }
        .card .placeholder-content span { font-size: 38px; }
        .detail-cover .placeholder-content i { font-size: 40px; }
        .detail-cover .placeholder-content span { font-size: 80px; }

        .empty {
            text-align: center;
            padding: 56px 20px;
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--g200);
        }

        .empty i { font-size: 44px; color: var(--g300); margin-bottom: 14px; display: block; }
        .empty h3 { color: var(--g600); font-size: 17px; }
        .empty p { color: var(--g500); margin-top: 6px; font-size: 14px; }

        .btn-red {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 11px 22px;
            background: var(--red);
            color: var(--white);
            border-radius: var(--radius);
            font-size: 14px;
            font-weight: 600;
            font-family: var(--font);
            border: none;
            cursor: pointer;
        }

        .btn-red:hover { background: var(--red-dark); }

        .footer {
            background: var(--dark);
            color: var(--g400);
            padding: 48px 0 0;
            margin-top: 40px;
            width: 100%;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1fr;
            gap: 40px;
            padding-bottom: 40px;
        }

        .footer h3 {
            color: var(--white);
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--red);
            display: inline-block;
        }

        .footer p { font-size: 13px; line-height: 1.7; }
        .footer ul li { padding: 4px 0; }
        .footer ul li a { font-size: 13px; color: var(--g400); }
        .footer ul li a:hover { color: var(--red); }

        .footer-socials {
            display: flex;
            gap: 7px;
            margin-top: 14px;
        }

        .footer-socials a {
            width: 34px;
            height: 34px;
            background: rgba(255,255,255,.07);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--g500);
            font-size: 13px;
        }

        .footer-socials a:hover { background: var(--red); color: var(--white); }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,.07);
            padding: 18px 0;
            text-align: center;
            font-size: 12px;
            color: var(--g600);
        }

        @media (max-width: 1100px) {
            .hero-grid { grid-template-columns: 1fr; }
            .hero-main { min-height: 340px; }
            .hero-side { grid-template-columns: 1fr 1fr; grid-template-rows: auto; }
            .hero-small { min-height: 220px; }
            .layout { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 768px) {
            .topbar { display: none; }
            .search-form { display: none; }
            .hamburger { display: block; }
            .nav { display: none; }

            .hero-main { min-height: 280px; }
            .hero-main .hero-info h2 { font-size: 20px; }
            .hero-side { grid-template-columns: 1fr; }
            .hero-small { min-height: 200px; }

            .grid-3 { grid-template-columns: 1fr; }
            .grid-2 { grid-template-columns: 1fr; }

            .layout { grid-template-columns: 1fr; }

            .grid-3, .grid-2 {
                display: flex;
                flex-direction: column;
                gap: 16px;
            }
            
            .card {
                aspect-ratio: 16/9;
                width: 100%;
                margin: 0;
                border-radius: 12px;
            }
            
            .card-body {
                padding: 16px;
            }
            
            .card h3 {
                font-size: 16px;
            }
            
            .ticker-tag {
                padding: 9px 12px;
                font-size: 9px;
            }
            
            .ticker-item {
                font-size: 11px;
            }
            
            .cat-link {
                flex-wrap: wrap;
                gap: 5px;
            }
            
            .cat-link .cnt {
                white-space: nowrap;
            }
            
            .rank-row {
                flex-wrap: wrap;
                gap: 8px;
            }
            
            .rank-row h4 {
                font-size: 12px;
                word-break: break-word;
            }
            
            .widget {
                padding: 16px;
            }
            
            .widget-head {
                font-size: 14px;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 28px;
            }

            .art-head h1 { font-size: 24px; }
            .art-head .lead { font-size: 15px; }
            .detail-cover { height: 240px; }
            .page-head h1 { font-size: 22px; }
        }

        @media (max-width: 480px) {
            .wrap { padding: 0 14px; }
            .hero-main { min-height: 240px; }
            .hero-main .hero-info h2 { font-size: 18px; }
            .hero-info { padding: 16px !important; }
            .hero-small .hero-info h2 { font-size: 15px; }
            .detail-cover { height: 200px; border-radius: var(--radius); }
            .art-head h1 { font-size: 21px; }
            
            .card h3 {
                font-size: 15px;
            }
        }
    </style>
</head>
<body>

<div class="mobile-nav-overlay" id="navOverlay" onclick="closeMobileNav()"></div>
<div class="mobile-nav" id="mobileNav">
    <div class="mobile-nav-header">
        <h3>Menu</h3>
        <button class="mobile-nav-close" onclick="closeMobileNav()"><i class="fas fa-times"></i></button>
    </div>
    <div class="mobile-search">
        <form action="" method="get">
            <input type="hidden" name="page" value="search">
            <input type="text" name="q" placeholder="Haberlerde ara...">
        </form>
    </div>
    <div class="mobile-nav-items">
        <a href="?page=home" class="<?php echo $page === 'home' ? 'on' : ''; ?>"><i class="fas fa-home"></i> Anasayfa</a>
        <?php foreach ($categories as $c): ?>
            <a href="?page=category&slug=<?php echo $c['slug']; ?>"
               class="<?php echo ($page === 'category' && isset($_GET['slug']) && $_GET['slug'] === $c['slug']) ? 'on' : ''; ?>">
                <i class="fas <?php echo $c['icon']; ?>" style="color:<?php echo htmlspecialchars($c['color'] ?? '#dc2626', ENT_QUOTES, 'UTF-8'); ?>"></i>
                <?php echo htmlspecialchars($c['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="topbar">
    <div class="wrap">
        <div class="topbar-l">
            <span><i class="far fa-calendar-alt"></i> <?php echo function_exists('turkishDate') ? turkishDate() : date('d.m.Y'); ?></span>
            <span><i class="far fa-clock"></i> <span id="clock"></span></span>
        </div>
        <div class="topbar-r">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-youtube"></i></a>
        </div>
    </div>
</div>

<header class="header">
    <div class="wrap">
        <div class="header-inner">
            <a href="?page=home" class="logo">
                <div class="logo-mark">H</div>
                <div>
                    <div class="logo-text">Haber<em>Portal</em></div>
                    <div class="logo-sub">Son Dakika Haberler</div>
                </div>
            </a>

            <form action="" method="get" class="search-form">
                <input type="hidden" name="page" value="search">
                <input type="text" name="q" placeholder="Haberlerde ara..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>

            <button class="hamburger" onclick="openMobileNav()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</header>

<nav class="nav">
    <div class="wrap">
        <div class="nav-list">
            <a href="?page=home" class="<?php echo $page === 'home' ? 'on' : ''; ?>">
                <i class="fas fa-home nav-icon"></i> Anasayfa
            </a>
            <?php foreach ($categories as $c): ?>
                <a href="?page=category&slug=<?php echo $c['slug']; ?>"
                   class="<?php echo ($page === 'category' && isset($_GET['slug']) && $_GET['slug'] === $c['slug']) ? 'on' : ''; ?>">
                    <?php echo htmlspecialchars($c['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</nav>

<?php
$breakingNews = function_exists('getNews') ? getNews(array('is_breaking' => 1, 'limit' => 10)) : array();
if (!empty($breakingNews)):
?>
<div class="ticker">
    <div class="ticker-flex">
        <div class="ticker-tag"><div class="ticker-dot"></div> SON DAKIKA</div>
        <div class="ticker-scroll">
            <div class="ticker-track">
                <?php foreach ($breakingNews as $bn): ?>
                    <div class="ticker-item"><a href="?page=detail&id=<?php echo $bn['id']; ?>"><?php echo htmlspecialchars($bn['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a></div>
                <?php endforeach; ?>
                <?php foreach ($breakingNews as $bn): ?>
                    <div class="ticker-item"><a href="?page=detail&id=<?php echo $bn['id']; ?>"><?php echo htmlspecialchars($bn['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<main>
<?php if ($page === 'home'):
    $headlines = function_exists('getNews') ? getNews(array('is_headline' => 1, 'limit' => 3)) : array();
    if (count($headlines) < 3) $headlines = function_exists('getNews') ? getNews(array('limit' => 3)) : array();
?>
    <?php if (!empty($headlines)): ?>
    <section class="hero">
        <div class="wrap">
            <div class="hero-grid">
                <?php if (isset($headlines[0])): $h = $headlines[0]; ?>
                <a href="?page=detail&id=<?php echo $h['id']; ?>" class="hero-card hero-main">
                    <?php echo function_exists('newsImage') ? newsImage($h) : '<div class="placeholder-img"><div class="placeholder-content"><i class="fas fa-newspaper"></i><span>H</span></div></div>'; ?>
                    <div class="hero-info">
                        <?php if (!empty($h['cat_name'])): ?>
                            <span class="badge" style="background:<?php echo htmlspecialchars($h['cat_color'] ?? '#dc2626', ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="fas <?php echo $h['cat_icon'] ?? 'fa-tag'; ?>"></i> <?php echo htmlspecialchars($h['cat_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        <?php endif; ?>
                        <h2><?php echo htmlspecialchars($h['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h2>
                        <div class="summary"><?php echo htmlspecialchars($h['summary'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="hero-meta">
                            <span><i class="far fa-clock"></i> <?php echo function_exists('timeAgo') ? timeAgo($h['published_at']) : ($h['published_at'] ?? ''); ?></span>
                            <span><i class="far fa-eye"></i> <?php echo function_exists('formatViews') ? formatViews($h['views'] ?? 0) : ($h['views'] ?? 0); ?></span>
                            <span><i class="far fa-user"></i> <?php echo htmlspecialchars($h['author'] ?? 'Editor', ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>
                </a>
                <?php endif; ?>

                <div class="hero-side">
                    <?php for ($i = 1; $i <= 2; $i++): if (isset($headlines[$i])): $h = $headlines[$i]; ?>
                    <a href="?page=detail&id=<?php echo $h['id']; ?>" class="hero-card hero-small">
                        <?php echo function_exists('newsImage') ? newsImage($h) : '<div class="placeholder-img"><div class="placeholder-content"><i class="fas fa-newspaper"></i><span>H</span></div></div>'; ?>
                        <div class="hero-info">
                            <?php if (!empty($h['cat_name'])): ?>
                                <span class="badge" style="background:<?php echo htmlspecialchars($h['cat_color'] ?? '#dc2626', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($h['cat_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <h2><?php echo htmlspecialchars($h['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h2>
                            <div class="hero-meta">
                                <span><i class="far fa-clock"></i> <?php echo function_exists('timeAgo') ? timeAgo($h['published_at']) : ($h['published_at'] ?? ''); ?></span>
                                <span><i class="far fa-eye"></i> <?php echo function_exists('formatViews') ? formatViews($h['views'] ?? 0) : ($h['views'] ?? 0); ?></span>
                            </div>
                        </div>
                    </a>
                    <?php endif; endfor; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <div class="wrap">
        <div class="layout">
            <div>
                <div class="sec-head">
                    <h2><span class="bar"></span> Son Haberler</h2>
                    <div class="line"></div>
                </div>
                <div class="grid-3">
                    <?php $latest = function_exists('getNews') ? getNews(array('limit' => 6)) : array(); foreach ($latest as $n): ?>
                    <a href="?page=detail&id=<?php echo $n['id']; ?>" class="card">
                        <?php echo function_exists('newsImage') ? newsImage($n) : '<div class="placeholder-img"><div class="placeholder-content"><i class="fas fa-newspaper"></i><span>H</span></div></div>'; ?>
                        <?php if (!empty($n['cat_name'])): ?>
                            <span class="badge" style="background:<?php echo htmlspecialchars($n['cat_color'] ?? '#dc2626', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($n['cat_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($n['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="card-meta">
                                <span><i class="far fa-clock"></i> <?php echo function_exists('timeAgo') ? timeAgo($n['published_at']) : ($n['published_at'] ?? ''); ?></span>
                                <span><i class="far fa-eye"></i> <?php echo function_exists('formatViews') ? formatViews($n['views'] ?? 0) : ($n['views'] ?? 0); ?></span>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <?php foreach (array_slice($categories, 0, 4) as $cat):
                    $catItems = function_exists('getNews') ? getNews(array('category_id' => $cat['id'], 'limit' => 2)) : array();
                    if (empty($catItems)) continue;
                ?>
                <div style="margin-top:44px">
                    <div class="sec-head">
                        <h2>
                            <span class="bar"></span>
                            <i class="fas <?php echo $cat['icon']; ?>" style="color:<?php echo htmlspecialchars($cat['color'] ?? '#dc2626', ENT_QUOTES, 'UTF-8'); ?>; font-size:16px"></i>
                            <?php echo htmlspecialchars($cat['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </h2>
                        <div class="line"></div>
                        <a href="?page=category&slug=<?php echo $cat['slug']; ?>">Tumu <i class="fas fa-chevron-right" style="font-size:9px"></i></a>
                    </div>
                    <div class="grid-2">
                        <?php foreach ($catItems as $n): ?>
                        <a href="?page=detail&id=<?php echo $n['id']; ?>" class="card">
                            <?php echo function_exists('newsImage') ? newsImage($n) : '<div class="placeholder-img"><div class="placeholder-content"><i class="fas fa-newspaper"></i><span>H</span></div></div>'; ?>
                            <?php if (!empty($n['cat_name'])): ?>
                                <span class="badge" style="background:<?php echo htmlspecialchars($n['cat_color'] ?? '#dc2626', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($n['cat_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <div class="card-body">
                                <h3><?php echo htmlspecialchars($n['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                                <div class="card-meta">
                                    <span><i class="far fa-clock"></i> <?php echo function_exists('timeAgo') ? timeAgo($n['published_at']) : ($n['published_at'] ?? ''); ?></span>
                                    <span><i class="far fa-eye"></i> <?php echo function_exists('formatViews') ? formatViews($n['views'] ?? 0) : ($n['views'] ?? 0); ?></span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <aside>
                <div class="widget">
                    <div class="widget-head"><i class="fas fa-fire-alt"></i> En Cok Okunanlar</div>
                    <?php $popular = function_exists('getNews') ? getNews(array('limit' => 8, 'orderBy' => 'n.views DESC')) : array();
                    foreach ($popular as $i => $p): ?>
                        <div class="rank-row">
                            <div class="rank-n <?php echo $i < 3 ? 'top' : ''; ?>"><?php echo $i + 1; ?></div>
                            <div>
                                <h4><a href="?page=detail&id=<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a></h4>
                                <div class="sm"><i class="far fa-eye"></i> <?php echo function_exists('formatViews') ? formatViews($p['views'] ?? 0) : ($p['views'] ?? 0); ?> &middot; <?php echo function_exists('timeAgo') ? timeAgo($p['published_at']) : ($p['published_at'] ?? ''); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="widget">
                    <div class="widget-head"><i class="fas fa-th-large"></i> Kategoriler</div>
                    <?php foreach ($categories as $c):
                        $cn = isset($db) ? $db->query("SELECT COUNT(*) as c FROM news WHERE category_id=" . intval($c['id'] ?? 0) . " AND status='published'")->fetch()['c'] : 0;
                    ?>
                        <a href="?page=category&slug=<?php echo $c['slug']; ?>" class="cat-link">
                            <span><i class="fas <?php echo $c['icon']; ?>" style="color:<?php echo htmlspecialchars($c['color'] ?? '#dc2626', ENT_QUOTES, 'UTF-8'); ?>; margin-right:7px; font-size:12px"></i><?php echo htmlspecialchars($c['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="cnt" style="background:<?php echo htmlspecialchars($c['color'] ?? '#dc2626', ENT_QUOTES, 'UTF-8'); ?>"><?php echo $cn; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </aside>
        </div>
    </div>

<?php elseif ($page === 'category'):
    $catSlug = isset($_GET['slug']) ? $_GET['slug'] : '';
    $curCat = null;
    foreach ($categories as $c) {
        if ($c['slug'] === $catSlug) $curCat = $c;
    }

    if (!$curCat):
?>
    <div class="wrap" style="padding:60px 20px; text-align:center">
        <h2>Kategori bulunamadi</h2>
        <a href="?page=home" class="btn-red" style="margin-top:16px; display:inline-flex">Anasayfa</a>
    </div>
<?php else:
    $pg = max(1, intval(isset($_GET['p']) ? $_GET['p'] : 1));
    $pp = 12;
    $ofs = ($pg - 1) * $pp;
    $items = function_exists('getNews') ? getNews(array('category_id' => $curCat['id'], 'limit' => $pp, 'offset' => $ofs)) : array();
    $total = function_exists('countNews') ? countNews(array('category_id' => $curCat['id'])) : 0;
    $tp = ceil($total / max($pp, 1));
?>
    <div class="page-head">
        <div class="wrap">
            <div class="crumbs">
                <a href="?page=home">Anasayfa</a>
                <i class="fas fa-chevron-right"></i>
                <span><?php echo htmlspecialchars($curCat['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <h1 style="color:<?php echo htmlspecialchars($curCat['color'] ?? '#dc2626', ENT_QUOTES, 'UTF-8'); ?>">
                <i class="fas <?php echo $curCat['icon']; ?>" style="margin-right:8px"></i><?php echo htmlspecialchars($curCat['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </h1>
            <div class="sub"><?php echo $total; ?> haber bulundu</div>
        </div>
    </div>
    <div class="wrap">
        <div class="layout">
            <div>
                <?php if (empty($items)): ?>
                    <div class="empty"><i class="fas fa-inbox"></i><h3>Haber bulunamadi</h3></div>
                <?php else: ?>
                    <div class="grid-3">
                        <?php foreach ($items as $n): ?>
                        <a href="?page=detail&id=<?php echo $n['id']; ?>" class="card">
                            <?php echo function_exists('newsImage') ? newsImage($n) : '<div class="placeholder-img"><div class="placeholder-content"><i class="fas fa-newspaper"></i><span>H</span></div></div>'; ?>
                            <?php if (!empty($n['cat_name'])): ?>
                                <span class="badge" style="background:<?php echo htmlspecialchars($n['cat_color'] ?? '#dc2626', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($n['cat_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <div class="card-body">
                                <h3><?php echo htmlspecialchars($n['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                                <div class="card-meta">
                                    <span><i class="far fa-clock"></i> <?php echo function_exists('timeAgo') ? timeAgo($n['published_at']) : ($n['published_at'] ?? ''); ?></span>
                                    <span><i class="far fa-eye"></i> <?php echo function_exists('formatViews') ? formatViews($n['views'] ?? 0) : ($n['views'] ?? 0); ?></span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($tp > 1): ?>
                    <div class="pages">
                        <?php for ($i = 1; $i <= $tp; $i++): ?>
                            <?php if ($i == $pg): ?><span class="now"><?php echo $i; ?></span>
                            <?php else: ?><a href="?page=category&slug=<?php echo $catSlug; ?>&p=<?php echo $i; ?>"><?php echo $i; ?></a><?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <aside>
                <div class="widget">
                    <div class="widget-head"><i class="fas fa-fire-alt"></i> Populer</div>
                    <?php $cpop = function_exists('getNews') ? getNews(array('category_id' => $curCat['id'], 'limit' => 5, 'orderBy' => 'n.views DESC')) : array();
                    foreach ($cpop as $i => $p): ?>
                        <div class="rank-row">
                            <div class="rank-n <?php echo $i < 3 ? 'top' : ''; ?>"><?php echo $i + 1; ?></div>
                            <div><h4><a href="?page=detail&id=<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a></h4><div class="sm"><?php echo function_exists('timeAgo') ? timeAgo($p['published_at']) : ($p['published_at'] ?? ''); ?></div></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </aside>
        </div>
    </div>
<?php endif; ?>

<?php elseif ($page === 'detail'):
    if (!isset($detailNews) || !$detailNews):
?>
    <div class="wrap" style="padding:60px 20px; text-align:center">
        <h2>Haber bulunamadi</h2>
        <a href="?page=home" class="btn-red" style="margin-top:16px; display:inline-flex">Anasayfa</a>
    </div>
<?php else:
    $related = function_exists('getNews') ? getNews(array('category_id' => $detailNews['category_id'], 'exclude_id' => $detailNews['id'], 'limit' => 4)) : array();
?>
    <div class="wrap">
        <div class="layout">
            <div>
                <div class="crumbs" style="margin-top:22px">
                    <a href="?page=home">Anasayfa</a>
                    <i class="fas fa-chevron-right"></i>
                    <?php if (!empty($detailNews['cat_name'])): ?>
                        <a href="?page=category&slug=<?php echo $detailNews['cat_slug']; ?>"><?php echo htmlspecialchars($detailNews['cat_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a>
                        <i class="fas fa-chevron-right"></i>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars(mb_substr($detailNews['title'] ?? '', 0, 40, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>...</span>
                </div>

                <div class="detail-cover"><?php echo function_exists('newsImage') ? newsImage($detailNews) : '<div class="placeholder-img"><div class="placeholder-content"><i class="fas fa-newspaper"></i><span>H</span></div></div>'; ?></div>

                <article>
                    <div class="art-head">
                        <?php if (!empty($detailNews['cat_name'])): ?>
                            <span class="badge" style="background:<?php echo htmlspecialchars($detailNews['cat_color'] ?? '#dc2626', ENT_QUOTES, 'UTF-8'); ?>; margin-bottom:14px; display:inline-flex; padding:5px 14px; font-size:12px">
                                <i class="fas <?php echo $detailNews['cat_icon']; ?>"></i> <?php echo htmlspecialchars($detailNews['cat_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        <?php endif; ?>
                        <h1><?php echo htmlspecialchars($detailNews['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
                        <?php if (!empty($detailNews['summary'])): ?>
                            <p class="lead"><?php echo htmlspecialchars($detailNews['summary'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                        <div class="art-info">
                            <span><i class="far fa-user"></i> <?php echo htmlspecialchars($detailNews['author'] ?? 'Editor', ENT_QUOTES, 'UTF-8'); ?></span>
                            <span><i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y H:i', strtotime($detailNews['published_at'] ?? '')); ?></span>
                            <span><i class="far fa-clock"></i> <?php echo function_exists('timeAgo') ? timeAgo($detailNews['published_at']) : ($detailNews['published_at'] ?? ''); ?></span>
                            <span><i class="far fa-eye"></i> <?php echo function_exists('formatViews') ? formatViews($detailNews['views'] ?? 0) : ($detailNews['views'] ?? 0); ?></span>
                        </div>
                    </div>
                    <div class="art-body"><?php echo $detailNews['content'] ?? ''; ?></div>

                    <?php if (!empty($detailNews['source'])): ?>
                        <p style="margin-top:20px; font-size:13px; color:var(--g500)"><strong>Kaynak:</strong> <?php echo htmlspecialchars($detailNews['source'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($detailNews['tags'])): ?>
                    <div class="tags">
                        <i class="fas fa-tags" style="color:var(--g400); margin-right:4px; line-height:2"></i>
                        <?php foreach (explode(',', $detailNews['tags'] ?? '') as $tag):
                            $tag = trim($tag); if ($tag): ?>
                            <a href="?page=search&q=<?php echo urlencode($tag); ?>">#<?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php endif; endforeach; ?>
                    </div>
                    <?php endif; ?>
                </article>

                <div class="share-bar">
                    <span style="font-size:13px; font-weight:700; margin-right:6px"><i class="fas fa-share-alt" style="color:var(--red); margin-right:4px"></i> Paylas</span>
                    <a href="#" class="share-btn" style="background:#1877f2"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="share-btn" style="background:#1da1f2"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="share-btn" style="background:#25d366"><i class="fab fa-whatsapp"></i></a>
                    <a href="#" class="share-btn" style="background:#0a66c2"><i class="fab fa-linkedin-in"></i></a>
                </div>

                <?php if (!empty($related)): ?>
                <div style="margin-top:36px">
                    <div class="sec-head"><h2><span class="bar"></span> Ilgili Haberler</h2><div class="line"></div></div>
                    <div class="grid-2">
                        <?php foreach ($related as $r): ?>
                        <a href="?page=detail&id=<?php echo $r['id']; ?>" class="card">
                            <?php echo function_exists('newsImage') ? newsImage($r) : '<div class="placeholder-img"><div class="placeholder-content"><i class="fas fa-newspaper"></i><span>H</span></div></div>'; ?>
                            <?php if (!empty($r['cat_name'])): ?>
                                <span class="badge" style="background:<?php echo htmlspecialchars($r['cat_color'] ?? '#dc2626', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($r['cat_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <div class="card-body">
                                <h3><?php echo htmlspecialchars($r['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                                <div class="card-meta">
                                    <span><i class="far fa-clock"></i> <?php echo function_exists('timeAgo') ? timeAgo($r['published_at']) : ($r['published_at'] ?? ''); ?></span>
                                    <span><i class="far fa-eye"></i> <?php echo function_exists('formatViews') ? formatViews($r['views'] ?? 0) : ($r['views'] ?? 0); ?></span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <aside>
                <div class="widget">
                    <div class="widget-head"><i class="fas fa-fire-alt"></i> En Cok Okunanlar</div>
                    <?php $pop = function_exists('getNews') ? getNews(array('limit' => 6, 'orderBy' => 'n.views DESC')) : array();
                    foreach ($pop as $i => $p): ?>
                        <div class="rank-row">
                            <div class="rank-n <?php echo $i < 3 ? 'top' : ''; ?>"><?php echo $i + 1; ?></div>
                            <div><h4><a href="?page=detail&id=<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a></h4><div class="sm"><?php echo function_exists('timeAgo') ? timeAgo($p['published_at']) : ($p['published_at'] ?? ''); ?></div></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </aside>
        </div>
    </div>
<?php endif; ?>

<?php elseif ($page === 'search'):
    $q = trim(isset($_GET['q']) ? $_GET['q'] : '');
    $pg = max(1, intval(isset($_GET['p']) ? $_GET['p'] : 1));
    $pp = 12;
    $ofs = ($pg - 1) * $pp;
    $results = ($q && function_exists('getNews')) ? getNews(array('search' => $q, 'limit' => $pp, 'offset' => $ofs)) : array();
    $total = ($q && function_exists('countNews')) ? countNews(array('search' => $q)) : 0;
    $tp = ceil($total / max($pp, 1));
?>
    <div class="page-head">
        <div class="wrap">
            <div class="crumbs">
                <a href="?page=home">Anasayfa</a>
                <i class="fas fa-chevron-right"></i>
                <span>Arama</span>
            </div>
            <h1><i class="fas fa-search" style="color:var(--red); margin-right:8px"></i>"<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>"</h1>
            <div class="sub"><?php echo $total; ?> sonuc</div>
        </div>
    </div>
    <div class="wrap">
        <div class="layout">
            <div>
                <?php if (empty($results)): ?>
                    <div class="empty"><i class="fas fa-search"></i><h3>Sonuc bulunamadi</h3><p>Farkli kelimeler deneyin.</p></div>
                <?php else: ?>
                    <div class="grid-3">
                        <?php foreach ($results as $n): ?>
                        <a href="?page=detail&id=<?php echo $n['id']; ?>" class="card">
                            <?php echo function_exists('newsImage') ? newsImage($n) : '<div class="placeholder-img"><div class="placeholder-content"><i class="fas fa-newspaper"></i><span>H</span></div></div>'; ?>
                            <?php if (!empty($n['cat_name'])): ?>
                                <span class="badge" style="background:<?php echo htmlspecialchars($n['cat_color'] ?? '#dc2626', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($n['cat_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <div class="card-body">
                                <h3><?php echo htmlspecialchars($n['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                                <div class="card-meta">
                                    <span><i class="far fa-clock"></i> <?php echo function_exists('timeAgo') ? timeAgo($n['published_at']) : ($n['published_at'] ?? ''); ?></span>
                                    <span><i class="far fa-eye"></i> <?php echo function_exists('formatViews') ? formatViews($n['views'] ?? 0) : ($n['views'] ?? 0); ?></span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($tp > 1): ?>
                    <div class="pages">
                        <?php for ($i = 1; $i <= $tp; $i++): ?>
                            <?php if ($i == $pg): ?><span class="now"><?php echo $i; ?></span>
                            <?php else: ?><a href="?page=search&q=<?php echo urlencode($q); ?>&p=<?php echo $i; ?>"><?php echo $i; ?></a><?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <aside>
                <div class="widget">
                    <div class="widget-head"><i class="fas fa-fire-alt"></i> Populer</div>
                    <?php $pop = function_exists('getNews') ? getNews(array('limit' => 6, 'orderBy' => 'n.views DESC')) : array();
                    foreach ($pop as $i => $p): ?>
                        <div class="rank-row">
                            <div class="rank-n <?php echo $i < 3 ? 'top' : ''; ?>"><?php echo $i + 1; ?></div>
                            <div><h4><a href="?page=detail&id=<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a></h4><div class="sm"><?php echo function_exists('timeAgo') ? timeAgo($p['published_at']) : ($p['published_at'] ?? ''); ?></div></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </aside>
        </div>
    </div>
<?php endif; ?>
</main>

<footer class="footer">
    <div class="wrap">
        <div class="footer-grid">
            <div>
                <div class="logo" style="margin-bottom:14px">
                    <div class="logo-mark" style="width:38px; height:38px; font-size:18px">H</div>
                    <div class="logo-text" style="font-size:20px; color:var(--white)">Haber<em>Portal</em></div>
                </div>
                <p>Turkiyenin en guncel haber portali. 7/24 son dakika haberleri, ekonomi, spor, teknoloji haberleri.</p>
                <div class="footer-socials">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                    <a href="#"><i class="fab fa-telegram-plane"></i></a>
                </div>
            </div>
            <div>
                <h3>Kategoriler</h3>
                <ul>
                    <?php foreach (array_slice($categories, 0, 6) as $c): ?>
                        <li><a href="?page=category&slug=<?php echo $c['slug']; ?>"><i class="fas <?php echo $c['icon']; ?>" style="width:14px; color:<?php echo htmlspecialchars($c['color'] ?? '#dc2626', ENT_QUOTES, 'UTF-8'); ?>; margin-right:6px; font-size:10px"></i><?php echo htmlspecialchars($c['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h3>Kurumsal</h3>
                <ul>
                    <li><a href="#">Hakkimizda</a></li>
                    <li><a href="#">Iletisim</a></li>
                    <li><a href="#">Reklam</a></li>
                    <li><a href="#">Kunye</a></li>
                    <li><a href="#">Gizlilik Politikasi</a></li>
                </ul>
            </div>
            <div>
                <h3>Iletisim</h3>
                <ul>
                    <li style="padding:6px 0"><i class="fas fa-map-marker-alt" style="color:var(--red); margin-right:6px; width:14px"></i> Istanbul, Turkiye</li>
                    <li style="padding:6px 0"><i class="fas fa-phone" style="color:var(--red); margin-right:6px; width:14px"></i> +90 (212) 555 00 00</li>
                    <li style="padding:6px 0"><i class="fas fa-envelope" style="color:var(--red); margin-right:6px; width:14px"></i> info@haberportal.com</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">&copy; <?php echo date('Y'); ?> HaberPortal - Tum haklari saklidir.</div>
    </div>
</footer>

<script>
function tick() {
    var d = new Date();
    var el = document.getElementById('clock');
    if (el) {
        el.textContent =
            String(d.getHours()).padStart(2, '0') + ':' +
            String(d.getMinutes()).padStart(2, '0') + ':' +
            String(d.getSeconds()).padStart(2, '0');
    }
}
setInterval(tick, 1000);
tick();

function openMobileNav() {
    document.getElementById('mobileNav').classList.add('show');
    document.getElementById('navOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeMobileNav() {
    document.getElementById('mobileNav').classList.remove('show');
    document.getElementById('navOverlay').classList.remove('show');
    document.body.style.overflow = '';
}
</script>
</body>
</html>