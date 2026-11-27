<?php
/* =====================================================================
 *  owner_pages.php — the inside of each owner dashboard page.
 *  Only included by owner.php after the sign-in check ($V, $page set).
 * ===================================================================== */
if (!isset($V) || !$V) { http_response_code(404); exit; }
$isOwner = $V['owner'];
$delta = function (float $now, float $before): string {
    if ($now == 0 && $before == 0) return '<span class="o-dl same">' . owBi('Same as before', 'مثل الفترة السابقة') . '</span>';
    $p = owPct($now, $before);
    if ($p === null) return '<span class="o-dl up">' . owBi('New this period', 'جديد في هذه الفترة') . '</span>';
    if ($p == 0) return '<span class="o-dl same">' . owBi('Same as before', 'مثل الفترة السابقة') . '</span>';
    $cls = $p > 0 ? 'up' : 'down'; $arrow = $p > 0 ? '▲' : '▼';
    return '<span class="o-dl ' . $cls . '">' . $arrow . ' ' . owNum(abs($p)) . '% ' . owBi('vs before', 'عن السابقة') . '</span>';
};
$periodBtns = function (array $keep = []) use ($qs): string {
    $d = owOwnerPeriod(); $h = '<div class="o-seg" role="group" aria-label="' . (owIsAr() ? 'الفترة' : 'Period') . '">';
    foreach ([1 => ['Today', 'اليوم'], 7 => ['7 days', '7 أيام'], 30 => ['30 days', '30 يوما']] as $k => $w)
        $h .= '<a class="o-sb' . ($d === $k ? ' on' : '') . '" href="' . e($qs(['d' => $k])) . '"' . ($d === $k ? ' aria-current="true"' : '') . '>' . owBi($w[0], $w[1]) . '</a>';
    return $h . '</div>';
};
$devName = ['phone' => ['Phone', 'جوال'], 'tablet' => ['iPad and tablets', 'آيباد وأجهزة لوحية'], 'computer' => ['Computer', 'كمبيوتر']];
$devIcon = function (?string $d): string {
    $p = ['phone' => '<rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/>', 'tablet' => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M11 18h2"/>',
          'computer' => '<rect x="3" y="4" width="18" height="12" rx="1"/><path d="M8 20h8M12 16v4"/>'][$d ?? ''] ?? '<circle cx="12" cy="12" r="2"/>';
    $t = ['phone' => 'Phone · جوال', 'tablet' => 'iPad · آيباد', 'computer' => 'Computer · كمبيوتر'][$d ?? ''] ?? '';
    return '<svg class="o-ic o-dev" viewBox="0 0 24 24" role="img" aria-label="' . e($t ?: (owIsAr() ? 'جهاز غير معروف' : 'Unknown device')) . '">' . $p . '</svg>';
};
$empty = function (string $en, string $ar): string { return '<p class="o-empty">' . owBi($en, $ar) . '</p>'; };

