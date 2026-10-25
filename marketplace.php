<?php
// marketplace.php — Oweili · Marketplace (live, database-driven)
require_once 'config.php';
$ART_TYPES = require __DIR__ . '/art_types.php';

$lang = isset($_GET['lang']) && $_GET['lang'] === 'ar' ? 'ar' : 'en';
$ar   = $lang === 'ar';

/* ---- Pagination ---- */
$perPage = 12;
$page    = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset  = ($page - 1) * $perPage;

/* ---- Count (for Prev/Next) — PDO prepared ---- */
$countStmt = getDB()->prepare(
    "SELECT COUNT(*) FROM artworks
     WHERE is_approved = 1 AND status IN ('available','auction')"
);
$countStmt->execute();
$total      = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

/* ---- Fetch this page of artworks, joined with the artist — PDO prepared ---- */
$stmt = getDB()->prepare(
    "SELECT a.id, a.title_en, a.title_ar, a.description_en, a.description_ar,
            a.price, a.type, a.status, a.image_url, a.artist_id,
            u.full_name_en AS artist_en, u.full_name_ar AS artist_ar
     FROM artworks a
     JOIN users u ON u.id = a.artist_id
     WHERE a.is_approved = 1 AND a.status IN ('available','auction')
     ORDER BY a.created_at DESC
     LIMIT :lim OFFSET :off"
);
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$artworks = $stmt->fetchAll();

