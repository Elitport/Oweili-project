<?php
require_once __DIR__ . '/studio_lib.php';   // the studio photo behind the page
/* =====================================================================
 *  institutes.php: Art Institutes («المعاهد الفنية»), back in the top menu
 *  after Art Map (owner, 15 Nov 2026; it was a redirect to the Art Map from
 *  12 to 15 Nov 2026). Same background photo as before (easels-class).
 *  Shows the courses visitors can book on Oweili, from approved institutes:
 *  approved, active, not started yet. Filters: city (or Online), level, price.
 *  Each course opens its page (course.php?id=N): "Book your seat" until real
 *  payments are on, then "Pay now" (booking_lib.php owCoursePayOn).
 *  ?i=N (institutes) or ?p=N (Art Map institutes in art_places): one institute's
 *  courses; with none, "No courses right now" and a "Notify me" form (name,
 *  email, phone) saved in course_leads and sent to Bashro Sales.
 *  HIDDEN while the courses switch is off (Admin → Settings): 404 to visitors.
 *  Tables: institutes, courses, art_places (read), course_leads
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/booking_lib.php';
require_once __DIR__ . '/artmap_lib.php';
$preview = owCoursesGate();
require_once __DIR__ . '/page_cache.php'; owPublicPage();   // public page: Back opens at once, early load on touch

$db = getDB();
$instId = (int) ($_GET['i'] ?? 0);
$placeId = (int) ($_GET['p'] ?? 0);
$inst = null;   // ['src' => 'i'|'p', 'id', 'name_en', 'name_ar', 'city_en', 'city_ar', 'logo']
try {
    if ($instId > 0) {
        $st = $db->prepare('SELECT * FROM institutes WHERE id = :id AND is_active = 1 AND ' . owSignupInstSql()); $st->execute([':id' => $instId]);
        if ($r = $st->fetch()) $inst = ['src' => 'i', 'id' => (int) $r['id'], 'name_en' => $r['name_en'] ?: $r['name_ar'], 'name_ar' => $r['name_ar'] ?: $r['name_en'],
                                        'city_en' => $r['city_en'] ?: $r['city_ar'], 'city_ar' => $r['city_ar'] ?: $r['city_en'], 'logo' => trim((string) $r['logo_path'])];
    } elseif ($placeId > 0) {
        $st = $db->prepare("SELECT * FROM art_places WHERE id = :id AND kind = 'institute' AND status = 'published'"); $st->execute([':id' => $placeId]);
        if ($r = $st->fetch()) $inst = ['src' => 'p', 'id' => (int) $r['id'], 'name_en' => $r['name_en'], 'name_ar' => $r['name_ar'] ?: $r['name_en'],
                                        'city_en' => (string) $r['city_en'], 'city_ar' => (string) ($r['city_ar'] ?: $r['city_en']), 'logo' => ''];
    }
} catch (Throwable $e) { $inst = null; }

/* ---- "Notify me" (owner, 15 Nov 2026) ---- */
/* "City or Online" choices (owner, 21 Nov 2026): the cities used by in-person courses, else the cities of the shown institutes,
   plus this institute's own city; 'online' is added in the form. Key = English name (saved), value = [English, Arabic]. */
