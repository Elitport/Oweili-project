<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>The Human Hand · Oweili</title>
<link rel="stylesheet" href="/assets/css/oweili.css?v=<?= @filemtime(__DIR__ . '/assets/css/oweili.css') ?>">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:#fff;font-family:var(--font-sans);overflow-x:hidden;}
.page{position:relative;z-index:10;width:100%;max-width:1040px;margin:0 auto;padding:100px 24px 64px;}
.page-header{margin-bottom:40px;text-align:center;}
.page-badge{display:inline-flex;align-items:center;gap:8px;padding:5px 14px;border-radius:999px;margin-bottom:16px;background:rgba(0,102,255,0.06);border:1px solid rgba(0,102,255,0.15);font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:var(--color-accent);}
.pulse-dot{width:6px;height:6px;border-radius:50%;background:var(--color-accent);box-shadow:0 0 8px rgba(0,102,255,0.5);animation:pulse 2s infinite;}
.page-header h1{font-size:clamp(30px,5vw,52px);font-weight:700;color:var(--color-dark);margin-bottom:14px;letter-spacing:-0.02em;}
.page-header h1 em{font-style:normal;font-weight:700;color:var(--color-primary);}
.page-header p{font-size:var(--fs-body);color:var(--color-body);line-height:1.9;max-width:660px;margin:0 auto;}
.cats{display:flex;flex-direction:column;gap:30px;}
.cat{background:#fff;border:1px solid rgba(0,0,0,0.07);border-radius:var(--radius-card);padding:38px 34px;position:relative;overflow:hidden;box-shadow:var(--shadow-card);}
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
.chip{padding:7px 15px;border-radius:999px;font-size:13px;font-weight:600;background:rgba(0,0,0,0.045);border:1px solid rgba(0,0,0,0.08);color:#222;}
.soul{background:var(--accent-soft);border:1px solid var(--accent-line);border-radius:16px;padding:18px 20px;}
.soul .soul-lead{font-size:var(--fs-body);font-weight:700;color:var(--accent);margin-bottom:8px;}
.soul p{font-size:var(--fs-body);line-height:1.9;color:var(--color-body);}
.closing{margin-top:var(--space-xl);text-align:center;font-size:var(--fs-body);line-height:1.7;color:var(--color-body);max-width:660px;margin-left:auto;margin-right:auto;}
[dir="rtl"] .block p,[dir="rtl"] .soul p,[dir="rtl"] .cat-title{text-align:right;}
.sec-h{margin-top:52px;text-align:center;font-size:clamp(24px,3.4vw,34px);font-weight:300;color:var(--color-dark);letter-spacing:-0.02em;}
.sec-h em{font-style:normal;font-weight:700;color:var(--color-primary);}
.sec-sub{text-align:center;font-size:var(--fs-body);color:var(--color-body);line-height:1.7;max-width:640px;margin:12px auto 30px;}
.mods{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px;}
.mod{background:#fff;border:1px solid rgba(0,0,0,0.07);border-radius:var(--radius-card);padding:24px;display:flex;flex-direction:column;gap:10px;transition:transform 0.3s ease,box-shadow 0.3s ease;}
.mod:hover{transform:translateY(-4px);box-shadow:0 18px 38px rgba(0,0,0,0.08);}
.mod .mod-ic{font-size:26px;}
.mod h3{font-size:18px;font-weight:700;color:var(--color-dark);}
.mod p{font-size:var(--fs-body);line-height:1.7;color:var(--color-body);}
[dir="rtl"] .mod{text-align:right;}
.mod .tech-chips{margin-top:auto;padding-top:6px;}
.mod .chip{font-size:12px;padding:5px 11px;}
.soon-card{margin-top:34px;text-align:center;background:linear-gradient(135deg,rgba(255,0,85,0.06),rgba(0,102,255,0.06),rgba(0,102,255,0.06));border:1px dashed rgba(0,0,0,0.15);border-radius:var(--radius-card);padding:44px 32px;}
.soon-emoji{font-size:40px;margin-bottom:14px;}
.soon-card h3{font-size:22px;font-weight:700;color:var(--color-dark);margin-bottom:10px;}
.soon-card p{font-size:var(--fs-body);line-height:1.9;color:var(--color-body);max-width:560px;margin:0 auto 20px;}
.soon-cta{display:inline-block;padding:12px 28px;border-radius:999px;background:var(--color-dark);color:#fff;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;text-decoration:none;transition:all 0.25s;}
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
  {a:'#0066ff', s:'rgba(0,102,255,0.09)', l:'rgba(0,102,255,0.25)'},
  {a:'#ff0055', s:'rgba(255,0,85,0.07)',   l:'rgba(255,0,85,0.2)'},
  {a:'#0066ff', s:'rgba(0,102,255,0.07)',  l:'rgba(0,102,255,0.2)'},
  {a:'#0066ff', s:'rgba(0,102,255,0.07)',  l:'rgba(0,102,255,0.2)'}
];

const CONTENT = {
  en:{
    badge:'A Tribute to the Human Hand',
    h1:'The <em>Human Hand</em>',
    desc:'Oweili celebrates art made by people. Here we honor four great families of human creation, grouped by the physical bond between the artist and the medium, and we show you how to recognize the living touch behind every genuine work.',
    labels:{story:'The Human Story', tech:'Key Techniques', soul:'The Soul Indicator'},
    soulLead:'What a collector looks for to verify a 100% human hand:',
    closing:'Every category here is a record of human labor, patience, and nerve. When you collect on Oweili, you are not buying an image. You are keeping the evidence of a person who stood before a surface and dared to leave a mark.',
    modernTitle:'Modern & <em>Contemporary</em> Styles',
    modernSub:'The living languages of today\'s working artists, from spray cans to reed pens.',
    soon:{title:'More Coming Soon', body:'New art styles are added to Oweili regularly as our community grows. Do you practise a discipline you do not see here? Tell us about it and we will feature it.', cta:'Suggest a Style'},
    modern:[
      {ic:'🧱',title:'Street Art & Urban Art',desc:'Public work made on walls and city surfaces, bold, fast, and meant for everyone who passes by.',tech:['Spray paint','Stencilling','Wheatpaste','Muralism']},
      {ic:'🔍',title:'Hyperrealism',desc:'Painting and drawing pushed to a detail sharper than a photograph, built from thousands of patient marks.',tech:['Layered glazing','Airbrushing','Fine detailing','Value mapping']},
      {ic:'⬜',title:'Minimalism',desc:'The art of less, where empty space, a single form, and a quiet palette carry the whole meaning.',tech:['Reduction','Negative space','Flat planes','Hard edge']},
      {ic:'🎯',title:'Pop Art',desc:'Bright, graphic work that borrows from advertising and everyday culture with confidence and wit.',tech:['Screen printing','Ben-Day dots','Bold flat colour','Repetition']},
      {ic:'🌀',title:'Contemporary Abstract',desc:'Non-representational work that speaks through gesture, layering, and raw texture rather than any subject.',tech:['Layering','Gestural marks','Texture building','Colour blocking']},
      {ic:'✂️',title:'Mixed Media & Collage',desc:'Art assembled from many materials at once: paper, fabric, paint, and found objects joined into one surface.',tech:['Assemblage','Layering','Found materials','Decoupage']},
      {ic:'🌿',title:'Botanical & Nature Art',desc:'Careful, loving studies of plants and living things, rooted in scientific observation and delicate line.',tech:['Botanical illustration','Watercolour washes','Fine linework','Stippling']},
      {ic:'👤',title:'Portrait & Figure Art',desc:'The timeless study of the human face and body, capturing likeness, weight, and character.',tech:['Life drawing','Proportion','Chiaroscuro','Gesture drawing']},
      {ic:'✒️',title:'Arabic Calligraphy Art',desc:'The revered art of beautiful Arabic script, where every letter is measured, rhythmic, and alive.',tech:['Thuluth','Diwani','Naskh','Reed pen (qalam)']},
      {ic:'❋',title:'Islamic Geometric Art',desc:'Infinite patterns built from circle and line, a meditation on order, symmetry, and the eternal.',tech:['Girih','Tessellation','Compass and straightedge','Arabesque']},
      {ic:'🏞️',title:'Landscape & Seascape',desc:'The land, sky, and sea rendered with atmosphere and light, often painted outdoors in a single sitting.',tech:['Plein air','Atmospheric perspective','Wet-on-wet','Glazing skies']},
      {ic:'📷',title:'Photography as Art',desc:'The camera used as a brush, where framing, light, and timing turn a fleeting moment into a lasting image.',tech:['Composition','Long exposure','Darkroom printing','Fine art print']}
    ],
    cats:[
      {tag:'Classical & Realism', title:'The Masters of Light & Form',
       story:'Before the camera, humanity\'s deepest wish was to stop time and hold the truth of a face, a fold of cloth, a shaft of afternoon light. The classical masters spent years grinding pigments, stretching linen, and training the eye to read value before colour. The struggle was patience itself. A single portrait could take months of thin, drying layers, and each layer was a quiet act of devotion to the visible world.',
       tech:['Chiaroscuro','Glazing','Sfumato','Underpainting','Verdaccio'],
       soul:'Look closely at the transitions of light. A human master leaves the faint memory of the hand: the softened edge where a thumb blurred a shadow, the barely uneven film of a glaze, a highlight placed a fraction away from perfect. These gentle imperfections are the fingerprint of a living eye.'},
      {tag:'Impressionism & Expressionism', title:'The Emotion of the Stroke',
       story:'When artists walked out of the studio and into the changing daylight, they surrendered control. They could no longer polish a canvas for months, because the light itself was fleeting. So they let the hand move fast and honest, loading the brush and striking the canvas while the feeling was still hot. The physical struggle became speed and nerve: capturing a moment before it dissolved, trusting the wrist and the heartbeat.',
       tech:['Alla prima','Impasto','Broken colour','Wet-on-wet','Gestural brushwork'],
       soul:'Feel the pressure of the stroke. Real human paint has weight and direction: thick ridges of impasto that catch the light, brush hairs dragged into the pigment, colours left deliberately unblended so the eye mixes them. You can almost read the speed of the arm that made them.'},
      {tag:'Cubism & Abstraction', title:'The Geometry of Thought',
       story:'In a century that shattered old certainties, artists stopped copying the world and began to take it apart. They asked what a form truly is when seen from every side at once, and whether pure colour and gesture could carry meaning with no object at all. The struggle turned inward and intellectual: hours of deconstruction, of balancing tension against void, of deciding by instinct alone when a canvas was finished.',
       tech:['Deconstruction','Action painting','Colour field','Collage','Faceting'],
       soul:'Search for the trace of the decision. Even in the most abstract work, the human hand records hesitation and courage: a drip that followed gravity, a rhythm of marks that quickens then slows, an edge scraped back and reworked. No formula could invent that living inconsistency.'},
      {tag:'Primitive & Surrealism', title:'The Raw & The Surreal',
       story:'Some artists turned away from the academies to reclaim the first honest impulse to make a mark, the same impulse that once moved a hand across a cave wall. Others dove into dream and the subconscious, painting what reason cannot see. The struggle here was a courage of a different kind: to be direct, strange, and unguarded, to let the untrained gesture and the impossible image stand as truth.',
       tech:['Direct mark-making','Frottage','Trompe l\'oeil','Grattage','Automatism'],
       soul:'Notice the honesty of the surface. Human rawness shows in uneven textures rubbed up from the grain of wood or stone, in the slightly wrong proportion that somehow feels alive, in a rubbing (frottage) that no printer could ever fake. Even a flawless trompe l\'oeil hides tiny tremors of the hand that prove a person, not a machine, was here.'}
    ]
  },
  ar:{
    badge:'تحية لليد البشرية',
    h1:'<em>اللمسة البشرية</em>',
    desc:'يحتفي أويلي بالفن الذي يصنعه البشر. هنا نكرّم أربع عائلات كبرى من الإبداع الإنساني، مصنّفة وفق العلاقة الجسدية بين الفنان والخامة، ونوضّح لك كيف تتعرّف على اللمسة الحيّة خلف كل عمل أصيل.',
    labels:{story:'الحكاية الإنسانية', tech:'التقنيات الأساسية', soul:'مؤشّر الروح'},
    soulLead:'ما الذي يبحث عنه المقتني للتأكد من أن العمل بشري 100%:',
    closing:'كل فئة هنا سجلٌّ للجهد الإنساني والصبر والجرأة. حين تقتني عبر أويلي فأنت لا تشتري صورة، بل تحتفظ بدليلٍ على إنسانٍ وقف أمام السطح وتجرّأ على ترك أثر.',
    modernTitle:'أساليب <em>حديثة ومعاصرة</em>',
    modernSub:'اللغات الحيّة لفناني اليوم، من علب الرذاذ إلى أقلام القصب.',
    soon:{title:'المزيد قريباً', body:'نضيف أساليب فنية جديدة إلى أويلي باستمرار مع نمو مجتمعنا. هل تمارس أسلوباً لا تجده هنا؟ أخبرنا عنه وسنُبرزه.', cta:'اقترح أسلوباً'},
    modern:[
      {ic:'🧱',title:'فن الشارع والفن الحضري',desc:'أعمال عامة تُنفّذ على الجدران وأسطح المدينة، جريئة وسريعة وموجّهة لكل عابر سبيل.',tech:['رذاذ الطلاء','الاستنسل','لصق الورق','الجداريات']},
      {ic:'🔍',title:'الواقعية المفرطة',desc:'رسم يتجاوز حدّة الصورة الفوتوغرافية، مبنيّ من آلاف اللمسات الصبورة.',tech:['التزجيج المتعدد','الرذاذ الهوائي','التفصيل الدقيق','خريطة القيم الضوئية']},
      {ic:'⬜',title:'التبسيط (مينيمال)',desc:'فن الاقتصاد، حيث يحمل الفراغ وشكلٌ واحد ولوحة ألوان هادئة كامل المعنى.',tech:['الاختزال','الفراغ السالب','المساحات المسطحة','الحواف الحادة']},
      {ic:'🎯',title:'فن البوب',desc:'أعمال جرافيكية زاهية تستلهم الإعلان والثقافة اليومية بثقة وذكاء.',tech:['الطباعة الشبكية','نقاط بن-داي','ألوان مسطحة جريئة','التكرار']},
      {ic:'🌀',title:'التجريد المعاصر',desc:'أعمال غير تمثيلية تتحدث عبر الإيماءة والطبقات والملمس الخام بدل أي موضوع.',tech:['الطبقات','لمسات إيمائية','بناء الملمس','كتل اللون']},
      {ic:'✂️',title:'الوسائط المختلطة والكولاج',desc:'فن يُجمع من خامات متعددة معاً: ورق وقماش وطلاء ومواد موجودة في سطح واحد.',tech:['التجميع','الطبقات','مواد موجودة','ديكوباج']},
      {ic:'🌿',title:'فن النبات والطبيعة',desc:'دراسات دقيقة ومحبّة للنباتات والكائنات الحية، متجذّرة في الملاحظة العلمية والخط الرقيق.',tech:['الرسم النباتي','مسحات مائية','خطوط دقيقة','التنقيط']},
      {ic:'👤',title:'فن البورتريه والشخوص',desc:'الدراسة الخالدة لوجه الإنسان وجسده، بالتقاط الشبه والثِقل والشخصية.',tech:['الرسم من الطبيعة','التناسب','التباين الضوئي','رسم الإيماءة']},
      {ic:'✒️',title:'فن الخط العربي',desc:'الفن الموقّر للخط العربي الجميل، حيث كل حرف موزون وإيقاعي ونابض بالحياة.',tech:['الثلث','الديواني','النسخ','قلم القصب']},
      {ic:'❋',title:'الفن الهندسي الإسلامي',desc:'أنماط لا نهائية تُبنى من الدائرة والخط، تأمّل في النظام والتناظر والخلود.',tech:['التعقيد (گِره)','التبليط','الفرجار والمسطرة','الأرابيسك']},
      {ic:'🏞️',title:'المناظر الطبيعية والبحرية',desc:'الأرض والسماء والبحر بأجوائها وضوئها، تُرسم غالباً في الهواء الطلق بجلسة واحدة.',tech:['الرسم في الهواء الطلق','المنظور الجوي','الرطب على الرطب','تزجيج السماء']},
      {ic:'📷',title:'التصوير الفوتوغرافي كفن',desc:'الكاميرا كفرشاة، حيث يحوّل التأطير والضوء والتوقيت اللحظة العابرة إلى صورة باقية.',tech:['التكوين','التعريض الطويل','الطباعة في غرفة التحميض','طباعة فنية']}
    ],
    cats:[
      {tag:'الكلاسيكية والواقعية', title:'أساتذة الضوء والشكل',
       story:'قبل ظهور الكاميرا، كانت أعمق أمنية للإنسان أن يوقف الزمن ويمسك حقيقة وجهٍ، وطيّة قماش، وخيط ضوءٍ في العصر. أمضى الأساتذة الكلاسيكيون سنوات في طحن الأصباغ، وشدّ الكتان، وتدريب العين على قراءة القيمة الضوئية قبل اللون. كان الصراع هو الصبر نفسه. قد يستغرق البورتريه الواحد شهوراً من الطبقات الرقيقة المتتابعة، وكل طبقة فعل تفانٍ هادئ تجاه العالم المرئي.',
       tech:['كياروسكورو (التباين الضوئي)','التزجيج','سفوماتو (التدرّج الضبابي)','الطبقة التحتية','فرداتشيو'],
       soul:'تأمّل انتقالات الضوء عن قرب. يترك الأستاذ البشري ذكرى خفيفة لليد: الحافة الناعمة حيث موّه الإبهام الظل، وغشاء التزجيج غير المتساوي قليلاً، وبريقٌ وُضع على بُعد شعرةٍ من الكمال. هذه العيوب اللطيفة هي بصمة عينٍ حيّة.'},
      {tag:'الانطباعية والتعبيرية', title:'انفعال اللمسة',
       story:'حين خرج الفنانون من المرسم إلى ضوء النهار المتغيّر، تخلّوا عن السيطرة. لم يعد بوسعهم صقل اللوحة لشهور، لأن الضوء ذاته عابر. فتركوا اليد تتحرك بسرعةٍ وصدق، يحمّلون الفرشاة ويضربون القماش والشعور ما زال حارّاً. صار الصراع سرعةً وجرأة: التقاط اللحظة قبل أن تتلاشى، والثقة بالمعصم ونبض القلب.',
       tech:['ألا بريما (الدفعة الواحدة)','إمباستو (الطلاء السميك)','اللون المكسور','الرطب على الرطب','ضربات إيمائية'],
       soul:'تحسّس ضغط اللمسة. الطلاء البشري الحقيقي له ثِقلٌ واتجاه: نتوءات سميكة من الإمباستو تلتقط الضوء، وشعيرات فرشاة انجرّت داخل الصبغة، وألوان تُركت بلا مزجٍ ليمزجها البصر. تكاد تقرأ سرعة الذراع التي صنعتها.'},
      {tag:'التكعيبية والتجريد', title:'هندسة الفكرة',
       story:'في قرنٍ حطّم اليقين القديم، توقّف الفنانون عن نسخ العالم وبدأوا بتفكيكه. سألوا: ما هو الشكل حقاً حين يُرى من كل جانب دفعةً واحدة، وهل يستطيع اللون الخالص والإيماءة أن يحملا معنى دون أي موضوع؟ تحوّل الصراع إلى الداخل وإلى الفكر: ساعات من التفكيك، وموازنة التوتر مقابل الفراغ، والحسم بالغريزة وحدها متى اكتمل العمل.',
       tech:['التفكيك','الرسم الحركي','حقل اللون','الكولاج','التقطيع السطحي'],
       soul:'ابحث عن أثر القرار. حتى في أشد الأعمال تجريداً، تسجّل اليد البشرية التردّد والشجاعة: قطرة تبعت الجاذبية، وإيقاع علاماتٍ يتسارع ثم يتباطأ، وحافة كُشطت وأُعيد تشكيلها. لا صيغة يمكنها اختراع هذا التفاوت الحيّ.'},
      {tag:'البدائية والسريالية', title:'الخام والسريالي',
       story:'ابتعد بعض الفنانين عن الأكاديميات ليستعيدوا الدافع الأول الصادق لترك علامة، الدافع نفسه الذي حرّك يوماً يداً على جدار كهف. وغاص آخرون في الحلم واللاوعي، يرسمون ما لا يراه العقل. كان الصراع هنا شجاعةً من نوعٍ آخر: أن تكون مباشراً وغريباً ومكشوفاً، وأن تدع الإيماءة غير المدرّبة والصورة المستحيلة تقفان بوصفهما حقيقة.',
       tech:['العلامة المباشرة','فروتاج (الفرك)','خداع البصر','غراتاج (الحفر)','الكتابة التلقائية'],
       soul:'لاحظ صدق السطح. تظهر الخشونة البشرية في القوام غير المتساوي المفروك من حبيبات الخشب أو الحجر، وفي النسبة المائلة قليلاً التي تنبض بالحياة رغم ذلك، وفي الفرك (فروتاج) الذي لا تستطيع أي طابعة تزييفه. حتى خداع البصر المتقن يُخفي رعشاتٍ دقيقة لليد تثبت أن إنساناً، لا آلة، كان هنا.'}
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
