<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>The Human Hand · Oweili</title>
<meta name="description" content="Oweili celebrates art made by people. Here are four great families of human art. Each one shows a different bond between artist and material. We will show you how to spot the living touch in every real work. يحتفي أويلي بالفن الذي تصنعه أيدي البشر. هنا أربع عائلات كبرى من الفن الإنساني. لكل منها علاقة مختلفة بين الفنان وخامته. وسنريك كيف تلمح اللمسة الحيّة في كل عمل أصيل.">
<link rel="stylesheet" href="/assets/css/oweili.css?v=<?= @filemtime(__DIR__ . '/assets/css/oweili.css') ?>">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:var(--color-bg);font-family:var(--font-sans);overflow-x:hidden;}
.page{position:relative;z-index:10;width:100%;max-width:1040px;margin:0 auto;padding:100px 24px 64px;}
.page-header{margin-bottom:40px;text-align:center;}
.page-badge{display:inline-flex;align-items:center;gap:8px;padding:5px 14px;border-radius:999px;margin-bottom:16px;background:rgba(var(--rgb-accent),0.06);border:1px solid rgba(var(--rgb-accent),0.15);font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:var(--color-accent);}
.pulse-dot{width:6px;height:6px;border-radius:50%;background:var(--color-accent);box-shadow:0 0 8px rgba(var(--rgb-accent),0.5);animation:pulse 2s infinite;}
.page-header h1{font-size:clamp(30px,5vw,52px);font-weight:700;color:var(--color-dark);margin-bottom:14px;letter-spacing:-0.02em;}
.page-header h1 em{font-style:normal;font-weight:700;color:var(--color-highlight);}
.page-header p{font-size:var(--fs-body);color:var(--color-body);line-height:1.9;max-width:660px;margin:0 auto;}
.cats{display:flex;flex-direction:column;gap:30px;}
.cat{background:var(--color-surface);border:1px solid rgba(var(--rgb-ink),0.07);border-radius:var(--radius-card);padding:38px 34px;position:relative;overflow:hidden;box-shadow:var(--shadow-card);}
.cat::before{content:"";position:absolute;top:0;left:0;width:5px;height:100%;background:var(--accent);}
[dir="rtl"] .cat::before{left:auto;right:0;}
.cat-num{font-size:13px;font-weight:700;letter-spacing:0.2em;color:var(--accent);margin-bottom:10px;}
.cat-tag{display:inline-block;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;background:var(--accent-soft);color:var(--accent);margin-bottom:12px;}
.cat-title{font-size:clamp(22px,3vw,30px);font-weight:700;color:var(--color-dark);margin-bottom:22px;line-height:1.2;}
.block{margin-bottom:22px;}
.block:last-child{margin-bottom:0;}
.block-label{font-size:12px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:var(--color-body);margin-bottom:10px;display:flex;align-items:center;gap:8px;}
.block-label::before{content:"";width:18px;height:2px;background:var(--accent);border-radius:2px;}
.block p{font-size:var(--fs-body);line-height:1.95;color:var(--color-body);}
.tech-chips{display:flex;flex-wrap:wrap;gap:9px;}
.chip{padding:7px 15px;border-radius:999px;font-size:13px;font-weight:600;background:rgba(var(--rgb-ink),0.045);border:1px solid rgba(var(--rgb-ink),0.08);color:var(--color-dark);}
.soul{background:var(--accent-soft);border:1px solid var(--accent-line);border-radius:16px;padding:18px 20px;}
.soul .soul-lead{font-size:var(--fs-body);font-weight:700;color:var(--accent);margin-bottom:8px;}
.soul p{font-size:var(--fs-body);line-height:1.9;color:var(--color-body);}
.closing{margin-top:var(--space-xl);text-align:center;font-size:var(--fs-body);line-height:1.7;color:var(--color-body);max-width:660px;margin-left:auto;margin-right:auto;}
[dir="rtl"] .block p,[dir="rtl"] .soul p,[dir="rtl"] .cat-title{text-align:right;}
.sec-h{margin-top:52px;text-align:center;font-size:clamp(24px,3.4vw,34px);font-weight:300;color:var(--color-dark);letter-spacing:-0.02em;}
.sec-h em{font-style:normal;font-weight:700;color:var(--color-highlight);}
.sec-sub{text-align:center;font-size:var(--fs-body);color:var(--color-body);line-height:1.7;max-width:640px;margin:12px auto 30px;}
.mods{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px;}
.mod{background:var(--color-surface);border:1px solid rgba(var(--rgb-ink),0.07);border-radius:var(--radius-card);padding:24px;display:flex;flex-direction:column;gap:10px;transition:transform 0.3s ease,box-shadow 0.3s ease;}
.mod:hover{transform:translateY(-4px);box-shadow:0 18px 38px rgba(var(--rgb-shadow),0.08);}
.mod .mod-ic{font-size:26px;}
.mod h3{font-size:18px;font-weight:700;color:var(--color-dark);}
.mod p{font-size:var(--fs-body);line-height:1.7;color:var(--color-body);}
[dir="rtl"] .mod{text-align:right;}
.mod .tech-chips{margin-top:auto;padding-top:6px;}
.mod .chip{font-size:12px;padding:5px 11px;}
.soon-card{margin-top:34px;text-align:center;background:linear-gradient(135deg,rgba(var(--rgb-primary),0.06),rgba(var(--rgb-accent),0.06),rgba(var(--rgb-accent),0.06));border:1px dashed rgba(var(--rgb-ink),0.15);border-radius:var(--radius-card);padding:44px 32px;}
.soon-emoji{font-size:40px;margin-bottom:14px;}
.soon-card h3{font-size:22px;font-weight:700;color:var(--color-dark);margin-bottom:10px;}
.soon-card p{font-size:var(--fs-body);line-height:1.9;color:var(--color-body);max-width:560px;margin:0 auto 20px;}
.soon-cta{display:inline-block;padding:12px 28px;border-radius:999px;background:var(--color-dark);color:var(--color-on-dark);font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;text-decoration:none;transition:all 0.25s;}
.soon-cta:hover{background:var(--color-primary);transform:translateY(-2px);}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.4}}
@media(max-width:768px){.page{padding:calc(var(--nav-h) + var(--space-md)) var(--space-sm) var(--space-xl);}.cat{padding:28px 22px;}}
</style>
</head>
<body>
<div class="bg-wrap"><video class="bg-video" id="vid" autoplay loop muted playsinline src="bg.mp4"></video></div>
<div class="overlay"></div>

