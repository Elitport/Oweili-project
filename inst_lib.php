<?php
/* =====================================================================
 *  inst_lib.php — art institutes sign themselves up with their documents
 *  (owner, 25 Nov 2026). Shared by register_institute.php,
 *  institute_dashboard.php, institute_doc.php, admin.php, admin_bookings.php,
 *  subadmin_dashboard.php, institutes.php and booking_lib.php.
 *    - the Saudi cities an institute can choose (Saudi Arabia only for now)
 *    - the registration (Commercial Register or Freelance document) and the
 *      training licence (Ministry of Culture "Ebdaa" or TVTC)
 *    - the private documents: kept in .private/institute-docs/ (never in
 *      uploads/), opened only through institute_doc.php by the owner and the
 *      sub-admins the owner allows (owner.php → Owner settings and team)
 *    - the new course details for the filters: audience, attendance, length
 *  Tables: institutes, courses, users, site_settings, activity_log
 * ===================================================================== */
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/article_lib.php';   // owExcerpt, owSafeLogo
require_once __DIR__ . '/gallery_lib.php';    // owSetting

/* Saudi cities (English name saved, as the older institutes use) */
const OW_SA_CITIES = [
    'Riyadh' => 'الرياض', 'Jeddah' => 'جدة', 'Makkah' => 'مكة المكرمة', 'Madinah' => 'المدينة المنورة', 'Dammam' => 'الدمام',
    'Khobar' => 'الخبر', 'Dhahran' => 'الظهران', 'Al Ahsa' => 'الأحساء', 'Jubail' => 'الجبيل', 'Qatif' => 'القطيف',
    'Taif' => 'الطائف', 'Yanbu' => 'ينبع', 'Abha' => 'أبها', 'Khamis Mushait' => 'خميس مشيط', 'Tabuk' => 'تبوك',
    'Buraidah' => 'بريدة', 'Unaizah' => 'عنيزة', 'Hail' => 'حائل', 'Jazan' => 'جازان', 'Najran' => 'نجران',
    'Al Baha' => 'الباحة', 'Sakaka' => 'سكاكا', 'Arar' => 'عرعر', 'AlUla' => 'العلا',
];
const OW_INST_REG_TYPES = ['cr' => ['Commercial Register', 'السجل التجاري'], 'freelance' => ['Freelance document', 'وثيقة العمل الحر']];
const OW_INST_LICENCES = ['moc' => ['Ministry of Culture ("Ebdaa" platform)', 'وزارة الثقافة (منصة «إبداع»)'],
                          'tvtc' => ['Technical and Vocational Training Corporation', 'المؤسسة العامة للتدريب التقني والمهني']];
const OW_INST_DOC_MAX = 10 * 1024 * 1024;   // 10 MB per document
const OW_INST_DOC_DIR = '.private/institute-docs/';

/* The new course details (owner, 25 Nov 2026) */
const OW_COURSE_AUDIENCE   = ['children' => ['Children', 'الأطفال'], 'adults' => ['Adults', 'الكبار'], 'all' => ['Everyone', 'الجميع']];
const OW_COURSE_ATTENDANCE = ['men' => ['Men', 'رجال'], 'women' => ['Women', 'نساء'], 'mixed' => ['Mixed', 'مختلط']];
const OW_COURSE_LENGTH     = ['workshop' => ['Workshop (1 to 2 days)', 'ورشة (يوم أو يومان)'], 'short' => ['Short course', 'دورة قصيرة'], 'long' => ['Long course', 'دورة طويلة']];

function owInstDocsReady(): bool { return owHasColumn('institutes', 'reg_file') && owHasColumn('institutes', 'licence_file'); }
function owCourseExtraReady(): bool { return owHasColumn('courses', 'audience') && owHasColumn('courses', 'attendance') && owHasColumn('courses', 'length_kind'); }

