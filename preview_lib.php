<?php
/* =====================================================================
 *  preview_lib.php — "Preview pages" for the owner (owner, 27 Nov 2026).
 *
 *  Shows the collector, artist and institute dashboards EXACTLY as their
 *  users see them, filled with FICTIONAL data, read-only.
 *  How it stays safe:
 *   - for this one request, every table is replaced by an empty private
 *     copy (MariaDB TEMPORARY tables, seen only by this request's own
 *     database connection and gone when the page ends). Real people's data
 *     is never read, and nothing written while the page is drawn reaches
 *     the real tables. Only the site settings are copied in, so the switches
 *     (payments, courses …) look the same as on the live site.
 *   - the fictional person is "signed in" only in memory: the owner's own
 *     session is closed first and never changed.
 *   - OW_PREVIEW is set: no email (owSendMail), no Bashro message, no admin
 *     alert, no invoice, no after-page jobs; preview.php refuses every form
 *     (POST) and the page shows a «معاينة / Preview» banner; forms are
 *     switched off in the browser too.
 *  Tables: every table (its name and shape only), site_settings (copied)
 * ===================================================================== */

const OW_PREVIEW_ROLES = [
    'collector' => ['Collector dashboard', 'لوحة المقتني', 'collector_dashboard.php'],
    'artist'    => ['Artist dashboard (My Studio)', 'لوحة الفنان (مرسمي)', 'artist_studio.php'],
    'institute' => ['Institute dashboard', 'لوحة المعهد', 'institute_dashboard.php'],
];

/* Replace every table with an empty private copy, keep the settings. false = could not be done (then nothing is shown) */
function owPreviewSandbox(): bool {
    $db = getDB();
    try {
        $tables = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
        $settings = in_array('site_settings', $tables, true) ? $db->query('SELECT * FROM site_settings')->fetchAll() : [];   // read BEFORE the copies hide the real table
        $make = [];
        foreach ($tables as $t) {   // the shapes first (columns, defaults, keys), all read before any copy exists
            if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $t)) return false;
            $sql = (string) $db->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM)[1];
            $sql = preg_replace('/\n\s*CONSTRAINT `[^`]+` FOREIGN KEY[^\n]*/', '', $sql);   // links between tables are not needed for a page view
            $sql = preg_replace('/,(\s*\n\))/', '$1', $sql);
            $sql = preg_replace('/\s+AUTO_INCREMENT=\d+/', '', $sql);
            $make[] = preg_replace('/^CREATE TABLE/', 'CREATE TEMPORARY TABLE', $sql, 1);
        }
        foreach ($make as $sql) $db->exec($sql);
        $chk = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ((int) $chk !== 0) return false;   // the copy must start empty; otherwise stop
        foreach ($settings as $s) {
            $cols = array_keys($s);
            $db->prepare('INSERT INTO site_settings (`' . implode('`, `', $cols) . '`) VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')')->execute(array_values($s));
        }
        $GLOBALS['OW_SETTINGS_CACHE'] = [];
        return true;
    } catch (Throwable $e) { error_log('owPreviewSandbox: ' . $e->getMessage()); return false; }
}

/* One statement on the private copy; a column that is not there yet is simply skipped (the page still shows) */
function owPv(string $sql, array $a = []): int {
    try { $st = getDB()->prepare($sql); $st->execute($a); return (int) getDB()->lastInsertId(); }
    catch (Throwable $e) { $GLOBALS['OW_PREVIEW_ERR'][] = $e->getMessage(); return 0; }
}
function owPvImg(string $key): string { return 'https://oweili.com/assets/img/styles/' . $key . '.jpg'; }

