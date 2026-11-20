<?php
/* =====================================================================
 *  demo_lib.php — DEMO content that ONLY the signed-in admin sees (owner, 20 Nov 2026)
 *  Purpose: let the owner see the Gallery, Art Events, Art Education and Art Institutes "full".
 *  Safety by design: NOTHING is in the database. The demo items live only in this file and are
 *  added to those four pages (and the demo article pages) only when owDemoOn() is true:
 *  the admin is signed in (owIsAdmin) AND the demo was not deleted. So visitors, other accounts,
 *  Google, the sitemap, the Art Map, emails, certificates and Bashro can never see them, and
 *  nothing can be bought, booked or sent (demo buttons are switched off).
 *  Admin → Settings → "Delete all demo content" switches them off for good
 *  (site_settings demo_content = '0'; no table or column is added).
 *  Pictures: public-domain paintings already on Art Styles (assets/img/styles/), with their credit.
 *  Tables: site_settings (one row, read and written by gallery_lib.php owSetting / owSetSetting)
 * ===================================================================== */
require_once __DIR__ . '/admin_shell.php';
require_once __DIR__ . '/gallery_lib.php';

function owDemoOn(): bool {
    static $on = null;
    if ($on === null) $on = owIsAdmin() && owSetting('demo_content', '1') !== '0';
    return $on;
}
function owDemoDelete(): bool { return owSetSetting('demo_content', '0'); }
/* Brings the demo back after a delete (owner, 20 Nov 2026): one row back to '1' */
function owDemoRestore(): bool { return owSetSetting('demo_content', '1'); }

/* The "Demo" badge, in the page language */
function owDemoBadge(): string {
    return '<span class="demo-badge">' . owBi('Demo', 'تجريبي') . '</span>';
}
function owDemoImg(string $key, bool $small = false): string {
    return owBase() . 'assets/img/styles/' . $key . ($small ? '-s' : '') . '.jpg';
}
const OW_DEMO_ARTIST = ['Demo Artist', 'فنان تجريبي'];
const OW_DEMO_INSTITUTE = ['Demo Institute', 'معهد تجريبي'];
const OW_DEMO_NOTE = ['Demo content: only you (the admin) can see it. Nothing can be bought, booked or sent.',
                      'محتوى تجريبي: تراه أنت فقط (الإدارة). لا يمكن شراء شيء أو حجزه أو إرساله.'];

/* ---- Gallery: 8 demo artworks (key = the Art Styles picture) ---- */
function owDemoArtworks(): array {
    $L = [
        ['oil', 'Night Over the Village', 'ليل فوق القرية', 4800, 'Vincent van Gogh, The Starry Night, 1889', 'فنسنت فان غوخ، «ليلة النجوم»، 1889'],
        ['watercolor', 'Blue Mountain at Dawn', 'جبل أزرق عند الفجر', 1200, 'J. M. W. Turner, The Blue Rigi, Sunrise, 1842', 'ج. م. و. تيرنر، «ريغي الزرقاء عند الشروق»، 1842'],
        ['marine', 'Waves at Sunset', 'أمواج عند الغروب', 3500, 'Ivan Aivazovsky, The Black Sea, 1881', 'إيفان أيفازوفسكي، «البحر الأسود»، 1881'],
        ['romanticism', 'Last Light on the River', 'آخر ضوء على النهر', 6200, 'J. M. W. Turner, The Fighting Temeraire, 1839', 'ج. م. و. تيرنر، «السفينة تيميرير»، 1839'],
        ['realism', 'The Big Wave', 'الموجة الكبيرة', 2900, 'Gustave Courbet, The Wave, 1869', 'غوستاف كوربيه، «الموجة»، 1869'],
        ['palette_knife', 'Spring in the Valley', 'نبع في الوادي', 1850, 'Gustave Courbet, The Source of the Loue, 1864', 'غوستاف كوربيه، «نبع اللو»، 1864'],
        ['symbolism', 'Calm Lake', 'بحيرة هادئة', 2400, 'Ferdinand Hodler, Lake Thun and the Stockhorn Mountains, 1904', 'فرديناند هودلر، «بحيرة تون وجبال ستوكهورن»، 1904'],
        ['art_nouveau', 'Golden Forest', 'غابة ذهبية', 5600, 'Gustav Klimt, Beech Grove I, 1902', 'غوستاف كليمت، «غابة الزان 1»، 1902'],
    ];
    $out = [];
    foreach ($L as $i => [$k, $tEn, $tAr, $price, $cEn, $cAr]) {
        $out[] = ['id' => 'd' . ($i + 1), 'type' => $k, 'title_en' => $tEn, 'title_ar' => $tAr, 'price' => $price, 'status' => 'available',
                  'image' => owDemoImg($k), 'image_s' => owDemoImg($k, true),
                  'desc_en' => 'A demo artwork to preview the Gallery. Picture: ' . $cEn . ' (public domain).',
                  'desc_ar' => 'عمل تجريبي لمعاينة المعرض. الصورة: ' . $cAr . ' (ملكية عامة).'];
    }
    return $out;
}

