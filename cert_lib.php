<?php
/* =====================================================================
 *  cert_lib.php — the Certificate of Authenticity.
 *    - artwork details every certificate needs (medium, size, year, type)
 *      and the artist's declaration: owCertDetailsFromPost / owCertMissing
 *    - owIssueCertificate: made automatically when an artwork is approved
 *    - owRefreshCertificate: when approved details change later
 *    - owCancelCertificate: admin only, with a reason (never deleted)
 *    - owSetCertificateOwner: ready for later (buyer's name after a sale)
 *    - lookups for the verify page, the artist's studio and the admin list
 *  Certificate numbers look like OW-2026-7KQ4-M9XD (no 0/O or 1/I, so they
 *  are easy to type) and are permanent.
 *  Until certificates_update.sql has run, owCertReady() is false and
 *  nothing here does anything.
 *  Tables: certificates, artworks, artwork_stages, users, activity_log
 * ===================================================================== */
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/platform_lib.php';

const OW_CERT_DECLARATION = ['I confirm this artwork is my original work, made by my own hand.',
                             'أؤكد أن هذا العمل من إبداعي الأصلي، وقد صنعته بيدي.'];
const OW_CERT_RIGHTS = ['The collector owns the physical artwork. The copyright stays with the artist.',
                        'يملك المقتني العمل الفني نفسه، وتبقى حقوق النشر والملكية الفكرية للفنان.'];
const OW_CERT_FIELDS = ['medium', 'width_cm', 'height_cm', 'year_made', 'edition_type', 'edition_number', 'edition_size'];

function owCertReady(): bool { return owHasTable('certificates') && owHasColumn('artworks', 'declared_at'); }

/* The public address of the verify page (also inside the QR code) */
function owCertVerifyUrl(string $certNo = ''): string {
    $host = preg_replace('/[^a-z0-9.:\-]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'oweili.com')) ?: 'oweili.com';
    return 'https://' . $host . owBase() . 'verify' . ($certNo !== '' ? '?no=' . rawurlencode($certNo) : '');
}

/* Tidy what someone typed into a certificate number: OW-2026-7KQ4-M9XD */
function owCertNormalize(string $raw): string {
    $s = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $raw));
    if (!preg_match('/^OW(\d{4})([A-Z0-9]{8})$/', $s, $m)) return '';
    return 'OW-' . $m[1] . '-' . substr($m[2], 0, 4) . '-' . substr($m[2], 4, 4);
}

function owCertNewNumber(): string {
    $abc = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';   // 32 letters, no 0/O/1/I
    for ($try = 0; $try < 20; $try++) {
        $r = '';
        for ($i = 0; $i < 8; $i++) $r .= $abc[random_int(0, 31)];
        $no = 'OW-' . date('Y') . '-' . substr($r, 0, 4) . '-' . substr($r, 4);
        $st = getDB()->prepare('SELECT 1 FROM certificates WHERE cert_no = :n');
        $st->execute([':n' => $no]);
        if (!$st->fetchColumn()) return $no;
    }
    throw new RuntimeException('could not make a certificate number');
}

/* ---------------------------------------------------------------------
 *  Artwork details (form ↔ database)
 * ------------------------------------------------------------------- */