/* The fictional people and their things. Returns the user id to "sign in" */
function owPreviewSeed(string $role): int {
    $pw = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);   // nobody can ever sign in with it
    $u = fn(string $email, string $en, string $ar, string $r, string $artist = '') => owPv(
        "INSERT INTO users (email, password, full_name_en, full_name_ar, artist_name, role, is_verified, is_approved, city, country, phone, preferred_lang, created_at)
         VALUES (?, ?, ?, ?, ?, ?, 1, 1, 'Jeddah', 'Saudi Arabia', '+966500000000', ?, NOW() - INTERVAL 140 DAY)",
        [$email, $pw, $en, $ar, $artist, $r, owIsAr() ? 'ar' : 'en']);
    /* two fictional artists and their artworks (public-domain paintings already on Art Styles) */
    $a1 = $u('preview.artist1@example.invalid', 'Lina Haddad', 'لينا حداد', 'artist', 'Lina H.');
    $a2 = $u('preview.artist2@example.invalid', 'Omar Saeed', 'عمر سعيد', 'artist', 'Omar S.');
    $art = [];
    foreach ([[$a1, 'Desert Light', 'ضوء الصحراء', 'abstract', 1800], [$a1, 'Blue Evening', 'مساء أزرق', 'impressionism', 2400], [$a1, 'Old Town Door', 'باب البلدة القديمة', 'realism', 3100],
              [$a2, 'Palm Shade', 'ظل النخيل', 'watercolor', 950], [$a2, 'Morning Market', 'سوق الصباح', 'baroque', 4200], [$a1, 'Red Dunes', 'كثبان حمراء', 'fauvism', 1500]] as $i => [$aid, $en, $ar, $img, $price]) {
        $art[$i] = owPv("INSERT INTO artworks (artist_id, title_en, title_ar, description_en, description_ar, price, type, status, image_url, is_approved, is_draft, view_count, medium, width_cm, height_cm, year_made, edition_type, declared_at, created_at)
                          VALUES (?, ?, ?, 'A fictional artwork for the preview', 'عمل تجريبي للمعاينة', ?, 'oil', 'available', ?, 1, 0, ?, 'Oil on canvas', 50, 70, 2026, 'original', NOW(), NOW() - INTERVAL ? DAY)",
                        [$aid, $en, $ar, $price, owPvImg($img), 180 + $i * 47, 60 - $i * 9]);
    }
    if ($role === 'collector') {
        $me = $u('preview.collector@example.invalid', 'Layla Preview', 'ليلى (معاينة)', 'collector');
        owPv("UPDATE artworks SET status = 'sold', sold_at = NOW() - INTERVAL 12 DAY WHERE id = ?", [$art[0]]);
        owPv('INSERT INTO purchases (collector_id, artwork_id, price, purchased_at) VALUES (?, ?, 1800, NOW() - INTERVAL 12 DAY)', [$me, $art[0]]);
        owPv("INSERT INTO certificates (cert_no, artwork_id, artist_id, snapshot, status, owner_user_id, owner_name, owner_added_at) VALUES ('OW-2026-PREV-0001', ?, ?, '{}', 'valid', ?, 'Layla Preview', NOW())", [$art[0], $a1, $me]);
        foreach ([$art[1], $art[3], $art[4]] as $f) owPv('INSERT INTO favorites (user_id, artwork_id) VALUES (?, ?)', [$me, $f]);
        foreach ([$a1, $a2] as $f) owPv('INSERT INTO artist_follows (collector_id, artist_id) VALUES (?, ?)', [$me, $f]);
        owPv("INSERT INTO artwork_inquiries (artwork_id, artist_id, buyer_name, buyer_phone, buyer_email, buyer_id, status, created_at) VALUES (?, ?, 'Layla Preview', '+966500000000', 'preview.collector@example.invalid', ?, 'contacted', NOW() - INTERVAL 3 DAY)", [$art[2], $a1, $me]);
        $inst = owPreviewInstitute(0);
        $c = owPreviewCourse($inst, 'Watercolour Weekend', 'عطلة الألوان المائية', 650, 14);
        $r = owPv("INSERT INTO course_registrations (course_id, user_id, status, ref_no, student_name, student_email, student_phone, amount, paid_at, registered_at) VALUES (?, ?, 'confirmed', 'B-2026-PV1', 'Layla Preview', 'preview.collector@example.invalid', '+966500000000', 650, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 6 DAY)", [$c, $me]);
        foreach ([['course', $r, 'B-2026-PV1', 'Course: Watercolour Weekend · Preview Art Institute', 'دورة: عطلة الألوان المائية · معهد المعاينة', 650, 1, 5], ['order', 1, 'O-2026-PV1', 'Original artwork: Desert Light', 'عمل فني أصلي: ضوء الصحراء', 1800, 2, 12]] as [$k, $sid, $ref, $ien, $iar, $am, $seq, $d])
            owPv("INSERT INTO invoices (invoice_no, year, seq, source_kind, source_id, ref_no, user_id, customer_name, customer_email, item_en, item_ar, amount, is_tax, issued_at, emailed_at) VALUES (?, YEAR(NOW()), ?, ?, ?, ?, ?, 'Layla Preview', 'preview.collector@example.invalid', ?, ?, ?, 0, NOW() - INTERVAL ? DAY, NOW() - INTERVAL ? DAY)",
                 [sprintf('OW-%d-%05d', (int) date('Y'), $seq), $seq, $k, $sid, $ref, $me, $ien, $iar, $am, $d, $d]);
        owPv("INSERT INTO sent_messages (batch, user_id, kind, subject, body_en, body_ar, status, created_at, sent_at) VALUES ('pv', ?, 'news', 'New artworks this week · أعمال جديدة هذا الأسبوع', 'Fictional message for the preview.', 'رسالة تجريبية للمعاينة.', 'sent', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY)", [$me]);
        return $me;
    }
    if ($role === 'artist') {
        $me = $a1;
        owPv("UPDATE artworks SET status = 'sold', sold_at = NOW() - INTERVAL 20 DAY WHERE id = ?", [$art[2]]);
        owPv("INSERT INTO artworks (artist_id, title_en, title_ar, price, type, status, image_url, is_approved, is_draft, created_at) VALUES (?, 'Sand Dunes', 'كثبان رملية', 0, 'oil', 'available', '', 0, 1, NOW() - INTERVAL 1 DAY)", [$me]);
        owPv("INSERT INTO artworks (artist_id, title_en, title_ar, price, type, status, image_url, is_approved, is_draft, rejected_reason, rejected_at, created_at) VALUES (?, 'Harbour at Dusk', 'الميناء عند الغروب', 1200, 'oil', 'available', ?, 0, 0, 'The 100% photo is blurry. Please take it again in daylight', NOW(), NOW() - INTERVAL 4 DAY)", [$me, owPvImg('romanticism')]);
        for ($d = 29; $d >= 0; $d--) foreach ([$art[0], $art[1], $art[5]] as $k => $aid)
            owPv("INSERT INTO page_stats (day, kind, item, lang, device, views) VALUES (CURDATE() - INTERVAL ? DAY, 'artwork', ?, 'ar', 'phone', ?)", [$d, (string) $aid, max(1, (int) round(3 + 2 * sin(($d + $k) / 4) + (29 - $d) / 7))]);
        $col = $u('preview.collector@example.invalid', 'Layla Preview', 'ليلى (معاينة)', 'collector');
        owPv('INSERT INTO favorites (user_id, artwork_id) VALUES (?, ?)', [$col, $art[0]]);
        owPv('INSERT INTO artist_follows (collector_id, artist_id) VALUES (?, ?)', [$col, $me]);
        owPv("INSERT INTO artwork_inquiries (artwork_id, artist_id, buyer_name, buyer_phone, buyer_email, status, created_at) VALUES (?, ?, 'Fictional collector', '+966500000001', 'preview.c2@example.invalid', 'new', NOW() - INTERVAL 1 DAY)", [$art[1], $me]);
        owPv("INSERT INTO certificates (cert_no, artwork_id, artist_id, snapshot, status) VALUES ('OW-2026-PREV-0002', ?, ?, '{\"title_en\":\"Desert Light\",\"title_ar\":\"ضوء الصحراء\"}', 'valid')", [$art[0], $me]);
        owPv("INSERT INTO payouts (artist_id, amount, currency, status, note, created_at) VALUES (?, 2635, 'SAR', 'paid', 'Fictional payout', NOW() - INTERVAL 10 DAY)", [$me]);
        return $me;
    }
    /* institute */
    $me = $u('preview.institute@example.invalid', 'Huda Preview', 'هدى (معاينة)', 'institute');
    $inst = owPreviewInstitute($me);
    $c1 = owPreviewCourse($inst, 'Watercolour Weekend', 'عطلة الألوان المائية', 650, 14);
    $c2 = owPreviewCourse($inst, 'Wood Carving for Beginners', 'نحت الخشب للمبتدئين', 0, 30);
    foreach ([['Sara A.', 'confirmed', 650, $c1, 'B-2026-PV2'], ['Nour K.', 'pending', 650, $c1, 'B-2026-PV3'], ['Faisal M.', 'confirmed', 0, $c2, 'B-2026-PV4']] as [$n, $s, $am, $cid, $ref])
        owPv("INSERT INTO course_registrations (course_id, status, ref_no, student_name, student_email, student_phone, amount, commission_rate, commission_amount, partner_amount, paid_at, registered_at) VALUES (?, ?, ?, ?, 'student@example.invalid', '+966500000002', ?, 10, ?, ?, ?, NOW() - INTERVAL 3 DAY)",
             [$cid, $s, $ref, $n, $am, round($am * 0.1, 2), round($am * 0.9, 2), $s === 'confirmed' ? date('Y-m-d H:i:s', time() - 2 * 86400) : null]);
    owPv("INSERT INTO partner_payouts (payee_kind, payee_id, source_kind, source_id, amount, commission, due_on, status) VALUES ('institute', ?, 'course', 1, 585, 65, CURDATE() + INTERVAL 14 DAY, 'pending')", [$inst]);
    return $me;
}
function owPreviewInstitute(int $userId): int {
    return owPv("INSERT INTO institutes (user_id, name_en, name_ar, city_en, city_ar, description_en, description_ar, contact_email, contact_phone, is_active, via_signup, legal_name, contact_person, reg_type, reg_number, licence_from, licence_number, review_link, docs_confirmed_at)
                 VALUES (?, 'Preview Art Institute', 'معهد المعاينة للفنون', 'Jeddah', 'جدة', 'A fictional institute for the preview', 'معهد تجريبي للمعاينة', 'preview.institute@example.invalid', '+966500000003', 1, 1, 'Preview Art Institute Est.', 'Huda Preview', 'cr', '1010000000', 'moc', 'MOC-0000', 'https://example.invalid', NOW())",
                [$userId ?: null]);
}
function owPreviewCourse(int $inst, string $en, string $ar, float $price, int $inDays): int {
    return owPv("INSERT INTO courses (institute_id, title_en, title_ar, description_en, description_ar, start_date, end_date, price, currency, seats_total, seats_remaining, is_active, bookable, status, course_type, level, schedule_en, schedule_ar, location_en, location_ar, art_type, audience, attendance, length_kind)
                 VALUES (?, ?, ?, 'A fictional course for the preview', 'دورة تجريبية للمعاينة', CURDATE() + INTERVAL ? DAY, CURDATE() + INTERVAL ? DAY, ?, 'SAR', 12, 9, 1, 1, 'approved', 'in_person', 'beginner', 'Fri and Sat, 4 to 7 pm', 'الجمعة والسبت، 4 إلى 7 مساء', 'Al Rawdah, Jeddah', 'الروضة، جدة', 'painting', 'all', 'mixed', 'workshop')",
                [$inst, $en, $ar, $inDays, $inDays + 1, $price]);
}