<?php include __DIR__ . "/nav.php"; ?>

<div class="page" id="pg">
  <div class="page-header">
    <div class="page-badge"><div class="pulse-dot"></div><span id="t-badge">A Tribute to the Human Hand</span></div>
    <h1 id="t-h1">The <em>Human Hand</em></h1>
    <p id="t-desc"></p>
  </div>

  <div class="cats" id="cats"></div>

  <h2 class="sec-h" id="t-modern-title"></h2>
  <p class="sec-sub" id="t-modern-sub"></p>
  <div class="mods" id="mods"></div>

  <div class="soon-card">
    <div class="soon-emoji">✨</div>
    <h3 id="t-soon-title"></h3>
    <p id="t-soon-body"></p>
    <a class="soon-cta" id="t-soon-cta" href="mailto:contact@oweili.com?subject=Art%20Style%20Suggestion"></a>
  </div>

  <p class="closing" id="t-closing"></p>
</div>

<script>
const ACCENTS = [
  {a:'var(--color-accent)', s:'rgba(var(--rgb-accent),0.09)', l:'rgba(var(--rgb-accent),0.25)'},
  {a:'var(--color-accent)', s:'rgba(var(--rgb-primary),0.07)',   l:'rgba(var(--rgb-primary),0.2)'},
  {a:'var(--color-accent)', s:'rgba(var(--rgb-accent),0.07)',  l:'rgba(var(--rgb-accent),0.2)'},
  {a:'var(--color-accent)', s:'rgba(var(--rgb-accent),0.07)',  l:'rgba(var(--rgb-accent),0.2)'}
];

