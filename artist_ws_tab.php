<?php
/* =====================================================================
 *  artist_ws_tab.php — the Workshops page inside My Studio
 *  (artist_studio.php?tab=workshops), owner, 25 Nov 2026. Only while the
 *  switch "Artist workshops" is on. Included by artist_studio.php, which
 *  has already checked the signed-in artist and handled the forms.
 *  1. Ask to offer workshops: the conditions with a tick each, the request
 *     form (only when every automatic condition is met), the answer.
 *  2. Once approved: add workshops; each one waits for the owner; the list
 *     shows the seats booked and only the first names of the people booked.
 *  Tables: artist_workshop_requests, artist_workshops, workshop_bookings
 * ===================================================================== */
if (!isset($uid, $me)) { http_response_code(404); exit; }
$wsReq = owWsLastRequest($uid);
$wsApproved = $wsReq && $wsReq['status'] === 'approved';
$wsChecks = owWsChecks($me);
$wsOk = owWsQualifies($me);
$wsRate = owWsCommission();
$wsList = $wsApproved ? owWsOfArtist($uid) : [];
$wsNames = $wsApproved ? owWsArtistBookings($uid) : [];
$wsEditId = (int) ($_GET['edit'] ?? ($wsOld['ws_id'] ?? 0));
$wsEdit = null;
if ($wsEditId > 0) { foreach ($wsList as $x) if ((int) $x['id'] === $wsEditId) $wsEdit = $x; }
$wsNew = $wsApproved && (isset($_GET['new']) || ($wsOld && ($wsOld['do'] ?? '') === 'ws_save' && !$wsEdit));
$v = function (string $k, string $def = '') use ($wsOld, $wsEdit) { if ($wsOld && array_key_exists($k, $wsOld)) return (string) $wsOld[$k]; return $wsEdit ? (string) ($wsEdit[$k] ?? $def) : $def; };
$wsAction = e($tabLink('workshops'));
?>
  <p class="o-note"><?= owBi('Teach your art on Oweili. You ask once to offer workshops, and the Oweili team checks your request and every workshop before visitors see it. Oweili keeps ' . owCommissionText($wsRate) . ' of each seat and handles the bookings, contact and payment.',
      'علّم فنك في أويلي. تطلب مرة واحدة تقديم ورش العمل، ويراجع فريق أويلي طلبك وكل ورشة قبل أن يراها الزوار. يحتفظ أويلي بـ' . owCommissionText($wsRate) . ' من كل مقعد ويتولى الحجوزات والتواصل والدفع.') ?></p>
  <?php foreach ($wsErrors as $er): ?><div class="o-flash bad" role="alert"><?= owBi($er[0], $er[1]) ?></div><?php endforeach; ?>

