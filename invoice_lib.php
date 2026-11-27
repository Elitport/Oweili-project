<?php
/* =====================================================================
 *  invoice_lib.php — an invoice for every purchase (owner, 27 Nov 2026)
 *
 *  Every purchase gets one invoice, made the moment it is paid (or, for a
 *  free course or workshop, the moment the seat is confirmed):
 *    order     artwork, gift or private commission order (orders, once paid)
 *    course    course booking or free course registration (course_registrations, confirmed)
 *    workshop  artist workshop booking (workshop_bookings, paid)
 *    ticket    museum ticket (museum_tickets, paid)
 *  Numbers run in order each year: OW-2026-00001, OW-2026-00002, … with no
 *  gap (taken from invoice_counters inside the same transaction as the row).
 *  The seller, the customer, the item and the money are frozen in the row
 *  when it is made, so the PDF never changes later.
 *
 *  Receipt or tax invoice (owner): while VAT is off or the VAT number in
 *  Owner settings is empty → "Purchase receipt / إيصال شراء" with the line
 *  "Not a tax invoice / ليست فاتورة ضريبية" and no VAT. Once the owner turns
 *  VAT on with a VAT number → "Tax invoice / فاتورة ضريبية" with VAT (15% by
 *  default) INCLUDED in the price and the VAT number. Oweili's commission is
 *  never shown to the customer.
 *  Full ZATCA e-invoicing (Fatoora: QR code, cryptographic stamp, reporting
 *  to ZATCA) is NOT built yet and will be needed later (see CLAUDE.md).
 *
 *  Who can open an invoice (invoice.php): the owner (admin), and the customer
 *  whose account it belongs to (or whose CONFIRMED email it was sent to).
 *  Guests get it by email only. Nobody else; never cached.
 *  PDF: TCPDF 6.11.4 (lib/TCPDF, LGPL-3.0), font DejaVu Sans (Arabic + English).
 *  Tables: invoices, invoice_counters (invoices_update.sql), orders,
 *          course_registrations, courses, institutes, workshop_bookings,
 *          artist_workshops, museum_tickets, art_places, users
 * ===================================================================== */
require_once __DIR__ . '/platform_lib.php';

const OW_INV_SELLER = ['Elitport Company Ltd.', 'شركة إيليتبورت المحدودة'];
const OW_INV_KINDS  = ['order', 'course', 'workshop', 'ticket'];

function owInvReady(): bool { return owHasTable('invoices') && owHasTable('invoice_counters'); }

/* Receipt or tax invoice, right now (owner_lib.php owVat: Owner settings → VAT) */
/* Tax invoice or receipt for a sale made on $date (vat_lib.php): VAT on, a VAT number, and the sale on or after the VAT start date (owner, 28 Nov 2026) */
function owInvTaxNow(?string $date = null): array {
    require_once __DIR__ . '/vat_lib.php';
    $on = owVatActive($date); $v = owVatSettings();
    return ['tax' => $on, 'rate' => $on ? $v['rate'] : 0.0, 'number' => $on ? $v['number'] : ''];
}