/* Reads and checks the details from a form. Returns [values, error code or ''] */
function owCertDetailsFromPost(array $p): array {
    $medium = mb_substr(trim(preg_replace('/[\x00-\x1F\x7F<>]/u', ' ', (string) ($p['medium'] ?? ''))), 0, 150);
    /* Arabic and Persian digits (٦٠ / ۶۰) are read as 60; the Arabic decimal mark ٫ as a point */
    $digits = ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
               '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٫' => '.', ',' => '.', ' ' => ''];
    $num = fn($k) => trim(strtr((string) ($p[$k] ?? ''), $digits));
    $w = $num('width_cm'); $h = $num('height_cm'); $y = $num('year_made');
    $type = in_array($p['edition_type'] ?? '', ['original', 'limited'], true) ? $p['edition_type'] : '';
    $en = $num('edition_number'); $es = $num('edition_size');
    $v = ['medium' => $medium, 'width_cm' => $w, 'height_cm' => $h, 'year_made' => $y, 'edition_type' => $type,
          'edition_number' => $type === 'limited' ? $en : '', 'edition_size' => $type === 'limited' ? $es : ''];
    $isSize = fn($x) => preg_match('/^\d{1,5}(\.\d)?$/', $x) && (float) $x >= 0.5 && (float) $x <= 99999;
    /* the standard sizes (size_lib.php, 5 Nov 2026): the form sends a size from the list and
       its orientation; width and height come from it */
    $sz = null; $szErr = '';
    if (array_key_exists('size_code', $p)) {
        require_once __DIR__ . '/size_lib.php';
        [$sz, $szErr] = owSizeFromPost($p, true);
        $v['size_code'] = (string) ($p['size_code'] ?? ''); $v['orientation'] = (string) ($p['orientation'] ?? '');
        if ($sz) { $w = (string) $sz['width_cm']; $h = (string) $sz['height_cm']; $v['width_cm'] = $w; $v['height_cm'] = $h; }
    }
    if ($medium === '' || mb_strlen($medium) < 2) return [$v, 'medium'];
    if ($szErr !== '') return [$v, $szErr];
    if (!$isSize($w) || !$isSize($h)) return [$v, 'dims'];
    if (!preg_match('/^\d{4}$/', $y) || (int) $y < 1900 || (int) $y > (int) date('Y')) return [$v, 'year'];
    if ($type === '') return [$v, 'edition'];
    if ($type === 'limited' && (!ctype_digit($en) || !ctype_digit($es) || (int) $en < 1 || (int) $es < 2 || (int) $en > (int) $es || (int) $es > 100000)) return [$v, 'edition_no'];
    $v['width_cm'] = (float) $w; $v['height_cm'] = (float) $h; $v['year_made'] = (int) $y;
    $v['edition_number'] = $type === 'limited' ? (int) $en : null; $v['edition_size'] = $type === 'limited' ? (int) $es : null;
    unset($v['size_code'], $v['orientation']);
    if ($sz && function_exists('owHasColumn') && owHasColumn('artworks', 'size_code')) {
        $v['size_code'] = $sz['size_code']; $v['size_category'] = $sz['size_category']; $v['orientation'] = $sz['orientation'];
    }
    return [$v, ''];
}

/* The messages for those error codes */
function owCertDetailErrors(): array {
    return [
        'medium'     => ['Please write the medium, for example "Oil on canvas".', 'يرجى كتابة الخامة، مثل «زيت على قماش».'],
        'dims'       => ['Please write the width and height in centimetres (numbers only).', 'يرجى كتابة العرض والارتفاع بالسنتيمتر (أرقام فقط).'],
        'size'       => ['Please choose a size from the list.', 'يرجى اختيار مقاس من القائمة.'],
        'orientation'=> ['Please choose portrait or landscape for this size.', 'يرجى اختيار طولي أو عرضي لهذا المقاس.'],
        'year'       => ['Please write the year the artwork was made (for example ' . date('Y') . ').', 'يرجى كتابة سنة إنجاز العمل (مثل ' . date('Y') . ').'],
        'edition'    => ['Please choose: original, one of a kind, or limited print.', 'يرجى الاختيار: عمل أصلي فريد، أو نسخة مطبوعة محدودة.'],
        'edition_no' => ['For a limited print, write its number and the total, for example 3 of 50.', 'للنسخة المحدودة، اكتب رقمها والعدد الكلي، مثل 3 من 50.'],
        'declare'    => ['Please tick the box to confirm this is your original work, made by your own hand.', 'يرجى وضع علامة في المربع لتأكيد أن هذا العمل من إبداعك الأصلي وصنع يدك.'],
    ];
}

