<?php
/* =====================================================================
 *  collector_lib.php — the collector dashboard's data (owner, 24 Nov 2026).
 *  Every function reads ONLY the signed-in collector's own rows.
 *  A collector sees an artist's public name and public page only, never
 *  the artist's email, phone or payout details. Artists see only how many
 *  collectors follow them, never who.
 *  Tables: artist_follows (collector_update.sql), orders, order_complaints,
 *          purchases, artworks, users, certificates, course_registrations,
 *          courses, museum_tickets, art_places, sent_messages, activity_log
 * ===================================================================== */
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/artwork_lib.php';

/* The collector's words for an order's step: "paid" reads "Paid, being prepared" (words only, owner) */
const OW_BUYER_ORDER_WORDS = [
    'created'   => ['Waiting for payment', 'بانتظار الدفع'],
    'paid'      => ['Paid, being prepared', 'مدفوع، قيد التجهيز'],
    'shipped'   => ['On its way', 'في الطريق'],
    'delivered' => ['Delivered', 'تم التسليم'],
    'confirmed' => ['Confirmed', 'تم التأكيد'],
    'complaint' => ['Problem reported', 'تم الإبلاغ عن مشكلة'],
    'released'  => ['Confirmed', 'تم التأكيد'],
    'refunded'  => ['Refunded', 'تم رد المبلغ'],
    'cancelled' => ['Cancelled', 'ملغى'],
];
/* the steps shown as a small progress line */
const OW_BUYER_ORDER_STEPS = ['paid', 'shipped', 'delivered', 'confirmed'];

/* where an order is, for the collector: 'confirmed' once they sent "I received the artwork" (or the payout was released) */
function owBuyerOrderStep(array $o): string {
    $s = (string) ($o['status'] ?? '');
    if ($s === 'released' || ($s === 'delivered' && !empty($o['confirmed_at']))) return 'confirmed';
    return isset(OW_BUYER_ORDER_WORDS[$s]) ? $s : 'created';
}
function owBuyerOrderPill(array $o, string $base = 'c-pill'): string {
    $s = owBuyerOrderStep($o); $w = OW_BUYER_ORDER_WORDS[$s];
    $cls = in_array($s, ['complaint', 'created'], true) ? ' warn' : (in_array($s, ['confirmed'], true) ? ' on' : (in_array($s, ['refunded', 'cancelled'], true) ? ' off' : ''));
    if ($base === 'pillx' && $cls === ' off') $cls = '';
    return '<span class="' . $base . $cls . '">' . owBi($w[0], $w[1]) . '</span>';
}

