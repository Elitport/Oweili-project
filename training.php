<?php
/* =====================================================================
 *  training.php: Art Institutes, for everyone.
 *  Top: "Upcoming Workshops" (5 planned workshops, Coming Soon + Notify Me,
 *  moved here from the retired Studio page). Then the institutes directory:
 *  institute cards (logo, name, city, short description, active courses)
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
<?php require_once __DIR__ . '/seo_lib.php'; owSeo(['en' => 'Art Institutes', 'ar' => 'المعاهد الفنية', 'desc_en' => 'Find the right place to learn art, close to home.', 'desc_ar' => 'اعثر على المكان المناسب لتتعلّم الفن، قريباً منك.', 'path' => 'training.php']); ?>
<meta property="og:title" content="Art Institutes · المعاهد الفنية · Oweili">
<meta property="og:type" content="website">
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/nav.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/discover.css') ?>">
<script src="<?= owAsset('assets/js/discover.js') ?>"></script>
<style>
/* Art Institutes — layout only; type, colors, buttons and cards come from oweili.css */
.filter{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:var(--space-sm);margin:0 auto var(--space-lg);}
.filter label{font-size:var(--fs-label);font-weight:700;color:var(--color-dark);}
.filter select{min-width:240px;width:auto;cursor:pointer;}
.result-count{text-align:center;font-size:var(--fs-caption);color:var(--color-body);margin:calc(-1 * var(--space-sm)) 0 var(--space-md);}
.i-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:var(--space-md);align-items:start;}
.i-card{display:flex;flex-direction:column;gap:var(--space-sm);}
.i-top{display:flex;gap:var(--space-sm);align-items:center;}
.i-logo{width:64px;height:64px;flex-shrink:0;border-radius:var(--radius-card);overflow:hidden;background:var(--color-surface);border:1px solid var(--color-border);display:flex;align-items:center;justify-content:center;}
.i-logo img{width:100%;height:100%;object-fit:contain;}
.i-logo svg{width:30px;height:30px;stroke:var(--color-institute);fill:none;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round;}
.i-logo.none{background:rgba(var(--rgb-neutral),0.07);border-color:rgba(var(--rgb-neutral),0.18);}
.i-name{font-size:var(--fs-subtitle);line-height:1.35;margin:0;}
.i-city{display:flex;align-items:center;gap:4px;font-size:var(--fs-caption);color:var(--color-body);margin-top:2px;}
.i-city svg{width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2;}
.i-desc{margin:0;}
.i-count{align-self:flex-start;padding:2px 10px;border-radius:var(--radius-pill);font-size:var(--fs-min);font-weight:700;color:var(--color-accent-dark);background:rgba(var(--rgb-accent),0.08);border:1px solid rgba(var(--rgb-accent),0.2);}
details.i-courses{border-top:1px solid var(--color-border);padding-top:var(--space-sm);}
details.i-courses > summary{list-style:none;user-select:none;}
details.i-courses > summary::-webkit-details-marker{display:none;}
details.i-courses > summary svg{width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2.4;transition:transform var(--ease);}
details.i-courses[open] > summary svg{transform:rotate(180deg);}
.when-open{display:none;}
details[open] .when-open{display:inline;}
details[open] .when-closed{display:none;}
.c-list{display:flex;flex-direction:column;gap:var(--space-xs);margin-top:var(--space-sm);}
.c-item{padding:var(--space-sm);border-radius:12px;background:var(--color-surface);border:1px solid var(--color-border);}
.c-item:nth-child(even){background:var(--color-surface-2);}
.c-title{font-size:var(--fs-body);font-weight:700;color:var(--color-dark);line-height:1.4;}
.c-meta{display:flex;flex-wrap:wrap;gap:4px var(--space-sm);margin:6px 0 var(--space-sm);font-size:var(--fs-caption);color:var(--color-body);}
.c-meta b{color:var(--color-dark);}
.c-none{margin-top:var(--space-sm);}
.ws{margin:0 0 var(--space-2xl);}
.ws-head{text-align:center;margin:0 auto var(--space-lg);max-width:640px;}
.ws-head h2{font-size:var(--fs-section);margin:0 0 var(--space-xs);}
.ws-head p{margin:0;color:var(--color-body);}
.ws-grid{align-items:stretch;}
.ws-card{height:100%;}
.ws-icon{background:var(--color-tint-pink);border-color:rgba(var(--rgb-primary),0.3);}
.ws-icon svg{stroke:var(--color-dark);}
.ws-tag{font-size:var(--fs-min);font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--color-body);}
[dir="rtl"] .ws-tag{letter-spacing:0;font-size:13px;}
.ws-soon{align-self:flex-start;padding:2px 10px;border-radius:var(--radius-pill);font-size:var(--fs-min);font-weight:700;color:var(--color-dark);background:var(--color-tint-pink);border:1px solid rgba(var(--rgb-primary),0.35);}
.ws-card .btn{margin-top:auto;align-self:flex-start;}
.dir-head{text-align:center;margin:0 0 var(--space-md);}
.dir-head h2{font-size:var(--fs-section);margin:0;}
@media (max-width:1024px){.i-grid{grid-template-columns:repeat(2,1fr);}}
@media (max-width:640px){.i-grid{grid-template-columns:1fr;}.filter select{min-width:0;flex:1 1 200px;}}
</style>
</head>
<body class="ow">
<div class="bg-wrap" aria-hidden="true"><video class="bg-video" id="vid" autoplay loop muted playsinline src="<?= owBase() ?>bg.mp4"></video></div>
<div class="veil" aria-hidden="true"></div>