const CONTENT = {
  en:{
    badge:'A Tribute to the Human Hand',
    h1:'The <em>Human Hand</em>',
    desc:'Oweili celebrates art made by people. Here are four great families of human art. Each one shows a different bond between artist and material. We will show you how to spot the living touch in every real work.',
    labels:{story:'The Human Story', tech:'Key Techniques', soul:'Signs of a Human Hand'},
    soulLead:'What collectors look for in work made 100% by hand:',
    closing:'Every style here holds human patience and courage. When you collect on Oweili, you are not buying an image. You are keeping proof that a person stood before a surface. And dared to leave a mark.',
    modernTitle:'Modern & <em>Contemporary</em> Styles',
    modernSub:'The living languages of today’s artists. From spray cans to reed pens.',
    soon:{title:'More Styles on the Way', body:'We add new styles as our community grows. Do you practice one we have missed? Tell us, and we will feature it.', cta:'Suggest a Style'},
    modern:[
      {ic:'🧱',title:'Street Art & Urban Art',desc:'Art made on walls and city streets. Bold, fast, and made for everyone who walks by.',tech:['Spray paint','Stencilling','Wheatpaste','Muralism']},
      {ic:'🔍',title:'Hyperrealism',desc:'Painting sharper than a photograph. Built from thousands of patient, careful marks.',tech:['Layered glazing','Airbrushing','Fine detailing','Value mapping']},
      {ic:'⬜',title:'Minimalism',desc:'The art of less. Empty space, one form and quiet color say everything.',tech:['Reduction','Negative space','Flat planes','Hard edge']},
      {ic:'🎯',title:'Pop Art',desc:'Bright, bold work that plays with ads and everyday life. Confident and full of wit.',tech:['Screen printing','Ben-Day dots','Bold flat colour','Repetition']},
      {ic:'🌀',title:'Contemporary Abstract',desc:'No subject at all. Gesture, layers and raw texture do all the talking.',tech:['Layering','Gestural marks','Texture building','Colour blocking']},
      {ic:'✂️',title:'Mixed Media & Collage',desc:'Paper, fabric, paint and found objects. Many materials joined into one surface.',tech:['Assemblage','Layering','Found materials','Decoupage']},
      {ic:'🌿',title:'Botanical & Nature Art',desc:'Loving studies of plants and living things. Careful eyes, delicate lines.',tech:['Botanical illustration','Watercolour washes','Fine linework','Stippling']},
      {ic:'👤',title:'Portrait & Figure Art',desc:'The timeless study of the human face and body. Likeness, weight and character.',tech:['Life drawing','Proportion','Chiaroscuro','Gesture drawing']},
      {ic:'✒️',title:'Arabic Calligraphy Art',desc:'The beloved art of Arabic script. Every letter is measured, rhythmic and alive.',tech:['Thuluth','Diwani','Naskh','Reed pen (qalam)']},
      {ic:'❋',title:'Islamic Geometric Art',desc:'Endless patterns from a circle and a line. A quiet meditation on order and harmony.',tech:['Girih','Tessellation','Compass and straightedge','Arabesque']},
      {ic:'🏞️',title:'Landscape & Seascape',desc:'Land, sky and sea, full of light and air. Often painted outdoors in one sitting.',tech:['Plein air','Atmospheric perspective','Wet-on-wet','Glazing skies']},
      {ic:'📷',title:'Photography as Art',desc:'The camera becomes a brush. Framing, light and timing turn a moment into memory.',tech:['Composition','Long exposure','Darkroom printing','Fine art print']}
    ],
    cats:[
      {tag:'Classical & Realism', title:'The Masters of Light & Form',
       story:'Before the camera, people longed to stop time. They wanted to hold a face, a fold of cloth, a beam of light. The classical masters spent years grinding pigments and stretching linen. They trained their eyes to read light before color. Their real struggle was patience. One portrait could take months of thin, slow layers. Each layer was a quiet act of devotion.',
       tech:['Chiaroscuro','Glazing','Sfumato','Underpainting','Verdaccio'],
       soul:'Look closely at how the light changes. A human master leaves a faint trace of the hand. A shadow softened by a thumb. A glaze that is slightly uneven. A highlight placed just short of perfect. These gentle flaws are the fingerprint of a living eye.'},
      {tag:'Impressionism & Expressionism', title:'The Emotion of the Stroke',
       story:'Artists walked out of the studio and into changing daylight. They had to let go of control. The light would not wait, so the hand moved fast and honest. They loaded the brush and struck the canvas while the feeling was still warm. Their struggle became speed and nerve. Catch the moment before it fades. Trust the wrist and the heartbeat.',
       tech:['Alla prima','Impasto','Broken colour','Wet-on-wet','Gestural brushwork'],
       soul:'Feel the pressure of each stroke. Real paint has weight and direction. Thick ridges catch the light. Brush hairs drag through the color. Colors sit unmixed, so your eye blends them. You can almost read the speed of the arm.'},
      {tag:'Cubism & Abstraction', title:'The Geometry of Thought',
       story:'In a century that broke old certainties, artists stopped copying the world. They began to take it apart. What is a form, seen from every side at once? Can pure color and gesture carry meaning alone? The struggle moved inward, into the mind. Hours of taking things apart and balancing tension with empty space. Then knowing, by instinct alone, when the work was done.',
       tech:['Deconstruction','Action painting','Colour field','Collage','Faceting'],
       soul:'Look for the trace of a decision. Even abstract work records doubt and courage. A drip that followed gravity. Marks that speed up, then slow down. An edge scraped back and painted again. No formula can invent that living imperfection.'},
      {tag:'Primitive & Surrealism', title:'The Raw & The Surreal',
       story:'Some artists left the academies behind. They wanted the first honest urge to make a mark. The same urge that once moved a hand across a cave wall. Others dove into dreams and the hidden mind. They painted what reason cannot see. This took a different kind of courage. To be direct, strange and open. To let the untrained gesture and the impossible image stand as truth.',
       tech:['Direct mark-making','Frottage','Trompe l\'oeil','Grattage','Automatism'],
       soul:'Notice how honest the surface is. Rough textures rubbed up from wood or stone. A proportion slightly off, yet somehow alive. A rubbing (frottage) no printer could ever fake. Even a perfect trompe l’oeil hides tiny tremors of the hand. They prove a person was here.'}
    ]
  },
  ar:{
    badge:'تحية لليد البشرية',
    h1:'<em>اللمسة البشرية</em>',
    desc:'يحتفي أويلي بالفن الذي تصنعه أيدي البشر. هنا أربع عائلات كبرى من الفن الإنساني. لكل منها علاقة مختلفة بين الفنان وخامته. وسنريك كيف تلمح اللمسة الحيّة في كل عمل أصيل.',
    labels:{story:'الحكاية الإنسانية', tech:'التقنيات الأساسية', soul:'علامات اليد البشرية'},
    soulLead:'ما يبحث عنه المقتني في عمل صُنع باليد 100%:',
    closing:'كل أسلوب هنا يحمل صبر الإنسان وجرأته. حين تقتني من أويلي، فأنت لا تشتري صورة. أنت تحتفظ بدليل على إنسان وقف أمام سطح. وتجرّأ أن يترك أثراً.',
    modernTitle:'أساليب <em>حديثة ومعاصرة</em>',
    modernSub:'لغات حيّة يتحدث بها فنانو اليوم. من علب الرذاذ إلى أقلام القصب.',
    soon:{title:'أساليب جديدة في الطريق', body:'نضيف أساليب جديدة كلما كبر مجتمعنا. هل تمارس أسلوباً فاتنا؟ أخبرنا، وسنحتفي به هنا.', cta:'اقترح أسلوباً'},
    modern:[
      {ic:'🧱',title:'فن الشارع والفن الحضري',desc:'فن يُرسم على الجدران وفي شوارع المدينة. جريء وسريع، ولكل من يمرّ به.',tech:['رذاذ الطلاء','الاستنسل','لصق الورق','الجداريات']},
      {ic:'🔍',title:'الواقعية المفرطة',desc:'رسم أدقّ من الصورة الفوتوغرافية. يُبنى من آلاف اللمسات الصبورة.',tech:['التزجيج المتعدد','الرذاذ الهوائي','التفصيل الدقيق','خريطة القيم الضوئية']},
      {ic:'⬜',title:'التبسيط (مينيمال)',desc:'فن القليل. فراغ وشكل واحد وألوان هادئة تقول كل شيء.',tech:['الاختزال','الفراغ السالب','المساحات المسطحة','الحواف الحادة']},
      {ic:'🎯',title:'فن البوب',desc:'أعمال زاهية وجريئة تلعب بالإعلانات والحياة اليومية. واثقة ومليئة بالذكاء.',tech:['الطباعة الشبكية','نقاط بن-داي','ألوان مسطحة جريئة','التكرار']},
      {ic:'🌀',title:'التجريد المعاصر',desc:'لا موضوع هنا. الإيماءة والطبقات والملمس الخام هي التي تتكلم.',tech:['الطبقات','لمسات إيمائية','بناء الملمس','كتل اللون']},
      {ic:'✂️',title:'الوسائط المختلطة والكولاج',desc:'ورق وقماش وطلاء وأشياء وجدها الفنان. خامات كثيرة تجتمع في سطح واحد.',tech:['التجميع','الطبقات','مواد موجودة','ديكوباج']},
      {ic:'🌿',title:'فن النبات والطبيعة',desc:'دراسات محبّة للنباتات والكائنات الحية. عين صبورة وخطوط رقيقة.',tech:['الرسم النباتي','مسحات مائية','خطوط دقيقة','التنقيط']},
      {ic:'👤',title:'فن البورتريه والشخوص',desc:'دراسة خالدة لوجه الإنسان وجسده. الشبه والثقل والشخصية.',tech:['الرسم من الطبيعة','التناسب','التباين الضوئي','رسم الإيماءة']},
      {ic:'✒️',title:'فن الخط العربي',desc:'فن الحرف العربي الذي نحبه. كل حرف موزون، له إيقاع، ونابض بالحياة.',tech:['الثلث','الديواني','النسخ','قلم القصب']},
      {ic:'❋',title:'الفن الهندسي الإسلامي',desc:'أنماط لا تنتهي من دائرة وخط. تأمّل هادئ في النظام والانسجام.',tech:['التعقيد (گِره)','التبليط','الفرجار والمسطرة','الأرابيسك']},
      {ic:'🏞️',title:'المناظر الطبيعية والبحرية',desc:'أرض وسماء وبحر، مليئة بالضوء والهواء. تُرسم غالباً في الهواء الطلق بجلسة واحدة.',tech:['الرسم في الهواء الطلق','المنظور الجوي','الرطب على الرطب','تزجيج السماء']},
      {ic:'📷',title:'التصوير الفوتوغرافي كفن',desc:'الكاميرا تصبح فرشاة. التأطير والضوء والتوقيت يحوّلون اللحظة إلى ذكرى.',tech:['التكوين','التعريض الطويل','الطباعة في غرفة التحميض','طباعة فنية']}
    ],
    cats:[
      {tag:'الكلاسيكية والواقعية', title:'أساتذة الضوء والشكل',
       story:'قبل الكاميرا، تمنّى الإنسان أن يوقف الزمن. أراد أن يمسك وجهاً، وطيّة قماش، وخيط ضوء. أمضى الأساتذة الكلاسيكيون سنوات يطحنون الأصباغ ويشدّون الكتان. درّبوا أعينهم على قراءة الضوء قبل اللون. كان صراعهم الحقيقي هو الصبر. قد تستغرق لوحة واحدة شهوراً من الطبقات الرقيقة. وكل طبقة فعل حبّ هادئ.',
       tech:['كياروسكورو (التباين الضوئي)','التزجيج','سفوماتو (التدرّج الضبابي)','الطبقة التحتية','فرداتشيو'],
       soul:'تأمّل تغيّر الضوء عن قرب. يترك الأستاذ أثراً خفيفاً ليده. ظلٌّ نعّمه إبهام. تزجيج غير متساوٍ قليلاً. بريقٌ وُضع على بُعد شعرة من الكمال. هذه العيوب اللطيفة بصمة عين حيّة.'},
      {tag:'الانطباعية والتعبيرية', title:'انفعال اللمسة',
       story:'خرج الفنانون من المرسم إلى ضوء النهار المتغيّر. وكان عليهم أن يتخلّوا عن السيطرة. الضوء لا ينتظر، فتحرّكت اليد بسرعة وصدق. حمّلوا الفرشاة وضربوا القماش والشعور ما زال دافئاً. صار صراعهم سرعة وجرأة. أن تلتقط اللحظة قبل أن تتلاشى. وأن تثق بمعصمك ونبض قلبك.',
       tech:['ألا بريما (الدفعة الواحدة)','إمباستو (الطلاء السميك)','اللون المكسور','الرطب على الرطب','ضربات إيمائية'],
       soul:'تحسّس ضغط كل لمسة. الطلاء الحقيقي له ثقل واتجاه. نتوءات سميكة تلتقط الضوء. شعيرات فرشاة تنجرّ في اللون. ألوان تُركت بلا مزج لتمزجها عينك. تكاد تقرأ سرعة الذراع التي رسمتها.'},
      {tag:'التكعيبية والتجريد', title:'هندسة الفكرة',
       story:'في قرن حطّم اليقين القديم، توقّف الفنانون عن نسخ العالم. وبدأوا يفكّكونه. ما الشكل حين تراه من كل جانب معاً؟ وهل يحمل اللون والإيماءة معنى وحدهما؟ انتقل الصراع إلى الداخل، إلى الفكر. ساعات من التفكيك، وموازنة التوتر مع الفراغ. ثم معرفة، بالغريزة وحدها، متى اكتمل العمل.',
       tech:['التفكيك','الرسم الحركي','حقل اللون','الكولاج','التقطيع السطحي'],
       soul:'ابحث عن أثر القرار. حتى العمل المجرّد يسجّل التردّد والشجاعة. قطرة تبعت الجاذبية. علامات تتسارع ثم تتباطأ. حافة كُشطت ثم رُسمت من جديد. لا توجد صيغة تخترع هذا التفاوت الحيّ.'},
      {tag:'البدائية والسريالية', title:'الخام والسريالي',
       story:'ترك بعض الفنانين الأكاديميات خلفهم. أرادوا الدافع الأول الصادق لترك علامة. الدافع نفسه الذي حرّك يداً على جدار كهف. وغاص آخرون في الأحلام والعقل الخفي. رسموا ما لا يراه المنطق. احتاج ذلك شجاعة من نوع آخر. أن تكون مباشراً وغريباً ومكشوفاً. وأن تدع الإيماءة البسيطة والصورة المستحيلة تقفان كحقيقة.',
       tech:['العلامة المباشرة','فروتاج (الفرك)','خداع البصر','غراتاج (الحفر)','الكتابة التلقائية'],
       soul:'لاحظ صدق السطح. ملمس خشن مفروك من الخشب أو الحجر. نسبة مائلة قليلاً، لكنها تنبض بالحياة. فرك (فروتاج) لا تستطيع أي طابعة تزييفه. حتى خداع البصر المتقن يخفي رعشات صغيرة لليد. تثبت أن إنساناً كان هنا.'}
    ]
  }
};

