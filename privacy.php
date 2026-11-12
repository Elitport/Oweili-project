<?php
/* =====================================================================
 *  privacy.php — Privacy Policy («سياسة الخصوصية»). Reviewed 11 Nov 2026 (no lawyer yet).
 *  Written to fit the Saudi Personal Data Protection Law (PDPL): what we
 *  collect, why, who sees it, how long we keep it, and how to delete it.
 *  Layout: legal_lib.php. Tables: none
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/page_cache.php'; owPublicPage();   // public page: Back opens at once, early load on touch (page_cache.php)
require_once __DIR__ . '/legal_lib.php';
$C = OW_LEGAL_CONTACT;
$CO = OW_COMPANY_NAME; $CR = function_exists('owCompanyCr') ? owCompanyCr() : '';

owLegalPage([
    'path' => 'privacy.php',
    'title_en' => 'Privacy Policy', 'title_ar' => 'سياسة الخصوصية',
    'lead_en' => 'Your trust matters more to us than anything. Here is, in plain words, what we keep about you and why.',
    'lead_ar' => 'ثقتك أهم ما لدينا. هنا نشرح بكلمات بسيطة ما نحفظه عنك ولماذا.',
    'desc_en' => 'How Oweili collects, uses, keeps and deletes personal data, under the Saudi Personal Data Protection Law.',
    'desc_ar' => 'كيف يجمع أويلي البيانات الشخصية ويستخدمها ويحفظها ويحذفها وفق نظام حماية البيانات الشخصية السعودي.',
    'updated_en' => '11 November 2026', 'updated_ar' => '11 نوفمبر 2026',
    'other' => ['terms.php', 'Terms of Use', 'شروط الاستخدام'],
], [
    ['who', 'Who we are', 'من نحن', [
        'Oweili (oweili.com) is an online gallery for real, handmade art, founded in Saudi Arabia and open to the world.',
        "Oweili is run by $CO, Jeddah, Saudi Arabia" . ($CR !== '' ? ", commercial registration $CR" : '') . '. We are the party responsible for your personal data under the Saudi Personal Data Protection Law (PDPL) and its regulations.',
        "You can reach us about anything in this policy at $C.",
    ], [
        'أويلي (oweili.com) معرض إلكتروني للفن الأصيل المصنوع باليد، انطلق من المملكة العربية السعودية ومفتوح للعالم.',
        "يُدار أويلي من قِبل $CO في جدة، المملكة العربية السعودية" . ($CR !== '' ? "، سجل تجاري رقم $CR" : '') . '. ونحن الجهة المسؤولة عن بياناتك الشخصية وفق نظام حماية البيانات الشخصية السعودي ولوائحه.',
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
        '- Stage photo details: we read the camera make, model and photo time of the stage photos, only to check that artworks are original. This is never shown publicly.',
        '- "I’m Interested" requests: the name, phone and email you type in, and the artwork you asked about.',
        '- Auctions waiting list: your email and, if you give it, your name. We use them only to tell you about Oweili auctions. Every email has a link to unsubscribe.',
        '- Gift requests: your name, phone and email, the occasion, the artwork or what you would like, your card message, the delivery date, and the recipient’s name, city, address and (if you give it) phone, used only to deliver the gift.',
        '- Private commission requests: your name, phone and email, what you would like, and your reference photos. The photos are kept in a private place visitors can never open. Only the Oweili team and the one artist we assign (after they accept our confidentiality agreement) can see them. Please send only photos you have the right to share. If a photo shows another person, ask for their permission first.',
        '- Messages to us: when you use "Write to Us", your name, email, phone (if you give it) and your message.',
        '- Artist signature: if an artist draws a signature for their certificates, we keep the picture in a private place. It is printed only on that artist’s certificates, after our team approves it.',
        '- Buyer profile: country, city, preferred language, and the artworks you save as favorites.',
        '- Activity: what you do in your account and when (for example signing in or saving a favorite). With it we keep a one-way code made from your internet address that changes every month. We never keep the address itself.',
        '- Visitor statistics: approximate visitor counts by country and city (from the internet address, using a location file on our own server, data by DB-IP). We keep only the country, city and date, never the address itself.',
        '- Photo consent records: for local artists featured in Artist Spotlights, a record of their approval (who, when and how).',
        '- Browser storage: a few small cookies and saved choices (see "Cookies and browser storage" below).',
        'We do not ask for sensitive data, such as health, religion, or family origin. Please do not send it to us.',
    ], [
        '- بيانات الحساب: الاسم والبريد الإلكتروني ورقم الهاتف وكلمة المرور. تُحفظ كلمات المرور كرمز آمن باتجاه واحد فقط، ولا تُحفظ كنص أبداً.',
        '- ملف الفنان: الصورة الشخصية واسم الفنان والنبذة بالعربية والإنجليزية والمدينة والدولة والأساليب الفنية وروابط التواصل. ويمكن للفنان إرسال صور من أعماله وفيديو اختياري عند التسجيل.',
        '- صور الأعمال: صور كل عمل عند 25% و50% و100% مع تاريخ التقاط كل صورة. نحذف البيانات المخفية مثل الموقع الجغرافي من كل صورة قبل حفظها.',
        '- تفاصيل صور المراحل: نقرأ نوع الكاميرا وطرازها ووقت التقاط صور المراحل، فقط للتأكد من أن الأعمال أصلية. ولا يُعرض ذلك للعموم أبداً.',
        '- طلبات «أنا مهتم»: الاسم والهاتف والبريد الذي تكتبه، والعمل الذي سألت عنه.',
        '- قائمة انتظار المزاد: بريدك الإلكتروني واسمك إن أعطيته. نستخدمهما فقط لإخبارك بمزادات أويلي. وفي كل رسالة رابط لإلغاء الاشتراك.',
        '- طلبات الهدايا: اسمك وهاتفك وبريدك، والمناسبة، والعمل أو ما ترغب فيه، ورسالة البطاقة، وتاريخ التوصيل، واسم المُهدى إليه ومدينته وعنوانه وهاتفه (إن أعطيته)، وتُستخدم لتوصيل الهدية فقط.',
        '- طلبات الأعمال الخاصة: اسمك وهاتفك وبريدك، وما ترغب فيه، وصورك المرجعية. تُحفظ الصور في مكان خاص لا يفتحه الزوار أبداً، ولا يراها إلا فريق أويلي والفنان الوحيد الذي نسند إليه العمل (بعد قبوله اتفاقية السرّية). يرجى إرسال الصور التي يحق لك مشاركتها فقط، وإن ظهر فيها شخص آخر فاطلب إذنه أولاً.',
        '- رسائلك إلينا: حين تستخدم «اكتب لنا»، نحفظ اسمك وبريدك وهاتفك (إن أعطيته) ورسالتك.',
        '- توقيع الفنان: إن رسم الفنان توقيعاً لشهاداته، نحفظ صورته في مكان خاص. ولا يُطبع إلا على شهادات هذا الفنان، بعد موافقة فريقنا.',
        '- ملف المشتري: الدولة والمدينة واللغة المفضلة والأعمال التي تحفظها في المفضلة.',
        '- النشاط: ما تفعله في حسابك ومتى (مثل تسجيل الدخول أو حفظ عمل في المفضلة). ونحفظ معه رمزاً باتجاه واحد من عنوانك على الإنترنت يتغير كل شهر، ولا نحفظ العنوان نفسه أبداً.',
        '- إحصاءات الزوار: أعداد تقريبية للزوار حسب الدولة والمدينة (من عنوان الإنترنت، بملف مواقع على خادمنا، والبيانات من DB-IP). نحفظ الدولة والمدينة والتاريخ فقط، ولا نحفظ العنوان نفسه أبداً.',
        '- سجلات موافقة الصور: للفنانين المحليين في «أضواء على الفنانين»، سجل بموافقتهم (من ومتى وكيف).',
        '- تخزين المتصفح: بضعة ملفات تعريف ارتباط صغيرة واختيارات محفوظة (راجع «ملفات تعريف الارتباط والتخزين في المتصفح» أدناه).',
        'لا نطلب بيانات حساسة، مثل الصحة أو الدين أو الأصل العائلي. يرجى عدم إرسالها إلينا.',
    ]],
    ['why', 'Why we use it', 'لماذا نستخدمها', [
        '- To run your account and let you sign in safely.',
        '- To show artworks and the creation journey in the Gallery.',
        '- To review every artwork and keep Oweili for handmade art only.',
        '- To contact you about an artwork you asked about, and to tell an artist that someone is interested.',
        '- To send account emails, such as confirming your email or resetting your password.',
        '- To keep the site safe: limits on sign-ups and sign-ins, and the activity log.',
        '- To answer the messages you send us.',
        'We rely on your consent, on what is needed to give you the service you asked for, on our legitimate interest in keeping the site safe and stopping fraud, and on what the law asks of us. You can withdraw your consent at any time.',
        'We send you news only when you ask for it, such as the Auctions waiting list, and every such email has a link to stop it.',
        'We do not sell your data. We do not use it for advertising profiles. We do not make decisions about you by automatic means alone.',
    ], [
        '- لتشغيل حسابك وتمكينك من الدخول بأمان.',
        '- لعرض الأعمال ورحلة الإبداع في المعرض.',
        '- لمراجعة كل عمل والحفاظ على أويلي للفن المصنوع باليد فقط.',
        '- للتواصل معك بخصوص عمل سألت عنه، ولإخبار الفنان أن هناك من يهتم.',
        '- لإرسال رسائل الحساب، مثل تأكيد البريد أو إعادة تعيين كلمة المرور.',
        '- لحماية الموقع: حدود على التسجيل والدخول، وسجل النشاط.',
        '- للرد على الرسائل التي ترسلها إلينا.',
        'نعتمد على موافقتك، وعلى ما يلزم لتقديم الخدمة التي طلبتها، وعلى مصلحتنا المشروعة في حماية الموقع ومنع الاحتيال، وعلى ما يفرضه النظام. ويمكنك سحب موافقتك في أي وقت.',
        'لا نرسل إليك الأخبار إلا حين تطلبها، مثل قائمة انتظار المزاد، وفي كل رسالة من هذا النوع رابط لإيقافها.',
        'لا نبيع بياناتك، ولا نستخدمها لبناء ملفات إعلانية، ولا نتخذ قرارات بشأنك بوسائل آلية وحدها.',
    ]],
    ['share', 'Who can see it', 'من يستطيع رؤيتها', [
        '- Everyone: what an artist chooses to make public (artist name, profile photo, bio, city, country, art styles, social links and approved artworks).',
        '- The Oweili team: the details needed to review artworks and answer requests. Only the admin sees full personal details.',
        '- Companies that help us run the site, only for that job: our hosting and email provider (Hostinger). They may not use your data for anything else.',
        '- Bashro, Oweili’s own internal control center, so our team can answer you. When you send an "I’m Interested", gift or commission request or a "Write to Us" message, or join the Auctions waiting list, its details (your name, email, phone, what you asked for, and for gifts only the recipient’s city) go to Bashro through a private, signed connection. Commission photos and recipient addresses are never sent. A second, read-only connection is switched off until we turn it on.',
        '- Authorities, only when Saudi law requires it.',
        'Our hosting provider may keep data on servers outside the Kingdom. When that happens we follow the PDPL rules and safeguards for sending data abroad, and we send only what is needed.',
    ], [
        '- الجميع: ما يختار الفنان إظهاره (اسم الفنان والصورة الشخصية والنبذة والمدينة والدولة والأساليب الفنية وروابط التواصل والأعمال المعتمدة).',
        '- فريق أويلي: ما يلزم لمراجعة الأعمال والرد على الطلبات. والمدير وحده يرى البيانات الشخصية كاملة.',
        '- الشركات التي تساعدنا في تشغيل الموقع، لهذه المهمة فقط: مزود الاستضافة والبريد (Hostinger). ولا يحق لها استخدام بياناتك لأي غرض آخر.',
        '- «بشرو»، مركز التحكم الداخلي الخاص بأويلي، ليتمكن فريقنا من الرد عليك. عندما ترسل طلب «أنا مهتم» أو طلب هدية أو عمل خاص أو رسالة «اكتب لنا»، أو تنضم إلى قائمة انتظار المزاد، تصل تفاصيله (اسمك وبريدك وهاتفك وما طلبته، ومدينة المُهدى إليه فقط في الهدايا) إلى بشرو عبر ربط خاص وموقّع. ولا تُرسل صور الأعمال الخاصة ولا عناوين المُهدى إليهم أبداً. وهناك ربط ثانٍ للقراءة فقط، متوقف حتى نشغّله.',
        '- الجهات الرسمية، فقط حين يفرض النظام السعودي ذلك.',
        'قد يحفظ مزود الاستضافة البيانات على خوادم خارج المملكة. وحين يحدث ذلك نتبع قواعد نظام حماية البيانات الشخصية وضماناته لنقل البيانات إلى الخارج، ولا ننقل إلا ما يلزم.',
    ]],
    ['keep', 'How long we keep it', 'كم من الوقت نحفظها', [
        '- Your account and profile: for as long as your account is open. When you ask us to delete it, we delete it within 30 days.',
        '- Artwork photos: while the artwork is on Oweili. When an artwork or account is deleted, its photos are deleted too.',
        '- "I’m Interested" requests: up to 2 years after the last contact, then deleted.',
        '- Commission reference photos: deleted automatically 30 days after delivery (or 30 days after a cancel).',
        '- Messages to us: up to 2 years after our last reply, then deleted.',
        '- Artist signature: until the artist removes it or the account is deleted. Certificates already issued keep the signature they were issued with.',
        '- Auctions waiting list: until you unsubscribe. Unsubscribing deletes your email from the list straight away.',
        '- Activity log: up to 12 months.',
        '- Database backups: up to 90 days, then replaced by newer ones.',
        '- Photo consent records: for as long as the photos are published, and up to 5 years after, so we can show the approval if asked.',
        '- Records the law asks us to keep (for example sales records, once payments open) are kept for the time the law sets.',
    ], [
        '- حسابك وملفك: ما دام حسابك مفتوحاً. وحين تطلب حذفه نحذفه خلال 30 يوماً.',
        '- صور الأعمال: ما دام العمل على أويلي. وحين يُحذف العمل أو الحساب تُحذف صوره أيضاً.',
        '- طلبات «أنا مهتم»: حتى سنتين بعد آخر تواصل، ثم تُحذف.',
        '- الصور المرجعية للأعمال الخاصة: تُحذف تلقائياً بعد 30 يوماً من التسليم (أو 30 يوماً من الإلغاء).',
        '- رسائلك إلينا: حتى سنتين بعد آخر رد منا، ثم تُحذف.',
        '- توقيع الفنان: حتى يحذفه الفنان أو يُحذف الحساب. وتحتفظ الشهادات الصادرة سابقاً بالتوقيع الذي صدرت به.',
        '- قائمة انتظار المزاد: حتى تلغي اشتراكك. وإلغاء الاشتراك يحذف بريدك من القائمة فوراً.',
        '- سجل النشاط: حتى 12 شهراً.',
        '- النسخ الاحتياطية لقاعدة البيانات: حتى 90 يوماً، ثم تُستبدل بنسخ أحدث.',
        '- سجلات موافقة الصور: ما دامت الصور منشورة، وحتى 5 سنوات بعد ذلك، لنثبت الموافقة عند الطلب.',
        '- السجلات التي يفرض النظام حفظها (مثل سجلات البيع بعد فتح المدفوعات) تُحفظ للمدة التي يحددها النظام.',
    ]],
    ['rights', 'Your rights', 'حقوقك', [
        'Under the PDPL you have the right to:',
        '- know what data we hold about you and why;',
        '- get a copy of your data in a clear, readable form;',
        '- correct data that is wrong or out of date (most of it you can change yourself in your dashboard);',
        '- ask us to delete your data;',
        '- withdraw your consent at any time.',
        "To use any of these rights, write to $C from the email of your account. We may ask you to confirm it is you. We answer within 30 days.",
        'If you are not happy with our answer, you can complain to the Saudi Data and Artificial Intelligence Authority (SDAIA).',
    ], [
        'وفق نظام حماية البيانات الشخصية يحق لك:',
        '- أن تعرف ما نحفظه من بياناتك ولماذا؛',
        '- أن تحصل على نسخة من بياناتك بصيغة واضحة ومقروءة؛',
        '- أن تصحح البيانات الخاطئة أو القديمة (ويمكنك تعديل أغلبها بنفسك من لوحتك)؛',
        '- أن تطلب حذف بياناتك؛',
        '- أن تسحب موافقتك في أي وقت.',
        "لاستخدام أي من هذه الحقوق، راسلنا على $C من بريد حسابك. وقد نطلب منك تأكيد هويتك. نرد خلال 30 يوماً.",
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
        'No system is perfect. If a breach ever affects your data, we tell the Saudi Data and Artificial Intelligence Authority (SDAIA) within 72 hours of learning about it, and we tell you without delay when it may harm you, as the PDPL requires.',
    ], [
        'كل صفحة تعمل باتصال آمن (HTTPS). كلمات المرور محفوظة كرموز باتجاه واحد. الملفات الخاصة، مثل إثباتات الموافقة والنسخ الاحتياطية، محجوبة عن العامة. والمدير وحده يرى البيانات الشخصية كاملة، وكل إجراء من الفريق مسجّل.',
        'لا يوجد نظام كامل. وإن تأثرت بياناتك بأي اختراق يوماً، نبلغ الهيئة السعودية للبيانات والذكاء الاصطناعي (سدايا) خلال 72 ساعة من علمنا به، ونبلغك دون تأخير إن كان قد يضرك، كما يفرض النظام.',
    ]],
    ['cookies', 'Cookies and browser storage', 'ملفات تعريف الارتباط والتخزين في المتصفح', [
        'We use only the small cookies the site needs. We use no advertising cookies and no tracking by other companies.',
        '- A sign-in cookie, so the site knows it is you from page to page. It lasts up to 7 days, or until you sign out.',
        '- ow_lang: your language choice, kept for one year.',
        '- ow_seen: only today’s date, so a visit is counted once a day in our visitor statistics.',
        '- ow_nostat: only on the Oweili team’s own browsers, so their visits are not counted.',
        '- Your light or dark mode choice and your language choice are also saved in your own browser (local storage).',
        'You can delete cookies in your browser settings at any time. The site still works, but you may need to choose your language again.',
    ], [
        'نستخدم فقط ملفات تعريف الارتباط الصغيرة التي يحتاجها الموقع. لا نستخدم ملفات إعلانية، ولا تتبعاً من شركات أخرى.',
        '- ملف لتسجيل الدخول، ليعرفك الموقع من صفحة إلى أخرى. يبقى حتى 7 أيام، أو حتى تسجل الخروج.',
        '- ow_lang: اختيارك للغة، ويُحفظ سنة واحدة.',
        '- ow_seen: تاريخ اليوم فقط، لتُحسب الزيارة مرة واحدة في اليوم في إحصاءات الزوار.',
        '- ow_nostat: على متصفحات فريق أويلي فقط، كي لا تُحسب زياراتهم.',
        '- ويُحفظ اختيارك للوضع الليلي أو النهاري ولغتك أيضاً في متصفحك أنت (التخزين المحلي).',
        'يمكنك حذف ملفات تعريف الارتباط من إعدادات متصفحك في أي وقت. ويبقى الموقع يعمل، لكن قد تحتاج إلى اختيار لغتك من جديد.',
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