/* What a purchase is, read fresh from its own table. null = no such purchase, or not paid / confirmed yet */
function owInvSource(string $kind, int $id): ?array {
    $db = getDB();
    try {
        if ($kind === 'order') {
            if (!owHasTable('orders')) return null;
            $st = $db->prepare('SELECT * FROM orders WHERE id = :id'); $st->execute([':id' => $id]); $o = $st->fetch();
            if (!$o || $o['paid_at'] === null || in_array($o['status'], ['created', 'cancelled'], true)) return null;
            $lab = ['artwork' => ['Original artwork', 'عمل فني أصلي'], 'gift' => ['Gift', 'هدية'], 'commission' => ['Private commission', 'عمل خاص'],
                    'course' => ['Course', 'دورة'], 'workshop' => ['Workshop', 'ورشة']][$o['kind']] ?? ['Purchase', 'شراء'];
            return ['ref' => (string) $o['order_no'], 'user' => $o['buyer_user_id'] ? (int) $o['buyer_user_id'] : null, 'name' => (string) $o['buyer_name'], 'email' => (string) $o['buyer_email'],
                    'en' => $lab[0] . ': ' . $o['title'], 'ar' => $lab[1] . ': ' . $o['title'], 'amount' => (float) $o['amount'], 'vat' => (float) ($o['vat_amount'] ?? 0), 'date' => (string) $o['paid_at'], 'lang' => 'en', 'kind' => (string) $o['kind']];
        }
        if ($kind === 'course') {
            $st = $db->prepare('SELECT r.*, c.title_en, c.title_ar, i.name_en AS inst_en, i.name_ar AS inst_ar FROM course_registrations r
                                JOIN courses c ON c.id = r.course_id JOIN institutes i ON i.id = c.institute_id WHERE r.id = :id');
            $st->execute([':id' => $id]); $r = $st->fetch();
            if (!$r || $r['status'] !== 'confirmed') return null;
            $lang = str_contains((string) ($r['notes'] ?? ''), 'lang ar') ? 'ar' : 'en';
            return ['ref' => (string) $r['ref_no'], 'user' => $r['user_id'] ? (int) $r['user_id'] : null, 'name' => (string) $r['student_name'], 'email' => (string) $r['student_email'],
                    'en' => 'Course: ' . ($r['title_en'] ?: $r['title_ar']) . ' · ' . ($r['inst_en'] ?: $r['inst_ar']),
                    'ar' => 'دورة: ' . ($r['title_ar'] ?: $r['title_en']) . ' · ' . ($r['inst_ar'] ?: $r['inst_en']), 'amount' => (float) $r['amount'], 'date' => (string) ($r['paid_at'] ?? ''), 'lang' => $lang, 'kind' => 'course'];
        }
        if ($kind === 'workshop') {
            if (!owHasTable('workshop_bookings')) return null;
            $st = $db->prepare('SELECT b.*, w.title_en, w.title_ar FROM workshop_bookings b JOIN artist_workshops w ON w.id = b.workshop_id WHERE b.id = :id');
            $st->execute([':id' => $id]); $b = $st->fetch();
            if (!$b || $b['status'] !== 'paid') return null;
            $u = null;
            if (filter_var($b['email'], FILTER_VALIDATE_EMAIL)) { $q = $db->prepare("SELECT id FROM users WHERE LOWER(email) = :e AND is_verified = 1 LIMIT 1"); $q->execute([':e' => strtolower((string) $b['email'])]); $u = ($x = $q->fetchColumn()) ? (int) $x : null; }
            return ['ref' => (string) $b['ref_no'], 'user' => $u, 'name' => (string) $b['name'], 'email' => (string) $b['email'],
                    'en' => 'Workshop: ' . ($b['title_en'] ?: $b['title_ar']), 'ar' => 'ورشة: ' . ($b['title_ar'] ?: $b['title_en']), 'amount' => (float) $b['amount'], 'date' => (string) ($b['paid_at'] ?? ''), 'lang' => $b['lang'] === 'ar' ? 'ar' : 'en', 'kind' => 'workshop'];
        }
        if ($kind === 'ticket') {
            $st = $db->prepare('SELECT t.*, p.name_en, p.name_ar FROM museum_tickets t JOIN art_places p ON p.id = t.place_id WHERE t.id = :id');
            $st->execute([':id' => $id]); $t = $st->fetch();
            if (!$t || !in_array($t['status'], ['paid', 'used'], true)) return null;
            $lines = json_decode((string) $t['lines_json'], true) ?: [];
            $en = []; $ar = []; foreach ($lines as $l) { $en[] = $l['qty'] . ' × ' . $l['en']; $ar[] = $l['qty'] . ' × ' . $l['ar']; }
            return ['ref' => (string) $t['ref_no'], 'user' => $t['user_id'] ? (int) $t['user_id'] : null, 'name' => (string) $t['buyer_name'], 'email' => (string) $t['buyer_email'],
                    'en' => 'Museum ticket: ' . $t['name_en'] . ' · ' . $t['visit_date'] . ($en ? ' · ' . implode(', ', $en) : ''),
                    'ar' => 'تذكرة متحف: ' . ($t['name_ar'] ?: $t['name_en']) . ' · ' . $t['visit_date'] . ($ar ? ' · ' . implode('، ', $ar) : ''), 'amount' => (float) $t['amount'], 'date' => (string) ($t['paid_at'] ?? ''), 'lang' => 'en', 'kind' => 'ticket'];
        }
    } catch (Throwable $e) { error_log('owInvSource: ' . $e->getMessage()); }
    return null;
}

function owInvGet(string $kind, int $id): ?array {
    if (!owInvReady()) return null;
    $st = getDB()->prepare('SELECT * FROM invoices WHERE source_kind = :k AND source_id = :id'); $st->execute([':k' => $kind, ':id' => $id]);
    return $st->fetch() ?: null;
}
function owInvByNo(string $no): ?array {
    if (!owInvReady() || !preg_match('/^OW-\d{4}-\d{5,}$/', $no)) return null;
    $st = getDB()->prepare('SELECT * FROM invoices WHERE invoice_no = :n'); $st->execute([':n' => $no]);
    return $st->fetch() ?: null;
}

/* Make the invoice of a purchase (once). Safe to call again: it returns the same row. null = not paid yet, or tables missing */
function owInvoiceIssue(string $kind, int $id): ?array {
    if (!in_array($kind, OW_INV_KINDS, true) || !owInvReady() || (defined('OW_PREVIEW') && OW_PREVIEW)) return null;
    if ($old = owInvGet($kind, $id)) return $old;
    $src = owInvSource($kind, $id);
    if (!$src) return null;
    $tax = owInvTaxNow($src['date'] !== '' ? $src['date'] : null);   // the sale (payment) date decides: on or after the VAT start date
    $amount = round(max(0, $src['amount']), 2);
    $vat = !$tax['tax'] ? 0.0 : (($src['vat'] ?? 0) > 0 ? round((float) $src['vat'], 2) : round($amount * $tax['rate'] / (100 + $tax['rate']), 2));   // VAT is INCLUDED in the price the customer paid
    $addr = function_exists('owInvoiceAddress') ? owInvoiceAddress() : '';
    $db = getDB();
    $year = (int) date('Y');
    try {
        $db->beginTransaction();
        $db->prepare('INSERT INTO invoice_counters (year, last) VALUES (:y, LAST_INSERT_ID(1)) ON DUPLICATE KEY UPDATE last = LAST_INSERT_ID(last + 1)')->execute([':y' => $year]);
        $seq = (int) $db->query('SELECT LAST_INSERT_ID()')->fetchColumn();
        $no = sprintf('OW-%d-%05d', $year, $seq);
        $db->prepare('INSERT INTO invoices (invoice_no, year, seq, source_kind, source_id, ref_no, user_id, customer_name, customer_email, item_en, item_ar, amount, is_tax, vat_rate, vat_amount, vat_number, seller_address, currency, lang, issued_at)
                      VALUES (:no, :y, :s, :k, :id, :ref, :u, :n, :e, :ien, :iar, :a, :t, :vr, :va, :vn, :sa, \'SAR\', :l, NOW())')
           ->execute([':no' => $no, ':y' => $year, ':s' => $seq, ':k' => $kind, ':id' => $id, ':ref' => mb_substr($src['ref'], 0, 40), ':u' => $src['user'],
                      ':n' => mb_substr($src['name'], 0, 150), ':e' => mb_substr(strtolower(trim($src['email'])), 0, 190), ':ien' => mb_substr($src['en'], 0, 255), ':iar' => mb_substr($src['ar'], 0, 255),
                      ':a' => $amount, ':t' => $tax['tax'] ? 1 : 0, ':vr' => $tax['rate'], ':va' => $vat, ':vn' => $tax['number'], ':sa' => mb_substr($addr, 0, 400), ':l' => $src['lang']]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        if ($old = owInvGet($kind, $id)) return $old;   // made at the same moment by another request
        error_log('owInvoiceIssue: ' . $e->getMessage());
        return null;
    }
    owLog('invoice_issue', 'invoice', (int) $db->lastInsertId() ?: null, $no . ' · ' . $kind . ' ' . $id, ['type' => 'system', 'id' => null]);
    return owInvGet($kind, $id);
}

function owInvTitle(array $inv): array { return (int) $inv['is_tax'] === 1 ? ['Tax invoice', 'فاتورة ضريبية'] : ['Purchase receipt', 'إيصال شراء']; }
function owInvFileName(array $inv): string { return $inv['invoice_no'] . '.pdf'; }
function owInvMoney($n): string { return number_format((float) $n, 2, '.', ','); }

/* The PDF (bytes). Made fresh from the frozen row, never stored */
function owInvoicePdf(array $inv): string {
    require_once __DIR__ . '/lib/TCPDF/tcpdf.php';
    $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    [$tEn, $tAr] = owInvTitle($inv);
    $tax = (int) $inv['is_tax'] === 1;
    $d = strtotime((string) $inv['issued_at']);
    $months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
    $dEn = date('j F Y', $d); $dAr = date('j', $d) . ' ' . $months[(int) date('n', $d) - 1] . ' ' . date('Y', $d);
    $amt = (float) $inv['amount']; $vat = (float) $inv['vat_amount'];
    $navy = '#23314A'; $muted = '#4A5568'; $line = '#E2E8F0';

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Oweili'); $pdf->SetAuthor('Oweili'); $pdf->SetTitle($tEn . ' ' . $inv['invoice_no']);
    $pdf->setPrintHeader(false); $pdf->setPrintFooter(false);
    $pdf->SetMargins(16, 16, 16); $pdf->SetAutoPageBreak(true, 16);
    $pdf->SetFont('dejavusans', '', 10); $pdf->SetTextColor(35, 49, 74);
    $pdf->AddPage();

    $sellerEn = $h(OW_INV_SELLER[0]) . '<br>Unified National Number ' . $h(defined('OW_COMPANY_UNN') ? OW_COMPANY_UNN : '7053193244') . '<br>Saudi Arabia<br>contact@oweili.com'
              . ($tax ? '<br>VAT number ' . $h($inv['vat_number']) : '');
    $sellerAr = $h(OW_INV_SELLER[1]) . '<br>الرقم الوطني الموحد ' . $h(defined('OW_COMPANY_UNN') ? OW_COMPANY_UNN : '7053193244') . '<br>المملكة العربية السعودية<br>contact@oweili.com'
              . ($tax ? '<br>الرقم الضريبي ' . $h($inv['vat_number']) : '');
    $addr = trim((string) ($inv['seller_address'] ?? ''));   // the invoice address from Owner settings, frozen in the row; shown ONLY here
    if ($addr !== '') { $sellerEn .= '<br>' . nl2br($h($addr)); $sellerAr .= '<br>' . nl2br($h($addr)); }
    $price = $amt > 0 ? owInvMoney($amt) . ' SAR' : 'Free · مجاني';

    $html = '<table cellpadding="0" cellspacing="0" width="100%"><tr>'
        . '<td width="50%"><span style="font-size:20pt;font-weight:bold;color:' . $navy . ';letter-spacing:2px">OWEILI</span><br><span style="font-size:9pt;color:' . $muted . '">oweili.com</span></td>'
        . '<td width="50%" align="right"><span style="font-size:16pt;font-weight:bold">' . $h($tEn) . '</span><br><span style="font-size:16pt;font-weight:bold">' . $h($tAr) . '</span></td>'
        . '</tr></table><br>'
        . ($tax ? '' : '<table cellpadding="6" width="100%"><tr><td style="background-color:#FBEFEF;border:1px solid #E8C4C1;font-size:10pt" align="center"><b>Not a tax invoice · ليست فاتورة ضريبية</b></td></tr></table><br>')
        . '<table cellpadding="4" width="100%" style="font-size:9.5pt">'
        . '<tr><td width="50%"><b>' . ($tax ? 'Invoice number' : 'Receipt number') . ':</b> ' . $h($inv['invoice_no']) . '<br><b>Date:</b> ' . $h($dEn)
        . ($inv['ref_no'] !== '' ? '<br><b>Order or booking:</b> ' . $h($inv['ref_no']) : '') . '</td>'
        . '<td width="50%" align="right" dir="rtl"><b>' . ($tax ? 'رقم الفاتورة' : 'رقم الإيصال') . ':</b> ' . $h($inv['invoice_no']) . '<br><b>التاريخ:</b> ' . $h($dAr)
        . ($inv['ref_no'] !== '' ? '<br><b>الطلب أو الحجز:</b> ' . $h($inv['ref_no']) : '') . '</td></tr></table>'
        . '<br><table cellpadding="8" width="100%" style="font-size:9.5pt">'
        . '<tr><td width="50%" style="border:1px solid ' . $line . '"><span style="color:' . $muted . '">Seller · البائع</span><br>' . $sellerEn . '</td>'
        . '<td width="50%" style="border:1px solid ' . $line . '" align="right" dir="rtl"><span style="color:' . $muted . '">البائع</span><br>' . $sellerAr . '</td></tr>'
        . '<tr><td colspan="2" style="border:1px solid ' . $line . '"><span style="color:' . $muted . '">Customer · العميل</span><br><b>' . $h($inv['customer_name'] !== '' ? $inv['customer_name'] : '—') . '</b>'
        . ($inv['customer_email'] !== '' ? '<br>' . $h($inv['customer_email']) : '') . '</td></tr></table>'
        . '<br><table cellpadding="8" width="100%" style="font-size:10pt">'
        . '<tr style="background-color:#F4F6F9;font-weight:bold"><td width="70%">Item · البند</td><td width="30%" align="right">Amount · المبلغ</td></tr>'
        . '<tr><td width="70%" style="border-bottom:1px solid ' . $line . '">' . $h($inv['item_en']) . '<br><span dir="rtl">' . $h($inv['item_ar']) . '</span></td>'
        . '<td width="30%" align="right" style="border-bottom:1px solid ' . $line . '">' . $h($price) . '</td></tr>';
    if ($tax) {
        $html .= '<tr><td width="70%" align="right">Total before VAT · الإجمالي قبل الضريبة</td><td width="30%" align="right">' . owInvMoney($amt - $vat) . ' SAR</td></tr>'
               . '<tr><td width="70%" align="right">VAT ' . rtrim(rtrim(number_format((float) $inv['vat_rate'], 2), '0'), '.') . '% · ضريبة القيمة المضافة</td><td width="30%" align="right">' . owInvMoney($vat) . ' SAR</td></tr>';
    }
    $html .= '<tr style="font-weight:bold"><td width="70%" align="right">' . ($tax ? 'Total including VAT · الإجمالي شامل الضريبة' : 'Total paid · الإجمالي المدفوع') . '</td>'
        . '<td width="30%" align="right" style="font-size:12pt">' . owInvMoney($amt) . ' SAR</td></tr></table>'
        . '<br><br><span style="font-size:9pt;color:' . $muted . '">' . ($tax ? 'Prices include VAT.' : 'No VAT is charged on this purchase.') . ' Thank you for buying on Oweili.</span><br>'
        . '<span dir="rtl" style="font-size:9pt;color:' . $muted . '">' . ($tax ? 'الأسعار شاملة ضريبة القيمة المضافة.' : 'لا تُحتسب ضريبة قيمة مضافة على هذا الشراء.') . ' شكرا لشرائك من أويلي.</span>';
    $pdf->writeHTML($html, true, false, true, false, '');
    if ($tax) {   // ZATCA QR code, Phase 1 (TLV then Base64): seller, VAT number, date and time, total with VAT, VAT
        $qr = owInvZatcaQr($inv);
        $y = $pdf->GetY() + 6;
        $pdf->write2DBarcode($qr, 'QRCODE,M', 16, $y, 34, 34, ['border' => false, 'padding' => 0, 'fgcolor' => [0, 0, 0], 'bgcolor' => false], 'N');
        $pdf->SetXY(54, $y + 10); $pdf->SetFont('dejavusans', '', 8.5); $pdf->SetTextColor(74, 85, 104);
        $pdf->MultiCell(120, 5, "ZATCA QR code (simplified tax invoice)\nرمز الاستجابة السريعة لهيئة الزكاة والضريبة والجمارك", 0, 'L');
    }
    return $pdf->Output(owInvFileName($inv), 'S');
}

/* The Phase 1 QR text of a tax invoice (vat_lib.php owZatcaTlv). The seller is the legal Arabic name. */
function owInvZatcaQr(array $inv): string {
    require_once __DIR__ . '/vat_lib.php';
    $t = strtotime((string) $inv['issued_at']);
    return owZatcaTlv(OW_INV_SELLER[1], (string) $inv['vat_number'], gmdate('Y-m-d\\TH:i:s\\Z', $t),
        number_format((float) $inv['amount'], 2, '.', ''), number_format((float) $inv['vat_amount'], 2, '.', ''));
}

/* The PDF as an email attachment */
function owInvAttachment(array $inv): array {
    try { return ['name' => owInvFileName($inv), 'data' => owInvoicePdf($inv), 'type' => 'application/pdf']; }
    catch (Throwable $e) { error_log('owInvAttachment: ' . $e->getMessage()); return []; }
}
function owInvMarkEmailed(array $inv): void {
    try { getDB()->prepare('UPDATE invoices SET emailed_at = NOW() WHERE id = :id')->execute([':id' => (int) $inv['id']]); } catch (Throwable $e) {}
}

/* Make the invoice and send it on its own email (workshops, gift and commission orders).
   Courses and tickets carry it on their own confirmation email; artworks on the email
   with the certificate (owArtworkPaidMail in order_lib.php). */
function owInvoiceSend(string $kind, int $id): ?array {
    $inv = owInvoiceIssue($kind, $id);
    if (!$inv || $inv['emailed_at'] !== null || !filter_var($inv['customer_email'], FILTER_VALIDATE_EMAIL)) return $inv;
    require_once __DIR__ . '/mail_lib.php';
    $att = owInvAttachment($inv);
    [$tEn, $tAr] = owInvTitle($inv);
    $link = 'https://oweili.com/collector_dashboard.php?tab=invoices';
    $en = '<p>Thank you for your purchase.</p><p>Your ' . strtolower($tEn) . ' <b>' . htmlspecialchars($inv['invoice_no']) . '</b> is attached as a PDF.</p>'
        . '<p>' . htmlspecialchars($inv['item_en']) . '<br>Total: ' . owInvMoney($inv['amount']) . ' SAR</p>'
        . '<p>If you have an Oweili account with this email, you also find it in My invoices: <a href="' . $link . '">' . $link . '</a></p>';
    $ar = '<p>شكرا لشرائك.</p><p>' . $tAr . ' رقم <b>' . htmlspecialchars($inv['invoice_no']) . '</b> مرفق بصيغة PDF.</p>'
        . '<p>' . htmlspecialchars($inv['item_ar']) . '<br>الإجمالي: ' . owInvMoney($inv['amount']) . ' ر.س</p>'
        . '<p>إن كان لديك حساب في أويلي بهذا البريد فستجده أيضا في «فواتيري»: <a href="' . $link . '&amp;lang=ar">' . $link . '</a></p>';
    $box = 'font-family:Tahoma,Arial,sans-serif;font-size:15px;line-height:1.7;color:#23314A;';
    $body = '<div style="' . $box . '">' . ($inv['lang'] === 'ar' ? '<div dir="rtl" style="text-align:right">' . $ar . '</div><hr style="border:0;border-top:1px solid #e5e7eb;margin:20px 0">' . $en
                                                               : $en . '<hr style="border:0;border-top:1px solid #e5e7eb;margin:20px 0"><div dir="rtl" style="text-align:right">' . $ar . '</div>') . '</div>';
    if (owSendMail((string) $inv['customer_email'], 'Your ' . strtolower($tEn) . ' · ' . $tAr . ': ' . $inv['invoice_no'], $body, true, $log, $att ? ['attach' => [$att]] : [])) owInvMarkEmailed($inv);
    return $inv;
}

/* For the confirmation emails that carry the invoice themselves: make it, give the attachment list */
function owInvoiceAttachFor(string $kind, int $id): array {
    $inv = owInvoiceIssue($kind, $id);
    if (!$inv) return [[], null];
    $att = owInvAttachment($inv);
    return [$att ? [$att] : [], $inv];
}

/* ---------------- who sees which invoice ---------------- */
function owInvForUserWhere(int $uid, string $email, bool $verified, array &$args): string {
    $args[':iu'] = $uid;
    if ($verified && $email !== '') { $args[':ie'] = strtolower(trim($email)); return '(user_id = :iu OR (user_id IS NULL AND customer_email = :ie))'; }
    return 'user_id = :iu';
}
function owInvForUser(int $uid, string $email, bool $verified): array {
    if (!owInvReady()) return [];
    $args = []; $w = owInvForUserWhere($uid, $email, $verified, $args);
    $st = getDB()->prepare("SELECT * FROM invoices WHERE $w ORDER BY issued_at DESC, id DESC LIMIT 300"); $st->execute($args);
    return $st->fetchAll();
}
/* Can this signed-in person (or the owner) open this invoice? */
function owInvCanOpen(array $inv, ?array $me, bool $isOwner): bool {
    if ($isOwner) return true;
    if (!$me) return false;
    if ($inv['user_id'] !== null) return (int) $inv['user_id'] === (int) $me['id'];
    return !empty($me['is_verified']) && $inv['customer_email'] !== '' && strtolower(trim((string) $me['email'])) === $inv['customer_email'];
}
/* invoice_no for each purchase of a list, for the "Invoice" links: [source_id => invoice_no] */
function owInvNumbers(string $kind, array $ids): array {
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (!$ids || !owInvReady()) return [];
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = getDB()->prepare("SELECT source_id, invoice_no FROM invoices WHERE source_kind = ? AND source_id IN ($in)"); $st->execute(array_merge([$kind], $ids));
    $out = []; foreach ($st->fetchAll() as $r) $out[(int) $r['source_id']] = (string) $r['invoice_no'];
    return $out;
}
/* Every invoice for the owner (Accounting) */
function owInvAll(int $limit = 200, string $q = ''): array {
    if (!owInvReady()) return [];
    $args = []; $w = '1';
    if ($q !== '') { $w = '(invoice_no LIKE :q OR ref_no LIKE :q2 OR customer_name LIKE :q3 OR customer_email LIKE :q4)'; $like = '%' . $q . '%'; $args = [':q' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like]; }
    $st = getDB()->prepare("SELECT * FROM invoices WHERE $w ORDER BY issued_at DESC, id DESC LIMIT " . max(1, min(1000, $limit))); $st->execute($args);
    return $st->fetchAll();
}

/* =====================================================================
 *  Artwork orders: once paid, the collector gets the invoice AND the
 *  Certificate of Authenticity (owner, 27 Nov 2026). The certificate is
 *  linked to their account (My collection) at that moment, and both PDFs
 *  travel on ONE email. Called by owOrderMove(…, 'paid') in order_lib.php.
 * ===================================================================== */
function owCertificatePdf(array $cert): string {
    require_once __DIR__ . '/lib/TCPDF/tcpdf.php';
    require_once __DIR__ . '/cert_lib.php';
    $s = $cert['snap'] ?? (json_decode((string) $cert['snapshot'], true) ?: []);
    $h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
    $no = (string) $cert['cert_no'];
    [$issEn, $issAr] = owDateBi((string) $cert['issued_at']);
    [$decEn, $decAr] = owDateBi((string) ($s['declared_at'] ?? ''), true);
    [$sizeEn, $sizeAr] = owCertSize($s['width_cm'] ?? 0, $s['height_cm'] ?? 0);
    [$edEn, $edAr] = owCertEdition($s['edition_type'] ?? 'original', $s['edition_number'] ?? null, $s['edition_size'] ?? null);
    $verify = owCertVerifyUrl($no);
    $owner = trim((string) ($cert['owner_name'] ?? ''));

    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Oweili'); $pdf->SetAuthor('Oweili'); $pdf->SetTitle('Certificate of Authenticity ' . $no);
    $pdf->setPrintHeader(false); $pdf->setPrintFooter(false);
    $pdf->SetMargins(14, 12, 14); $pdf->SetAutoPageBreak(false);
    $pdf->SetFont('dejavusans', '', 9.5); $pdf->SetTextColor(35, 49, 74);
    $pdf->AddPage();
    $pdf->SetDrawColor(219, 128, 132); $pdf->SetLineWidth(0.6); $pdf->Rect(8, 8, 281, 194);

    /* the main photo, on the left (a local file under uploads/ only) */
    $img = (string) ($s['image'] ?? '');
    $path = preg_match('#^/?uploads/[A-Za-z0-9_./-]+\.(jpe?g|png)$#i', $img) ? __DIR__ . '/' . ltrim($img, '/') : '';
    if ($path !== '' && is_file($path) && !str_contains($img, '..')) {
        try { $pdf->Image($path, 16, 40, 92, 92, '', '', '', true, 150, '', false, false, 0, 'CM'); } catch (Throwable $e) {}
    }
    $pdf->SetXY(14, 14);
    $pdf->writeHTMLCell(269, 0, 14, 14, '<table width="100%"><tr><td width="50%"><span style="font-size:18pt;font-weight:bold;letter-spacing:2px">OWEILI</span><br><span style="font-size:13pt;font-weight:bold">Certificate of Authenticity</span></td>'
        . '<td width="50%" align="right"><span style="font-size:9pt;color:#4A5568">' . $h($no) . '</span><br><span style="font-size:13pt;font-weight:bold">شهادة أصالة</span></td></tr></table>', 0, 1);
    $row = fn($en, $ar, $vEn, $vAr = null) => '<tr><td width="24%" style="color:#4A5568">' . $h($en) . '</td><td width="52%"><b>' . $h($vEn) . '</b>'
        . (($vAr ?? $vEn) !== $vEn ? '<br><span dir="rtl">' . $h($vAr) . '</span>' : '') . '</td><td width="24%" align="right" dir="rtl" style="color:#4A5568">' . $h($ar) . '</td></tr>';
    $tbl = '<table cellpadding="3" style="font-size:9.5pt">'
        . $row('Certificate number', 'رقم الشهادة', $no)
        . $row('Title', 'العنوان', (string) ($s['title_en'] ?? ''), (string) ($s['title_ar'] ?? ''))
        . $row('Artist', 'الفنان', (string) ($s['artist_en'] ?? ''), (string) ($s['artist_ar'] ?? ''))
        . $row('Medium', 'الخامة', (string) ($s['medium'] ?? ''))
        . $row('Size', 'المقاس', $sizeEn, $sizeAr)
        . $row('Year', 'السنة', (string) (int) ($s['year_made'] ?? 0))
        . $row('Type', 'النوع', $edEn, $edAr)
        . $row('Art style', 'الأسلوب', (string) ($s['type_en'] ?? ''), (string) ($s['type_ar'] ?? ''))
        . ($owner !== '' ? $row('Owner', 'المالك', $owner) : '')
        . '</table>'
        . '<br><span style="font-size:9pt">“' . $h(OW_CERT_DECLARATION[0]) . '” Declared by the artist on ' . $h($decEn) . '</span>'
        . '<br><span dir="rtl" style="font-size:9pt">«' . $h(OW_CERT_DECLARATION[1]) . '» أقرّ به الفنان بتاريخ ' . $h($decAr) . '</span>'
        . '<br><br><span style="font-size:8.5pt;color:#4A5568">' . $h(OW_CERT_RIGHTS[0]) . '</span><br><span dir="rtl" style="font-size:8.5pt;color:#4A5568">' . $h(OW_CERT_RIGHTS[1]) . '</span>';
    $pdf->writeHTMLCell(165, 0, 118, 38, $tbl, 0, 1);
    $pdf->write2DBarcode($verify, 'QRCODE,M', 16, 150, 34, 34, ['border' => false, 'padding' => 0, 'fgcolor' => [35, 49, 74], 'bgcolor' => false], 'N');
    $pdf->writeHTMLCell(110, 0, 54, 156, '<span style="font-size:8.5pt"><b>Scan to verify</b> · امسح للتحقق<br>' . $h(preg_replace('#^https://#', '', $verify)) . '</span>', 0, 1);
    $pdf->writeHTMLCell(120, 0, 163, 164, '<div style="font-size:9pt;text-align:right">Issued by <b>Oweili</b>, the home of real art, on <b>' . $h($issEn) . '</b></div>'
        . '<div dir="rtl" style="font-size:9pt;text-align:right">صادرة عن أويلي، بيت الفن الأصيل، بتاريخ ' . $h($issAr) . '</div>', 0, 1);
    return $pdf->Output('Oweili-Certificate-' . $no . '.pdf', 'S');
}

/* The artwork order is paid: invoice + certificate, linked to the account, one email with both PDFs */
function owArtworkPaidMail(int $orderId): void {
    try {
        $st = getDB()->prepare('SELECT * FROM orders WHERE id = :id'); $st->execute([':id' => $orderId]); $o = $st->fetch();
        if (!$o || $o['kind'] !== 'artwork' || $o['paid_at'] === null) return;
        $inv = owInvoiceIssue('order', $orderId);
        require_once __DIR__ . '/collector_lib.php';
        require_once __DIR__ . '/cert_lib.php';
        owCertLinkToCollector($o);   // My collection → "Open the certificate" (only with an account)
        $cert = owCertReady() && $o['ref_id'] ? owCertDecode(owCertForArtwork((int) $o['ref_id'])) : null;
        if (!filter_var($o['buyer_email'], FILTER_VALIDATE_EMAIL)) return;
        if ($inv && $inv['emailed_at'] !== null) return;   // sent once
        $att = [];
        if ($inv && ($a = owInvAttachment($inv))) $att[] = $a;
        if ($cert) { try { $att[] = ['name' => 'Oweili-Certificate-' . $cert['cert_no'] . '.pdf', 'data' => owCertificatePdf($cert), 'type' => 'application/pdf']; } catch (Throwable $e) { error_log('cert pdf: ' . $e->getMessage()); } }
        $h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
        [$tEn, $tAr] = $inv ? owInvTitle($inv) : ['Receipt', 'الإيصال'];
        $link = 'https://oweili.com/collector_dashboard.php?tab=collection';
        $en = '<p><b>Thank you, your artwork is paid.</b></p><p>' . $h($o['title']) . '<br>Order ' . $h($o['order_no']) . '<br>Total: ' . owInvMoney($o['amount']) . ' SAR</p>'
            . '<p>Attached: your ' . strtolower($tEn) . ($inv ? ' ' . $h($inv['invoice_no']) : '') . ($cert ? ' and the Certificate of Authenticity ' . $h($cert['cert_no']) : '') . '.</p>'
            . ($o['buyer_user_id'] ? '<p>You also find them in My collection: <a href="' . $link . '">' . $link . '</a></p>' : '')
            . '<p>We will tell you when the artwork is on its way.</p>';
        $ar = '<p><b>شكرا لك، تم دفع العمل الفني.</b></p><p>' . $h($o['title']) . '<br>الطلب ' . $h($o['order_no']) . '<br>الإجمالي: ' . owInvMoney($o['amount']) . ' ر.س</p>'
            . '<p>مرفق: ' . $tAr . ($inv ? ' ' . $h($inv['invoice_no']) : '') . ($cert ? ' وشهادة الأصالة ' . $h($cert['cert_no']) : '') . '.</p>'
            . ($o['buyer_user_id'] ? '<p>تجدهما أيضا في «مجموعتي»: <a href="' . $link . '&amp;lang=ar">' . $link . '</a></p>' : '')
            . '<p>سنخبرك عندما يكون العمل في الطريق.</p>';
        $box = 'font-family:Tahoma,Arial,sans-serif;font-size:15px;line-height:1.7;color:#23314A;';
        $body = '<div style="' . $box . '">' . $en . '<hr style="border:0;border-top:1px solid #e5e7eb;margin:20px 0"><div dir="rtl" style="text-align:right">' . $ar . '</div></div>';
        require_once __DIR__ . '/mail_lib.php';
        if (owSendMail((string) $o['buyer_email'], 'Your artwork is paid · تم دفع عملك الفني: ' . $o['order_no'], $body, true, $log, ['attach' => $att]) && $inv) owInvMarkEmailed($inv);
        owLog('order_paid_mail', 'order', $orderId, ($inv['invoice_no'] ?? '') . ($cert ? ' + ' . $cert['cert_no'] : ''), ['type' => 'system', 'id' => null]);
    } catch (Throwable $e) { error_log('owArtworkPaidMail: ' . $e->getMessage()); }
}