function esc(s){const d=document.createElement('div');d.textContent=String(s==null?'':s);return d.innerHTML;}
function buildMods(l){
  const c = CONTENT[l];
  document.getElementById('mods').innerHTML = c.modern.map(m => {
    const chips = m.tech.map(t => '<span class="chip">'+esc(t)+'</span>').join('');
    return '<div class="mod"><div class="mod-ic">'+esc(m.ic)+'</div>'
      + '<h3>'+esc(m.title)+'</h3><p>'+esc(m.desc)+'</p>'
      + '<div class="tech-chips">'+chips+'</div></div>';
  }).join('');
}
function buildCats(l){
  const c = CONTENT[l];
  document.getElementById('cats').innerHTML = c.cats.map((cat, i) => {
    const ac = ACCENTS[i % ACCENTS.length];
    const chips = cat.tech.map(t => '<span class="chip">'+esc(t)+'</span>').join('');
    const num = String(i+1).padStart(2,'0');
    return '<section class="cat" style="--accent:'+ac.a+';--accent-soft:'+ac.s+';--accent-line:'+ac.l+';">'
      + '<div class="cat-num">'+num+'</div>'
      + '<span class="cat-tag">'+esc(cat.tag)+'</span>'
      + '<h2 class="cat-title">'+esc(cat.title)+'</h2>'
      + '<div class="block"><div class="block-label">'+esc(c.labels.story)+'</div><p>'+esc(cat.story)+'</p></div>'
      + '<div class="block"><div class="block-label">'+esc(c.labels.tech)+'</div><div class="tech-chips">'+chips+'</div></div>'
      + '<div class="block"><div class="block-label">'+esc(c.labels.soul)+'</div><div class="soul"><div class="soul-lead">'+esc(c.soulLead)+'</div><p>'+esc(cat.soul)+'</p></div></div>'
      + '</section>';
  }).join('');
}