<?php include __DIR__ . '/nav.php'; ?>

<main>
  <div class="d-wrap">
    <header class="d-head reveal">
      <span class="d-badge"><i aria-hidden="true"></i><?= owBi('Discover', 'اكتشف') ?></span>
      <h1><?= owBi('Art Institutes', 'المعاهد الفنية') ?></h1>
      <p><?= owBi('Find the right place to learn art, close to home.', 'اعثر على المكان المناسب لتتعلّم الفن، قريباً منك.') ?></p>
    </header>

    <!-- Upcoming Workshops (moved here from the retired Studio page). Planned, so "Coming Soon" + Notify Me (rule 25). -->
    <section class="ws" aria-labelledby="ws-h">
      <div class="ws-head reveal">
        <h2 id="ws-h"><?= owBi('Upcoming Workshops', 'ورش العمل القادمة') ?></h2>
        <p><?= owBi('Hands-on workshops with Saudi art institutes are on their way. Be the first to know.', 'ورش عمل تطبيقية مع معاهد الفنون السعودية في الطريق. كن أول من يعرف.') ?></p>
      </div>
      <div class="i-grid ws-grid">
        <article class="card i-card ws-card reveal">
          <div class="i-top">
            <span class="i-logo ws-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3C8.5 7.5 6 10.6 6 14a6 6 0 0 0 12 0c0-3.4-2.5-6.5-6-11z"/></svg></span>
            <div>
              <span class="ws-tag"><?= owBi('Watercolor', 'ألوان مائية') ?></span>
              <h3 class="i-name"><?= owBi('Desert Light in Watercolor', 'ضوء الصحراء بالألوان المائية') ?></h3>
            </div>
          </div>
          <p class="i-desc"><?= owBi('Capture the warmth of Saudi desert light. Layer by layer, until the dunes glow.', 'التقط دفء ضوء الصحراء السعودية. طبقة بعد طبقة، حتى تتوهّج الكثبان.') ?></p>
          <span class="ws-soon"><?= owBi('Coming Soon', 'قريباً') ?></span>
          <a class="btn btn-dark" href="register.php"><?= owBi('Notify Me', 'أبلغني') ?></a>
        </article>
        <article class="card i-card ws-card reveal">
          <div class="i-top">
            <span class="i-logo ws-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="16" cy="8" r="3"/><path d="M3 18c4-4 8-4 18-1M3 21h18"/></svg></span>
            <div>
              <span class="ws-tag"><?= owBi('Oil Painting', 'رسم زيتي') ?></span>
              <h3 class="i-name"><?= owBi('Desert Landscapes in Oil', 'مناظر الصحراء بالرسم الزيتي') ?></h3>
            </div>
          </div>
          <p class="i-desc"><?= owBi('Paint wide horizons and golden sunset skies. Classic oil techniques, step by step.', 'ارسم الآفاق الواسعة وسماء الغروب الذهبية. تعلّم تقنيات الزيت الكلاسيكية خطوة بخطوة.') ?></p>
          <span class="ws-soon"><?= owBi('Coming Soon', 'قريباً') ?></span>
          <a class="btn btn-dark" href="register.php"><?= owBi('Notify Me', 'أبلغني') ?></a>
        </article>
        <article class="card i-card ws-card reveal">
          <div class="i-top">
            <span class="i-logo ws-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/></svg></span>
            <div>
              <span class="ws-tag"><?= owBi('Digital Art', 'فن رقمي') ?></span>
              <h3 class="i-name"><?= owBi('Digital Arabic Calligraphy', 'الخط العربي الرقمي') ?></h3>
            </div>
          </div>
          <p class="i-desc"><?= owBi('Love Arabic letters and geometric patterns. Then bring them to life on screen.', 'اعشق الحرف العربي والزخارف الهندسية. ثم امنحها الحياة على الشاشة.') ?></p>
          <span class="ws-soon"><?= owBi('Coming Soon', 'قريباً') ?></span>
          <a class="btn btn-dark" href="register.php"><?= owBi('Notify Me', 'أبلغني') ?></a>
        </article>
        <article class="card i-card ws-card reveal">
          <div class="i-top">
            <span class="i-logo ws-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 21V9l7-5 7 5v12"/><path d="M10 21v-6h4v6M9 11h1M14 11h1"/></svg></span>
            <div>
              <span class="ws-tag"><?= owBi('Charcoal', 'فحم') ?></span>
              <h3 class="i-name"><?= owBi('Saudi Heritage Drawing', 'رسم التراث السعودي') ?></h3>
            </div>
          </div>
          <p class="i-desc"><?= owBi('Draw carved doors and Najdi mud houses in charcoal and ink. Every line holds a story.', 'ارسم الأبواب المنقوشة والبيوت النجدية بالفحم والحبر. كل خط يحفظ حكاية.') ?></p>
          <span class="ws-soon"><?= owBi('Coming Soon', 'قريباً') ?></span>
          <a class="btn btn-dark" href="register.php"><?= owBi('Notify Me', 'أبلغني') ?></a>
        </article>
        <article class="card i-card ws-card reveal">
          <div class="i-top">
            <span class="i-logo ws-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="11" height="9" rx="1"/><rect x="10" y="10" width="11" height="9" rx="1"/></svg></span>
            <div>
              <span class="ws-tag"><?= owBi('Mixed Media', 'وسائط مختلطة') ?></span>
              <h3 class="i-name"><?= owBi('Contemporary Saudi Expression', 'التعبير السعودي المعاصر') ?></h3>
            </div>
          </div>
          <p class="i-desc"><?= owBi('Mix collage, color and photography. Tell the story of Saudi Arabia today, in your own voice.', 'امزج الكولاج والألوان والتصوير. احكِ قصة السعودية اليوم بصوتك أنت.') ?></p>
          <span class="ws-soon"><?= owBi('Coming Soon', 'قريباً') ?></span>
          <a class="btn btn-dark" href="register.php"><?= owBi('Notify Me', 'أبلغني') ?></a>
        </article>
      </div>
    </section>

    <div class="dir-head reveal"><h2><?= owBi('Institutes Directory', 'دليل المعاهد') ?></h2></div>

    <?php if ($cities): ?>
    <form class="filter reveal" method="get" action="training.php">
      <label for="city"><?= owBi('City', 'المدينة') ?></label>
      <select class="input" id="city" name="city" onchange="this.form.submit()">
        <option value="" class="nav-i18n" data-en="All cities" data-ar="كل المدن">All cities</option>
        <?php foreach ($cities as $c): ?>
          <option value="<?= e($c['city_en']) ?>" class="nav-i18n" data-en="<?= e($c['city_en']) ?>" data-ar="<?= e($c['city_ar'] ?: $c['city_en']) ?>"<?= $c['city_en'] === $city ? ' selected' : '' ?>><?= e($c['city_en']) ?></option>
        <?php endforeach; ?>
      </select>
      <noscript><button class="btn btn-secondary" type="submit"><?= owBi('Show', 'عرض') ?></button></noscript>
    </form>
    <?php endif; ?>

    <?php if (!$institutes): ?>
      <div class="card empty-state d-empty reveal">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1 2.7 3 6 3s6-2 6-3v-5"/></svg>
        <?php if ($city !== '' && $cities): ?>
          <?= owBi('No institutes in this city yet. Try another city.', 'لا توجد معاهد في هذه المدينة بعد. جرّب مدينة أخرى.') ?>
          <div><a class="btn btn-secondary" href="training.php"><?= owBi('Show all cities', 'عرض كل المدن') ?></a></div>
        <?php else: ?>
          <?= owBi('We are gathering the best art institutes for you. Check back soon.', 'نجمع لك أفضل المعاهد الفنية. عد قريباً.') ?>
          <div><a class="btn btn-primary" href="articles_list.php?type=art_education"><?= owBi('Learn about art styles', 'تعرّف على الأساليب الفنية') ?></a></div>
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
        <article class="card i-card reveal">
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
            <summary class="btn btn-dark">
              <span class="when-closed"><?= owBi('View Courses', 'عرض الدورات') ?></span>
              <span class="when-open"><?= owBi('Hide Courses', 'إخفاء الدورات') ?></span>
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
            </summary>
            <?php if (!$list): $link = instContact($i, $nameEn); ?>
              <p class="c-none"><?= owBi('No open courses right now. Ask the institute when the next one starts.', 'لا توجد دورات متاحة الآن. اسأل المعهد عن موعد الدورة القادمة.') ?></p>
              <?php if ($link !== ''): ?><a class="btn btn-secondary btn-sm c-btn" href="<?= e($link) ?>"<?= strpos($link, 'http') === 0 ? ' target="_blank" rel="noopener"' : '' ?>><?= owBi('Contact the institute', 'تواصل مع المعهد') ?></a><?php endif; ?>
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
                    <a class="btn btn-primary btn-sm c-btn" href="<?= e($link) ?>"<?= strpos($link, 'http') === 0 ? ' target="_blank" rel="noopener"' : '' ?>><?= owBi('Register Interest', 'سجّل اهتمامك') ?></a>
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

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
