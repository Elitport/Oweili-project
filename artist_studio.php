<?php
/* =====================================================================
 *  artist_studio.php — "My Studio" («مرسمي»), the artist's ONLY home (owner, 11 Nov 2026):
 *  the old dashboard address (artist_dashboard.php without ?id) comes here, and
 *  artist_dashboard.php?id=N is only the public profile. Only the signed-in
 *  artist's OWN data; everyone else is sent away. On top: one summary
 *  (artworks, sold, the artist's share after the commission, waiting for approval)
 *  worked out once below, so every number is the same everywhere.
 *  Rebuilt 25 Nov 2026 (owner) with the side menu of the owner and collector
 *  dashboards (owner.css, owner.js, collector.css, artist.css). Old ?tab= links keep
 *  working; no tab = Home (Bento: Needs you now, numbers, views by day).
 *  Pages (?tab=):
 *    home      — greeting, Needs you now, numbers, views of the last 30 days,
 *                newest artworks and recent sales
 *    works     — every artwork with its status (draft, in review, approved,
 *                not approved + the reason, sold) and its numbers: views,
 *                favorites, "I'm Interested" requests. Never buyer names,
 *                phones or emails: Oweili is the middle party.
 *    articles  — the artist's own articles (written in my_articles.php,
 *                published only after the admin approves)
 *    commissions — private commissions the admin assigned to this artist
 *                (artist_commission.php; photos only after the
 *                confidentiality agreement)
 *    account   — email, password, notification settings
 *    activity  — what the artist did and when
 *    earnings  — sales and payouts: every sale with the commission and the share, the
 *                orders and their payout ("On hold" while a complaint is open, never
 *                the collector's words or name), 12 months, receipt photos
 *    packing   — the packing guide; certs — certificates; workshops: artist_workshops.php
 *  Tables: users, artworks, favorites, artwork_inquiries, purchases,
 *          payouts, orders, page_stats, artist_follows, artist_workshop_requests,
 *          artist_workshops, activity_log, sessions
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/account_lib.php';
require_once __DIR__ . '/approval_lib.php';
require_once __DIR__ . '/cert_lib.php';

if (!isLoggedIn()) { header('Location: login.php?next=artist_studio.php'); exit; }
$me = currentUser();
if (!$me) { header('Location: login.php'); exit; }
if (($me['role'] ?? '') !== 'artist') { header('Location: ' . dashboardUrl($me['role'] ?? '')); exit; }
$uid = (int) $me['id'];

$TABS = ['home', 'works', 'certs', 'articles', 'commissions', 'account', 'activity', 'earnings', 'packing'];
/* the Commissions tab follows the Commissions switch in Admin → Settings (owner, 13 Nov 2026); an artist who already
   has a commission keeps the tab, so that work is never lost */
require_once __DIR__ . '/gallery_lib.php';
$commTabOn = owCommissionsPageOn();
if (!$commTabOn) {
    try { $q = getDB()->prepare("SELECT COUNT(*) FROM commissions WHERE artist_id = :u AND status <> 'cancelled'"); $q->execute([':u' => $uid]); $commTabOn = (int) $q->fetchColumn() > 0; }
    catch (Throwable $e) { $commTabOn = false; }
}
if (!$commTabOn) $TABS = array_values(array_diff($TABS, ['commissions']));
$tab  = in_array($_GET['tab'] ?? '', $TABS, true) ? $_GET['tab'] : 'home';
$tabLink = fn(string $t) => 'artist_studio.php?tab=' . $t;
/* Artist workshops (owner, 25 Nov 2026): only while the switch "Artist workshops" is on (workshop_lib.php) */
require_once __DIR__ . '/workshop_lib.php';
$wsOn = owWsOn() && owWsReady();
if ($wsOn) { array_splice($TABS, 5, 0, ['workshops']); if (($_GET['tab'] ?? '') === 'workshops') $tab = 'workshops'; }
$wsErrors = []; $wsOld = [];

/* ---------------------------------------------------------------------
 *  Changes (POST → back to the page)
 * ------------------------------------------------------------------- */
$err = ''; $old = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $do = (string) ($_POST['do'] ?? '');
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $err = 'csrf';
    } elseif ($do === 'email') {
        $err = owChangeEmail($me, (string) ($_POST['new_email'] ?? ''), (string) ($_POST['current_password'] ?? ''));
        $old = ['new_email' => (string) ($_POST['new_email'] ?? '')];
        if ($err === '') { header('Location: ' . $tabLink('account') . '&done=email'); exit; }
    } elseif ($do === 'password') {
        $err = owChangePassword($me, (string) ($_POST['current_password'] ?? ''), (string) ($_POST['new_password'] ?? ''), (string) ($_POST['confirm_password'] ?? ''));
        if ($err === '') { header('Location: ' . $tabLink('account') . '&done=password'); exit; }
    } elseif ($do === 'notify') {
        if (owHasColumn('users', 'notify_inquiries')) {
            getDB()->prepare('UPDATE users SET notify_inquiries = :i, notify_news = :n WHERE id = :id')
                   ->execute([':i' => isset($_POST['notify_inquiries']) ? 1 : 0, ':n' => isset($_POST['notify_news']) ? 1 : 0, ':id' => $uid]);
            owLog('notify_update', 'user', $uid, '', ['type' => 'artist', 'id' => $uid]);
        }
        header('Location: ' . $tabLink('account') . '&done=notify'); exit;
    } elseif ($wsOn && $do === 'ws_request') {
        if (owWsSendRequest($me, $wsErrors)) { header('Location: ' . $tabLink('workshops') . '&done=ws_request'); exit; }
        $tab = 'workshops'; $wsOld = $_POST;
    } elseif ($wsOn && $do === 'ws_save') {
        $wid = owWsSave($uid, (int) ($_POST['ws_id'] ?? 0), $wsErrors);
        if ($wid > 0) { header('Location: ' . $tabLink('workshops') . '&done=ws_saved'); exit; }
        $tab = 'workshops'; $wsOld = $_POST;
    } elseif ($do === 'resend') {
        $err = owResendVerify($me);
        if ($err === '') { header('Location: ' . $tabLink($tab) . '&done=resend'); exit; }
    }
    if ($err !== '' && !in_array($do, ['resend', 'ws_request', 'ws_save'], true)) $tab = 'account';
}

/* ---------------------------------------------------------------------
 *  This artist's data
 * ------------------------------------------------------------------- */
