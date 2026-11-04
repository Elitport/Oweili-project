<?php
/* =====================================================================
 *  my_articles.php — "My articles" («مقالاتي»): an approved artist writes
 *  Art Events, Artist Spotlights and Art Education articles from My Studio.
 *  Nothing goes public without the admin: "Send for approval" keeps the
 *  article a draft and puts it in admin.php → "Waiting for approval"
 *  (pending_changes, target 'article', the same path as a sub-admin's
 *  request). The admin gets an email alert; the artist gets an email with
 *  the result (approval_lib.php). A published article is locked for the
 *  artist (only the team changes it), so live text never changes unseen.
 *  Spotlights with photos also need a photo consent record (same rule as
 *  the team tool), written here by the artist and checked by the admin.
 *    my_articles.php                  list of my articles
 *    my_articles.php?new=event        new article of that type
 *    my_articles.php?edit=12          edit one of my drafts
 *  Tables: articles, article_images, photo_consents, pending_changes,
 *          activity_log, users
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/article_lib.php';
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/approval_lib.php';

if (!isLoggedIn()) { header('Location: login.php?next=my_articles.php'); exit; }
$me = currentUser();
if (!$me) { header('Location: login.php'); exit; }
if (($me['role'] ?? '') !== 'artist') { header('Location: ' . dashboardUrl($me['role'] ?? '')); exit; }
$uid      = (int) $me['id'];
$approved = (int) ($me['is_approved'] ?? 0) === 1 && empty($me['is_banned']);
$TYPES    = owArticleTypes();
$METHODS  = owConsentMethods();
const OW_MY_ARTICLE_MAX  = 40;   // articles one artist can keep
const OW_MY_ARTICLE_IMGS = 6;    // extra photos per artist article
$ready = owArticlesReady() && owApprovalsReady();
$db = $ready ? getDB() : null;

function myFlash(string $en, string $ar, bool $bad = false): void { $_SESSION['my_art_flash'] = [$en, $ar, $bad]; }

/* One of MY articles, with its photos and consents */
function myArticle(PDO $db, int $id, int $uid): ?array {
    $st = $db->prepare('SELECT * FROM articles WHERE id = :id AND author_user_id = :u');
    $st->execute([':id' => $id, ':u' => $uid]);
    $a = $st->fetch();
    if (!$a) return null;
    $st = $db->prepare('SELECT * FROM article_images WHERE article_id = :id ORDER BY sort_order, id');
    $st->execute([':id' => $id]); $a['images'] = $st->fetchAll();
    $st = $db->prepare('SELECT * FROM photo_consents WHERE article_id = :id ORDER BY consent_given_at DESC, id DESC');
    $st->execute([':id' => $id]); $a['consents'] = $st->fetchAll();
    return $a;
}

$errors = [];
$form = null;

