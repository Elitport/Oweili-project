<?php
/* =====================================================================
 *  vat_lib.php — VAT for every paid sale (owner, 28 Nov 2026)
 *
 *  Elitport is VAT registered. Under the amended Article 47(3) of the VAT
 *  Implementing Regulations (in force from January 2026), an online
 *  marketplace that sells for Saudi sellers who are NOT VAT registered is
 *  treated as the seller. So Oweili charges 15% VAT on the FULL price of
 *  every paid sale (artworks, courses, workshops, museum tickets), the
 *  customer pays it, and the invoice is in Oweili's (Elitport's) name.
 *
 *  Example: the artist's price 1,000 → the customer pays 1,150 (150 VAT);
 *  Oweili's commission is worked out on the artist's price BEFORE VAT
 *  (at 20%: 200), so the artist gets 800.
 *
 *  VAT is ACTIVE only when, in Owner settings → VAT, the switch is on AND
 *  there is a 15-digit VAT number AND the sale date is on or after the
 *  "VAT start date". Before that: prices without VAT and a purchase receipt.
 *  While VAT is active every price a customer sees already includes VAT
 *  with the words "VAT included / شامل الضريبة"; nothing is added at
 *  checkout. Settings: site_settings 'vat' JSON {on, rate, number, start}
 *  and 'invoice_address' (shown only inside the invoice PDF).
 * ===================================================================== */
require_once __DIR__ . '/gallery_lib.php';

function owVatSettings(): array {
    $j = json_decode((string) owSetting('vat', ''), true);
    $j = is_array($j) ? $j : [];
    $num = preg_replace('/\D/', '', (string) ($j['number'] ?? ''));
    $start = (string) ($j['start'] ?? '');
    if ($start !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) $start = '';
    $rate = isset($j['rate']) ? (float) $j['rate'] : 15.0;
    return ['on' => !empty($j['on']), 'rate' => ($rate >= 0 && $rate <= 50) ? $rate : 15.0, 'number' => strlen($num) === 15 ? $num : '', 'start' => $start];
}
function owVatForget(): void { $GLOBALS['OW_SETTINGS_CACHE'] = []; }

/* Is VAT charged on a sale made on this date (Y-m-d or a date-time; default: today)? */
function owVatActive(?string $date = null): bool {
    $v = owVatSettings();
    if (!$v['on'] || $v['number'] === '' || $v['rate'] <= 0) return false;
    $d = $date !== null && $date !== '' ? substr($date, 0, 10) : date('Y-m-d');
    return $v['start'] === '' || $d >= $v['start'];
}
function owVatRate(?string $date = null): float { return owVatActive($date) ? owVatSettings()['rate'] : 0.0; }

/* The artist's or institute's price (before VAT) → the VAT and what the customer pays */
function owVatOf(float $base, ?string $date = null): float { return round($base * owVatRate($date) / 100, 2); }
function owGross(float $base, ?string $date = null): float { return round($base + owVatOf($base, $date), 2); }
/* The factor between what visitors see and the artist's price (1.15 while VAT is active, else 1) */
function owVatFactor(): float { return 1 + owVatRate() / 100; }

/* Split of one paid sale: commission on the price BEFORE VAT, VAT on top */
function owSaleSplit(float $base, float $commissionRate, ?string $date = null): array {
    $base = round($base, 2);
    $fee = round($base * $commissionRate / 100, 2);
    $vat = owVatOf($base, $date);
    return ['base' => $base, 'rate' => $commissionRate, 'commission' => $fee, 'partner' => round($base - $fee, 2), 'vat' => $vat, 'gross' => round($base + $vat, 2)];
}

/* "VAT included / شامل الضريبة", only while VAT is active (empty otherwise) */
function owVatTag(string $class = 'ow-vat-tag'): string {
    if (!owVatActive()) return '';
    return '<span class="' . htmlspecialchars($class, ENT_QUOTES) . '">' . (function_exists('owBi') ? owBi('VAT included', 'شامل الضريبة') : 'VAT included · شامل الضريبة') . '</span>';
}
function owVatTagText(): string { return owVatActive() ? (function_exists('owIsAr') && owIsAr() ? 'شامل الضريبة' : 'VAT included') : ''; }

/* The address printed only inside invoice PDFs (Owner settings) */
function owInvoiceAddress(): string { return trim((string) owSetting('invoice_address', '')); }

/* =====================================================================
 *  ZATCA QR code, Phase 1 (simplified tax invoices): TLV, then Base64.
 *  1 seller name · 2 VAT number · 3 invoice date and time (ISO 8601)
 *  4 total including VAT · 5 VAT amount. Every value is UTF-8 text.
 * ===================================================================== */
function owZatcaTlv(string $seller, string $vatNo, string $isoTime, string $total, string $vat): string {
    $out = '';
    foreach ([1 => $seller, 2 => $vatNo, 3 => $isoTime, 4 => $total, 5 => $vat] as $tag => $val) {
        $val = (string) $val;
        if (strlen($val) > 255) $val = mb_strcut($val, 0, 255, 'UTF-8');
        $out .= chr($tag) . chr(strlen($val)) . $val;
    }
    return base64_encode($out);
}
/* Reads a Phase 1 QR text back into its 5 values (for tests and the owner's check) */
function owZatcaTlvRead(string $b64): array {
    $raw = base64_decode($b64, true); $out = []; $i = 0;
    if ($raw === false) return [];
    while ($i + 2 <= strlen($raw)) { $t = ord($raw[$i]); $l = ord($raw[$i + 1]); $out[$t] = substr($raw, $i + 2, $l); $i += 2 + $l; }
    return $out;
}