<?php if (!$wsApproved): ?>
  <section class="o-t">
    <div class="o-th"><h2><?= owBi('Conditions to offer workshops', 'شروط تقديم ورش العمل') ?></h2></div>
    <ul class="ws-checks">
      <?php foreach ($wsChecks as [$k, $en, $ar, $ok, $dEn, $dAr]): ?>
        <li class="<?= $ok ? 'ok' : '' ?>"><span class="ws-mark" aria-hidden="true"><?= $ok ? '✓' : '–' ?></span>
          <span><?= owBi($en, $ar) ?><span class="sr-only"><?= $ok ? owBi(' (met)', ' (متحقق)') : owBi(' (not yet)', ' (لم يتحقق بعد)') ?></span><?php if ($dEn !== ''): ?><span class="o-sm"><?= owBi($dEn, $dAr) ?></span><?php endif; ?></span></li>
      <?php endforeach; ?>
      <li><span class="ws-mark" aria-hidden="true">✎</span><span><?= owBi('Your teaching experience and a first workshop plan, written in the form below', 'خبرتك في التدريس وخطة ورشتك الأولى، تكتبهما في النموذج أدناه') ?></span></li>
    </ul>
  </section>

  <?php if ($wsReq && $wsReq['status'] === 'pending'): ?>
    <div class="o-t"><p class="o-empty"><?= owBi('Your request is waiting for the Oweili team. We will email you the answer', 'طلبك بانتظار فريق أويلي. سنراسلك بالرد') ?></p></div>
  <?php else: ?>
    <?php if ($wsReq && $wsReq['status'] === 'refused'): ?>
      <div class="o-flash bad" role="status"><?= owBi('Your last request was not approved. Reason: ', 'لم تتم الموافقة على طلبك الأخير. السبب: ') ?><?= e((string) $wsReq['reason']) ?></div>
    <?php endif; ?>
    <?php if (!$wsOk): ?>
      <div class="o-t"><p class="o-empty"><?= owBi('You can send your request once every condition above is met', 'يمكنك إرسال طلبك حين تتحقق كل الشروط أعلاه') ?></p></div>
    <?php else: ?>
    <form class="o-t ws-form" method="post" action="<?= $wsAction ?>" novalidate>
      <?= csrfField() ?><input type="hidden" name="do" value="ws_request">
      <h2 class="c-sec"><?= owBi('Ask to offer workshops', 'اطلب تقديم ورش العمل') ?></h2>
      <div class="field"><label for="ws-sub"><?= owBi('What would you teach?', 'ماذا ستعلّم؟') ?></label>
        <input id="ws-sub" name="ws_subjects" maxlength="500" required value="<?= e((string) ($wsOld['ws_subjects'] ?? '')) ?>" placeholder="<?= e(owIsAr() ? 'مثال: الألوان المائية للمبتدئين، رسم المناظر الصحراوية' : 'For example: watercolour for beginners, desert landscapes') ?>"></div>
      <div class="ws-g2">
        <div class="field"><label for="ws-mode"><?= owBi('In person or online', 'حضوري أو عن بعد') ?></label>
          <select id="ws-mode" name="ws_mode" required><option value=""><?= owIsAr() ? 'اختر' : 'Choose' ?></option>
            <?php foreach (OW_WS_REQ_MODES as $mk => $ml): ?><option value="<?= $mk ?>"<?= ($wsOld['ws_mode'] ?? '') === $mk ? ' selected' : '' ?>><?= e(owIsAr() ? $ml[1] : $ml[0]) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="ws-city"><?= owBi('City (for in person)', 'المدينة (للحضوري)') ?></label>
          <input id="ws-city" name="ws_city" maxlength="80" value="<?= e((string) ($wsOld['ws_city'] ?? ($me['city'] ?? ''))) ?>"></div>
      </div>
      <div class="field"><label for="ws-exp"><?= owBi('Your teaching experience', 'خبرتك في التدريس') ?></label>
        <textarea id="ws-exp" name="ws_experience" maxlength="3000" required placeholder="<?= e(owIsAr() ? 'أين درّست، ولمن، ولكم من الوقت' : 'Where you taught, who, and for how long') ?>"><?= e((string) ($wsOld['ws_experience'] ?? '')) ?></textarea></div>
      <div class="field"><label for="ws-plan"><?= owBi('Your first workshop plan', 'خطة ورشتك الأولى') ?></label>
        <textarea id="ws-plan" name="ws_plan" maxlength="3000" required placeholder="<?= e(owIsAr() ? 'العنوان، المستوى، عدد الساعات، الأدوات، عدد المقاعد، السعر' : 'Title, level, hours, materials, number of seats, price') ?>"><?= e((string) ($wsOld['ws_plan'] ?? '')) ?></textarea></div>
      <p class="o-sm"><?= owBi('The Oweili team reads every request and emails you the answer', 'يقرأ فريق أويلي كل طلب ويراسلك بالرد') ?></p>
      <p><button class="btn btn-primary" type="submit"><?= owBi('Send my request', 'أرسل طلبي') ?></button></p>
    </form>
    <?php endif; ?>
  <?php endif; ?>