$leadCities = [];
function owLeadCityOk(string $c): bool { return (bool) preg_match("/^[A-Za-z][A-Za-z' -]*$/", $c) && !preg_match('/ and /i', $c); }   // one city per choice (not "Riyadh and Jeddah")
try {
    foreach ([
        "SELECT i.city_en, MIN(i.city_ar) AS city_ar FROM courses c JOIN institutes i ON i.id = c.institute_id
         WHERE c.bookable = 1 AND c.status = 'approved' AND c.course_type = 'in_person' AND i.is_active = 1 AND i.city_en <> '' GROUP BY i.city_en ORDER BY i.city_en",
        "SELECT city_en, MIN(city_ar) AS city_ar FROM institutes WHERE is_active = 1 AND city_en <> '' GROUP BY city_en ORDER BY city_en",
    ] as $sql) {
        foreach ($db->query($sql)->fetchAll() as $r) if (owLeadCityOk((string) $r['city_en']))
            $leadCities[(string) $r['city_en']] = [(string) $r['city_en'], (string) ($r['city_ar'] ?: $r['city_en'])];
        if ($leadCities) break;
    }
} catch (Throwable $e) { $leadCities = []; }
if ($inst && owLeadCityOk($inst['city_en']) && !isset($leadCities[$inst['city_en']])) $leadCities[$inst['city_en']] = [$inst['city_en'], $inst['city_ar']];
$nErr = null; $nVals = ['name' => '', 'email' => '', 'phone' => '', 'city' => '', 'art' => 'any'];
$selfUrl = 'institutes.php' . ($inst ? '?' . ($inst['src'] === 'i' ? 'i=' : 'p=') . $inst['id'] : '');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['do'] ?? '') === 'notify') {
    $nVals = ['name' => mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 150), 'email' => strtolower(trim((string) ($_POST['email'] ?? ''))), 'phone' => trim((string) ($_POST['phone'] ?? '')), 'city' => trim((string) ($_POST['city'] ?? '')), 'art' => (string) ($_POST['art'] ?? 'any')];
    if ($nVals['art'] !== 'any' && !isset(OW_COURSE_ARTS[$nVals['art']])) $nVals['art'] = 'any';   // "Which art?" is optional (owner, 21 Nov 2026)
    $back = $selfUrl . (str_contains($selfUrl, '?') ? '&' : '?') . 'notified=1#notify';
    if (trim((string) ($_POST['website'] ?? '')) !== '') { header('Location: ' . $back); exit; }   // the hidden robot trap
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) $nErr = ['The page was open too long. Please try again.', 'انتهت صلاحية الصفحة. حاول مرة أخرى.'];
    elseif (owReqLimited('course_lead', 3)) $nErr = ['Too many requests from this device. Please try again in an hour.', 'طلبات كثيرة من هذا الجهاز. حاول بعد ساعة.'];
    elseif (owNameProblem($nVals['name'], true) !== '') $nErr = ['Please write your name in letters.', 'يرجى كتابة اسمك بالحروف.'];
    elseif (!filter_var($nVals['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($nVals['email']) > 190) $nErr = ['Please write a valid email address.', 'يرجى كتابة بريد إلكتروني صحيح.'];
    elseif (!preg_match('/^\+?[0-9][0-9 \-()]{6,29}$/', $nVals['phone'])) $nErr = ['Please enter a valid mobile number.', 'يرجى إدخال رقم جوال صالح.'];
    elseif ($nVals['city'] !== 'online' && !isset($leadCities[$nVals['city']])) $nErr = ['Please choose a city or Online.', 'يرجى اختيار مدينة أو «عن بعد».'];
    else {
        owReqCounted('course_lead');
        $nErr = owCourseLeadSave($inst && $inst['src'] === 'i' ? $inst['id'] : null, $inst && $inst['src'] === 'p' ? $inst['id'] : null,
                                 $nVals['name'], $nVals['email'], $nVals['phone'], owIsAr() ? 'ar' : 'en', $inst['name_en'] ?? '', $inst['name_ar'] ?? '', $nVals['city'], $nVals['art']);
        if (!$nErr) { header('Location: ' . $back); exit; }
    }
}
$notified = isset($_GET['notified']);

/* ---- filters ---- */
const OW_INST_PRICES = ['free' => ['Free', 'مجاني', 0, 0], 'u500' => ['Under 500 SAR', 'أقل من 500 ر.س', 0.01, 499.99],
                        '500-1000' => ['500 to 1,000 SAR', 'من 500 إلى 1,000 ر.س', 500, 1000], 'o1000' => ['Over 1,000 SAR', 'أكثر من 1,000 ر.س', 1000.01, 99999999]];
$fCity = (string) ($_GET['city'] ?? '');
$fLevel = (string) ($_GET['level'] ?? '');
$fPrice = (string) ($_GET['price'] ?? '');
$fArt = (string) ($_GET['art'] ?? '');   // art type (owner, 21 Nov 2026): painting, sculpture or handmade jewellery
if (!isset(OW_COURSE_ARTS[$fArt]) || !owCourseArtReady()) $fArt = '';
if (!isset(OW_COURSE_LEVELS[$fLevel])) $fLevel = '';
if (!isset(OW_INST_PRICES[$fPrice])) $fPrice = '';
if (mb_strlen($fCity) > 120) $fCity = '';

$base = "c.bookable = 1 AND c.status = 'approved' AND c.is_active = 1 AND i.is_active = 1 AND " . owSignupInstSql('i.') . " AND (c.start_date IS NULL OR c.start_date > CURDATE())";
$courses = []; $cities = [];
try {
    if (!$inst || $inst['src'] === 'i') {
        $cities = $db->query("SELECT i.city_en, MIN(i.city_ar) AS city_ar FROM courses c JOIN institutes i ON i.id = c.institute_id
                              WHERE $base AND c.course_type = 'in_person' AND i.city_en <> '' GROUP BY i.city_en ORDER BY i.city_en")->fetchAll();
        $where = $base; $args = [];
        if ($inst) { $where .= ' AND c.institute_id = :inst'; $args[':inst'] = $inst['id']; }
        if ($fCity === 'online') $where .= " AND c.course_type = 'online'";
        elseif ($fCity !== '') { $where .= " AND c.course_type = 'in_person' AND i.city_en = :city"; $args[':city'] = $fCity; }
        if ($fArt !== '') { $where .= ' AND c.art_type = :art'; $args[':art'] = $fArt; }
        if ($fLevel !== '') { $where .= ' AND c.level = :lvl'; $args[':lvl'] = $fLevel; }
        if ($fPrice !== '') { $where .= ' AND c.price BETWEEN :p1 AND :p2'; $args[':p1'] = OW_INST_PRICES[$fPrice][2]; $args[':p2'] = OW_INST_PRICES[$fPrice][3]; }
        $st = $db->prepare("SELECT c.*, i.name_en AS inst_en, i.name_ar AS inst_ar, i.city_en AS inst_city_en, i.city_ar AS inst_city_ar, i.logo_path AS inst_logo
                            FROM courses c JOIN institutes i ON i.id = c.institute_id WHERE $where ORDER BY (c.start_date IS NULL), c.start_date, c.id LIMIT 300");
        $st->execute($args);
        $courses = $st->fetchAll();
    }
} catch (Throwable $e) { error_log('institutes.php: ' . $e->getMessage()); $courses = []; }
$filtered = $fCity !== '' || $fLevel !== '' || $fPrice !== '' || $fArt !== '';
if ($fCity !== '' && $fCity !== 'online' && !in_array($fCity, array_column($cities, 'city_en'), true)) $cities[] = ['city_en' => $fCity, 'city_ar' => $fCity];
$q = function (array $over = []) use ($inst, $fCity, $fLevel, $fPrice, $fArt): string {
    $a = array_filter(array_merge(['i' => $inst && $inst['src'] === 'i' ? $inst['id'] : '', 'p' => $inst && $inst['src'] === 'p' ? $inst['id'] : '', 'city' => $fCity, 'art' => $fArt, 'level' => $fLevel, 'price' => $fPrice], $over), fn($v) => $v !== '' && $v !== null);
    return 'institutes.php' . ($a ? '?' . http_build_query($a) : '');
};
$PIN = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>';
/* DEMO courses (owner, 20 Nov 2026): only for the signed-in admin, on the main list, never in the database (demo_lib.php).
   They follow the same City, Level and Price filters; nothing can be booked. */
require_once __DIR__ . '/demo_lib.php';
if (!$inst && owDemoOn()) {
    foreach (owDemoCourses() as $dc) {
        if ($dc['course_type'] === 'in_person' && !in_array($dc['inst_city_en'], array_column($cities, 'city_en'), true)) $cities[] = ['city_en' => $dc['inst_city_en'], 'city_ar' => $dc['inst_city_ar']];
        if ($fCity === 'online' && $dc['course_type'] !== 'online') continue;
        if ($fCity !== '' && $fCity !== 'online' && ($dc['course_type'] !== 'in_person' || $dc['inst_city_en'] !== $fCity)) continue;
        if ($fArt !== '' && $dc['art_type'] !== $fArt) continue;
        if ($fLevel !== '' && $dc['level'] !== $fLevel) continue;
        if ($fPrice !== '' && ($dc['price'] < OW_INST_PRICES[$fPrice][2] || $dc['price'] > OW_INST_PRICES[$fPrice][3])) continue;
        $courses[] = $dc;
    }
}
$n = count($courses);
$instHasCourses = $inst && ($n > 0 || $filtered);   // an institute page with no courses hides its 'courses you can book' line and 'All courses' (21 Nov 2026)
$titleEn = $inst ? $inst['name_en'] . ': courses' : 'Art Institutes';
$titleAr = $inst ? 'دورات ' . $inst['name_ar'] : 'المعاهد الفنية';
?>
<!DOCTYPE html>
<html <?php require_once __DIR__ . '/lang_lib.php'; owHtmlAttrs(); ?> data-title-en="<?= e($titleEn) ?> · Oweili" data-title-ar="<?= e($titleAr) ?> · أويلي">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require_once __DIR__ . '/seo_lib.php'; owSeo(['en' => $titleEn, 'ar' => $titleAr, 'desc_en' => 'Art courses from approved institutes. Book your seat on Oweili.', 'desc_ar' => 'دورات فنية من معاهد معتمدة. احجز مقعدك عبر أويلي.', 'path' => $inst ? $selfUrl : 'institutes.php', 'noindex' => $preview || $filtered]); ?>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/nav.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/discover.css') ?>">
<script>document.documentElement.classList.add('js')</script><script defer src="<?= owAsset('assets/js/discover.js') ?>"></script>
<style>
/* Art Institutes — layout only; type, colors, buttons and cards come from oweili.css */
.ci-bar{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:center;gap:var(--space-sm);margin:0 auto var(--space-md);padding:var(--space-sm) var(--space-md);max-width:860px;}
.ci-bar .field{margin:0;min-width:180px;flex:1 1 180px;}
.ci-bar label{font-size:var(--fs-label);font-weight:700;color:var(--color-dark);}
.ci-bar select{width:100%;cursor:pointer;}
.ci-bar .ci-go{flex:0 0 auto;}
.js .ci-bar .ci-go{display:none;}
.result-count{text-align:center;font-size:var(--fs-caption);color:var(--color-dark);width:fit-content;margin:0 auto var(--space-md);padding:6px 16px;border-radius:var(--radius-card);background:var(--studio-glass);}
.c-grid{display:flex;flex-wrap:wrap;justify-content:center;gap:var(--space-md);align-items:stretch;}
.c-grid > .c-card{flex:0 1 calc((100% - 2 * var(--space-md)) / 3);min-width:0;}
.c-card{display:flex;flex-direction:column;gap:var(--space-xs);}
.c-inst{display:flex;align-items:center;gap:var(--space-xs);font-size:var(--fs-caption);color:var(--color-body);}
.c-inst img{width:32px;height:32px;object-fit:contain;border-radius:8px;background:#fff;border:1px solid var(--color-border);}
.c-inst a{color:var(--color-body);text-decoration:none;font-weight:700;}
.c-inst a:hover,.c-inst a:focus-visible{text-decoration:underline;color:var(--color-dark);}
.c-card h2{font-size:var(--fs-subtitle);line-height:1.35;margin:0;}
.c-tags{display:flex;flex-wrap:wrap;gap:6px;}
.c-tag{padding:2px 10px;border-radius:var(--radius-pill);font-size:var(--fs-min);font-weight:700;color:var(--color-accent-dark);background:rgba(var(--rgb-accent),0.08);border:1px solid rgba(var(--rgb-accent),0.2);}
.c-tag svg{width:12px;height:12px;stroke:currentColor;fill:none;stroke-width:2;vertical-align:-1px;margin-inline-end:3px;}
.c-meta{display:flex;flex-direction:column;gap:2px;font-size:var(--fs-caption);color:var(--color-body);margin:0;}
.c-price{font-size:20px;font-weight:800;color:var(--color-dark);margin-top:auto;}
.c-card .btn{align-self:stretch;}
.ci-head{display:flex;align-items:center;gap:var(--space-sm);justify-content:center;}
.ci-head img{width:56px;height:56px;object-fit:contain;border-radius:12px;background:#fff;border:1px solid var(--color-border);}
.ci-city{display:flex;align-items:center;justify-content:center;gap:4px;font-size:var(--fs-caption);color:var(--color-body);margin-top:var(--space-xs);}
.ci-city svg{width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2;}
.ci-none{max-width:560px;margin:0 auto;text-align:center;padding:var(--space-lg) var(--space-md);}
.ci-none h2{font-size:var(--fs-subtitle);margin:0 0 var(--space-xs);}
.ci-none > p{margin:0 0 var(--space-md);}
.ci-form{text-align:center;margin-top:var(--space-md);}
.ci-form > summary{list-style:none;}
.ci-form > summary::-webkit-details-marker{display:none;}
.ci-form[open] > summary{display:none;}
.ci-form form{text-align:start;}
.ci-form .g2{display:grid;grid-template-columns:1fr 1fr;gap:0 var(--space-sm);}
.ci-back{text-align:center;margin-top:var(--space-md);}
html.dark-mode .ci-priv{color:var(--color-body);}   /* the "Privacy Policy" link under Notify me: lighter in dark mode (owner, 16 Nov 2026) */
.ci-trap{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%);white-space:nowrap;}
html.dark-mode .c-card,html.dark-mode .ci-none{background:var(--color-card);}
@media (max-width:1024px){.c-grid > .c-card{flex-basis:calc((100% - var(--space-md)) / 2);}}
@media (max-width:640px){.c-grid > .c-card{flex-basis:100%;}.ci-form .g2{grid-template-columns:1fr;}.ci-bar{padding:var(--space-sm);}.ci-bar .field{flex-basis:100%;}}
</style>
</head>
<body class="ow bx bx-3">
<?= owStudioBackdrop('easels-class') /* the easels ready for a class (owner, 6 Nov 2026; back 15 Nov 2026) */ ?>
<div class="bg-wrap" aria-hidden="true"></div>
<div class="veil" aria-hidden="true"></div>

<?php include __DIR__ . '/nav.php'; ?>
<?php if ($preview) echo owHiddenNotice(); ?>

<main>
  <div class="d-wrap">
    <header class="d-head reveal">
      <?php if ($inst): ?>
        <div class="ci-head"><?php if ($inst['logo'] !== '' && owSafeLogo($inst['logo'])): ?><img src="<?= e($inst['logo']) ?>" alt="<?= e('Logo of ' . $inst['name_en'] . ' · شعار ' . $inst['name_ar']) ?>"><?php endif; ?>
          <h1><?= owBi($inst['name_en'], $inst['name_ar']) ?></h1></div>
        <?php if ($inst['city_en'] !== ''): ?><div class="ci-city"><?= $PIN ?><?= owBi($inst['city_en'], $inst['city_ar']) ?></div><?php endif; ?>
        <?php if ($instHasCourses): ?><p><?= owBi('Courses you can book on Oweili.', 'دورات يمكنك حجزها عبر أويلي.') ?></p><?php endif; ?>
      <?php else: ?>
        <h1><?= owBi('Art Institutes', 'المعاهد الفنية') ?></h1>
        <p><?= owBi('Art courses from approved institutes. Book your seat on Oweili.', 'دورات فنية من معاهد معتمدة. احجز مقعدك عبر أويلي.') ?></p>
      <?php endif; ?>
    </header>

    <?php if (!$inst || $inst['src'] === 'i'): if ($n > 0 || $filtered): ?>
    <form class="card ci-bar reveal" method="get" action="institutes.php" id="ci-filter">
      <?php if ($inst): ?><input type="hidden" name="i" value="<?= (int) $inst['id'] ?>"><?php endif; ?>
      <div class="field"><label for="f-city"><?= owBi('City', 'المدينة') ?></label>
        <select id="f-city" name="city"><option value=""><?= owIsAr() ? 'كل المدن' : 'All cities' ?></option>
          <?php foreach ($cities as $ct): ?><option value="<?= e($ct['city_en']) ?>"<?= $fCity === $ct['city_en'] ? ' selected' : '' ?>><?= e(owIsAr() ? ($ct['city_ar'] ?: $ct['city_en']) : $ct['city_en']) ?></option><?php endforeach; ?>
          <option value="online"<?= $fCity === 'online' ? ' selected' : '' ?>><?= owIsAr() ? 'عن بعد' : 'Online' ?></option>
        </select></div>
      <?php if (owCourseArtReady()): ?><div class="field"><label for="f-art"><?= owBi('Art type', 'نوع الفن') ?></label>
        <select id="f-art" name="art"><option value=""><?= owIsAr() ? 'كل الأنواع' : 'All types' ?></option>
          <?php foreach (OW_COURSE_ARTS as $k => $l): ?><option value="<?= e($k) ?>"<?= $fArt === $k ? ' selected' : '' ?>><?= e(owIsAr() ? $l[1] : $l[0]) ?></option><?php endforeach; ?>
        </select></div><?php endif; ?>
      <div class="field"><label for="f-level"><?= owBi('Level', 'المستوى') ?></label>
        <select id="f-level" name="level"><option value=""><?= owIsAr() ? 'كل المستويات' : 'All levels' ?></option>
          <?php foreach (OW_COURSE_LEVELS as $k => $l): ?><option value="<?= e($k) ?>"<?= $fLevel === $k ? ' selected' : '' ?>><?= e(owIsAr() ? $l[1] : $l[0]) ?></option><?php endforeach; ?>
        </select></div>
      <div class="field"><label for="f-price"><?= owBi('Price', 'السعر') ?></label>
        <select id="f-price" name="price"><option value=""><?= owIsAr() ? 'أي سعر' : 'Any price' ?></option>
          <?php foreach (OW_INST_PRICES as $k => $l): ?><option value="<?= e($k) ?>"<?= $fPrice === $k ? ' selected' : '' ?>><?= e(owIsAr() ? $l[1] : $l[0]) ?></option><?php endforeach; ?>
        </select></div>
      <button class="btn btn-secondary ci-go" type="submit"><?= owBi('Show', 'اعرض') ?></button>
    </form>
    <script>document.getElementById('ci-filter').addEventListener('change',function(){this.submit();});</script>
    <?php endif; endif; ?>

    <?php if ($n > 0): ?>
      <p class="result-count"><?= owBi($n === 1 ? '1 course' : $n . ' courses', $n === 1 ? 'دورة واحدة' : ($n === 2 ? 'دورتان' : $n . ($n <= 10 ? ' دورات' : ' دورة'))) ?></p>
      <div class="c-grid">
        <?php foreach ($courses as $c):
          [$wEn, $wAr] = owDateRangeBi($c['start_date'], $c['end_date']);
          $lvl = OW_COURSE_LEVELS[$c['level']] ?? OW_COURSE_LEVELS['beginner'];
          $online = $c['course_type'] === 'online';
          $price = (float) $c['price'];
          $left = (int) $c['seats_remaining'];
          $pTxt = number_format($price, $price == floor($price) ? 0 : 2);
        ?>
        <?php $isDemo = !empty($c['demo']); ?>
        <article class="card c-card reveal<?= $isDemo ? ' is-demo' : '' ?>">
          <div class="c-inst"><?php if (trim((string) $c['inst_logo']) !== '' && owSafeLogo((string) $c['inst_logo'])): ?><img loading="lazy" src="<?= e($c['inst_logo']) ?>" alt=""><?php endif; ?>
            <?php if ($isDemo): ?><span><?= owBi($c['inst_en'], $c['inst_ar']) ?></span> <?= owDemoBadge() ?><?php else: ?><a href="institutes.php?i=<?= (int) $c['institute_id'] ?>"><?= owBi((string) ($c['inst_en'] ?: $c['inst_ar']), (string) ($c['inst_ar'] ?: $c['inst_en'])) ?></a><?php endif; ?></div>
          <h2><?= owBi((string) ($c['title_en'] ?: $c['title_ar']), (string) ($c['title_ar'] ?: $c['title_en'])) ?></h2>
          <div class="c-tags">
            <?php if (owCourseArtReady() || $isDemo): $art = OW_COURSE_ARTS[$c['art_type'] ?? ''] ?? OW_COURSE_ARTS['painting']; ?><span class="c-tag c-art"><?= owBi($art[0], $art[1]) ?></span><?php endif; ?>
            <span class="c-tag"><?= owBi($lvl[0], $lvl[1]) ?></span>
            <span class="c-tag"><?php if ($online): ?><?= owBi('Online', 'عن بعد') ?><?php else: ?><?= $PIN ?><?= owBi((string) ($c['inst_city_en'] ?: $c['inst_city_ar']), (string) ($c['inst_city_ar'] ?: $c['inst_city_en'])) ?><?php endif; ?></span>
          </div>
          <p class="c-meta"><span><?= owBi($wEn, $wAr) ?></span>
            <span><?= $left > 0 ? owBi($left === 1 ? '1 seat left' : $left . ' seats left', $left === 1 ? 'بقي مقعد واحد' : 'متبقٍ ' . $left . ' مقاعد') : owBi('Full', 'اكتمل العدد') ?></span></p>
          <div class="c-price"><?= $price > 0 ? owBi($pTxt . ' SAR', $pTxt . ' ر.س') : owBi('Free', 'مجاني') ?></div>
          <?php if ($isDemo): ?><button type="button" class="btn btn-primary" disabled aria-disabled="true" title="<?= e(owIsAr() ? OW_DEMO_NOTE[1] : OW_DEMO_NOTE[0]) ?>"><?= owBi('View the course', 'عرض الدورة') ?></button>
          <?php else: ?><a class="btn btn-primary" href="course.php?id=<?= (int) $c['id'] ?>"><?= owBi('View the course', 'عرض الدورة') ?></a><?php endif; ?>
        </article>
        <?php endforeach; ?>
      </div>
    <?php elseif ($filtered): ?>
      <div class="card empty-state d-empty reveal">
        <?= owBi('No courses match these choices. Try another city, level or price.', 'لا توجد دورات تطابق هذه الاختيارات. جرّب مدينة أو مستوى أو سعراً آخر.') ?>
        <div><a class="btn btn-secondary" href="<?= e($q(['city' => '', 'art' => '', 'level' => '', 'price' => ''])) ?>"><?= owBi('Show all courses', 'اعرض كل الدورات') ?></a></div>
      </div>
    <?php else: ?>
      <div class="card ci-none reveal" id="notify">
        <h2><?= owBi('No courses right now', 'لا توجد دورات حالياً') ?></h2>
        <p><?= $inst ? owBi('Leave your details and we will tell you when ' . $inst['name_en'] . ' opens a course on Oweili.', 'اترك بياناتك ونخبرك عندما يفتح ' . $inst['name_ar'] . ' دورة عبر أويلي.')
                     : owBi('Leave your details and we will tell you when a new course opens.', 'اترك بياناتك ونخبرك عند توفر دورة جديدة.') ?></p>
        <?php if ($notified): ?>
          <div class="alert alert-ok" role="status"><?= owBi('Thank you. We will tell you when a course opens.', 'شكراً. سنخبرك عند توفر دورة.') ?></div>
        <?php else: ?>
          <details class="ci-form"<?= $nErr ? ' open' : '' ?>>
            <summary class="btn btn-primary"><?= owBi('Notify me', 'نبّهني عند توفر دورة') ?></summary>
            <?php if ($nErr): ?><div class="alert alert-error" role="alert" style="margin-top:var(--space-sm)"><?= owBi($nErr[0], $nErr[1]) ?></div><?php endif; ?>
            <form method="post" action="<?= e($selfUrl) ?>#notify" style="margin-top:var(--space-sm)"><?= csrfField() ?><input type="hidden" name="do" value="notify">
              <div class="g2">
                <div class="field"><label for="n-name"><?= owBi('Your name', 'اسمك') ?></label><input id="n-name" name="name" required maxlength="150" autocomplete="name" dir="auto" value="<?= e($nVals['name']) ?>"></div>
                <div class="field"><label for="n-phone"><?= owBi('Mobile number', 'رقم الجوال') ?></label><input id="n-phone" name="phone" type="tel" required maxlength="30" autocomplete="tel" dir="ltr" placeholder="+966 5X XXX XXXX" value="<?= e($nVals['phone']) ?>"></div>
              </div>
              <div class="field"><label for="n-email"><?= owBi('Email', 'البريد الإلكتروني') ?></label><input id="n-email" name="email" type="email" required maxlength="190" autocomplete="email" dir="ltr" value="<?= e($nVals['email']) ?>"></div>
              <div class="field"><label for="n-city"><?= owBi('City or Online', 'المدينة أو عن بعد') ?></label>
                <select id="n-city" name="city" required><option value=""><?= owIsAr() ? 'اختر' : 'Choose' ?></option>
                  <?php foreach ($leadCities as $ck => $cv): ?><option value="<?= e($ck) ?>"<?= $nVals['city'] === $ck ? ' selected' : '' ?>><?= e(owIsAr() ? $cv[1] : $cv[0]) ?></option><?php endforeach; ?>
                  <option value="online"<?= $nVals['city'] === 'online' ? ' selected' : '' ?>><?= owIsAr() ? 'عن بعد' : 'Online' ?></option>
                </select></div>
              <div class="field"><label for="n-art"><?= owBi('Which art? (optional)', 'أي فن؟ (اختياري)') ?></label>
                <select id="n-art" name="art"><option value="any"<?= $nVals['art'] === 'any' ? ' selected' : '' ?>><?= owIsAr() ? 'أي فن' : 'Any' ?></option>
                  <?php foreach (OW_COURSE_ARTS as $ak => $aw): ?><option value="<?= e($ak) ?>"<?= $nVals['art'] === $ak ? ' selected' : '' ?>><?= e(owIsAr() ? $aw[1] : $aw[0]) ?></option><?php endforeach; ?>
                </select></div>
              <div class="ci-trap" aria-hidden="true"><label for="n-web">Website</label><input id="n-web" name="website" tabindex="-1" autocomplete="off"></div>
              <p class="mini" style="font-size:var(--fs-caption)"><?= owBi('We use these details only to tell you about courses. See our', 'نستخدم هذه البيانات فقط لإخبارك عن الدورات. اطّلع على') ?> <a class="ci-priv" href="privacy.php"><?= owBi('Privacy Policy', 'سياسة الخصوصية') ?></a>.</p>
              <button class="btn btn-primary" type="submit"><?= owBi('Notify me', 'نبّهني عند توفر دورة') ?></button>
            </form>
          </details>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($inst): ?><p class="ci-back"><?php if ($instHasCourses): ?><a class="btn btn-secondary" href="institutes.php"><?= owBi('All courses', 'كل الدورات') ?></a><?php endif; ?>
      <a class="btn btn-secondary" href="art_map.php?tab=institutes"><?= owBi('Institutes on the Art Map', 'المعاهد في وجهات الفن') ?></a></p><?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