/* ---------------- follow an artist ---------------- */
function owFollowReady(): bool { return owHasTable('artist_follows'); }
/* Only approved artists who are not suspended can be followed */
function owFollowArtistOk(int $artistId): bool {
    if ($artistId <= 0) return false;
    $st = getDB()->prepare("SELECT 1 FROM users WHERE id = :id AND role = 'artist' AND is_approved = 1 AND COALESCE(is_banned, 0) = 0");
    $st->execute([':id' => $artistId]);
    return (bool) $st->fetchColumn();
}
function owIsFollowing(int $collectorId, int $artistId): bool {
    if (!owFollowReady()) return false;
    $st = getDB()->prepare('SELECT 1 FROM artist_follows WHERE collector_id = :c AND artist_id = :a');
    $st->execute([':c' => $collectorId, ':a' => $artistId]);
    return (bool) $st->fetchColumn();
}
function owFollowSet(int $collectorId, int $artistId, bool $on): bool {
    if (!owFollowReady() || $collectorId <= 0) return false;
    if ($on) {
        if (!owFollowArtistOk($artistId)) return false;
        getDB()->prepare('INSERT IGNORE INTO artist_follows (collector_id, artist_id) VALUES (:c, :a)')->execute([':c' => $collectorId, ':a' => $artistId]);
    } else {
        getDB()->prepare('DELETE FROM artist_follows WHERE collector_id = :c AND artist_id = :a')->execute([':c' => $collectorId, ':a' => $artistId]);
    }
    return true;
}
/* For the artist: only the number, never who */
function owFollowerCount(int $artistId): int {
    if (!owFollowReady()) return 0;
    $st = getDB()->prepare("SELECT COUNT(*) FROM artist_follows f JOIN users c ON c.id = f.collector_id AND COALESCE(c.is_banned, 0) = 0 WHERE f.artist_id = :a");
    $st->execute([':a' => $artistId]);
    return (int) $st->fetchColumn();
}
/* The artists this collector follows: public name, picture and city only */
function owFollowedArtists(int $collectorId): array {
    if (!owFollowReady()) return [];
    $st = getDB()->prepare("SELECT u.id, u.profile_picture, u.city, f.created_at AS followed_at, " . owArtistNameSql('u') . ",
            (SELECT COUNT(*) FROM artworks a WHERE a.artist_id = u.id AND a.is_approved = 1 AND a.status IN ('available','auction')" . owNotDraftSql('a') . ") AS works
         FROM artist_follows f JOIN users u ON u.id = f.artist_id
         WHERE f.collector_id = :c AND u.role = 'artist' AND u.is_approved = 1 AND COALESCE(u.is_banned, 0) = 0
         ORDER BY f.created_at DESC LIMIT 200");
    $st->execute([':c' => $collectorId]);
    return $st->fetchAll();
}
/* New works by the artists this collector follows (only what visitors can see in the Gallery) */
function owFollowNewWorks(int $collectorId, int $limit = 8): array {
    if (!owFollowReady()) return [];
    $st = getDB()->prepare("SELECT a.id, a.title_en, a.title_ar, a.image_url, a.price, a.created_at, a.artist_id, " . owArtistNameSql('u') . "
         FROM artist_follows f
         JOIN artworks a ON a.artist_id = f.artist_id AND a.is_approved = 1 AND a.status IN ('available','auction')" . owNotDraftSql('a') . "
         JOIN users u ON u.id = a.artist_id AND COALESCE(u.is_banned, 0) = 0
         WHERE f.collector_id = :c
         ORDER BY a.created_at DESC, a.id DESC LIMIT " . max(1, min(24, $limit)));
    $st->execute([':c' => $collectorId]);
    return $st->fetchAll();
}

/* ---------------- orders, collection, complaints ---------------- */
function owCollectorOrders(int $uid): array {
    require_once __DIR__ . '/order_lib.php';
    if (!owOrdersReady()) return [];
    /* an artwork order shows the artwork's own title in each language */
    $st = getDB()->prepare("SELECT o.id, o.order_no, o.kind, o.ref_id, o.title, o.amount, o.status, o.created_at, o.paid_at, o.shipped_at, o.delivered_at, o.confirmed_at,
                                   a.title_en AS a_en, a.title_ar AS a_ar
                            FROM orders o LEFT JOIN artworks a ON o.kind = 'artwork' AND a.id = o.ref_id
                            WHERE o.buyer_user_id = :u ORDER BY o.created_at DESC, o.id DESC LIMIT 200");
    $st->execute([':u' => $uid]);
    $rows = $st->fetchAll();
    foreach ($rows as &$r) { $r['title_en'] = (string) ($r['a_en'] ?: $r['a_ar'] ?: $r['title']); $r['title_ar'] = (string) ($r['a_ar'] ?: $r['a_en'] ?: $r['title']); }
    return $rows;
}
function owCollectorComplaints(int $uid): array {
    require_once __DIR__ . '/order_lib.php';
    if (!owOrdersReady()) return [];
    $st = getDB()->prepare("SELECT c.id, c.status, c.reason, c.resolution_note, c.created_at, c.resolved_at, o.order_no, o.title,
                                   COALESCE(NULLIF(a.title_en, ''), NULLIF(a.title_ar, ''), o.title) AS title_en, COALESCE(NULLIF(a.title_ar, ''), NULLIF(a.title_en, ''), o.title) AS title_ar
                            FROM order_complaints c JOIN orders o ON o.id = c.order_id LEFT JOIN artworks a ON o.kind = 'artwork' AND a.id = o.ref_id
                            WHERE o.buyer_user_id = :u ORDER BY c.created_at DESC, c.id DESC LIMIT 100");
    $st->execute([':u' => $uid]);
    return $st->fetchAll();
}
/* Every artwork this collector bought: the older purchases list and the newer orders, each artwork once */
function owCollection(int $uid): array {
    $out = [];
    $artCols = "a.id AS aid, a.title_en, a.title_ar, a.image_url, a.artist_id, " . owArtistNameSql('u');
    try {
        require_once __DIR__ . '/order_lib.php';
        if (owOrdersReady()) {
            $st = getDB()->prepare("SELECT o.id AS order_id, o.amount AS price, COALESCE(o.delivered_at, o.paid_at, o.created_at) AS bought_at, o.status, o.confirmed_at, $artCols
                                    FROM orders o JOIN artworks a ON a.id = o.ref_id LEFT JOIN users u ON u.id = a.artist_id
                                    WHERE o.buyer_user_id = :u AND o.kind = 'artwork' AND o.status IN ('paid','shipped','delivered','complaint','released')
                                    ORDER BY bought_at DESC, o.id DESC");
            $st->execute([':u' => $uid]);
            foreach ($st->fetchAll() as $r) if (!isset($out[(int) $r['aid']])) $out[(int) $r['aid']] = $r;
        }
    } catch (Throwable $e) { error_log('collector collection orders: ' . $e->getMessage()); }
    try {
        if (owHasTable('purchases')) {
            $st = getDB()->prepare("SELECT NULL AS order_id, p.price, p.purchased_at AS bought_at, 'released' AS status, NULL AS confirmed_at, $artCols
                                    FROM purchases p JOIN artworks a ON a.id = p.artwork_id LEFT JOIN users u ON u.id = a.artist_id
                                    WHERE p.collector_id = :u ORDER BY p.purchased_at DESC");
            $st->execute([':u' => $uid]);
            foreach ($st->fetchAll() as $r) if (!isset($out[(int) $r['aid']])) $out[(int) $r['aid']] = $r;
        }
    } catch (Throwable $e) { error_log('collector collection purchases: ' . $e->getMessage()); }
    /* the certificate of each artwork: the full certificate only when it is linked to this collector */
    try {
        require_once __DIR__ . '/cert_lib.php';
        foreach ($out as $aid => $r) {
            $c = owCertReady() ? owCertForArtwork((int) $aid) : null;
            $out[$aid]['cert_no']  = $c ? (string) $c['cert_no'] : '';
            $out[$aid]['cert_own'] = $c && $c['owner_user_id'] !== null && (int) $c['owner_user_id'] === $uid;
        }
    } catch (Throwable $e) { error_log('collector collection certs: ' . $e->getMessage()); }
    $out = array_values($out);
    usort($out, fn($a, $b) => strcmp((string) $b['bought_at'], (string) $a['bought_at']));
    return $out;
}
/* When the collector confirms an order: the artwork's certificate is linked to their account (owner, 24 Nov 2026) */
function owCertLinkToCollector(array $order): void {
    if (($order['kind'] ?? '') !== 'artwork' || empty($order['ref_id']) || empty($order['buyer_user_id'])) return;
    try {
        require_once __DIR__ . '/cert_lib.php';
        if (!owCertReady() || !($c = owCertForArtwork((int) $order['ref_id']))) return;
        $name = trim((string) ($order['buyer_name'] ?? '')) ?: 'Collector';
        owSetCertificateOwner((int) $c['id'], $name, (int) $order['buyer_user_id']);
        /* the same name was already on it (added by hand): link the account too */
        getDB()->prepare('UPDATE certificates SET owner_user_id = :u WHERE id = :id AND owner_user_id IS NULL')
               ->execute([':u' => (int) $order['buyer_user_id'], ':id' => (int) $c['id']]);
    } catch (Throwable $e) { error_log('cert link to collector: ' . $e->getMessage()); }
}

/* ---------------- courses and museum tickets ---------------- */
/* Bookings made while signed in, and (only once the email is confirmed) bookings made as a guest with the same email */
function owCollectorBookingWhere(int $uid, string $email, bool $verified, array &$args): string {
    $args[':u'] = $uid;
    if ($verified && $email !== '') { $args[':e'] = strtolower(trim($email)); return '(r.user_id = :u OR (r.user_id IS NULL AND LOWER(r.student_email) = :e))'; }
    return 'r.user_id = :u';
}
function owCollectorBookings(int $uid, string $email, bool $verified): array {
    if (!owHasTable('course_registrations') || !owHasColumn('courses', 'bookable')) return [];
    $args = []; $who = owCollectorBookingWhere($uid, $email, $verified, $args);
    $st = getDB()->prepare("SELECT r.*, c.title_en, c.title_ar, c.start_date, c.end_date, c.course_type
                            FROM course_registrations r JOIN courses c ON c.id = r.course_id
                            WHERE $who AND c.bookable = 1 AND (r.status = 'confirmed' OR r.paid_at IS NOT NULL OR (r.status = 'pending' AND r.hold_until IS NULL) OR r.status = 'cancelled')
                            ORDER BY c.start_date DESC, r.id DESC LIMIT 100");
    $st->execute($args);
    return $st->fetchAll();
}
function owCollectorTickets(int $uid): array {
    if (!owHasTable('museum_tickets')) return [];
    $st = getDB()->prepare("SELECT t.*, p.name_en, p.name_ar FROM museum_tickets t JOIN art_places p ON p.id = t.place_id
                            WHERE t.user_id = :u AND t.status IN ('paid','used','cancelled') ORDER BY t.visit_date DESC LIMIT 100");
    $st->execute([':u' => $uid]);
    return $st->fetchAll();
}

/* ---------------- messages from Oweili (the group messages sent to this person) ---------------- */
function owCollectorMessages(int $uid): array {
    if (!owHasTable('sent_messages')) return [];
    $st = getDB()->prepare("SELECT id, kind, subject, body_en, body_ar, sent_at FROM sent_messages WHERE user_id = :u AND status = 'sent'
                            ORDER BY sent_at DESC, id DESC LIMIT 100");
    $st->execute([':u' => $uid]);
    return $st->fetchAll();
}