<?php elseif ($wsNew || $wsEdit): $isEdit = (bool) $wsEdit; ?>
  <?php if ($isEdit && (int) $wsEdit['booked'] > 0): ?>
    <div class="o-t"><p class="o-empty"><?= owBi('This workshop has bookings, so it cannot be changed here. Please write to ' . owContactEmail(), 'لهذه الورشة حجوزات، لذا لا يمكن تعديلها هنا. يرجى مراسلة ' . owContactEmail()) ?></p></div>
  <?php else: ?>
  <form class="o-t ws-form" method="post" action="<?= $wsAction ?>" novalidate>
    <?= csrfField() ?><input type="hidden" name="do" value="ws_save"><input type="hidden" name="ws_id" value="<?= $isEdit ? (int) $wsEdit['id'] : 0 ?>">
    <h2 class="c-sec"><?= $isEdit ? owBi('Change the workshop', 'عدّل الورشة') : owBi('Add a workshop', 'أضف ورشة') ?></h2>
    <p class="o-sm"><?= owBi('It waits for the Oweili team’s approval before visitors see it' . ($isEdit ? '. Until then it is not shown' : ''), 'تنتظر موافقة فريق أويلي قبل أن يراها الزوار' . ($isEdit ? '. ولا تظهر حتى ذلك الحين' : '')) ?></p>
    <div class="ws-g2">
      <div class="field"><label for="w-ten"><?= owBi('Title in English', 'العنوان بالإنجليزية') ?></label><input id="w-ten" name="title_en" maxlength="200" dir="ltr" value="<?= e($v('title_en')) ?>"></div>
      <div class="field"><label for="w-tar"><?= owBi('Title in Arabic', 'العنوان بالعربية') ?></label><input id="w-tar" name="title_ar" maxlength="200" dir="rtl" value="<?= e($v('title_ar')) ?>"></div>
    </div>
    <p class="o-sm"><?= owBi('One language is enough: the same words are used for both', 'تكفي لغة واحدة: تُستخدم الكلمات نفسها للغتين') ?></p>
    <div class="ws-g2">
      <div class="field"><label for="w-den"><?= owBi('What students will learn (English)', 'ما سيتعلمه المتدربون (بالإنجليزية)') ?></label><textarea id="w-den" name="desc_en" maxlength="3000" dir="ltr"><?= e($v('desc_en')) ?></textarea></div>
      <div class="field"><label for="w-dar"><?= owBi('What students will learn (Arabic)', 'ما سيتعلمه المتدربون (بالعربية)') ?></label><textarea id="w-dar" name="desc_ar" maxlength="3000" dir="rtl"><?= e($v('desc_ar')) ?></textarea></div>
    </div>
    <div class="ws-g2">
      <div class="field"><label for="w-lv"><?= owBi('Level', 'المستوى') ?></label><select id="w-lv" name="level"><option value=""><?= owIsAr() ? 'اختر' : 'Choose' ?></option>
        <?php foreach (OW_WS_LEVELS as $lk => $ll): ?><option value="<?= $lk ?>"<?= $v('level') === $lk ? ' selected' : '' ?>><?= e(owIsAr() ? $ll[1] : $ll[0]) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="w-md"><?= owBi('In person or online', 'حضوري أو عن بعد') ?></label><select id="w-md" name="mode"><option value=""><?= owIsAr() ? 'اختر' : 'Choose' ?></option>
        <?php foreach (OW_WS_MODES as $mk => $ml): ?><option value="<?= $mk ?>"<?= $v('mode') === $mk ? ' selected' : '' ?>><?= e(owIsAr() ? $ml[1] : $ml[0]) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="w-ct"><?= owBi('City (in person)', 'المدينة (للحضوري)') ?></label><input id="w-ct" name="city" maxlength="80" value="<?= e($v('city', (string) ($me['city'] ?? ''))) ?>"></div>
      <div class="field"><label for="w-hr"><?= owBi('Total hours', 'عدد الساعات') ?></label><input id="w-hr" name="hours" inputmode="decimal" dir="ltr" value="<?= e($v('hours') !== '' && (float) $v('hours') > 0 ? rtrim(rtrim($v('hours'), '0'), '.') : '') ?>"></div>
      <div class="field"><label for="w-sd"><?= owBi('Start date', 'تاريخ البدء') ?></label><input id="w-sd" name="start_date" type="date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= e($v('start_date')) ?>"></div>
      <div class="field"><label for="w-ed"><?= owBi('End date', 'تاريخ الانتهاء') ?></label><input id="w-ed" name="end_date" type="date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= e($v('end_date')) ?>"></div>
      <div class="field"><label for="w-sen"><?= owBi('Days and times (English)', 'الأيام والأوقات (بالإنجليزية)') ?></label><input id="w-sen" name="schedule_en" maxlength="200" dir="ltr" value="<?= e($v('schedule_en')) ?>" placeholder="Saturdays, 4 to 7 pm"></div>
      <div class="field"><label for="w-sar"><?= owBi('Days and times (Arabic)', 'الأيام والأوقات (بالعربية)') ?></label><input id="w-sar" name="schedule_ar" maxlength="200" dir="rtl" value="<?= e($v('schedule_ar')) ?>" placeholder="أيام السبت، من 4 إلى 7 مساء"></div>
      <div class="field"><label for="w-pr"><?= owBi('Price per seat (SAR, 0 for free)', 'سعر المقعد (ر.س، 0 للمجاني)') ?></label><input id="w-pr" name="price" inputmode="decimal" dir="ltr" value="<?= e($v('price') !== '' ? rtrim(rtrim($v('price'), '0'), '.') : '') ?>"></div>
      <div class="field"><label for="w-se"><?= owBi('Number of seats', 'عدد المقاعد') ?></label><input id="w-se" name="seats" inputmode="numeric" dir="ltr" value="<?= e($v('seats') !== '0' ? $v('seats') : '') ?>"></div>
    </div>
    <label class="check"><input type="checkbox" name="materials" value="1"<?= $v('materials') === '1' ? ' checked' : '' ?>> <span><?= owBi('Materials are included in the price', 'الأدوات مشمولة في السعر') ?></span></label>
    <p class="o-sm"><?= owBi('Your share per seat is the price minus Oweili’s ' . owCommissionText($wsRate), 'حصتك من كل مقعد هي السعر بعد خصم ' . owCommissionText($wsRate) . ' لأويلي') ?></p>
    <p class="acts" style="display:flex;gap:8px;flex-wrap:wrap"><button class="btn btn-primary" type="submit"><?= owBi('Send for approval', 'أرسل للموافقة') ?></button>
      <a class="btn btn-ghost" href="<?= $wsAction ?>"><?= owBi('Back', 'رجوع') ?></a></p>
  </form>
  <?php endif; ?>