/* The banner, the read-only script and the links that stay inside the preview */
function owPreviewFilter(string $html, string $role): string {
    $self = OW_PREVIEW_ROLES[$role][2];
    $html = preg_replace_callback('#(href|action)="' . preg_quote($self, '#') . '(\?[^"]*)?"#', function ($m) use ($role) {
        $q = isset($m[2]) && $m[2] !== '' ? '&amp;' . substr($m[2], 1) : '';
        return $m[1] . '="preview.php?as=' . $role . $q . '"';
    }, $html);
    /* files and pages outside the dashboard would open real things: in the preview they do nothing */
    $html = preg_replace('#href="(invoice|certificate|receipt_photo|id_doc|my_orders|my_bookings|signature|edit_profile|edit_artwork|upload_artwork|my_articles|artist_commission|follow)\.php[^"]*"#', 'href="#" data-preview-off="1"', $html);
    $ar = owIsAr();
    $banner = '<div class="ow-pv-bar" role="status"><b>' . ($ar ? 'معاينة' : 'Preview') . '</b> · ' . ($ar ? 'بيانات تجريبية للعرض فقط. لا يُحفظ شيء ولا يُرسل شيء ولا يُحسب شيء' : 'Fictional data, view only. Nothing is saved, sent or counted')
            . ' · <a href="owner.php?p=preview">' . ($ar ? 'العودة إلى لوحة المالك' : 'Back to the owner dashboard') . '</a></div>'
            . '<style>.ow-pv-bar{position:fixed;left:50%;bottom:16px;transform:translateX(-50%);z-index:2147483000;width:max-content;max-width:calc(100vw - 32px);box-sizing:border-box;background:#23314A;color:#fff;font:600 14px/1.5 var(--font-sans,system-ui);padding:10px 18px;border-radius:14px;box-shadow:0 8px 28px rgba(0,0,0,.28);text-align:center}body{padding-bottom:84px}.ow-pv-bar a{color:#fff;text-decoration:underline;margin-inline-start:6px}.ow-pv-bar b{background:#DB8084;color:#23314A;padding:2px 10px;border-radius:999px;margin-inline-end:6px}</style>'
            . '<script>(function(){var m=' . json_encode($ar ? 'معاينة: لا يُحفظ شيء' : 'Preview: nothing is saved') . ';document.addEventListener("submit",function(e){e.preventDefault();e.stopPropagation();alert(m);},true);document.addEventListener("click",function(e){var a=e.target.closest&&e.target.closest("a[data-preview-off]");if(a){e.preventDefault();alert(m);}},true);})();</script>';
    return preg_replace('#<body([^>]*)>#i', '<body$1>' . $banner, $html, 1);
}
