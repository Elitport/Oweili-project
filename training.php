<?php
/* =====================================================================
 *  training.php — Art Institutes directory, for everyone
 *  Institute cards (logo, name, city, short description, active courses)
 *  with a "View Courses" panel. Filter by city (?city=...).
 *  Tables: institutes, courses (created by database_update.sql)
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/article_lib.php';

$city = trim((string) ($_GET['city'] ?? ''));
if (mb_strlen($city) > 120) $city = '';

$institutes = [];
$cities     = [];
$courses    = [];
try {
    $db = getDB();
    $cities = $db->query("SELECT city_en, MIN(city_ar) AS city_ar FROM institutes
                          WHERE is_active = 1 AND city_en <> '' GROUP BY city_en ORDER BY city_en")->fetchAll();
    $activeCourse = 'c.is_active = 1 AND (c.end_date IS NULL OR c.end_date >= CURDATE())';
    $sql = "SELECT i.id, i.name_en, i.name_ar, i.city_en, i.city_ar, i.description_en, i.description_ar,
                   i.logo_path, i.contact_email, i.contact_phone, i.website_url,
                   (SELECT COUNT(*) FROM courses c WHERE c.institute_id = i.id AND $activeCourse) AS course_count
            FROM institutes i WHERE i.is_active = 1" . ($city !== '' ? ' AND i.city_en = :city' : '') . '
            ORDER BY course_count DESC, i.name_en';
    $st = $db->prepare($sql);
    $st->execute($city !== '' ? [':city' => $city] : []);
    $institutes = $st->fetchAll();
    $ids = array_map('intval', array_column($institutes, 'id'));
    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $st = $db->prepare("SELECT c.* FROM courses c WHERE c.institute_id IN ($in) AND $activeCourse
                            ORDER BY (c.start_date IS NULL), c.start_date, c.id");
        $st->execute($ids);
        foreach ($st->fetchAll() as $c) $courses[(int) $c['institute_id']][] = $c;
    }
} catch (Throwable $e) {
    // Tables not created yet (database_update.sql) — the friendly empty message is shown.
    $institutes = $cities = $courses = [];
}

/* Contact link for "Register Interest": email first, then phone, then website. */
function instContact(array $i, string $courseTitle): string {
    $mail = trim((string) $i['contact_email']);
    if ($mail !== '' && filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        return 'mailto:' . $mail . '?subject=' . rawurlencode('Course interest via Oweili: ' . $courseTitle);
    }
    $tel = preg_replace('/[^0-9+]/', '', (string) $i['contact_phone']);
    if (strlen($tel) >= 7) return 'tel:' . $tel;
    $web = trim((string) $i['website_url']);
    if (preg_match('#^https?://#i', $web) && filter_var($web, FILTER_VALIDATE_URL)) return $web;
    return '';
}