/* ---- Bilingual nav + page labels ---- */
const NAV={
  en:{lb:'العربية',studio:'The Studio',community:'Community',marketplace:'Gallery',explore:'Explore Art Styles',support:'Support',
    s1:'Watercolor Workshop',s2:'Oil Painting Studio',s3:'Digital Art Lab',s4:'Charcoal & Ink',
    c2:'Meet Fellow Artists',c3:'Share Your Work',c4:'Learn Together',
    sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free'},
  ar:{lb:'English',studio:'الاستوديو',community:'المجتمع',marketplace:'المعرض',explore:'استكشف أساليب الرسم',support:'الدعم',
    s1:'ورشة الألوان المائية',s2:'استوديو الرسم الزيتي',s3:'مختبر الفن الرقمي',s4:'الفحم والحبر',
    c2:'تعرّف على فنانين',c3:'شارك أعمالك',c4:'تعلّم معاً',
    sp1:'تواصل معنا',sp2:'كيف يعمل الموقع',sp3:'شروط الاستخدام',login:'تسجيل الدخول',reg:'انضم مجاناً'}
};
let L='en';
function setTxt(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}
function apply(l){
  L=l;
  const n=NAV[l], c=CONTENT[l];
  document.getElementById('html').lang=l;
  document.getElementById('html').setAttribute('dir', l==='ar'?'rtl':'ltr');
  document.documentElement.setAttribute('dir', l==='ar'?'rtl':'ltr');
  document.getElementById('topnav').setAttribute('dir', l==='ar'?'rtl':'ltr');
  document.getElementById('lb').textContent=n.lb;
  setTxt('nav-studio-label',n.studio);setTxt('nav-community-label',n.community);
  setTxt('nav-marketplace-label',n.marketplace);setTxt('nav-explore-label',n.explore);setTxt('nav-support-label',n.support);
  setTxt('dd-s1',n.s1);setTxt('dd-s2',n.s2);setTxt('dd-s3',n.s3);setTxt('dd-s4',n.s4);
  setTxt('dd-c2',n.c2);setTxt('dd-c3',n.c3);setTxt('dd-c4',n.c4);
  setTxt('dd-sp1',n.sp1);setTxt('dd-sp2',n.sp2);setTxt('dd-sp3',n.sp3);
  setTxt('n-login-t',n.login);setTxt('n-reg-t',n.reg);
  document.getElementById('t-badge').textContent=c.badge;
  document.getElementById('t-h1').innerHTML=c.h1;
  document.getElementById('t-desc').textContent=c.desc;
  document.getElementById('t-closing').textContent=c.closing;
  document.getElementById('t-modern-title').innerHTML=c.modernTitle;
  document.getElementById('t-modern-sub').textContent=c.modernSub;
  document.getElementById('t-soon-title').textContent=c.soon.title;
  document.getElementById('t-soon-body').textContent=c.soon.body;
  document.getElementById('t-soon-cta').textContent=c.soon.cta;
  buildCats(l);
  buildMods(l);
}
function tgl(){L=L==='en'?'ar':'en';apply(L);try{localStorage.setItem('lang',L);}catch(e){}}
try{var _s=localStorage.getItem('lang');if(_s==='ar'||_s==='en')L=_s;}catch(e){}
apply(L);

const v=document.getElementById('vid');
if(v){{const on=()=>v.classList.add('on');if(v.readyState>=3)on();v.addEventListener('canplay',on,{once:true});v.addEventListener('playing',on,{once:true});}v.play().catch(()=>{});}
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