/* =============================== HOME =============================== */
if ($page === 'home'):
    $d = owOwnerPeriod(); $R = owRange($d);
    $hour = (int) (new DateTime('now', new DateTimeZone('Asia/Riyadh')))->format('G');
    $hello = $hour < 12 ? ['Good morning', 'صباح الخير'] : ['Good evening', 'مساء الخير'];
    $name = $isOwner ? owOwnerName() : owPersonName($V['user']);
    $nameAr = $isOwner ? owOwnerNameAr() : $name;
    $can = fn(string $g) => owOwnerCan($V, $g);
    /* key numbers, each compared with the period just before */
    $K = [];
    if ($can('content')) {
        $K[] = ['Visitors', 'الزوار', owKVisitors($R['from'], $R['to']), owKVisitors($R['pfrom'], $R['pto']), false, 'admin.php#stats'];
        $K[] = ['Artworks sent for review', 'أعمال أُرسلت للمراجعة', owKSent($R['from'], $R['to']), owKSent($R['pfrom'], $R['pto']), false, $isOwner ? 'admin.php#pending' : 'subadmin_dashboard.php'];
    }
    if ($can('people')) {
        $K[] = ['New artists', 'فنانون جدد', owKNewUsers('artist', $R['from'], $R['to']), owKNewUsers('artist', $R['pfrom'], $R['pto']), false, 'owner.php?p=crm&t=artist'];
        $K[] = ['New collectors', 'مقتنون جدد', owKNewUsers('collector', $R['from'], $R['to']), owKNewUsers('collector', $R['pfrom'], $R['pto']), false, 'owner.php?p=crm&t=collector'];
    }
    if ($can('money')) {
        $s = owKSales($R['from'], $R['to']); $sp = owKSales($R['pfrom'], $R['pto']);
        $K[] = ['Artwork sales', 'مبيعات الأعمال', $s['value'], $sp['value'], true, 'owner.php?p=money', owBi(owNum($s['n']) . ' sold', owNum($s['n']) . ' مباع')];
        $K[] = ['Course bookings', 'حجوزات الدورات', owKBookings($R['from'], $R['to']), owKBookings($R['pfrom'], $R['pto']), false, 'owner.php?p=orders'];
        $c = $s['commission'] + owKPartnerCommission($R['from'], $R['to']); $cp = $sp['commission'] + owKPartnerCommission($R['pfrom'], $R['pto']);
        $K[] = ['Oweili commission earned', 'عمولة أويلي المكتسبة', $c, $cp, true, 'owner.php?p=money'];
    }
    if ($isOwner) {   // the owner's six, in this order
        $by = []; foreach ($K as $k) $by[$k[0]] = $k;
        $K = array_values(array_filter([$by['Visitors'] ?? null, $by['New artists'] ?? null, ['Artworks waiting', 'أعمال بانتظار المراجعة',
             (int) owQ('SELECT COUNT(*) FROM artworks a WHERE a.is_approved = 0' . owNotDraftSql('a') . (owHasColumn('artworks', 'rejected_at') ? ' AND a.rejected_at IS NULL' : '')), null, false, 'admin.php#pending',
             owBi(owNum(owKSent($R['from'], $R['to'])) . ' sent in this period', owNum(owKSent($R['from'], $R['to'])) . ' أُرسلت في هذه الفترة')],
             $by['Artwork sales'] ?? null, $by['Course bookings'] ?? null, $by['Oweili commission earned'] ?? null]));
    }
    if ($can('messages')) {
        $mb = owHasTable('sent_messages') ? owQAll("SELECT SUM(status = 'sent' AND DATE(sent_at) BETWEEN :a AND :b) s, SUM(status = 'queued') q FROM sent_messages", [':a' => $R['from'], ':b' => $R['to']])[0] ?? [] : [];
        if (!$isOwner) {
            $K[] = ['Messages sent', 'رسائل أُرسلت', (int) ($mb['s'] ?? 0), null, false, 'owner.php?p=messages'];
            $K[] = ['Messages waiting to go out', 'رسائل بانتظار الإرسال', (int) ($mb['q'] ?? 0), null, false, 'owner.php?p=messages'];
        }
    }
    $tR = $d === 1 ? owRange(7) : $R;
    ?>
    <div class="o-bento<?= $isOwner ? '' : ' sub' ?>">
      <section class="o-t t-hi">
        <div class="o-hi-l">
          <h2 class="o-hello"><?= '<span class="bi-en" lang="en">' . e($hello[0]) . ($name !== '' ? ', ' . owBdi($name) : '') . '</span><span class="bi-ar" lang="ar">' . e($hello[1]) . ($nameAr !== '' ? '، ' . owBdi($nameAr) : '') . '</span>' ?></h2>
          <p class="o-sub"><?= $isOwner ? owBi('Here is Oweili at a glance', 'هذه أويلي في لمحة') : owBi('Your sections at a glance', 'أقسامك في لمحة') ?> · <?= owODate($R['to']) ?></p>
        </div>
        <?= $periodBtns() ?>
        <div class="o-quick">
          <?php if ($isOwner): ?>
            <a class="o-qa" href="admin.php#pending"><?= $icon('wait') ?><?= owBi('Approvals', 'الموافقات') ?></a>
            <a class="o-qa" href="owner.php?p=messages"><?= $icon('mail') ?><?= owBi('Send a message', 'إرسال رسالة') ?></a>
            <a class="o-qa" href="admin_articles.php"><?= $icon('article') ?><?= owBi('Write an article', 'كتابة مقال') ?></a>
            <a class="o-qa" href="admin_art_map.php"><?= $icon('map') ?><?= owBi('Art Map', 'وجهات') ?></a>
            <a class="o-qa" href="admin_gallery.php"><?= $icon('star') ?><?= owBi('Artist of the week', 'فنان الأسبوع') ?></a>
            <a class="o-qa" href="admin.php?settings=1"><?= $icon('gear') ?><?= owBi('Site settings', 'إعدادات الموقع') ?></a>
          <?php else: ?>
            <?php if ($can('content')): ?><a class="o-qa" href="subadmin_dashboard.php"><?= $icon('art') ?><?= owBi('Approve artworks', 'الموافقة على الأعمال') ?></a><a class="o-qa" href="admin_articles.php"><?= $icon('article') ?><?= owBi('Articles', 'المقالات') ?></a><?php endif; ?>
            <?php if ($can('people')): ?><a class="o-qa" href="owner.php?p=crm&f=due"><?= $icon('users') ?><?= owBi('Follow-ups due', 'متابعات مستحقة') ?></a><?php endif; ?>
            <?php if ($can('messages')): ?><a class="o-qa" href="owner.php?p=messages"><?= $icon('mail') ?><?= owBi('Send a message', 'إرسال رسالة') ?></a><?php endif; ?>
          <?php endif; ?>
        </div>
      </section>

      <?php if ($K): ?>
      <section class="o-kpis" style="--k:<?= count($K) ?>" aria-label="<?= owIsAr() ? 'الأرقام الرئيسية' : 'Key numbers' ?>">
        <?php foreach ($K as $k): ?>
          <a class="o-t o-kpi" href="<?= e($k[5]) ?>">
            <span class="o-kl"><?= owBi($k[0], $k[1]) ?></span>
            <span class="o-kv"><?= $k[4] ? owMoney($k[2]) : owNum($k[2]) ?></span>
            <?php if (isset($k[6])): ?><span class="o-ks"><?= $k[6] ?></span><?php endif; ?>
            <?php if ($k[3] !== null): ?><?= $delta((float) $k[2], (float) $k[3]) ?><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </section>
      <?php endif; ?>

      <?php if ($can('content') || $isOwner):
        $series = ['visitors' => ['Visitors', 'الزوار'], 'accounts' => ['New accounts', 'حسابات جديدة'], 'requests' => ['"I’m Interested"', '«أنا مهتم»'], 'bookings' => ['Course bookings', 'حجوزات الدورات']]; ?>
      <section class="o-t t-trend">
        <div class="o-th"><h2><?= owBi('Trend', 'الاتجاه') ?></h2><span class="o-thn"><?= $d === 1 ? owBi('Last 7 days', 'آخر 7 أيام') : owBi('By day', 'يوما بيوم') ?></span></div>
        <div class="o-tabs">
          <?php $i = 0; foreach ($series as $sk => $sw): ?>
            <input type="radio" name="o-tr" id="tr-<?= $sk ?>" class="o-tr-in"<?= $i++ === 0 ? ' checked' : '' ?>><label for="tr-<?= $sk ?>" class="o-tr-lb"><?= owBi($sw[0], $sw[1]) ?></label>
          <?php endforeach; ?>
          <?php foreach ($series as $sk => $sw): $vals = owDaily($sk, $tR['from'], $tR['to']); ?>
            <div class="o-tr-pane" data-s="<?= $sk ?>"><?= owSvgLine($vals, (owIsAr() ? $sw[1] : $sw[0])) ?><p class="o-thn"><?= owBi('Total', 'المجموع') ?>: <b><?= owNum(array_sum($vals)) ?></b></p></div>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

      <?php if ($isOwner): $AL = owOwnerAlerts(); ?>
      <section class="o-t t-alert">
        <div class="o-th"><h2><?= owBi('Needs you', 'يحتاجك') ?></h2><?php if ($AL): ?><span class="o-badge hot"><?= owNum(array_sum(array_column($AL, 'n'))) ?></span><?php endif; ?></div>
        <?php if (!$AL): ?><?= $empty('Nothing needs you right now', 'لا شيء يحتاجك الآن') ?><?php else: ?>
          <ul class="o-alerts"><?php foreach ($AL as $a): ?>
            <li><a href="<?= e($a['href']) ?>"><span class="o-an <?= $a['tone'] ?>"><?= owNum($a['n']) ?></span><span><?= owBi($a['en'], $a['ar']) ?></span><span class="o-go" aria-hidden="true">›</span></a></li>
          <?php endforeach; ?></ul>
        <?php endif; ?>
      </section>
      <?php endif; ?>

      <?php if ($can('content') || $isOwner): $J = owJourney($R['from'], $R['to']); $first = max(1, (int) $J['steps'][0]['n']); $hasJ = array_sum(array_column($J['steps'], 'n')) > 0; ?>
      <section class="o-t t-journey">
        <div class="o-th"><h2><?= owBi('Visitor journey', 'رحلة الزائر') ?></h2><span class="o-thn"><?= owBi('Browsers, once a day', 'المتصفحات، مرة في اليوم') ?></span></div>
        <?php if (!$hasJ): ?><?= $empty('Counting started on 24 Nov 2026. The journey shows here as visitors come', 'بدأ العد في 24 نوفمبر 2026. تظهر الرحلة هنا مع قدوم الزوار') ?><?php endif; ?>
        <ol class="o-funnel">
          <?php foreach ($J['steps'] as $i => $st): ?>
            <li class="<?= $J['worst'] === $i ? 'worst' : '' ?>">
              <span class="o-fl"><?= owBi($st['en'], $st['ar']) ?></span>
              <span class="o-fbar"><span style="width:<?= max(2, (int) round($st['n'] / $first * 100)) ?>%"></span></span>
              <span class="o-fn"><?= owNum($st['n']) ?></span>
              <?php if ($st['drop'] !== null): ?><span class="o-fd"><bdi dir="ltr">−<?= owNum($st['drop']) ?>%</bdi></span><?php else: ?><span class="o-fd"></span><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
        <?php if ($J['worst'] !== null && $hasJ): $w = $J['steps'][$J['worst']]; $pv = $J['steps'][$J['worst'] - 1]; ?>
          <p class="o-note"><?= owBi('Biggest drop: ' . $pv['en'] . ' → ' . $w['en'] . ' (−' . owNum($w['drop']) . '%)', 'أكبر انخفاض: ' . $pv['ar'] . ' ← ' . $w['ar'] . ' (' . owNum($w['drop']) . '% انخفاض)') ?></p>
        <?php endif; ?>
      </section>

      <?php $DV = owStatSum('visit', $R['from'], $R['to'], '', '', 'device'); $dvT = array_sum($DV); ?>
      <section class="o-t t-dev">
        <div class="o-th"><h2><?= owBi('Devices', 'الأجهزة') ?></h2></div>
        <?php if ($dvT === 0): ?><?= $empty('No visits counted yet in this period', 'لا زيارات محسوبة بعد في هذه الفترة') ?><?php endif; ?>
        <ul class="o-bars">
          <?php foreach ($devName as $dk => $dw): $n = (int) ($DV[$dk] ?? 0); $pc = $dvT ? (int) round($n / $dvT * 100) : 0; ?>
            <li><span class="o-bl"><?= $devIcon($dk) ?><?= owBi($dw[0], $dw[1]) ?></span><span class="o-bb"><span style="width:<?= $pc ?>%"></span></span><span class="o-bn"><?= owNum($pc) ?>%</span></li>
          <?php endforeach; ?>
        </ul>
      </section>
      <?php endif; ?>

      <?php if ($isOwner): $H = owSiteHealth(); ?>
      <section class="o-t t-health">
        <div class="o-th"><h2><?= owBi('Site health', 'صحة الموقع') ?></h2></div>
        <ul class="o-health">
          <li class="<?= $H['errors'] ? 'warn' : 'ok' ?>"><span><?= owBi('Errors in the last 24 hours', 'أخطاء آخر 24 ساعة') ?></span><b><?= owNum($H['errors']) ?></b></li>
          <li class="<?= $H['backup'] && $H['backup'] > time() - 14 * 86400 ? 'ok' : 'warn' ?>"><span><?= owBi('Newest database backup', 'أحدث نسخة احتياطية') ?></span><b><?= $H['backup'] ? owODate(date('Y-m-d', $H['backup'])) : owBi('None', 'لا يوجد') ?></b></li>
          <li class="<?= $H['demo'] ? 'warn' : 'ok' ?>"><span><?= owBi('Demo content', 'المحتوى التجريبي') ?></span><b><?= $H['demo'] ? owBi('Shown to everyone', 'ظاهر للجميع') : owBi('Off', 'مُطفأ') ?></b></li>
          <li class="info"><span><?= owBi('Online payments', 'الدفع الإلكتروني') ?></span><b><?= $H['payments'] ? owBi('On', 'مُشغّل') : owBi('Off', 'مُطفأ') ?></b></li>
          <li class="info"><span><?= owBi('Course booking', 'حجز الدورات') ?></span><b><?= $H['courses'] ? owBi('On', 'مُشغّل') : owBi('Off', 'مُطفأ') ?></b></li>
          <li class="info"><span><?= owBi('Museum tickets', 'تذاكر المتاحف') ?></span><b><?= $H['tickets'] ? owBi('On', 'مُشغّل') : owBi('Off', 'مُطفأ') ?></b></li>
        </ul>
        <a class="o-more" href="admin.php?error_log=1"><?= owBi('Open the error log', 'فتح سجل الأخطاء') ?></a>
      </section>

      <?php $ACT = owRecentActivity(8); ?>
      <section class="o-t t-act">
        <div class="o-th"><h2><?= owBi('Recent activity', 'آخر النشاط') ?></h2></div>
        <?php if (!$ACT): ?><?= $empty('No activity yet', 'لا نشاط بعد') ?><?php else: ?>
        <ul class="o-act">
          <?php foreach ($ACT as $a): [$aen, $aar] = owActionText((string) $a['action']); $who = OW_ACTOR_WORDS[$a['actor_type']] ?? ['Someone', 'شخص'];
            $nm = $a['actor_id'] ? owPersonName(['id' => $a['actor_id'], 'artist_name' => $a['artist_name'], 'full_name_en' => $a['full_name_en']]) : ''; ?>
            <li><?= $devIcon($a['device'] ?? null) ?>
              <span class="o-aw"><b><?= owBi($who[0], $who[1]) ?><?= $nm !== '' ? ' · ' . owBdi($nm) : '' ?></b> <?= owBi($aen, $aar) ?></span>
              <time class="o-at" datetime="<?= e((string) $a['created_at']) ?>"><?= owODate((string) $a['created_at'], true) ?></time></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </section>
      <?php endif; ?>

      <?php if ($can('content') || $isOwner): $TOP = owStatSum('artwork', $R['from'], $R['to'], '', '', 'item', 8);
        $titles = []; if ($TOP) foreach (owQAll('SELECT id, title_en, title_ar FROM artworks WHERE id IN (' . implode(',', array_map('intval', array_keys($TOP))) . ')') as $r) $titles[(int) $r['id']] = $r; ?>
      <section class="o-t t-top">
        <div class="o-th"><h2><?= owBi('Most viewed artworks', 'الأعمال الأكثر مشاهدة') ?></h2></div>
        <?php if (!$TOP): ?><?= $empty('No artwork views counted yet in this period', 'لا مشاهدات محسوبة بعد في هذه الفترة') ?><?php else: ?>
          <ol class="o-rank"><?php foreach ($TOP as $id => $n): $t = $titles[(int) $id] ?? null; ?>
            <li><a href="artwork.php?id=<?= (int) $id ?>"><?= $t ? owBi((string) ($t['title_en'] ?: $t['title_ar']), (string) ($t['title_ar'] ?: $t['title_en'])) : '#' . (int) $id ?></a><b><?= owNum($n) ?></b></li>
          <?php endforeach; ?></ol>
        <?php endif; ?>
        <a class="o-more" href="owner.php?p=content&d=<?= $d === 1 ? 7 : $d ?>"><?= owBi('All content statistics', 'كل إحصاءات المحتوى') ?></a>
      </section>
      <?php endif; ?>
    </div>
    <p class="o-foot-note"><?= owBi('Never counted: the owner and the team, robots, data centers and demo content', 'لا يُحسب أبدا: المالك والفريق، والروبوتات، ومراكز البيانات، والمحتوى التجريبي') ?></p>