/* What a certificate still needs from this artwork ([] = nothing) */
function owCertMissing(array $aw): array {
    if (!owCertReady()) return [];
    $miss = [];
    if (trim((string) ($aw['medium'] ?? '')) === '') $miss[] = 'medium';
    if ((float) ($aw['width_cm'] ?? 0) <= 0 || (float) ($aw['height_cm'] ?? 0) <= 0) $miss[] = 'dims';
    if ((int) ($aw['year_made'] ?? 0) < 1900) $miss[] = 'year';
    if (!in_array($aw['edition_type'] ?? '', ['original', 'limited'], true)) $miss[] = 'edition';
    elseif ($aw['edition_type'] === 'limited' && ((int) ($aw['edition_number'] ?? 0) < 1 || (int) ($aw['edition_size'] ?? 0) < 1)) $miss[] = 'edition_no';
    if (empty($aw['declared_at'])) $miss[] = 'declare';
    return $miss;
}

/* Words for the admin list of what is missing */
function owCertMissingWords(array $miss): array {
    $w = ['medium' => ['medium', 'الخامة'], 'dims' => ['size', 'المقاس'], 'year' => ['year', 'السنة'], 'edition' => ['type', 'النوع'],
          'edition_no' => ['print number', 'رقم النسخة'], 'declare' => ["artist's declaration", 'إقرار الفنان']];
    return [implode(', ', array_map(fn($k) => $w[$k][0], $miss)), implode('، ', array_map(fn($k) => $w[$k][1], $miss))];
}

/* "60 × 80 cm" */
/* Saves the standard size of an artwork (only when the database has the columns) */
function owSaveArtworkSize(int $artworkId, array $dv, int $artistId): void {
    if (!isset($dv['size_code']) || !owHasColumn('artworks', 'size_code')) return;
    getDB()->prepare('UPDATE artworks SET size_code = :c, size_category = :g, orientation = :o WHERE id = :id AND artist_id = :uid')
           ->execute([':c' => $dv['size_code'], ':g' => $dv['size_category'], ':o' => $dv['orientation'], ':id' => $artworkId, ':uid' => $artistId]);
}

function owCertSize($w, $h): array {
    $f = fn($x) => rtrim(rtrim(number_format((float) $x, 1, '.', ''), '0'), '.');
    return [$f($w) . ' × ' . $f($h) . ' cm', $f($w) . ' × ' . $f($h) . ' سم'];
}

/* The size line shown to visitors (artwork page, Gallery window): "50 × 70 cm · Medium · Portrait",
   [English, Arabic], or null when the artwork has no size yet */
function owArtworkSizeWords(array $aw): ?array {
    if ((float) ($aw['width_cm'] ?? 0) <= 0) return null;
    [$sE, $sA] = owCertSize($aw['width_cm'], $aw['height_cm'] ?? 0);
    if (!empty($aw['size_code'])) {
        require_once __DIR__ . '/size_lib.php';
        $sc = owSizeCheck((string) $aw['size_code'], (string) ($aw['orientation'] ?? ''));
        if ($sc) { $sE .= ' · ' . owSizeGroups()[$sc[1]][0] . ' · ' . preg_replace('/ \(.*\)$/', '', owSizeOrientations()[$sc[2]][0]); $sA .= ' · ' . owSizeGroups()[$sc[1]][1] . ' · ' . owSizeOrientations()[$sc[2]][1]; }
    }
    return [$sE, $sA];
}

/* Under the price (artwork page, Gallery window): what happens after "I'm Interested" */
const OW_BUY_NEXT = ['We’ll contact you within 24 hours to arrange payment and delivery.', 'سنتواصل معك خلال 24 ساعة لترتيب الدفع والتوصيل.'];

