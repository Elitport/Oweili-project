<?php
/* =====================================================================
 *  terms.php — Terms of Use («شروط الاستخدام»). Reviewed 11 Nov 2026 (no lawyer yet).
 *  Covers: Oweili as the middle party, accounts, handmade art only,
 *  proof of process, artwork rights, the commission (Admin → Settings), conduct,
 *  removal, liability, Saudi law. Layout: legal_lib.php. Tables: none
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/page_cache.php'; owPublicPage();   // public page: Back opens at once, early load on touch (page_cache.php)
require_once __DIR__ . '/legal_lib.php';
require_once __DIR__ . '/platform_lib.php';   // owCommissionText: the commission from Admin → Settings
$C = OW_LEGAL_CONTACT;
$CO = OW_COMPANY_NAME; $CR = function_exists('owCompanyCr') ? owCompanyCr() : '';
require_once __DIR__ . '/cert_lib.php';   // OW_CERT_RIGHTS: the rights line of the certificate

owLegalPage([
    'path' => 'terms.php',
    'title_en' => 'Terms of Use', 'title_ar' => 'شروط الاستخدام', 'centered_head' => true,
    'lead_en' => 'The simple rules that keep Oweili a trusted home for real, handmade art.',
    'lead_ar' => 'القواعد البسيطة التي تجعل أويلي بيتاً موثوقاً للفن الأصيل المصنوع باليد.',
    'desc_en' => 'The rules for artists, collectors and visitors on Oweili, the gallery of handmade art.',
    'desc_ar' => 'القواعد للفنانين والمقتنين والزوار في أويلي، معرض الفن الأصيل المصنوع باليد.',
    'updated_en' => '11 November 2026', 'updated_ar' => '11 نوفمبر 2026',
    'other' => ['privacy.php', 'Privacy Policy', 'سياسة الخصوصية'],
], [
    ['agree', 'Using Oweili', 'استخدام أويلي', [
        "Oweili (oweili.com) is run by $CO, Jeddah, Saudi Arabia" . ($CR !== '' ? ", commercial registration $CR" : '') . ". You can reach us at $C. By using the site or opening an account, you agree to these terms and to our Privacy Policy.",
        'If you do not agree, please do not use the site.',
    ], [
        "يُدار أويلي (oweili.com) من قِبل $CO في جدة، المملكة العربية السعودية" . ($CR !== '' ? "، سجل تجاري رقم $CR" : '') . ". ويمكنك التواصل معنا على $C. باستخدامك الموقع أو فتح حساب، فإنك توافق على هذه الشروط وعلى سياسة الخصوصية.",
        'إن لم توافق، يرجى عدم استخدام الموقع.',
    ]],
    ['middle', 'Oweili is the middle party', 'أويلي هو الوسيط', [
        'Oweili connects artists and collectors. When you press "I’m Interested", your request comes to the Oweili team, and we handle the contact between you and the artist.',
        'Artists and collectors agree not to use Oweili to take a sale outside Oweili, and not to ask each other for private contact details through the site.',
        'Pressing "I’m Interested" is not a purchase and does not oblige you to buy. Online payments are not open yet. Until they open, our team agrees each sale with both sides.',
    ], [
        'يربط أويلي بين الفنانين والمقتنين. حين تضغط «أنا مهتم» يصل طلبك إلى فريق أويلي، ونتولى التواصل بينك وبين الفنان.',
        'يتعهد الفنانون والمقتنون بعدم استخدام أويلي لإتمام البيع خارجه، وعدم طلب بيانات التواصل الخاصة من بعضهم عبر الموقع.',
        'الضغط على «أنا مهتم» ليس شراءً ولا يُلزمك بالشراء. المدفوعات الإلكترونية لم تُفتح بعد، وحتى تُفتح يتفق فريقنا على كل عملية بيع مع الطرفين.',
    ]],
    ['accounts', 'Your account', 'حسابك', [
        '- You must be 18 or over, or use Oweili with a parent or guardian who agrees for you.',
        '- Give true details and keep them up to date.',
        '- Keep your password private. You are responsible for what happens in your account.',
        '- One person, one account. Artists sign up as themselves, never for someone else.',
    ], [
        '- يجب أن يكون عمرك 18 عاماً فأكثر، أو أن تستخدم أويلي مع ولي أمر يوافق عنك.',
        '- قدّم بيانات صحيحة وحدّثها.',
        '- احفظ كلمة مرورك سراً. وأنت مسؤول عما يحدث في حسابك.',
        '- شخص واحد، حساب واحد. ويسجّل الفنان باسمه هو، لا عن غيره.',
    ]],
    ['handmade', 'Handmade art only', 'فن مصنوع باليد فقط', [
        'Oweili is the home of real art. Every artwork must be made by the artist’s own hands.',
        '- Work made by machines that create images on their own is not allowed, in any part of the artwork.',
        '- The 25%, 50% and 100% photos must be real photos of your real artwork, taken through the Oweili website camera, at the time they say.',
        '- Do not upload work by another person, copies, prints sold as originals, or photos found online.',
        'Every artwork is reviewed by our team before it appears in the Gallery. We may ask questions or more photos, and we may decline a work and tell you why.',
    ], [
        'أويلي بيت الفن الأصيل. ويجب أن يكون كل عمل من صنع يد الفنان نفسه.',
        '- لا يُسمح بالأعمال التي تصنعها الآلات التي تولّد الصور بنفسها، في أي جزء من العمل.',
        '- يجب أن تكون صور 25% و50% و100% صوراً حقيقية لعملك الحقيقي، ملتقطة عبر كاميرا موقع أويلي، وفي الوقت الذي تشير إليه.',
        '- لا ترفع أعمال غيرك، أو نسخاً، أو مطبوعات تُباع كأصلية، أو صوراً من الإنترنت.',
        'يراجع فريقنا كل عمل قبل ظهوره في المعرض. وقد نطرح أسئلة أو نطلب صوراً إضافية، وقد نعتذر عن عمل ونخبرك بالسبب.',
    ]],
    ['rights', 'Your artwork stays yours', 'عملك يبقى ملكك', [
        'You keep every right in your artwork. You give Oweili permission to show its photos, title and story on the site and in Oweili’s own posts and ads, only to present your work, for as long as it is on Oweili.',
        'When you delete an artwork, we stop showing it. Posts already shared may stay where they are.',
        'Visitors may not copy, download or reuse artwork photos without the artist’s permission.',
        'When an artwork is sold: ' . lcfirst(OW_CERT_RIGHTS[0]),
    ], [
        'تبقى لك كل الحقوق في عملك. وتمنح أويلي الإذن بعرض صوره وعنوانه وقصته على الموقع وفي منشورات أويلي وإعلاناته، لتقديم عملك فقط، ما دام على أويلي.',
        'حين تحذف عملاً نتوقف عن عرضه. وقد تبقى المنشورات التي سبق نشرها في أماكنها.',
        'لا يحق للزوار نسخ صور الأعمال أو تنزيلها أو إعادة استخدامها دون إذن الفنان.',
        'عند بيع العمل: ' . OW_CERT_RIGHTS[1],
    ]],
    ['price', 'Prices and commission', 'الأسعار والعمولة', [
        'Artists set their own prices in Saudi riyals (SAR).',
        'When an artwork sells, Oweili keeps ' . owCommissionText() . ' of the price, before VAT. This covers shipping and advertising. Joining and showing your work is free.',
        'Once online payments open, you are paid your share after the collector receives the artwork. You are responsible for any tax that applies to your own income.',
    ], [
        'يحدد الفنان أسعار أعماله بالريال السعودي.',
        'حين يُباع عمل، يحتفظ أويلي بـ' . owCommissionText() . ' من السعر قبل ضريبة القيمة المضافة، وتغطي الشحن والإعلان. والانضمام وعرض أعمالك مجاني.',
        'حين تُفتح المدفوعات الإلكترونية، تحصل على حصتك بعد أن يستلم المقتني العمل. وأنت مسؤول عن أي ضريبة تنطبق على دخلك.',
    ]],
    ['buying', 'Buying an artwork', 'شراء عمل فني', [
        '- Prices are shown in Saudi riyals (SAR). Before you pay, our team confirms the full total with you in writing: the price, any VAT, any delivery cost, and the expected delivery date.',
        '- You receive an invoice for every sale.',
        '- Your right to cancel: under the Saudi E-Commerce Law, you may cancel within 7 days of receiving the artwork, if it is unused and in the condition you received it. You pay the return shipping, unless the artwork arrived damaged or different from its description.',
        '- This right to cancel does not apply to a private commission made to your own description.',
        '- If the artwork arrives damaged or different from its description, tell us within 7 days of delivery, with photos. We arrange its return and give you a full refund.',
        '- If a ready artwork is delivered more than 15 days later than the date we agreed, you may cancel and get your money back, unless the delay is caused by something outside our control. This does not apply to private commissions, which will have their own terms.',
        "- Questions or complaints about a purchase: write to us first at $C. You can also contact the Ministry of Commerce.",
    ], [
        '- تُعرض الأسعار بالريال السعودي. وقبل أن تدفع، يؤكد لك فريقنا كتابياً المبلغ الكامل: السعر وأي ضريبة قيمة مضافة وأي تكلفة توصيل وموعد التوصيل المتوقع.',
        '- تحصل على فاتورة لكل عملية بيع.',
        '- حقك في الإلغاء: وفق نظام التجارة الإلكترونية السعودي، يمكنك الإلغاء خلال 7 أيام من استلام العمل، إن لم يُستخدم وبقي بالحالة التي استلمته بها. وتتحمل تكلفة شحن الإرجاع، إلا إن وصل العمل تالفاً أو مختلفاً عن وصفه.',
        '- لا ينطبق حق الإلغاء على العمل الخاص المصنوع وفق وصفك أنت.',
        '- إن وصل العمل تالفاً أو مختلفاً عن وصفه، أخبرنا خلال 7 أيام من التسليم مع صور. ونرتب إرجاعه ونرد لك المبلغ كاملاً.',
        '- إن تأخر توصيل عمل جاهز أكثر من 15 يوماً عن الموعد المتفق عليه، يمكنك الإلغاء واسترداد مالك، إلا إن كان التأخير لسبب خارج عن إرادتنا. ولا ينطبق ذلك على الأعمال الخاصة، فلها شروطها الخاصة.',
        "- للأسئلة أو الشكاوى عن عملية شراء: راسلنا أولاً على $C. ويمكنك أيضاً التواصل مع وزارة التجارة.",
    ]],
    ['site', 'Oweili’s own content', 'محتوى أويلي', [
        'The Oweili name, logo, design and our own texts belong to Oweili. Please do not copy them without our permission.',
        'Links to other websites are there to help you. We are not responsible for their content.',
    ], [
        'اسم أويلي وشعاره وتصميمه ونصوصنا ملك لأويلي. يرجى عدم نسخها دون إذننا.',
        'الروابط إلى مواقع أخرى موجودة لمساعدتك، ولسنا مسؤولين عن محتواها.',
    ]],
    ['conduct', 'Be kind and fair', 'كن لطيفاً وعادلاً', [
        '- Respect other people and Saudi law and values.',
        '- No false, hurtful or offensive content.',
        '- No attempts to break, overload or get around the site’s safety measures.',
        '- No automatic copying of the site or its content.',
    ], [
        '- احترم الآخرين وأنظمة المملكة وقيمها.',
        '- لا محتوى كاذباً أو جارحاً أو مسيئاً.',
        '- لا محاولات لتعطيل الموقع أو إثقاله أو تجاوز وسائل حمايته.',
        '- لا نسخ آلي للموقع أو محتواه.',
    ]],
    ['removal', 'When we may step in', 'متى قد نتدخل', [
        'We may hide an artwork, or suspend or close an account, when these terms are broken or when the law asks us to. When we can, we tell you why first and give you a chance to fix it.',
        'You can close your account at any time. See "How to delete your data" in the Privacy Policy.',
    ], [
        'قد نخفي عملاً أو نوقف حساباً أو نغلقه عند مخالفة هذه الشروط أو حين يطلب النظام ذلك. ومتى أمكن نخبرك بالسبب أولاً ونمنحك فرصة للتصحيح.',
        'يمكنك إغلاق حسابك في أي وقت. راجع «كيف تحذف بياناتك» في سياسة الخصوصية.',
    ]],
    ['liability', 'Our responsibility', 'مسؤوليتنا', [
        'We work hard to keep Oweili safe, true and working. Still, the site is offered as it is, and it may sometimes be unavailable.',
        'Artists are responsible for their artworks and what they say about them. As far as Saudi law allows, Oweili is not responsible for indirect losses from using the site.',
    ], [
        'نعمل بجد ليبقى أويلي آمناً وصادقاً ويعمل جيداً. ومع ذلك يُقدَّم الموقع كما هو، وقد يتوقف أحياناً.',
        'الفنانون مسؤولون عن أعمالهم وما يقولونه عنها. وبالقدر الذي يسمح به النظام السعودي، لا يتحمل أويلي الخسائر غير المباشرة الناتجة عن استخدام الموقع.',
    ]],
    ['law', 'Saudi law', 'النظام السعودي', [
        'These terms follow the laws of the Kingdom of Saudi Arabia. If there is a dispute, we will first try to solve it together, kindly. If that does not work, the competent courts in Saudi Arabia decide.',
        'If the English and Arabic versions ever differ, the Arabic version applies.',
    ], [
        'تخضع هذه الشروط لأنظمة المملكة العربية السعودية. وعند أي خلاف نحاول أولاً حله معاً بالحسنى، فإن لم يتيسر تختص به المحاكم المختصة في المملكة.',
        'وإن اختلفت النسختان العربية والإنجليزية يوماً، تُعتمد النسخة العربية.',
    ]],
    ['contact', 'Changes and contact', 'التغييرات والتواصل', [
        "We may update these terms. When a change is important, we tell you first. Questions are always welcome at $C.",
    ], [
        "قد نحدّث هذه الشروط، وإن كان التغيير مهماً نخبرك أولاً. ونرحب بأسئلتك دائماً على $C.",
    ]],
]);
