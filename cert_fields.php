<?php
/* =====================================================================
 *  cert_fields.php — the artwork details every Certificate of Authenticity
 *  needs: medium, size, year and type (original or limited print).
 *  Shared by upload_artwork.php (last step) and edit_artwork.php.
 *  Expects: $cv (fn(field) → escaped current value) and $etype.
 *  Tables: none (the pages save the values in artworks)
 * ===================================================================== */
if (!isset($cv, $etype)) return;
/* 60.0 → 60, 60.5 stays (whole numbers are never cut) */
$cfNum = fn(string $x) => preg_match('/^\d+\.\d+$/', $x) ? rtrim(rtrim($x, '0'), '.') : $x;
?>
<fieldset class="cert-fields">
  <legend><?= owBi('Details for the Certificate of Authenticity', 'تفاصيل شهادة الأصالة') ?></legend>
  <p class="cert-hint"><?= owBi('Every approved artwork gets a certificate that buyers can check. These details are printed on it.', 'يحصل كل عمل معتمد على شهادة يستطيع المشتري التحقق منها. تُطبع هذه التفاصيل عليها.') ?></p>
  <div class="field">
    <label for="cf-medium"><?= owBi('Medium', 'الخامة') ?></label>
    <input id="cf-medium" name="medium" type="text" maxlength="150" required dir="auto" list="cf-mediums" value="<?= $cv('medium') ?>"
           placeholder="Oil on canvas · زيت على قماش">
    <datalist id="cf-mediums">
      <option value="Oil on canvas"><option value="Acrylic on canvas"><option value="Watercolour on paper"><option value="Ink on paper">
      <option value="Charcoal on paper"><option value="Mixed media"><option value="Digital print on paper">
      <option value="زيت على قماش"><option value="أكريليك على قماش"><option value="ألوان مائية على ورق"><option value="حبر على ورق">
    </datalist>
  </div>
  <div class="cf-row">
    <div class="field"><label for="cf-w"><?= owBi('Width (cm)', 'العرض (سم)') ?></label>
      <input id="cf-w" name="width_cm" type="text" inputmode="decimal" dir="ltr" maxlength="7" required pattern="[0-9٠-٩]+([.,٫][0-9٠-٩])?" value="<?= $cfNum($cv('width_cm')) ?>"></div>
    <div class="field"><label for="cf-h"><?= owBi('Height (cm)', 'الارتفاع (سم)') ?></label>
      <input id="cf-h" name="height_cm" type="text" inputmode="decimal" dir="ltr" maxlength="7" required pattern="[0-9٠-٩]+([.,٫][0-9٠-٩])?" value="<?= $cfNum($cv('height_cm')) ?>"></div>
    <div class="field"><label for="cf-y"><?= owBi('Year made', 'سنة الإنجاز') ?></label>
      <input id="cf-y" name="year_made" type="text" inputmode="numeric" dir="ltr" maxlength="4" required pattern="[0-9٠-٩]{4}" placeholder="<?= date('Y') ?>" value="<?= $cv('year_made') ?>"></div>
  </div>
  <div class="field">
    <span class="cf-label"><?= owBi('Type', 'النوع') ?></span>
    <div class="cf-choice">
      <label><input type="radio" name="edition_type" value="original" required<?= $etype === 'original' ? ' checked' : '' ?>> <?= owBi('Original, one of a kind', 'عمل أصلي فريد') ?></label>
      <label><input type="radio" name="edition_type" value="limited"<?= $etype === 'limited' ? ' checked' : '' ?>> <?= owBi('Limited print', 'نسخة مطبوعة محدودة') ?></label>
    </div>
  </div>
  <div class="cf-row cf-edition"<?= $etype === 'limited' ? '' : ' hidden' ?>>
    <div class="field"><label for="cf-en"><?= owBi('Print number', 'رقم النسخة') ?></label>
      <input id="cf-en" name="edition_number" type="text" inputmode="numeric" dir="ltr" maxlength="6" pattern="[0-9٠-٩]+" placeholder="3" value="<?= $cv('edition_number') ?>"></div>
    <div class="field"><label for="cf-es"><?= owBi('of (prints in total)', 'من (عدد النسخ)') ?></label>
      <input id="cf-es" name="edition_size" type="text" inputmode="numeric" dir="ltr" maxlength="6" pattern="[0-9٠-٩]+" placeholder="50" value="<?= $cv('edition_size') ?>"></div>
  </div>
</fieldset>
<script>
(function(){ var box = document.querySelector('.cf-edition'); if (!box) return;
  document.querySelectorAll('input[name=edition_type]').forEach(function(r){ r.addEventListener('change', function(){
    var lim = document.querySelector('input[name=edition_type][value=limited]').checked;
    box.hidden = !lim; box.querySelectorAll('input').forEach(function(i){ i.required = lim; }); }); });
  var lim0 = document.querySelector('input[name=edition_type][value=limited]').checked; box.querySelectorAll('input').forEach(function(i){ i.required = lim0; });
})();
</script>