/* The medium suggestions of the upload form, [English, Arabic] (cert_fields.php) */
const OW_MEDIUMS = [['Oil on canvas', 'زيت على قماش'], ['Acrylic on canvas', 'أكريليك على قماش'], ['Watercolor on paper', 'ألوان مائية على ورق'],
    ['Ink on paper', 'حبر على ورق'], ['Charcoal on paper', 'فحم على ورق'], ['Mixed media', 'وسائط مختلطة'], ['Digital print on paper', 'طباعة رقمية على ورق']];

/* A medium the artist picked from the suggestions is shown in the page language
   ("Watercolour on paper" counts as "Watercolor on paper"); anything else is shown as typed */
function owMediumWords(string $m): array {
    $k = mb_strtolower(trim(str_ireplace('colour', 'color', $m)));
    foreach (OW_MEDIUMS as $pair) if ($k === mb_strtolower($pair[0]) || $k === $pair[1]) return $pair;
    return [$m, $m];
}

/* "Original, one of a kind" / "Limited print, 3 of 50" */
function owCertEdition(?string $type, $no = null, $of = null): array {
    if ($type === 'limited') return ['Limited print, ' . (int) $no . ' of ' . (int) $of, 'نسخة مطبوعة محدودة، رقم ' . (int) $no . ' من ' . (int) $of];
    return ['Original, one of a kind', 'عمل أصلي فريد'];
}

/* ---------------------------------------------------------------------
 *  Issue / refresh / cancel
 * ------------------------------------------------------------------- */

/* Everything the certificate shows, taken from the artwork right now */
function owCertSnapshot(int $artworkId): ?array {
    $st = getDB()->prepare('SELECT a.*, ' . owArtistNameSql('u') . ' FROM artworks a JOIN users u ON u.id = a.artist_id WHERE a.id = :id');
    $st->execute([':id' => $artworkId]);
    $a = $st->fetch();
    if (!$a) return null;
    $stages = [];
    foreach (owGetStages($artworkId) as $pct => $s) $stages[(string) $pct] = ['photo' => (string) $s['photo_path'], 'taken_at' => (string) ($s['taken_at'] ?? '')];
    $types = require __DIR__ . '/art_types.php';
    $t = $types[(string) $a['type']] ?? null;
    return [
        'title_en' => (string) $a['title_en'], 'title_ar' => (string) ($a['title_ar'] ?: $a['title_en']),
        'artist_en' => (string) $a['artist_en'], 'artist_ar' => (string) $a['artist_ar'],
        'type' => (string) $a['type'], 'type_en' => $t['en'] ?? (string) $a['type'], 'type_ar' => $t['ar'] ?? (string) $a['type'],
        'medium' => (string) $a['medium'], 'width_cm' => (float) $a['width_cm'], 'height_cm' => (float) $a['height_cm'],
        'year_made' => (int) $a['year_made'], 'edition_type' => (string) $a['edition_type'],
        'edition_number' => $a['edition_number'] !== null ? (int) $a['edition_number'] : null,
        'edition_size' => $a['edition_size'] !== null ? (int) $a['edition_size'] : null,
        'image' => (string) $a['image_url'], 'stages' => $stages,
        'real_touch' => isset($stages['25'], $stages['100']),
        'declared_at' => (string) $a['declared_at'],
    ];
}

/* The certificate that is in force for an artwork, or null */
function owCertForArtwork(int $artworkId): ?array {
    if (!owCertReady()) return null;
    $st = getDB()->prepare("SELECT * FROM certificates WHERE artwork_id = :id AND status = 'valid' ORDER BY id DESC LIMIT 1");
    $st->execute([':id' => $artworkId]);
    return $st->fetch() ?: null;
}

/* Made when an artwork is approved. Returns the certificate number, or '' when
   the artwork still misses a detail or the declaration (the admin list says what). */