<?php else: ?>
  <p class="a-top-act"><a class="btn btn-primary" href="<?= $wsAction ?>&amp;new=1"><?= owBi('Add a workshop', 'أضف ورشة') ?></a></p>
  <?php if (!$wsList): ?>
    <div class="o-t"><p class="o-empty"><?= owBi('Your request is approved. Add your first workshop', 'تمت الموافقة على طلبك. أضف ورشتك الأولى') ?></p></div>
  <?php else: ?>
  <div class="c-list">
    <?php foreach ($wsList as $w): $st = OW_WS_STATUS[$w['status']] ?? OW_WS_STATUS['pending']; [$sEn, $sAr] = owDateBi((string) $w['start_date']);
      $names = $wsNames[(int) $w['id']] ?? []; $cls = ['approved' => 'on', 'refused' => 'warn', 'hidden' => 'off', 'pending' => ''][$w['status']] ?? ''; ?>
    <div class="o-t c-order">
      <div class="c-orow"><b><?= owBi((string) $w['title_en'], (string) $w['title_ar']) ?></b><span class="c-pill <?= $cls ?>"><?= owBi($st[0], $st[1]) ?></span></div>
      <span class="o-sm"><?= owBi('Starts ' . $sEn, 'تبدأ ' . $sAr) ?> · <?= $w['mode'] === 'online' ? owBi('Online', 'عن بعد') : e((string) $w['city']) ?> · <?= (float) $w['price'] > 0 ? $money($w['price']) : owBi('Free', 'مجانية') ?></span>
      <span class="o-sm"><b><?= (int) $w['booked'] ?></b> <?= owBi('of ' . (int) $w['seats'] . ' seats booked', 'من ' . (int) $w['seats'] . ' مقاعد محجوزة') ?></span>
      <?php if ($names): ?><div class="ws-names" aria-label="<?= owIsAr() ? 'الأسماء الأولى للمحجوزين' : 'First names of the people booked' ?>"><?php foreach ($names as $nm): ?><span><?= e($nm['first']) ?></span><?php endforeach; ?></div>
        <span class="o-sm"><?= owBi('Oweili handles contact with the students', 'أويلي يتولى التواصل مع المتدربين') ?></span><?php endif; ?>
      <?php if ($w['status'] === 'refused'): ?><div class="w-reason"><?= owBi('Why: ', 'السبب: ') ?><?= e((string) $w['reason']) ?></div><?php endif; ?>
      <?php if ((int) $w['booked'] === 0): ?><a class="o-more" href="<?= $wsAction ?>&amp;edit=<?= (int) $w['id'] ?>"><?= owBi('Change it', 'عدّلها') ?></a><?php endif; ?>
      <?php if ($w['status'] === 'approved'): ?><a class="o-more" href="workshop.php?id=<?= (int) $w['id'] ?>"><?= owBi('See its page', 'شاهد صفحتها') ?></a><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
<?php endif; ?>