/* Data for the details modal (escaped on output via JSON flags) */
$modalData = [];
foreach ($artworks as $aw) {
    $modalData[(int) $aw['id']] = [
        'title_en'  => $aw['title_en'],
        'title_ar'  => $aw['title_ar'],
        'desc_en'   => $aw['description_en'] ?? '',
        'desc_ar'   => $aw['description_ar'] ?? '',
        'artist_en' => $aw['artist_en'],
        'artist_ar' => $aw['artist_ar'],
        'artist_id' => (int) $aw['artist_id'],
        'price'     => (float) $aw['price'],
        'image'     => $aw['image_url'],
        'status'    => $aw['status'],
    ];
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $ar ? 'rtl' : 'ltr' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Oweili Marketplace</title>
  <script>
  /* Honor the site-wide saved language. This page switches language via a
     server reload (?lang=), so if the visitor previously chose Arabic on
     another page, redirect to the Arabic version before rendering. */
  (function(){
    var KEY='lang';
    var params=new URLSearchParams(location.search);
    var urlLang=params.get('lang');
    var saved=null; try{ saved=localStorage.getItem(KEY); }catch(e){}
    if(urlLang){ try{ localStorage.setItem(KEY, urlLang==='ar'?'ar':'en'); }catch(e){} }
    else if(saved==='ar'){ params.set('lang','ar'); location.replace(location.pathname+'?'+params.toString()); }
  })();
  </script>
  <link rel="stylesheet" href="/assets/css/oweili.css">
  <style>
    *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
    body, html { width:100%; min-height:100vh; background:#fff; font-family:"Helvetica Neue",Helvetica,Arial,sans-serif; overflow-x:hidden; }
    .bg-wrap { position: fixed; inset: 0; z-index: 0; overflow:hidden; }
    .bg-video { position:absolute; inset:0; width: 100%; height: 100%; object-fit: cover; }

    /* ── TOP NAV (shared brand navbar) ── */
    .topnav{position:fixed;top:0;left:0;right:0;z-index:1000;display:flex;align-items:center;justify-content:space-between;padding:0 16px;height:56px;background:rgba(255,255,255,0.6);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-bottom:1px solid rgba(0,0,0,0.05);}
    [dir="rtl"] .nav-center{flex-direction:row-reverse;}
    [dir="rtl"] .dropdown{left:auto;right:0;text-align:right;}
    [dir="rtl"] .dropdown a{flex-direction:row-reverse;}
    .nav-logo{display:flex;align-items:center;gap:6px;text-decoration:none;color:#111111;flex-shrink:0;}
    .nav-logo svg{color:#ff0055;}
    .nav-logo span{font-size:12px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;}
    .nav-center{display:flex;align-items:stretch;gap:0;height:100%;}
    .nav-item{position:relative;display:flex;align-items:center;}
    .nav-item > a{display:flex;align-items:center;gap:4px;padding:0 12px;height:100%;font-size:10px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:rgba(0,0,0,0.65);text-decoration:none;transition:color 0.2s;white-space:nowrap;border-bottom:2px solid transparent;}
    .nav-item > a:hover{color:#000;}
    .nav-item:hover > a{color:#0055ff;border-bottom-color:#0055ff;}
    .nav-item > a .chevron{width:10px;height:10px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;transition:transform 0.2s;flex-shrink:0;}
    .nav-item:hover > a .chevron{transform:rotate(180deg);}
    .dropdown{position:absolute;top:100%;left:0;min-width:200px;background:rgba(255,255,255,0.96);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(0,0,0,0.07);border-radius:14px;box-shadow:0 16px 40px rgba(0,0,0,0.1);padding:8px;opacity:0;visibility:hidden;transform:translateY(8px);transition:opacity 0.2s,transform 0.2s,visibility 0.2s;pointer-events:none;}
    .nav-item:hover .dropdown{opacity:1;visibility:visible;transform:translateY(0);pointer-events:auto;}
    .dropdown a{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:9px;font-size:12px;color:rgba(0,0,0,0.7);text-decoration:none;transition:background 0.15s,color 0.15s;}
    .dropdown a:hover{background:rgba(0,100,255,0.07);color:#0055ff;}
    .dropdown a .d-icon{width:14px;height:14px;fill:currentColor;opacity:0.6;flex-shrink:0;}
    .nav-right{display:flex;align-items:center;gap:6px;flex-shrink:0;}
    .nav-btn{padding:5px 12px;border-radius:999px;font-size:10px;font-weight:600;letter-spacing:0.04em;text-transform:uppercase;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;transition:all 0.22s;border:1px solid rgba(0,0,0,0.1);background:rgba(0,0,0,0.04);color:rgba(0,0,0,0.8);}
    .nav-btn:hover{background:rgba(0,0,0,0.08);color:#000;}
    .nav-btn.primary{background:rgba(0,100,255,0.1);border-color:rgba(0,100,255,0.2);color:#0066ff;}
    .nav-btn.primary:hover{background:rgba(0,100,255,0.18);}
    .lang-btn{padding:4px 10px;border-radius:999px;font-size:10px;font-weight:600;letter-spacing:0.04em;cursor:pointer;border:1px solid rgba(0,0,0,0.1);background:rgba(255,255,255,0.7);color:rgba(0,0,0,0.7);backdrop-filter:blur(8px);transition:all 0.22s;}
    .lang-btn:hover{background:#111111;color:#fff;}
    @media(max-width:1024px){.nav-center{display:none;}}

    .main-content { position: relative; z-index: 10; width:100%; max-width: 1200px; margin: 0 auto; padding: 100px 24px 48px; }

    /* PAGE HEADER */
    .page-header{margin-bottom:24px;}
    .page-header h1{font-size:clamp(26px,4vw,42px);font-weight:300;color:#111111;letter-spacing:-0.02em;margin-bottom:10px;}
    .page-header h1 em{font-style:normal;font-weight:700;background:linear-gradient(90deg,#ff0055,#0066ff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
    .page-header p{font-size:14px;color:rgba(0,0,0,0.6);line-height:1.8;max-width:560px;}
    [dir="rtl"] .page-header p{text-align:right;}

    /* SEARCH */
    .search-box{width:100%;max-width:420px;padding:11px 16px;border-radius:999px;border:1px solid rgba(0,0,0,0.1);background:rgba(255,255,255,0.6);backdrop-filter:blur(8px);font-size:14px;color:#111111;margin-bottom:18px;font-family:inherit;}
    .search-box:focus{outline:none;border-color:#0066ff;box-shadow:0 0 0 3px rgba(0,100,255,0.12);}

    /* FILTER BAR */
    .filter-group{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:14px;align-items:center;}
    [dir="rtl"] .filter-group{flex-direction:row-reverse;}
    .filter-label{font-size:10px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:rgba(0,0,0,0.4);margin-inline-end:4px;}
    .filter-btn{padding:8px 18px;border-radius:999px;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;cursor:pointer;border:1px solid rgba(0,0,0,0.1);background:rgba(255,255,255,0.6);backdrop-filter:blur(8px);color:rgba(0,0,0,0.7);transition:all 0.22s;}
    .filter-btn:hover{background:rgba(255,255,255,0.9);color:#111111;}
    .filter-btn.active{background:#111111;color:#fff;border-color:#111111;}

    /* ART GRID */
    .art-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; margin-top:8px; }

    /* GLASS CARD */
    .card { background: rgba(255,255,255,0.6); border:1px solid rgba(0,0,0,0.07); border-radius: 20px; overflow: hidden; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); transition: transform 0.3s ease, box-shadow 0.3s ease; display:flex; flex-direction:column; }
    .card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.08); }
    .card-img-wrap { position:relative; height: 200px; overflow:hidden; }
    .card-img { width:100%; height:100%; object-fit:cover; transition: transform 0.5s ease; }
    .card:hover .card-img { transform: scale(1.05); }
    .status-badge { position:absolute; top:12px; left:12px; padding:4px 12px; border-radius:999px; font-size:10px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; backdrop-filter:blur(8px); }
    [dir="rtl"] .status-badge { left:auto; right:12px; }
    .status-available { background:rgba(0,180,100,0.15); color:#00a050; border:1px solid rgba(0,180,100,0.3); }
    .status-sold { background:rgba(120,120,120,0.15); color:#666; border:1px solid rgba(120,120,120,0.3); }
    .status-auction { background:rgba(255,140,0,0.15); color:#ff8c00; border:1px solid rgba(255,140,0,0.35); }
    .card-body { padding:18px; display:flex; flex-direction:column; gap:4px; flex:1; }
    .card-type{font-size:11px;color:#aa00ff;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;margin-top:4px;}
    [dir="rtl"] .card-body,
    [dir="rtl"] .card-title,
    [dir="rtl"] .card-title-ar,
    [dir="rtl"] .card-type,
    [dir="rtl"] .card-artist,
    [dir="rtl"] .card-price { text-align:right; direction:rtl; }
    [dir="rtl"] .card-btn{align-self:flex-end;}
    .card-title { font-size:16px; font-weight:700; color:#111111; }
    .card-title-ar { font-size:13px; font-weight:600; color:rgba(0,0,0,0.55); }
    .card-artist { font-size:12px; color:rgba(0,0,0,0.6); margin-top:6px; }
    .card-artist a { color:#0066ff; text-decoration:none; font-weight:600; }
    .card-artist a:hover { text-decoration:underline; }
    .card-price { font-size:18px; font-weight:700; color:#0066ff; margin-top:10px; }
    .card-btn { margin-top:14px; display:inline-block; text-align:center; padding:10px 18px; border-radius:999px; background:#111111; color:#fff; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; text-decoration:none; border:none; cursor:pointer; transition:all 0.25s ease; }
    .card-btn:hover { background:#ff0055; transform:translateY(-2px); }
    .card.hidden { display:none; }

    /* EMPTY STATE */
    .empty-state{display:none;padding:48px;text-align:center;font-size:15px;color:rgba(0,0,0,0.5);background:rgba(255,255,255,0.6);border:1px solid rgba(0,0,0,0.07);border-radius:20px;backdrop-filter:blur(16px);margin-top:8px;}

    /* HUMAN-ONLY BANNER */
    .human-banner{display:flex;align-items:center;justify-content:center;gap:10px;text-align:center;margin:0 0 24px;padding:15px 22px;border-radius:16px;font-size:14.5px;font-weight:600;color:#111;background:linear-gradient(90deg,rgba(255,0,85,0.08),rgba(255,215,0,0.08),rgba(0,102,255,0.08),rgba(170,0,255,0.08));border:1px solid rgba(0,0,0,0.06);letter-spacing:0.01em;}
    .human-banner .hb-icon{font-size:18px;flex-shrink:0;}
    [dir="rtl"] .human-banner{flex-direction:row-reverse;}

    /* FIRST-STROKE (no approved artworks) */
    .gallery-empty{text-align:center;padding:64px 30px;background:rgba(255,255,255,0.6);border:1px dashed rgba(0,0,0,0.15);border-radius:24px;backdrop-filter:blur(16px);margin-top:10px;}
    .gallery-empty .ge-emoji{font-size:46px;margin-bottom:16px;}
    .gallery-empty h2{font-size:clamp(20px,3vw,28px);font-weight:700;color:#111;margin-bottom:12px;}
    .gallery-empty p{font-size:15px;color:rgba(0,0,0,0.62);line-height:1.8;max-width:460px;margin:0 auto 24px;}
    .gallery-empty .ge-btn{display:inline-block;padding:13px 32px;border-radius:999px;background:#ff0055;color:#fff;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;text-decoration:none;transition:all 0.25s;}
    .gallery-empty .ge-btn:hover{background:#111;transform:translateY(-2px);}

    /* ARTIST NAME emphasis */
    .card-artist{font-size:12.5px;color:rgba(0,0,0,0.6);margin:2px 0 6px;}
    .card-artist a{color:#0066ff;font-weight:700;text-decoration:none;}
    .card-artist a:hover{text-decoration:underline;}

    /* PAGINATION */
    .pagination{display:flex;justify-content:center;align-items:center;gap:14px;margin-top:36px;}
    .page-btn{padding:10px 22px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;text-decoration:none;border:1px solid rgba(0,0,0,0.12);background:rgba(255,255,255,0.6);backdrop-filter:blur(8px);color:#111111;transition:all 0.22s;}
    .page-btn:hover{background:#111111;color:#fff;}
    .page-btn.disabled{opacity:0.4;pointer-events:none;}
    .page-info{font-size:12px;color:rgba(0,0,0,0.55);font-weight:600;}

    /* MODAL */
    .modal-bg{position:fixed;inset:0;z-index:2000;background:rgba(0,0,0,0.5);backdrop-filter:blur(6px);display:none;align-items:center;justify-content:center;padding:20px;}
    .modal-bg.open{display:flex;}
    .modal{background:rgba(255,255,255,0.96);backdrop-filter:blur(20px);border:1px solid rgba(0,0,0,0.08);border-radius:20px;max-width:560px;width:100%;overflow:hidden;box-shadow:0 30px 70px rgba(0,0,0,0.25);max-height:90vh;overflow-y:auto;}
    .modal img{width:100%;height:260px;object-fit:cover;}
    .modal-body{padding:26px;}
    [dir="rtl"] .modal-body{text-align:right;}
    .modal-body h2{font-size:22px;font-weight:700;color:#111111;margin-bottom:2px;}
    .modal-body .m-title-ar{font-size:15px;color:rgba(0,0,0,0.55);margin-bottom:10px;}
    .modal-body .m-artist{font-size:13px;color:#0066ff;font-weight:600;margin-bottom:14px;text-decoration:none;display:inline-block;}
    .modal-body .m-label{font-size:10px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:rgba(0,0,0,0.4);margin:14px 0 4px;}
    .modal-body p{font-size:13px;color:rgba(0,0,0,0.7);line-height:1.8;}
    .modal-body .m-price{font-size:22px;font-weight:700;color:#0066ff;margin-top:16px;}
    .modal-close{float:right;background:none;border:none;font-size:22px;cursor:pointer;color:rgba(0,0,0,0.4);line-height:1;}
    [dir="rtl"] .modal-close{float:left;}

    @media(max-width:768px){ .main-content{padding:90px 16px 32px;} }
  </style>
</head>
<body>

<div class="bg-wrap">
  <video class="bg-video" autoplay loop muted playsinline src="bg.mp4"></video>
</div>

<nav class="topnav">
  <a class="nav-logo" href="index.html">
    <span style="display:inline-block;background:linear-gradient(135deg,#ff0055,#0066ff,#aa00ff);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent;font-weight:800;font-size:15px;letter-spacing:0.14em;">OWEILI</span>
    <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><defs><linearGradient id="navLogoGrad" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#ff0055"/><stop offset="50%" stop-color="#0066ff"/><stop offset="100%" stop-color="#aa00ff"/></linearGradient></defs><path fill="url(#navLogoGrad)" d="M12 0C12 6 18 12 24 12C18 12 12 18 12 24C12 18 6 12 0 12C6 12 12 6 12 0Z"/></svg>
  </a>
  <div class="nav-center">
    <div class="nav-item">
      <a href="studio.php"><span><?= $ar?'الاستوديو':'The Studio' ?></span><svg class="chevron" viewBox="0 0 10 6"><polyline points="1,1 5,5 9,1"/></svg></a>
      <div class="dropdown">
        <a href="studio.php#watercolor"><svg class="d-icon" viewBox="0 0 24 24"><path d="M12 2C8 2 4 6 4 10c0 5.25 8 12 8 12s8-6.75 8-12c0-4-4-8-8-8z"/></svg><span><?= $ar?'ورشة الألوان المائية':'Watercolor Workshop' ?></span></a>
        <a href="studio.php#oil"><svg class="d-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg><span><?= $ar?'استوديو الرسم الزيتي':'Oil Painting Studio' ?></span></a>
        <a href="studio.php#digital"><svg class="d-icon" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg><span><?= $ar?'مختبر الفن الرقمي':'Digital Art Lab' ?></span></a>
        <a href="studio.php#charcoal"><svg class="d-icon" viewBox="0 0 24 24"><path d="M3 17l4-8 4 4 4-6 4 10"/></svg><span><?= $ar?'الفحم والحبر':'Charcoal & Ink' ?></span></a>
      </div>
    </div>
    <div class="nav-item">
      <a href="community.php"><span><?= $ar?'المجتمع':'Community' ?></span><svg class="chevron" viewBox="0 0 10 6"><polyline points="1,1 5,5 9,1"/></svg></a>
      <div class="dropdown">
        <a href="community.php#meet"><svg class="d-icon" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg><span><?= $ar?'تعرّف على فنانين':'Meet Fellow Artists' ?></span></a>
        <a href="community.php#share"><svg class="d-icon" viewBox="0 0 24 24"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg><span><?= $ar?'شارك أعمالك':'Share Your Work' ?></span></a>
        <a href="community.php#learn"><svg class="d-icon" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg><span><?= $ar?'تعلّم معاً':'Learn Together' ?></span></a>
      </div>
    </div>
    <div class="nav-item"><a href="marketplace.php?lang=<?= $lang ?>"><span><?= $ar?'السوق':'Marketplace' ?></span></a></div>
    <div class="nav-item"><a href="explore.php"><span><?= $ar?'استكشف أساليب الرسم':'Explore Art Styles' ?></span></a></div>
    <div class="nav-item">
      <a href="support.php"><span><?= $ar?'الدعم':'Support' ?></span><svg class="chevron" viewBox="0 0 10 6"><polyline points="1,1 5,5 9,1"/></svg></a>
      <div class="dropdown">
        <a href="mailto:contact@oweili.com"><svg class="d-icon" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg><span><?= $ar?'تواصل معنا':'Contact Us' ?></span></a>
        <a href="support.php#how"><svg class="d-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><span><?= $ar?'كيف يعمل الموقع':'How It Works' ?></span></a>
        <a href="support.php#terms"><svg class="d-icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><span><?= $ar?'شروط الاستخدام':'Terms of Use' ?></span></a>
      </div>
    </div>
  </div>
  <div class="nav-right">
    <a class="nav-btn" href="login.php"><span><?= $ar?'تسجيل الدخول':'Sign In' ?></span></a>
    <a class="nav-btn" href="register.php"><span><?= $ar?'انضم مجاناً':'Join Free' ?></span></a>
    <button class="lang-btn" onclick="window.location.href='?lang=<?= $ar?'en':'ar' ?>'"><?= $ar?'English':'العربية' ?></button>
  </div>
</nav>

<main class="main-content">

  <div class="page-header">
    <h1><?= $ar ? 'فن <em>السوق</em>' : 'The <em>Marketplace</em>' ?></h1>
    <p><?= $ar
        ? 'اكتشف واقتنِ أعمالاً فنية أصلية من فنانين سعوديين موهوبين. كل قطعة فريدة من نوعها.'
        : 'Discover and collect original artworks from talented Saudi artists. Every piece is one of a kind.' ?></p>
  </div>

  <!-- HUMAN-ONLY BANNER -->
  <div class="human-banner">
    <span class="hb-icon">✋</span>
    <?= $ar ? 'كل عمل فني هنا صنعته يد إنسان' : 'Every artwork here was created by a human hand' ?>
  </div>

  <?php if ($total > 0): ?>
  <input type="search" class="search-box" id="searchBox"
         placeholder="<?= $ar ? 'ابحث عن عمل فني…' : 'Search artworks by title…' ?>"
         oninput="onSearch(this.value)">

  <!-- TYPE FILTER -->
  <div class="filter-group">
    <span class="filter-label"><?= $ar?'النوع':'Type' ?></span>
    <button class="filter-btn active" data-type="all" onclick="setType('all',this)"><?= $ar?'الكل':'All' ?></button>
    <?php foreach ($ART_TYPES as $slug => $lbl): ?>
    <button class="filter-btn" data-type="<?= e($slug) ?>" onclick="setType('<?= e($slug) ?>',this)"><?= e($ar ? $lbl['ar'] : $lbl['en']) ?></button>
    <?php endforeach; ?>
  </div>

  <!-- STATUS FILTER -->
  <div class="filter-group">
    <span class="filter-label"><?= $ar?'الحالة':'Status' ?></span>
    <button class="filter-btn active" data-status="all" onclick="setStatus('all',this)"><?= $ar?'الكل':'All' ?></button>
    <button class="filter-btn" data-status="available" onclick="setStatus('available',this)"><?= $ar?'متاح':'Available' ?></button>
    <button class="filter-btn" data-status="auction" onclick="setStatus('auction',this)"><?= $ar?'مزاد':'Auction' ?></button>
  </div>
  <?php endif; ?>

  <div class="art-grid" id="artGrid">
    <?php foreach ($artworks as $aw):
      $st = $aw['status']; // available | auction (sold excluded by query)
      $badge = $st === 'auction' ? 'status-auction' : ($st === 'sold' ? 'status-sold' : 'status-available');
      $badgeLabel = $st === 'auction' ? ($ar?'مزاد':'Auction') : ($st === 'sold' ? ($ar?'مباع':'Sold') : ($ar?'متاح':'Available'));
    ?>
      <div class="card" data-type="<?= e($aw['type']) ?>" data-status="<?= e($st) ?>"<?= $ar ? ' dir="rtl"' : '' ?>
           data-title-en="<?= e($aw['title_en']) ?>" data-title-ar="<?= e($aw['title_ar']) ?>">
        <div class="card-img-wrap">
          <img class="card-img" src="<?= e($aw['image_url']) ?>" alt="<?= e($ar ? $aw['title_ar'] : $aw['title_en']) ?>">
          <span class="status-badge <?= $badge ?>"><?= e($badgeLabel) ?></span>
        </div>
        <div class="card-body"<?= $ar ? ' dir="rtl"' : '' ?>>
          <?php if ($ar): ?>
            <div class="card-title" dir="rtl"><?= e($aw['title_ar'] ?: $aw['title_en']) ?></div>
            <?php if (!empty($aw['title_en'])): ?><div class="card-title-ar" dir="ltr" style="text-align:right;"><?= e($aw['title_en']) ?></div><?php endif; ?>
          <?php else: ?>
            <div class="card-title"><?= e($aw['title_en']) ?></div>
            <div class="card-title-ar" dir="rtl"><?= e($aw['title_ar']) ?></div>
          <?php endif; ?>
          <?php $tlbl = $ART_TYPES[$aw['type']] ?? null; ?>
          <?php if ($tlbl): ?><div class="card-type"><?= e($ar ? $tlbl['ar'] : $tlbl['en']) ?></div><?php endif; ?>
          <div class="card-artist"><?= $ar?'بواسطة':'by' ?>
            <a href="artist_dashboard.php?id=<?= (int) $aw['artist_id'] ?>&lang=<?= $lang ?>"><?= e($ar ? $aw['artist_ar'] : $aw['artist_en']) ?></a>
          </div>
          <div class="card-price"><?= number_format((float) $aw['price']) ?> <?= $ar?'ر.س':'SAR' ?></div>
          <button type="button" class="card-btn" onclick="openModal(<?= (int) $aw['id'] ?>)"><?= $ar?'عرض التفاصيل':'View Details' ?></button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- EMPTY STATE -->
  <div class="empty-state" id="emptyState"
       data-en="No artworks found" data-ar="لم يتم العثور على أعمال">
    <?php if ($total === 0): ?>
      <?= $ar ? 'لم يتم العثور على أعمال' : 'No artworks found' ?>
    <?php else: ?>
      <?= $ar ? 'لم يتم العثور على أعمال' : 'No artworks found' ?>
    <?php endif; ?>
  </div>
  <?php if ($total === 0): ?>
    <div class="gallery-empty">
      <div class="ge-emoji">🎨</div>
      <h2><?= $ar ? 'المعرض ينتظر أول لمسة إنسانية' : 'The gallery awaits its first human stroke' ?></h2>
      <p><?= $ar ? 'هل أنت فنان؟ شارك عملك مع العالم.' : 'Are you an artist? Share your work with the world.' ?></p>
      <a class="ge-btn" href="register_artist.php"><?= $ar ? 'انضم كفنان' : 'Join as an Artist' ?></a>
    </div>
  <?php endif; ?>

  <!-- PAGINATION -->
  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <a class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>"
       href="?lang=<?= $lang ?>&page=<?= max(1, $page - 1) ?>"><?= $ar?'السابق':'Previous' ?></a>
    <span class="page-info"><?= $ar ? "صفحة $page من $totalPages" : "Page $page of $totalPages" ?></span>
    <a class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>"
       href="?lang=<?= $lang ?>&page=<?= min($totalPages, $page + 1) ?>"><?= $ar?'التالي':'Next' ?></a>
  </div>
  <?php endif; ?>

</main>

<!-- DETAILS MODAL -->
<div class="modal-bg" id="modalBg" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <div class="modal-body">
      <button class="modal-close" onclick="closeModal()">&times;</button>
      <img id="m-img" src="" alt="" style="border-radius:14px;margin-bottom:16px;">
      <h2 id="m-title-en"></h2>
      <div class="m-title-ar" id="m-title-ar" dir="rtl"></div>
      <a class="m-artist" id="m-artist" href="#"></a>
      <div class="m-label" id="m-label-en-h"><?= $ar?'الوصف (إنجليزي)':'Description (English)' ?></div>
      <p id="m-desc-en"></p>
      <div class="m-label" id="m-label-ar-h"><?= $ar?'الوصف (عربي)':'Description (Arabic)' ?></div>
      <p id="m-desc-ar" dir="rtl"></p>
      <div class="m-price" id="m-price"></div>
    </div>
  </div>
</div>

<script>
const LANG = <?= json_encode($lang) ?>;
const ARTWORKS = <?= json_encode($modalData, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?>;

/* ---- Filters + search (client-side, no reload) ---- */
let curType = 'all', curStatus = 'all', curSearch = '';

function sanitizeSearch(v){
  // strip characters that have no business in a title search
  return String(v).replace(/[<>"'`]/g, '').trim().toLowerCase();
}
function setType(t, btn){
  curType = t;
  document.querySelectorAll('[data-type]').forEach(b=>{ if(b.classList.contains('filter-btn')) b.classList.remove('active'); });
  btn.classList.add('active');
  applyFilters();
}
function setStatus(s, btn){
  curStatus = s;
  document.querySelectorAll('[data-status].filter-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  applyFilters();
}
function onSearch(v){ curSearch = sanitizeSearch(v); applyFilters(); }

function applyFilters(){
  let visible = 0;
  document.querySelectorAll('#artGrid .card').forEach(card=>{
    const okType   = curType === 'all'   || card.dataset.type === curType;
    const okStatus = curStatus === 'all' || card.dataset.status === curStatus;
    const okSearch = !curSearch
      || (card.dataset.titleEn||'').toLowerCase().includes(curSearch)
      || (card.dataset.titleAr||'').toLowerCase().includes(curSearch);
    const show = okType && okStatus && okSearch;
    card.classList.toggle('hidden', !show);
    if (show) visible++;
  });
  const empty = document.getElementById('emptyState');
  empty.style.display = visible === 0 ? 'block' : 'none';
}

/* ---- Details modal ---- */
function esc(s){ return String(s==null?'':s); }
function openModal(id){
  const a = ARTWORKS[id];
  if(!a) return;
  document.getElementById('m-img').src = a.image;
  document.getElementById('m-title-en').textContent = a.title_en;
  document.getElementById('m-title-ar').textContent = a.title_ar;
  const artist = document.getElementById('m-artist');
  artist.textContent = (LANG==='ar' ? 'بواسطة ' : 'by ') + (LANG==='ar' ? a.artist_ar : a.artist_en);
  artist.href = 'artist_dashboard.php?id=' + a.artist_id + '&lang=' + LANG;
  document.getElementById('m-desc-en').textContent = esc(a.desc_en);
  document.getElementById('m-desc-ar').textContent = esc(a.desc_ar);
  document.getElementById('m-price').textContent =
    Number(a.price).toLocaleString() + ' ' + (LANG==='ar' ? 'ر.س' : 'SAR');
  document.getElementById('modalBg').classList.add('open');
}
function closeModal(){ document.getElementById('modalBg').classList.remove('open'); }
document.addEventListener('keydown', e=>{ if(e.key==='Escape') closeModal(); });
</script>

</body>
</html>