/* A course's length: what the institute chose, or (older courses) worked out from its dates */
function owCourseLengthOf(array $c): string {
    $k = (string) ($c['length_kind'] ?? '');
    if (isset(OW_COURSE_LENGTH[$k])) return $k;
    $a = (string) ($c['start_date'] ?? ''); $b = (string) ($c['end_date'] ?? '') ?: $a;
    if ($a === '') return 'short';
    $days = (int) round((strtotime($b) - strtotime($a)) / 86400) + 1;
    return $days <= 2 ? 'workshop' : ($days <= 42 ? 'short' : 'long');
}
/* The same, in SQL (for the filter) */
function owCourseLengthSql(string $c = 'c'): string {
    return "(CASE WHEN $c.length_kind IN ('workshop','short','long') THEN $c.length_kind
                  WHEN $c.start_date IS NULL THEN 'short'
                  WHEN DATEDIFF(COALESCE($c.end_date, $c.start_date), $c.start_date) + 1 <= 2 THEN 'workshop'
                  WHEN DATEDIFF(COALESCE($c.end_date, $c.start_date), $c.start_date) + 1 <= 42 THEN 'short' ELSE 'long' END)";
}

/* -------------------------------------------------------------------
 *  Private documents
 * ----------------------------------------------------------------- */
/* Keeps one uploaded document (PDF, JPG, PNG or WebP, up to 10 MB). Photos are redrawn, so location and
   camera data are removed. Returns ['ok' => true, 'name' => file name] or ['ok' => false, 'err' => [en, ar]] */
function owInstDocStore(array $file): array {
    $E = fn(string $en, string $ar) => ['ok' => false, 'err' => [$en, $ar]];
    if (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return ['ok' => false, 'err' => null];
    if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || (int) $file['size'] > OW_INST_DOC_MAX) return $E('Each document can be up to 10 MB', 'حجم كل مستند حتى 10 ميغابايت');
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) || (int) $file['size'] <= 0) return $E('The document could not be saved. Please try again.', 'تعذّر حفظ المستند. حاول مرة أخرى.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $dir = __DIR__ . '/' . OW_INST_DOC_DIR;
    if (!is_dir($dir) && !@mkdir($dir, 0700, true)) return $E('The document could not be saved. Please try again.', 'تعذّر حفظ المستند. حاول مرة أخرى.');
    if (!is_file($dir . '.htaccess')) @file_put_contents($dir . '.htaccess', "Require all denied\nDeny from all\n");
    $name = bin2hex(random_bytes(16));
    if ($mime === 'application/pdf') {
        $head = (string) file_get_contents($file['tmp_name'], false, null, 0, 5);
        if ($head !== '%PDF-') return $E('Please upload a PDF or a photo (JPG, PNG or WebP)', 'يرجى رفع ملف PDF أو صورة (JPG أو PNG أو WebP)');
        $name .= '.pdf';
        if (!@move_uploaded_file($file['tmp_name'], $dir . $name)) return $E('The document could not be saved. Please try again.', 'تعذّر حفظ المستند. حاول مرة أخرى.');
    } elseif (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        $info = @getimagesize($file['tmp_name']);
        $img = ($info && function_exists('imagecreatefromstring')) ? @imagecreatefromstring((string) file_get_contents($file['tmp_name'])) : false;
        if (!$img) return $E('Please upload a PDF or a photo (JPG, PNG or WebP)', 'يرجى رفع ملف PDF أو صورة (JPG أو PNG أو WebP)');
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {   // turn it upright before the data is dropped
            $o = (int) ((@exif_read_data($file['tmp_name']))['Orientation'] ?? 1);
            if ($o === 3) $img = imagerotate($img, 180, 0); elseif ($o === 6) $img = imagerotate($img, -90, 0); elseif ($o === 8) $img = imagerotate($img, 90, 0);
        }
        $name .= '.jpg';
        $w = imagesx($img); $h = imagesy($img); $out = imagecreatetruecolor($w, $h);
        imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255)); imagecopy($out, $img, 0, 0, 0, 0, $w, $h);
        $ok = imagejpeg($out, $dir . $name, 88); imagedestroy($img); imagedestroy($out);
        if (!$ok) return $E('The document could not be saved. Please try again.', 'تعذّر حفظ المستند. حاول مرة أخرى.');
    } else {
        return $E('Please upload a PDF or a photo (JPG, PNG or WebP)', 'يرجى رفع ملف PDF أو صورة (JPG أو PNG أو WebP)');
    }
    @chmod($dir . $name, 0600);
    return ['ok' => true, 'name' => $name];
}
function owInstDocPath(string $name): string {
    return preg_match('/^[a-f0-9]{32}\.(pdf|jpg)$/', $name) ? __DIR__ . '/' . OW_INST_DOC_DIR . $name : '';
}
function owInstDocDelete(string $name): void { $p = owInstDocPath($name); if ($p !== '' && is_file($p)) @unlink($p); }
function owInstDocUrl(int $instId, string $kind): string { return 'institute_doc.php?i=' . $instId . '&d=' . $kind; }

