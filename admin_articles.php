<?php
/* =====================================================================
 *  admin_articles.php — Oweili team tool for articles
 *  Tabs: Art Events · Artist Spotlights · Art Education
 *  Only sub-admins and admins (see owArticleStaff in article_lib.php).
 *  Photos → uploads/articles/ · consent proofs → uploads/consents/ (private)
 *  Consent records are never deleted — they can only be marked withdrawn.
 *  Tables: articles, article_images, photo_consents
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/article_lib.php';

$TYPES   = owArticleTypes();
$METHODS = owConsentMethods();
$staff   = owArticleStaff();

/* ---- Staff only ---- */
if (!$staff) {
    http_response_code(403);
    ?><!DOCTYPE html><html lang="en" dir="ltr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff only · Oweili</title><meta name="robots" content="noindex"><link rel="stylesheet" href="/assets/css/oweili.css">
    <script>try{if(localStorage.getItem('lang')==='ar'){document.documentElement.dir='rtl';document.documentElement.lang='ar';}}catch(e){}</script>
    <style>body.ow{background:#f4f5f7;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:16px;}.box{max-width:420px;background:#fff;border:1px solid var(--color-border);border-radius:16px;padding:32px;text-align:center;}.box h1{font-size:22px;font-weight:700;margin:0 0 8px;}.box a{display:inline-block;margin:8px 6px 0;font-weight:700;color:var(--color-accent);}</style></head>
    <body class="ow"><div class="box"><h1><?= owBi('Staff only', 'للفريق فقط') ?></h1>
    <p><?= owBi('This page is for the Oweili team. Please sign in with a team account.', 'هذه الصفحة لفريق أويلي. يرجى تسجيل الدخول بحساب الفريق.') ?></p>
    <a href="login.php"><?= owBi('Sign in', 'تسجيل الدخول') ?></a><a href="admin.php"><?= owBi('Admin login', 'دخول الإدارة') ?></a><a href="index.php"><?= owBi('Homepage', 'الصفحة الرئيسية') ?></a></div></body></html><?php
    exit;
}

$ready = owArticlesReady();
$db    = $ready ? getDB() : null;

/* ---- Open a consent proof file (private, staff only) ---- */
if ($ready && isset($_GET['proof'])) {
    $st = $db->prepare('SELECT proof_path FROM photo_consents WHERE id = :id');
    $st->execute([':id' => (int) $_GET['proof']]);
    $p = (string) $st->fetchColumn();
    if (!owSafeConsentPath($p) || !is_file(__DIR__ . '/' . $p)) { http_response_code(404); exit('Not found.'); }
    $types = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf'];
    header('Content-Type: ' . $types[pathinfo($p, PATHINFO_EXTENSION)]);
    header('Content-Disposition: inline; filename="consent-proof.' . pathinfo($p, PATHINFO_EXTENSION) . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    readfile(__DIR__ . '/' . $p);
    exit;
}

$tab = (string) ($_GET['tab'] ?? 'event');
if (!isset($TYPES[$tab])) $tab = 'event';

function flash(string $en, string $ar, bool $bad = false): void { $_SESSION['art_flash'] = [$en, $ar, $bad]; }

/* Load one article with its photos and consent records. */
function loadArticle(PDO $db, int $id): ?array {
    $st = $db->prepare('SELECT * FROM articles WHERE id = :id');
    $st->execute([':id' => $id]);
    $a = $st->fetch();
    if (!$a) return null;
    $st = $db->prepare('SELECT * FROM article_images WHERE article_id = :id ORDER BY sort_order, id');
    $st->execute([':id' => $id]);
    $a['images'] = $st->fetchAll();
    $st = $db->prepare('SELECT * FROM photo_consents WHERE article_id = :id ORDER BY consent_given_at DESC, id DESC');
    $st->execute([':id' => $id]);
    $a['consents'] = $st->fetchAll();
    return $a;
}

$errors = [];   // list of [en, ar]
$form   = null; // values to show again after a failed save

/* ---- Actions (all CSRF-checked) ---- */
if ($ready && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // A post bigger than the server allows arrives empty — explain instead of a token error.
    if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        flash('The files were too large to send together. Please send fewer or smaller photos (10MB each at most).',
              'الملفات كبيرة جداً لإرسالها معاً. يرجى إرسال صور أقل أو أصغر (10 ميغابايت لكل صورة كحد أقصى).', true);
        header('Location: admin_articles.php?tab=' . $tab);
        exit;
    }
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) { http_response_code(403); exit('Invalid CSRF token.'); }
    $action = (string) ($_POST['action'] ?? '');

    /* --- Delete an article (its photos too; consent records stay) --- */
    if ($action === 'delete') {
        $a = loadArticle($db, (int) ($_POST['id'] ?? 0));
        if ($a) {
            $db->prepare('DELETE FROM articles WHERE id = :id')->execute([':id' => $a['id']]);
            owDeleteArticleFile((string) $a['cover_image_path']);
            foreach ($a['images'] as $im) owDeleteArticleFile((string) $im['image_path']);
            flash('Article deleted.', 'تم حذف المقال.');
            $tab = $a['type'];
        }
        header('Location: admin_articles.php?tab=' . $tab);
        exit;
    }

    /* --- Withdraw a consent (never deleted; the date is recorded) --- */
    if ($action === 'withdraw') {
        $st = $db->prepare('SELECT * FROM photo_consents WHERE id = :id');
        $st->execute([':id' => (int) ($_POST['consent_id'] ?? 0)]);
        $c = $st->fetch();
        if ($c) {
            $db->prepare('UPDATE photo_consents SET withdrawn_at = NOW() WHERE id = :id AND withdrawn_at IS NULL')->execute([':id' => $c['id']]);
            $msg = ['Consent marked as withdrawn.', 'تم تسجيل سحب الموافقة.'];
            $a = $c['article_id'] ? loadArticle($db, (int) $c['article_id']) : null;
            if ($a) {
                $active = array_filter($a['consents'], fn($x) => $x['withdrawn_at'] === null);
                $hasPhotos = $a['cover_image_path'] !== '' || $a['images'];
                if ($a['status'] === 'published' && $hasPhotos && !$active) {
                    $db->prepare("UPDATE articles SET status = 'draft' WHERE id = :id")->execute([':id' => $a['id']]);
                    $msg = ['Consent withdrawn. The article had photos and no other consent, so it is now a draft and hidden from visitors.',
                            'تم سحب الموافقة. المقال يحتوي على صور ولا توجد موافقة أخرى، لذلك أصبح مسودة ومخفياً عن الزوار.'];
                }
                header('Location: admin_articles.php?edit=' . (int) $a['id']);
            } else {
                header('Location: admin_articles.php?tab=artist_spotlight');
            }
            flash($msg[0], $msg[1]);
            exit;
        }
        header('Location: admin_articles.php?tab=artist_spotlight');
        exit;
    }

    /* --- Create or update an article --- */
    if ($action === 'save') {
        $id       = (int) ($_POST['id'] ?? 0);
        $existing = $id > 0 ? loadArticle($db, $id) : null;
        if ($id > 0 && !$existing) { header('Location: admin_articles.php'); exit; }
        $type = $existing ? $existing['type'] : (string) ($_POST['type'] ?? '');
        if (!isset($TYPES[$type])) { header('Location: admin_articles.php'); exit; }
        $tab = $type;

        $f = [
            'title_en' => trim((string) ($_POST['title_en'] ?? '')),
            'title_ar' => trim((string) ($_POST['title_ar'] ?? '')),
            'body_en'  => trim((string) ($_POST['body_en'] ?? '')),
            'body_ar'  => trim((string) ($_POST['body_ar'] ?? '')),
            'status'   => ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft',
            'event_start_date'  => trim((string) ($_POST['event_start_date'] ?? '')),
            'event_end_date'    => trim((string) ($_POST['event_end_date'] ?? '')),
            'event_location_en' => trim((string) ($_POST['event_location_en'] ?? '')),
            'event_location_ar' => trim((string) ($_POST['event_location_ar'] ?? '')),
            'c_name_en'  => trim((string) ($_POST['c_name_en'] ?? '')),
            'c_name_ar'  => trim((string) ($_POST['c_name_ar'] ?? '')),
            'c_contact'  => trim((string) ($_POST['c_contact'] ?? '')),
            'c_method'   => (string) ($_POST['c_method'] ?? ''),
            'c_date'     => trim((string) ($_POST['c_date'] ?? '')),
            'remove_cover'  => !empty($_POST['remove_cover']),
            'remove_images' => array_map('intval', (array) ($_POST['remove_images'] ?? [])),
        ];

        if ($f['title_en'] === '') $errors[] = ['Please write the title in English.', 'يرجى كتابة العنوان بالإنجليزية.'];
        if ($f['title_ar'] === '') $errors[] = ['Please write the title in Arabic.', 'يرجى كتابة العنوان بالعربية.'];
        if (mb_strlen($f['title_en']) > 255 || mb_strlen($f['title_ar']) > 255) $errors[] = ['Titles can be 255 characters at most.', 'العنوان لا يتجاوز 255 حرفاً.'];
        if ($f['body_en'] === '') $errors[] = ['Please write the article text in English.', 'يرجى كتابة نص المقال بالإنجليزية.'];
        if ($f['body_ar'] === '') $errors[] = ['Please write the article text in Arabic.', 'يرجى كتابة نص المقال بالعربية.'];
        if (mb_strlen($f['body_en']) > 60000 || mb_strlen($f['body_ar']) > 60000) $errors[] = ['The article text is too long (60,000 characters at most).', 'نص المقال طويل جداً (60,000 حرف كحد أقصى).'];

        $start = $end = null;
        if ($type === 'event') {
            $start = owFormDate($f['event_start_date']);
            $end   = $f['event_end_date'] !== '' ? owFormDate($f['event_end_date']) : null;
            if (!$start) $errors[] = ['Please choose the event start date.', 'يرجى اختيار تاريخ بداية الملتقى.'];
            if ($f['event_end_date'] !== '' && !$end) $errors[] = ['The event end date is not a real date.', 'تاريخ نهاية الملتقى غير صحيح.'];
            if ($start && $end && $end < $start) $errors[] = ['The end date cannot be before the start date.', 'لا يمكن أن يكون تاريخ النهاية قبل تاريخ البداية.'];
            if ($f['event_location_en'] === '') $errors[] = ['Please write the event location in English.', 'يرجى كتابة مكان الملتقى بالإنجليزية.'];
            if ($f['event_location_ar'] === '') $errors[] = ['Please write the event location in Arabic.', 'يرجى كتابة مكان الملتقى بالعربية.'];
            if (mb_strlen($f['event_location_en']) > 255 || mb_strlen($f['event_location_ar']) > 255) $errors[] = ['The location can be 255 characters at most.', 'المكان لا يتجاوز 255 حرفاً.'];
        }

        /* Which photos will the article have after this save? */
        $coverFile  = $_FILES['cover'] ?? null;
        $newCover   = $coverFile && ($coverFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $imgFiles   = [];
        if (!empty($_FILES['images']['name']) && is_array($_FILES['images']['name'])) {
            foreach ($_FILES['images']['name'] as $i => $n) {
                if (($_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
                $imgFiles[] = ['name' => $n, 'type' => $_FILES['images']['type'][$i], 'tmp_name' => $_FILES['images']['tmp_name'][$i],
                               'error' => $_FILES['images']['error'][$i], 'size' => $_FILES['images']['size'][$i]];
            }
        }
        $keptImages = $existing ? array_values(array_filter($existing['images'], fn($im) => !in_array((int) $im['id'], $f['remove_images'], true))) : [];
        if (count($keptImages) + count($imgFiles) > OW_ARTICLE_MAX_IMAGES) {
            $errors[] = ['An article can have ' . OW_ARTICLE_MAX_IMAGES . ' extra photos at most.', 'يمكن أن يحتوي المقال على ' . OW_ARTICLE_MAX_IMAGES . ' صورة إضافية كحد أقصى.'];
        }
        $willHaveCover = $newCover || ($existing && $existing['cover_image_path'] !== '' && !$f['remove_cover']);
        $hasPhotos     = $willHaveCover || $keptImages || $imgFiles;

        /* New consent record (spotlights only): all fields, or none */
        $newConsent = false;
        $cDate = null;
        if ($type === 'artist_spotlight') {
            $proofFile = $_FILES['c_proof'] ?? null;
            $proofGiven = $proofFile && ($proofFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            $any = $f['c_name_en'] !== '' || $f['c_name_ar'] !== '' || $f['c_contact'] !== '' || $f['c_method'] !== '' || $f['c_date'] !== '' || $proofGiven;
            if ($any) {
                $newConsent = true;
                $cDate = owFormDate($f['c_date']);
                if ($f['c_name_en'] === '') $errors[] = ['Consent: please write the artist name in English.', 'الموافقة: يرجى كتابة اسم الفنان بالإنجليزية.'];
                if ($f['c_name_ar'] === '') $errors[] = ['Consent: please write the artist name in Arabic.', 'الموافقة: يرجى كتابة اسم الفنان بالعربية.'];
                if ($f['c_contact'] === '') $errors[] = ['Consent: please add the artist email or phone.', 'الموافقة: يرجى إضافة بريد الفنان أو هاتفه.'];
                elseif (!filter_var($f['c_contact'], FILTER_VALIDATE_EMAIL) && !preg_match('/^\+?[0-9 ()-]{7,20}$/', $f['c_contact'])) {
                    $errors[] = ['Consent: the contact must be an email address or a phone number.', 'الموافقة: يجب أن يكون التواصل بريداً إلكترونياً أو رقم هاتف.'];
                }
                if (!isset($METHODS[$f['c_method']])) $errors[] = ['Consent: please choose how the artist agreed.', 'الموافقة: يرجى اختيار طريقة الموافقة.'];
                if (!$cDate) $errors[] = ['Consent: please choose the date the artist agreed.', 'الموافقة: يرجى اختيار تاريخ الموافقة.'];
                elseif ($cDate > date('Y-m-d')) $errors[] = ['Consent: the date cannot be in the future.', 'الموافقة: لا يمكن أن يكون التاريخ في المستقبل.'];
                if (mb_strlen($f['c_name_en']) > 200 || mb_strlen($f['c_name_ar']) > 200 || mb_strlen($f['c_contact']) > 190) $errors[] = ['Consent: a field is too long.', 'الموافقة: أحد الحقول طويل جداً.'];
            }
            $activeConsent = $existing && array_filter($existing['consents'], fn($x) => $x['withdrawn_at'] === null);
            if ($f['status'] === 'published' && $hasPhotos && !$activeConsent && !$newConsent) {
                $errors[] = ["This spotlight has photos, so it can only be published after the artist's photo consent is recorded below. You can save it as a draft for now.",
                             'هذا المقال يحتوي على صور، لذلك لا يُنشر إلا بعد تسجيل موافقة الفنان على الصور في الأسفل. يمكنك حفظه كمسودة الآن.'];
            }
        }

        /* Save the photos only when everything else is right */
        $stored = [];   // new files, removed again if anything fails
        $coverPath = null; $newImagePaths = []; $proofPath = '';
        if (!$errors && $newCover) {
            $r = owStoreImage($coverFile, OW_ARTICLE_DIR);
            if ($r['ok']) { $coverPath = $r['path']; $stored[] = $r['path']; }
            else $errors[] = owUploadErrBi($r['err'], 'Cover image', 'صورة الغلاف');
        }
        if (!$errors) {
            foreach ($imgFiles as $k => $file) {
                $r = owStoreImage($file, OW_ARTICLE_DIR);
                if ($r['ok']) { $newImagePaths[] = $r['path']; $stored[] = $r['path']; }
                else { $errors[] = owUploadErrBi($r['err'], 'Extra photo ' . ($k + 1), 'الصورة الإضافية ' . ($k + 1)); break; }
            }
        }
        if (!$errors && $newConsent && $proofGiven) {
            $r = owStoreConsentProof($proofFile);
            if ($r['ok']) $proofPath = $r['path'];
            else $errors[] = $r['err'] === 'type'
                ? ['Consent proof: please choose a photo (JPG, PNG, WEBP) or a PDF.', 'إثبات الموافقة: يرجى اختيار صورة (JPG أو PNG أو WEBP) أو ملف PDF.']
                : owUploadErrBi($r['err'], 'Consent proof', 'إثبات الموافقة');
        }

        if (!$errors) {
            try {
                $db->beginTransaction();
                $everPublished = $existing && $existing['published_at'] !== null;
                $slug = ($existing && $everPublished) ? $existing['slug'] : owUniqueSlug($f['title_en'], $id);
                $publishedAt = $existing['published_at'] ?? null;
                if ($f['status'] === 'published' && !$publishedAt) $publishedAt = date('Y-m-d H:i:s');
                $cover = $coverPath ?? (($existing && !$f['remove_cover']) ? $existing['cover_image_path'] : '');
                $vals = [
                    ':slug' => $slug, ':te' => $f['title_en'], ':ta' => $f['title_ar'], ':be' => $f['body_en'], ':ba' => $f['body_ar'],
                    ':cover' => $cover, ':status' => $f['status'], ':pub' => $publishedAt,
                    ':sd' => $type === 'event' ? $start : null, ':ed' => $type === 'event' ? $end : null,
                    ':le' => $type === 'event' ? $f['event_location_en'] : '', ':la' => $type === 'event' ? $f['event_location_ar'] : '',
                ];
                if ($existing) {
                    $db->prepare('UPDATE articles SET slug=:slug, title_en=:te, title_ar=:ta, body_en=:be, body_ar=:ba, cover_image_path=:cover,
                                  status=:status, published_at=:pub, event_start_date=:sd, event_end_date=:ed, event_location_en=:le, event_location_ar=:la
                                  WHERE id=:id')->execute($vals + [':id' => $id]);
                } else {
                    $db->prepare('INSERT INTO articles (type, slug, title_en, title_ar, body_en, body_ar, cover_image_path, author_user_id, status, published_at,
                                  event_start_date, event_end_date, event_location_en, event_location_ar)
                                  VALUES (:type, :slug, :te, :ta, :be, :ba, :cover, :author, :status, :pub, :sd, :ed, :le, :la)')
                       ->execute($vals + [':type' => $type, ':author' => $staff['user_id']]);
                    $id = (int) $db->lastInsertId();
                }
                $removed = [];
                if ($existing) {
                    foreach ($existing['images'] as $im) {
                        if (in_array((int) $im['id'], $f['remove_images'], true)) {
                            $db->prepare('DELETE FROM article_images WHERE id = :i AND article_id = :a')->execute([':i' => $im['id'], ':a' => $id]);
                            $removed[] = $im['image_path'];
                        }
                    }
                    if ($existing['cover_image_path'] !== '' && $existing['cover_image_path'] !== $cover) $removed[] = $existing['cover_image_path'];
                }
                $mx = $db->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM article_images WHERE article_id = :a');
                $mx->execute([':a' => $id]);
                $order = (int) $mx->fetchColumn();
                $insImg = $db->prepare('INSERT INTO article_images (article_id, image_path, alt_en, alt_ar, sort_order) VALUES (:a, :p, :ae, :aa, :o)');
                foreach ($newImagePaths as $k => $p) {
                    $order++;
                    $insImg->execute([':a' => $id, ':p' => $p, ':ae' => mb_substr($f['title_en'] . ' — photo ' . $order, 0, 255),
                                      ':aa' => mb_substr($f['title_ar'] . ' — صورة ' . $order, 0, 255), ':o' => $order]);
                }
                if ($newConsent) {
                    $db->prepare('INSERT INTO photo_consents (article_id, artist_name_en, artist_name_ar, artist_contact, consent_method, consent_given_at, proof_path, recorded_by)
                                  VALUES (:a, :ne, :na, :c, :m, :d, :p, :by)')
                       ->execute([':a' => $id, ':ne' => $f['c_name_en'], ':na' => $f['c_name_ar'], ':c' => $f['c_contact'], ':m' => $f['c_method'],
                                  ':d' => $cDate . ' 00:00:00', ':p' => $proofPath, ':by' => $staff['user_id']]);
                }
                $db->commit();
                foreach ($removed as $p) owDeleteArticleFile((string) $p);
                flash($f['status'] === 'published' ? 'Saved and published.' : 'Saved as a draft.',
                      $f['status'] === 'published' ? 'تم الحفظ والنشر.' : 'تم الحفظ كمسودة.');
                header('Location: admin_articles.php?edit=' . $id);
                exit;
            } catch (Throwable $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log('admin_articles save: ' . $e->getMessage());
                $errors[] = ['Something went wrong while saving. Nothing was changed — please try again.', 'حدث خطأ أثناء الحفظ. لم يتغير شيء — يرجى المحاولة مرة أخرى.'];
            }
        }
        foreach ($stored as $p) owDeleteArticleFile($p);
        if ($proofPath !== '' && owSafeConsentPath($proofPath)) @unlink(__DIR__ . '/' . $proofPath);
        $form = $f + ['id' => $existing ? $id : 0, 'type' => $type];
    }
}

/* ---- What to show: list, new form or edit form ---- */
$mode = 'list';
$article = null;
if ($ready) {
    if ($form !== null) {
        $mode = 'form';
        $article = $form['id'] ? loadArticle($db, (int) $form['id']) : null;
        $tab = $form['type'];
    } elseif (isset($_GET['edit'])) {
        $article = loadArticle($db, (int) $_GET['edit']);
        if ($article) {
            $mode = 'form';
            $tab = $article['type'];
            $form = $article + ['c_name_en' => '', 'c_name_ar' => '', 'c_contact' => '', 'c_method' => '', 'c_date' => '', 'remove_cover' => false, 'remove_images' => []];
        }
    } elseif (isset($_GET['new'])) {
        $mode = 'form';
        $form = ['id' => 0, 'type' => $tab, 'title_en' => '', 'title_ar' => '', 'body_en' => '', 'body_ar' => '', 'status' => 'draft',
                 'event_start_date' => '', 'event_end_date' => '', 'event_location_en' => '', 'event_location_ar' => '',
                 'c_name_en' => '', 'c_name_ar' => '', 'c_contact' => '', 'c_method' => '', 'c_date' => '', 'remove_cover' => false, 'remove_images' => []];
    }
}
$list = []; $counts = array_fill_keys(array_keys($TYPES), 0);
if ($ready && $mode === 'list') {
    foreach ($db->query('SELECT type, COUNT(*) n FROM articles GROUP BY type') as $r) $counts[$r['type']] = (int) $r['n'];
    $st = $db->prepare('SELECT id, slug, title_en, title_ar, status, created_at FROM articles WHERE type = :t ORDER BY created_at DESC, id DESC');
    $st->execute([':t' => $tab]);
    $list = $st->fetchAll();
}
$flash = $_SESSION['art_flash'] ?? null;
unset($_SESSION['art_flash']);
$v = fn(string $k) => e((string) ($form[$k] ?? ''));
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Articles · Oweili Team</title>
<meta name="robots" content="noindex">
<script>try{if(localStorage.getItem('lang')==='ar'){document.documentElement.dir='rtl';document.documentElement.lang='ar';}}catch(e){}</script>
<link rel="stylesheet" href="/assets/css/oweili.css">
<style>
/* Articles tool — staff layout only; buttons, tabs, cards, inputs and pills come from oweili.css */
.wrap{max-width:1000px;margin:0 auto;padding:var(--space-lg);}
.top{display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:var(--space-sm);margin-bottom:var(--space-md);}
.top h1{font-size:var(--fs-section);margin:0;}
.top .sub{font-size:var(--fs-body);color:var(--color-body);margin:4px 0 0;}
.top-links{display:flex;flex-wrap:wrap;gap:var(--space-xs);align-items:center;}
.msg{padding:12px 16px;border-radius:var(--radius-input);margin-bottom:var(--space-sm);font-size:15px;line-height:1.6;color:var(--color-dark);}
.msg.ok{background:var(--color-tint-blue);border:1px solid rgba(0,102,255,0.2);}
.msg.bad{background:var(--color-tint-pink);border:1px solid rgba(255,0,85,0.3);}
.msg ul{margin:6px 0 0;padding-inline-start:20px;}
.tab .n{font-size:var(--fs-min);padding:0 8px;border-radius:var(--radius-pill);background:rgba(0,0,0,0.07);}
.tab.on .n{background:rgba(255,255,255,0.22);}
.list-head{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:var(--space-sm);margin-bottom:var(--space-sm);}
.list-head h2{font-size:var(--fs-subtitle);margin:0;}
.row{display:grid;grid-template-columns:1fr auto;gap:var(--space-sm);align-items:center;padding:var(--space-sm);border-top:1px solid var(--color-border);}
.row:nth-of-type(even){background:rgba(0,0,0,0.02);}
.row:first-of-type{border-top:0;}
.row-title{font-size:var(--fs-body);font-weight:700;color:var(--color-dark);}
.row-meta{display:flex;flex-wrap:wrap;gap:var(--space-xs);align-items:center;font-size:var(--fs-label);color:var(--color-body);margin-top:4px;}
.pill.pub{background:rgba(0,102,255,0.1);color:var(--color-accent-dark);}
.pill.draft{background:rgba(0,0,0,0.07);color:var(--color-dark);}
.pill.wd{background:var(--color-tint-pink);color:var(--color-dark);border:1px solid rgba(255,0,85,0.3);}
.row-actions{display:flex;gap:var(--space-xs);flex-wrap:wrap;justify-content:flex-end;}
.row-actions form{margin:0;}
.empty{text-align:center;padding:var(--space-lg) var(--space-sm);}
fieldset{border:0;padding:0;margin:0 0 var(--space-md);}
legend{font-size:var(--fs-subtitle);font-weight:700;color:var(--color-dark);margin-bottom:var(--space-sm);padding:0;}
.lbl{font-size:var(--fs-label);font-weight:700;color:var(--color-dark);}
.req{color:var(--color-dark);font-weight:700;}
.field textarea{min-height:260px;}
.radios{display:flex;gap:var(--space-xs);flex-wrap:wrap;}
.radio{display:flex;align-items:center;gap:var(--space-xs);min-height:var(--input-h);padding:0 var(--space-sm);border:1px solid var(--color-border-strong);border-radius:var(--radius-input);background:#fff;cursor:pointer;font-size:15px;font-weight:600;color:var(--color-dark);}
.radio:has(input:checked){border-color:var(--color-accent);background:var(--color-tint-blue);}
.thumbs{display:flex;flex-wrap:wrap;gap:var(--space-xs);margin-top:6px;}
.thumb{width:120px;font-size:var(--fs-label);}
.thumb img{width:120px;height:90px;object-fit:cover;border-radius:var(--radius-input);display:block;background:#f4f5f7;margin-bottom:4px;}
.thumb label{display:flex;gap:6px;align-items:center;cursor:pointer;}
.cover-now{display:flex;gap:var(--space-sm);align-items:center;flex-wrap:wrap;}
.cover-now img{width:180px;height:110px;object-fit:cover;border-radius:var(--radius-input);background:#f4f5f7;}
.box-blue{background:var(--color-tint-blue);border:1px solid rgba(0,102,255,0.18);border-radius:var(--radius-card);padding:var(--space-md);}
.consent{padding:var(--space-sm) 0;border-top:1px solid var(--color-border);font-size:15px;line-height:1.7;color:var(--color-body);}
.consent:first-child{border-top:0;}
.consent b{color:var(--color-dark);}
.consent-actions{display:flex;gap:var(--space-xs);flex-wrap:wrap;margin-top:6px;}
.form-foot{display:flex;flex-wrap:wrap;gap:var(--space-sm);align-items:center;justify-content:space-between;border-top:1px solid var(--color-border);padding-top:var(--space-md);}
.slug{font-size:var(--fs-label);color:var(--color-body);word-break:break-all;}
@media (max-width:768px){
  .wrap{padding:var(--space-sm);}
  .card{padding:var(--space-sm);}
  .row{grid-template-columns:1fr;}
  .row-actions{justify-content:flex-start;}
}
</style>
</head>
<body class="staff-page">
<div class="wrap">
  <div class="top">
    <div>
      <h1><?= owBi('Articles', 'المقالات') ?></h1>
      <p class="sub"><?= owBi('Events, artist spotlights and art education — written by the Oweili team.', 'الملتقيات وأضواء الفنانين والتثقيف الفني — بقلم فريق أويلي.') ?></p>
    </div>
    <div class="top-links">
      <button type="button" class="btn btn-ghost btn-sm" id="lb" onclick="tgl()">العربية</button>
      <?php if ($staff['user_id'] === null || (currentUser()['role'] ?? '') === 'admin'): ?>
        <a class="btn btn-ghost btn-sm" href="admin.php"><?= owBi('Admin panel', 'لوحة الإدارة') ?></a>
      <?php else: ?>
        <a class="btn btn-ghost btn-sm" href="subadmin_dashboard.php"><?= owBi('Review panel', 'لوحة المراجعة') ?></a>
      <?php endif; ?>
      <a class="btn btn-ghost btn-sm" href="index.php"><?= owBi('View site', 'عرض الموقع') ?></a>
    </div>
  </div>

  <?php if (!$ready): ?>
    <div class="card empty">
      <?= owBi('The articles system is being set up. It will open as soon as the database update (database_update.sql) is run in phpMyAdmin.',
               'نظام المقالات قيد الإعداد. سيعمل فور تشغيل تحديث قاعدة البيانات (database_update.sql) في phpMyAdmin.') ?>
    </div>
  <?php else: ?>

  <?php if ($flash): ?>
    <div class="msg <?= $flash[2] ? 'bad' : 'ok' ?>" role="status"><?= owBi(($flash[2] ? '⚠ ' : '✓ ') . $flash[0], ($flash[2] ? '⚠ ' : '✓ ') . $flash[1]) ?></div>
  <?php endif; ?>

  <nav class="tabs" aria-label="Article types">
    <?php foreach ($TYPES as $k => [$en, $ar]): ?>
      <a class="tab<?= $k === $tab ? ' on' : '' ?>" href="admin_articles.php?tab=<?= e($k) ?>"<?= $k === $tab ? ' aria-current="page"' : '' ?>>
        <?= owBi($en, $ar) ?><?php if ($mode === 'list'): ?><span class="n"><?= $counts[$k] ?></span><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <?php if ($mode === 'list'): ?>
    <div class="card">
      <div class="list-head">
        <h2><?= owBi($TYPES[$tab][0], $TYPES[$tab][1]) ?></h2>
        <a class="btn btn-primary" href="admin_articles.php?new=1&amp;tab=<?= e($tab) ?>"><?= owBi('+ New Article', '+ مقال جديد') ?></a>
      </div>
      <?php if (!$list): ?>
        <div class="empty"><?= owBi('No articles here yet. Press "New Article" to write the first one.', 'لا توجد مقالات هنا بعد. اضغط «مقال جديد» لكتابة أول مقال.') ?></div>
      <?php endif; ?>
      <?php foreach ($list as $r): [$dEn, $dAr] = owDateBi($r['created_at']); ?>
        <div class="row">
          <div>
            <div class="row-title"><?= owBi($r['title_en'], $r['title_ar']) ?></div>
            <div class="row-meta">
              <?= $r['status'] === 'published' ? '<span class="pill pub">' . owBi('Published', 'منشور') . '</span>' : '<span class="pill draft">' . owBi('Draft', 'مسودة') . '</span>' ?>
              <span><?= owBi('Created ' . $dEn, 'أُنشئ في ' . $dAr) ?></span>
            </div>
          </div>
          <div class="row-actions">
            <?php if ($r['status'] === 'published'): ?>
              <a class="btn btn-ghost btn-sm" href="article.php?slug=<?= e(rawurlencode($r['slug'])) ?>" target="_blank" rel="noopener"><?= owBi('View', 'عرض') ?></a>
            <?php endif; ?>
            <a class="btn btn-secondary btn-sm" href="admin_articles.php?edit=<?= (int) $r['id'] ?>"><?= owBi('Edit', 'تعديل') ?></a>
            <form method="post" action="admin_articles.php?tab=<?= e($tab) ?>" class="js-confirm"
                  data-confirm-en="Delete “<?= e($r['title_en']) ?>”? Its photos will be deleted too. This cannot be undone."
                  data-confirm-ar="حذف «<?= e($r['title_ar']) ?>»؟ ستُحذف صوره أيضاً. لا يمكن التراجع عن ذلك.">
              <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit"><?= owBi('Delete', 'حذف') ?></button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  <?php else: /* ---------- NEW / EDIT FORM ---------- */
    $type = $form['type'];
    $isEdit = !empty($form['id']);
  ?>
    <?php if ($errors): ?>
      <div class="msg bad" role="alert">
        <?= owBi('⚠ Please fix these and save again:', '⚠ يرجى تصحيح ما يلي ثم الحفظ مرة أخرى:') ?>
        <ul><?php foreach ($errors as [$en, $ar]): ?><li><?= owBi($en, $ar) ?></li><?php endforeach; ?></ul>
        <?php if (!empty($_FILES)): ?><div class="hint"><?= owBi('Photos you chose were not saved — please choose them again.', 'الصور التي اخترتها لم تُحفظ — يرجى اختيارها مرة أخرى.') ?></div><?php endif; ?>
      </div>
    <?php endif; ?>

    <form class="card" method="post" action="admin_articles.php" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int) $form['id'] ?>">
      <input type="hidden" name="type" value="<?= e($type) ?>">

      <div class="list-head">
        <h2><?= $isEdit ? owBi('Edit article — ' . $TYPES[$type][0], 'تعديل مقال — ' . $TYPES[$type][1]) : owBi('New article — ' . $TYPES[$type][0], 'مقال جديد — ' . $TYPES[$type][1]) ?></h2>
        <a class="btn btn-ghost btn-sm" href="admin_articles.php?tab=<?= e($type) ?>"><?= owBi('← Back to list', '→ العودة للقائمة') ?></a>
      </div>
      <?php if ($article): ?>
        <p class="slug"><?= owBi('Web address:', 'عنوان الصفحة:') ?> article.php?slug=<?= e($article['slug']) ?>
          <?= $article['published_at'] ? '' : owBi(' (made from the English title; it stays fixed once published)', ' (من العنوان الإنجليزي؛ ويثبت بعد النشر)') ?></p>
      <?php else: ?>
        <p class="slug"><?= owBi('The web address is made from the English title automatically.', 'يُنشأ عنوان الصفحة من العنوان الإنجليزي تلقائياً.') ?></p>
      <?php endif; ?>

      <fieldset>
        <legend><?= owBi('Title and text', 'العنوان والنص') ?></legend>
        <div class="grid2">
          <div class="field"><label for="title_en"><?= owBi('Title in English', 'العنوان بالإنجليزية') ?> <span class="req">*</span></label>
            <input type="text" id="title_en" name="title_en" dir="ltr" lang="en" maxlength="255" required value="<?= $v('title_en') ?>"></div>
          <div class="field"><label for="title_ar"><?= owBi('Title in Arabic', 'العنوان بالعربية') ?> <span class="req">*</span></label>
            <input type="text" id="title_ar" name="title_ar" dir="rtl" lang="ar" maxlength="255" required value="<?= $v('title_ar') ?>"></div>
        </div>
        <div class="field"><label for="body_en"><?= owBi('Text in English', 'النص بالإنجليزية') ?> <span class="req">*</span></label>
          <textarea id="body_en" name="body_en" dir="ltr" lang="en" required><?= $v('body_en') ?></textarea>
          <span class="hint"><?= owBi('Leave an empty line between paragraphs.', 'اترك سطراً فارغاً بين الفقرات.') ?></span></div>
        <div class="field"><label for="body_ar"><?= owBi('Text in Arabic', 'النص بالعربية') ?> <span class="req">*</span></label>
          <textarea id="body_ar" name="body_ar" dir="rtl" lang="ar" required><?= $v('body_ar') ?></textarea></div>
      </fieldset>

      <?php if ($type === 'event'): ?>
      <fieldset>
        <legend><?= owBi('Event details', 'تفاصيل الملتقى') ?></legend>
        <div class="grid2">
          <div class="field"><label for="event_start_date"><?= owBi('Start date', 'تاريخ البداية') ?> <span class="req">*</span></label>
            <input type="date" id="event_start_date" name="event_start_date" required value="<?= $v('event_start_date') ?>"></div>
          <div class="field"><label for="event_end_date"><?= owBi('End date (optional)', 'تاريخ النهاية (اختياري)') ?></label>
            <input type="date" id="event_end_date" name="event_end_date" value="<?= $v('event_end_date') ?>"></div>
          <div class="field"><label for="event_location_en"><?= owBi('Location in English', 'المكان بالإنجليزية') ?> <span class="req">*</span></label>
            <input type="text" id="event_location_en" name="event_location_en" dir="ltr" lang="en" maxlength="255" required value="<?= $v('event_location_en') ?>"></div>
          <div class="field"><label for="event_location_ar"><?= owBi('Location in Arabic', 'المكان بالعربية') ?> <span class="req">*</span></label>
            <input type="text" id="event_location_ar" name="event_location_ar" dir="rtl" lang="ar" maxlength="255" required value="<?= $v('event_location_ar') ?>"></div>
        </div>
      </fieldset>
      <?php endif; ?>

      <fieldset>
        <legend><?= owBi('Photos', 'الصور') ?></legend>
        <?php if ($type === 'artist_spotlight'): ?>
          <p class="hint" style="margin:0 0 12px;"><?= owBi('Photos of a local artist can only be published after their consent is recorded below.', 'لا تُنشر صور فنان محلي إلا بعد تسجيل موافقته في الأسفل.') ?></p>
        <?php endif; ?>
        <div class="field">
          <span class="lbl" id="cover-lbl"><?= owBi('Cover image', 'صورة الغلاف') ?></span>
          <?php if ($article && $article['cover_image_path'] !== '' && owSafeArticlePath($article['cover_image_path'])): ?>
            <div class="cover-now">
              <img src="<?= e($article['cover_image_path']) ?>" alt="<?= e($article['title_en'] . ' · ' . $article['title_ar']) ?>">
              <label class="radio"><input type="checkbox" name="remove_cover" value="1"<?= !empty($form['remove_cover']) ? ' checked' : '' ?>> <?= owBi('Remove cover', 'إزالة الغلاف') ?></label>
            </div>
            <span class="hint"><?= owBi('Choose a new photo to replace it.', 'اختر صورة جديدة لاستبدالها.') ?></span>
          <?php endif; ?>
          <input type="file" name="cover" accept="image/jpeg,image/png,image/webp" aria-labelledby="cover-lbl">
          <span class="hint"><?= owBi('JPG, PNG or WEBP, up to 10MB. A wide photo looks best.', 'JPG أو PNG أو WEBP حتى 10 ميغابايت. الصورة العريضة أجمل.') ?></span>
        </div>
        <div class="field">
          <span class="lbl" id="imgs-lbl"><?= owBi('Extra photos (shown under the text)', 'صور إضافية (تظهر تحت النص)') ?></span>
          <?php if ($article && $article['images']): ?>
            <div class="thumbs">
              <?php foreach ($article['images'] as $im): if (!owSafeArticlePath($im['image_path'])) continue; ?>
                <div class="thumb"><img src="<?= e($im['image_path']) ?>" alt="<?= e($im['alt_en'] . ' · ' . $im['alt_ar']) ?>">
                  <label><input type="checkbox" name="remove_images[]" value="<?= (int) $im['id'] ?>"<?= in_array((int) $im['id'], $form['remove_images'] ?? [], true) ? ' checked' : '' ?>> <?= owBi('Remove', 'إزالة') ?></label></div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" aria-labelledby="imgs-lbl">
          <span class="hint"><?= owBi('You can choose several at once (up to ' . OW_ARTICLE_MAX_IMAGES . ' in total).', 'يمكنك اختيار عدة صور معاً (حتى ' . OW_ARTICLE_MAX_IMAGES . ' صورة).') ?></span>
        </div>
      </fieldset>

      <?php if ($type === 'artist_spotlight'): ?>
      <fieldset class="box-blue">
        <legend><?= owBi('Photo consent', 'موافقة الفنان على الصور') ?></legend>
        <?php if ($article && $article['consents']): ?>
          <div style="margin-bottom:14px;">
          <?php foreach ($article['consents'] as $c): [$cdEn, $cdAr] = owDateBi($c['consent_given_at']); $m = $METHODS[$c['consent_method']] ?? ['Other', 'أخرى']; ?>
            <div class="consent">
              <b><?= owBi($c['artist_name_en'], $c['artist_name_ar']) ?></b> · <?= e($c['artist_contact']) ?><br>
              <?= owBi('Agreed by ' . $m[0] . ' on ' . $cdEn, 'وافق عبر ' . $m[1] . ' في ' . $cdAr) ?>
              <?php if ($c['withdrawn_at']): [$wEn, $wAr] = owDateBi($c['withdrawn_at']); ?>
                · <span class="pill wd"><?= owBi('Withdrawn ' . $wEn, 'سُحبت في ' . $wAr) ?></span>
              <?php else: ?>
                · <span class="pill pub"><?= owBi('Active', 'سارية') ?></span>
              <?php endif; ?>
              <div class="consent-actions">
                <?php if ($c['proof_path'] !== ''): ?>
                  <a class="btn btn-ghost btn-sm" href="admin_articles.php?proof=<?= (int) $c['id'] ?>" target="_blank" rel="noopener"><?= owBi('Open proof', 'فتح الإثبات') ?></a>
                <?php else: ?>
                  <span class="hint"><?= owBi('No proof file uploaded', 'لم يُرفع ملف إثبات') ?></span>
                <?php endif; ?>
                <?php if (!$c['withdrawn_at']): ?>
                  <button class="btn btn-danger btn-sm" type="submit" form="wd-<?= (int) $c['id'] ?>"><?= owBi('Mark as withdrawn', 'تسجيل سحب الموافقة') ?></button>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
          </div>
          <p class="hint" style="margin:0 0 10px;"><?= owBi('Consent records are kept as a legal record and cannot be deleted. Add another one below if needed.', 'سجلات الموافقة تُحفظ كسجل قانوني ولا يمكن حذفها. أضف سجلاً آخر في الأسفل عند الحاجة.') ?></p>
        <?php else: ?>
          <p class="hint" style="margin:0 0 12px;"><?= owBi('Record how the artist agreed to have their photos published. This record is kept and cannot be deleted.', 'سجّل كيف وافق الفنان على نشر صوره. يُحفظ هذا السجل ولا يمكن حذفه.') ?></p>
        <?php endif; ?>
        <div class="grid2">
          <div class="field"><label for="c_name_en"><?= owBi('Artist name in English', 'اسم الفنان بالإنجليزية') ?></label>
            <input type="text" id="c_name_en" name="c_name_en" dir="ltr" lang="en" maxlength="200" value="<?= $v('c_name_en') ?>"></div>
          <div class="field"><label for="c_name_ar"><?= owBi('Artist name in Arabic', 'اسم الفنان بالعربية') ?></label>
            <input type="text" id="c_name_ar" name="c_name_ar" dir="rtl" lang="ar" maxlength="200" value="<?= $v('c_name_ar') ?>"></div>
          <div class="field"><label for="c_contact"><?= owBi('Artist contact (email or phone)', 'تواصل الفنان (بريد أو هاتف)') ?></label>
            <input type="text" id="c_contact" name="c_contact" dir="ltr" maxlength="190" value="<?= $v('c_contact') ?>"></div>
          <div class="field"><label for="c_method"><?= owBi('How the artist agreed', 'طريقة الموافقة') ?></label>
            <select id="c_method" name="c_method">
              <option value="">— · —</option>
              <?php foreach ($METHODS as $k => [$en, $ar]): ?>
                <option value="<?= e($k) ?>"<?= ($form['c_method'] ?? '') === $k ? ' selected' : '' ?>><?= e($en . ' · ' . $ar) ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="field"><label for="c_date"><?= owBi('Date consent was given', 'تاريخ الموافقة') ?></label>
            <input type="date" id="c_date" name="c_date" max="<?= date('Y-m-d') ?>" value="<?= $v('c_date') ?>"></div>
          <div class="field"><span class="lbl" id="proof-lbl"><?= owBi('Proof (screenshot or signed form)', 'الإثبات (لقطة شاشة أو نموذج موقّع)') ?></span>
            <input type="file" name="c_proof" accept="image/jpeg,image/png,image/webp,application/pdf" aria-labelledby="proof-lbl">
            <span class="hint"><?= owBi('Photo or PDF, up to 10MB. Kept private — only the team can open it.', 'صورة أو PDF حتى 10 ميغابايت. يبقى خاصاً — لا يفتحه إلا الفريق.') ?></span></div>
        </div>
      </fieldset>
      <?php endif; ?>

      <fieldset>
        <legend><?= owBi('Status', 'الحالة') ?></legend>
        <div class="radios">
          <label class="radio"><input type="radio" name="status" value="draft"<?= ($form['status'] ?? 'draft') !== 'published' ? ' checked' : '' ?>> <?= owBi('Draft — only the team sees it', 'مسودة — يراها الفريق فقط') ?></label>
          <label class="radio"><input type="radio" name="status" value="published"<?= ($form['status'] ?? '') === 'published' ? ' checked' : '' ?>> <?= owBi('Published — visible to everyone', 'منشور — يراه الجميع') ?></label>
        </div>
      </fieldset>

      <div class="form-foot">
        <a class="btn btn-ghost" href="admin_articles.php?tab=<?= e($type) ?>"><?= owBi('Cancel', 'إلغاء') ?></a>
        <button class="btn btn-primary" type="submit"><?= owBi('Save Article', 'حفظ المقال') ?></button>
      </div>
    </form>

    <?php /* Withdraw forms live outside the main form (forms cannot be nested) */
    if ($article): foreach ($article['consents'] as $c): if ($c['withdrawn_at']) continue; ?>
      <form id="wd-<?= (int) $c['id'] ?>" method="post" action="admin_articles.php" class="js-confirm" hidden
            data-confirm-en="Mark this consent as withdrawn? The record is kept, and an article with photos and no other consent goes back to draft."
            data-confirm-ar="تسجيل سحب هذه الموافقة؟ يبقى السجل محفوظاً، والمقال الذي يحتوي على صور دون موافقة أخرى يعود مسودة.">
        <?= csrfField() ?><input type="hidden" name="action" value="withdraw"><input type="hidden" name="consent_id" value="<?= (int) $c['id'] ?>">
      </form>
    <?php endforeach; endif; ?>
  <?php endif; ?>
  <?php endif; ?>
</div>
<script>
/* Language button: flip direction; bi-en / bi-ar texts follow it. */
function tgl(){
  var h = document.documentElement, ar = h.getAttribute('dir') !== 'rtl';
  h.setAttribute('dir', ar ? 'rtl' : 'ltr'); h.setAttribute('lang', ar ? 'ar' : 'en');
  try { localStorage.setItem('lang', ar ? 'ar' : 'en'); } catch (e) {}
  setLb();
}
function setLb(){ document.getElementById('lb').textContent = document.documentElement.getAttribute('dir') === 'rtl' ? 'English' : 'العربية'; }
setLb();
/* Ask before deleting or withdrawing, in the current language */
document.querySelectorAll('form.js-confirm').forEach(function(f){
  f.addEventListener('submit', function(ev){
    var msg = document.documentElement.getAttribute('dir') === 'rtl' ? f.dataset.confirmAr : f.dataset.confirmEn;
    if (!window.confirm(msg)) ev.preventDefault();
  });
});
</script>
</body>
</html>
