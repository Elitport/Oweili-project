<?php
/* TEMPORARY (owner, 13 Nov 2026): read-only copy of the Art Education drafts (titles, text, cover) for proofreading. Locked by a secret key. Removed straight after. */
if (!hash_equals('328b2bf69ff9e4e0a51e8554019ff91da97284c846ef7da4706049e73917c571', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
echo json_encode(getDB()->query("SELECT id, slug, status, author_user_id, title_en, title_ar, body_en, body_ar, cover_image_path FROM articles WHERE type = 'art_education' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