if ($ready && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        myFlash('The photos were too large to send together. Please send fewer or smaller photos (10MB each at most).',
                'الصور كبيرة جداً لإرسالها معاً. يرجى إرسال صور أقل أو أصغر (10 ميغابايت لكل صورة كحد أقصى).', true);
        header('Location: my_articles.php'); exit;
    }
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        myFlash('This page was open for a long time, so nothing was saved. Please try again.', 'كانت هذه الصفحة مفتوحة لوقت طويل، لذلك لم يُحفظ شيء. يرجى المحاولة مرة أخرى.', true);
        header('Location: my_articles.php'); exit;
    }
    if (!$approved) { header('Location: my_articles.php'); exit; }
    $action = (string) ($_POST['action'] ?? '');

    /* --- Delete one of my articles that is not published --- */
    if ($action === 'delete') {
        $a = myArticle($db, (int) ($_POST['id'] ?? 0), $uid);
        if ($a && $a['status'] !== 'published') {
            $db->prepare("UPDATE pending_changes SET status = 'replaced', decided_at = NOW() WHERE target_type = 'article' AND target_id = :id AND status = 'pending'")->execute([':id' => $a['id']]);
            $db->prepare('DELETE FROM articles WHERE id = :id AND author_user_id = :u')->execute([':id' => $a['id'], ':u' => $uid]);
            owDeleteArticleFile((string) $a['cover_image_path']);
            foreach ($a['images'] as $im) owDeleteArticleFile((string) $im['image_path']);
            owLog('article_delete', 'article', (int) $a['id'], '', ['type' => 'artist', 'id' => $uid]);
            myFlash('The article is deleted.', 'تم حذف المقال.');
        }
        header('Location: my_articles.php'); exit;
    }

    /* --- Save (draft) or save and send for approval --- */
    if ($action === 'save' || $action === 'send') {
        $id = (int) ($_POST['id'] ?? 0);
        $existing = $id > 0 ? myArticle($db, $id, $uid) : null;
        if ($id > 0 && (!$existing || $existing['status'] === 'published')) { header('Location: my_articles.php'); exit; }
        $type = $existing ? $existing['type'] : (string) ($_POST['type'] ?? '');
        if (!isset($TYPES[$type])) { header('Location: my_articles.php'); exit; }
        if (!$existing) {
            $n = $db->prepare('SELECT COUNT(*) FROM articles WHERE author_user_id = :u'); $n->execute([':u' => $uid]);
            if ((int) $n->fetchColumn() >= OW_MY_ARTICLE_MAX) {
                myFlash('You have reached ' . OW_MY_ARTICLE_MAX . ' articles. Please delete a draft first.', 'وصلت إلى ' . OW_MY_ARTICLE_MAX . ' مقالاً. يرجى حذف مسودة أولاً.', true);
                header('Location: my_articles.php'); exit;
            }
        }
        $clean = fn(string $k, int $max = 0) => trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) ($_POST[$k] ?? '')));
        $f = [
            'title_en' => $clean('title_en'), 'title_ar' => $clean('title_ar'),
            'body_en'  => $clean('body_en'),  'body_ar'  => $clean('body_ar'),
            'event_start_date' => $clean('event_start_date'), 'event_end_date' => $clean('event_end_date'),
            'event_location_en' => $clean('event_location_en'), 'event_location_ar' => $clean('event_location_ar'),
            'c_name_en' => $clean('c_name_en'), 'c_name_ar' => $clean('c_name_ar'), 'c_contact' => $clean('c_contact'),
            'c_method' => (string) ($_POST['c_method'] ?? ''), 'c_date' => $clean('c_date'),
            'c_confirm' => !empty($_POST['c_confirm']),
            'remove_cover' => !empty($_POST['remove_cover']),
            'remove_images' => array_map('intval', (array) ($_POST['remove_images'] ?? [])),
        ];
        $send = $action === 'send';
        if ($f['title_en'] === '') $errors[] = ['Please write the title in English.', 'يرجى كتابة العنوان بالإنجليزية.'];
        if ($f['title_ar'] === '') $errors[] = ['Please write the title in Arabic.', 'يرجى كتابة العنوان بالعربية.'];
        if (mb_strlen($f['title_en']) > 200 || mb_strlen($f['title_ar']) > 200) $errors[] = ['Titles can be 200 characters at most.', 'العنوان لا يتجاوز 200 حرف.'];
        if ($send && $f['body_en'] === '') $errors[] = ['Please write the text in English.', 'يرجى كتابة النص بالإنجليزية.'];
        if ($send && $f['body_ar'] === '') $errors[] = ['Please write the text in Arabic.', 'يرجى كتابة النص بالعربية.'];
        if (mb_strlen($f['body_en']) > 20000 || mb_strlen($f['body_ar']) > 20000) $errors[] = ['The text is too long (20,000 characters at most).', 'النص طويل جداً (20,000 حرف كحد أقصى).'];
        $start = $end = null;
        if ($type === 'event') {
            $start = $f['event_start_date'] !== '' ? owFormDate($f['event_start_date']) : null;
            $end   = $f['event_end_date'] !== '' ? owFormDate($f['event_end_date']) : null;
            if ($f['event_start_date'] !== '' && !$start) $errors[] = ['The start date is not a real date.', 'تاريخ البداية غير صحيح.'];
            if ($f['event_end_date'] !== '' && !$end) $errors[] = ['The end date is not a real date.', 'تاريخ النهاية غير صحيح.'];
            if ($start && $end && $end < $start) $errors[] = ['The end date cannot be before the start date.', 'لا يمكن أن يكون تاريخ النهاية قبل تاريخ البداية.'];
            if ($send && !$start) $errors[] = ['Please choose the start date of the event.', 'يرجى اختيار تاريخ بداية الملتقى.'];
            if ($send && ($f['event_location_en'] === '' || $f['event_location_ar'] === '')) $errors[] = ['Please write the place of the event in English and Arabic.', 'يرجى كتابة مكان الملتقى بالإنجليزية والعربية.'];
            if (mb_strlen($f['event_location_en']) > 200 || mb_strlen($f['event_location_ar']) > 200) $errors[] = ['The place can be 200 characters at most.', 'المكان لا يتجاوز 200 حرف.'];
        }
        foreach (['title_en', 'title_ar', 'body_en', 'body_ar'] as $k) {
            if (preg_match('~<\s*/?\s*(script|iframe|object)~i', $f[$k])) { $errors[] = ['Please write plain text only.', 'يرجى كتابة نص عادي فقط.']; break; }
        }

        $coverFile = $_FILES['cover'] ?? null;
        $newCover  = $coverFile && ($coverFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $imgFiles  = [];
        if (!empty($_FILES['images']['name']) && is_array($_FILES['images']['name'])) {
            foreach ($_FILES['images']['name'] as $i => $n) {
                if (($_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
                $imgFiles[] = ['name' => $n, 'type' => $_FILES['images']['type'][$i], 'tmp_name' => $_FILES['images']['tmp_name'][$i],
                               'error' => $_FILES['images']['error'][$i], 'size' => $_FILES['images']['size'][$i]];
            }
        }
        $kept = $existing ? array_values(array_filter($existing['images'], fn($im) => !in_array((int) $im['id'], $f['remove_images'], true))) : [];
        if (count($kept) + count($imgFiles) > OW_MY_ARTICLE_IMGS) $errors[] = ['An article can have ' . OW_MY_ARTICLE_IMGS . ' extra photos at most.', 'يمكن أن يحتوي المقال على ' . OW_MY_ARTICLE_IMGS . ' صور إضافية كحد أقصى.'];
        $hasPhotos = $newCover || ($existing && $existing['cover_image_path'] !== '' && !$f['remove_cover']) || $kept || $imgFiles;

        /* Spotlights with photos: the person in the photos must have agreed (consent record) */
        $newConsent = false; $cDate = null; $proofGiven = false; $proofFile = null;
        if ($type === 'artist_spotlight') {
            $proofFile  = $_FILES['c_proof'] ?? null;
            $proofGiven = $proofFile && ($proofFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            $any = $f['c_name_en'] !== '' || $f['c_name_ar'] !== '' || $f['c_contact'] !== '' || $f['c_method'] !== '' || $f['c_date'] !== '' || $proofGiven;
            if ($any) {
                $newConsent = true;
                $cDate = owFormDate($f['c_date']);
                if ($f['c_name_en'] === '' || $f['c_name_ar'] === '') $errors[] = ['Photo consent: please write the name in English and Arabic.', 'الموافقة على الصور: يرجى كتابة الاسم بالإنجليزية والعربية.'];
                if (!filter_var($f['c_contact'], FILTER_VALIDATE_EMAIL) && !preg_match('/^\+?[0-9 ()-]{7,20}$/', $f['c_contact'])) $errors[] = ['Photo consent: please add their email or phone number.', 'الموافقة على الصور: يرجى إضافة بريده أو رقم هاتفه.'];
                if (!isset($METHODS[$f['c_method']])) $errors[] = ['Photo consent: please choose how they agreed.', 'الموافقة على الصور: يرجى اختيار طريقة الموافقة.'];
                if (!$cDate || $cDate > date('Y-m-d')) $errors[] = ['Photo consent: please choose the date they agreed (not in the future).', 'الموافقة على الصور: يرجى اختيار تاريخ الموافقة (ليس في المستقبل).'];
                if (!$f['c_confirm']) $errors[] = ['Photo consent: please confirm that they agreed.', 'الموافقة على الصور: يرجى تأكيد موافقته.'];
                if (mb_strlen($f['c_name_en']) > 200 || mb_strlen($f['c_name_ar']) > 200 || mb_strlen($f['c_contact']) > 190) $errors[] = ['Photo consent: a field is too long.', 'الموافقة على الصور: أحد الحقول طويل جداً.'];
            }
            $active = $existing && array_filter($existing['consents'], fn($x) => $x['withdrawn_at'] === null);
            if ($send && $hasPhotos && !$active && !$newConsent) {
                $errors[] = ['This spotlight has photos. Please fill in the photo consent below: the artist in the photos must agree first.',
                             'هذا المقال يحتوي على صور. يرجى تعبئة الموافقة على الصور في الأسفل: يجب أن يوافق الفنان الظاهر فيها أولاً.'];
            }
        }

        $stored = []; $coverPath = null; $newPaths = []; $proofPath = '';
        if (!$errors && $newCover) {
            $r = owStoreImage($coverFile, OW_ARTICLE_DIR);
            if ($r['ok']) { $coverPath = $r['path']; $stored[] = $r['path']; } else $errors[] = owUploadErrBi($r['err'], 'Cover photo', 'صورة الغلاف');
        }
        if (!$errors) foreach ($imgFiles as $k => $file) {
            $r = owStoreImage($file, OW_ARTICLE_DIR);
            if ($r['ok']) { $newPaths[] = $r['path']; $stored[] = $r['path']; } else { $errors[] = owUploadErrBi($r['err'], 'Extra photo ' . ($k + 1), 'الصورة الإضافية ' . ($k + 1)); break; }
        }
        if (!$errors && $newConsent && $proofGiven) {
            $r = owStoreConsentProof($proofFile);
            if ($r['ok']) $proofPath = $r['path'];
            else $errors[] = $r['err'] === 'type' ? ['Consent proof: please choose a photo (JPG, PNG, WEBP) or a PDF.', 'إثبات الموافقة: يرجى اختيار صورة (JPG أو PNG أو WEBP) أو ملف PDF.'] : owUploadErrBi($r['err'], 'Consent proof', 'إثبات الموافقة');
        }

        if (!$errors) {
            try {
                $db->beginTransaction();
                $slug  = owUniqueSlug($f['title_en'], $id);
                $cover = $coverPath ?? (($existing && !$f['remove_cover']) ? $existing['cover_image_path'] : '');
                $vals = [':slug' => $slug, ':te' => $f['title_en'], ':ta' => $f['title_ar'], ':be' => $f['body_en'], ':ba' => $f['body_ar'], ':cover' => $cover,
                         ':sd' => $type === 'event' ? $start : null, ':ed' => $type === 'event' ? $end : null,
                         ':le' => $type === 'event' ? $f['event_location_en'] : '', ':la' => $type === 'event' ? $f['event_location_ar'] : ''];
                if ($existing) {
                    $db->prepare("UPDATE articles SET slug=:slug, title_en=:te, title_ar=:ta, body_en=:be, body_ar=:ba, cover_image_path=:cover,
                                  event_start_date=:sd, event_end_date=:ed, event_location_en=:le, event_location_ar=:la
                                  WHERE id=:id AND author_user_id=:u AND status='draft'")->execute($vals + [':id' => $id, ':u' => $uid]);
                } else {
                    $db->prepare("INSERT INTO articles (type, slug, title_en, title_ar, body_en, body_ar, cover_image_path, author_user_id, status,
                                  event_start_date, event_end_date, event_location_en, event_location_ar)
                                  VALUES (:type, :slug, :te, :ta, :be, :ba, :cover, :u, 'draft', :sd, :ed, :le, :la)")->execute($vals + [':type' => $type, ':u' => $uid]);
                    $id = (int) $db->lastInsertId();
                }
                $removed = [];
                if ($existing) {
                    foreach ($existing['images'] as $im) if (in_array((int) $im['id'], $f['remove_images'], true)) {
                        $db->prepare('DELETE FROM article_images WHERE id = :i AND article_id = :a')->execute([':i' => $im['id'], ':a' => $id]);
                        $removed[] = $im['image_path'];
                    }
                    if ($existing['cover_image_path'] !== '' && $existing['cover_image_path'] !== $cover) $removed[] = $existing['cover_image_path'];
                }
                $mx = $db->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM article_images WHERE article_id = :a'); $mx->execute([':a' => $id]);
                $order = (int) $mx->fetchColumn();
                $ins = $db->prepare('INSERT INTO article_images (article_id, image_path, alt_en, alt_ar, sort_order) VALUES (:a, :p, :ae, :aa, :o)');
                foreach ($newPaths as $p) { $order++; $ins->execute([':a' => $id, ':p' => $p, ':ae' => mb_substr($f['title_en'] . ', photo ' . $order, 0, 255), ':aa' => mb_substr($f['title_ar'] . '، صورة ' . $order, 0, 255), ':o' => $order]); }
                if ($newConsent) {
                    $db->prepare('INSERT INTO photo_consents (article_id, artist_name_en, artist_name_ar, artist_contact, consent_method, consent_given_at, proof_path, recorded_by)
                                  VALUES (:a, :ne, :na, :c, :m, :d, :p, :by)')
                       ->execute([':a' => $id, ':ne' => $f['c_name_en'], ':na' => $f['c_name_ar'], ':c' => $f['c_contact'], ':m' => $f['c_method'], ':d' => $cDate . ' 00:00:00', ':p' => $proofPath, ':by' => $uid]);
                }
                $db->commit();
                foreach ($removed as $p) owDeleteArticleFile((string) $p);
                /* Changed after it was sent: the waiting request is withdrawn, so the admin never approves a version they did not read */
                if (!$send && $existing) $db->prepare("UPDATE pending_changes SET status = 'replaced', decided_at = NOW() WHERE target_type = 'article' AND target_id = :id AND status = 'pending'")->execute([':id' => $id]);
                owLog($existing ? 'article_edit' : 'article_new', 'article', $id, $type, ['type' => 'artist', 'id' => $uid]);
                if ($send) {
                    if (owSubmitChange($uid, 'article', $id, ['publish' => 1])) myFlash('Sent. The Oweili team will read it and email you the result. Until then, visitors do not see it.', 'تم الإرسال. سيقرؤه فريق أويلي ويراسلك بالنتيجة. حتى ذلك الحين لا يراه الزوار.');
                    else myFlash('Saved as a draft, but it could not be sent. Please try again.', 'تم الحفظ كمسودة، لكن تعذّر الإرسال. يرجى المحاولة مرة أخرى.', true);
                    header('Location: my_articles.php'); exit;
                }
                myFlash('Saved as a draft. Only you can see it.', 'تم الحفظ كمسودة. لا يراها أحد غيرك.');
                header('Location: my_articles.php?edit=' . $id); exit;
            } catch (Throwable $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log('my_articles save: ' . $e->getMessage());
                $errors[] = ['Something went wrong while saving. Nothing was changed. Please try again.', 'حدث خطأ أثناء الحفظ. لم يتغير شيء. يرجى المحاولة مرة أخرى.'];
            }
        }
        foreach ($stored as $p) owDeleteArticleFile($p);
        if ($proofPath !== '' && owSafeConsentPath($proofPath)) @unlink(__DIR__ . '/' . $proofPath);
        $form = $f + ['id' => $existing ? $id : 0, 'type' => $type];
    }
}

/* ---- What to show ---- */
$mode = 'list'; $article = null;
$blank = ['title_en' => '', 'title_ar' => '', 'body_en' => '', 'body_ar' => '', 'event_start_date' => '', 'event_end_date' => '', 'event_location_en' => '', 'event_location_ar' => '',
          'c_name_en' => '', 'c_name_ar' => '', 'c_contact' => '', 'c_method' => '', 'c_date' => '', 'c_confirm' => false];
if ($ready && $approved) {
    if ($form !== null) { $mode = 'form'; $article = $form['id'] ? myArticle($db, (int) $form['id'], $uid) : null; }
    elseif (isset($_GET['edit'])) {
        $article = myArticle($db, (int) $_GET['edit'], $uid);
        if ($article && $article['status'] !== 'published') { $mode = 'form'; $form = $article + $blank; $form['c_method'] = ''; foreach (['c_name_en', 'c_name_ar', 'c_contact', 'c_date'] as $k) $form[$k] = ''; }
    } elseif (isset($_GET['new']) && isset($TYPES[(string) $_GET['new']])) {
        $mode = 'form'; $form = ['id' => 0, 'type' => (string) $_GET['new']] + $blank;
    }
}
$list = [];
$changes = owMyChanges($uid);
if ($ready) {
    $st = $db->prepare('SELECT id, type, slug, title_en, title_ar, status, created_at, updated_at, published_at FROM articles WHERE author_user_id = :u ORDER BY updated_at DESC, id DESC');
    $st->execute([':u' => $uid]); $list = $st->fetchAll();
}
$flash = $_SESSION['my_art_flash'] ?? null; unset($_SESSION['my_art_flash']);
$v = fn(string $k) => e((string) ($form[$k] ?? ''));
$TYPE_HELP = [
    'event'            => ['An art exhibition, fair or gathering anywhere in the world. Add the dates and the place.', 'معرض أو ملتقى أو تجمّع فني في أي مكان من العالم. أضف التواريخ والمكان.'],
    'artist_spotlight' => ['A story about an artist you admire: their path, their style, their work.', 'قصة عن فنان تُعجب به: مسيرته وأسلوبه وأعماله.'],
    'art_education'    => ['Teach something you know: a technique, a material, the story of a style.', 'علّم شيئاً تعرفه: تقنية أو خامة أو قصة أسلوب.'],
];
$stateOf = function (array $a) use ($changes): string {
    if ($a['status'] === 'published') return 'published';
    $c = $changes['article:' . (int) $a['id']] ?? null;
    if ($c && $c['state'] === 'pending') return 'pending';
    if ($c && $c['state'] === 'rejected') return 'rejected';
    return 'draft';
};
$STATE = ['draft' => ['Draft', 'مسودة'], 'pending' => ['Waiting for approval', 'بانتظار الموافقة'], 'rejected' => ['Not approved', 'لم تتم الموافقة'], 'published' => ['Published', 'منشور']];
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>My articles · مقالاتي · Oweili</title>
<meta name="description" content="Write articles for Oweili. اكتب مقالات لأويلي.">
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
/* My articles — layout only; cards, buttons, fields and pills come from oweili.css (role color: artist) */
.page{position:relative;z-index:10;}
.ma-head{display:flex;gap:var(--space-md);align-items:center;justify-content:space-between;flex-wrap:wrap;padding:var(--space-lg);}
.ma-head h1{font-size:var(--fs-section);margin:0 0 4px;}
.ma-head p{margin:0;color:var(--color-body);}
.ma-new{display:grid;grid-template-columns:repeat(3,1fr);gap:var(--space-sm);margin:var(--space-md) 0;}
.ma-new a{display:flex;flex-direction:column;gap:6px;padding:var(--space-md);text-decoration:none;color:inherit;}
.ma-new b{font-size:var(--fs-subtitle);color:var(--color-dark);}
.ma-new span{font-size:var(--fs-caption);color:var(--color-body);}
.ma-new .go{margin-top:auto;font-weight:700;color:var(--color-accent);font-size:15px;}
.ma-list{display:grid;gap:var(--space-sm);}
.ma-row{display:grid;grid-template-columns:1fr auto;gap:var(--space-sm);align-items:center;padding:var(--space-sm) var(--space-md);}
.ma-row > div{min-width:0;}
.ma-t{font-weight:700;color:var(--color-dark);margin:0 0 4px;overflow-wrap:anywhere;}
.ma-meta{font-size:var(--fs-caption);color:var(--color-body);display:flex;gap:var(--space-xs);flex-wrap:wrap;}
.ma-acts{display:flex;gap:var(--space-xs);flex-wrap:wrap;justify-content:flex-end;align-items:center;}
.ma-acts form{margin:0;}
.st-pill{display:inline-block;font-size:var(--fs-min);font-weight:700;padding:3px 10px;border-radius:var(--radius-pill);border:1px solid var(--color-border-strong);color:var(--color-dark);background:var(--color-surface);white-space:nowrap;}
.st-pill.s-published{background:rgba(var(--rgb-accent),.1);border-color:rgba(var(--rgb-accent),.35);}
.st-pill.s-rejected{background:rgba(var(--rgb-primary),.18);border-color:rgba(var(--rgb-primary),.45);}
.st-pill.s-pending{background:var(--color-tint-blue);}
.w-reason{margin-top:6px;font-size:var(--fs-caption);color:var(--color-dark);background:var(--color-tint-pink);border:1px solid rgba(var(--rgb-primary),.3);border-radius:var(--radius-input);padding:6px 10px;}
.note{font-size:var(--fs-caption);color:var(--color-body);margin:0 0 var(--space-sm);}
.ma-form{padding:var(--space-lg);max-width:860px;}
.ma-form h2{font-size:var(--fs-subtitle);margin:var(--space-md) 0 var(--space-sm);}
.ma-form h2:first-of-type{margin-top:0;}
.two{display:grid;grid-template-columns:1fr 1fr;gap:var(--space-sm);}
.field textarea{min-height:220px;}
.thumbs{display:flex;flex-wrap:wrap;gap:var(--space-xs);margin:6px 0 var(--space-sm);}
.thumb{width:120px;font-size:var(--fs-caption);}
.thumb img{width:120px;height:90px;object-fit:cover;border-radius:var(--radius-input);display:block;background:var(--color-surface-2);margin-bottom:4px;}
.thumb label{display:flex;gap:6px;align-items:center;cursor:pointer;}
.box-blue{background:var(--color-tint-blue);border:1px solid rgba(var(--rgb-accent),.18);border-radius:var(--radius-card);padding:var(--space-md);margin:var(--space-sm) 0;}
.check{display:flex;gap:var(--space-xs);align-items:flex-start;font-size:15px;margin:var(--space-xs) 0;}
.check input{margin-top:4px;}
.foot{display:flex;flex-wrap:wrap;gap:var(--space-sm);align-items:center;justify-content:space-between;border-top:1px solid var(--color-border);padding-top:var(--space-md);margin-top:var(--space-md);}
.foot .btns{display:flex;gap:var(--space-xs);flex-wrap:wrap;}
.msg ul{margin:6px 0 0;padding-inline-start:20px;}
.empty{text-align:center;padding:var(--space-xl) var(--space-md);}
@media(max-width:768px){.ma-head,.ma-form{padding:var(--space-md);}.ma-new{grid-template-columns:1fr;}.two{grid-template-columns:1fr;}
  .ma-row{grid-template-columns:1fr;}.ma-acts{justify-content:flex-start;}}
</style>
</head>
<body>
<div class="bg-wrap"></div>
<div class="overlay"></div>
<?php include __DIR__ . '/nav.php'; ?>

<main class="page dash role-artist dash-wrap">
  <div class="card ma-head">
    <div>
      <h1><?= owBi('My articles', 'مقالاتي') ?></h1>
      <p><?= owBi('Share what you know with art lovers. The Oweili team reads every article before it goes live.', 'شارك ما تعرفه مع محبي الفن. يقرأ فريق أويلي كل مقال قبل نشره.') ?></p>
    </div>
    <a class="btn btn-ghost" href="artist_studio.php?tab=articles"><?= owBi('Back to My Studio', 'العودة إلى مرسمي') ?></a>
  </div>

  <?php if ($flash): ?><div class="alert <?= $flash[2] ? 'alert-error' : 'alert-ok' ?>" role="status" style="margin-top:var(--space-md)"><?= owBi($flash[0], $flash[1]) ?></div><?php endif; ?>

  <?php if (!$ready): ?>
    <div class="card empty" style="margin-top:var(--space-md)"><p><?= owBi('Articles are being set up. Please check back soon.', 'المقالات قيد التجهيز. يرجى العودة قريباً.') ?></p></div>
  <?php elseif (!$approved): ?>
    <div class="card empty" style="margin-top:var(--space-md)"><p><?= owBi('You can write articles once your artist account is approved. We will email you.', 'يمكنك كتابة المقالات بعد الموافقة على حسابك كفنان. سنراسلك.') ?></p>
      <a class="btn btn-secondary" href="artist_studio.php"><?= owBi('Back to My Studio', 'العودة إلى مرسمي') ?></a></div>

  <?php elseif ($mode === 'form'): $type = $form['type']; [$tEn, $tAr] = $TYPES[$type]; $isSpot = $type === 'artist_spotlight'; ?>
    <form class="card ma-form" method="post" action="my_articles.php" enctype="multipart/form-data" style="margin-top:var(--space-md)">
      <?= csrfField() ?>
      <input type="hidden" name="id" value="<?= (int) ($form['id'] ?? 0) ?>"><input type="hidden" name="type" value="<?= e($type) ?>">
      <?php if ($errors): ?><div class="alert alert-error msg" role="alert"><?= owBi('Please check these:', 'يرجى مراجعة ما يلي:') ?><ul><?php foreach ($errors as [$en, $ar]): ?><li><?= owBi($en, $ar) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
      <h2><?= owBi(($form['id'] ? 'Edit: ' : 'New: ') . $tEn, ($form['id'] ? 'تعديل: ' : 'جديد: ') . $tAr) ?></h2>
      <p class="note"><?= owBi($TYPE_HELP[$type][0], $TYPE_HELP[$type][1]) ?> <?= owBi('Please write it in English and Arabic.', 'يرجى كتابته بالإنجليزية والعربية.') ?></p>
      <?php $pc = $form['id'] ? ($changes['article:' . (int) $form['id']] ?? null) : null; if ($pc && $pc['state'] === 'pending'): ?>
        <div class="alert alert-ok"><?= owBi('This article is waiting for approval. If you change it, press "Send for approval" again: "Save draft" takes it back from the review.', 'هذا المقال بانتظار الموافقة. إن عدّلته فاضغط «أرسل للموافقة» مرة أخرى: «احفظ كمسودة» يسحبه من المراجعة.') ?></div>
      <?php elseif ($pc && $pc['state'] === 'rejected'): ?>
        <div class="w-reason"><?= owBi('Not approved last time. The reason: ', 'لم تتم الموافقة في المرة السابقة. السبب: ') ?><?= e($pc['reason'] ?: '—') ?></div>
      <?php endif; ?>
      <div class="two">
        <div class="field"><label for="te"><?= owBi('Title in English', 'العنوان بالإنجليزية') ?></label><input id="te" name="title_en" dir="ltr" maxlength="200" required value="<?= $v('title_en') ?>"></div>
        <div class="field"><label for="ta"><?= owBi('Title in Arabic', 'العنوان بالعربية') ?></label><input id="ta" name="title_ar" dir="rtl" maxlength="200" required value="<?= $v('title_ar') ?>"></div>
      </div>
      <?php if ($type === 'event'): ?>
      <div class="two">
        <div class="field"><label for="sd"><?= owBi('Start date', 'تاريخ البداية') ?></label><input id="sd" type="date" name="event_start_date" value="<?= $v('event_start_date') ?>"></div>
        <div class="field"><label for="ed"><?= owBi('End date (optional)', 'تاريخ النهاية (اختياري)') ?></label><input id="ed" type="date" name="event_end_date" value="<?= $v('event_end_date') ?>"></div>
        <div class="field"><label for="le"><?= owBi('Place in English', 'المكان بالإنجليزية') ?></label><input id="le" name="event_location_en" dir="ltr" maxlength="200" value="<?= $v('event_location_en') ?>" placeholder="Jeddah, Saudi Arabia"></div>
        <div class="field"><label for="la"><?= owBi('Place in Arabic', 'المكان بالعربية') ?></label><input id="la" name="event_location_ar" dir="rtl" maxlength="200" value="<?= $v('event_location_ar') ?>" placeholder="جدة، السعودية"></div>
      </div>
      <?php endif; ?>
      <div class="field"><label for="be"><?= owBi('Text in English', 'النص بالإنجليزية') ?></label><textarea id="be" name="body_en" dir="ltr" maxlength="20000"><?= $v('body_en') ?></textarea></div>
      <div class="field"><label for="ba"><?= owBi('Text in Arabic', 'النص بالعربية') ?></label><textarea id="ba" name="body_ar" dir="rtl" maxlength="20000"><?= $v('body_ar') ?></textarea></div>
      <p class="note"><?= owBi('Leave an empty line between paragraphs.', 'اترك سطراً فارغاً بين الفقرات.') ?></p>

      <h2><?= owBi('Photos', 'الصور') ?></h2>
      <p class="note"><?= owBi('Only photos you took yourself or have the right to share. JPG, PNG or WEBP, 10MB each at most. Location data is removed.', 'فقط صور التقطتها بنفسك أو يحق لك مشاركتها. JPG أو PNG أو WEBP، بحد أقصى 10 ميغابايت لكل صورة. تُزال بيانات الموقع.') ?></p>
      <?php if ($article && $article['cover_image_path'] !== '' && owSafeArticlePath($article['cover_image_path'])): ?>
        <div class="thumbs"><div class="thumb"><img src="<?= e($article['cover_image_path']) ?>" alt="<?= e('Cover photo · صورة الغلاف') ?>"><label><input type="checkbox" name="remove_cover" value="1"> <?= owBi('Remove', 'إزالة') ?></label></div></div>
      <?php endif; ?>
      <div class="field"><label for="cv"><?= owBi('Cover photo', 'صورة الغلاف') ?></label><input id="cv" type="file" name="cover" accept="image/jpeg,image/png,image/webp"></div>
      <?php if ($article && $article['images']): ?>
        <div class="thumbs"><?php foreach ($article['images'] as $im): if (!owSafeArticlePath((string) $im['image_path'])) continue; ?>
          <div class="thumb"><img src="<?= e($im['image_path']) ?>" alt="<?= e($im['alt_en'] . ' · ' . $im['alt_ar']) ?>"><label><input type="checkbox" name="remove_images[]" value="<?= (int) $im['id'] ?>"> <?= owBi('Remove', 'إزالة') ?></label></div>
        <?php endforeach; ?></div>
      <?php endif; ?>
      <div class="field"><label for="im"><?= owBi('Extra photos (up to ' . OW_MY_ARTICLE_IMGS . ')', 'صور إضافية (حتى ' . OW_MY_ARTICLE_IMGS . ')') ?></label><input id="im" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp"></div>

      <?php if ($isSpot): $hasC = $article && array_filter($article['consents'], fn($x) => $x['withdrawn_at'] === null); ?>
      <div class="box-blue">
        <h2 style="margin-top:0"><?= owBi('Photo consent', 'الموافقة على الصور') ?></h2>
        <?php if ($hasC): ?>
          <p class="note" style="margin:0"><?= owBi('A photo consent is saved for this spotlight. Thank you.', 'تم حفظ موافقة على الصور لهذا المقال. شكراً لك.') ?></p>
        <?php else: ?>
          <p class="note"><?= owBi('If the spotlight has photos, the artist in them must agree before it is published. Without photos, you can leave this empty.', 'إن كان المقال يحتوي على صور، يجب أن يوافق الفنان الظاهر فيها قبل النشر. بدون صور يمكنك ترك هذا فارغاً.') ?></p>
          <div class="two">
            <div class="field"><label for="cne"><?= owBi('Their name in English', 'اسمه بالإنجليزية') ?></label><input id="cne" name="c_name_en" dir="ltr" maxlength="200" value="<?= $v('c_name_en') ?>"></div>
            <div class="field"><label for="cna"><?= owBi('Their name in Arabic', 'اسمه بالعربية') ?></label><input id="cna" name="c_name_ar" dir="rtl" maxlength="200" value="<?= $v('c_name_ar') ?>"></div>
            <div class="field"><label for="cct"><?= owBi('Their email or phone', 'بريده أو هاتفه') ?></label><input id="cct" name="c_contact" dir="ltr" maxlength="190" value="<?= $v('c_contact') ?>"></div>
            <div class="field"><label for="cdt"><?= owBi('Date they agreed', 'تاريخ الموافقة') ?></label><input id="cdt" type="date" name="c_date" max="<?= date('Y-m-d') ?>" value="<?= $v('c_date') ?>"></div>
          </div>
          <div class="field"><label for="cmt"><?= owBi('How they agreed', 'طريقة الموافقة') ?></label>
            <select id="cmt" name="c_method"><option value=""><?= e('— Choose · اختر —') ?></option>
              <?php foreach ($METHODS as $mk => [$me1, $ma1]): ?><option value="<?= e($mk) ?>"<?= ($form['c_method'] ?? '') === $mk ? ' selected' : '' ?>><?= e($me1 . ' · ' . $ma1) ?></option><?php endforeach; ?>
            </select></div>
          <div class="field"><label for="cpf"><?= owBi('Proof (optional): a photo or PDF of their message or signed form', 'إثبات (اختياري): صورة أو ملف PDF لرسالته أو النموذج الموقّع') ?></label><input id="cpf" type="file" name="c_proof" accept="image/jpeg,image/png,image/webp,application/pdf"></div>
          <label class="check"><input type="checkbox" name="c_confirm" value="1"<?= !empty($form['c_confirm']) ? ' checked' : '' ?>> <span><?= owBi('I confirm this artist agreed to have these photos published on Oweili.', 'أؤكد أن هذا الفنان وافق على نشر هذه الصور في أويلي.') ?></span></label>
          <p class="note" style="margin:0"><?= owBi('The proof is private: only the Oweili team can open it.', 'الإثبات خاص: لا يفتحه إلا فريق أويلي.') ?></p>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="foot">
        <a class="btn btn-ghost" href="my_articles.php"><?= owBi('Cancel', 'إلغاء') ?></a>
        <div class="btns">
          <button class="btn btn-secondary" type="submit" name="action" value="save" formnovalidate><?= owBi('Save draft', 'احفظ كمسودة') ?></button>
          <button class="btn btn-primary" type="submit" name="action" value="send"><?= owBi('Send for approval', 'أرسل للموافقة') ?></button>
        </div>
      </div>
      <p class="note" style="margin-top:var(--space-sm)"><?= owBi('By sending, you confirm the text is your own and the facts are true. The team may make small corrections before publishing.', 'بالإرسال تؤكد أن النص من كتابتك وأن المعلومات صحيحة. قد يجري الفريق تصحيحات بسيطة قبل النشر.') ?></p>
    </form>

  <?php else: ?>
    <div class="ma-new">
      <?php foreach ($TYPES as $k => [$en, $ar]): ?>
        <a class="card card-hover" href="my_articles.php?new=<?= e($k) ?>"><b><?= owBi($en, $ar) ?></b><span><?= owBi($TYPE_HELP[$k][0], $TYPE_HELP[$k][1]) ?></span><span class="go"><?= owBi('Write one →', 'اكتب واحداً ←') ?></span></a>
      <?php endforeach; ?>
    </div>
    <?php if (!$list): ?>
      <div class="card empty"><p><?= owBi('No articles yet. Pick a kind above to write your first one.', 'لا مقالات بعد. اختر نوعاً من الأعلى لتكتب أول مقال.') ?></p></div>
    <?php else: ?>
    <div class="ma-list">
      <?php foreach ($list as $a): $s = $stateOf($a); [$ten, $tar] = $TYPES[$a['type']] ?? ['Article', 'مقال']; [$de, $da] = owDateBi($a['updated_at']); $ch = $changes['article:' . (int) $a['id']] ?? null; ?>
      <div class="card ma-row">
        <div>
          <p class="ma-t"><?= owBi((string) ($a['title_en'] ?: $a['title_ar']), (string) ($a['title_ar'] ?: $a['title_en'])) ?></p>
          <div class="ma-meta"><span><?= owBi($ten, $tar) ?></span><span>·</span><span><?= owBi('Changed ' . $de, 'آخر تعديل ' . $da) ?></span></div>
          <?php if ($s === 'rejected'): ?><div class="w-reason"><?= owBi('Why: ', 'السبب: ') ?><?= e($ch['reason'] ?: '—') ?> · <?= owBi('Edit it and send it again.', 'عدّله وأرسله مجدداً.') ?></div><?php endif; ?>
          <?php if ($s === 'published'): ?><p class="note" style="margin:6px 0 0"><?= owBi('Published. To change it, write to us at ', 'منشور. لتعديله راسلنا على ') ?><a href="mailto:<?= e(owContactEmail()) ?>" dir="ltr"><?= e(owContactEmail()) ?></a></p><?php endif; ?>
        </div>
        <div class="ma-acts">
          <span class="st-pill s-<?= e($s) ?>"><?= owBi($STATE[$s][0], $STATE[$s][1]) ?></span>
          <?php if ($s === 'published'): ?>
            <a class="btn btn-ghost btn-sm" href="article.php?slug=<?= e(rawurlencode($a['slug'])) ?>"><?= owBi('See it', 'شاهده') ?></a>
          <?php else: ?>
            <a class="btn btn-accent btn-sm" href="my_articles.php?edit=<?= (int) $a['id'] ?>"><?= owBi('Edit', 'تعديل') ?></a>
            <form method="post" action="my_articles.php" onsubmit="return confirm(document.documentElement.dir==='rtl'?'حذف هذا المقال؟':'Delete this article?')"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
              <button class="btn btn-danger btn-sm" name="action" value="delete"><?= owBi('Delete', 'حذف') ?></button></form>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
