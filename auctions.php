<?php
/* =====================================================================
 *  auctions.php — "Oweili Auctions, Coming soon" («مزادات أويلي، قريباً»)
 *  Also at oweili.com/auctions (.htaccess). A teaser before real auctions:
 *    - the REAL number of people waiting (auction_waitlist), counted live
 *      (auctions.php?count=1 answers {"n": …}; the page asks every 30 s)
 *    - "Notify me": name (optional) + email, one row per email, thank-you
 *      message, confirmation email with an unsubscribe link
 *    - auctions.php?leave=ID&t=TOKEN: unsubscribe (asks first, then deletes)
 *  Protection: security token, hidden robot trap, 3 sign-ups per visitor
 *  per hour plus a cap for the whole site (request_lib.php owReqLimited).
 *  Ready for later: when real auctions exist (auction_lib.php owAuctionsLive)
 *  this address sends visitors to the auction room; no bidding or payment now.
 *  Tables: auction_waitlist, activity_log, users
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/gallery_lib.php';
require_once __DIR__ . '/admin_shell.php';
/* Hidden from visitors (owner, 5 Nov 2026) until switched on in Admin → Settings: everyone
   gets the normal "page not found", except a signed-in admin (preview). Unsubscribe links
   (?leave=) from earlier confirmation emails keep working, so people can always leave the list. */
$owAucPreview = false;
if (!owAuctionsPageOn() && !isset($_GET['leave']) && !isset($_POST['leave'])) {
    if (!owIsAdmin()) { require_once __DIR__ . '/page_cache.php'; http_response_code(404); require __DIR__ . '/404.php'; exit; }
    $owAucPreview = true;   // the admin's preview: never kept by the browser (no-store stays)
} else {
    require_once __DIR__ . '/page_cache.php'; owPublicPage();   // public page: Back opens at once, early load on touch (page_cache.php)
}
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/request_lib.php';
require_once __DIR__ . '/auction_lib.php';

if (owAuctionsLive()) { header('Location: auction_room.php', true, 302); exit; }   // later: the real auction page

/* The live counter, for the page's script */
if (isset($_GET['count'])) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode(['n' => owAuctionCount()]);
    exit;
}

$ready = owAuctionReady();
$me = isLoggedIn() ? currentUser() : null;
$errors = []; $f = ['name' => '', 'email' => $me ? (string) $me['email'] : ''];

/* ---- Unsubscribe ---- */
$leaveRow = null; $left = false;
if (isset($_GET['leave']) || isset($_POST['leave'])) {
    $leaveRow = owAuctionByToken((int) ($_GET['leave'] ?? $_POST['leave'] ?? 0), (string) ($_GET['t'] ?? $_POST['t'] ?? ''));
    if ($leaveRow && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && verifyCsrf($_POST['csrf_token'] ?? null)) {
        owAuctionLeave($leaveRow);
        header('Location: auctions.php?left=1'); exit;
    }
}
$left = isset($_GET['left']);