function owIssueCertificate(int $artworkId, ?int $staffId = null): string {
    if (!owCertReady()) return '';
    if ($c = owCertForArtwork($artworkId)) return (string) $c['cert_no'];
    $st = getDB()->prepare('SELECT * FROM artworks WHERE id = :id AND is_approved = 1' . owNotDraftSql());
    $st->execute([':id' => $artworkId]);
    $aw = $st->fetch();
    if (!$aw || owCertMissing($aw)) return '';
    $snap = owCertSnapshot($artworkId);
    if (!$snap) return '';
    $no = owCertNewNumber();
    getDB()->prepare('INSERT INTO certificates (cert_no, artwork_id, artist_id, snapshot, issued_by) VALUES (:n, :a, :u, :s, :by)')
           ->execute([':n' => $no, ':a' => $artworkId, ':u' => (int) $aw['artist_id'], ':s' => json_encode($snap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ':by' => $staffId]);
    owLog('cert_issue', 'artwork', $artworkId, $no, ['type' => 'admin', 'id' => $staffId]);
    return $no;
}

/* Approved details changed later: the certificate keeps its number and shows the new details */
function owRefreshCertificate(int $artworkId): void {
    if (!($c = owCertForArtwork($artworkId))) { owIssueCertificate($artworkId); return; }
    if ($snap = owCertSnapshot($artworkId)) {
        getDB()->prepare('UPDATE certificates SET snapshot = :s WHERE id = :id')
               ->execute([':s' => json_encode($snap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ':id' => (int) $c['id']]);
        owLog('cert_update', 'artwork', $artworkId, (string) $c['cert_no'], ['type' => 'admin', 'id' => null]);
    }
}

/* Cancel (never delete). The verify page then shows "Cancelled". */
function owCancelCertificate(int $certId, string $reason, ?int $staffId = null): bool {
    $reason = mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]/u', ' ', $reason)), 0, 500);
    if ($reason === '' || !owCertReady()) return false;
    $st = getDB()->prepare("UPDATE certificates SET status = 'cancelled', cancelled_at = NOW(), cancel_reason = :r, cancelled_by = :by WHERE id = :id AND status = 'valid'");
    $st->execute([':r' => $reason, ':by' => $staffId, ':id' => $certId]);
    if ($st->rowCount() < 1) return false;
    $q = getDB()->prepare('SELECT artwork_id, cert_no FROM certificates WHERE id = :id'); $q->execute([':id' => $certId]); $c = $q->fetch();
    owLog('cert_cancel', 'artwork', (int) $c['artwork_id'], $c['cert_no'] . ': ' . $reason, ['type' => 'admin', 'id' => $staffId]);
    return true;
}

/* An artwork was deleted: its certificate is cancelled, so the number shows "Cancelled" */
function owCancelCertificatesOfArtwork(int $artworkId, string $reason): void {
    if (!owCertReady()) return;
    getDB()->prepare("UPDATE certificates SET status = 'cancelled', cancelled_at = NOW(), cancel_reason = :r WHERE artwork_id = :a AND status = 'valid'")
           ->execute([':r' => $reason, ':a' => $artworkId]);
}

/* Ready for later (Sprint 4): the buyer's name on the certificate after a sale.
   Never shown on the public verify page. */
function owSetCertificateOwner(int $certId, string $name, ?int $ownerUserId = null): bool {
    $name = mb_substr(trim(preg_replace('/[\x00-\x1F\x7F<>]/u', ' ', $name)), 0, 200);
    if ($name === '' || !owCertReady()) return false;
    $q = getDB()->prepare("SELECT artwork_id, cert_no, owner_name FROM certificates WHERE id = :id AND status = 'valid'");
    $q->execute([':id' => $certId]); $c = $q->fetch();
    if (!$c) return false;
    if (trim((string) $c['owner_name']) === $name) return true;                   // same owner again: nothing new
    $st = getDB()->prepare("UPDATE certificates SET owner_name = :n, owner_user_id = :u, owner_added_at = NOW() WHERE id = :id AND status = 'valid'");
    $st->execute([':n' => $name, ':u' => $ownerUserId, ':id' => $certId]);
    /* one record per owner (only the certificate number, never the name), so the verify page can count owners */
    owLog('cert_owner', 'artwork', (int) $c['artwork_id'], (string) $c['cert_no'], ['type' => 'admin', 'id' => null]);
    return $st->rowCount() > 0;
}

/* How many owners a certificate has had (0 = not sold yet). Names are never counted or shown in public. */
function owCertOwnerCount(array $cert): int {
    if (trim((string) ($cert['owner_name'] ?? '')) === '') return 0;
    if (!owHasTable('activity_log')) return 1;
    $st = getDB()->prepare("SELECT COUNT(*) FROM activity_log WHERE action = 'cert_owner' AND target_id = :a AND details = :n");
    $st->execute([':a' => (int) $cert['artwork_id'], ':n' => (string) $cert['cert_no']]);
    return max(1, (int) $st->fetchColumn());
}

/* ---------------------------------------------------------------------
 *  Lookups
 * ------------------------------------------------------------------- */
function owCertDecode(?array $c): ?array {
    if (!$c) return null;
    $c['snap'] = json_decode((string) $c['snapshot'], true) ?: [];
    return $c;
}

function owCertByNo(string $no): ?array {
    if (!owCertReady() || ($no = owCertNormalize($no)) === '') return null;
    $st = getDB()->prepare('SELECT * FROM certificates WHERE cert_no = :n LIMIT 1');
    $st->execute([':n' => $no]);
    return owCertDecode($st->fetch() ?: null);
}

function owCertsForArtist(int $artistId): array {
    if (!owCertReady()) return [];
    $st = getDB()->prepare('SELECT * FROM certificates WHERE artist_id = :u ORDER BY id DESC');
    $st->execute([':u' => $artistId]);
    return array_map('owCertDecode', $st->fetchAll());
}

function owAllCerts(): array {
    if (!owCertReady()) return [];
    return array_map('owCertDecode', getDB()->query('SELECT * FROM certificates ORDER BY id DESC LIMIT 2000')->fetchAll());
}

/* "✦ Certified authentic" badge with its verify link (artwork page) */
function owCertBadgeHtml(array $cert): string {
    return '<a class="cert-badge" href="' . e(owCertVerifyUrl((string) $cert['cert_no'])) . '">'
         . '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l2.4 2.2 3.2-.4.9 3.1 2.8 1.6-1.2 3 1.2 3-2.8 1.6-.9 3.1-3.2-.4L12 22l-2.4-2.2-3.2.4-.9-3.1L2.7 15.5l1.2-3-1.2-3 2.8-1.6.9-3.1 3.2.4z"/><path d="M8.5 12.2l2.3 2.3 4.7-4.8"/></svg>'
         . owBi('Certified authentic', 'موثّق الأصالة') . ' <span class="cert-no" dir="ltr">' . e((string) $cert['cert_no']) . '</span> · '
         . owBi('Verify', 'تحقّق') . '</a>';
}

/* Staff: the admin signed in on admin.php, or an admin / sub-admin account */
function owCertIsStaff(): bool {
    if (isLoggedIn() && ($u = currentUser()) && in_array($u['role'] ?? '', ['admin', 'sub_admin'], true) && empty($u['is_banned'])) return true;
    $hash = defined('ADMIN_HASH') ? (string) ADMIN_HASH : '';
    return $hash !== '' && !empty($_SESSION['is_admin']) && hash_equals(hash('sha256', $hash), (string) ($_SESSION['admin_sig'] ?? ''));
}

/* Who may open the full certificate (certificate.php): its artist, the team, and later its owner */
function owCertCanView(array $c): bool {
    if (owCertIsStaff()) return true;
    if (!isLoggedIn() || !($u = currentUser())) return false;
    return (int) $u['id'] === (int) $c['artist_id'] || ($c['owner_user_id'] !== null && (int) $u['id'] === (int) $c['owner_user_id']);
}