$cityLabel = function (array $i): array {
    return [$i['city_en'] ?: $i['city_ar'], $i['city_ar'] ?: $i['city_en']];
};
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" data-title-en="Art Institutes · Oweili" data-title-ar="المعاهد الفنية · أويلي">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Art Institutes · Oweili</title>
<meta name="description" content="Find art institutes and courses near you on Oweili. ابحث عن معاهد فنية ودورات بالقرب منك على أويلي.">
<meta property="og:title" content="Art Institutes · المعاهد الفنية · Oweili">
<meta property="og:type" content="website">
<link rel="stylesheet" href="/assets/css/oweili.css">
<link rel="stylesheet" href="/assets/css/nav.css">
<link rel="stylesheet" href="/assets/css/discover.css">
<script src="/assets/js/discover.js"></script>
<style>
.filter{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:10px;margin:0 auto 28px;}
.filter label{font-size:14px;font-weight:700;color:var(--color-dark);}
.filter select{min-height:46px;min-width:220px;padding:10px 14px;border-radius:12px;border:1px solid rgba(0,0,0,0.16);background:rgba(255,255,255,0.9);font-size:15px;font-family:inherit;color:var(--color-dark);cursor:pointer;}
.filter select:hover{border-color:rgba(0,0,0,0.3);}
.result-count{text-align:center;font-size:13px;color:var(--color-body);margin:-14px 0 22px;}
.i-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;align-items:start;}
.i-card{padding:22px;display:flex;flex-direction:column;gap:12px;}
.i-top{display:flex;gap:14px;align-items:center;}
.i-logo{width:64px;height:64px;flex-shrink:0;border-radius:16px;overflow:hidden;background:#fff;border:1px solid var(--color-border);display:flex;align-items:center;justify-content:center;}
.i-logo img{width:100%;height:100%;object-fit:contain;}
.i-logo svg{width:30px;height:30px;stroke:var(--color-accent);fill:none;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round;}
.i-logo.none{background:rgba(0,102,255,0.07);border-color:rgba(0,102,255,0.15);}
.ow .i-name{font-size:18px;font-weight:700;line-height:1.35;color:var(--color-dark);margin:0;}
.i-city{display:flex;align-items:center;gap:4px;font-size:13px;color:var(--color-body);margin-top:2px;}
.i-city svg{width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2;}
.ow .i-desc{font-size:14px;line-height:1.75;color:var(--color-body);margin:0;}
.i-count{align-self:flex-start;padding:3px 12px;border-radius:999px;font-size:12px;font-weight:700;color:var(--color-accent-dark);background:rgba(0,102,255,0.08);border:1px solid rgba(0,102,255,0.2);}
details.i-courses{border-top:1px solid var(--color-border);padding-top:12px;}
details.i-courses > summary{list-style:none;display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:44px;padding:10px 20px;border-radius:999px;font-size:14px;font-weight:700;cursor:pointer;color:#fff;background:var(--color-dark);transition:background 0.2s,transform 0.2s;user-select:none;}
details.i-courses > summary::-webkit-details-marker{display:none;}
details.i-courses > summary:hover{background:#333;transform:translateY(-1px);}
details.i-courses > summary svg{width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2.4;transition:transform 0.2s;}
details.i-courses[open] > summary svg{transform:rotate(180deg);}
.when-open{display:none;}
details[open] .when-open{display:inline;}
details[open] .when-closed{display:none;}
.c-list{display:flex;flex-direction:column;gap:10px;margin-top:14px;}
.c-item{padding:14px;border-radius:14px;background:rgba(255,255,255,0.85);border:1px solid var(--color-border);}
.c-title{font-size:15px;font-weight:700;color:var(--color-dark);line-height:1.4;}
.c-meta{display:flex;flex-wrap:wrap;gap:6px 14px;margin:6px 0 10px;font-size:13px;color:var(--color-body);}
.c-meta b{color:var(--color-dark);}
.c-none{font-size:14px;line-height:1.7;color:var(--color-body);margin-top:12px;}
.c-btn{min-height:40px;padding:8px 18px;font-size:13px;}
@media (max-width:1024px){.i-grid{grid-template-columns:repeat(2,1fr);}}
@media (max-width:640px){.i-grid{grid-template-columns:1fr;}.filter select{min-width:0;flex:1 1 200px;}}
@media (prefers-reduced-motion:reduce){details.i-courses > summary svg{transition:none;}}
</style>
</head>
<body class="ow">
<div class="bg-wrap" aria-hidden="true"><video class="bg-video" id="vid" autoplay loop muted playsinline src="bg.mp4"></video></div>
<div class="veil" aria-hidden="true"></div>

<?php include __DIR__ . '/nav.php'; ?>

<main>
  <div class="d-wrap">
    <header class="d-head reveal">
      <span class="d-badge"><i aria-hidden="true"></i><?= owBi('Discover', 'اكتشف') ?></span>
      <h1><?= owBi('Art Institutes', 'المعاهد الفنية') ?></h1>
      <p><?= owBi('Find art institutes and courses near you', 'ابحث عن معاهد فنية ودورات بالقرب منك') ?></p>
    </header>

    <?php if ($cities): ?>
    <form class="filter reveal" method="get" action="training.php">
      <label for="city"><?= owBi('City', 'المدينة') ?></label>
      <select id="city" name="city" onchange="this.form.submit()">
        <option value="" class="nav-i18n" data-en="All cities" data-ar="كل المدن">All cities</option>
        <?php foreach ($cities as $c): ?>
          <option value="<?= e($c['city_en']) ?>" class="nav-i18n" data-en="<?= e($c['city_en']) ?>" data-ar="<?= e($c['city_ar'] ?: $c['city_en']) ?>"<?= $c['city_en'] === $city ? ' selected' : '' ?>><?= e($c['city_en']) ?></option>
        <?php endforeach; ?>
      </select>
      <noscript><button class="d-btn d-btn-blue" type="submit"><?= owBi('Show', 'عرض') ?></button></noscript>
    </form>
    <?php endif; ?>

    <?php if (!$institutes): ?>
      <div class="glass-card d-empty reveal">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1 2.7 3 6 3s6-2 6-3v-5"/></svg>
        <?php if ($city !== '' && $cities): ?>
          <?= owBi('No institutes in this city yet.', 'لا توجد معاهد في هذه المدينة بعد.') ?>
          <div><a class="d-btn d-btn-glass" href="training.php"><?= owBi('Show all cities', 'عرض كل المدن') ?></a></div>
        <?php else: ?>
          <?= owBi('We are building our institutes directory. Check back soon.', 'نحن نبني دليل المعاهد. عد قريباً.') ?>
          <div><a class="d-btn d-btn-pink" href="articles_list.php?type=art_education"><?= owBi('Learn about art styles', 'تعرّف على الأساليب الفنية') ?></a></div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <p class="result-count"><?= owBi(count($institutes) === 1 ? '1 institute' : count($institutes) . ' institutes',
                                        count($institutes) === 1 ? 'معهد واحد' : (count($institutes) === 2 ? 'معهدان' : count($institutes) . (count($institutes) <= 10 ? ' معاهد' : ' معهداً'))) ?></p>
      <div class="i-grid">
        <?php foreach ($institutes as $i):
          $iid = (int) $i['id'];
          $nameEn = $i['name_en'] ?: $i['name_ar'];
          $nameAr = $i['name_ar'] ?: $i['name_en'];
          [$cEn, $cAr] = $cityLabel($i);
          $logo = trim((string) $i['logo_path']);
          [$nEn, $nAr] = owCourseCountBi((int) $i['course_count']);
          $list = $courses[$iid] ?? [];
        ?>
        <article class="glass-card i-card reveal">
          <div class="i-top">
            <?php if ($logo !== '' && owSafeLogo($logo)): ?>
              <span class="i-logo"><img loading="lazy" src="<?= e($logo) ?>" alt="<?= e('Logo of ' . $nameEn . ' · شعار ' . $nameAr) ?>"></span>
            <?php else: ?>
              <span class="i-logo none" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 21h18M5 21V10l7-5 7 5v11"/><path d="M9 21v-6h6v6"/></svg></span>
            <?php endif; ?>
            <div>
              <h2 class="i-name"><?= owBi($nameEn, $nameAr) ?></h2>
              <?php if ($cEn !== ''): ?>
                <div class="i-city"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg><?= owBi($cEn, $cAr) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <?php $dEn = owExcerpt((string) ($i['description_en'] ?: $i['description_ar']), 170); $dAr = owExcerpt((string) ($i['description_ar'] ?: $i['description_en']), 170); ?>
          <?php if ($dEn !== ''): ?><p class="i-desc"><?= owBi($dEn, $dAr) ?></p><?php endif; ?>
          <span class="i-count"><?= owBi($nEn, $nAr) ?></span>

          <details class="i-courses">
            <summary>
              <span class="when-closed"><?= owBi('View Courses', 'عرض الدورات') ?></span>
              <span class="when-open"><?= owBi('Hide Courses', 'إخفاء الدورات') ?></span>
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
            </summary>
            <?php if (!$list): $link = instContact($i, $nameEn); ?>
              <p class="c-none"><?= owBi('No open courses right now. Contact the institute to ask about the next one.', 'لا توجد دورات متاحة الآن. تواصل مع المعهد لتسأل عن الدورة القادمة.') ?></p>
              <?php if ($link !== ''): ?><a class="d-btn d-btn-blue c-btn" href="<?= e($link) ?>"<?= strpos($link, 'http') === 0 ? ' target="_blank" rel="noopener"' : '' ?>><?= owBi('Contact the institute', 'تواصل مع المعهد') ?></a><?php endif; ?>
            <?php else: ?>
              <div class="c-list">
                <?php foreach ($list as $c):
                  $ctEn = $c['title_en'] ?: $c['title_ar'];
                  $ctAr = $c['title_ar'] ?: $c['title_en'];
                  [$wEn, $wAr] = owDateRangeBi($c['start_date'], $c['end_date']);
                  $price = (float) $c['price'];
                  $total = (int) $c['seats_total'];
                  $left  = (int) $c['seats_remaining'];
                  $link  = instContact($i, $ctEn);
                ?>
                <div class="c-item">
                  <div class="c-title"><?= owBi($ctEn, $ctAr) ?></div>
                  <div class="c-meta">
                    <span><?= owBi($wEn, $wAr) ?></span>
                    <span><b><?= $price <= 0 ? owBi('Free', 'مجاني') : owBi(number_format($price, $price == floor($price) ? 0 : 2) . ' SAR', number_format($price, $price == floor($price) ? 0 : 2) . ' ر.س') ?></b></span>
                    <span><?php
                      if ($total <= 0)     echo owBi('Ask the institute about seats', 'اسأل المعهد عن المقاعد');
                      elseif ($left <= 0)  echo owBi('Fully booked', 'اكتمل العدد');
                      else                 echo owBi($left . ' of ' . $total . ' seats left', 'متبقٍ ' . $left . ' من ' . $total . ' مقعداً');
                    ?></span>
                  </div>
                  <?php if ($link !== ''): ?>
                    <a class="d-btn d-btn-pink c-btn" href="<?= e($link) ?>"<?= strpos($link, 'http') === 0 ? ' target="_blank" rel="noopener"' : '' ?>><?= owBi('Register Interest', 'سجّل اهتمامك') ?></a>
                  <?php endif; ?>
                </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </details>
        </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</main>

<footer class="d-foot"><?= owBi('© ' . date('Y') . ' Oweili — real art by real artists.', '© ' . date('Y') . ' أويلي — فن حقيقي بأيدي فنانين حقيقيين.') ?> · <a href="support.php"><?= owBi('Support', 'الدعم') ?></a></footer>
</body>
</html>