/* ---- Notify me ---- */
if ($ready && !$leaveRow && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = ['This page was open for a long time. Please try again.', 'كانت الصفحة مفتوحة لوقت طويل. يرجى المحاولة مرة أخرى.'];
    } elseif (trim((string) ($_POST['website'] ?? '')) !== '') {
        header('Location: auctions.php?joined=1#join'); exit;          // a robot filled the hidden field
    } elseif (owReqLimited('auction_signup', 3)) {
        $errors[] = ['You have signed up several times. Please try again in an hour.', 'سجّلت عدة مرات. حاول مرة أخرى بعد ساعة.'];
    } else {
        $f['name'] = owReqClean('name', 120);
        $f['email'] = strtolower(owReqClean('email', 190));
        if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors[] = ['Please write a valid email address.', 'يرجى كتابة بريد إلكتروني صحيح.'];
        if ($f['name'] !== '' && owNameProblem($f['name'], false) !== '') $errors[] = ['Please write your name with letters, or leave it empty.', 'يرجى كتابة اسمك بالحروف، أو اتركه فارغاً.'];
        if (!$errors) {
            $r = owAuctionJoin($f['name'], $f['email'], ($_POST['lang'] ?? '') === 'ar' ? 'ar' : 'en', $me ? (int) $me['id'] : null);
            if ($r === 'new') owReqCounted('auction_signup');
            if ($r !== 'error') { header('Location: auctions.php?joined=' . ($r === 'new' ? '1' : '2') . '#join'); exit; }
            $errors[] = ['Something went wrong. Please try again.', 'حدث خطأ. يرجى المحاولة مرة أخرى.'];
        }
    }
}
$joined = (string) ($_GET['joined'] ?? '');
$count = owAuctionCount();
$v = fn(string $k) => e((string) $f[$k]);
?>
<!DOCTYPE html>
<html <?php require_once __DIR__ . '/lang_lib.php'; owHtmlAttrs(); ?> data-title-en="Oweili Auctions, Coming soon" data-title-ar="مزادات أويلي، قريباً">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require_once __DIR__ . '/seo_lib.php'; owSeo(['en' => 'Oweili Auctions, Coming soon', 'ar' => 'مزادات أويلي، قريباً',
  'desc_en' => 'Rare original artworks are coming to auction on Oweili. Join the list and be the first to know.', 'desc_ar' => 'أعمال فنية أصلية ونادرة في طريقها إلى المزاد في أويلي. انضم إلى القائمة وكن أول من يعرف.',
  'path' => 'auctions'] + ($leaveRow || $left ? ['noindex' => true] : [])); ?>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/nav.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/discover.css') ?>">