<?php
/* =============================== ACCOUNTING =============================== */
elseif ($page === 'money'):
    $rows = owMonthReport(12); $vat = owVat(); $po = owPayoutsOpen();
    $sum = function (string $k) use ($rows) { return array_sum(array_column($rows, $k)); };
    $thisM = $rows[0] ?? null; ?>
    <div class="o-grid4">
      <div class="o-t o-kpi"><span class="o-kl"><?= owBi('Oweili commission this month', 'عمولة أويلي هذا الشهر') ?></span><span class="o-kv"><?= owMoney($thisM['commission'] ?? 0) ?></span></div>
      <div class="o-t o-kpi"><span class="o-kl"><?= owBi('Oweili commission, last 12 months', 'عمولة أويلي، آخر 12 شهرا') ?></span><span class="o-kv"><?= owMoney($sum('commission')) ?></span></div>
      <div class="o-t o-kpi"><span class="o-kl"><?= owBi('Artist payouts waiting', 'دفعات الفنانين المنتظرة') ?></span><span class="o-kv"><?= owMoney($po['artists']) ?></span><span class="o-ks"><?= owBi(owNum($po['artists_n']) . ($po['artists_n'] === 1 ? ' payout' : ' payouts'), owNum($po['artists_n']) . ' دفعة') ?></span></div>
      <div class="o-t o-kpi"><span class="o-kl"><?= owBi('Institute and museum payouts waiting', 'دفعات المعاهد والمتاحف المنتظرة') ?></span><span class="o-kv"><?= owMoney($po['partners']) ?></span><span class="o-ks"><?= owBi(owNum($po['partners_n']) . ($po['partners_n'] === 1 ? ' payout' : ' payouts'), owNum($po['partners_n']) . ' دفعة') ?></span></div>
    </div>
    <section class="o-t">
      <div class="o-th"><h2><?= owBi('Month by month', 'شهرا بشهر') ?></h2>
        <a class="btn btn-secondary btn-sm" href="owner.php?p=money&csv=1" data-no-prefetch><?= owBi('Export to Excel (CSV)', 'تصدير إلى Excel (CSV)') ?></a></div>
      <p class="o-note"><?= owBi('Commission is before VAT, each sale at the rate on its sale day. Sales are artworks marked as sold; course and ticket money counts once it is paid', 'العمولة قبل ضريبة القيمة المضافة، وكل بيع بالنسبة المعمول بها يوم بيعه. المبيعات هي الأعمال المعلّمة كمباعة، وأموال الدورات والتذاكر تُحسب بعد دفعها') ?>
        · <?= $vat['on'] ? owBi('VAT on Oweili commission: ' . owNum($vat['rate'], 1) . '% (for your reports only, not a tax invoice)', 'ضريبة القيمة المضافة على عمولة أويلي: ' . owNum($vat['rate'], 1) . '% (لتقاريرك فقط، وليست فاتورة ضريبية)') : owBi('VAT is off in Owner settings', 'ضريبة القيمة المضافة مُطفأة في إعدادات المالك') ?></p>
      <div class="o-tablewrap"><table class="o-table">
        <thead><tr><th><?= owBi('Month', 'الشهر') ?></th><th><?= owBi('Artworks sold', 'أعمال مباعة') ?></th><th><?= owBi('Artwork sales', 'مبيعات الأعمال') ?></th>
          <th><?= owBi('Commission on artworks', 'العمولة على الأعمال') ?></th><th><?= owBi('Artists’ share', 'حصة الفنانين') ?></th><th><?= owBi('Courses and tickets paid', 'الدورات والتذاكر المدفوعة') ?></th>
          <th><?= owBi('Commission on courses and tickets', 'العمولة على الدورات والتذاكر') ?></th><th><?= owBi('Institutes and museums share', 'حصة المعاهد والمتاحف') ?></th>
          <th><?= owBi('Oweili commission', 'عمولة أويلي') ?></th><?php if ($vat['on']): ?><th><?= owBi('VAT on commission', 'الضريبة على العمولة') ?></th><?php endif; ?>
          <th><?= owBi('Refunds', 'المبالغ المستردة') ?></th><th><?= owBi('Payouts paid', 'دفعات مدفوعة') ?></th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr><th scope="row"><?= owMonthBi($r['month']) ?></th><td><?= owNum($r['sold']) ?></td><td><?= owMoney($r['sales']) ?></td><td><?= owMoney($r['art_com']) ?></td><td><?= owMoney($r['artist_share']) ?></td>
              <td><?= owMoney($r['partner_sales']) ?></td><td><?= owMoney($r['partner_com']) ?></td><td><?= owMoney($r['partner_share']) ?></td><td><b><?= owMoney($r['commission']) ?></b></td>
              <?php if ($vat['on']): ?><td><?= owMoney($r['vat']) ?></td><?php endif; ?><td><?= owMoney($r['refunds']) ?></td><td><?= owMoney($r['paid_out']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot><tr><th scope="row"><?= owBi('12 months', '12 شهرا') ?></th><td><?= owNum($sum('sold')) ?></td><td><?= owMoney($sum('sales')) ?></td><td><?= owMoney($sum('art_com')) ?></td><td><?= owMoney($sum('artist_share')) ?></td>
          <td><?= owMoney($sum('partner_sales')) ?></td><td><?= owMoney($sum('partner_com')) ?></td><td><?= owMoney($sum('partner_share')) ?></td><td><b><?= owMoney($sum('commission')) ?></b></td>
          <?php if ($vat['on']): ?><td><?= owMoney($sum('vat')) ?></td><?php endif; ?><td><?= owMoney($sum('refunds')) ?></td><td><?= owMoney($sum('paid_out')) ?></td></tr></tfoot>
      </table></div>
    </section>
    <?php if ($isOwner): require_once __DIR__ . '/invoice_lib.php'; $iq = mb_substr(trim((string) ($_GET['iq'] ?? '')), 0, 60); $invs = owInvAll(200, $iq); $taxNow = owInvTaxNow(); ?>
    <section class="o-t" id="invoices">
      <div class="o-th"><h2><?= owBi('Invoices', 'الفواتير') ?></h2>
        <form method="get" action="owner.php#invoices" class="o-inline-search" role="search"><input type="hidden" name="p" value="money">
          <label class="sr-only" for="iq"><?= owBi('Search invoices', 'ابحث في الفواتير') ?></label>
          <input id="iq" name="iq" type="search" value="<?= e($iq) ?>" placeholder="<?= e(owIsAr() ? 'رقم أو اسم أو بريد' : 'Number, name or email') ?>">
          <button class="btn btn-secondary btn-sm" type="submit"><?= owBi('Search', 'بحث') ?></button></form></div>
      <p class="o-note"><?= $taxNow['tax']
          ? owBi('New invoices are tax invoices with VAT ' . owNum($taxNow['rate'], 1) . '% included and your VAT number', 'الفواتير الجديدة فواتير ضريبية شاملة ضريبة القيمة المضافة ' . owNum($taxNow['rate'], 1) . '% ومعها رقمك الضريبي')
          : owBi('New invoices are purchase receipts, "Not a tax invoice", with no VAT. They become tax invoices once you turn VAT on with a VAT number', 'الفواتير الجديدة إيصالات شراء «ليست فاتورة ضريبية» وبلا ضريبة. وتصبح فواتير ضريبية عندما تفعّل الضريبة مع رقم ضريبي') ?>
        · <?= owBi('Full ZATCA e-invoicing (Fatoora) is not built yet', 'الفوترة الإلكترونية الكاملة من هيئة الزكاة (فاتورة) لم تُبنَ بعد') ?></p>
      <?php if (!$invs): ?><p class="o-empty"><?= $iq !== '' ? owBi('No invoice matches your search', 'لا توجد فاتورة تطابق البحث') : owBi('No invoices yet. One is made with every purchase', 'لا فواتير بعد. تُنشأ فاتورة مع كل عملية شراء') ?></p>
      <?php else: ?><div class="o-tablewrap"><table class="o-table">
        <thead><tr><th><?= owBi('Number', 'الرقم') ?></th><th><?= owBi('Date', 'التاريخ') ?></th><th><?= owBi('Customer', 'العميل') ?></th><th><?= owBi('Item', 'البند') ?></th>
          <th><?= owBi('Order or booking', 'الطلب أو الحجز') ?></th><th><?= owBi('Amount', 'المبلغ') ?></th><th><?= owBi('VAT', 'الضريبة') ?></th><th><?= owBi('Type', 'النوع') ?></th><th><?= owBi('Emailed', 'أُرسلت') ?></th></tr></thead>
        <tbody><?php foreach ($invs as $iv): [$tEn, $tAr] = owInvTitle($iv); ?>
          <tr><td><a href="invoice.php?no=<?= e(rawurlencode($iv['invoice_no'])) ?>" target="_blank" rel="noopener" data-no-prefetch><bdi dir="ltr"><?= e($iv['invoice_no']) ?></bdi></a></td>
            <td><?= owODate($iv['issued_at']) ?></td><td><?= e((string) $iv['customer_name']) ?><br><span class="o-sm"><?= e((string) $iv['customer_email']) ?></span></td>
            <td><?= owBi((string) $iv['item_en'], (string) $iv['item_ar']) ?></td><td><bdi dir="ltr"><?= e((string) $iv['ref_no']) ?></bdi></td><td><?= owMoney($iv['amount']) ?></td>
            <td><?= (int) $iv['is_tax'] === 1 ? owMoney($iv['vat_amount']) : '—' ?></td><td><?= owBi($tEn, $tAr) ?></td><td><?= $iv['emailed_at'] ? owODate($iv['emailed_at']) : '—' ?></td></tr>
        <?php endforeach; ?></tbody></table></div><?php endif; ?>
    </section>
    <?php endif; ?>
    <?php if ($isOwner): ?><p class="o-foot-note"><a href="admin_bookings.php"><?= owBi('Mark course payouts as paid in Manage courses and tickets', 'سجّل دفع مستحقات الدورات في إدارة الدورات والتذاكر') ?></a> · <a href="admin_orders.php"><?= owBi('Artwork orders and escrow', 'طلبات الأعمال والضمان') ?></a> · <a href="owner.php?p=settings#vat"><?= owBi('VAT settings', 'إعدادات الضريبة') ?></a></p><?php endif; ?>

<?php
/* =============================== ORDERS (read only) =============================== */
elseif ($page === 'orders'):
    $v = (string) ($_GET['v'] ?? 'bookings'); $V2 = ['bookings' => ['Course bookings', 'حجوزات الدورات'], 'orders' => ['Orders', 'الطلبات'], 'tickets' => ['Museum tickets', 'تذاكر المتاحف'], 'payments' => ['Payments', 'المدفوعات'], 'payouts' => ['Payouts', 'الدفعات للمستحقين']];
    if (!isset($V2[$v])) $v = 'bookings'; ?>
    <p class="o-note"><?= owBi('Read only. To change something, open its own admin page', 'للعرض فقط. لتغيير أي شيء افتح صفحة الإدارة الخاصة به') ?></p>
    <div class="o-seg o-seg-wide" role="tablist"><?php foreach ($V2 as $k => $w): ?><a class="o-sb<?= $v === $k ? ' on' : '' ?>" href="owner.php?p=orders&v=<?= $k ?>"<?= $v === $k ? ' aria-current="true"' : '' ?>><?= owBi($w[0], $w[1]) ?></a><?php endforeach; ?></div>
    <section class="o-t">
    <?php
    $tbl = function (array $head, array $rows, callable $cell) use ($empty) {
        if (!$rows) { echo $empty('Nothing here yet', 'لا شيء هنا بعد'); return; }
        echo '<div class="o-tablewrap"><table class="o-table"><thead><tr>';
        foreach ($head as $h) echo '<th>' . owBi($h[0], $h[1]) . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($rows as $r) { echo '<tr>'; foreach ($cell($r) as $c) echo '<td>' . $c . '</td>'; echo '</tr>'; }
        echo '</tbody></table></div>';
    };
    $stw = fn(string $s) => '<span class="o-pill s-' . e($s) . '">' . e($s) . '</span>';
    if ($v === 'bookings') {
        $col = owHasColumn('course_registrations', 'registered_at') ? 'r.registered_at' : 'NULL';
        $hasM = owHasColumn('course_registrations', 'amount');
        $rows = owHasTable('course_registrations') ? owQAll("SELECT r.id, r.status, $col AS at, " . ($hasM ? 'r.ref_no, r.student_name, r.amount, r.paid_at' : "'' ref_no, '' student_name, 0 amount, NULL paid_at") . ", c.title_en, c.title_ar
            FROM course_registrations r LEFT JOIN courses c ON c.id = r.course_id ORDER BY r.id DESC LIMIT 50") : [];
        $tbl([['Date', 'التاريخ'], ['Booking', 'الحجز'], ['Course', 'الدورة'], ['Student', 'الطالب'], ['Amount', 'المبلغ'], ['Status', 'الحالة'], ['Paid', 'الدفع']], $rows,
            fn($r) => [owODate($r['at']), e((string) ($r['ref_no'] ?: '#' . $r['id'])), owBi((string) $r['title_en'], (string) ($r['title_ar'] ?: $r['title_en'])), e((string) $r['student_name']), owMoney($r['amount']), $stw((string) $r['status']), $r['paid_at'] ? owODate($r['paid_at']) : '—']);
    } elseif ($v === 'orders') {
        $rows = owHasTable('orders') ? owQAll('SELECT order_no, kind, title, amount, commission_amount, status, created_at FROM orders ORDER BY id DESC LIMIT 50') : [];
        echo '<p class="o-note">' . owBi('Online payments are switched off, so there are no orders yet', 'الدفع الإلكتروني مُطفأ، لذلك لا توجد طلبات بعد') . '</p>';
        $tbl([['Date', 'التاريخ'], ['Order', 'الطلب'], ['Kind', 'النوع'], ['Title', 'العنوان'], ['Amount', 'المبلغ'], ['Commission', 'العمولة'], ['Status', 'الحالة']], $rows,
            fn($r) => [owODate($r['created_at']), e((string) $r['order_no']), e((string) $r['kind']), e((string) $r['title']), owMoney($r['amount']), owMoney($r['commission_amount']), $stw((string) $r['status'])]);
    } elseif ($v === 'tickets') {
        $rows = owHasTable('museum_tickets') ? owQAll('SELECT t.ref_no, t.visit_date, t.people, t.amount, t.status, t.created_at, p.name_en, p.name_ar FROM museum_tickets t LEFT JOIN art_places p ON p.id = t.place_id ORDER BY t.id DESC LIMIT 50') : [];
        $tbl([['Date', 'التاريخ'], ['Ticket', 'التذكرة'], ['Museum', 'المتحف'], ['Visit', 'الزيارة'], ['People', 'الأشخاص'], ['Amount', 'المبلغ'], ['Status', 'الحالة']], $rows,
            fn($r) => [owODate($r['created_at']), e((string) $r['ref_no']), owBi((string) $r['name_en'], (string) ($r['name_ar'] ?: $r['name_en'])), owODate($r['visit_date']), owNum($r['people']), owMoney($r['amount']), $stw((string) $r['status'])]);
    } elseif ($v === 'payments') {
        $rows = owHasTable('booking_payments') ? owQAll('SELECT kind, ref_id, amount, gateway, test_mode, status, created_at, paid_at FROM booking_payments ORDER BY id DESC LIMIT 50') : [];
        $tbl([['Date', 'التاريخ'], ['For', 'مقابل'], ['Amount', 'المبلغ'], ['Way', 'الطريقة'], ['Test', 'تجربة'], ['Status', 'الحالة']], $rows,
            fn($r) => [owODate($r['created_at']), e($r['kind'] . ' #' . $r['ref_id']), owMoney($r['amount']), e((string) $r['gateway']), (int) $r['test_mode'] ? owBi('Yes', 'نعم') : owBi('No', 'لا'), $stw((string) $r['status'])]);
    } else {
        $a = owHasTable('payouts') ? owQAll("SELECT 'artist' who, p.amount, p.status, p.reference, p.created_at, COALESCE(NULLIF(u.artist_name,''), u.full_name_en) name FROM payouts p LEFT JOIN users u ON u.id = p.artist_id ORDER BY p.id DESC LIMIT 25") : [];
        $b = owHasTable('partner_payouts') ? owQAll("SELECT payee_kind who, amount, status, reference, created_at, CONCAT(payee_kind, ' #', payee_id) name FROM partner_payouts ORDER BY id DESC LIMIT 25") : [];
        $rows = array_merge($a, $b); usort($rows, fn($x, $y) => strcmp((string) $y['created_at'], (string) $x['created_at']));
        $tbl([['Date', 'التاريخ'], ['To', 'إلى'], ['Who', 'المستفيد'], ['Amount', 'المبلغ'], ['Status', 'الحالة'], ['Transfer reference', 'مرجع التحويل']], $rows,
            fn($r) => [owODate($r['created_at']), e((string) $r['who']), e((string) $r['name']), owMoney($r['amount']), $stw((string) $r['status']), e((string) ($r['reference'] ?: '—'))]);
    }
    ?></section>

<?php
/* =============================== PEOPLE (CRM) =============================== */
elseif ($page === 'crm'):
    $uid = (int) ($_GET['u'] ?? 0);
    if ($uid > 0 && ($P = owCrmPerson($uid))):
        $tw = OW_CRM_TYPES[$P['role']]; $msgs = owHasTable('sent_messages') ? owQAll('SELECT subject, kind, status, created_at, sent_at FROM sent_messages WHERE user_id = :u ORDER BY id DESC LIMIT 30', [':u' => $uid]) : []; ?>
      <p><a class="o-more" href="owner.php?p=crm&t=<?= e($P['role']) ?>">‹ <?= owBi('Back to ' . strtolower($tw[0]), 'العودة إلى ' . $tw[1]) ?></a></p>
      <div class="o-two">
        <section class="o-t">
          <div class="o-th"><h2><?= owBdi(owPersonName($P)) ?></h2><span class="o-pill"><?= owBi($tw[2], $tw[3]) ?></span></div>
          <dl class="o-kvl">
            <dt><?= owBi('Email', 'البريد') ?></dt><dd><a href="mailto:<?= e((string) $P['email']) ?>"><?= e((string) $P['email']) ?></a><?= (int) ($P['is_verified'] ?? 0) ? '' : ' · ' . owBi('not confirmed', 'غير مؤكد') ?></dd>
            <dt><?= owBi('Phone', 'الجوال') ?></dt><dd dir="ltr"><?= e((string) ($P['phone'] ?: '—')) ?></dd>
            <dt><?= owBi('City', 'المدينة') ?></dt><dd><?= e(trim((string) $P['city'] . ' ' . (string) ($P['country'] ?? '')) ?: '—') ?></dd>
            <dt><?= owBi('Joined', 'انضم') ?></dt><dd><?= owODate($P['created_at']) ?></dd>
            <dt><?= owBi('Last sign-in', 'آخر دخول') ?></dt><dd><?= owODate($P['last_login_at'], true) ?></dd>
            <dt><?= owBi('Status', 'الحالة') ?></dt><dd><?= (int) $P['is_banned'] ? owBi('Suspended', 'موقوف') : ($P['role'] === 'artist' && !(int) $P['is_approved'] ? owBi('Waiting for approval', 'بانتظار الموافقة') : owBi('Active', 'نشط')) ?></dd>
            <dt><?= owBi('Oweili news', 'أخبار أويلي') ?></dt><dd><?= (int) $P['notify_news'] ? owBi('Yes, wants news', 'نعم، يريد الأخبار') : owBi('No news', 'لا يريد الأخبار') ?></dd>
          </dl>
          <?php if ($isOwner): ?><a class="o-more" href="admin.php?person=<?= (int) $P['id'] ?>"><?= owBi('Full account page', 'صفحة الحساب الكاملة') ?></a><?php endif; ?>
        </section>
        <section class="o-t">
          <div class="o-th"><h2><?= owBi('Follow-up', 'المتابعة') ?></h2></div>
          <?php if (!owHasTable('crm_people')): ?><?= $empty('The follow-up notes start once the new database part is added', 'تبدأ ملاحظات المتابعة بعد إضافة جزء قاعدة البيانات الجديد') ?><?php else: ?>
          <form method="post" class="o-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="act" value="crm_save"><input type="hidden" name="uid" value="<?= (int) $P['id'] ?>">
            <label class="o-lbl" for="c-st"><?= owBi('Stage', 'المرحلة') ?></label>
            <select class="o-in" id="c-st" name="stage"><?php foreach (OW_CRM_STAGES as $k => $w): ?><option value="<?= $k ?>"<?= $P['stage'] === $k ? ' selected' : '' ?>><?= e(owIsAr() ? $w[1] : $w[0]) ?></option><?php endforeach; ?></select>
            <label class="o-lbl" for="c-fu"><?= owBi('Follow-up date', 'تاريخ المتابعة') ?></label>
            <input class="o-in" id="c-fu" type="date" name="follow_up" value="<?= e((string) ($P['follow_up'] ?? '')) ?>">
            <label class="o-lbl" for="c-nt"><?= owBi('Note (only the team sees it)', 'ملاحظة (يراها الفريق فقط)') ?></label>
            <textarea class="o-in" id="c-nt" name="note" rows="5" maxlength="4000"><?= e((string) ($P['note'] ?? '')) ?></textarea>
            <button class="btn btn-primary" type="submit"><?= owBi('Save', 'حفظ') ?></button>
          </form>
          <?php endif; ?>
        </section>
      </div>
      <section class="o-t">
        <div class="o-th"><h2><?= owBi('Message history', 'سجل الرسائل') ?></h2>
          <?php if (owOwnerCan($V, 'messages')): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="act" value="crm_pick"><input type="hidden" name="ids[]" value="<?= (int) $P['id'] ?>"><button class="btn btn-secondary btn-sm" type="submit"><?= owBi('Send a message', 'إرسال رسالة') ?></button></form><?php endif; ?></div>
        <?php if (!$msgs): ?><?= $empty('No group messages sent to this person yet', 'لم تُرسل رسائل جماعية لهذا الشخص بعد') ?><?php else: ?>
          <ul class="o-act"><?php foreach ($msgs as $m): ?><li><span class="o-pill s-<?= e($m['status']) ?>"><?= e($m['status']) ?></span><span class="o-aw"><?= e((string) $m['subject']) ?> · <?= $m['kind'] === 'news' ? owBi('News', 'أخبار') : owBi('Service', 'خدمة') ?></span><time class="o-at"><?= owODate($m['sent_at'] ?: $m['created_at'], true) ?></time></li><?php endforeach; ?></ul>
        <?php endif; ?>
      </section>
    <?php else:
      $f = ['t' => (string) ($_GET['t'] ?? 'artist'), 's' => (string) ($_GET['s'] ?? ''), 'f' => (string) ($_GET['f'] ?? ''), 'st' => (string) ($_GET['st'] ?? ''), 'q' => (string) ($_GET['q'] ?? '')];
      if (!isset(OW_CRM_TYPES[$f['t']]) && $f['t'] !== 'all') $f['t'] = 'artist';
      $pg = max(1, (int) ($_GET['pg'] ?? 1)); $L = owCrmList($f, 50, ($pg - 1) * 50); ?>
      <form class="o-t o-filters" method="get" action="owner.php" role="search">
        <input type="hidden" name="p" value="crm">
        <label class="o-fi"><span><?= owBi('Who', 'مَن') ?></span><select class="o-in" name="t"><?php foreach (array_merge(['all' => ['Everyone', 'الجميع']], OW_CRM_TYPES) as $k => $w): ?><option value="<?= $k ?>"<?= $f['t'] === $k ? ' selected' : '' ?>><?= e(owIsAr() ? $w[1] : $w[0]) ?></option><?php endforeach; ?></select></label>
        <label class="o-fi grow"><span><?= owBi('Search', 'بحث') ?></span><input class="o-in" type="search" name="q" value="<?= e($f['q']) ?>" placeholder="<?= owIsAr() ? 'الاسم أو البريد أو الجوال أو المدينة' : 'Name, email, phone or city' ?>"></label>
        <label class="o-fi"><span><?= owBi('Stage', 'المرحلة') ?></span><select class="o-in" name="s"><option value=""><?= owIsAr() ? 'كل المراحل' : 'All stages' ?></option><?php foreach (OW_CRM_STAGES as $k => $w): ?><option value="<?= $k ?>"<?= $f['s'] === $k ? ' selected' : '' ?>><?= e(owIsAr() ? $w[1] : $w[0]) ?></option><?php endforeach; ?></select></label>
        <label class="o-fi"><span><?= owBi('Follow-up', 'المتابعة') ?></span><select class="o-in" name="f"><?php foreach (['' => ['Any', 'الكل'], 'due' => ['Due today or late', 'اليوم أو متأخرة'], 'soon' => ['Coming', 'قادمة']] as $k => $w): ?><option value="<?= $k ?>"<?= $f['f'] === $k ? ' selected' : '' ?>><?= e(owIsAr() ? $w[1] : $w[0]) ?></option><?php endforeach; ?></select></label>
        <label class="o-fi"><span><?= owBi('Status', 'الحالة') ?></span><select class="o-in" name="st"><?php foreach (['' => ['Any', 'الكل'], 'active' => ['Active', 'نشط'], 'pending' => ['Waiting for approval', 'بانتظار الموافقة'], 'suspended' => ['Suspended', 'موقوف']] as $k => $w): ?><option value="<?= $k ?>"<?= $f['st'] === $k ? ' selected' : '' ?>><?= e(owIsAr() ? $w[1] : $w[0]) ?></option><?php endforeach; ?></select></label>
        <button class="btn btn-primary" type="submit"><?= owBi('Show', 'عرض') ?></button>
      </form>
      <form method="post" class="o-t" id="crm-list">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="act" value="crm_pick">
        <div class="o-th"><h2><?= owBi(owNum($L['total']) . ' people', owNum($L['total']) . ' شخصا') ?></h2>
          <?php if (owOwnerCan($V, 'messages') && $L['rows']): ?><button class="btn btn-secondary btn-sm" type="submit"><?= owBi('Message the ticked people', 'مراسلة المحددين') ?></button><?php endif; ?></div>
        <?php if (!$L['rows']): ?><?= $empty('Nobody matches these choices', 'لا أحد يطابق هذه الاختيارات') ?><?php else: ?>
        <div class="o-tablewrap"><table class="o-table o-crm">
          <thead><tr><?php if (owOwnerCan($V, 'messages')): ?><th><span class="sr-only"><?= owBi('Tick', 'تحديد') ?></span></th><?php endif; ?><th><?= owBi('Name', 'الاسم') ?></th><th><?= owBi('Type', 'النوع') ?></th><th><?= owBi('City', 'المدينة') ?></th><th><?= owBi('Joined', 'انضم') ?></th><th><?= owBi('Stage', 'المرحلة') ?></th><th><?= owBi('Follow-up', 'المتابعة') ?></th></tr></thead>
          <tbody><?php foreach ($L['rows'] as $r): $late = $r['follow_up'] && $r['follow_up'] <= date('Y-m-d'); ?>
            <tr><?php if (owOwnerCan($V, 'messages')): ?><td><input type="checkbox" name="ids[]" value="<?= (int) $r['id'] ?>" aria-label="<?= e(owPersonName($r)) ?>"></td><?php endif; ?>
              <td><a href="owner.php?p=crm&u=<?= (int) $r['id'] ?>"><b><?= owBdi(owPersonName($r)) ?></b></a><span class="o-sm"><?= e((string) $r['email']) ?></span></td>
              <td><?= owBi(OW_CRM_TYPES[$r['role']][2], OW_CRM_TYPES[$r['role']][3]) ?><?= (int) $r['is_banned'] ? ' · ' . owBi('Suspended', 'موقوف') : ($r['role'] === 'artist' && !(int) $r['is_approved'] ? ' · ' . owBi('Waiting', 'بانتظار') : '') ?></td>
              <td><?= e((string) ($r['city'] ?: '—')) ?></td><td><?= owODate($r['created_at']) ?></td>
              <td><span class="o-pill st-<?= e($r['stage']) ?>"><?= owBi(OW_CRM_STAGES[$r['stage']][0] ?? $r['stage'], OW_CRM_STAGES[$r['stage']][1] ?? $r['stage']) ?></span></td>
              <td class="<?= $late ? 'o-late' : '' ?>"><?= $r['follow_up'] ? owODate($r['follow_up']) : '—' ?></td></tr>
          <?php endforeach; ?></tbody>
        </table></div>
        <?php if ($L['total'] > 50): ?><div class="o-pager"><?php if ($pg > 1): ?><a class="btn btn-secondary btn-sm" href="<?= e($qs(['pg' => $pg - 1])) ?>"><?= owBi('Previous', 'السابق') ?></a><?php endif; ?><span><?= owNum($pg) ?> / <?= owNum((int) ceil($L['total'] / 50)) ?></span><?php if ($pg * 50 < $L['total']): ?><a class="btn btn-secondary btn-sm" href="<?= e($qs(['pg' => $pg + 1])) ?>"><?= owBi('Next', 'التالي') ?></a><?php endif; ?></div><?php endif; ?>
        <?php endif; ?>
      </form>
    <?php endif; ?>

<?php
/* =============================== CONTENT STATISTICS =============================== */
elseif ($page === 'content'):
    $d = (int) ($_GET['d'] ?? 7); if (!in_array($d, [7, 30], true)) $d = 7; $R = owRange($d);
    $lg = in_array($_GET['l'] ?? '', ['en', 'ar'], true) ? $_GET['l'] : ''; $dv = isset($devName[$_GET['dv'] ?? '']) ? $_GET['dv'] : '';
    $pages = owStatSum('page', $R['from'], $R['to'], $lg, $dv, 'item', 10);
    $arts = owStatSum('artwork', $R['from'], $R['to'], $lg, $dv, 'item', 10);
    $artists = owStatSum('artist', $R['from'], $R['to'], $lg, $dv, 'item', 10);
    $styles = owStatSum('style', $R['from'], $R['to'], $lg, $dv, 'item', 10);
    $places = owStatSum('place', $R['from'], $R['to'], $lg, $dv, 'item', 10);
    $langs = owStatSum('visit', $R['from'], $R['to'], '', $dv, 'lang'); $devs = owStatSum('visit', $R['from'], $R['to'], $lg, '', 'device');
    $PN = ['index' => ['Home', 'الرئيسية'], 'marketplace' => ['Gallery', 'المعرض'], 'explore' => ['Art Styles', 'الأساليب الفنية'], 'articles_list' => ['Art Events and Art Education lists', 'قوائم الملتقيات والتثقيف'],
           'article' => ['Articles', 'المقالات'], 'art_map' => ['Art Map', 'وجهات'], 'art_place' => ['Art Map place and region pages', 'صفحات الأماكن والمناطق في وجهات'], 'institutes' => ['Art Institutes', 'المعاهد الفنية'],
           'course' => ['Course pages', 'صفحات الدورات'], 'support' => ['Help and Support', 'المساعدة والدعم'], 'about' => ['About', 'من نحن'], 'terms' => ['Terms', 'الشروط'], 'privacy' => ['Privacy', 'الخصوصية'],
           'artwork' => ['Artwork pages', 'صفحات الأعمال'], 'artist_dashboard' => ['Artist profiles', 'ملفات الفنانين'], 'register' => ['Join', 'انضم'], 'register_artist' => ['Join as an artist', 'انضم كفنان'],
           'register_collector' => ['Join as a collector', 'انضم كمقتن'], 'register_institute' => ['Join as an institute', 'انضم كمعهد'], 'login' => ['Sign in', 'تسجيل الدخول'], 'cert_verify' => ['Verify a certificate', 'التحقق من شهادة'],
           'auctions' => ['Auctions', 'المزاد'], 'gifts' => ['Gifts', 'الهدايا'], 'commission' => ['Private commissions', 'الأعمال الخاصة'], 'community' => ['Community', 'المجتمع'], '404' => ['Page not found', 'صفحة غير موجودة']];
    $artN = []; if ($arts) foreach (owQAll('SELECT a.id, a.title_en, a.title_ar, ' . owArtistNameSql('u') . ' FROM artworks a LEFT JOIN users u ON u.id = a.artist_id WHERE a.id IN (' . implode(',', array_map('intval', array_keys($arts))) . ')') as $r) $artN[(int) $r['id']] = $r;
    $peopleN = []; if ($artists) foreach (owQAll('SELECT id, artist_name, full_name_en, full_name_ar FROM users WHERE id IN (' . implode(',', array_map('intval', array_keys($artists))) . ')') as $r) $peopleN[(int) $r['id']] = $r;
    $TYPES = $styles ? (require __DIR__ . '/art_types.php') : [];
    $placeN = []; if ($places) { try { require_once __DIR__ . '/artmap_lib.php'; foreach (owMapDirectory() as $p) $placeN[$p['region'] . '/' . $p['slug']] = $p; } catch (Throwable $e) {} }
    $list = function (string $en, string $ar, array $data, callable $name, string $cls = '') use ($empty) {
        $max = max(1, max($data ?: [0]));
        echo '<section class="o-t ' . $cls . '"><div class="o-th"><h2>' . owBi($en, $ar) . '</h2></div>';
        if (!$data) { echo $empty('Nothing counted yet in this period', 'لا شيء محسوب بعد في هذه الفترة'); echo '</section>'; return; }
        echo '<ol class="o-rank bars">';
        foreach ($data as $k => $n) echo '<li><span class="o-rn">' . $name((string) $k) . '</span><span class="o-bb"><span style="width:' . (int) round($n / $max * 100) . '%"></span></span><b>' . owNum($n) . '</b></li>';
        echo '</ol></section>';
    }; ?>
    <form class="o-t o-filters" method="get" action="owner.php">
      <input type="hidden" name="p" value="content">
      <label class="o-fi"><span><?= owBi('Period', 'الفترة') ?></span><select class="o-in" name="d"><option value="7"<?= $d === 7 ? ' selected' : '' ?>><?= owIsAr() ? '7 أيام' : '7 days' ?></option><option value="30"<?= $d === 30 ? ' selected' : '' ?>><?= owIsAr() ? '30 يوما' : '30 days' ?></option></select></label>
      <label class="o-fi"><span><?= owBi('Language', 'اللغة') ?></span><select class="o-in" name="l"><option value=""><?= owIsAr() ? 'العربية والإنجليزية' : 'Arabic and English' ?></option><option value="ar"<?= $lg === 'ar' ? ' selected' : '' ?>><?= owIsAr() ? 'العربية' : 'Arabic' ?></option><option value="en"<?= $lg === 'en' ? ' selected' : '' ?>><?= owIsAr() ? 'الإنجليزية' : 'English' ?></option></select></label>
      <label class="o-fi"><span><?= owBi('Device', 'الجهاز') ?></span><select class="o-in" name="dv"><option value=""><?= owIsAr() ? 'كل الأجهزة' : 'All devices' ?></option><?php foreach ($devName as $k => $w): ?><option value="<?= $k ?>"<?= $dv === $k ? ' selected' : '' ?>><?= e(owIsAr() ? $w[1] : $w[0]) ?></option><?php endforeach; ?></select></label>
      <button class="btn btn-primary" type="submit"><?= owBi('Show', 'عرض') ?></button>
    </form>
    <p class="o-note"><?= owBi('Each page or item counts once per browser a day. No names, no addresses, no outside tools. Counting started on 24 Nov 2026', 'تُحسب كل صفحة أو عنصر مرة واحدة لكل متصفح في اليوم. بلا أسماء ولا عناوين ولا أدوات خارجية. بدأ العد في 24 نوفمبر 2026') ?></p>
    <div class="o-grid2">
      <?php
      $list('Pages', 'الصفحات', $pages, fn($k) => isset($PN[$k]) ? owBi($PN[$k][0], $PN[$k][1]) : e($k));
      $list('Artworks', 'الأعمال الفنية', $arts, function ($k) use ($artN) { $r = $artN[(int) $k] ?? null; return $r ? '<a href="artwork.php?id=' . (int) $k . '">' . owBi((string) ($r['title_en'] ?: $r['title_ar']), (string) ($r['title_ar'] ?: $r['title_en'])) . '</a><span class="o-sm">' . owBiName((string) $r['artist_en'], (string) $r['artist_ar']) . '</span>' : '#' . (int) $k; });
      $list('Artists', 'الفنانون', $artists, function ($k) use ($peopleN) { $r = $peopleN[(int) $k] ?? null; return '<a href="artist_dashboard.php?id=' . (int) $k . '">' . owBdi($r ? owPersonName($r + ['id' => $k]) : '#' . $k) . '</a>'; });
      $list('Art Styles', 'الأساليب الفنية', $styles, function ($k) use ($TYPES) { $t = $TYPES[$k] ?? null; $en = (string) ($t['en'] ?? $t['label_en'] ?? $k); $ar = (string) ($t['ar'] ?? $t['label_ar'] ?? $en); return '<a href="explore.php#' . e($k) . '">' . owBi($en, $ar) . '</a>'; });
      $list('Art Map places', 'أماكن وجهات', $places, function ($k) use ($placeN) { $p = $placeN[$k] ?? null; return $p ? '<a href="' . e($p['url']) . '">' . owBi((string) $p['name_en'], (string) $p['name_ar']) . '</a><span class="o-sm">' . owBi((string) $p['city_en'], (string) $p['city_ar']) . '</span>' : e($k); });
      ?>
      <section class="o-t">
        <div class="o-th"><h2><?= owBi('Visits by language and device', 'الزيارات حسب اللغة والجهاز') ?></h2></div>
        <?php $lt = array_sum($langs); $dt = array_sum($devs); if (!$lt && !$dt): ?><?= $empty('Nothing counted yet in this period', 'لا شيء محسوب بعد في هذه الفترة') ?><?php else: ?>
        <ul class="o-bars">
          <?php foreach (['ar' => ['Arabic', 'العربية'], 'en' => ['English', 'الإنجليزية']] as $k => $w): $pc = $lt ? (int) round(($langs[$k] ?? 0) / $lt * 100) : 0; ?>
            <li><span class="o-bl"><?= owBi($w[0], $w[1]) ?></span><span class="o-bb"><span style="width:<?= $pc ?>%"></span></span><span class="o-bn"><?= owNum($pc) ?>%</span></li><?php endforeach; ?>
          <?php foreach ($devName as $k => $w): $pc = $dt ? (int) round(($devs[$k] ?? 0) / $dt * 100) : 0; ?>
            <li><span class="o-bl"><?= $devIcon($k) ?><?= owBi($w[0], $w[1]) ?></span><span class="o-bb"><span style="width:<?= $pc ?>%"></span></span><span class="o-bn"><?= owNum($pc) ?>%</span></li><?php endforeach; ?>
        </ul><?php endif; ?>
      </section>
    </div>

<?php
/* =============================== GROUP MESSAGES =============================== */
elseif ($page === 'messages'):
    $m = (array) ($_SESSION['owner_msg'] ?? []); $step = (string) ($_GET['step'] ?? '');
    $picked = (array) ($_SESSION['owner_picked'] ?? []);
    $who = (string) ($_GET['who'] ?? ($m['who'] ?? 'artist'));
    if ($step === '' && !isset($_GET['who'])) { $m = []; unset($_SESSION['owner_msg']); }
    $B = owMsgBatches(); $queued = array_sum(array_column($B, 'queued'));
    $WHO = ['artist' => ['All artists', 'كل الفنانين'], 'collector' => ['All collectors', 'كل المقتنين'], 'institute' => ['All institutes', 'كل المعاهد'], 'all' => ['Everyone', 'الجميع']];
    if ($picked) $WHO['picked'] = ['The people I ticked (' . count($picked) . ')', 'الأشخاص الذين حددتهم (' . count($picked) . ')']; ?>
    <div class="o-two">
      <section class="o-t">
        <div class="o-th"><h2><?= $step === 'check' ? owBi('Check before sending', 'راجع قبل الإرسال') : owBi('New message', 'رسالة جديدة') ?></h2></div>
        <?php if (!owHasTable('sent_messages')): ?><?= $empty('Group messages start once the new database part is added', 'تبدأ الرسائل الجماعية بعد إضافة جزء قاعدة البيانات الجديد') ?><?php elseif ($step === 'check' && $m): $n = (int) ($_SESSION['owner_msg_n'] ?? 0); ?>
          <p class="o-big"><?= owBi('It goes to ' . owNum($n) . ' people', 'تذهب إلى ' . owNum($n) . ' شخصا') ?></p>
          <p class="o-note"><?= $m['kind'] === 'news' ? owBi('News: only people who ticked "email me Oweili news". Each email says how to stop', 'أخبار: فقط من اختار «راسلني بأخبار أويلي». وكل رسالة تشرح كيف يوقفها') : owBi('Service message: everyone in this choice', 'رسالة خدمة: كل من في هذا الاختيار') ?> · <?= owBi('Only confirmed email addresses, never suspended accounts', 'فقط البريد المؤكد، ولا ترسل أبدا للحسابات الموقوفة') ?></p>
          <div class="o-preview"><p><b><?= e($m['subj_en'] . ' · ' . $m['subj_ar']) ?></b></p><p class="pre" dir="ltr"><?= nl2br(e($m['body_en'])) ?></p><hr><p class="pre" dir="rtl"><?= nl2br(e($m['body_ar'])) ?></p></div>
          <form method="post" class="o-row-btns">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <?php foreach (['who', 'kind', 'stage', 'subj_en', 'subj_ar', 'body_en', 'body_ar'] as $k): ?><input type="hidden" name="<?= $k ?>" value="<?= e((string) ($m[$k] ?? '')) ?>"><?php endforeach; ?>
            <a class="btn btn-secondary" href="owner.php?p=messages&step=edit"><?= owBi('Change it', 'تعديل') ?></a>
            <?php if ($n > 0): ?><button class="btn btn-primary" name="act" value="msg_send" type="submit"><?= owBi('Send to ' . owNum($n) . ' people', 'أرسل إلى ' . owNum($n) . ' شخصا') ?></button><?php endif; ?>
          </form>
        <?php else: ?>
          <form method="post" class="o-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="act" value="msg_preview">
            <div class="o-g2">
              <label><span class="o-lbl"><?= owBi('To', 'إلى') ?></span><select class="o-in" name="who"><?php foreach ($WHO as $k => $w): ?><option value="<?= $k ?>"<?= $who === $k ? ' selected' : '' ?>><?= e(owIsAr() ? $w[1] : $w[0]) ?></option><?php endforeach; ?></select></label>
              <label><span class="o-lbl"><?= owBi('Only this stage', 'هذه المرحلة فقط') ?></span><select class="o-in" name="stage"><option value=""><?= owIsAr() ? 'كل المراحل' : 'All stages' ?></option><?php foreach (OW_CRM_STAGES as $k => $w): ?><option value="<?= $k ?>"<?= ($m['stage'] ?? '') === $k ? ' selected' : '' ?>><?= e(owIsAr() ? $w[1] : $w[0]) ?></option><?php endforeach; ?></select></label>
            </div>
            <fieldset class="o-radio"><legend class="o-lbl"><?= owBi('Kind of message', 'نوع الرسالة') ?></legend>
              <label><input type="radio" name="kind" value="service"<?= ($m['kind'] ?? 'service') === 'service' ? ' checked' : '' ?>> <?= owBi('Service (about their account, artworks, bookings): everyone', 'خدمة (عن حساباتهم وأعمالهم وحجوزاتهم): الجميع') ?></label>
              <label><input type="radio" name="kind" value="news"<?= ($m['kind'] ?? '') === 'news' ? ' checked' : '' ?>> <?= owBi('News: only people who ticked "email me Oweili news"', 'أخبار: فقط من اختار «راسلني بأخبار أويلي»') ?></label>
            </fieldset>
            <div class="o-g2">
              <label><span class="o-lbl"><?= owBi('Subject in English', 'الموضوع بالإنجليزية') ?></span><input class="o-in" name="subj_en" dir="ltr" maxlength="90" value="<?= e((string) ($m['subj_en'] ?? '')) ?>" required></label>
              <label><span class="o-lbl"><?= owBi('Subject in Arabic', 'الموضوع بالعربية') ?></span><input class="o-in" name="subj_ar" dir="rtl" maxlength="90" value="<?= e((string) ($m['subj_ar'] ?? '')) ?>" required></label>
              <label><span class="o-lbl"><?= owBi('Message in English', 'الرسالة بالإنجليزية') ?></span><textarea class="o-in" name="body_en" dir="ltr" rows="7" maxlength="5000" required><?= e((string) ($m['body_en'] ?? '')) ?></textarea></label>
              <label><span class="o-lbl"><?= owBi('Message in Arabic', 'الرسالة بالعربية') ?></span><textarea class="o-in" name="body_ar" dir="rtl" rows="7" maxlength="5000" required><?= e((string) ($m['body_ar'] ?? '')) ?></textarea></label>
            </div>
            <button class="btn btn-primary" type="submit"><?= owBi('See who gets it', 'مَن يستلمها؟') ?></button>
          </form>
        <?php endif; ?>
      </section>
      <section class="o-t"<?= $queued ? ' data-sending="1"' : '' ?> id="o-batches">
        <div class="o-th"><h2><?= owBi('Sent messages', 'الرسائل المرسلة') ?></h2></div>
        <p class="o-note"><?= owBi('Sent in small groups of ' . OW_MSG_BATCH . ', one group every 2 minutes, at most ' . OW_MSG_HOUR . ' an hour, so the email server never blocks Oweili. Keep any owner page open while a message is going out', 'تُرسل على دفعات من ' . OW_MSG_BATCH . '، دفعة كل دقيقتين، وبحد أقصى ' . OW_MSG_HOUR . ' في الساعة، حتى لا يحظر خادم البريد أويلي. أبقِ أي صفحة من لوحة المالك مفتوحة أثناء الإرسال') ?></p>
        <?php if (!$B): ?><?= $empty('No group messages yet', 'لا رسائل جماعية بعد') ?><?php else: ?>
          <ul class="o-batches"><?php foreach ($B as $b): $tot = max(1, (int) $b['total']); $done = (int) $b['sent'] + (int) $b['failed'] + (int) $b['skipped']; ?>
            <li><div class="o-bt"><b><?= e((string) $b['subject']) ?></b><span class="o-sm"><?= $b['kind'] === 'news' ? owBi('News', 'أخبار') : owBi('Service', 'خدمة') ?> · <?= owODate((string) $b['created_at'], true) ?></span></div>
              <span class="o-bb"><span style="width:<?= (int) round($done / $tot * 100) ?>%"></span></span>
              <span class="o-sm"><?= owBi(owNum($b['sent']) . ' sent · ' . owNum($b['queued']) . ' waiting' . ((int) $b['failed'] ? ' · ' . owNum($b['failed']) . ' failed' : '') . ((int) $b['skipped'] ? ' · ' . owNum($b['skipped']) . ' not sent' : ''),
                                       owNum($b['sent']) . ' أُرسلت · ' . owNum($b['queued']) . ' بالانتظار' . ((int) $b['failed'] ? ' · ' . owNum($b['failed']) . ' فشلت' : '') . ((int) $b['skipped'] ? ' · ' . owNum($b['skipped']) . ' لم تُرسل' : '')) ?></span>
              <?php if ((int) $b['queued']): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="act" value="msg_stop"><input type="hidden" name="batch" value="<?= e((string) $b['batch']) ?>"><button class="btn btn-ghost btn-sm" type="submit"><?= owBi('Stop sending', 'إيقاف الإرسال') ?></button></form><?php endif; ?>
            </li><?php endforeach; ?></ul>
        <?php endif; ?>
      </section>
    </div>

<?php
/* =============================== OWNER SETTINGS AND TEAM =============================== */
elseif ($page === 'preview' && $isOwner): require_once __DIR__ . '/preview_lib.php';
    $pvDesc = ['collector' => ['Home, My collection, My invoices, bookings, favourites and the artists followed', 'الرئيسية ومجموعتي وفواتيري والحجوزات والمفضلة والفنانون المتابَعون'],
               'artist' => ['My Studio: home, artworks, sales and payouts, certificates and the rest of the menu', 'مرسمي: الرئيسية والأعمال والمبيعات والدفعات والشهادات وبقية القائمة'],
               'institute' => ['Courses, registrations, earnings and the profile of an approved institute', 'الدورات والتسجيلات والأرباح والملف الشخصي لمعهد معتمد']]; ?>
    <p class="o-note"><?= owBi('Each dashboard exactly as its users see it, filled with fictional people and artworks. Read only: nothing is saved, sent or counted, and no real person’s data is ever shown. Only you can open these pages', 'كل لوحة كما يراها مستخدموها تماما، بأشخاص وأعمال تجريبية. للعرض فقط: لا يُحفظ شيء ولا يُرسل شيء ولا يُحسب شيء، ولا تظهر بيانات أي شخص حقيقي أبدا. لا يفتح هذه الصفحات أحد غيرك') ?></p>
    <div class="o-grid3 o-pv">
      <?php foreach (OW_PREVIEW_ROLES as $k => $r): ?>
        <section class="o-t">
          <div class="o-th"><h2><?= owBi($r[0], $r[1]) ?></h2></div>
          <p class="o-note"><?= owBi($pvDesc[$k][0], $pvDesc[$k][1]) ?></p>
          <p><a class="btn btn-primary" href="preview.php?as=<?= $k ?>" target="_blank" rel="noopener" data-no-prefetch><?= owBi('Open the preview', 'افتح المعاينة') ?></a></p>
        </section>
      <?php endforeach; ?>
    </div>

<?php
elseif ($page === 'settings' && $isOwner):
    $vat = owVat(); $subs = owQAll("SELECT id, email, full_name_en, full_name_ar, artist_name FROM users WHERE role = 'sub_admin' ORDER BY id"); $SG = owSubadminGroupsAll(); ?>
    <div class="o-two">
      <section class="o-t">
        <div class="o-th"><h2><?= owBi('Your name', 'اسمك') ?></h2></div>
        <p class="o-note"><?= owBi('Used in the greeting on the dashboard. You sign in with the admin password, never with an account', 'يظهر في الترحيب في لوحة التحكم. تدخل بكلمة مرور الإدارة، وليس بحساب') ?></p>
        <form method="post" class="o-form"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="act" value="owner_name">
          <div class="o-g2"><label><span class="o-lbl"><?= owBi('Name in English', 'الاسم بالإنجليزية') ?></span><input class="o-in" id="on" name="owner_name" maxlength="60" dir="ltr" value="<?= e(owOwnerName()) ?>"></label>
            <label><span class="o-lbl"><?= owBi('Name in Arabic', 'الاسم بالعربية') ?></span><input class="o-in" id="on-ar" name="owner_name_ar" maxlength="60" dir="rtl" value="<?= e(trim((string) owSetting('owner_name_ar', ''))) ?>"></label></div>
          <button class="btn btn-primary" type="submit"><?= owBi('Save', 'حفظ') ?></button></form>
      </section>
      <section class="o-t" id="vat">
        <div class="o-th"><h2><?= owBi('VAT', 'ضريبة القيمة المضافة') ?></h2></div>
        <p class="o-note"><?= owBi('VAT is charged on the full price of every paid sale (artworks, courses, workshops, tickets) only when VAT is on, the VAT number is filled in AND the sale date is on or after the start date. Then every price visitors see already includes VAT ("VAT included") and the invoice is a tax invoice with its ZATCA QR code. Before that: prices without VAT and a purchase receipt. Oweili’s commission is always worked out on the price before VAT. Full ZATCA e-invoicing (Fatoora phase 2) is not built yet', 'تُحتسب الضريبة على السعر الكامل لكل بيع مدفوع (الأعمال والدورات والورش والتذاكر) فقط عندما تكون الضريبة مفعلة والرقم الضريبي مكتوبا وتاريخ البيع في تاريخ البدء أو بعده. عندها تظهر كل الأسعار للزوار شاملة الضريبة («شامل الضريبة») وتكون الفاتورة فاتورة ضريبية برمز QR الخاص بهيئة الزكاة. وقبل ذلك: أسعار بلا ضريبة وإيصال شراء. وتُحسب عمولة أويلي دائما على السعر قبل الضريبة. الفوترة الإلكترونية الكاملة (المرحلة الثانية من فاتورة) لم تُبنَ بعد') ?></p>
        <form method="post" class="o-form"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="act" value="vat">
          <label class="o-check"><input type="checkbox" name="on" value="1"<?= $vat['on'] ? ' checked' : '' ?>> <?= owBi('VAT on (Accounting and tax invoices)', 'تفعيل الضريبة (المحاسبة والفواتير الضريبية)') ?></label>
          <div class="o-g2"><label><span class="o-lbl"><?= owBi('Rate %', 'النسبة %') ?></span><input class="o-in" name="rate" inputmode="decimal" value="<?= e(owNum($vat['rate'], 1)) ?>" dir="ltr"></label>
            <label><span class="o-lbl"><?= owBi('VAT number (15 digits)', 'الرقم الضريبي (15 رقما)') ?></span><input class="o-in" name="number" inputmode="numeric" maxlength="15" value="<?= e($vat['number']) ?>" dir="ltr"></label>
            <label><span class="o-lbl"><?= owBi('VAT start date', 'تاريخ بدء الضريبة') ?></span><input class="o-in" name="start" type="date" data-native value="<?= e((string) ($vat['start'] ?? '')) ?>" dir="ltr"></label></div>
          <button class="btn btn-primary" type="submit"><?= owBi('Save', 'حفظ') ?></button></form>
      </section>
    </div>
    <section class="o-t" id="invaddr">
      <div class="o-th"><h2><?= owBi('Invoice address', 'عنوان الفاتورة') ?></h2></div>
      <p class="o-note"><?= owBi('Printed only inside invoice PDFs, under the seller’s name. Never shown anywhere else on the website, in the footer or in emails', 'يُطبع داخل ملفات الفواتير فقط، تحت اسم البائع. ولا يظهر في أي مكان آخر في الموقع أو التذييل أو الرسائل') ?></p>
      <form method="post" class="o-form"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="act" value="invoice_address">
        <label><span class="o-lbl"><?= owBi('Address (up to 4 lines)', 'العنوان (حتى 4 أسطر)') ?></span><textarea class="o-in" name="invoice_address" rows="4" maxlength="400" dir="auto" style="min-height:110px"><?= e(trim((string) owSetting('invoice_address', ''))) ?></textarea></label>
        <button class="btn btn-primary" type="submit"><?= owBi('Save', 'حفظ') ?></button></form>
    </section>
    <section class="o-t">
      <div class="o-th"><h2><?= owBi('Sub-admins: what each one can see', 'المشرفون: ما يراه كل واحد') ?></h2></div>
      <p class="o-note"><?= owBi('Tick the menu groups for each sub-admin. Money, messages and people stay hidden unless you tick them. Settings and the classic admin pages are always yours only', 'حدّد مجموعات القائمة لكل مشرف. يبقى المال والرسائل والأشخاص مخفيا ما لم تحدده. الإعدادات وصفحات الإدارة السابقة لك وحدك دائما') ?>
        · <?= owBi('"Institute documents" lets that sub-admin open the institutes’ private registration and licence documents', '«مستندات المعاهد» يسمح لهذا المشرف بفتح مستندات التسجيل والترخيص الخاصة بالمعاهد') ?>
        · <?= owBi('"Identity documents" lets that sub-admin open artists’ identity documents and institutes’ National Address certificates and photos (every opening is recorded)', '«وثائق الهوية» يسمح لهذا المشرف بفتح وثائق هوية الفنانين وشهادات العنوان الوطني وصور المعاهد (ويُسجَّل كل اطلاع)') ?>
        · <?= owBi('To make someone a sub-admin: Artists or Collectors list in the classic admin page → role "Sub admin"', 'لجعل شخص مشرفا: قائمة الفنانين أو المقتنين في صفحة الإدارة السابقة ← الدور «Sub admin»') ?></p>
      <?php if (!$subs): ?><?= $empty('No sub-admins yet', 'لا يوجد مشرفون بعد') ?><?php else: ?>
      <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="act" value="subadmins">
        <div class="o-tablewrap"><table class="o-table"><thead><tr><th><?= owBi('Sub-admin', 'المشرف') ?></th><?php foreach (OW_OWNER_GROUPS as $g => $w): ?><th><?= owBi($w[0], $w[1]) ?></th><?php endforeach; ?><th><?= owBi('Institute documents', 'مستندات المعاهد') ?></th><th><?= owBi('Identity documents', 'وثائق الهوية') ?></th></tr></thead>
          <tbody><?php foreach ($subs as $s): $have = (array) ($SG[(string) $s['id']] ?? []); ?>
            <tr><th scope="row"><?= owBdi(owPersonName($s)) ?><span class="o-sm"><?= e((string) $s['email']) ?></span></th>
              <?php foreach (OW_OWNER_GROUPS as $g => $w): ?><td><input type="checkbox" name="g[<?= (int) $s['id'] ?>][]" value="<?= $g ?>"<?= in_array($g, $have, true) ? ' checked' : '' ?> aria-label="<?= e(owPersonName($s) . ' · ' . (owIsAr() ? $w[1] : $w[0])) ?>"></td><?php endforeach; ?>
              <td><?php require_once __DIR__ . '/inst_lib.php'; ?><input type="checkbox" name="instdocs[<?= (int) $s['id'] ?>]" value="1"<?= in_array((int) $s['id'], owInstDocsSubadmins(), true) ? ' checked' : '' ?> aria-label="<?= e(owPersonName($s) . ' · ' . (owIsAr() ? 'مستندات المعاهد' : 'Institute documents')) ?>"></td>
              <td><?php require_once __DIR__ . '/identity_lib.php'; ?><input type="checkbox" name="iddocs[<?= (int) $s['id'] ?>]" value="1"<?= in_array((int) $s['id'], owIdSubadmins(), true) ? ' checked' : '' ?> aria-label="<?= e(owPersonName($s) . ' · ' . (owIsAr() ? 'وثائق الهوية' : 'Identity documents')) ?>"></td></tr>
          <?php endforeach; ?></tbody></table></div>
        <button class="btn btn-primary" type="submit"><?= owBi('Save', 'حفظ') ?></button>
      </form><?php endif; ?>
    </section>
    <?php require_once __DIR__ . '/identity_lib.php'; ?>
    <section class="o-t" id="idkeep">
      <div class="o-th"><h2><?= owBi('Identity documents: how long they are kept', 'وثائق الهوية: مدة الاحتفاظ') ?></h2></div>
      <p class="o-note"><?= owBi('Identity documents are kept after approval, and after the account closes (deleted, or the sign-up refused) for this many years. Then the files and the number are deleted automatically. The sign-up tick and the Privacy Policy say the same number', 'تُحفظ وثائق الهوية بعد الموافقة، وبعد إغلاق الحساب (حذفه أو رفض التسجيل) لهذا العدد من السنوات، ثم تُحذف الملفات والرقم تلقائيا. ويذكر خيار الموافقة عند التسجيل وسياسة الخصوصية الرقم نفسه') ?></p>
      <form method="post" class="o-form"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="act" value="id_keep">
        <label><span class="o-lbl"><?= owBi('Years after the account closes (1 to 20)', 'سنوات بعد إغلاق الحساب (1 إلى 20)') ?></span><input class="o-in" name="years" type="number" min="1" max="20" step="1" inputmode="numeric" dir="ltr" value="<?= owIdKeepYears() ?>" style="max-width:140px"></label>
        <button class="btn btn-primary" type="submit"><?= owBi('Save', 'حفظ') ?></button></form>
    </section>
    <section class="o-t">
      <div class="o-th"><h2><?= owBi('More settings', 'إعدادات أخرى') ?></h2></div>
      <div class="o-quick">
        <a class="o-qa" href="admin.php?settings=1"><?= $icon('gear') ?><?= owBi('Site settings and switches', 'إعدادات الموقع والمفاتيح') ?></a>
        <a class="o-qa" href="admin.php?error_log=1"><?= $icon('old') ?><?= owBi('Error log', 'سجل الأخطاء') ?></a>
        <a class="o-qa" href="admin.php?mail_check=1"><?= $icon('mail') ?><?= owBi('Email check', 'فحص البريد') ?></a>
        <a class="o-qa" href="admin.php?photo_check=1"><?= $icon('art') ?><?= owBi('Photo check', 'فحص الصور') ?></a>
      </div>
    </section>
<?php endif; ?>