/* Who may open the documents: the owner (the admin), and the sub-admins the owner ticked */
function owInstDocsSubadmins(): array {
    $j = json_decode((string) owSetting('subadmin_inst_docs', '[]'), true);
    return is_array($j) ? array_values(array_map('intval', $j)) : [];
}
function owInstDocsAllowed(): bool {
    require_once __DIR__ . '/admin_shell.php';
    if (owIsAdmin()) return true;
    if (function_exists('isLoggedIn') && isLoggedIn() && ($u = currentUser()) && ($u['role'] ?? '') === 'sub_admin' && empty($u['is_banned']))
        return in_array((int) $u['id'], owInstDocsSubadmins(), true);
    return false;
}

/* Institutes that joined and wait for the owner (not approved, not refused) */
function owInstWaiting(): array {
    if (!owHasColumn('institutes', 'via_signup')) return [];
    try {
        $rej = owHasColumn('institutes', 'reject_reason') ? " AND i.reject_reason = ''" : '';
        return getDB()->query("SELECT i.*, u.email, u.is_verified, u.full_name_en AS person, u.created_at AS joined FROM institutes i JOIN users u ON u.id = i.user_id
                               WHERE i.via_signup = 1 AND u.role = 'institute' AND u.is_approved = 0$rej ORDER BY i.id")->fetchAll();
    } catch (Throwable $e) { return []; }
}

/* The documents block the owner sees (Waiting for approval, Courses and tickets, sub-admin panel) */
function owInstDocsHtml(array $i): string {
    static $css = false;
    $o = $css ? '' : '<style>.inst-docs{display:grid;grid-template-columns:max-content 1fr;gap:4px 12px;margin:8px 0 0;font-size:14px;line-height:1.6}.inst-docs dt{font-weight:700;color:var(--color-dark)}.inst-docs dd{margin:0;overflow-wrap:anywhere}.inst-docs a{font-weight:700}@media(max-width:700px){.inst-docs{grid-template-columns:1fr}.inst-docs dd{margin-bottom:6px}}</style>';
    $css = true;
    $o .= '<dl class="inst-docs">';
    $rt = OW_INST_REG_TYPES[$i['reg_type'] ?? ''] ?? null;
    $o .= '<dt>' . owBi('Registration', 'التسجيل') . '</dt><dd>' . ($rt ? owBi($rt[0], $rt[1]) . ' · <bdi dir="ltr">' . e((string) $i['reg_number']) . '</bdi>' : owBi('Not given', 'لم يُذكر'))
        . (($i['reg_file'] ?? '') !== '' ? ' · <a href="' . e(owInstDocUrl((int) $i['id'], 'reg')) . '" target="_blank" rel="noopener">' . owBi('Open the document', 'افتح المستند') . '</a>' : '') . '</dd>';
    $lc = OW_INST_LICENCES[$i['licence_from'] ?? ''] ?? null;
    $o .= '<dt>' . owBi('Training licence', 'ترخيص التدريب') . '</dt><dd>' . ($lc ? owBi($lc[0], $lc[1]) . ' · <bdi dir="ltr">' . e((string) $i['licence_number']) . '</bdi>'
        . (($i['licence_file'] ?? '') !== '' ? ' · <a href="' . e(owInstDocUrl((int) $i['id'], 'licence')) . '" target="_blank" rel="noopener">' . owBi('Open the document', 'افتح المستند') . '</a>' : '')
        : '<b>' . owBi('Not added yet: needed before their courses go live', 'لم يُضف بعد: مطلوب قبل ظهور دوراتهم') . '</b>') . '</dd>';
    if (($i['review_link'] ?? '') !== '') $o .= '<dt>' . owBi('Website or social link (only for you)', 'الموقع أو رابط التواصل (لك فقط)') . '</dt><dd><a href="' . e((string) $i['review_link']) . '" target="_blank" rel="noopener nofollow" dir="ltr">' . e((string) $i['review_link']) . '</a></dd>';
    if (!empty($i['docs_confirmed_at'])) { [$en, $ar] = owDateBi((string) $i['docs_confirmed_at'], true); $o .= '<dt>' . owBi('Confirmed the documents are valid and theirs', 'أكد أن المستندات سارية وتخصه') . '</dt><dd>' . owBi($en, $ar) . '</dd>'; }
    return $o . '</dl>';
}
