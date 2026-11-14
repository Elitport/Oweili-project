<?php
/* =====================================================================
 *  ticket_buy.php?p=N — buy tickets for a partner museum: choose the date
 *  and how many of each ticket type, then pay on Oweili.
 *  HIDDEN until the bookings switch is on (booking_lib.php).
 *  Tables: art_places, museum_ticket_types, museum_ticket_dates, museum_tickets, booking_payments
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/booking_lib.php';
$preview = owBookingGate();
owBookingCleanup();

$p = owMuseumGet((int) ($_GET['p'] ?? 0));
if (!$p || $p['status'] !== 'published' || (int) $p['partner'] !== 1) { http_response_code(404); require __DIR__ . '/404.php'; exit; }
$types = owMuseumTypes((int) $p['id']);
$dates = array_values(array_filter(owMuseumDates((int) $p['id']), fn($d) => (int) $d['capacity'] > (int) $d['sold']));
$me = isLoggedIn() ? currentUser() : null;
$err = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!$me) { header('Location: login.php?next=' . rawurlencode('ticket_buy.php?p=' . (int) $p['id'])); exit; }
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) $err = ['The page was open too long. Please try again.', 'انتهت صلاحية الصفحة. حاول مرة أخرى.'];
    elseif (($me['role'] ?? '') !== 'collector') $err = ['Please sign in with a collector account to buy tickets.', 'يرجى الدخول بحساب مقتنٍ لشراء التذاكر.'];
    elseif (empty($_POST['agree'])) $err = ['Please tick that you read the booking rules.', 'يرجى التأشير على أنك قرأت قواعد الحجز.'];
    else {
        $name = mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 150);
        $phone = trim((string) ($_POST['phone'] ?? ''));
        if (owNameProblem($name, true) !== '') $err = ['Please write your name in letters.', 'يرجى كتابة اسمك بالحروف.'];
        elseif (!preg_match('/^[+0-9][0-9 \-()]{6,29}$/', $phone)) $err = ['Please enter a valid mobile number.', 'يرجى إدخال رقم جوال صالح.'];
        else {
            [$tid, $err] = owTicketHold($p, (int) ($_POST['date'] ?? 0), (array) ($_POST['qty'] ?? []), $me, $name, (string) $me['email'], $phone);
            if (!$err) {
                $t = getDB()->prepare('SELECT amount FROM museum_tickets WHERE id = :id'); $t->execute([':id' => $tid]);
                require_once __DIR__ . '/pay_lib.php';
                $pay = owPayNew('ticket', $tid, (int) $me['id'], (float) $t->fetchColumn());
                header('Location: pay.php?t=' . $pay['token']); exit;
            }
        }
    }
}
owBookTop('Tickets: ' . $p['name_en'], 'تذاكر: ' . $p['name_ar'], 'ticket_buy.php?p=' . (int) $p['id'], $preview, '
.tt{display:grid;grid-template-columns:1fr auto auto;gap:var(--space-sm);align-items:center;padding:10px 0;border-bottom:1px solid var(--color-border);}
.tt input{width:72px;text-align:center;}
.tt-total{font-size:22px;font-weight:800;margin-top:var(--space-sm);}
', !owBookingsOn());
?>
<div class="card">
  <p class="mini" style="margin:0"><?= owBi($p['city_en'], $p['city_ar']) ?></p>
  <h1><?= owBi('Tickets: ' . $p['name_en'], 'تذاكر: ' . $p['name_ar']) ?></h1>
  <?php $dE = owExcerpt((string) ($p['desc_en'] ?: $p['desc_ar']), 220); $dA = owExcerpt((string) ($p['desc_ar'] ?: $p['desc_en']), 220); if ($dE !== ''): ?><p><?= owBi($dE, $dA) ?></p><?php endif; ?>
  <p class="mini"><?= owBi('Paid on Oweili. Your ticket arrives by email with a QR code to show at the entrance.', 'الدفع عبر أويلي. تصلك التذكرة بالبريد مع رمز QR تعرضه عند المدخل.') ?></p>
</div>
<?php if ($err): ?><div class="alert alert-error" role="alert"><?= owBi($err[0], $err[1]) ?></div><?php endif; ?>
<?php if (!$types || !$dates): ?>
  <div class="card"><p><?= owBi('No tickets on sale right now. Please check again soon.', 'لا توجد تذاكر للبيع الآن. يرجى المراجعة قريباً.') ?></p></div>
<?php elseif (!$me): ?>
  <div class="card"><a class="btn btn-primary" href="login.php?next=<?= e(rawurlencode('ticket_buy.php?p=' . (int) $p['id'])) ?>"><?= owBi('Sign in to buy tickets', 'سجّل الدخول لشراء التذاكر') ?></a>
    <p class="mini"><?= owBi('New here?', 'جديد هنا؟') ?> <a href="register_collector.php"><?= owBi('Create a free account', 'أنشئ حساباً مجانياً') ?></a></p></div>
<?php else: ?>
<form method="post" action="ticket_buy.php?p=<?= (int) $p['id'] ?>" class="card" id="tform"><?= csrfField() ?>
  <div class="field"><label for="t-date"><?= owBi('Visit date', 'تاريخ الزيارة') ?></label>
    <select id="t-date" name="date" required>
      <?php foreach ($dates as $d): [$de, $da] = owDateRangeBi($d['visit_date'], null); $left = (int) $d['capacity'] - (int) $d['sold']; ?>
      <option value="<?= (int) $d['id'] ?>"<?= (int) ($_POST['date'] ?? 0) === (int) $d['id'] ? ' selected' : '' ?>><?= e(owIsAr() ? $da . ' · متبقٍ ' . $left : $de . ' · ' . $left . ' left') ?></option>
      <?php endforeach; ?>
    </select></div>
  <h2><?= owBi('Tickets', 'التذاكر') ?></h2>
  <?php foreach ($types as $t): ?>
  <div class="tt"><label for="q<?= (int) $t['id'] ?>"><?= owBi($t['name_en'], $t['name_ar']) ?></label><span><?= owSar((float) $t['price']) ?></span>
    <input id="q<?= (int) $t['id'] ?>" type="number" name="qty[<?= (int) $t['id'] ?>]" min="0" max="20" value="<?= (int) ($_POST['qty'][$t['id']] ?? 0) ?>" data-price="<?= e((string) (float) $t['price']) ?>" inputmode="numeric"></div>
  <?php endforeach; ?>
  <div class="tt-total"><?= owBi('Total', 'الإجمالي') ?>: <span id="tt-sum" dir="ltr">0</span> <?= owBi('SAR', 'ر.س') ?></div>
  <div class="g2" style="margin-top:var(--space-sm)">
    <div class="field"><label for="b-name"><?= owBi('Your full name', 'اسمك كاملاً') ?></label><input id="b-name" name="name" required maxlength="150" dir="auto" value="<?= e($_POST['name'] ?? owRealName($me['full_name_en'] ?? '')) ?>"></div>
    <div class="field"><label for="b-phone"><?= owBi('Mobile number', 'رقم الجوال') ?></label><input id="b-phone" name="phone" type="tel" required maxlength="30" dir="ltr" value="<?= e($_POST['phone'] ?? ($me['phone'] ?? '')) ?>"></div>
  </div>
  <p class="mini"><?= owBi('The ticket goes to ' . $me['email'] . '.', 'تصل التذكرة إلى ' . $me['email'] . '.') ?></p>
  <details style="margin:var(--space-xs) 0"><summary><?= owBi('Booking and refund rules', 'قواعد الحجز والاسترداد') ?></summary><?= owBookingTermsHtml() ?></details>
  <label class="chk" style="display:flex;gap:6px;align-items:flex-start;margin:var(--space-sm) 0"><input type="checkbox" name="agree" value="1" required> <span><?= owBi('I read the booking and refund rules.', 'قرأت قواعد الحجز والاسترداد.') ?></span></label>
  <button class="btn btn-primary" type="submit"><?= owBi('Continue to payment', 'متابعة إلى الدفع') ?></button>
</form>
<script>
(function(){ var f = document.getElementById('tform'); if (!f) return; var out = document.getElementById('tt-sum');
  function sum(){ var s = 0; f.querySelectorAll('input[data-price]').forEach(function(i){ s += (parseInt(i.value, 10) || 0) * parseFloat(i.dataset.price); }); out.textContent = s.toFixed(s % 1 ? 2 : 0); }
  f.addEventListener('input', sum); sum(); })();
</script>
<?php endif; ?>
<?php owBookBottom();