<script>document.documentElement.classList.add('js')</script><script defer src="<?= owAsset('assets/js/discover.js') ?>"></script>
<style>
/* Oweili Auctions (teaser) — layout only; every colour comes from oweili.css */
.au-band{position:relative;z-index:2;margin-top:var(--nav-h);overflow:hidden;background:var(--color-accent);color:var(--color-on-dark);}
html.dark-mode .au-band{background:var(--color-surface-2);color:var(--color-dark);border-bottom:1px solid var(--color-border);}
.au-band::before{content:"";position:absolute;inset:0;background:radial-gradient(60% 80% at 85% 10%,rgba(var(--rgb-primary),.35),transparent 60%),radial-gradient(50% 70% at 10% 100%,rgba(var(--rgb-primary),.18),transparent 60%);pointer-events:none;}
.au-pattern{position:absolute;inset:0;opacity:.09;pointer-events:none;}
.au-pattern path{fill:none;stroke:currentColor;stroke-width:1;}
.au-inner{position:relative;max-width:1200px;margin:0 auto;padding:var(--space-2xl) var(--space-lg) var(--space-2xl);display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,.85fr);gap:var(--space-xl);align-items:center;}
.au-kicker{display:inline-flex;align-items:center;gap:8px;padding:6px 14px;border-radius:var(--radius-pill);border:1px solid rgba(var(--rgb-primary),.6);font-size:var(--fs-min);font-weight:700;letter-spacing:.14em;text-transform:uppercase;}
[dir="rtl"] .au-kicker{letter-spacing:0;font-size:13px;}
.au-kicker i{width:8px;height:8px;border-radius:50%;background:var(--color-primary);box-shadow:0 0 0 0 rgba(var(--rgb-primary),.7);animation:auPulse 2s infinite;}
.au-band h1{font-size:clamp(40px,6vw,72px);font-weight:300;letter-spacing:-.03em;line-height:1.05;margin:var(--space-md) 0 var(--space-sm);color:inherit;}
[dir="rtl"] .au-band h1{letter-spacing:0;line-height:1.25;}
.au-band h1 b{font-weight:700;color:var(--color-primary);display:block;}
.au-sub{font-size:18px;line-height:1.6;max-width:520px;margin:0;opacity:.9;}
.au-count{margin-top:var(--space-lg);display:flex;align-items:baseline;gap:var(--space-sm);flex-wrap:wrap;}
.au-num{font-size:clamp(48px,7vw,80px);font-weight:700;line-height:1;font-variant-numeric:tabular-nums;color:var(--color-primary);}
.au-num-l{font-size:var(--fs-body);font-weight:600;max-width:260px;opacity:.95;}
.au-art{position:relative;aspect-ratio:4/5;max-width:380px;justify-self:center;width:100%;}
.au-frame{position:absolute;inset:0;border-radius:4px;background:linear-gradient(160deg,rgba(var(--rgb-primary),.45),rgba(var(--rgb-primary),.08));box-shadow:0 30px 60px rgba(0,0,0,.35),inset 0 0 0 10px rgba(255,255,255,.08);transform:rotate(-3deg);}
.au-frame.b{transform:rotate(4deg) translate(18px,14px);opacity:.55;}
.au-gavel{position:absolute;inset:18% 14%;width:72%;height:64%;}
.au-gavel path,.au-gavel rect,.au-gavel line{fill:none;stroke:currentColor;stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round;}
.au-lot{position:absolute;bottom:8%;inset-inline-start:8%;padding:6px 12px;border-radius:var(--radius-pill);background:rgba(0,0,0,.25);font-size:var(--fs-min);font-weight:700;letter-spacing:.12em;}
[dir="rtl"] .au-lot{letter-spacing:0;}
.au-main{max-width:1100px;margin:0 auto;padding:var(--space-xl) var(--space-lg) var(--space-2xl);position:relative;z-index:2;}
.au-join{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:var(--space-lg);padding:var(--space-xl);margin-top:calc(-1 * var(--space-2xl));position:relative;}
.au-join h2{font-size:var(--fs-section);margin:0 0 var(--space-xs);}
.au-join p{margin:0 0 var(--space-sm);}
.au-form{display:grid;gap:var(--space-xs);align-content:start;}
.au-form .field{margin:0;}
.au-note{font-size:var(--fs-caption);color:var(--color-body);margin:0;}
.trap{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);clip-path:inset(50%);white-space:nowrap;}
.au-ok{display:flex;gap:var(--space-sm);align-items:flex-start;}
.au-ok svg{width:40px;height:40px;flex-shrink:0;fill:none;stroke:var(--color-primary);stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
.au-ok b{display:block;font-size:var(--fs-subtitle);color:var(--color-dark);margin-bottom:4px;}
.au-why{display:grid;grid-template-columns:repeat(3,1fr);gap:var(--space-sm);margin-top:var(--space-xl);}
.au-why .card{padding:var(--space-md);}
.au-why svg{width:34px;height:34px;padding:7px;border-radius:12px;background:var(--color-tint-pink);fill:none;stroke:var(--color-dark);stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round;margin-bottom:var(--space-xs);}
.au-why h3{font-size:var(--fs-body);margin:0 0 4px;}
.au-why p{margin:0;font-size:var(--fs-caption);color:var(--color-body);}
.au-steps{list-style:none;margin:var(--space-xl) 0 0;padding:0;display:grid;grid-template-columns:repeat(3,1fr);gap:var(--space-sm);counter-reset:au;}
.au-steps li{position:relative;padding:var(--space-md);padding-inline-start:calc(var(--space-md) + 44px);border-top:2px solid var(--color-border-strong);}
.au-steps li::before{counter-increment:au;content:counter(au);position:absolute;inset-inline-start:var(--space-md);top:var(--space-md);width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--color-dark);color:var(--color-on-dark);font-weight:700;}
.au-steps b{display:block;color:var(--color-dark);}
.au-steps span{font-size:var(--fs-caption);color:var(--color-body);}
.au-more{text-align:center;margin-top:var(--space-xl);}
@keyframes auPulse{0%{box-shadow:0 0 0 0 rgba(var(--rgb-primary),.7);}70%{box-shadow:0 0 0 10px rgba(var(--rgb-primary),0);}100%{box-shadow:0 0 0 0 rgba(var(--rgb-primary),0);}}
@media (prefers-reduced-motion: reduce){.au-kicker i{animation:none;}}
@media(max-width:900px){.au-inner{grid-template-columns:1fr;padding:var(--space-xl) var(--space-sm) calc(var(--space-2xl) + var(--space-lg));}.au-art{max-width:240px;order:-1;}
  .au-join{grid-template-columns:1fr;padding:var(--space-md);}.au-why,.au-steps{grid-template-columns:1fr;}.au-main{padding:var(--space-md) var(--space-sm) var(--space-xl);}}
