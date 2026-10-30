<?php
/* =====================================================================
 *  terms.php — Terms of Use («شروط الاستخدام»), DRAFT under review.
 *  Covers: Oweili as the middle party, accounts, human-made art only,
 *  proof of process, artwork rights, the 15% commission, conduct,
 *  removal, liability, Saudi law. Layout: legal_lib.php. Tables: none
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/legal_lib.php';
$C = OW_LEGAL_CONTACT;

owLegalPage([
    'path' => 'terms.php',
    'title_en' => 'Terms of Use', 'title_ar' => 'شروط الاستخدام',
    'lead_en' => 'The simple rules that keep Oweili a trusted home for real, human-made art.',
    'lead_ar' => 'القواعد البسيطة التي تجعل أويلي بيتاً موثوقاً للفن الحقيقي المصنوع بأيدٍ بشرية.',
    'desc_en' => 'The rules for artists, buyers and visitors on Oweili, the gallery of human-made art.',
    'desc_ar' => 'القواعد للفنانين والمشترين والزوار في أويلي، معرض الفن المصنوع بأيدٍ بشرية.',
    'updated_en' => '31 October 2026 (draft)', 'updated_ar' => '31 أكتوبر 2026 (مسودة)',
    'other' => ['privacy.php', 'Privacy Policy', 'سياسة الخصوصية'],
], [
    ['agree', 'Using Oweili', 'استخدام أويلي', [
        'Oweili (oweili.com) is run from Saudi Arabia. By using the site or opening an account, you agree to these terms and to our Privacy Policy.',
        'If you do not agree, please do not use the site.',
    ], [
        'يُدار أويلي (oweili.com) من المملكة العربية السعودية. باستخدامك الموقع أو فتح حساب، فإنك توافق على هذه الشروط وعلى سياسة الخصوصية.',
        'إن لم توافق، يرجى عدم استخدام الموقع.',
    ]],
    ['middle', 'Oweili is the middle party', 'أويلي هو الوسيط', [
        'Oweili connects artists and buyers. When you press "I’m Interested", your request comes to the Oweili team, and we handle the contact between you and the artist.',
        'Artists and buyers agree not to use Oweili to take a sale outside Oweili, and not to ask each other for private contact details through the site.',
        'Pressing "I’m Interested" is not a purchase and does not oblige you to buy. Online payments are not open yet. Until they open, our team agrees each sale with both sides.',
    ], [
        'يربط أويلي بين الفنانين والمشترين. حين تضغط «أنا مهتم» يصل طلبك إلى فريق أويلي، ونتولى التواصل بينك وبين الفنان.',
        'يتعهد الفنانون والمشترون بعدم استخدام أويلي لإتمام البيع خارجه، وعدم طلب بيانات التواصل الخاصة من بعضهم عبر الموقع.',
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
    ['human', 'Human-made art only', 'فن بشري فقط', [
        'Oweili is the home of real human art. Every artwork must be made by the artist’s own hands.',
        '- Work made by machines that create images on their own is not allowed, in any part of the artwork.',
        '- The 25%, 50% and 100% photos must be real photos of your real artwork, taken through the Oweili website camera, at the time they say.',
        '- Do not upload work by another person, copies, prints sold as originals, or photos found online.',
        'Every artwork is reviewed by our team before it appears in the Gallery. We may ask questions or more photos, and we may decline a work and tell you why.',
    ], [
        'أويلي بيت الفن البشري الحقيقي. ويجب أن يكون كل عمل من صنع يد الفنان نفسه.',
        '- لا يُسمح بالأعمال التي تصنعها الآلات التي تولّد الصور بنفسها، في أي جزء من العمل.',
        '- يجب أن تكون صور 25% و50% و100% صوراً حقيقية لعملك الحقيقي، ملتقطة عبر كاميرا موقع أويلي، وفي الوقت الذي تشير إليه.',
        '- لا ترفع أعمال غيرك، أو نسخاً، أو مطبوعات تُباع كأصلية، أو صوراً من الإنترنت.',
        'يراجع فريقنا كل عمل قبل ظهوره في المعرض. وقد نطرح أسئلة أو نطلب صوراً إضافية، وقد نعتذر عن عمل ونخبرك بالسبب.',
    ]],
    ['rights', 'Your artwork stays yours', 'عملك يبقى ملكك', [
        'You keep every right in your artwork. You give Oweili permission to show its photos, title and story on the site and in Oweili’s own posts and ads, only to present your work, for as long as it is on Oweili.',
        'When you delete an artwork, we stop showing it. Posts already shared may stay where they are.',
        'Visitors may not copy, download or reuse artwork photos without the artist’s permission.',
    ], [
        'تبقى لك كل الحقوق في عملك. وتمنح أويلي الإذن بعرض صوره وعنوانه وقصته على الموقع وفي منشورات أويلي وإعلاناته، لتقديم عملك فقط، ما دام على أويلي.',
        'حين تحذف عملاً نتوقف عن عرضه. وقد تبقى المنشورات التي سبق نشرها في أماكنها.',
        'لا يحق للزوار نسخ صور الأعمال أو تنزيلها أو إعادة استخدامها دون إذن الفنان.',
    ]],
    ['price', 'Prices and commission', 'الأسعار والعمولة', [
        'Artists set their own prices in Saudi riyals (SAR).',
        'When an artwork sells, Oweili keeps 15% of the price, before VAT. This covers shipping and advertising. Joining and showing your work is free.',
        'The full payment, shipping and refund rules will be added here before online payments open.',
    ], [
        'يحدد الفنان أسعار أعماله بالريال السعودي.',
        'حين يُباع عمل، يحتفظ أويلي بـ15% من السعر قبل ضريبة القيمة المضافة، وتغطي الشحن والإعلان. والانضمام وعرض أعمالك مجاني.',
        'ستُضاف هنا قواعد الدفع والشحن والاسترجاع كاملة قبل فتح المدفوعات الإلكترونية.',
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
