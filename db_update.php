<?php
/* =====================================================================
 *  db_update.php — TEMPORARY page that backs up the database and then
 *  runs database_update.sql safely. Remove it right after use.
 *  - Switches itself off at the time in OW_DBU_UNTIL (3 hours after it was made)
 *  - Only for an admin signed in on admin.php, or with the one-time key whose
 *    SHA-256 is OW_DBU_KEY_HASH (the key itself is never stored anywhere here)
 *  - POST only. Steps: status (read only), backup, update, verify
 *  - Never deletes or changes existing rows. Every step checks first and skips
 *    what already exists, so it is safe to run twice. Stops at the first failure.
 *  - The backup goes to .backups/ next to this file: hidden from visitors
 *    (.htaccess blocks every dot folder, plus its own deny file) and kept by
 *    deploys (.gitignore)
 * ===================================================================== */
const OW_DBU_UNTIL    = 1793401286;
const OW_DBU_KEY_HASH = 'd6fe5654aaf291131faa2735bcb94dcaa6e6793d3ceac8eb21c8f4854d16a494';

if (time() > OW_DBU_UNTIL) { http_response_code(404); exit; }
require_once 'config.php';
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

$adminHash = defined('ADMIN_HASH') ? (string) ADMIN_HASH : '';
$adminSig  = $adminHash !== '' ? hash('sha256', $adminHash) : '';
$isAdmin   = !empty($_SESSION['is_admin']) && $adminSig !== '' && hash_equals($adminSig, (string) ($_SESSION['admin_sig'] ?? ''));
$keyOk     = is_string($_POST['key'] ?? null) && hash_equals(OW_DBU_KEY_HASH, hash('sha256', (string) $_POST['key']));
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || (!$isAdmin && !$keyOk)) { http_response_code(404); exit; }

@set_time_limit(300);
$db   = getDB();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$name = (string) $db->query('SELECT DATABASE()')->fetchColumn();
$dir  = __DIR__ . '/.backups';
$say  = function (string $s) { echo $s, "\n"; };