</style>
</head>
<body class="ow">
<?php include __DIR__ . '/nav.php'; ?>

<section class="au-band" aria-labelledby="au-h">
  <svg class="au-pattern" aria-hidden="true" width="100%" height="100%"><defs><pattern id="au-p" width="56" height="56" patternUnits="userSpaceOnUse"><path d="M28 2 54 28 28 54 2 28Z M28 14 42 28 28 42 14 28Z"/></pattern></defs><rect width="100%" height="100%" fill="url(#au-p)" style="fill:url(#au-p);stroke:none"/></svg>
  <div class="au-inner">
    <div>
      <span class="au-kicker reveal"><i aria-hidden="true"></i><?= owBi('Coming soon', 'قريباً') ?></span>
      <h1 id="au-h" class="reveal"><?= owBi('Oweili Auctions,', 'مزادات أويلي،') ?><b><?= owBi('Coming soon', 'قريباً') ?></b></h1>
      <p class="au-sub reveal"><?= owBi('Rare original artworks, each one of a kind, are going to auction. Every lot comes with its story and its certificate.', 'أعمال فنية أصلية ونادرة، كل واحد منها فريد، في طريقها إلى المزاد. ولكل عمل حكايته وشهادته.') ?></p>
      <div class="au-count reveal" aria-live="polite">
        <span class="au-num" id="au-num" data-n="<?= $count ?>"><?= number_format($count) ?></span>
        <span class="au-num-l"><span class="bi-en"><?= $count === 1 ? 'person is' : 'people are' ?> waiting for the first auction</span><span class="bi-ar">شخص بانتظار أول مزاد</span></span>
      </div>
    </div>
    <div class="au-art" aria-hidden="true">
      <span class="au-frame b"></span><span class="au-frame"></span>
      <svg class="au-gavel" viewBox="0 0 120 110"><rect x="18" y="10" width="44" height="20" rx="4" transform="rotate(-35 40 20)"/><line x1="46" y1="30" x2="86" y2="74"/><rect x="20" y="92" width="80" height="10" rx="3"/><path d="M34 92v-6h52v6"/></svg>
      <span class="au-lot">LOT 001</span>
    </div>
  </div>
</section>

<main class="au-main">
<?php if ($owAucPreview): ?>
  <div class="alert alert-error" role="status" style="max-width:1100px;margin:0 auto var(--space-md);"><?= owBi('Preview for the admin only. Visitors see "page not found" until you switch this page on in Admin → Settings.', 'معاينة للمدير فقط. يرى الزوار «الصفحة غير موجودة» حتى تُفعّل هذه الصفحة من الإدارة ← الإعدادات.') ?></div>