/* ---- Art Events (4) and Art Education (4): demo articles, opened at article.php?slug=demo-… ---- */
function owDemoArticles(string $type): array {
    $note = ["\n\nThis is demo content. Only the admin can see it.", "\n\nهذا محتوى تجريبي. لا يراه إلا المدير."];
    $all = [
        'event' => [
            ['demo-event-1', 'glazing', 'Open Studio Evening', 'أمسية المرسم المفتوح', '2027-01-15', '2027-01-17', 'Jeddah', 'جدة',
             'Artists open their studios for three evenings. Visitors watch real paintings take shape.', 'يفتح الفنانون مراسمهم ثلاث أمسيات. يشاهد الزوار لوحات حقيقية وهي تتشكّل.',
             'Johannes Vermeer, View of Delft, about 1660–61', 'يوهانس فيرمير، «منظر دلفت»، نحو 1660–1661'],
            ['demo-event-2', 'tonalism', 'Night Painting Walk', 'جولة الرسم الليلي', '2027-02-05', null, 'Riyadh', 'الرياض',
             'A calm evening walk with sketchbooks. We paint the city lights together.', 'جولة مسائية هادئة مع دفاتر الرسم. نرسم أضواء المدينة معاً.',
             'James McNeill Whistler, Nocturne: Blue and Silver – Chelsea, 1871', 'جيمس ماكنيل ويسلر، «ليلية: أزرق وفضي»، 1871'],
            ['demo-event-3', 'alla_prima', 'Sky Sketching Day', 'يوم رسم السماء', '2027-02-20', '2027-02-21', 'AlUla', 'العلا',
             'Two days of painting the open sky in one sitting. All levels are welcome.', 'يومان لرسم السماء المفتوحة في جلسة واحدة. الجميع مرحّب بهم.',
             'John Constable, Cloud Study, 1822', 'جون كونستابل، «دراسة سحب»، 1822'],
            ['demo-event-4', 'expressionism', 'Colour and Feeling Exhibition', 'معرض اللون والشعور', '2027-03-10', '2027-03-20', 'Dammam', 'الدمام',
             'An exhibition about how colour carries feeling. Original works by hand only.', 'معرض عن كيف يحمل اللون الشعور. أعمال أصلية مرسومة باليد فقط.',
             'Franz Marc, The Large Blue Horses, 1911', 'فرانتس مارك، «الخيول الزرقاء الكبيرة»، 1911'],
        ],
        'art_education' => [
            ['demo-education-1', 'landscape', 'How to look at a landscape painting', 'كيف تنظر إلى لوحة منظر طبيعي', null, null, '', '',
             'Start with the light, then the shapes, then the small details. Take your time.', 'ابدأ بالضوء، ثم الأشكال، ثم التفاصيل الصغيرة. خذ وقتك.',
             'Katsushika Hokusai, Fine Wind, Clear Morning (Red Fuji), about 1830', 'كاتسوشيكا هوكوساي، «ريح لطيفة وصباح صافٍ»، نحو 1830'],
            ['demo-education-2', 'pointillism', 'What is pointillism?', 'ما هو التنقيط؟', null, null, '', '',
             'Small dots of pure colour sit side by side. Your eye mixes them from afar.', 'نقاط صغيرة من لون نقي تتجاور. وعينك تمزجها من بعيد.',
             'Paul Signac, The Port of Saint-Tropez, 1901–02', 'بول سينياك، «ميناء سان تروبيه»، 1901–1902'],
            ['demo-education-3', 'dry_brush', 'Watercolor for beginners', 'الألوان المائية للمبتدئين', null, null, '', '',
             'Use good paper, a few colours and lots of water. Let each layer dry first.', 'استخدم ورقاً جيداً وألواناً قليلة وماءً كثيراً. واترك كل طبقة تجف أولاً.',
             'Winslow Homer, Palm Tree, Nassau, 1898', 'وينسلو هومر، «نخلة، ناسو»، 1898'],
            ['demo-education-4', 'pastel', 'Caring for pastel drawings', 'العناية برسوم الباستيل', null, null, '', '',
             'Keep pastels behind glass, away from sun and damp. Never touch the surface.', 'احفظ الباستيل خلف زجاج، بعيداً عن الشمس والرطوبة. ولا تلمس سطحه أبداً.',
             'Edgar Degas, Landscape with Rocks, 1892', 'إدغار ديغا، «منظر مع صخور»، 1892'],
        ],
    ];
    $out = [];
    foreach ($all[$type] ?? [] as $i => [$slug, $k, $tEn, $tAr, $s, $e, $lEn, $lAr, $bEn, $bAr, $cEn, $cAr]) {
        $out[] = ['id' => 0, 'demo' => true, 'slug' => $slug, 'type' => $type, 'status' => 'published', 'author_user_id' => null,
                  'title_en' => $tEn, 'title_ar' => $tAr, 'cover_image_path' => owDemoImg($k),
                  'published_at' => date('Y-m-d H:i:s', strtotime('-' . ($i + 1) . ' days')),
                  'event_start_date' => $s, 'event_end_date' => $e, 'event_location_en' => $lEn, 'event_location_ar' => $lAr,
                  'body_en' => $bEn . "\n\nPicture: " . $cEn . ' (public domain).' . $note[0],
                  'body_ar' => $bAr . "\n\nالصورة: " . $cAr . ' (ملكية عامة).' . $note[1]];
    }
    return $out;
}
function owDemoArticle(string $slug): ?array {
    foreach (['event', 'art_education'] as $t) foreach (owDemoArticles($t) as $a) if ($a['slug'] === $slug) return $a;
    return null;
}

