<?php
/* =====================================================================
 *  privacy.php — Privacy Policy («سياسة الخصوصية»), DRAFT under review.
 *  Written to fit the Saudi Personal Data Protection Law (PDPL): what we
 *  collect, why, who sees it, how long we keep it, and how to delete it.
 *  Layout: legal_lib.php. Tables: none
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/legal_lib.php';
$C = OW_LEGAL_CONTACT;

owLegalPage([
    'path' => 'privacy.php',
    'title_en' => 'Privacy Policy', 'title_ar' => 'سياسة الخصوصية',
    'lead_en' => 'Your trust matters more to us than anything. Here is, in plain words, what we keep about you and why.',
    'lead_ar' => 'ثقتك أهم ما لدينا. هنا نشرح بكلمات بسيطة ما نحفظه عنك ولماذا.',
    'desc_en' => 'How Oweili collects, uses, keeps and deletes personal data, under the Saudi Personal Data Protection Law.',
    'desc_ar' => 'كيف يجمع أويلي البيانات الشخصية ويستخدمها ويحفظها ويحذفها وفق نظام حماية البيانات الشخصية السعودي.',
    'updated_en' => '31 October 2026 (draft)', 'updated_ar' => '31 أكتوبر 2026 (مسودة)',
    'other' => ['terms.php', 'Terms of Use', 'شروط الاستخدام'],
], [
    ['who', 'Who we are', 'من نحن', [
        'Oweili (oweili.com) is an online gallery for real, human-made art, founded in Saudi Arabia and open to the world.',
        'Oweili is the party responsible for your personal data under the Saudi Personal Data Protection Law (PDPL) and its regulations.',
        "You can reach us about anything in this policy at $C.",
    ], [
        'أويلي (oweili.com) معرض إلكتروني للفن الحقيقي المصنوع بأيدٍ بشرية، انطلق من المملكة العربية السعودية ومفتوح للعالم.',
        'أويلي هو الجهة المسؤولة عن بياناتك الشخصية وفق نظام حماية البيانات الشخصية السعودي ولوائحه.',
        "يمكنك التواصل معنا بخصوص أي شيء في هذه السياسة على $C.",
    ]],
    ['middle', 'Oweili is the middle party', 'أويلي هو الوسيط', [
        'We stand between artists and buyers. When a buyer is interested in an artwork, the buyer’s details come to the Oweili team, not to the artist.',
        'The artist only learns that someone is interested and how many people are interested. We never give the artist a buyer’s name, phone or email.',
        'In the same way, we do not show an artist’s phone or email to buyers. Buyers see the artist’s public profile only.',
    ], [
        'نحن نقف بين الفنانين والمشترين. حين يهتم مشترٍ بعمل فني، تصل بياناته إلى فريق أويلي لا إلى الفنان.',
        'يعرف الفنان فقط أن هناك من يهتم وعدد المهتمين. لا نعطي الفنان اسم المشتري أو هاتفه أو بريده أبداً.',
        'وبالمثل لا نُظهر هاتف الفنان أو بريده للمشترين. يرى المشترون الملف العام للفنان فقط.',
    ]],
    ['collect', 'What we collect', 'ما الذي نجمعه', [
        '- Account details: your name, email address, phone number and password. Passwords are stored only as a secure one-way code, never as text.',
        '- Artist profile: profile photo, artist name, bio in English and Arabic, city, country, art styles and social links. Artists may also send portfolio photos and an optional video when they join.',
        '- Artwork photos: the photos of each artwork at 25%, 50% and 100%, with the date each one was taken. We remove hidden data such as GPS location from every photo before saving it.',
        '- "I’m Interested" requests: the name, phone and email you type in, and the artwork you asked about.',
        '- Buyer profile: country, city, preferred language, and the artworks you save as favorites.',
        '- Activity: what you do in your account and when (for example signing in or saving a favorite). With it we keep a one-way code made from your internet address that changes every month. We never keep the address itself.',
        '- Photo consent records: for local artists featured in Artist Spotlights, a record of their approval (who, when and how).',
        '- Browser storage: a small sign-in cookie, and your language and light or dark mode choice saved in your own browser.',
    ], [
        '- بيانات الحساب: الاسم والبريد الإلكتروني ورقم الهاتف وكلمة المرور. تُحفظ كلمات المرور كرمز آمن باتجاه واحد فقط، ولا تُحفظ كنص أبداً.',
        '- ملف الفنان: الصورة الشخصية واسم الفنان والنبذة بالعربية والإنجليزية والمدينة والدولة والأساليب الفنية وروابط التواصل. ويمكن للفنان إرسال صور من أعماله وفيديو اختياري عند التسجيل.',
        '- صور الأعمال: صور كل عمل عند 25% و50% و100% مع تاريخ التقاط كل صورة. نحذف البيانات المخفية مثل الموقع الجغرافي من كل صورة قبل حفظها.',
        '- طلبات «أنا مهتم»: الاسم والهاتف والبريد الذي تكتبه، والعمل الذي سألت عنه.',
        '- ملف المشتري: الدولة والمدينة واللغة المفضلة والأعمال التي تحفظها في المفضلة.',
        '- النشاط: ما تفعله في حسابك ومتى (مثل تسجيل الدخول أو حفظ عمل في المفضلة). ونحفظ معه رمزاً باتجاه واحد من عنوانك على الإنترنت يتغير كل شهر، ولا نحفظ العنوان نفسه أبداً.',
        '- سجلات موافقة الصور: للفنانين المحليين في «أضواء على الفنانين»، سجل بموافقتهم (من ومتى وكيف).',
        '- تخزين المتصفح: ملف صغير لتسجيل الدخول، واختيارك للغة والوضع الليلي أو النهاري محفوظاً في متصفحك أنت.',
    ]],
    ['why', 'Why we use it', 'لماذا نستخدمها', [
        '- To run your account and let you sign in safely.',
        '- To show artworks and the creation journey in the Gallery.',
        '- To review every artwork and keep Oweili for human-made art only.',
        '- To contact you about an artwork you asked about, and to tell an artist that someone is interested.',
        '- To send account emails, such as confirming your email or resetting your password.',
        '- To keep the site safe: limits on sign-ups and sign-ins, and the activity log.',
        'We rely on your consent, on what is needed to give you the service you asked for, and on what the law asks of us. You can withdraw your consent at any time.',
        'We do not sell your data. We do not use it for advertising profiles.',
    ], [
        '- لتشغيل حسابك وتمكينك من الدخول بأمان.',
        '- لعرض الأعمال ورحلة الإبداع في المعرض.',
        '- لمراجعة كل عمل والحفاظ على أويلي للفن البشري فقط.',
        '- للتواصل معك بخصوص عمل سألت عنه، ولإخبار الفنان أن هناك من يهتم.',
        '- لإرسال رسائل الحساب، مثل تأكيد البريد أو إعادة تعيين كلمة المرور.',
        '- لحماية الموقع: حدود على التسجيل والدخول، وسجل النشاط.',
        'نعتمد على موافقتك، وعلى ما يلزم لتقديم الخدمة التي طلبتها، وعلى ما يفرضه النظام. ويمكنك سحب موافقتك في أي وقت.',
        'لا نبيع بياناتك، ولا نستخدمها لبناء ملفات إعلانية.',
    ]],
    ['share', 'Who can see it', 'من يستطيع رؤيتها', [
        '- Everyone: what an artist chooses to make public (artist name, profile photo, bio, city, country, art styles, social links and approved artworks).',
        '- The Oweili team: the details needed to review artworks and answer requests. Only the admin sees full personal details.',
        '- Companies that help us run the site, only for that job: our hosting and email provider (Hostinger). They may not use your data for anything else.',
        '- Bashro, Oweili’s own internal control center, through a private read-only connection. It is switched off until we turn it on.',
        '- Authorities, only when Saudi law requires it.',
        'Our hosting provider may keep data on servers outside the Kingdom. When that happens we follow the PDPL rules for sending data abroad.',
    ], [
        '- الجميع: ما يختار الفنان إظهاره (اسم الفنان والصورة الشخصية والنبذة والمدينة والدولة والأساليب الفنية وروابط التواصل والأعمال المعتمدة).',
        '- فريق أويلي: ما يلزم لمراجعة الأعمال والرد على الطلبات. والمدير وحده يرى البيانات الشخصية كاملة.',
        '- الشركات التي تساعدنا في تشغيل الموقع، لهذه المهمة فقط: مزود الاستضافة والبريد (Hostinger). ولا يحق لها استخدام بياناتك لأي غرض آخر.',
        '- «بشرو»، مركز التحكم الداخلي الخاص بأويلي، عبر ربط خاص للقراءة فقط. وهو متوقف حتى نشغّله.',
        '- الجهات الرسمية، فقط حين يفرض النظام السعودي ذلك.',
        'قد يحفظ مزود الاستضافة البيانات على خوادم خارج المملكة. وحين يحدث ذلك نتبع قواعد نظام حماية البيانات الشخصية لنقل البيانات إلى الخارج.',
    ]],
    ['keep', 'How long we keep it', 'كم من الوقت نحفظها', [
        '- Your account and profile: for as long as your account is open. When you ask us to delete it, we delete it within 30 days.',
        '- Artwork photos: while the artwork is on Oweili. When an artwork or account is deleted, its photos are deleted too.',
        '- "I’m Interested" requests: up to 2 years after the last contact, then deleted.',
        '- Activity log: up to 12 months.',
        '- Database backups: up to 90 days, then replaced by newer ones.',
        '- Photo consent records: for as long as the photos are published, and up to 5 years after, so we can show the approval if asked.',
        '- Records the law asks us to keep (for example sales records, once payments open) are kept for the time the law sets.',
    ], [
        '- حسابك وملفك: ما دام حسابك مفتوحاً. وحين تطلب حذفه نحذفه خلال 30 يوماً.',
        '- صور الأعمال: ما دام العمل على أويلي. وحين يُحذف العمل أو الحساب تُحذف صوره أيضاً.',
        '- طلبات «أنا مهتم»: حتى سنتين بعد آخر تواصل، ثم تُحذف.',
        '- سجل النشاط: حتى 12 شهراً.',
        '- النسخ الاحتياطية لقاعدة البيانات: حتى 90 يوماً، ثم تُستبدل بنسخ أحدث.',
        '- سجلات موافقة الصور: ما دامت الصور منشورة، وحتى 5 سنوات بعد ذلك، لنثبت الموافقة عند الطلب.',
        '- السجلات التي يفرض النظام حفظها (مثل سجلات البيع بعد فتح المدفوعات) تُحفظ للمدة التي يحددها النظام.',
    ]],
    ['rights', 'Your rights', 'حقوقك', [
        'Under the PDPL you have the right to:',
        '- know what data we hold about you and why;',
        '- get a copy of your data;',
        '- correct data that is wrong or out of date (most of it you can change yourself in your dashboard);',
        '- ask us to delete your data;',
        '- withdraw your consent at any time.',
        "To use any of these rights, write to $C from the email of your account. We answer within 30 days.",
        'If you are not happy with our answer, you can complain to the Saudi Data and Artificial Intelligence Authority (SDAIA).',
    ], [
        'وفق نظام حماية البيانات الشخصية يحق لك:',
        '- أن تعرف ما نحفظه من بياناتك ولماذا؛',
        '- أن تحصل على نسخة من بياناتك؛',
        '- أن تصحح البيانات الخاطئة أو القديمة (ويمكنك تعديل أغلبها بنفسك من لوحتك)؛',
        '- أن تطلب حذف بياناتك؛',
        '- أن تسحب موافقتك في أي وقت.',
        "لاستخدام أي من هذه الحقوق، راسلنا على $C من بريد حسابك. نرد خلال 30 يوماً.",
        'وإن لم يرضك ردنا، يمكنك تقديم شكوى إلى الهيئة السعودية للبيانات والذكاء الاصطناعي (سدايا).',
    ]],
    ['delete', 'How to delete your data', 'كيف تحذف بياناتك', [
        "Send an email to $C from the email of your account with the words \"Delete my Oweili account\". We confirm it is really you, then delete your account, profile, photos, favorites and requests within 30 days.",
        'Some records stay for a short time only when the law asks for it, or inside backups until they are replaced (up to 90 days).',
        'You can also delete any of your artworks yourself, at any time, from your dashboard.',
    ], [
        "أرسل رسالة إلى $C من بريد حسابك بعبارة «احذف حسابي في أويلي». نتأكد أنك صاحب الحساب، ثم نحذف حسابك وملفك وصورك ومفضلتك وطلباتك خلال 30 يوماً.",
        'تبقى بعض السجلات لفترة قصيرة فقط حين يفرض النظام ذلك، أو داخل النسخ الاحتياطية حتى تُستبدل (حتى 90 يوماً).',
        'ويمكنك أيضاً حذف أي من أعمالك بنفسك في أي وقت من لوحتك.',
    ]],
    ['safe', 'How we keep it safe', 'كيف نحميها', [
        'Every page uses a secure connection (HTTPS). Passwords are stored as one-way codes. Private files, such as consent proofs and backups, are blocked from the public. Only the admin can see full personal details, and every staff action is recorded.',
        'No system is perfect. If a breach ever affects your data, we will tell you and the authorities as the PDPL requires.',
    ], [
        'كل صفحة تعمل باتصال آمن (HTTPS). كلمات المرور محفوظة كرموز باتجاه واحد. الملفات الخاصة، مثل إثباتات الموافقة والنسخ الاحتياطية، محجوبة عن العامة. والمدير وحده يرى البيانات الشخصية كاملة، وكل إجراء من الفريق مسجّل.',
        'لا يوجد نظام كامل. وإن تأثرت بياناتك بأي اختراق يوماً، سنبلغك ونبلغ الجهات المختصة كما يفرض النظام.',
    ]],
    ['young', 'Young people', 'صغار السن', [
        'Oweili is for people aged 18 and over. Younger artists and buyers may use it only with a parent or guardian, who agrees for them.',
    ], [
        'أويلي لمن هم في الثامنة عشرة فما فوق. ويمكن لمن هم أصغر من الفنانين والمشترين استخدامه فقط مع ولي أمر يوافق عنهم.',
    ]],
    ['changes', 'Changes to this policy', 'التغييرات على هذه السياسة', [
        'When we change this policy we update the date below. If a change is important, we tell you by email or on the site first.',
    ], [
        'حين نغيّر هذه السياسة نحدّث التاريخ أدناه. وإن كان التغيير مهماً نخبرك أولاً بالبريد أو على الموقع.',
    ]],
]);