function dbuTables(PDO $db): array {
    return $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
}
function dbuCounts(PDO $db): array {
    $out = [];
    foreach (dbuTables($db) as $t) $out[$t] = (int) $db->query('SELECT COUNT(*) FROM `' . str_replace('`', '``', $t) . '`')->fetchColumn();
    return $out;
}
function dbuColumn(PDO $db, string $table, string $col): ?array {
    $st = $db->prepare('SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $st->execute([$table, $col]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}
function dbuHasTable(PDO $db, string $t): bool {
    $st = $db->prepare('SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $st->execute([$t]); return (bool) $st->fetchColumn();
}
function dbuHasIndex(PDO $db, string $t, string $idx): bool {
    $st = $db->prepare('SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
    $st->execute([$t, $idx]); return (bool) $st->fetchColumn();
}

$step = (string) ($_POST['step'] ?? 'status');
$say("Database: $name   Server: " . $db->getAttribute(PDO::ATTR_SERVER_VERSION) . "   Step: $step   Time: " . gmdate('Y-m-d H:i:s') . ' UTC');

/* ---------------- status / verify: read only ---------------- */
if ($step === 'status' || $step === 'verify') {
    foreach (dbuCounts($db) as $t => $n) $say(sprintf('  %-28s %8d rows', $t, $n));
    $r = dbuColumn($db, 'users', 'role');      $say('users.role: ' . ($r['COLUMN_TYPE'] ?? 'missing') . ' default ' . var_export($r['COLUMN_DEFAULT'] ?? null, true));
    $say('users.id: '    . (dbuColumn($db, 'users', 'id')['COLUMN_TYPE'] ?? 'missing'));
    $say('artworks.id: ' . (dbuColumn($db, 'artworks', 'id')['COLUMN_TYPE'] ?? 'missing'));
    $say('artworks.is_draft: ' . (dbuColumn($db, 'artworks', 'is_draft')['COLUMN_TYPE'] ?? 'missing'));
    $roles = $db->query('SELECT role, COUNT(*) FROM users GROUP BY role')->fetchAll(PDO::FETCH_KEY_PAIR);
    $say('roles in use: ' . json_encode($roles));
    $say('backups: ' . (is_dir($dir) ? implode(', ', array_map(fn($f) => basename($f) . ' (' . filesize($f) . ' bytes)', glob($dir . '/*.sql.gz') ?: [])) : 'none yet'));
    exit;
}

/* ---------------- backup: full dump of every table ---------------- */
if ($step === 'backup') {
    if (!is_dir($dir) && !@mkdir($dir, 0700, true)) { $say('FAILED at backup: could not make the .backups folder'); exit; }
    @file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
    @file_put_contents($dir . '/index.html', '');
    $file = $dir . '/db-' . $name . '-' . gmdate('Ymd-His') . '.sql.gz';
    $gz = @gzopen($file, 'wb6');
    if (!$gz) { $say('FAILED at backup: could not write the backup file'); exit; }
    gzwrite($gz, "-- Oweili database backup of `$name`, " . gmdate('Y-m-d H:i:s') . " UTC\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
    $written = [];
    foreach (dbuTables($db) as $t) {
        $q = '`' . str_replace('`', '``', $t) . '`';
        $create = $db->query("SHOW CREATE TABLE $q")->fetch(PDO::FETCH_NUM)[1];
        gzwrite($gz, "DROP TABLE IF EXISTS $q;\n$create;\n");
        $n = 0;
        $rows = $db->query("SELECT * FROM $q", PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $vals = array_map(fn($v) => $v === null ? 'NULL' : $db->quote((string) $v), array_values($row));
            gzwrite($gz, "INSERT INTO $q VALUES (" . implode(',', $vals) . ");\n");
            $n++;
        }
        gzwrite($gz, "\n");
        $written[$t] = $n;
    }
    gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n-- end of backup\n");
    gzclose($gz);
    /* check: the file reads back, ends properly, and has every row */
    $check = dbuCounts($db);
    $txt   = (string) @file_get_contents('compress.zlib://' . $file);
    $okEnd = str_ends_with(rtrim($txt), '-- end of backup');
    $bad   = array_keys(array_filter($check, fn($n, $t) => ($written[$t] ?? -1) !== $n, ARRAY_FILTER_USE_BOTH));
    foreach ($written as $t => $n) $say(sprintf('  %-28s %8d rows saved', $t, $n));
    if (!$okEnd || $bad) { $say('FAILED at backup check: ' . (!$okEnd ? 'file is incomplete ' : '') . ($bad ? 'row counts differ in ' . implode(', ', $bad) : '')); exit; }
    @chmod($file, 0600);
    $say('BACKUP OK: .backups/' . basename($file) . ' (' . filesize($file) . ' bytes, ' . count($written) . ' tables, sha256 ' . hash_file('sha256', $file) . ')');
    exit;
}

/* ---------------- update: database_update.sql, step by step ---------------- */
if ($step === 'update') {
    $recent = array_filter(glob($dir . '/*.sql.gz') ?: [], fn($f) => filemtime($f) > time() - 3600);
    if (!$recent) { $say('STOPPED: no backup from the last hour. Run the backup step first.'); exit; }
    $before = dbuCounts($db);
    $userId = dbuColumn($db, 'users', 'id')['COLUMN_TYPE'] ?? '';
    $artId  = dbuColumn($db, 'artworks', 'id')['COLUMN_TYPE'] ?? '';
    if ($userId === '' || $artId === '') { $say('STOPPED: users or artworks table not found'); exit; }
    $T = ['{UID}' => strtoupper($userId), '{AID}' => strtoupper($artId)];
    $steps = [];

    /* 1. allow the 'institute' role (only adds a choice; existing roles stay) */
    $steps[] = ['users.role: add institute', function () use ($db) {
        $c = dbuColumn($db, 'users', 'role');
        if (!$c) return 'skipped: no role column';
        if ($c['DATA_TYPE'] !== 'enum') return 'skipped: role is ' . $c['COLUMN_TYPE'] . ' and already takes any value';
        preg_match_all("/'((?:[^'\\\\]|\\\\.|'')*)'/", $c['COLUMN_TYPE'], $m);
        $vals = $m[1];
        if (in_array('institute', $vals, true)) return 'skipped: already there';
        $want = array_values(array_unique(array_merge($vals, ['artist', 'collector', 'admin', 'sub_admin', 'institute'])));
        $list = implode(',', array_map(fn($v) => $db->quote($v), $want));
        $null = $c['IS_NULLABLE'] === 'YES' ? 'NULL' : 'NOT NULL';
        $def  = $c['COLUMN_DEFAULT'] === null ? ($null === 'NULL' ? ' DEFAULT NULL' : '') : ' DEFAULT ' . $db->quote(trim((string) $c['COLUMN_DEFAULT'], "'"));
        $db->exec("ALTER TABLE `users` MODIFY COLUMN `role` ENUM($list) $null$def");
        return 'done: ' . implode(', ', $want);
    }];
    /* 2. artworks.is_draft + its index */
    $steps[] = ['artworks.is_draft', function () use ($db) {
        if (dbuColumn($db, 'artworks', 'is_draft')) return 'skipped: already there';
        $db->exec('ALTER TABLE `artworks` ADD COLUMN `is_draft` TINYINT(1) NOT NULL DEFAULT 0');
        return 'done (every existing artwork is 0 = not a draft)';
    }];
    $steps[] = ['artworks index idx_artworks_draft', function () use ($db) {
        if (dbuHasIndex($db, 'artworks', 'idx_artworks_draft')) return 'skipped: already there';
        $db->exec('ALTER TABLE `artworks` ADD INDEX `idx_artworks_draft` (`artist_id`, `is_draft`)');
        return 'done';
    }];
    /* 3. the 8 new tables (CREATE only if missing) */
    $tables = [
      'artwork_stages' => "CREATE TABLE `artwork_stages` (
          `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `artwork_id` {AID} NOT NULL, `stage` TINYINT UNSIGNED NOT NULL,
          `photo_path` VARCHAR(500) NOT NULL DEFAULT '', `taken_at` DATETIME NULL DEFAULT NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`), UNIQUE KEY `uq_stage_per_artwork` (`artwork_id`, `stage`),
          CONSTRAINT `chk_stage_value` CHECK (`stage` IN (25, 50, 100))
          @FK, CONSTRAINT `fk_stages_artwork` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE@
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
      'artwork_colors' => "CREATE TABLE `artwork_colors` (
          `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `artwork_id` {AID} NOT NULL,
          `color_name_en` VARCHAR(60) NOT NULL DEFAULT '', `color_name_ar` VARCHAR(60) NOT NULL DEFAULT '', `hex_code` CHAR(7) NULL DEFAULT NULL,
          PRIMARY KEY (`id`), KEY `idx_colors_artwork` (`artwork_id`),
          CONSTRAINT `chk_hex_code` CHECK (`hex_code` IS NULL OR `hex_code` REGEXP '^#[0-9A-Fa-f]{6}$')
          @FK, CONSTRAINT `fk_colors_artwork` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE@
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
      'institutes' => "CREATE TABLE `institutes` (
          `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `user_id` {UID} NULL DEFAULT NULL,
          `name_en` VARCHAR(200) NOT NULL DEFAULT '', `name_ar` VARCHAR(200) NOT NULL DEFAULT '', `city_en` VARCHAR(120) NOT NULL DEFAULT '', `city_ar` VARCHAR(120) NOT NULL DEFAULT '',
          `description_en` TEXT NULL, `description_ar` TEXT NULL, `logo_path` VARCHAR(500) NOT NULL DEFAULT '',
          `contact_email` VARCHAR(190) NOT NULL DEFAULT '', `contact_phone` VARCHAR(30) NOT NULL DEFAULT '', `website_url` VARCHAR(500) NOT NULL DEFAULT '',
          `is_active` TINYINT(1) NOT NULL DEFAULT 1, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`), KEY `idx_institutes_user` (`user_id`), KEY `idx_institutes_active` (`is_active`)
          @FK, CONSTRAINT `fk_institutes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE@
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
      'courses' => "CREATE TABLE `courses` (
          `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `institute_id` BIGINT UNSIGNED NOT NULL,
          `title_en` VARCHAR(200) NOT NULL DEFAULT '', `title_ar` VARCHAR(200) NOT NULL DEFAULT '', `description_en` TEXT NULL, `description_ar` TEXT NULL,
          `start_date` DATE NULL DEFAULT NULL, `end_date` DATE NULL DEFAULT NULL, `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00, `currency` CHAR(3) NOT NULL DEFAULT 'SAR',
          `seats_total` INT UNSIGNED NOT NULL DEFAULT 0, `seats_remaining` INT UNSIGNED NOT NULL DEFAULT 0, `is_active` TINYINT(1) NOT NULL DEFAULT 1,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`), KEY `idx_courses_institute` (`institute_id`), KEY `idx_courses_start` (`start_date`),
          CONSTRAINT `chk_course_seats` CHECK (`seats_remaining` <= `seats_total`),
          CONSTRAINT `chk_course_dates` CHECK (`end_date` IS NULL OR `start_date` IS NULL OR `end_date` >= `start_date`)
          @FK, CONSTRAINT `fk_courses_institute` FOREIGN KEY (`institute_id`) REFERENCES `institutes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE@
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
      'course_registrations' => "CREATE TABLE `course_registrations` (
          `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `course_id` BIGINT UNSIGNED NOT NULL, `user_id` {UID} NOT NULL,
          `registered_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `status` ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending', `notes` TEXT NULL,
          PRIMARY KEY (`id`), UNIQUE KEY `uq_registration` (`course_id`, `user_id`), KEY `idx_registrations_user` (`user_id`)
          @FK, CONSTRAINT `fk_registrations_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
          CONSTRAINT `fk_registrations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE@
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
      'articles' => "CREATE TABLE `articles` (
          `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `type` ENUM('event','artist_spotlight','art_education') NOT NULL, `slug` VARCHAR(190) NOT NULL,
          `title_en` VARCHAR(255) NOT NULL DEFAULT '', `title_ar` VARCHAR(255) NOT NULL DEFAULT '', `body_en` LONGTEXT NULL, `body_ar` LONGTEXT NULL,
          `cover_image_path` VARCHAR(500) NOT NULL DEFAULT '', `author_user_id` {UID} NULL DEFAULT NULL,
          `status` ENUM('draft','published') NOT NULL DEFAULT 'draft', `published_at` DATETIME NULL DEFAULT NULL,
          `event_start_date` DATE NULL DEFAULT NULL, `event_end_date` DATE NULL DEFAULT NULL,
          `event_location_en` VARCHAR(255) NOT NULL DEFAULT '', `event_location_ar` VARCHAR(255) NOT NULL DEFAULT '',
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`), UNIQUE KEY `uq_articles_slug` (`slug`), KEY `idx_articles_type_status` (`type`, `status`), KEY `idx_articles_event_date` (`event_start_date`)
          @FK, CONSTRAINT `fk_articles_author` FOREIGN KEY (`author_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE@
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
      'article_images' => "CREATE TABLE `article_images` (
          `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `article_id` BIGINT UNSIGNED NOT NULL, `image_path` VARCHAR(500) NOT NULL DEFAULT '',
          `alt_en` VARCHAR(255) NOT NULL DEFAULT '', `alt_ar` VARCHAR(255) NOT NULL DEFAULT '', `sort_order` INT NOT NULL DEFAULT 0,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`), KEY `idx_article_images_article` (`article_id`, `sort_order`)
          @FK, CONSTRAINT `fk_article_images_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE@
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
      'photo_consents' => "CREATE TABLE `photo_consents` (
          `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `article_id` BIGINT UNSIGNED NULL DEFAULT NULL,
          `artist_name_en` VARCHAR(200) NOT NULL DEFAULT '', `artist_name_ar` VARCHAR(200) NOT NULL DEFAULT '', `artist_contact` VARCHAR(190) NOT NULL DEFAULT '',
          `consent_method` ENUM('email','whatsapp','signed_form','in_person','other') NOT NULL, `consent_given_at` DATETIME NOT NULL,
          `proof_path` VARCHAR(500) NOT NULL DEFAULT '', `notes` TEXT NULL, `recorded_by` {UID} NULL DEFAULT NULL,
          `withdrawn_at` DATETIME NULL DEFAULT NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`), KEY `idx_consents_article` (`article_id`)
          @FK, CONSTRAINT `fk_consents_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
          CONSTRAINT `fk_consents_recorder` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE@
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
    foreach ($tables as $t => $sql) {
        $steps[] = ["table $t", function () use ($db, $t, $sql, $T) {
            if (dbuHasTable($db, $t)) return 'skipped: already there';
            $sql = strtr($sql, $T);
            try {
                $db->exec(preg_replace('/@FK(.*?)@/s', '$1', $sql));
                return 'done';
            } catch (PDOException $e) {
                if (dbuHasTable($db, $t)) throw $e;
                /* the link to the parent table could not be made: make the table without it */
                $db->exec(preg_replace('/@FK(.*?)@/s', '', $sql));
                return 'done, without the link to its parent table (' . $e->getMessage() . ')';
            }
        }];
    }
    $n = 0;
    foreach ($steps as [$label, $fn]) {
        $n++;
        try { $say("Step $n  $label: " . $fn()); }
        catch (Throwable $e) {
            $say("FAILED at step $n  $label: " . $e->getMessage());
            $say('Nothing after this step was run. Existing data was not touched. The backup is in .backups/.');
            exit;
        }
    }
    $after = dbuCounts($db);
    $changed = [];
    foreach ($before as $t => $c) if (($after[$t] ?? -1) !== $c) $changed[] = "$t: $c -> " . ($after[$t] ?? 'gone');
    $say($changed ? 'WARNING row counts changed: ' . implode('; ', $changed) : 'Existing data unchanged: every existing table has the same number of rows (' . count($before) . ' tables)');
    $say('UPDATE OK');
    exit;
}
$say('Unknown step');