$works = [];
try {
    $favSql = owHasTable('favorites') ? '(SELECT COUNT(*) FROM favorites f WHERE f.artwork_id = a.id)' : '0';
    $inqSql = owHasTable('artwork_inquiries') ? '(SELECT COUNT(*) FROM artwork_inquiries q WHERE q.artwork_id = a.id)' : '0';
    $viewSql = owHasColumn('artworks', 'view_count') ? 'a.view_count' : '0';
    $reason  = owHasColumn('artworks', 'rejected_reason') ? 'a.rejected_reason' : 'NULL';
    $st = getDB()->prepare(
        "SELECT a.*, a.id, a.title_en, a.title_ar, a.image_url, a.price, a.status AS sale_status, a.created_at,
                " . owArtworkStatusSql('a') . " AS state, $reason AS reason,
                $viewSql AS views, $favSql AS favs, $inqSql AS inquiries
         FROM artworks a WHERE a.artist_id = :u ORDER BY a.created_at DESC, a.id DESC");
    $st->execute([':u' => $uid]);
    $works = $st->fetchAll();
} catch (Throwable $e) { error_log('artist_studio works: ' . $e->getMessage()); }

$tot = ['views' => 0, 'favs' => 0, 'inquiries' => 0];
foreach ($works as $w) foreach ($tot as $k => $_) $tot[$k] += (int) $w[$k];

/* The one summary (owner, 11 Nov 2026). Artworks = every piece sent to Oweili (drafts in progress are counted
   apart); sold = marked sold; share = each sale minus the commission in force on its sale date;
   waiting = pieces in review + changes waiting for approval (+ the account itself while it waits). */
$soldOn = fn(array $w): string => (string) (($w['sold_at'] ?? '') ?: (($w['updated_at'] ?? '') ?: $w['created_at']));
$sum = ['works' => 0, 'drafts' => 0, 'sold' => 0, 'sales' => 0.0, 'share' => 0.0, 'share_year' => 0.0, 'waiting' => 0];
foreach ($works as $w) {
    if ($w['state'] === 'draft') { $sum['drafts']++; continue; }
    $sum['works']++;
    if ($w['state'] === 'pending') $sum['waiting']++;
    if ($w['state'] === 'sold') {
        $sh = owArtistShare((float) $w['price'], $soldOn($w));
        $sum['sold']++; $sum['sales'] += (float) $w['price']; $sum['share'] += $sh;
        if (substr($soldOn($w), 0, 4) === date('Y')) $sum['share_year'] += $sh;
    }
}

$STATE = [
    'draft'    => ['Draft', 'مسودة'],
    'pending'  => ['In review', 'قيد المراجعة'],
    'approved' => ['In the Gallery', 'في المعرض'],
    'rejected' => ['Not approved', 'لم تتم الموافقة'],
    'sold'     => ['Sold', 'مباع'],
];

$activity = $tab === 'activity' ? owActivityFor('artist', $uid, 60) : [];

$sales = []; $payouts = [];
if (true) {   /* also on Home (recent sales, payouts) */
    try {
        $st = getDB()->prepare('SELECT a.title_en, a.title_ar, a.price, COALESCE(a.sold_at, a.updated_at, a.created_at) AS sold_on
                                FROM artworks a WHERE a.artist_id = :u AND a.status = \'sold\' ORDER BY sold_on DESC');
        $st->execute([':u' => $uid]);
        $sales = $st->fetchAll();
    } catch (Throwable $e) {
        try { $st = getDB()->prepare('SELECT title_en, title_ar, price, created_at AS sold_on FROM artworks WHERE artist_id = :u AND status = \'sold\'');
              $st->execute([':u' => $uid]); $sales = $st->fetchAll(); } catch (Throwable $e2) {}
    }
    if (owHasTable('payouts')) {
        $st = getDB()->prepare('SELECT amount, currency, status, reference, created_at FROM payouts WHERE artist_id = :u ORDER BY created_at DESC');
        $st->execute([':u' => $uid]);
        $payouts = $st->fetchAll();
    }
}

$DONE = [
    'email'    => ['Your new email is saved. Please open the link we sent to it to confirm it.' . ' ' . OW_JUNK_HINT[0], 'تم حفظ بريدك الجديد. افتح الرابط الذي أرسلناه إليه لتأكيده.' . ' ' . OW_JUNK_HINT[1]],
    'password' => ['Your password is changed. Other phones and computers were signed out.', 'تم تغيير كلمة المرور. وتم تسجيل الخروج من الأجهزة الأخرى.'],
    'notify'   => ['Your email choices are saved', 'تم حفظ اختياراتك للرسائل'],
    'resend'   => ['We sent the confirmation link again. Please check your inbox.' . ' ' . OW_JUNK_HINT[0], 'أرسلنا رابط التأكيد مرة أخرى. راجع بريدك.' . ' ' . OW_JUNK_HINT[1]],
    'waiting'  => ['Saved. Your changes are waiting for the team’s approval. Visitors see the approved version until then.', 'تم الحفظ. تعديلاتك بانتظار موافقة الفريق. يرى الزوار النسخة المعتمدة حتى ذلك الحين.'],
    'saved'    => ['Your changes are saved', 'تم حفظ تعديلاتك'],
    'ws_request' => ['Your request is sent. The Oweili team will email you the answer', 'تم إرسال طلبك. سيراسلك فريق أويلي بالرد'],
    'ws_saved'   => ['Your workshop is saved and waiting for the team’s approval. Visitors see it once it is approved', 'تم حفظ ورشتك وهي بانتظار موافقة الفريق. يراها الزوار بعد الموافقة'],
];
$myChanges = owMyChanges($uid);
foreach ($myChanges as $mc) if ($mc['state'] === 'pending') $sum['waiting']++;
if ((int) ($me['is_approved'] ?? 1) === 0) $sum['waiting']++;   // the account itself, until the team approves it
/* Certificates of Authenticity: one per approved artwork */
$certs = owCertsForArtist($uid);
$certByArt = [];
foreach ($certs as $c) if ($c['status'] === 'valid') $certByArt[(int) $c['artwork_id']] = $c;
$done = $DONE[$_GET['done'] ?? ''] ?? null;
$verified = (int) ($me['is_verified'] ?? 0) === 1;
$name = owRealName($me['artist_name']) ?: (owRealName($me['full_name_en']) ?: 'Artist');
$fmtDate = function (?string $d, bool $time = false): string { [$en, $ar] = owDateBi($d, $time); return owBi($en, $ar); };

/* ---------------------------------------------------------------------
 *  The new dashboard (owner, 25 Nov 2026): side menu, Bento home, views by day,
 *  sales and money with each order's step. NEVER a collector's name, email or
 *  phone: the orders are read without the buyer columns.
 * ------------------------------------------------------------------- */
require_once __DIR__ . '/order_lib.php';
require_once __DIR__ . '/owner_lib.php';       // owSvgLine (the small chart drawn on the server)
require_once __DIR__ . '/collector_lib.php';   // owFollowReady, owFollowerCount
require_once __DIR__ . '/sig_lib.php';
$payOn = false; try { $payOn = owPaymentsOn(); } catch (Throwable $e) { $payOn = false; }
$followers = owFollowReady() ? owFollowerCount($uid) : 0;
$sigOn = owSigExists((string) $uid);

/* views by day, from the anonymous daily counts (page_stats kind 'artwork', item = artwork id) */
$artIds = array_map(fn($w) => (string) (int) $w['id'], array_values(array_filter($works, fn($w) => $w['state'] !== 'draft')));
$d30a = date('Y-m-d', strtotime('-29 days')); $d30b = date('Y-m-d');
$views30 = []; for ($t = strtotime($d30a); $t <= strtotime($d30b); $t += 86400) $views30[date('Y-m-d', $t)] = 0;
$viewsBy = [];   // artwork id => views in the last 30 days
if ($artIds && owHasTable('page_stats')) {
    $in = implode(',', array_fill(0, count($artIds), '?'));
    try {
        $q = getDB()->prepare("SELECT day, item, SUM(views) n FROM page_stats WHERE kind = 'artwork' AND item IN ($in) AND day BETWEEN ? AND ? GROUP BY day, item");
        $q->execute(array_merge($artIds, [$d30a, $d30b]));
        foreach ($q->fetchAll() as $r) { $views30[(string) $r['day']] = ($views30[(string) $r['day']] ?? 0) + (int) $r['n']; $viewsBy[(int) $r['item']] = ($viewsBy[(int) $r['item']] ?? 0) + (int) $r['n']; }
    } catch (Throwable $e) {}
}
$views30Sum = array_sum($views30);

/* the artwork orders (only once payments are on): never the buyer's columns */
$orders = []; $payByOrder = [];
if ($payOn) {
    try {
        $q = getDB()->prepare("SELECT o.id, o.order_no, o.ref_id, o.title, o.amount, o.commission_rate, o.commission_amount, o.artist_amount, o.status,
                                      o.paid_at, o.shipped_at, o.delivered_at, o.confirmed_at, o.released_at, o.created_at, a.title_en, a.title_ar
                               FROM orders o LEFT JOIN artworks a ON a.id = o.ref_id
                               WHERE o.artist_id = :u AND o.kind = 'artwork' AND o.status NOT IN ('created','cancelled') ORDER BY o.id DESC LIMIT 200");
        $q->execute([':u' => $uid]); $orders = $q->fetchAll();
        if (owHasColumn('payouts', 'order_id')) {
            $q = getDB()->prepare('SELECT order_id, status FROM payouts WHERE artist_id = :u AND order_id IS NOT NULL');
            $q->execute([':u' => $uid]); foreach ($q->fetchAll() as $r) $payByOrder[(int) $r['order_id']] = (string) $r['status'];
        }
    } catch (Throwable $e) { $orders = []; }
}
/* each order in the artist's words: [English, Arabic, pill class] */
$ORDER_WORDS = [
    'paid'      => ['Sold, please pack it', 'بيع، يرجى تغليفه', 'warn'],
    'shipped'   => ['On its way to the collector', 'في الطريق إلى المقتني', ''],
    'delivered' => ['Delivered', 'تم التسليم', ''],
    'complaint' => ['There is a problem with this order, Oweili is handling it', 'توجد مشكلة في هذا الطلب، وأويلي يتابعها', 'warn'],
    'released'  => ['Completed', 'مكتمل', 'on'],
    'refunded'  => ['Refunded to the collector', 'أعيد المبلغ إلى المقتني', 'off'],
];
/* the payout of one order, in the artist's words */
$payWord = function (array $o) use ($payByOrder): array {
    if ($o['status'] === 'complaint') return ['On hold', 'معلّقة', 'warn'];
    if ($o['status'] === 'refunded') return ['No payout', 'لا دفعة', 'off'];
    $p = $payByOrder[(int) $o['id']] ?? '';
    if ($p === 'paid') return ['Paid to you', 'دُفعت لك', 'on'];
    if ($p === 'pending') return ['Payout on its way', 'الدفعة قيد التحويل', ''];
    if (!empty($o['confirmed_at'])) return ['Waiting for Oweili’s approval', 'بانتظار موافقة أويلي', ''];
    return ['After the collector confirms receipt', 'بعد تأكيد المقتني للاستلام', 'off'];
};
/* the last 12 months of sales: count, total, the artist's share (each sale at its own rate) */
$months = [];
for ($i = 11; $i >= 0; $i--) $months[date('Y-m', strtotime(date('Y-m-01') . " -$i months"))] = ['n' => 0, 'sales' => 0.0, 'share' => 0.0];
foreach ($sales as $s) { $m = substr((string) $s['sold_on'], 0, 7); if (isset($months[$m])) { $months[$m]['n']++; $months[$m]['sales'] += (float) $s['price']; $months[$m]['share'] += owArtistShare((float) $s['price'], (string) $s['sold_on']); } }
$payoutWait = 0.0; $payoutPaid = 0.0;
foreach ($payouts as $p) { if ($p['status'] === 'pending') $payoutWait += (float) $p['amount']; if ($p['status'] === 'paid') $payoutPaid += (float) $p['amount']; }

/* What needs the artist now: [link, [English, Arabic], urgent?] */
$needs = [];
foreach ($works as $w) {
    $tEn = (string) ($w['title_en'] ?: $w['title_ar']); $tAr = (string) ($w['title_ar'] ?: $w['title_en']);
    if ($w['state'] === 'rejected') $needs[] = ['edit_artwork.php?id=' . (int) $w['id'], ['Not approved: ' . $tEn . '. Why: ' . ($w['reason'] ?: '—') . '. Edit it to send it again', 'لم تتم الموافقة: ' . $tAr . '. السبب: ' . ($w['reason'] ?: '—') . '. عدّله لإرساله مجددا'], true];
    elseif ($w['state'] === 'draft') $needs[] = ['upload_artwork.php?id=' . (int) $w['id'], ['Finish your draft: ' . $tEn, 'أكمل مسودتك: ' . $tAr], false];
    $ch = $myChanges['artwork:' . (int) $w['id']] ?? null;
    if ($ch && $ch['state'] === 'rejected') $needs[] = [$tabLink('works'), ['Your last changes to ' . $tEn . ' were not approved', 'لم تتم الموافقة على تعديلاتك الأخيرة على ' . $tAr], false];
}
foreach ($orders as $o) {
    $oEn = (string) ($o['title_en'] ?: $o['title']); $oAr = (string) ($o['title_ar'] ?: $o['title']);
    if ($o['status'] === 'paid') $needs[] = [$tabLink('packing'), ['Pack ' . $oEn . ': it is sold', 'غلّف ' . $oAr . ': لقد بيع'], true];
    if ($o['status'] === 'complaint') $needs[] = [$tabLink('earnings'), [$oEn . ': there is a problem with this order, Oweili is handling it', $oAr . ': توجد مشكلة في هذا الطلب، وأويلي يتابعها'], false];
    if (!empty($o['confirmed_at']) && strtotime((string) $o['confirmed_at']) > time() - 14 * 86400) $needs[] = [$tabLink('earnings'), ['The collector received ' . $oEn . '. See their photos', 'استلم المقتني ' . $oAr . '. شاهد صوره'], false];
}
if ($payoutWait > 0) $needs[] = [$tabLink('earnings'), ['A payout of ' . owArtistShareText($payoutWait) . ' SAR is on its way to you', 'دفعة بقيمة ' . owArtistShareText($payoutWait) . ' ر.س في طريقها إليك'], false];
if (!$verified) $needs[] = [$tabLink('account'), ['Please confirm your email address', 'يرجى تأكيد بريدك الإلكتروني'], true];
if (!$sigOn) $needs[] = ['signature.php', ['Draw your signature for your certificates', 'ارسم توقيعك لشهاداتك'], false];
if ($wsOn) {
    $wsReq = owWsLastRequest($uid);
    if ($wsReq && $wsReq['status'] === 'refused' && strtotime((string) $wsReq['decided_at']) > time() - 30 * 86400) $needs[] = [$tabLink('workshops'), ['Your workshop request was not approved. See why', 'لم تتم الموافقة على طلب الورش. اطّلع على السبب'], false];
    foreach (owWsOfArtist($uid) as $ww) if ($ww['status'] === 'refused') $needs[] = [$tabLink('workshops') . '&edit=' . (int) $ww['id'], ['Workshop not approved: ' . $ww['title_en'] . '. Why: ' . $ww['reason'], 'لم تتم الموافقة على الورشة: ' . $ww['title_ar'] . '. السبب: ' . $ww['reason']], false];
}

$ICON = [
 'home' => '<path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/>',
 'frame' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 16l5-5 4 4 3-3 6 6"/>',
 'camera' => '<path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>',
 'cert' => '<circle cx="12" cy="9" r="5"/><path d="M9 13.5L8 21l4-2 4 2-1-7.5"/>',
 'pen' => '<path d="M4 20l4-1 11-11-3-3L5 16z"/><path d="M14 6l3 3"/>',
 'money' => '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9v.01M18 15v.01"/>',
 'box' => '<path d="M4 8l8-4 8 4v9l-8 4-8-4z"/><path d="M4 8l8 4 8-4M12 12v9"/>',
 'doc' => '<path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 13h7M9 17h5"/>',
 'brush' => '<path d="M14 4l6 6-8 8-6-6z"/><path d="M6 12l-2 6 6-2"/>',
 'class' => '<rect x="3" y="4" width="18" height="12" rx="1"/><path d="M8 20l4-4 4 4"/>',
 'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>',
 'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
 'list' => '<path d="M8 6h12M8 12h12M8 18h12M4 6v.01M4 12v.01M4 18v.01"/>',
 'eye' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
 'gallery' => '<path d="M4 20V9l8-5 8 5v11"/><path d="M9 20v-6h6v6"/>',
 'out' => '<path d="M15 4h4v16h-4"/><path d="M10 8l-4 4 4 4M6 12h10"/>',
];
$icon = fn(string $k): string => '<svg class="o-ic" viewBox="0 0 24 24" aria-hidden="true">' . ($ICON[$k] ?? '') . '</svg>';
/* [tab or page, English, Arabic, icon, shown, link (pages outside My Studio)] */
$MENU = [
  [['Home', 'الرئيسية'], [['home', 'Home', 'الرئيسية', 'home', true, '']]],
  [['My art', 'أعمالي'], [
     ['works', 'My artworks', 'أعمالي', 'frame', true, ''],
     ['upload', 'Add an artwork', 'أضف عملا', 'camera', true, 'upload_artwork.php'],
     ['certs', 'Certificates', 'الشهادات', 'cert', true, ''],
     ['signature', 'Signature', 'التوقيع', 'pen', true, 'signature.php']]],
  [['Sales and money', 'المبيعات والمال'], [
     ['earnings', 'Sales and payouts', 'المبيعات والمدفوعات', 'money', true, ''],
     ['packing', 'Packing guide', 'دليل التغليف', 'box', true, '']]],
  [['Writing and teaching', 'الكتابة والتعليم'], [
     ['articles', 'My articles', 'مقالاتي', 'doc', true, ''],
     ['commissions', 'Private commissions', 'الأعمال الخاصة', 'brush', in_array('commissions', $TABS, true), ''],
     ['workshops', 'Workshops', 'ورش العمل', 'class', $wsOn, '']]],
  [['Account', 'الحساب'], [
     ['profile', 'Public profile', 'الملف العام', 'user', true, 'edit_profile.php'],
     ['account', 'Email and password', 'البريد وكلمة المرور', 'lock', true, ''],
     ['activity', 'Activity', 'النشاط', 'list', true, '']]],
];
$TITLE = ['home' => ['My Studio', 'مرسمي'], 'works' => ['My artworks', 'أعمالي'], 'certs' => ['Certificates', 'الشهادات'], 'articles' => ['My articles', 'مقالاتي'],
          'commissions' => ['Private commissions', 'الأعمال الخاصة'], 'earnings' => ['Sales and payouts', 'المبيعات والمدفوعات'], 'packing' => ['Packing guide', 'دليل التغليف'],
          'account' => ['Email and password', 'البريد وكلمة المرور'], 'activity' => ['Activity', 'النشاط'], 'workshops' => ['Workshops', 'ورش العمل']];
$nameAr = owRealName((string) ($me['artist_name'] ?? '')) ?: (owRealName((string) ($me['full_name_ar'] ?? '')) ?: $name);
$money = fn($n): string => '<bdi dir="ltr">' . owArtistShareText((float) $n) . '</bdi> <span class="c-cur">' . owBi('SAR', 'ر.س') . '</span>';
$thumb = function (string $raw, string $alt, string $cls = 'c-thumb'): string {
    $img = owImgSrc($raw); $s = $img !== '' ? owImgSized($img, 400) : '';
    return '<span class="' . $cls . '">' . ($img !== '' ? '<img loading="lazy" src="' . e($s) . '" alt="' . e($alt) . '"' . owImgFallbackAttrs($img, $s) . '>' . owImgPlaceholder(true) : owImgPlaceholder(false)) . '</span>';
};
?>
<!DOCTYPE html>
<html <?php require_once __DIR__ . '/lang_lib.php'; owHtmlAttrs(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title><?= e(owIsAr() ? $TITLE[$tab][1] . ' · أويلي' : $TITLE[$tab][0] . ' · Oweili') ?></title>
<meta name="description" content="Your artworks, sales and account on Oweili. أعمالك ومبيعاتك وحسابك في أويلي">
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/owner.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/collector.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/artist.css') ?>">
<script>try{if(localStorage.getItem('ow_side')==='mini')document.documentElement.classList.add('o-mini');}catch(e){}</script>
</head>
<body class="o-body c-body a-body role-artist">
<div class="o-shell">
  <aside class="o-side" id="o-side" aria-label="<?= owIsAr() ? 'قائمة مرسمي' : 'My Studio menu' ?>">
    <div class="o-side-top">
      <a class="o-brand" href="index.php"><span class="o-logo" aria-hidden="true"></span><b>OWEILI</b></a>
      <button type="button" class="o-collapse" id="o-collapse" aria-label="<?= owIsAr() ? 'تصغير القائمة' : 'Collapse the menu' ?>" aria-pressed="false"><svg class="o-ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg></button>
    </div>
    <nav class="o-nav">
      <?php foreach ($MENU as [$gName, $items]): $vis = array_values(array_filter($items, fn($it) => $it[4])); if (!$vis) continue; ?>
        <div class="o-group">
          <p class="o-gname"><?= owBi($gName[0], $gName[1]) ?></p>
          <?php foreach ($vis as $it): $on = $it[5] === '' && $it[0] === $tab; $href = $it[5] !== '' ? $it[5] : $tabLink($it[0]); ?>
            <a class="o-link<?= $on ? ' on' : '' ?>" href="<?= e($href) ?>"<?= $on ? ' aria-current="page"' : '' ?> title="<?= e(owIsAr() ? $it[2] : $it[1]) ?>"><?= $icon($it[3]) ?><span class="o-lt"><?= owBi($it[1], $it[2]) ?></span></a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </nav>
    <div class="o-side-foot">
      <a class="o-link" href="artist_dashboard.php?id=<?= $uid ?>" title="<?= owIsAr() ? 'صفحتي العامة' : 'My public page' ?>"><?= $icon('eye') ?><span class="o-lt"><?= owBi('My public page', 'صفحتي العامة') ?></span></a>
      <a class="o-link" href="logout.php" title="<?= owIsAr() ? 'تسجيل الخروج' : 'Sign out' ?>"><?= $icon('out') ?><span class="o-lt"><?= owBi('Sign out', 'تسجيل الخروج') ?></span></a>
    </div>
  </aside>
  <div class="o-scrim" id="o-scrim" hidden></div>
  <div class="o-main">
    <header class="o-top">
      <button type="button" class="o-burger" id="o-burger" aria-controls="o-side" aria-expanded="false" aria-label="<?= owIsAr() ? 'القائمة' : 'Menu' ?>"><svg class="o-ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
      <h1 class="o-title"><?= owBi($TITLE[$tab][0], $TITLE[$tab][1]) ?></h1>
      <div class="o-top-r"><a class="o-site" href="index.php"><?= owBi('View the site', 'عرض الموقع') ?></a><?php owCornerControls('owLang()'); ?></div>
    </header>
    <main class="o-content c-content a-content" id="o-content">

  <?php if ($done): ?><div class="o-flash" role="status"><?= owBi($done[0], $done[1]) ?></div><?php endif; ?>
  <?php if ($err !== ''): $m = OW_ACCOUNT_ERR[$err] ?? OW_ACCOUNT_ERR['server']; ?><div class="o-flash bad" role="alert"><?= owBi($m[0], $m[1]) ?></div><?php endif; ?>
  <?php $dashMsg = (string) ($_SESSION['dash_msg'] ?? ''); unset($_SESSION['dash_msg']);   /* the result of Delete (artist_dashboard.php does the deleting) */
  $DASH_MSG = ['deleted' => ['', 'The artwork is deleted. It is gone from your profile and the Gallery.', 'تم حذف العمل. لم يعد في ملفك ولا في المعرض.'],
               'not_deleted' => [' bad', 'We could not delete that artwork. Please try again, or write to ' . owContactEmail() . '.', 'لم نتمكن من حذف هذا العمل. حاول مرة أخرى، أو راسلنا على ' . owContactEmail() . '.'],
               'expired' => [' bad', 'This page was open for a long time, so nothing was changed. Please press Delete again.', 'كانت هذه الصفحة مفتوحة لوقت طويل، لذلك لم يتغير شيء. اضغط «حذف» مرة أخرى.']];
  if (isset($DASH_MSG[$dashMsg])): ?><div class="o-flash<?= $DASH_MSG[$dashMsg][0] ?>" role="status"><?= owBi($DASH_MSG[$dashMsg][1], $DASH_MSG[$dashMsg][2]) ?></div><?php endif; ?>
  <?php if (!$verified && $tab !== 'home'): ?>
  <div class="o-t c-verify">
    <span><?= owBi('Please confirm your email address. We sent you a link when you joined.', 'يرجى تأكيد بريدك الإلكتروني. أرسلنا لك رابطا عند التسجيل.') ?></span>
    <form method="post" action="<?= e($tabLink($tab)) ?>"><?= csrfField() ?><input type="hidden" name="do" value="resend">
      <button class="btn btn-secondary btn-sm" type="submit"><?= owBi('Send the link again', 'أرسل الرابط مجددا') ?></button></form>
  </div>
  <?php endif; ?>

<?php if ($tab === 'home'): ?>
  <div class="o-bento c-bento a-bento">
    <section class="o-t t-hi c-hi">
      <div>
        <p class="o-hello"><span class="bi-en" lang="en">Hello, <bdi><?= e(rtrim($name, '. ')) ?></bdi></span><span class="bi-ar" lang="ar">أهلا، <bdi><?= e(rtrim($nameAr, '. ')) ?></bdi></span></p>
        <p class="o-sub"><?= owBi('Your artworks, sales and certificates, all in one place', 'أعمالك ومبيعاتك وشهاداتك، في مكان واحد') ?></p>
      </div>
      <div class="o-quick">
        <a class="o-qa a-add" href="upload_artwork.php"><?= $icon('camera') ?><?= owBi('Add an artwork', 'أضف عملا') ?></a>
        <a class="o-qa" href="artist_dashboard.php?id=<?= $uid ?>"><?= $icon('eye') ?><?= owBi('My public page', 'صفحتي العامة') ?></a>
      </div>
    </section>

    <div class="o-kpis c-kpis a-kpis" style="--k:6">
      <a class="o-t o-kpi" href="<?= e($tabLink('works')) ?>"><span class="o-kl"><?= owBi('Views, last 30 days', 'المشاهدات، آخر 30 يوما') ?></span><span class="o-kv"><?= number_format($views30Sum) ?></span><span class="o-ks"><?= owBi(number_format($tot['views']) . ' all time', number_format($tot['views']) . ' منذ البداية') ?></span></a>
      <a class="o-t o-kpi" href="artist_dashboard.php?id=<?= $uid ?>"><span class="o-kl"><?= owBi('Followers', 'المتابعون') ?></span><span class="o-kv"><?= number_format($followers) ?></span></a>
      <a class="o-t o-kpi" href="<?= e($tabLink('works')) ?>"><span class="o-kl"><?= owBi('Favourites', 'الإعجابات') ?></span><span class="o-kv"><?= number_format($tot['favs']) ?></span></a>
      <a class="o-t o-kpi" href="<?= e($tabLink('works')) ?>"><span class="o-kl"><?= owBi('Interested collectors', 'مهتمون بالشراء') ?></span><span class="o-kv"><?= number_format($tot['inquiries']) ?></span></a>
      <a class="o-t o-kpi" href="<?= e($tabLink('earnings')) ?>"><span class="o-kl"><?= owBi('Sold', 'المباعة') ?></span><span class="o-kv"><?= $sum['sold'] ?></span></a>
      <a class="o-t o-kpi" href="<?= e($tabLink('earnings')) ?>"><span class="o-kl"><?= owBi('Your share after commission', 'حصتك بعد العمولة') ?></span><span class="o-kv"><?= $money($sum['share']) ?></span></a>
    </div>

    <section class="o-t c-needs a-needs">
      <div class="o-th"><h2><?= owBi('Needs you now', 'يحتاجك الآن') ?></h2><?php if ($needs): ?><span class="o-badge hot"><?= count($needs) ?></span><?php endif; ?></div>
      <?php if (!$needs): ?><p class="o-empty"><?= owBi('Nothing needs you now', 'لا شيء يحتاجك الآن') ?></p>
      <?php else: ?><ul class="o-alerts"><?php foreach (array_slice($needs, 0, 8) as [$href, $w, $hot]): ?>
        <li><a href="<?= e($href) ?>"><span class="o-an<?= $hot ? ' hot' : '' ?>" aria-hidden="true">!</span><span><?= owBi($w[0], $w[1]) ?></span><span class="o-go" aria-hidden="true">›</span></a></li>
      <?php endforeach; ?></ul><?php endif; ?>
      <?php if ($sum['waiting']): ?><p class="o-sm a-wait"><?= owBi($sum['waiting'] . ' waiting for the team’s approval', $sum['waiting'] . ' بانتظار موافقة الفريق') ?></p><?php endif; ?>
    </section>

    <section class="o-t a-trend">
      <div class="o-th"><h2><?= owBi('Views of your artworks, last 30 days', 'مشاهدات أعمالك، آخر 30 يوما') ?></h2></div>
      <?php if ($views30Sum === 0): ?><p class="o-empty"><?= owBi('No views in the last 30 days yet. Share your public page to bring visitors', 'لا مشاهدات في آخر 30 يوما بعد. شارك صفحتك العامة لتجذب الزوار') ?></p>
      <?php else: ?><?= owSvgLine($views30, owIsAr() ? 'المشاهدات في آخر 30 يوما' : 'Views in the last 30 days') ?><?php endif; ?>
    </section>

    <section class="o-t a-works">
      <div class="o-th"><h2><?= owBi('My artworks', 'أعمالي') ?></h2></div>
      <?php $recent = array_slice($works, 0, 4); if (!$recent): ?><p class="o-empty"><?= owBi('Your studio is ready for its first piece', 'مرسمك جاهز لعملك الأول') ?></p>
        <p class="c-center"><a class="btn btn-primary" href="upload_artwork.php"><?= owBi('Add an artwork', 'أضف عملا') ?></a></p>
      <?php else: ?><div class="c-strip"><?php foreach ($recent as $w): $tEn = (string) ($w['title_en'] ?: $w['title_ar']); $tAr = (string) ($w['title_ar'] ?: $w['title_en']); $s = isset($STATE[$w['state']]) ? $w['state'] : 'pending'; ?>
        <a class="c-mini" href="<?= $s === 'draft' ? 'upload_artwork.php?id=' . (int) $w['id'] : e($tabLink('works')) ?>"><?= $thumb((string) $w['image_url'], $tEn . ' · ' . $tAr) ?>
          <span class="c-mt"><?= owBi($tEn, $tAr) ?></span><span class="c-ms"><span class="c-pill a-st-<?= e($s) ?>"><?= owBi($STATE[$s][0], $STATE[$s][1]) ?></span></span></a>
      <?php endforeach; ?></div><?php endif; ?>
      <a class="o-more" href="<?= e($tabLink('works')) ?>"><?= owBi('All my artworks', 'كل أعمالي') ?></a>
    </section>

    <section class="o-t a-sales">
      <div class="o-th"><h2><?= owBi('Recent sales', 'آخر المبيعات') ?></h2></div>
      <?php if (!$sales): ?><p class="o-empty"><?= owBi('No sales yet. Your sold works will appear here', 'لا مبيعات بعد. ستظهر أعمالك المباعة هنا') ?></p>
      <?php else: ?><ul class="c-olist"><?php foreach (array_slice($sales, 0, 3) as $s): [$dEn, $dAr] = owDateBi((string) $s['sold_on']); ?>
        <li><div class="c-orow"><b><?= owBi((string) ($s['title_en'] ?: $s['title_ar']), (string) ($s['title_ar'] ?: $s['title_en'])) ?></b><span><?= $money(owArtistShare((float) $s['price'], (string) $s['sold_on'])) ?></span></div>
          <span class="o-sm"><?= owBi('Sold ' . $dEn . ' · your share after commission', 'بيع في ' . $dAr . ' · حصتك بعد العمولة') ?></span></li>
      <?php endforeach; ?></ul><?php endif; ?>
      <a class="o-more" href="<?= e($tabLink('earnings')) ?>"><?= owBi('Sales and payouts', 'المبيعات والمدفوعات') ?></a>
    </section>
  </div>

<?php elseif ($tab === 'works'): ?>
  <p class="o-note"><?= owBi('Oweili is the middle party: we talk to collectors for you and keep their details private. You see how many people are interested, never who they are.', 'أويلي هو الوسيط: نتواصل مع المقتنين نيابة عنك ونحفظ بياناتهم. ترى عدد المهتمين، لا هوياتهم.') ?></p>
  <p class="a-top-act"><a class="btn btn-primary" href="upload_artwork.php"><?= owBi('Add an artwork', 'أضف عملا') ?></a></p>
  <?php if (!$works): ?>
    <div class="o-t"><p class="o-empty"><?= owBi('Your studio is ready for its first piece', 'مرسمك جاهز لعملك الأول') ?></p></div>
  <?php else: ?>
  <div class="w-list">
    <?php foreach ($works as $w):
      $s = isset($STATE[$w['state']]) ? $w['state'] : 'pending';
      $img = owImgSrc($w['image_url'] ?? ''); $t4 = $img !== '' ? owImgSized($img, 400) : '';
      $tEn = (string) ($w['title_en'] ?: $w['title_ar']); $tAr = (string) ($w['title_ar'] ?: $w['title_en']); ?>
    <div class="card w-row">
      <div class="w-thumb"><?php if ($img !== ''): ?><img loading="lazy" src="<?= e($t4) ?>" alt="<?= e($tEn . ' · ' . $tAr) ?>"<?= owImgFallbackAttrs($img, $t4) ?>><?= owImgPlaceholder(true) ?><?php else: ?><?= owImgPlaceholder(false) ?><?php endif; ?></div>
      <div>
        <p class="w-title"><?= owBi($tEn, $tAr) ?></p>
        <div class="w-nums">
          <span><b><?= number_format((int) ($viewsBy[(int) $w['id']] ?? 0)) ?></b> <?= owBi('views in 30 days', 'مشاهدة في 30 يوما') ?></span>
          <span><b><?= number_format((int) $w['views']) ?></b> <?= owBi('views in all', 'مشاهدة إجمالا') ?></span>
          <span><b><?= number_format((int) $w['favs']) ?></b> <?= owBi('favorites', 'إعجاب') ?></span>
          <span><b><?= number_format((int) $w['inquiries']) ?></b> <?= owBi('interested', 'مهتم') ?></span>
          <span><?= number_format((float) $w['price']) ?> <?= owBi('SAR', 'ر.س') ?></span>
        </div>
        <?php if (isset($certByArt[(int) $w['id']])): $wc = $certByArt[(int) $w['id']]; ?>
          <div class="w-cert">✓ <?= owBi('Certificate of Authenticity', 'شهادة أصالة') ?> <a href="certificate.php?no=<?= e(rawurlencode($wc['cert_no'])) ?>" dir="ltr"><?= e($wc['cert_no']) ?></a></div>
        <?php elseif (owCertReady() && $s !== 'draft' && ($wm = owCertMissing($w))): [$mE, $mA] = owCertMissingWords($wm); ?>
          <div class="w-wait"><?= owBi('For its certificate, please add: ' . $mE . '.', 'لإصدار شهادته، يرجى إضافة: ' . $mA . '.') ?> <a href="edit_artwork.php?id=<?= (int) $w['id'] ?>"><?= owBi('Add details', 'أضف التفاصيل') ?></a></div>
        <?php endif; ?>
        <?php $ch = $myChanges['artwork:' . (int) $w['id']] ?? null; if ($ch && $ch['state'] === 'pending'): ?>
          <div class="w-wait">⏳ <?= owBi('Your changes are waiting for approval. Visitors see the approved version.', 'تعديلاتك بانتظار الموافقة. يرى الزوار النسخة المعتمدة.') ?></div>
        <?php elseif ($ch && $ch['state'] === 'rejected'): ?>
          <div class="w-reason"><?= owBi('Your last changes were not approved: ', 'لم تتم الموافقة على تعديلاتك الأخيرة: ') ?><?= e($ch['reason'] ?: '—') ?></div>
        <?php endif; ?>
        <?php if ($s === 'rejected'): ?>
          <div class="w-reason"><?= owBi('Why: ', 'السبب: ') ?><?= e((string) ($w['reason'] ?: '—')) ?> · <?= owBi('Edit it and it goes back to review', 'عدّله ليعود إلى المراجعة') ?></div>
        <?php endif; ?>
      </div>
      <div class="w-side">
        <span class="st-pill s-<?= e($s) ?>"><?= owBi($STATE[$s][0], $STATE[$s][1]) ?></span>
        <?php if ($s === 'draft'): ?>
          <a class="btn btn-accent btn-sm" href="upload_artwork.php?id=<?= (int) $w['id'] ?>"><?= owBi('Continue', 'متابعة') ?></a>
        <?php else: ?>
          <a class="btn btn-ghost btn-sm" href="edit_artwork.php?id=<?= (int) $w['id'] ?>"><?= owBi('Edit', 'تعديل') ?></a>
        <?php endif; ?>
          <form method="post" action="artist_dashboard.php" onsubmit="return confirm(document.documentElement.dir === 'rtl' ? <?= $s === 'draft' ? "'حذف هذه المسودة؟'" : "'حذف هذا العمل؟ لا يمكن التراجع.'" ?> : <?= $s === 'draft' ? "'Delete this draft?'" : "'Delete this artwork? This cannot be undone.'" ?>);">
            <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="artwork_id" value="<?= (int) $w['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm"><?= owBi('Delete', 'حذف') ?></button></form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

<?php elseif ($tab === 'articles'):
  $myArts = [];
  try { if (owHasTable('articles')) { $q = getDB()->prepare('SELECT id, type, slug, title_en, title_ar, status FROM articles WHERE author_user_id = :u ORDER BY updated_at DESC, id DESC LIMIT 50'); $q->execute([':u' => $uid]); $myArts = $q->fetchAll(); } } catch (Throwable $e) { $myArts = []; }
  $aState = ['draft' => ['Draft', 'مسودة'], 'pending' => ['Waiting for approval', 'بانتظار الموافقة'], 'rejected' => ['Not approved', 'لم تتم الموافقة'], 'published' => ['Published', 'منشور']];
  $aTypes = ['event' => ['Art Event', 'ملتقى فني'], 'artist_spotlight' => ['Artist Spotlight', 'أضواء على فنان'], 'art_education' => ['Art Education', 'تثقيف فني']]; ?>
  <p class="note"><?= owBi('Write Art Events, Artist Spotlights and Art Education articles. The Oweili team reads each one first, and we email you the result.', 'اكتب عن الملتقيات الفنية وأضواء على الفنانين والتثقيف الفني. يقرأ فريق أويلي كل مقال أولا، ونراسلك بالنتيجة.') ?></p>
  <div style="display:flex;gap:var(--space-xs);flex-wrap:wrap;margin-bottom:var(--space-md)">
    <a class="btn btn-primary" href="my_articles.php?new=event"><?= owBi('New Art Event', 'ملتقى فني جديد') ?></a>
    <a class="btn btn-secondary" href="my_articles.php?new=artist_spotlight"><?= owBi('New Artist Spotlight', 'أضواء جديدة على فنان') ?></a>
    <a class="btn btn-secondary" href="my_articles.php?new=art_education"><?= owBi('New Art Education article', 'مقال تثقيف فني جديد') ?></a>
  </div>
  <?php if (!$myArts): ?>
    <div class="card empty"><p><?= owBi('No articles yet. Your first one starts with one of the buttons above.', 'لا مقالات بعد. يبدأ مقالك الأول بأحد الأزرار في الأعلى.') ?></p></div>
  <?php else: ?>
  <div class="w-list">
    <?php foreach ($myArts as $ar): $ch = $myChanges['article:' . (int) $ar['id']] ?? null;
      $s = $ar['status'] === 'published' ? 'published' : ($ch && $ch['state'] === 'pending' ? 'pending' : ($ch && $ch['state'] === 'rejected' ? 'rejected' : 'draft'));
      $ty = $aTypes[$ar['type']] ?? ['Article', 'مقال']; ?>
    <div class="card w-row" style="grid-template-columns:1fr auto">
      <div><p class="w-title"><?= owBi((string) ($ar['title_en'] ?: $ar['title_ar']), (string) ($ar['title_ar'] ?: $ar['title_en'])) ?></p>
        <div class="w-nums"><span><?= owBi($ty[0], $ty[1]) ?></span></div>
        <?php if ($s === 'rejected'): ?><div class="w-reason"><?= owBi('Why: ', 'السبب: ') ?><?= e($ch['reason'] ?: '—') ?></div><?php endif; ?></div>
      <div class="w-side"><span class="st-pill s-<?= $s === 'published' ? 'approved' : e($s) ?>"><?= owBi($aState[$s][0], $aState[$s][1]) ?></span>
        <?php if ($s === 'published'): ?><a class="btn btn-ghost btn-sm" href="article.php?slug=<?= e(rawurlencode($ar['slug'])) ?>"><?= owBi('See it', 'شاهده') ?></a>
        <?php else: ?><a class="btn btn-accent btn-sm" href="my_articles.php?edit=<?= (int) $ar['id'] ?>"><?= owBi('Edit', 'تعديل') ?></a><?php endif; ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <p class="note" style="margin-top:var(--space-sm)"><a href="my_articles.php"><?= owBi('Open all my articles', 'افتح كل مقالاتي') ?></a></p>
  <?php endif; ?>
<?php elseif ($tab === 'commissions'):
  require_once __DIR__ . '/commission_lib.php';
  $myC = [];
  try { if (owCommReady()) { $q = getDB()->prepare("SELECT c.id, c.ref_no, c.subject, c.status, c.assigned_at, (SELECT COUNT(*) FROM commission_nda n WHERE n.commission_id = c.id AND n.artist_id = c.artist_id) AS nda
                                                     FROM commissions c WHERE c.artist_id = :u AND c.status <> 'cancelled' ORDER BY c.id DESC LIMIT 100"); $q->execute([':u' => $uid]); $myC = $q->fetchAll(); } } catch (Throwable $e) { $myC = []; }
  $cS = owCommSubjects(); ?>
  <p class="note"><?= owBi('Private commissions the Oweili team chose you for: portraits and personal memories, painted from the collector’s photos. Everything here is confidential.', 'أعمال خاصة اختارك لها فريق أويلي: بورتريهات وذكريات شخصية تُرسم من صور المقتني. كل ما هنا سرّي.') ?></p>
  <?php if (!$myC): ?>
    <div class="card empty"><p><?= owBi('No private commissions yet. When the team chooses you, you get an email.', 'لا أعمال خاصة بعد. عندما يختارك الفريق تصلك رسالة.') ?></p></div>
  <?php else: ?>
  <div class="w-list">
    <?php foreach ($myC as $cm): [$ce, $ca] = owCommStatus()[$cm['status']]; ?>
    <div class="card w-row" style="grid-template-columns:1fr auto">
      <div><p class="w-title"><?= e($cm['ref_no']) ?> · <?= owBi($cS[$cm['subject']][0] ?? '', $cS[$cm['subject']][1] ?? '') ?></p>
        <?php if (!(int) $cm['nda']): ?><div class="w-wait"><?= owBi('Please accept the confidentiality agreement to see the photos', 'يرجى قبول اتفاقية السرّية لرؤية الصور') ?></div><?php endif; ?></div>
      <div class="w-side"><span class="st-pill"><?= owBi($ce, $ca) ?></span><a class="btn btn-accent btn-sm" href="artist_commission.php?id=<?= (int) $cm['id'] ?>"><?= owBi('Open', 'افتح') ?></a></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
<?php elseif ($tab === 'account'): ?>
  <form class="card form-card" method="post" action="<?= e($tabLink('account')) ?>" novalidate>
    <?= csrfField() ?><input type="hidden" name="do" value="notify">
    <h2><?= owBi('Emails from Oweili', 'رسائل أويلي') ?></h2>
    <label class="check"><input type="checkbox" name="notify_inquiries" value="1"<?= (int) ($me['notify_inquiries'] ?? 1) === 1 ? ' checked' : '' ?>>
      <span><?= owBi('Tell me when someone is interested in one of my works', 'أخبرني حين يهتم أحد بأحد أعمالي') ?></span></label>
    <label class="check"><input type="checkbox" name="notify_news" value="1"<?= (int) ($me['notify_news'] ?? 1) === 1 ? ' checked' : '' ?>>
      <span><?= owBi('Send me Oweili news and tips for artists', 'أرسل لي أخبار أويلي ونصائح للفنانين') ?></span></label>
    <p class="hint"><?= owBi('Important account emails (like password changes) always arrive', 'رسائل الحساب المهمة (مثل تغيير كلمة المرور) تصلك دائما') ?></p>
    <button class="btn btn-primary" type="submit"><?= owBi('Save', 'حفظ') ?></button>
  </form>
  <form class="card form-card" method="post" action="<?= e($tabLink('account')) ?>" novalidate>
    <?= csrfField() ?><input type="hidden" name="do" value="email">
    <h2><?= owBi('Email address', 'البريد الإلكتروني') ?></h2>
    <p class="hint"><?= owBi('Now: ', 'الحالي: ') ?><span dir="ltr"><?= e($me['email']) ?></span><?= $verified ? '' : ' · ' . owBi('not confirmed yet', 'لم يُؤكَّد بعد') ?></p>
    <div class="field<?= in_array($err, ['email', 'email_same', 'exists'], true) ? ' has-error' : '' ?>"><label for="a-email"><?= owBi('New email', 'البريد الجديد') ?></label>
      <input id="a-email" name="new_email" type="email" required maxlength="190" autocomplete="email" dir="ltr" value="<?= e($old['new_email'] ?? '') ?>"></div>
    <div class="field"><label for="a-cur1"><?= owBi('Current password', 'كلمة المرور الحالية') ?></label>
      <input id="a-cur1" name="current_password" type="password" required autocomplete="current-password"></div>
    <p class="hint"><?= owBi('We will send a link to the new address to confirm it', 'سنرسل رابطا إلى البريد الجديد لتأكيده') ?></p>
    <button class="btn btn-primary" type="submit"><?= owBi('Change email', 'غيّر البريد') ?></button>
  </form>
  <form class="card form-card" method="post" action="<?= e($tabLink('account')) ?>" novalidate>
    <?= csrfField() ?><input type="hidden" name="do" value="password">
    <h2><?= owBi('Password', 'كلمة المرور') ?></h2>
    <div class="field"><label for="a-cur2"><?= owBi('Current password', 'كلمة المرور الحالية') ?></label>
      <input id="a-cur2" name="current_password" type="password" required autocomplete="current-password"></div>
    <div class="field<?= in_array($err, ['weak', 'same'], true) ? ' has-error' : '' ?>"><label for="a-new"><?= owBi('New password', 'كلمة المرور الجديدة') ?></label>
      <input id="a-new" name="new_password" type="password" required minlength="8" autocomplete="new-password"></div>
    <p class="hint"><?= owBi('8 or more characters, with a capital letter, a number and a symbol', '8 أحرف أو أكثر، مع حرف كبير ورقم ورمز') ?></p>
    <div class="field<?= $err === 'mismatch' ? ' has-error' : '' ?>"><label for="a-conf"><?= owBi('New password again', 'كلمة المرور الجديدة مرة أخرى') ?></label>
      <input id="a-conf" name="confirm_password" type="password" required minlength="8" autocomplete="new-password"></div>
    <button class="btn btn-primary" type="submit"><?= owBi('Change password', 'غيّر كلمة المرور') ?></button>
  </form>

<?php elseif ($tab === 'certs'): ?>
  <p class="note"><?= owBi('Each approved artwork gets a Certificate of Authenticity with its own number. Open one to download it as a PDF, or share its verify page with a collector.', 'يحصل كل عمل معتمد على شهادة أصالة برقم خاص. افتح الشهادة لتنزيلها بصيغة PDF، أو شارك صفحة التحقق مع المقتني.') ?></p>
  <?php require_once __DIR__ . '/sig_lib.php'; $sigOn = owSigExists((string) $uid); ?>
  <div class="card" style="display:flex;flex-wrap:wrap;gap:var(--space-xs);align-items:center;justify-content:space-between;margin-bottom:var(--space-sm)">
    <span><?= $sigOn ? owBi('✓ Your signature is on your certificates', '✓ توقيعك على شهاداتك') : owBi('Add your signature to your certificates: draw it once', 'أضف توقيعك إلى شهاداتك: ارسمه مرة واحدة') ?></span>
    <a class="btn btn-secondary btn-sm" href="signature.php"><?= $sigOn ? owBi('Change signature', 'غيّر التوقيع') : owBi('Draw my signature', 'ارسم توقيعي') ?></a>
  </div>
  <?php if (!owCertReady() || !$certs): ?>
    <div class="card empty"><p><?= owBi('Your certificates will appear here once your artworks are approved', 'ستظهر شهاداتك هنا بعد اعتماد أعمالك') ?></p></div>
  <?php else: ?>
  <div class="w-list">
    <?php foreach ($certs as $c): $cs = $c['snap']; $ci = owImgSrc((string) ($cs['image'] ?? '')); $c4 = $ci !== '' ? owImgSized($ci, 400) : ''; [$ciE, $ciA] = owDateBi((string) $c['issued_at']); $ok = $c['status'] === 'valid'; ?>
    <div class="card w-row">
      <div class="w-thumb"><?php if ($ci !== ''): ?><img loading="lazy" src="<?= e($c4) ?>" alt="<?= e(($cs['title_en'] ?? '') . ' · ' . ($cs['title_ar'] ?? '')) ?>"<?= owImgFallbackAttrs($ci, $c4) ?>><?= owImgPlaceholder(true) ?><?php else: ?><?= owImgPlaceholder(false) ?><?php endif; ?></div>
      <div>
        <p class="w-title"><?= owBi((string) ($cs['title_en'] ?? ''), (string) ($cs['title_ar'] ?? '')) ?></p>
        <div class="w-nums"><span dir="ltr"><b><?= e($c['cert_no']) ?></b></span><span><?= owBi('Issued ' . $ciE, 'صدرت ' . $ciA) ?></span></div>
        <?php if (!$ok): ?><div class="w-reason"><?= owBi('Cancelled by Oweili. Reason: ', 'ألغتها أويلي. السبب: ') ?><?= e((string) $c['cancel_reason']) ?></div><?php endif; ?>
      </div>
      <div class="w-side">
        <span class="st-pill <?= $ok ? 's-approved' : 's-rejected' ?>"><?= $ok ? owBi('Valid', 'سارية') : owBi('Cancelled', 'ملغاة') ?></span>
        <a class="btn btn-accent btn-sm" href="certificate.php?no=<?= e(rawurlencode($c['cert_no'])) ?>"><?= owBi('View & download', 'عرض وتنزيل') ?></a>
        <a class="btn btn-ghost btn-sm" href="<?= e(owCertVerifyUrl((string) $c['cert_no'])) ?>"><?= owBi('Verify page', 'صفحة التحقق') ?></a>
        <?php if ($ok): ?><a class="btn btn-ghost btn-sm" href="cert_label.php?no=<?= e(rawurlencode($c['cert_no'])) ?>"><?= owBi('Back label', 'ملصق الظهر') ?></a><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

<?php elseif ($tab === 'packing'): /* Packing guide (owner, 11 Nov 2026): only here, for the signed-in artist */ ?>
  <p class="note"><?= owBi('Oweili arranges the shipping. You only pack the artwork.', 'أويلي يتولى الشحن. عليك فقط تغليف العمل.') ?></p>
  <ol class="pk-list">
      <li class="card pk-step"><span class="pk-ico"><svg viewBox="0 0 64 64" aria-hidden="true"><rect x="10" y="12" width="40" height="40" rx="2"/><path d="M16 18h28v28H16z" stroke-dasharray="3 3"/><path d="M18 8h36v36l-8 8H18z"/><path d="M54 44h-8v8"/></svg></span><span class="pk-txt"><b class="pk-n">1</b><?= owBi('Cover the front of the painting with glassine paper (soft protective paper). Never put bubble wrap straight on the paint.', 'غطِّ وجه اللوحة بورق الجلاسين (ورق حماية ناعم). لا تضع الغلاف الفقاعي مباشرة على الطلاء أبدا.') ?></span></li>
      <li class="card pk-step"><span class="pk-ico"><svg viewBox="0 0 64 64" aria-hidden="true"><rect x="10" y="10" width="44" height="44" rx="2"/><path d="M10 22V10h12M42 10h12v12M54 42v12H42M22 54H10V42"/><path d="M10 22l12-12M42 10l12 12M54 42L42 54M22 54L10 42"/></svg></span><span class="pk-txt"><b class="pk-n">2</b><?= owBi('Add corner protectors', 'أضف واقيات الزوايا') ?></span></li>
      <li class="card pk-step"><span class="pk-ico"><svg viewBox="0 0 64 64" aria-hidden="true"><rect x="8" y="10" width="48" height="44" rx="6"/><circle cx="18" cy="20" r="3"/><circle cx="32" cy="20" r="3"/><circle cx="46" cy="20" r="3"/><circle cx="18" cy="32" r="3"/><circle cx="32" cy="32" r="3"/><circle cx="46" cy="32" r="3"/><circle cx="18" cy="44" r="3"/><circle cx="32" cy="44" r="3"/><circle cx="46" cy="44" r="3"/></svg></span><span class="pk-txt"><b class="pk-n">3</b><?= owBi('Wrap it in bubble wrap', 'لُفَّ اللوحة بالغلاف الفقاعي') ?></span></li>
      <li class="card pk-step"><span class="pk-ico"><svg viewBox="0 0 64 64" aria-hidden="true"><path d="M8 24l24-10 24 10v26L32 60 8 50z"/><path d="M8 24l24 10 24-10M32 34v26"/><path d="M8 24l-4 8 24 10 4-8M56 24l4 8-24 10-4-8"/></svg></span><span class="pk-txt"><b class="pk-n">4</b><?= owBi('Put it in a strong cardboard box that fits snugly, with no empty space', 'ضعها في صندوق كرتون متين يناسب مقاسها تماما، دون فراغات') ?></span></li>
      <li class="card pk-step"><span class="pk-ico"><svg viewBox="0 0 64 64" aria-hidden="true"><path d="M6 26l22-9 22 9v22L28 57 6 48z"/><path d="M6 26l22 9 22-9M28 35v22"/><path d="M11 37l12 5v6l-12-5z"/><rect x="40" y="6" width="20" height="16" rx="2"/><path d="M44 12h12M44 16h8"/></svg></span><span class="pk-txt"><b class="pk-n">5</b><?= owBi('Write the certificate number on the box, and put the back label on the artwork', 'اكتب رقم الشهادة على الصندوق، وألصق ملصق الظهر على العمل') ?></span></li>
  </ol>
  <p class="note" style="margin-top:var(--space-sm)"><?= owBi('Your certificate numbers and back labels are in', 'تجد أرقام شهاداتك وملصقات الظهر في') ?> <a href="<?= e($tabLink('certs')) ?>"><?= owBi('Certificates', 'الشهادات') ?></a>.</p>

<?php elseif ($tab === 'activity'): ?>
  <div class="card">
    <?php if (!$activity): ?>
      <p class="empty"><?= owBi('Your activity will show here', 'سيظهر نشاطك هنا') ?></p>
    <?php else: ?>
    <ul class="act-list">
      <?php foreach ($activity as $a): $w = OW_ACTION_WORDS[$a['action']] ?? [ucfirst(str_replace('_', ' ', $a['action'])), $a['action']]; ?>
        <li><span><?= owBi($w[0], $w[1]) ?></span><span class="act-when"><?= $fmtDate($a['created_at'], true) ?></span></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>

<?php elseif ($tab === 'workshops'): include __DIR__ . '/artist_ws_tab.php'; ?>
<?php else: /* earnings: sales and payouts */ ?>
  <p class="o-note"><?= owBi('When a work sells, Oweili keeps ' . owCommissionText() . ' (before VAT), which covers shipping and advertising. A work already sold keeps the rate it was sold at.', 'عند بيع عمل، يحتفظ أويلي بـ' . owCommissionText() . ' (قبل الضريبة) تغطي الشحن والإعلان. ويبقى العمل المباع سابقا على النسبة التي بيع بها.') ?></p>
  <div class="o-kpis c-kpis" style="--k:4">
    <div class="o-t o-kpi"><span class="o-kl"><?= owBi('Works sold', 'أعمال مباعة') ?></span><span class="o-kv"><?= $sum['sold'] ?></span></div>
    <div class="o-t o-kpi"><span class="o-kl"><?= owBi('Sales total', 'إجمالي المبيعات') ?></span><span class="o-kv"><?= $money($sum['sales']) ?></span></div>
    <div class="o-t o-kpi"><span class="o-kl"><?= owBi('Your share, all time', 'حصتك الإجمالية') ?></span><span class="o-kv"><?= $money($sum['share']) ?></span></div>
    <div class="o-t o-kpi"><span class="o-kl"><?= owBi('Your share this year', 'حصتك هذا العام') ?></span><span class="o-kv"><?= $money($sum['share_year']) ?></span></div>
  </div>

  <?php if ($payOn): ?>
  <h2 class="c-sec"><?= owBi('Orders', 'الطلبات') ?></h2>
  <?php if (!$orders): ?><div class="o-t"><p class="o-empty"><?= owBi('No orders yet', 'لا طلبات بعد') ?></p></div>
  <?php else: ?><div class="c-list">
    <?php foreach ($orders as $o): $ow = $ORDER_WORDS[$o['status']] ?? ['', '', '']; $pw = $payWord($o); [$dEn, $dAr] = owDateBi((string) ($o['paid_at'] ?: $o['created_at'])); ?>
    <div class="o-t c-order">
      <div class="c-orow"><b><?= owBi((string) ($o['title_en'] ?: $o['title']), (string) ($o['title_ar'] ?: $o['title'])) ?></b><span class="o-sm" dir="ltr"><?= e($o['order_no']) ?></span></div>
      <span class="o-sm"><?= owBi('Sold ' . $dEn, 'بيع في ' . $dAr) ?> · <?= owBi('price', 'السعر') ?> <?= $money($o['amount']) ?> · <?= owBi('your share', 'حصتك') ?> <?= $money((float) $o['artist_amount'] > 0 ? $o['artist_amount'] : round((float) $o['amount'] * (1 - (float) $o['commission_rate'] / 100), 2)) ?> <?= owBi('(after ' . owCommissionText((float) $o['commission_rate']) . ')', '(بعد ' . owCommissionText((float) $o['commission_rate']) . ')') ?></span>
      <div class="a-pills"><span class="c-pill <?= e($ow[2]) ?>"><?= owBi($ow[0], $ow[1]) ?></span><span class="c-pill <?= e($pw[2]) ?>"><?= owBi('Payout: ' . $pw[0], 'الدفعة: ' . $pw[1]) ?></span></div>
      <?php if ($o['status'] === 'paid'): ?><a class="o-more" href="<?= e($tabLink('packing')) ?>"><?= owBi('How to pack it', 'طريقة التغليف') ?></a><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div><?php endif; ?>
  <?php endif; ?>

  <h2 class="c-sec"><?= owBi('Every sale', 'كل المبيعات') ?></h2>
  <?php if (!$sales): ?>
    <div class="o-t"><p class="o-empty"><?= owBi('No sales yet. Your sold works will appear here', 'لا مبيعات بعد. ستظهر أعمالك المباعة هنا') ?></p></div>
  <?php else: ?>
  <div class="o-t o-tablewrap"><table class="o-table">
    <thead><tr><th><?= owBi('Artwork', 'العمل') ?></th><th><?= owBi('Sold on', 'تاريخ البيع') ?></th><th><?= owBi('Price', 'السعر') ?></th><th><?= owBi('Oweili commission', 'عمولة أويلي') ?></th><th><?= owBi('Your share', 'حصتك') ?></th></tr></thead>
    <tbody><?php foreach ($sales as $s): $sRate = owCommissionAt((string) $s['sold_on']); $sh = owArtistShare((float) $s['price'], (string) $s['sold_on']); ?>
      <tr><td><?= owBi((string) ($s['title_en'] ?: $s['title_ar']), (string) ($s['title_ar'] ?: $s['title_en'])) ?></td><td><?= $fmtDate($s['sold_on']) ?></td><td><?= $money($s['price']) ?></td>
        <td><?= $money((float) $s['price'] - $sh) ?> <span class="o-ks">(<?= e(owCommissionText($sRate)) ?>)</span></td><td><b><?= $money($sh) ?></b></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php endif; ?>

  <h2 class="c-sec"><?= owBi('Month by month, last 12 months', 'شهرا بشهر، آخر 12 شهرا') ?></h2>
  <div class="o-t o-tablewrap"><table class="o-table">
    <thead><tr><th><?= owBi('Month', 'الشهر') ?></th><th><?= owBi('Works sold', 'أعمال مباعة') ?></th><th><?= owBi('Sales', 'المبيعات') ?></th><th><?= owBi('Your share', 'حصتك') ?></th></tr></thead>
    <tbody><?php foreach (array_reverse($months, true) as $m => $v): [$mEn, $mAr] = owDateBi($m . '-01'); ?>
      <tr><td><?= owBi(preg_replace('/^\d+\s+/', '', $mEn), preg_replace('/^\d+\s+/u', '', $mAr)) ?></td><td><?= $v['n'] ?></td><td><?= $money($v['sales']) ?></td><td><b><?= $money($v['share']) ?></b></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>

  <?php /* the collectors' "I received the artwork" photos: private, only the artist and the admin see them (owner, 14 Nov 2026; only once payments are switched on) */
    $rcpts = [];
    try { if ($payOn) { foreach ($orders as $ro) { if (empty($ro['confirmed_at'])) continue; $rc = owOrderReceipt((int) $ro['id']); if ($rc && $rc['photos']) $rcpts[] = [$ro, $rc]; } } } catch (Throwable $e) { $rcpts = []; }
    if ($rcpts): ?>
  <h2 class="c-sec"><?= owBi('Your artworks as the collectors received them', 'أعمالك كما استلمها المقتنون') ?></h2>
  <p class="o-note"><?= owBi('Private photos: only you and the Oweili team see them', 'صور خاصة: لا يراها إلا أنت وفريق أويلي') ?></p>
  <div class="c-list"><?php foreach ($rcpts as [$ro, $rc]): ?><div class="o-t"><b><?= owBi((string) ($ro['title_en'] ?: $ro['title']), (string) ($ro['title_ar'] ?: $ro['title'])) ?></b> <span class="o-sm" dir="ltr"><?= e($ro['order_no']) ?></span>
    <div class="c-rphotos"><?php foreach ($rc['photos'] as $ri => $_): ?><a href="<?= e(owReceiptPhotoUrl((int) $ro['id'], $ri)) ?>" target="_blank" rel="noopener"><img loading="lazy" src="<?= e(owReceiptPhotoUrl((int) $ro['id'], $ri)) ?>" alt="<?= e('Your artwork as received · عملك كما وصل') ?>"></a><?php endforeach; ?></div></div>
  <?php endforeach; ?></div>
  <?php endif; ?>

  <h2 class="c-sec"><?= owBi('Payouts', 'المدفوعات') ?></h2>
  <?php if ($payouts): ?><p class="o-note"><?= owBi('Paid to you: ', 'دُفع لك: ') ?><b><?= $money($payoutPaid) ?></b><?php if ($payoutWait > 0): ?> · <?= owBi('on its way: ', 'قيد التحويل: ') ?><b><?= $money($payoutWait) ?></b><?php endif; ?></p><?php endif; ?>
  <?php if (!$payouts): ?>
    <div class="o-t"><p class="o-empty"><?= owBi('No payouts yet. Once payments open, every transfer to you will be listed here', 'لا مدفوعات بعد. حين تفتح المدفوعات، ستظهر هنا كل تحويلاتك') ?></p></div>
  <?php else: $PST = ['pending' => ['On its way', 'قيد التحويل'], 'paid' => ['Paid', 'مدفوعة'], 'failed' => ['Not sent, Oweili will contact you', 'لم تُرسل، ستتواصل معك أويلي'], 'cancelled' => ['Cancelled', 'ملغاة']]; ?>
  <div class="o-t o-tablewrap"><table class="o-table">
    <thead><tr><th><?= owBi('Date', 'التاريخ') ?></th><th><?= owBi('Amount', 'المبلغ') ?></th><th><?= owBi('Status', 'الحالة') ?></th></tr></thead>
    <tbody><?php foreach ($payouts as $p): $pl = $PST[$p['status']] ?? [ucfirst((string) $p['status']), (string) $p['status']]; ?>
      <tr><td><?= $fmtDate($p['created_at']) ?></td><td><b><?= $money($p['amount']) ?></b></td><td><?= owBi($pl[0], $pl[1]) ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php endif; ?>
<?php endif; ?>
    </main>
  </div>
</div>
<script src="<?= owAsset('assets/js/owner.js') ?>" defer></script>
</body>
</html>