<?php endif; ?>
  <div class="card au-join reveal" id="join">
    <div>
      <h2><?= owBi('Be the first to know', 'كن أول من يعرف') ?></h2>
      <p><?= owBi('Leave your email and we will tell you the moment the first auction opens. One short email, no noise.', 'اترك بريدك وسنخبرك لحظة افتتاح أول مزاد. رسالة واحدة قصيرة، بلا إزعاج.') ?></p>
      <p class="au-note"><?= owBi('We use your email only to tell you about Oweili auctions. Every email has a link to unsubscribe.', 'نستخدم بريدك فقط لإخبارك بمزادات أويلي. وفي كل رسالة رابط لإلغاء الاشتراك.') ?> <a href="privacy.php"><?= owBi('Privacy Policy', 'سياسة الخصوصية') ?></a></p>
    </div>
    <div>
    <?php if ($leaveRow): ?>
      <form class="au-form" method="post" action="auctions.php"><?= csrfField() ?><input type="hidden" name="leave" value="<?= (int) $leaveRow['id'] ?>"><input type="hidden" name="t" value="<?= e((string) $leaveRow['unsub_token']) ?>">
        <p><b><?= owBi('Unsubscribe from Oweili Auctions?', 'إلغاء الاشتراك في مزادات أويلي؟') ?></b></p>
        <p class="au-note" dir="ltr"><?= e((string) $leaveRow['email']) ?></p>
        <p class="au-note"><?= owBi('Your email will be deleted from the list.', 'سيُحذف بريدك من القائمة.') ?></p>
        <button class="btn btn-secondary" type="submit"><?= owBi('Yes, unsubscribe me', 'نعم، ألغِ اشتراكي') ?></button></form>
    <?php elseif ($left): ?>
      <div class="au-ok" role="status"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/></svg>
        <div><b><?= owBi('You are unsubscribed.', 'تم إلغاء اشتراكك.') ?></b><?= owBi('Your email is no longer on the list. You can join again any time.', 'لم يعد بريدك في القائمة. يمكنك الانضمام مجدداً في أي وقت.') ?></div></div>
    <?php elseif ((isset($_GET['leave']) || isset($_POST['leave'])) && !$leaveRow): ?>
      <div class="au-ok" role="status"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg>
        <div><b><?= owBi('This link is not on the list any more.', 'هذا الرابط لم يعد في القائمة.') ?></b><?= owBi('You may have unsubscribed already. If you still get emails, write to us at ', 'ربما ألغيت اشتراكك من قبل. إن كانت الرسائل ما زالت تصلك فراسلنا على ') ?><a href="mailto:<?= e(owContactEmail()) ?>" dir="ltr"><?= e(owContactEmail()) ?></a></div></div>
    <?php elseif ($joined !== ''): ?>
      <div class="au-ok" role="status"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M7.5 12.5l3 3 6-6"/></svg>
        <div><b><?= owBi('Thank you, you are on the list!', 'شكراً لك، أنت في القائمة!') ?></b><?= $joined === '2' ? owBi('This email was already on the list, so nothing changed.', 'هذا البريد موجود في القائمة من قبل، فلم يتغير شيء.') : owBi('We sent you a confirmation email. See you at the first auction.', 'أرسلنا لك رسالة تأكيد. نراك في أول مزاد.') ?></div></div>
      <p class="au-note" style="margin-top:var(--space-sm)"><a href="marketplace.php"><?= owBi('Meanwhile, explore the Gallery →', 'وحتى ذلك الحين، استكشف المعرض ←') ?></a></p>
    <?php elseif (!$ready): ?>
      <p><?= owBi('The list opens in a moment. Please check back soon.', 'تُفتح القائمة خلال لحظات. عد قريباً.') ?></p>
    <?php else: ?>
      <form class="au-form" method="post" action="auctions.php#join" novalidate>
        <?= csrfField() ?><input type="hidden" name="lang" value="en" id="au-lang">
        <div class="trap" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <?php if ($errors): ?><div class="alert alert-error" role="alert"><?php foreach ($errors as [$en, $ar]): ?><div><?= owBi($en, $ar) ?></div><?php endforeach; ?></div><?php endif; ?>
        <div class="field"><label for="au-name"><?= owBi('Your name (optional)', 'اسمك (اختياري)') ?></label><input id="au-name" name="name" maxlength="120" autocomplete="name" dir="auto" value="<?= $v('name') ?>"></div>
        <div class="field"><label for="au-email"><?= owBi('Your email', 'بريدك الإلكتروني') ?></label><input id="au-email" name="email" type="email" maxlength="190" required autocomplete="email" dir="ltr" value="<?= $v('email') ?>"></div>
        <button class="btn btn-primary btn-block" type="submit"><?= owBi('Notify me', 'أبلغني') ?></button>
      </form>
    <?php endif; ?>
    </div>
  </div>

  <div class="au-why">
    <div class="card reveal"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l2.6 6.3L21 9l-5 4.3L17.5 20 12 16.6 6.5 20 8 13.3 3 9l6.4-.7z"/></svg>
      <h3><?= owBi('Rare originals', 'أعمال أصلية نادرة') ?></h3><p><?= owBi('One-of-a-kind pieces, chosen by the Oweili team. Never prints.', 'قطع فريدة يختارها فريق أويلي. لا نسخ مطبوعة أبداً.') ?></p></div>
    <div class="card reveal"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="6" height="14" rx="1"/><rect x="9" y="5" width="6" height="14" rx="1"/><rect x="15" y="5" width="6" height="14" rx="1"/></svg>
      <h3><?= owBi('See how each was made', 'شاهد كيف صُنع كل عمل') ?></h3><p><?= owBi('Every lot shows its creation journey: the 25%, 50% and 100% photos.', 'يعرض كل عمل رحلة إبداعه: صور 25% و50% و100%.') ?></p></div>
    <div class="card reveal"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 14l2 2 4-4"/></svg>
      <h3><?= owBi('Certified authentic', 'أصالة موثّقة') ?></h3><p><?= owBi('Each piece has its Certificate of Authenticity, checkable at oweili.com/verify.', 'لكل قطعة شهادة أصالة يمكن التحقق منها في oweili.com/verify.') ?></p></div>
  </div>

  <ol class="au-steps">
    <li class="reveal"><b><?= owBi('Join the list', 'انضم إلى القائمة') ?></b><span><?= owBi('Today, with your email.', 'اليوم، ببريدك الإلكتروني.') ?></span></li>
    <li class="reveal"><b><?= owBi('Meet the lots', 'تعرّف على الأعمال') ?></b><span><?= owBi('We share the pieces before the auction opens.', 'نعرض القطع قبل افتتاح المزاد.') ?></span></li>
    <li class="reveal"><b><?= owBi('Bid when it opens', 'زايد عند الافتتاح') ?></b><span><?= owBi('The first auction opens soon. You will hear first.', 'يُفتح أول مزاد قريباً. وستعرف أنت أولاً.') ?></span></li>
  </ol>
  <div class="au-more reveal"><a class="btn btn-secondary" href="marketplace.php"><?= owBi('Explore the Gallery', 'استكشف المعرض') ?></a></div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
<script>
(function(){
  var f = document.querySelector('.au-form'), l = document.getElementById('au-lang');
  if (f && l) f.addEventListener('submit', function(){ l.value = document.documentElement.getAttribute('dir') === 'rtl' ? 'ar' : 'en'; });
  /* the live counter: the real number, counted up once, then checked every 30 seconds */
  var el = document.getElementById('au-num'); if (!el) return;
  var shown = 0, target = parseInt(el.dataset.n, 10) || 0, reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  function show(n){ el.textContent = n.toLocaleString('en-US'); }
  function roll(to){ if (reduce || to <= shown) { shown = to; show(to); return; } var from = shown, t0 = null;
    function step(t){ if (!t0) t0 = t; var p = Math.min(1, (t - t0) / 900); shown = Math.round(from + (to - from) * (1 - Math.pow(1 - p, 3))); show(shown); if (p < 1) requestAnimationFrame(step); }
    requestAnimationFrame(step); }
  roll(target);
  setInterval(function(){ if (document.hidden) return; fetch('auctions.php?count=1', {cache: 'no-store'}).then(function(r){ return r.json(); }).then(function(d){ if (d && typeof d.n === 'number') roll(d.n); }).catch(function(){}); }, 30000);
})();
</script>
</body>
</html>