/* ---- Art Institutes: 4 demo courses (two cities and two online) ---- */
function owDemoCourses(): array {
    $L = [
        ['Watercolor Basics', 'أساسيات الألوان المائية', 'in_person', 'Riyadh', 'الرياض', 'beginner', 450, 8, '2027-01-10', '2027-01-31'],
        ['Charcoal Drawing', 'الرسم بالفحم', 'in_person', 'Jeddah', 'جدة', 'intermediate', 750, 5, '2027-01-18', '2027-02-15'],
        ['Oil Painting from Home', 'الرسم الزيتي من المنزل', 'online', '', '', 'beginner', 300, 20, '2027-02-01', '2027-02-28'],
        ['Colour Theory for Painters', 'نظرية الألوان للرسامين', 'online', '', '', 'advanced', 1200, 12, '2027-02-14', '2027-03-14'],
    ];
    $out = [];
    foreach ($L as $i => [$tEn, $tAr, $type, $cEn, $cAr, $lvl, $price, $seats, $s, $e]) {
        $out[] = ['id' => 0, 'demo' => true, 'institute_id' => 0, 'inst_en' => OW_DEMO_INSTITUTE[0], 'inst_ar' => OW_DEMO_INSTITUTE[1], 'inst_logo' => '',
                  'inst_city_en' => $cEn, 'inst_city_ar' => $cAr, 'title_en' => $tEn, 'title_ar' => $tAr, 'course_type' => $type,
                  'level' => $lvl, 'price' => $price, 'seats_remaining' => $seats, 'start_date' => $s, 'end_date' => $e];
    }
    return $out;
}
