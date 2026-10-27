/* =====================================================================
 *  art_styles.js: the single source for all art style content (50 styles).
 *  Used by explore.php (cards + the style window), and read by
 *  art_types.php for the art type names. Never copy this data into a
 *  page: load this file instead.
 *
 *  Each style:
 *    id     short name, also used in the web address (explore.php#watercolor)
 *    cat    traditional | digital | heritage | contemporary | nature | photography
 *    type   the Gallery type for this style (same as id). art_types.php reads
 *           the names from this file, so every style is a Gallery type
 *    level  beginner | intermediate | advanced
 *    en/ar  name, feel (one word), essence (one line), story (3 paragraphs),
 *           tech [name, one-line explanation], signs (numbered list),
 *           tools [tool, purpose], time, bestFor,
 *           artists [name, nationality and dates, contribution], guide (3 tips)
 * ===================================================================== */
window.OW_ART_STYLES = [
 {
  "id": "watercolor",
  "cat": "traditional",
  "type": "watercolor",
  "level": "intermediate",
  "en": {
   "name": "Watercolor",
   "feel": "Dreamy",
   "essence": "Pigment carried by water, with light shining up from the paper.",
   "story": [
    "Watercolor is one of the oldest ways people have painted. Painters in China used ink and color washes on silk and paper for centuries. Persian and Indian painters also worked with water-based color.",
    "In Europe, Albrecht Dürer painted grass and a young hare with it around 1500. Later, British painters carried small paint boxes outdoors. Turner turned whole skies into pure light. Winslow Homer and John Singer Sargent made it fresh, fast and bold.",
    "Today, artists everywhere keep it alive, from Jeddah to Tokyo. The secret is trust. Water moves on its own. The artist guides it, then lets go. A mistake cannot be hidden, only built upon."
   ],
   "tech": [
    [
     "Wet-on-wet",
     "Paint on wet paper, so colors bloom and blend softly."
    ],
    [
     "Wet-on-dry",
     "Paint on dry paper for crisp, sharp edges."
    ],
    [
     "Glazing",
     "Thin layers over dry ones to deepen the color."
    ],
    [
     "Lifting",
     "Removing wet paint with a brush or tissue to bring back light."
    ],
    [
     "Reserving whites",
     "Leaving the paper bare, because watercolor has no true white."
    ],
    [
     "Granulation",
     "Some pigments settle into the paper grain, making a speckled texture."
    ]
   ],
   "signs": [
    "Soft blooms where water pushed the pigment outward as it dried.",
    "Pigment settled into the paper grain, a texture no print can copy.",
    "Faint pencil lines under the paint, left from the first drawing.",
    "A darker rim where a small pool of paint dried at its edge.",
    "Paper that waves a little where it was soaked."
   ],
   "tools": [
    [
     "Cotton watercolor paper",
     "Heavy paper, often 300gsm, that holds water without falling apart."
    ],
    [
     "Round brushes",
     "Soft brushes with a fine point for washes and detail."
    ],
    [
     "Pans and tubes of paint",
     "Pigment bound with gum arabic, brought back to life with water."
    ],
    [
     "Mixing palette",
     "A white surface to test colors before they touch the paper."
    ],
    [
     "Masking fluid",
     "A rubbery liquid that protects whites, peeled off later."
    ],
    [
     "Gummed tape and board",
     "Keeps soaked paper flat while it dries."
    ],
    [
     "Two water jars",
     "One to rinse, one clean, so colors stay fresh."
    ]
   ],
   "time": "2–8 hours",
   "bestFor": "Patient artists who love light, and collectors who want fresh, airy color.",
   "artists": [
    [
     "Albrecht Dürer",
     "German, 1471–1528",
     "His studies of a young hare and grass showed watercolor’s power for close looking."
    ],
    [
     "J. M. W. Turner",
     "British, 1775–1851",
     "He dissolved skies, storms and sunsets into glowing washes of light."
    ],
    [
     "Winslow Homer",
     "American, 1836–1910",
     "He painted the sea and the tropics with bold, confident washes."
    ],
    [
     "John Singer Sargent",
     "American, 1856–1925",
     "His quick travel watercolors catch sunlight in a few sure strokes."
    ],
    [
     "Emil Nolde",
     "German, 1867–1956",
     "He soaked paper with deep, glowing color, especially flowers and seas."
    ]
   ],
   "guide": [
    "Tilt the work under a light. Real paint sits in the paper grain. A print looks flat.",
    "Feel the paper’s weight and edges. Good watercolor paper is thick, with soft, uneven edges.",
    "Ask about the paper. A serious artist will name it, such as 300gsm cotton."
   ]
  },
  "ar": {
   "name": "الألوان المائية",
   "feel": "حالم",
   "essence": "صبغة يحملها الماء، والضوء ينبع من الورق نفسه.",
   "story": [
    "الألوان المائية من أقدم طرق الرسم عند البشر. رسم فنانو الصين بالحبر والألوان المخففة على الحرير والورق قرونًا طويلة. واستعملها كذلك رسامون في فارس والهند.",
    "في أوروبا رسم ألبرشت دورر بها العشب وأرنبًا صغيرًا نحو عام 1500. ثم حمل الرسامون البريطانيون علب ألوانهم الصغيرة إلى الطبيعة. وحوّل تيرنر السماء كلها إلى نور خالص. وجعلها وينسلو هومر وجون سنجر سارجنت أكثر جرأة وحيوية.",
    "اليوم يحافظ عليها فنانون في كل مكان، من جدة إلى طوكيو. سرّها الثقة. فالماء يتحرك وحده. يوجّهه الفنان ثم يتركه. والخطأ لا يُخفى، بل يُبنى فوقه."
   ],
   "tech": [
    [
     "الرطب على الرطب",
     "اللون على ورق مبلل، فيتفتح ويمتزج بنعومة."
    ],
    [
     "الرطب على الجاف",
     "اللون على ورق جاف لحواف حادة وواضحة."
    ],
    [
     "التزجيج",
     "طبقات شفافة فوق طبقات جافة لتعميق اللون."
    ],
    [
     "الرفع",
     "سحب اللون الرطب بفرشاة أو منديل لاستعادة الضوء."
    ],
    [
     "حفظ البياض",
     "ترك الورق أبيض، فلا أبيض حقيقي في الألوان المائية."
    ],
    [
     "الترسيب",
     "بعض الأصباغ تستقر في حبيبات الورق فتصنع ملمسًا منقّطًا."
    ]
   ],
   "signs": [
    "تفتحات ناعمة حيث دفع الماء الصبغة نحو الخارج وهو يجف.",
    "صبغة مستقرة في حبيبات الورق، ملمس لا تقلده الطباعة.",
    "خطوط قلم رصاص خفيفة تحت اللون، بقيت من الرسم الأول.",
    "حافة أغمق حيث جفّت بركة صغيرة من اللون.",
    "ورق متموج قليلًا في المواضع التي تشرّبت الماء."
   ],
   "tools": [
    [
     "ورق قطني للألوان المائية",
     "ورق ثقيل، غالبًا 300 غرام، يحتمل الماء دون أن يتلف."
    ],
    [
     "فرش مستديرة",
     "فرش ناعمة برأس دقيق للمساحات والتفاصيل."
    ],
    [
     "ألوان في أقراص وأنابيب",
     "صبغة مثبتة بالصمغ العربي، تعود إلى الحياة بالماء."
    ],
    [
     "لوح خلط",
     "سطح أبيض لتجربة اللون قبل أن يلمس الورق."
    ],
    [
     "سائل الحجب",
     "سائل مطاطي يحمي البياض، ويُنزع بعد الجفاف."
    ],
    [
     "شريط لاصق ولوح",
     "يُبقي الورق المبلل مستويًا حتى يجف."
    ],
    [
     "وعاءان للماء",
     "واحد للغسل وآخر نظيف، لتبقى الألوان صافية."
    ]
   ],
   "time": "من ساعتين إلى ثماني ساعات",
   "bestFor": "للفنان الصبور الذي يحب الضوء، وللمقتني الذي يعشق الألوان الخفيفة المنعشة.",
   "artists": [
    [
     "ألبرشت دورر",
     "ألماني، 1471–1528",
     "أظهرت دراساته لأرنب صغير وللعشب قدرة الألوان المائية على الملاحظة الدقيقة."
    ],
    [
     "ج. م. و. تيرنر",
     "بريطاني، 1775–1851",
     "أذاب السماء والعواصف والغروب في مساحات متوهجة من الضوء."
    ],
    [
     "وينسلو هومر",
     "أمريكي، 1836–1910",
     "رسم البحر والمناطق الاستوائية بمساحات لونية جريئة واثقة."
    ],
    [
     "جون سنجر سارجنت",
     "أمريكي، 1856–1925",
     "تلتقط ألوانه المائية في أسفاره ضوء الشمس ببضع ضربات واثقة."
    ],
    [
     "إميل نولده",
     "ألماني، 1867–1956",
     "أشبع الورق بألوان عميقة متوهجة، خاصة الزهور والبحار."
    ]
   ],
   "guide": [
    "أمِل العمل تحت الضوء. اللون الحقيقي يستقر في حبيبات الورق، والمطبوع يبدو مسطحًا.",
    "تحسّس وزن الورق وحوافه. ورق الألوان المائية الجيد سميك وحوافه ناعمة غير منتظمة.",
    "اسأل عن نوع الورق. الفنان الجاد سيسمّيه لك، مثل ورق قطني 300 غرام."
   ]
  }
 },
 {
  "id": "oil",
  "cat": "traditional",
  "type": "oil",
  "level": "advanced",
  "en": {
   "name": "Oil Painting",
   "feel": "Timeless",
   "essence": "Slow-drying color with a deep glow, built in patient layers.",
   "story": [
    "Oil paint is older than many think. Painters in the Bamiyan caves of Afghanistan used oil binders around the 600s. But it truly flowered in Flanders in the 1400s.",
    "Jan van Eyck’s glowing layers showed what oil could do. Leonardo, Rembrandt and Vermeer built light and shadow with it. One portrait could take months of thin, slow layers.",
    "Oil dries slowly, so the artist can return again and again. Today, painters from Cairo to Jeddah still love its depth. Mahmoud Saïd and Safeya Binzagr painted their own worlds in oil. The surface remembers every decision."
   ],
   "tech": [
    [
     "Underpainting",
     "A first single-color layer that maps light and shadow."
    ],
    [
     "Glazing",
     "Thin transparent layers that give a deep, glowing color."
    ],
    [
     "Impasto",
     "Thick paint that stands up from the canvas and catches light."
    ],
    [
     "Chiaroscuro",
     "Strong contrast between light and dark to shape form."
    ],
    [
     "Sfumato",
     "Soft, smoky blending with no hard outlines."
    ],
    [
     "Fat over lean",
     "Each layer holds a bit more oil than the last, to prevent cracks."
    ],
    [
     "Alla prima",
     "Wet paint into wet paint, finished in one sitting."
    ]
   ],
   "signs": [
    "Brushstrokes with direction and ridges you can see from the side.",
    "Brush hairs caught in the paint, and colors left partly unmixed.",
    "Edges softened by a thumb or a rag, slightly imperfect.",
    "Ghost shapes, called pentimenti, where the artist changed an earlier idea.",
    "Fine cracks on older works, as layers age at different speeds."
   ],
   "tools": [
    [
     "Linen or cotton canvas",
     "Stretched over a wooden frame, the classic surface for oil."
    ],
    [
     "Gesso",
     "A primer that seals the canvas so the oil cannot harm it."
    ],
    [
     "Stiff bristle brushes",
     "Move thick paint and leave clear, lively marks."
    ],
    [
     "Soft brushes",
     "For smooth glazes and fine detail."
    ],
    [
     "Palette knife",
     "Mixes color and lays down thick, sculpted paint."
    ],
    [
     "Linseed oil",
     "Thins the paint and adds richness and gloss."
    ],
    [
     "Odorless mineral spirits",
     "Cleans brushes and thins the early layers."
    ],
    [
     "Easel",
     "Holds the canvas steady and upright through long sessions."
    ]
   ],
   "time": "Several days to several months",
   "bestFor": "Painters who love depth and patience, and collectors seeking work that lasts generations.",
   "artists": [
    [
     "Jan van Eyck",
     "Flemish, c. 1390–1441",
     "His jewel-like layers set the standard for oil painting in Europe."
    ],
    [
     "Leonardo da Vinci",
     "Italian, 1452–1519",
     "He perfected sfumato, the smoky blending seen in the Mona Lisa."
    ],
    [
     "Rembrandt van Rijn",
     "Dutch, 1606–1669",
     "His thick, warm paint and deep shadows give faces a living soul."
    ],
    [
     "Johannes Vermeer",
     "Dutch, 1632–1675",
     "He painted quiet rooms filled with soft, pearly daylight."
    ],
    [
     "Mahmoud Saïd",
     "Egyptian, 1897–1964",
     "A pioneer of modern Egyptian painting, with warm, glowing figures and landscapes."
    ],
    [
     "Safeya Binzagr",
     "Saudi, 1940–2024",
     "She painted Hejazi homes, dress and customs, keeping Saudi heritage alive."
    ]
   ],
   "guide": [
    "Look at the canvas from the side. Real oil has raised texture and brush marks.",
    "Check the back. You should see raw canvas, a wooden frame and often a signature or date.",
    "Watch the shine. Real oil has uneven gloss, not one flat glossy coat."
   ]
  },
  "ar": {
   "name": "الرسم الزيتي",
   "feel": "خالد",
   "essence": "ألوان بطيئة الجفاف بوهج عميق، تُبنى طبقة فوق طبقة.",
   "story": [
    "الرسم الزيتي أقدم مما يظن كثيرون. استعمل رسامو كهوف باميان في أفغانستان زيوتًا رابطة نحو القرن السابع. لكنه ازدهر حقًا في فلاندرز في القرن الخامس عشر.",
    "أظهرت طبقات يان فان آيك المتوهجة ما يستطيعه الزيت. ثم بنى به ليوناردو ورامبرانت وفيرمير الضوء والظل. وقد تستغرق لوحة شخصية واحدة شهورًا من الطبقات الرقيقة.",
    "يجف الزيت ببطء، فيعود إليه الفنان مرة بعد مرة. واليوم ما زال رسامون من القاهرة إلى جدة يعشقون عمقه. رسم به محمود سعيد وصفية بن زقر عوالمهما الخاصة. وسطح اللوحة يتذكر كل قرار."
   ],
   "tech": [
    [
     "الرسم التحضيري",
     "طبقة أولى بلون واحد ترسم خريطة الضوء والظل."
    ],
    [
     "التزجيج",
     "طبقات شفافة رقيقة تمنح اللون وهجًا عميقًا."
    ],
    [
     "الإمباستو",
     "لون كثيف يبرز من القماش ويلتقط الضوء."
    ],
    [
     "الكياروسكورو",
     "تباين قوي بين الضوء والظل لتجسيم الشكل."
    ],
    [
     "السفوماتو",
     "مزج ناعم كالدخان بلا خطوط حادة."
    ],
    [
     "الدهني فوق الخفيف",
     "كل طبقة فيها زيت أكثر قليلًا مما تحتها، كي لا تتشقق."
    ],
    [
     "ألا بريما",
     "لون رطب فوق لون رطب، وتكتمل اللوحة في جلسة واحدة."
    ]
   ],
   "signs": [
    "ضربات فرشاة لها اتجاه ونتوءات تراها من الجانب.",
    "شعيرات فرشاة عالقة في اللون، وألوان لم تمتزج تمامًا.",
    "حواف نعّمها إبهام أو قطعة قماش، غير مثالية قليلًا.",
    "أشكال خفية تظهر من تحت اللون، حيث غيّر الفنان فكرته الأولى.",
    "تشققات دقيقة في الأعمال القديمة، لأن الطبقات تشيخ بسرعات مختلفة."
   ],
   "tools": [
    [
     "قماش كتان أو قطن",
     "مشدود على إطار خشبي، السطح الكلاسيكي للزيت."
    ],
    [
     "الجيسو",
     "طبقة تأسيس تحمي القماش من تأثير الزيت."
    ],
    [
     "فرش خشنة",
     "تحرك اللون الكثيف وتترك أثرًا واضحًا حيًا."
    ],
    [
     "فرش ناعمة",
     "للطبقات الشفافة الملساء والتفاصيل الدقيقة."
    ],
    [
     "سكين الألوان",
     "لخلط اللون ووضعه كثيفًا كأنه منحوت."
    ],
    [
     "زيت بذر الكتان",
     "يخفف اللون ويمنحه غنى ولمعانًا."
    ],
    [
     "مذيب عديم الرائحة",
     "لتنظيف الفرش وتخفيف الطبقات الأولى."
    ],
    [
     "حامل اللوحة",
     "يثبت القماش قائمًا طوال الجلسات الطويلة."
    ]
   ],
   "time": "من عدة أيام إلى عدة أشهر",
   "bestFor": "للرسام الذي يحب العمق والصبر، وللمقتني الباحث عن عمل يبقى لأجيال.",
   "artists": [
    [
     "يان فان آيك",
     "فلمنكي، نحو 1390–1441",
     "صارت طبقاته المتلألئة كالجواهر معيار الرسم الزيتي في أوروبا."
    ],
    [
     "ليوناردو دا فينشي",
     "إيطالي، 1452–1519",
     "أتقن السفوماتو، المزج الدخاني الذي نراه في الموناليزا."
    ],
    [
     "رامبرانت فان راين",
     "هولندي، 1606–1669",
     "لونه الكثيف الدافئ وظلاله العميقة تمنح الوجوه روحًا حية."
    ],
    [
     "يوهانس فيرمير",
     "هولندي، 1632–1675",
     "رسم غرفًا هادئة يملؤها ضوء النهار الناعم كاللؤلؤ."
    ],
    [
     "محمود سعيد",
     "مصري، 1897–1964",
     "رائد الرسم المصري الحديث، بشخوص ومناظر دافئة متوهجة."
    ],
    [
     "صفية بن زقر",
     "سعودية، 1940–2024",
     "رسمت البيوت والأزياء والعادات الحجازية، فحفظت التراث السعودي حيًا."
    ]
   ],
   "guide": [
    "انظر إلى القماش من الجانب. الزيت الحقيقي له ملمس بارز وآثار فرشاة.",
    "افحص ظهر اللوحة. سترى قماشًا خامًا وإطارًا خشبيًا، وغالبًا توقيعًا أو تاريخًا.",
    "راقب اللمعان. الزيت الحقيقي لمعانه متفاوت، لا طبقة لامعة واحدة مسطحة."
   ]
  }
 },
 {
  "id": "charcoal",
  "cat": "traditional",
  "type": "charcoal",
  "level": "beginner",
  "en": {
   "name": "Charcoal",
   "feel": "Raw",
   "essence": "Burnt wood on paper, the most direct line from hand to surface.",
   "story": [
    "Charcoal is humanity’s first drawing tool. Artists in the Chauvet Cave in France drew with it over 30,000 years ago.",
    "For centuries, painters used it for quick sketches, then brushed it away. Renaissance masters drew whole compositions in charcoal before painting. In the 1800s, Odilon Redon made charcoal the final work. His dark drawings, called noirs, feel like dreams.",
    "Today, William Kentridge films his charcoal drawings as he changes them. Charcoal is honest. It smudges, breaks and stains the fingers. Every mark shows the pressure and speed of the hand."
   ],
   "tech": [
    [
     "Vine charcoal",
     "Light, soft sticks for first lines that erase easily."
    ],
    [
     "Compressed charcoal",
     "Dense sticks for deep, velvety blacks."
    ],
    [
     "Charcoal pencil",
     "A wooden pencil for fine lines and detail."
    ],
    [
     "Blending",
     "Softening tones with fingers, cloth or a paper stump."
    ],
    [
     "Subtractive drawing",
     "Pulling light out of a dark area with an eraser."
    ],
    [
     "Fixing",
     "A light spray that stops the charcoal from smudging."
    ]
   ],
   "signs": [
    "Lines that change width with pressure, thick then thin.",
    "Fine charcoal dust resting on top of the paper texture.",
    "Faint ghost lines where the artist erased and drew again.",
    "Tiny white specks of paper showing through the darkest areas.",
    "Small smudges or fingerprints near the edges."
   ],
   "tools": [
    [
     "Vine charcoal",
     "Thin, soft sticks for light first lines."
    ],
    [
     "Compressed charcoal",
     "Dense blocks for strong, rich blacks."
    ],
    [
     "Charcoal pencils",
     "Wrapped in wood for control and fine detail."
    ],
    [
     "Textured drawing paper",
     "A rough surface, or tooth, that grips the charcoal."
    ],
    [
     "Kneaded eraser",
     "A soft putty eraser that lifts charcoal gently."
    ],
    [
     "Blending stump",
     "A tight roll of paper for smooth, soft tones."
    ],
    [
     "Chamois cloth",
     "Soft leather that sweeps broad areas into gentle greys."
    ],
    [
     "Fixative spray",
     "Holds the finished drawing in place."
    ]
   ],
   "time": "1–6 hours",
   "bestFor": "Artists who love drama and directness, and collectors drawn to raw, honest marks.",
   "artists": [
    [
     "Odilon Redon",
     "French, 1840–1916",
     "His charcoal noirs filled the paper with dreamlike, mysterious figures."
    ],
    [
     "Käthe Kollwitz",
     "German, 1867–1945",
     "Her powerful drawings of mothers, workers and grief carry deep compassion."
    ],
    [
     "John Singer Sargent",
     "American, 1856–1925",
     "Later in life, he drew hundreds of quick, elegant charcoal portraits."
    ],
    [
     "William Kentridge",
     "South African, born 1955",
     "He animates charcoal drawings by erasing and redrawing them for film."
    ]
   ],
   "guide": [
    "Look closely with the light at an angle. Real charcoal has a matte, dusty sheen.",
    "Ask if it is fixed and framed under glass. Unfixed charcoal must never be touched.",
    "Prints of charcoal look too even. Real blacks move from soft grey to deep black."
   ]
  },
  "ar": {
   "name": "الفحم",
   "feel": "خام",
   "essence": "خشب محروق على ورق، أقصر طريق بين اليد والسطح.",
   "story": [
    "الفحم أول أداة رسم عرفها الإنسان. رسم به فنانو كهف شوفيه في فرنسا قبل أكثر من 30 ألف سنة.",
    "ظل الرسامون قرونًا يستعملونه للمسودات السريعة ثم يمسحونه. ورسم به فنانو عصر النهضة تكوينات كاملة قبل التلوين. وفي القرن التاسع عشر جعله أوديلون رودون عملًا نهائيًا. رسومه السوداء تبدو كالأحلام.",
    "واليوم يصوّر ويليام كنتريدج رسومه بالفحم وهو يغيّرها، فيصنع منها أفلامًا. الفحم صادق. يتلطخ وينكسر ويترك أثره على الأصابع. كل خط يكشف ضغط اليد وسرعتها."
   ],
   "tech": [
    [
     "فحم الكرمة",
     "أعواد خفيفة طرية للخطوط الأولى، تُمسح بسهولة."
    ],
    [
     "الفحم المضغوط",
     "أعواد كثيفة لسواد عميق مخملي."
    ],
    [
     "قلم الفحم",
     "قلم خشبي للخطوط الدقيقة والتفاصيل."
    ],
    [
     "المزج",
     "تنعيم الدرجات بالأصابع أو القماش أو أداة ورقية."
    ],
    [
     "الرسم بالحذف",
     "استخراج الضوء من منطقة داكنة بالممحاة."
    ],
    [
     "التثبيت",
     "رذاذ خفيف يمنع الفحم من التلطخ."
    ]
   ],
   "signs": [
    "خطوط يتغير عرضها مع الضغط، سميكة ثم رفيعة.",
    "غبار فحم ناعم يستقر فوق ملمس الورق.",
    "خطوط باهتة حيث مسح الفنان ثم رسم من جديد.",
    "نقاط بيضاء صغيرة من الورق تظهر داخل أشد المناطق سوادًا.",
    "لطخات صغيرة أو بصمات أصابع قرب الحواف."
   ],
   "tools": [
    [
     "فحم الكرمة",
     "أعواد رفيعة طرية للخطوط الأولى الخفيفة."
    ],
    [
     "الفحم المضغوط",
     "قطع كثيفة لسواد قوي وغني."
    ],
    [
     "أقلام الفحم",
     "مغلفة بالخشب للتحكم والتفاصيل الدقيقة."
    ],
    [
     "ورق رسم خشن",
     "سطح خشن يمسك بالفحم جيدًا."
    ],
    [
     "ممحاة عجينية",
     "ممحاة لينة ترفع الفحم بلطف."
    ],
    [
     "أداة المزج الورقية",
     "لفافة ورق مضغوطة لدرجات ناعمة ملساء."
    ],
    [
     "قطعة جلد الشمواه",
     "جلد ناعم يحوّل المساحات الواسعة إلى رماديات هادئة."
    ],
    [
     "رذاذ التثبيت",
     "يثبّت الرسم بعد اكتماله."
    ]
   ],
   "time": "من ساعة إلى ست ساعات",
   "bestFor": "للفنان الذي يحب الدراما والصدق، وللمقتني الذي تجذبه الخطوط الخام الصادقة.",
   "artists": [
    [
     "أوديلون رودون",
     "فرنسي، 1840–1916",
     "ملأت رسومه السوداء بالفحم الورق بشخوص غامضة كالأحلام."
    ],
    [
     "كيته كولفيتز",
     "ألمانية، 1867–1945",
     "رسومها القوية للأمهات والعمال والحزن تفيض بالرحمة."
    ],
    [
     "جون سنجر سارجنت",
     "أمريكي، 1856–1925",
     "رسم في سنواته الأخيرة مئات الوجوه السريعة الأنيقة بالفحم."
    ],
    [
     "ويليام كنتريدج",
     "جنوب أفريقي، مواليد 1955",
     "يحرّك رسومه بالفحم بمسحها وإعادة رسمها أمام الكاميرا."
    ]
   ],
   "guide": [
    "انظر عن قرب والضوء مائل. الفحم الحقيقي له لمعة مطفأة ترابية.",
    "اسأل هل العمل مثبّت ومؤطّر خلف زجاج. الفحم غير المثبت لا يُلمس أبدًا.",
    "المطبوع من الفحم يبدو متساويًا جدًا. السواد الحقيقي يتدرج من الرمادي إلى الأسود العميق."
   ]
  }
 },
 {
  "id": "acrylic",
  "cat": "traditional",
  "type": "acrylic",
  "level": "beginner",
  "en": {
   "name": "Acrylic",
   "feel": "Bold",
   "essence": "Fast-drying, vivid color that can act like watercolor or oil.",
   "story": [
    "Acrylic paint is young. Artist acrylics appeared in the 1940s and 1950s, born from modern plastics.",
    "Mexican muralists like David Alfaro Siqueiros tested synthetic paints on huge outdoor walls. Then came Pop Art. Roy Lichtenstein and Andy Warhol loved its flat, bright punch. Helen Frankenthaler switched to acrylic to stain raw canvas with thin color. David Hockney used it for sunlit California pools.",
    "Today, it is the paint of murals, studios and first lessons around the world. It dries in minutes, so an idea can be finished in a day. It can be thin as ink or thick as clay."
   ],
   "tech": [
    [
     "Flat color fields",
     "Even, solid areas of color with clean edges."
    ],
    [
     "Staining",
     "Very thin paint soaked into raw canvas."
    ],
    [
     "Impasto with gel",
     "Thick gel mediums that build raised texture."
    ],
    [
     "Masking",
     "Tape used to make sharp, hard edges."
    ],
    [
     "Dry brushing",
     "A nearly dry brush dragged to show texture."
    ],
    [
     "Layering",
     "Fast drying lets many layers stack in one session."
    ],
    [
     "Pouring",
     "Fluid paint poured and tilted into flowing patterns."
    ]
   ],
   "signs": [
    "Tape edges that are sharp but slightly lifted or bled in places.",
    "Brush marks inside flat areas when you look from the side.",
    "Layers you can see at the edges of the canvas.",
    "Raised ghost shapes where the artist painted over an earlier idea.",
    "Tiny brush hairs or specks trapped in the fast-drying paint."
   ],
   "tools": [
    [
     "Heavy body acrylics",
     "Thick, buttery paint that holds brush marks."
    ],
    [
     "Fluid acrylics",
     "Thin, flowing paint for washes, stains and pours."
    ],
    [
     "Synthetic brushes",
     "Tough brushes that handle acrylic and wash out with water."
    ],
    [
     "Gel medium and modeling paste",
     "Builds texture, stretches paint and adds body."
    ],
    [
     "Painter’s tape",
     "Masks areas for crisp, straight edges."
    ],
    [
     "Stay-wet palette",
     "A damp palette that keeps paint from drying too fast."
    ],
    [
     "Spray bottle",
     "A fine mist that keeps paint workable longer."
    ],
    [
     "Primed canvas or panel",
     "A sealed surface ready for paint."
    ]
   ],
   "time": "A few hours to a few days",
   "bestFor": "Artists who love speed and bold color, and collectors who want bright, durable work.",
   "artists": [
    [
     "David Alfaro Siqueiros",
     "Mexican, 1896–1974",
     "He pioneered industrial and synthetic paints on huge public murals."
    ],
    [
     "Roy Lichtenstein",
     "American, 1923–1997",
     "He painted his crisp comic-style dots and outlines with acrylic-based paint."
    ],
    [
     "Andy Warhol",
     "American, 1928–1987",
     "He painted acrylic backgrounds, then screen printed famous faces over them."
    ],
    [
     "Helen Frankenthaler",
     "American, 1928–2011",
     "She poured thinned paint into raw canvas, inventing the soak-stain method."
    ],
    [
     "David Hockney",
     "British, born 1937",
     "His sunlit swimming pools showed acrylic’s clean, brilliant color."
    ]
   ],
   "guide": [
    "Look along the surface. Hand-painted acrylic shows brush direction, even in flat areas.",
    "Check the sides of the canvas. Real works often carry drips or wrapped color there.",
    "Ask if it is one of a kind. An original painting is unique, not a print with added gloss."
   ]
  },
  "ar": {
   "name": "الأكريليك",
   "feel": "جريء",
   "essence": "لون سريع الجفاف وزاهٍ، يتصرف كالمائي أو كالزيت.",
   "story": [
    "الأكريليك لون حديث العهد. ظهرت ألوانه الفنية في الأربعينيات والخمسينيات من القرن العشرين، مولودة من البلاستيك الحديث.",
    "جرّب رسامو الجداريات المكسيكيون، مثل دافيد ألفارو سيكيروس، الألوان الصناعية على جدران ضخمة. ثم جاء فن البوب. أحب روي ليختنشتاين وآندي وارهول ألوانه المسطحة الزاهية. وانتقلت هيلين فرانكنثالر إليه لتصبغ القماش الخام بألوان رقيقة. ورسم به ديفيد هوكني مسابح كاليفورنيا المشمسة.",
    "واليوم هو لون الجداريات والمراسم والدروس الأولى حول العالم. يجف في دقائق، فتكتمل الفكرة في يوم واحد. وقد يكون رقيقًا كالحبر أو كثيفًا كالطين."
   ],
   "tech": [
    [
     "مساحات لونية مسطحة",
     "مساحات متساوية من لون صلب بحواف نظيفة."
    ],
    [
     "الصبغ",
     "لون رقيق جدًا يتشربه القماش الخام."
    ],
    [
     "الإمباستو بالجل",
     "وسائط جل كثيفة تبني ملمسًا بارزًا."
    ],
    [
     "الحجب بالشريط",
     "شريط لاصق يصنع حوافًا حادة."
    ],
    [
     "الفرشاة الجافة",
     "فرشاة شبه جافة تُسحب لإظهار الملمس."
    ],
    [
     "الطبقات",
     "الجفاف السريع يسمح بطبقات كثيرة في جلسة واحدة."
    ],
    [
     "الصب",
     "لون سائل يُصب ويُمال ليصنع أنماطًا متدفقة."
    ]
   ],
   "signs": [
    "حواف شريط حادة، لكنها مرتفعة أو متسربة قليلًا في مواضع.",
    "آثار فرشاة داخل المساحات المسطحة عند النظر من الجانب.",
    "طبقات تراها عند حواف القماش.",
    "أشكال بارزة خفية حيث رسم الفنان فوق فكرة سابقة.",
    "شعيرات فرشاة أو ذرات صغيرة عالقة في اللون السريع الجفاف."
   ],
   "tools": [
    [
     "أكريليك كثيف",
     "لون كثيف كالزبدة يحتفظ بأثر الفرشاة."
    ],
    [
     "أكريليك سائل",
     "لون رقيق متدفق للمساحات والصبغ والصب."
    ],
    [
     "فرش صناعية",
     "فرش متينة تتحمل الأكريليك وتُغسل بالماء."
    ],
    [
     "وسيط الجل ومعجون التشكيل",
     "يبني الملمس ويزيد كمية اللون وكثافته."
    ],
    [
     "شريط الدهان",
     "يحجب المساحات لحواف مستقيمة حادة."
    ],
    [
     "لوح خلط رطب",
     "لوح مبلل يمنع اللون من الجفاف السريع."
    ],
    [
     "بخاخ ماء",
     "رذاذ ناعم يُبقي اللون صالحًا للعمل مدة أطول."
    ],
    [
     "قماش أو لوح مؤسَّس",
     "سطح جاهز لاستقبال اللون."
    ]
   ],
   "time": "من ساعات قليلة إلى أيام قليلة",
   "bestFor": "للفنان الذي يحب السرعة واللون الجريء، وللمقتني الذي يريد عملًا زاهيًا يدوم.",
   "artists": [
    [
     "دافيد ألفارو سيكيروس",
     "مكسيكي، 1896–1974",
     "كان رائدًا في استعمال الألوان الصناعية في جدارياته العامة الضخمة."
    ],
    [
     "روي ليختنشتاين",
     "أمريكي، 1923–1997",
     "رسم نقاطه وخطوطه الشبيهة بالقصص المصورة بألوان أكريليكية."
    ],
    [
     "آندي وارهول",
     "أمريكي، 1928–1987",
     "رسم خلفيات بالأكريليك، ثم طبع فوقها وجوهه الشهيرة بالشاشة الحريرية."
    ],
    [
     "هيلين فرانكنثالر",
     "أمريكية، 1928–2011",
     "صبّت ألوانًا مخففة على قماش خام، فابتكرت أسلوب الصبغ بالتشرّب."
    ],
    [
     "ديفيد هوكني",
     "بريطاني، مواليد 1937",
     "أظهرت مسابحه المشمسة صفاء الأكريليك ولمعانه."
    ]
   ],
   "guide": [
    "انظر على امتداد السطح. الأكريليك المرسوم باليد يُظهر اتجاه الفرشاة، حتى في المساحات المسطحة.",
    "افحص جوانب القماش. الأعمال الأصلية تحمل غالبًا قطرات أو لونًا ممتدًا عليها.",
    "اسأل هل العمل قطعة فريدة. اللوحة الأصلية واحدة، لا طباعة أضيف إليها لمعان."
   ]
  }
 },
 {
  "id": "ink",
  "cat": "traditional",
  "type": "ink",
  "level": "intermediate",
  "en": {
   "name": "Ink & Pen",
   "feel": "Precise",
   "essence": "Line in its purest form, where every stroke is final.",
   "story": [
    "Ink has carried human thought for thousands of years. In China and Japan, ink painting grew beside poetry and calligraphy. Arab calligraphers made their own inks with great care.",
    "Sesshū painted whole mountains in a few strokes. In Europe, Dürer and Rembrandt filled sketchbooks with pen drawings. Hokusai’s Manga books gathered thousands of lively drawings of daily life. Later, Aubrey Beardsley made black and white illustration an art of its own.",
    "Today, illustrators and comic artists worldwide still reach for pen and ink. Ink forgives nothing. Once it touches the paper, it stays. That is why a confident ink line feels so alive."
   ],
   "tech": [
    [
     "Hatching",
     "Parallel lines that build shadow."
    ],
    [
     "Cross-hatching",
     "Crossed lines for deeper darks."
    ],
    [
     "Stippling",
     "Thousands of dots that create tone."
    ],
    [
     "Ink wash",
     "Ink thinned with water for soft grey tones."
    ],
    [
     "Dip pen line",
     "A flexible nib that swells and thins with pressure."
    ],
    [
     "Brush and ink",
     "A pointed brush for bold strokes that swell and taper."
    ]
   ],
   "signs": [
    "Lines that swell and thin with the pressure of the nib.",
    "Small blots or pooled ink at the start or end of a stroke.",
    "Slightly raised, shiny ink when light hits it at an angle.",
    "Faint pencil guidelines under the ink, or gentle erasing marks.",
    "Slight feathering where ink soaked into the paper fibers."
   ],
   "tools": [
    [
     "Dip pen and nibs",
     "Flexible steel nibs for expressive, varied lines."
    ],
    [
     "India ink",
     "A deep, permanent black ink made from carbon."
    ],
    [
     "Technical pens",
     "Steady, even lines for precise detail."
    ],
    [
     "Pointed brushes",
     "For bold strokes, fills and soft washes."
    ],
    [
     "Smooth Bristol paper",
     "A hard, smooth surface that keeps lines crisp."
    ],
    [
     "Ink stick and ink stone",
     "Ground with water to make traditional East Asian ink."
    ],
    [
     "Pencil and soft eraser",
     "For light guidelines, erased after inking."
    ],
    [
     "Blotting paper",
     "Lifts extra ink to stop smears."
    ]
   ],
   "time": "2–10 hours",
   "bestFor": "Artists who love control and line, and collectors who admire fine detail.",
   "artists": [
    [
     "Sesshū Tōyō",
     "Japanese, 1420–1506",
     "A master whose ink landscapes shaped Japanese painting for centuries."
    ],
    [
     "Rembrandt van Rijn",
     "Dutch, 1606–1669",
     "His quick pen and wash sketches capture life in a few lines."
    ],
    [
     "Katsushika Hokusai",
     "Japanese, 1760–1849",
     "His Hokusai Manga books gathered thousands of lively drawings of daily life."
    ],
    [
     "Qi Baishi",
     "Chinese, 1864–1957",
     "He painted shrimp, birds and flowers in fresh, playful ink strokes."
    ],
    [
     "Aubrey Beardsley",
     "British, 1872–1898",
     "His bold black and white pen drawings defined Art Nouveau illustration."
    ]
   ],
   "guide": [
    "Use a magnifier. Printed lines are made of tiny dots. Real ink lines are solid.",
    "Look for small differences. No two hand-drawn hatching lines are ever the same.",
    "Check the back of the paper. Strong ink sometimes shows through faintly."
   ]
  },
  "ar": {
   "name": "الحبر والقلم",
   "feel": "دقيق",
   "essence": "الخط في أنقى صوره، حيث كل ضربة نهائية.",
   "story": [
    "حمل الحبر أفكار البشر آلاف السنين. في الصين واليابان نما الرسم بالحبر إلى جانب الشعر والخط. وصنع الخطاطون العرب أحبارهم بعناية كبيرة.",
    "رسم سيسشو جبالًا كاملة ببضع ضربات. وفي أوروبا ملأ دورر ورامبرانت دفاترهما برسوم القلم. وجمعت كتب هوكوساي آلاف الرسوم الحية للحياة اليومية. ثم جعل أوبري بيردسلي الرسم بالأبيض والأسود فنًا قائمًا بذاته.",
    "واليوم ما زال الرسامون وفنانو القصص المصورة حول العالم يمسكون القلم والحبر. الحبر لا يغفر. ما إن يلمس الورق حتى يبقى. لذلك يبدو خط الحبر الواثق حيًا."
   ],
   "tech": [
    [
     "التظليل بالخطوط",
     "خطوط متوازية تبني الظل."
    ],
    [
     "التظليل المتقاطع",
     "خطوط متقاطعة لظلال أعمق."
    ],
    [
     "التنقيط",
     "آلاف النقاط التي تصنع الدرجات."
    ],
    [
     "الغسل بالحبر",
     "حبر مخفف بالماء لدرجات رمادية ناعمة."
    ],
    [
     "خط الريشة",
     "سن مرن يتسع ويدق مع الضغط."
    ],
    [
     "الفرشاة والحبر",
     "فرشاة مدببة لضربات جريئة تتسع ثم تدق."
    ]
   ],
   "signs": [
    "خطوط تتسع وتدق مع ضغط سن الريشة.",
    "بقع صغيرة أو حبر متجمع في بداية الخط أو نهايته.",
    "حبر بارز قليلًا يلمع حين يسقط عليه الضوء مائلًا.",
    "خطوط قلم رصاص باهتة تحت الحبر، أو آثار ممحاة خفيفة.",
    "انتشار خفيف حيث تشرّبت ألياف الورق الحبر."
   ],
   "tools": [
    [
     "الريشة والأسنان المعدنية",
     "أسنان فولاذية مرنة لخطوط معبّرة متنوعة."
    ],
    [
     "الحبر الصيني",
     "حبر أسود عميق ثابت مصنوع من الكربون."
    ],
    [
     "الأقلام التقنية",
     "خطوط ثابتة متساوية للتفاصيل الدقيقة."
    ],
    [
     "فرش مدببة",
     "للضربات الجريئة والتعبئة والغسل الناعم."
    ],
    [
     "ورق بريستول أملس",
     "سطح صلب أملس يحافظ على حدة الخطوط."
    ],
    [
     "عصا الحبر وحجر الحبر",
     "يُطحنان بالماء لصنع الحبر التقليدي في شرق آسيا."
    ],
    [
     "قلم رصاص وممحاة لينة",
     "لخطوط إرشادية خفيفة تُمسح بعد التحبير."
    ],
    [
     "ورق نشّاف",
     "يرفع الحبر الزائد ويمنع التلطخ."
    ]
   ],
   "time": "من ساعتين إلى عشر ساعات",
   "bestFor": "للفنان الذي يحب التحكم والخط، وللمقتني الذي يقدّر التفاصيل الدقيقة.",
   "artists": [
    [
     "سيسشو تويو",
     "ياباني، 1420–1506",
     "أستاذ شكّلت مناظره الطبيعية بالحبر الرسم الياباني قرونًا."
    ],
    [
     "رامبرانت فان راين",
     "هولندي، 1606–1669",
     "تلتقط رسومه السريعة بالقلم والحبر الحياة في خطوط قليلة."
    ],
    [
     "كاتسوشيكا هوكوساي",
     "ياباني، 1760–1849",
     "جمعت كتبه المعروفة بمانغا هوكوساي آلاف الرسوم الحية للحياة اليومية."
    ],
    [
     "تشي بايشي",
     "صيني، 1864–1957",
     "رسم الروبيان والطيور والزهور بضربات حبر منعشة مرحة."
    ],
    [
     "أوبري بيردسلي",
     "بريطاني، 1872–1898",
     "رسمت رسومه الجريئة بالأبيض والأسود ملامح فن الآرت نوفو."
    ]
   ],
   "guide": [
    "استعمل عدسة مكبرة. الخطوط المطبوعة نقاط صغيرة، وخطوط الحبر الحقيقية متصلة.",
    "ابحث عن الفروق الصغيرة. لا يتطابق خطّا تظليل مرسومان باليد أبدًا.",
    "افحص ظهر الورقة. الحبر القوي يظهر أحيانًا من الخلف بخفة."
   ]
  }
 },
 {
  "id": "pastel",
  "cat": "traditional",
  "type": "pastel",
  "level": "intermediate",
  "en": {
   "name": "Pastel",
   "feel": "Soft",
   "essence": "Pure pigment in a stick, the closest thing to drawing with light.",
   "story": [
    "Pastel is almost pure pigment, pressed into a stick with a little binder. Its colors stay bright and do not yellow like old varnish.",
    "In the 1700s, Rosalba Carriera made pastel portraits famous across Europe. Jean-Étienne Liotard and Maurice Quentin de La Tour followed. Later, Edgar Degas drew his dancers in layer after layer of pastel. Mary Cassatt used it for tender scenes of mothers and children.",
    "Today, pastel artists around the world gather in societies and fill galleries with color. Pastel is touched, not brushed. The artist draws, blends with a finger, then draws again. It is fragile, like a memory."
   ],
   "tech": [
    [
     "Layering",
     "Many light layers built up for rich, deep color."
    ],
    [
     "Finger blending",
     "Softening color with a fingertip for smooth skin and skies."
    ],
    [
     "Scumbling",
     "Light, broken layers over another color."
    ],
    [
     "Feathering",
     "Short parallel strokes that mix in the eye."
    ],
    [
     "Toned paper",
     "Colored paper that shows through as a middle tone."
    ],
    [
     "Sgraffito",
     "Scratching through oil pastel to reveal the color below."
    ]
   ],
   "signs": [
    "Chalky texture caught on the rough tooth of the paper.",
    "Finger blending marks, soft and slightly uneven.",
    "Separate strokes of color that only mix from a distance.",
    "Specks of paper showing where the stick skipped across the surface.",
    "Fine pastel dust inside the frame, near the bottom edge."
   ],
   "tools": [
    [
     "Soft pastels",
     "Rich, chalky sticks of nearly pure pigment."
    ],
    [
     "Hard pastels",
     "Firmer sticks for edges and early drawing."
    ],
    [
     "Pastel pencils",
     "Pastel in wood, for fine lines and detail."
    ],
    [
     "Oil pastels",
     "Creamy, waxy sticks that do not dust."
    ],
    [
     "Sanded pastel paper",
     "A gritty surface that holds many layers."
    ],
    [
     "Colored pastel paper",
     "Toned sheets that set the mood of the piece."
    ],
    [
     "Blending stumps",
     "Paper tools for soft, controlled blending."
    ],
    [
     "Glassine paper",
     "A smooth sheet that protects finished work in storage."
    ]
   ],
   "time": "3–12 hours",
   "bestFor": "Artists who love pure color and touch, and collectors who treasure soft, luminous work.",
   "artists": [
    [
     "Rosalba Carriera",
     "Venetian, 1673–1757",
     "She made pastel portraits the height of fashion across Europe."
    ],
    [
     "Jean-Étienne Liotard",
     "Swiss, 1702–1789",
     "His smooth, clear pastels look almost like fine porcelain."
    ],
    [
     "Maurice Quentin de La Tour",
     "French, 1704–1788",
     "He captured lively, smiling faces with sparkling pastel strokes."
    ],
    [
     "Edgar Degas",
     "French, 1834–1917",
     "He layered and fixed pastel to draw dancers in glowing color."
    ],
    [
     "Mary Cassatt",
     "American, 1844–1926",
     "Her tender pastels of mothers and children are full of warmth."
    ]
   ],
   "guide": [
    "Ask for glass, not plastic. Static from plastic pulls pastel off the paper.",
    "Look from an angle. Real pastel has a velvety, matte surface.",
    "Check for spacers in the frame, so the glass never touches the work."
   ]
  },
  "ar": {
   "name": "الباستيل",
   "feel": "ناعم",
   "essence": "صبغة نقية في عصا، أقرب شيء إلى الرسم بالضوء.",
   "story": [
    "الباستيل صبغة شبه نقية مضغوطة في عصا مع قليل من المادة الرابطة. تبقى ألوانه زاهية ولا تصفرّ كالورنيش القديم.",
    "في القرن الثامن عشر جعلت روزالبا كاريرا لوحات الباستيل الشخصية مشهورة في أوروبا كلها. ثم تبعها جان إتيان ليوتار وموريس كانتان دو لا تور. ولاحقًا رسم إدغار ديغا راقصاته طبقة فوق طبقة. واستعملته ماري كاسات لمشاهد حانية بين الأمهات والأطفال.",
    "واليوم يجتمع فنانو الباستيل حول العالم في جمعيات، ويملؤون المعارض بالألوان. الباستيل يُرسم باللمس لا بالفرشاة. يرسم الفنان ويمزج بإصبعه ثم يرسم من جديد. إنه هش كالذكرى."
   ],
   "tech": [
    [
     "الطبقات",
     "طبقات خفيفة كثيرة تبني لونًا غنيًا عميقًا."
    ],
    [
     "المزج بالإصبع",
     "تنعيم اللون بطرف الإصبع للبشرة والسماء."
    ],
    [
     "التغشية",
     "طبقات خفيفة متقطعة فوق لون آخر."
    ],
    [
     "التريش",
     "ضربات قصيرة متوازية تمتزج في العين."
    ],
    [
     "الورق الملون",
     "ورق ملون يظهر من بين الألوان كدرجة وسطى."
    ],
    [
     "الخدش",
     "خدش طبقة الباستيل الزيتي لكشف اللون تحتها."
    ]
   ],
   "signs": [
    "ملمس طباشيري عالق في خشونة الورق.",
    "آثار مزج بالإصبع، ناعمة وغير متساوية قليلًا.",
    "ضربات لونية منفصلة لا تمتزج إلا من بعيد.",
    "نقاط من الورق تظهر حيث قفزت العصا فوق السطح.",
    "غبار باستيل ناعم داخل الإطار، قرب الحافة السفلية."
   ],
   "tools": [
    [
     "باستيل طري",
     "عصي طباشيرية غنية من صبغة شبه نقية."
    ],
    [
     "باستيل صلب",
     "عصي أكثر صلابة للحواف والرسم الأولي."
    ],
    [
     "أقلام الباستيل",
     "باستيل داخل الخشب للخطوط الدقيقة والتفاصيل."
    ],
    [
     "باستيل زيتي",
     "عصي كريمية شمعية لا تترك غبارًا."
    ],
    [
     "ورق باستيل مصنفر",
     "سطح خشن يحمل طبقات كثيرة."
    ],
    [
     "ورق باستيل ملون",
     "أوراق ملونة تحدد جو العمل."
    ],
    [
     "أدوات المزج الورقية",
     "للمزج الناعم المتحكم فيه."
    ],
    [
     "ورق الجلاسين",
     "ورقة ملساء تحمي العمل المكتمل عند التخزين."
    ]
   ],
   "time": "من ثلاث ساعات إلى اثنتي عشرة ساعة",
   "bestFor": "للفنان الذي يحب اللون النقي واللمس، وللمقتني الذي يعتز بالأعمال الناعمة المضيئة.",
   "artists": [
    [
     "روزالبا كاريرا",
     "إيطالية من البندقية، 1673–1757",
     "جعلت لوحات الباستيل الشخصية قمة الأناقة في أوروبا."
    ],
    [
     "جان إتيان ليوتار",
     "سويسري، 1702–1789",
     "لوحاته الصافية الملساء بالباستيل تكاد تشبه الخزف الرفيع."
    ],
    [
     "موريس كانتان دو لا تور",
     "فرنسي، 1704–1788",
     "التقط وجوهًا حية مبتسمة بضربات باستيل متألقة."
    ],
    [
     "إدغار ديغا",
     "فرنسي، 1834–1917",
     "راكم الباستيل وثبّته ليرسم الراقصات بألوان متوهجة."
    ],
    [
     "ماري كاسات",
     "أمريكية، 1844–1926",
     "لوحاتها الحانية للأمهات والأطفال تفيض بالدفء."
    ]
   ],
   "guide": [
    "اطلب زجاجًا لا بلاستيك. كهرباء البلاستيك الساكنة تسحب الباستيل عن الورق.",
    "انظر من زاوية مائلة. الباستيل الحقيقي سطحه مخملي مطفأ.",
    "تأكد من وجود فواصل في الإطار، كي لا يلمس الزجاج العمل."
   ]
  }
 },
 {
  "id": "gouache",
  "cat": "traditional",
  "type": "gouache",
  "level": "intermediate",
  "en": {
   "name": "Gouache",
   "feel": "Velvety",
   "essence": "Opaque watercolor with a soft matte finish and bold, flat color.",
   "story": [
    "Gouache is watercolor made opaque with more pigment or white chalk. It covers the paper fully and dries flat and velvety. Painters across the Islamic world used opaque watercolor for centuries.",
    "Persian and Mughal miniature painters built tiny, jewel-like worlds with it. Kamāl ud-Dīn Behzād painted whole crowds in a few inches. In the 1900s, Mohammed Racim brought miniature painting back to life in Algeria. Henri Matisse painted paper with gouache, then cut it into shapes.",
    "Today, illustrators and animation artists love it for clean, bright color. Mary Blair’s gouache designs shaped classic animated films. Gouache asks for planning. Colors shift as they dry, so the artist must know the color first."
   ],
   "tech": [
    [
     "Flat washes",
     "Smooth, even areas of opaque color."
    ],
    [
     "Light over dark",
     "Opaque paint lets light colors sit on dark ones."
    ],
    [
     "Fine detail",
     "Tiny brushes for miniature-style detail."
    ],
    [
     "Dry brush",
     "Very little water for crisp texture."
    ],
    [
     "Reactivating",
     "Wetting dry paint to soften or blend an edge again."
    ],
    [
     "Cut paper",
     "Paper painted with gouache, then cut and arranged."
    ]
   ],
   "signs": [
    "A matte, chalky surface with no shine.",
    "Slight brush ridges at the edges of flat shapes.",
    "Tiny color changes inside areas that look flat from afar.",
    "Faint pencil lines peeking out near the edges of shapes.",
    "Fine cracks where the paint was laid on too thick."
   ],
   "tools": [
    [
     "Gouache tubes",
     "Creamy, opaque paint mixed or rewetted with water."
    ],
    [
     "Small round brushes",
     "Fine points for detail and clean edges."
    ],
    [
     "Flat brushes",
     "For smooth, even areas of color."
    ],
    [
     "Heavy paper or illustration board",
     "A firm surface that will not buckle."
    ],
    [
     "Palette with wells",
     "Keeps mixed colors separate and ready."
    ],
    [
     "Two water jars",
     "One to rinse, one clean for mixing."
    ],
    [
     "Pencil",
     "A light underdrawing to plan every shape."
    ],
    [
     "Masking tape",
     "Holds the paper flat and leaves a clean border."
    ]
   ],
   "time": "A few hours to several days",
   "bestFor": "Illustrators and detail lovers, and collectors who enjoy bold, velvety color.",
   "artists": [
    [
     "Kamāl ud-Dīn Behzād",
     "Persian, c. 1450–1535",
     "The great master of Herat, famous for lively, crowded miniature scenes."
    ],
    [
     "Reza Abbasi",
     "Persian, c. 1565–1635",
     "He painted elegant single figures for the Safavid court in Isfahan."
    ],
    [
     "Mohammed Racim",
     "Algerian, 1896–1975",
     "He revived Islamic miniature painting with scenes of old Algiers."
    ],
    [
     "Henri Matisse",
     "French, 1869–1954",
     "In his last years, he cut gouache-painted paper into joyful shapes."
    ],
    [
     "Mary Blair",
     "American, 1911–1978",
     "Her bright gouache concept art shaped classic animated films."
    ]
   ],
   "guide": [
    "Tilt it in the light. Gouache stays matte. Prints of it often shine.",
    "Use a magnifier. Real paint shows brush texture. Prints show dots.",
    "Ask how it is protected. Gouache can re-wet, so it needs glass and a dry room."
   ]
  },
  "ar": {
   "name": "الغواش",
   "feel": "مخملي",
   "essence": "ألوان مائية معتمة، بلمسة مطفأة ناعمة ولون مسطح جريء.",
   "story": [
    "الغواش ألوان مائية صارت معتمة بزيادة الصبغة أو الطباشير الأبيض. يغطي الورق تمامًا ويجف مسطحًا مخمليًا. واستعمل رسامو العالم الإسلامي الألوان المائية المعتمة قرونًا.",
    "بنى بها رسامو المنمنمات الفارسية والمغولية في الهند عوالم صغيرة كالجواهر. رسم كمال الدين بهزاد حشودًا كاملة في بضعة سنتيمترات. وفي القرن العشرين أعاد محمد راسم فن المنمنمات إلى الحياة في الجزائر. ورسم هنري ماتيس على الورق بالغواش ثم قصّه أشكالًا.",
    "واليوم يحبه الرسامون التوضيحيون وفنانو الرسوم المتحركة لألوانه النظيفة الزاهية. صمّمت ماري بلير بالغواش عوالم أفلام رسوم متحركة كلاسيكية. الغواش يحتاج إلى تخطيط. تتغير ألوانه قليلًا حين تجف، فيجب أن يعرف الفنان لونه مسبقًا."
   ],
   "tech": [
    [
     "المساحات المسطحة",
     "مساحات ناعمة متساوية من لون معتم."
    ],
    [
     "الفاتح فوق الداكن",
     "اللون المعتم يسمح بوضع الفاتح فوق الداكن."
    ],
    [
     "التفاصيل الدقيقة",
     "فرش صغيرة جدًا لتفاصيل بأسلوب المنمنمات."
    ],
    [
     "الفرشاة الجافة",
     "ماء قليل جدًا لملمس حاد."
    ],
    [
     "إعادة التنشيط",
     "ترطيب اللون الجاف لتليين حافة أو مزجها من جديد."
    ],
    [
     "الورق المقصوص",
     "ورق يُلوَّن بالغواش ثم يُقص ويُرتَّب."
    ]
   ],
   "signs": [
    "سطح مطفأ طباشيري بلا لمعان.",
    "نتوءات فرشاة خفيفة عند حواف الأشكال المسطحة.",
    "تغيرات لونية صغيرة داخل مساحات تبدو مسطحة من بعيد.",
    "خطوط قلم رصاص باهتة تطل قرب حواف الأشكال.",
    "تشققات دقيقة حيث وُضع اللون كثيفًا جدًا."
   ],
   "tools": [
    [
     "أنابيب الغواش",
     "لون كريمي معتم يُخلط بالماء أو يُرطَّب من جديد."
    ],
    [
     "فرش مستديرة صغيرة",
     "رؤوس دقيقة للتفاصيل والحواف النظيفة."
    ],
    [
     "فرش مسطحة",
     "للمساحات اللونية الناعمة المتساوية."
    ],
    [
     "ورق ثقيل أو لوح رسم",
     "سطح متين لا يتموج."
    ],
    [
     "لوح خلط بتجاويف",
     "يحفظ الألوان الممزوجة منفصلة وجاهزة."
    ],
    [
     "وعاءان للماء",
     "واحد للغسل وآخر نظيف للخلط."
    ],
    [
     "قلم رصاص",
     "رسم أولي خفيف لتخطيط كل شكل."
    ],
    [
     "شريط لاصق",
     "يُبقي الورق مستويًا ويترك حافة نظيفة."
    ]
   ],
   "time": "من ساعات قليلة إلى عدة أيام",
   "bestFor": "للرسام التوضيحي ومحب التفاصيل، وللمقتني الذي يستمتع باللون المخملي الجريء.",
   "artists": [
    [
     "كمال الدين بهزاد",
     "فارسي، نحو 1450–1535",
     "أستاذ هراة الكبير، اشتهر بمشاهد منمنمات حية مزدحمة."
    ],
    [
     "رضا عباسي",
     "فارسي، نحو 1565–1635",
     "رسم شخوصًا أنيقة منفردة لبلاط الصفويين في أصفهان."
    ],
    [
     "محمد راسم",
     "جزائري، 1896–1975",
     "أحيا فن المنمنمات الإسلامية بمشاهد من الجزائر القديمة."
    ],
    [
     "هنري ماتيس",
     "فرنسي، 1869–1954",
     "قصّ في سنواته الأخيرة ورقًا ملونًا بالغواش إلى أشكال مبهجة."
    ],
    [
     "ماري بلير",
     "أمريكية، 1911–1978",
     "شكّلت رسومها التصميمية الزاهية بالغواش أفلام رسوم متحركة كلاسيكية."
    ]
   ],
   "guide": [
    "أمِل العمل في الضوء. الغواش يبقى مطفأ، ومطبوعاته تلمع غالبًا.",
    "استعمل عدسة مكبرة. اللون الحقيقي يُظهر ملمس الفرشاة، والمطبوع يُظهر نقاطًا.",
    "اسأل كيف يُحمى العمل. الغواش قد يذوب بالماء، فيحتاج إلى زجاج وغرفة جافة."
   ]
  }
 },
 {
  "id": "printmaking",
  "cat": "traditional",
  "type": "printmaking",
  "level": "advanced",
  "en": {
   "name": "Printmaking",
   "feel": "Patient",
   "essence": "An image carved or etched into a plate, then printed by hand.",
   "story": [
    "Printmaking began with carved wood blocks in China, over a thousand years ago. It lets an artist make a small number of originals from one plate. Each print is pulled by hand.",
    "Albrecht Dürer carved woodcuts so fine they looked like drawings. Rembrandt and Francisco Goya etched copper plates with light and darkness. In Japan, Hokusai and Hiroshige designed prints with expert carvers and printers. Hokusai’s Great Wave was printed from carved wooden blocks.",
    "Today, print studios around the world keep these old skills alive. It is slow, patient work. The artist works in reverse, thinking in mirror images. Only the press reveals the result, at the very end."
   ],
   "tech": [
    [
     "Woodcut",
     "Carving away wood, so the raised parts print."
    ],
    [
     "Linocut",
     "Like woodcut, but carved in soft linoleum."
    ],
    [
     "Etching",
     "Lines bitten into a metal plate with acid."
    ],
    [
     "Engraving",
     "Lines cut straight into metal with a sharp burin."
    ],
    [
     "Lithography",
     "Drawing on stone with a greasy crayon, then printing."
    ],
    [
     "Screen printing",
     "Ink pushed through a fine mesh stencil."
    ],
    [
     "Edition numbering",
     "Each print marked like 3/20 and signed in pencil."
    ]
   ],
   "signs": [
    "A plate mark: a pressed border around etchings and engravings.",
    "Ink that sits slightly raised, or pressed into the paper.",
    "Small differences between prints of the same edition.",
    "A pencil signature and edition number, never printed.",
    "Quality paper with wide margins, often with soft, uneven edges."
   ],
   "tools": [
    [
     "Carving gouges",
     "Sharp V and U tools for cutting wood or lino."
    ],
    [
     "Wood or lino blocks",
     "The surface carved for relief prints."
    ],
    [
     "Copper or zinc plates",
     "Metal plates for etching and engraving."
    ],
    [
     "Etching needle",
     "Draws through a waxy ground before the acid bath."
    ],
    [
     "Brayer",
     "A rubber roller that spreads ink evenly."
    ],
    [
     "Baren",
     "A flat disc for rubbing paper onto an inked block."
    ],
    [
     "Printing press",
     "Applies strong, even pressure to transfer the image."
    ],
    [
     "Printmaking paper",
     "Soft, strong cotton paper that takes ink well."
    ]
   ],
   "time": "Several days to several weeks",
   "bestFor": "Patient artists who love craft, and collectors seeking real originals at gentler prices.",
   "artists": [
    [
     "Albrecht Dürer",
     "German, 1471–1528",
     "His woodcuts and engravings set a standard of detail still admired today."
    ],
    [
     "Rembrandt van Rijn",
     "Dutch, 1606–1669",
     "He reworked his etching plates again and again, changing light and mood."
    ],
    [
     "Francisco Goya",
     "Spanish, 1746–1828",
     "His etching series, like Los Caprichos, used aquatint for deep, smoky tones."
    ],
    [
     "Katsushika Hokusai",
     "Japanese, 1760–1849",
     "His Great Wave became one of the most famous prints in the world."
    ],
    [
     "Utagawa Hiroshige",
     "Japanese, 1797–1858",
     "His woodblock views of rain, snow and roads inspired European painters."
    ]
   ],
   "guide": [
    "Check the number. 5/30 means print five of thirty. Smaller editions are rarer.",
    "Look for a pencil signature. A printed signature means a copy.",
    "Use a magnifier. Hand-pulled prints show solid ink, not the dots of a poster."
   ]
  },
  "ar": {
   "name": "الطباعة الفنية",
   "feel": "صبور",
   "essence": "صورة تُحفر أو تُنقش على لوح، ثم تُطبع باليد.",
   "story": [
    "بدأت الطباعة الفنية بألواح خشبية محفورة في الصين قبل أكثر من ألف عام. تتيح للفنان صنع عدد قليل من النسخ الأصلية من لوح واحد. وكل نسخة تُسحب باليد.",
    "حفر ألبرشت دورر ألواحًا خشبية دقيقة كأنها رسوم. ونقش رامبرانت وفرانسيسكو غويا ألواح النحاس بالضوء والظلمة. وفي اليابان صمّم هوكوساي وهيروشيغي مطبوعاتهما مع حفّارين وطبّاعين مهرة. وطُبعت موجة هوكوساي العظيمة من ألواح خشبية محفورة.",
    "واليوم تحافظ مراسم الطباعة حول العالم على هذه المهارات القديمة. إنه عمل بطيء صبور. يعمل الفنان بالمقلوب ويفكر بصورة المرآة. ولا تكشف المطبعة النتيجة إلا في النهاية."
   ],
   "tech": [
    [
     "الحفر على الخشب",
     "إزالة أجزاء من الخشب لتطبع الأجزاء البارزة."
    ],
    [
     "الحفر على اللينوليوم",
     "مثل الخشب، لكن على مادة اللينوليوم الطرية."
    ],
    [
     "الحفر الحمضي",
     "خطوط يحفرها الحمض في لوح معدني."
    ],
    [
     "النقش المباشر",
     "خطوط تُقطع في المعدن مباشرة بأداة حادة."
    ],
    [
     "الطباعة الحجرية",
     "الرسم على حجر بقلم دهني، ثم الطباعة."
    ],
    [
     "الطباعة بالشاشة الحريرية",
     "حبر يُدفع عبر شبكة دقيقة مع قالب."
    ],
    [
     "ترقيم الإصدار",
     "كل نسخة تُرقَّم مثل 3/20 وتُوقَّع بقلم رصاص."
    ]
   ],
   "signs": [
    "أثر اللوح: إطار مضغوط حول مطبوعات الحفر الحمضي والنقش.",
    "حبر بارز قليلًا، أو مضغوط داخل الورق.",
    "فروق صغيرة بين نسخ الإصدار نفسه.",
    "توقيع ورقم إصدار بقلم رصاص، لا مطبوعان أبدًا.",
    "ورق جيد بهوامش واسعة، وغالبًا بحواف ناعمة غير منتظمة."
   ],
   "tools": [
    [
     "أزاميل الحفر",
     "أدوات حادة لقطع الخشب أو اللينوليوم."
    ],
    [
     "ألواح خشب أو لينوليوم",
     "السطح الذي يُحفر للطباعة البارزة."
    ],
    [
     "ألواح نحاس أو زنك",
     "ألواح معدنية للحفر الحمضي والنقش."
    ],
    [
     "إبرة الحفر",
     "ترسم عبر طبقة شمعية قبل حمّام الحمض."
    ],
    [
     "الأسطوانة المطاطية",
     "تفرد الحبر بالتساوي على اللوح."
    ],
    [
     "البارين",
     "قرص مسطح لفرك الورق فوق اللوح المحبّر."
    ],
    [
     "المطبعة",
     "تضغط بقوة متساوية لنقل الصورة إلى الورق."
    ],
    [
     "ورق الطباعة الفنية",
     "ورق قطني لين متين يتقبل الحبر جيدًا."
    ]
   ],
   "time": "من عدة أيام إلى عدة أسابيع",
   "bestFor": "للفنان الصبور المحب للحرفة، وللمقتني الباحث عن أعمال أصلية بأسعار ألطف.",
   "artists": [
    [
     "ألبرشت دورر",
     "ألماني، 1471–1528",
     "ما زالت دقة حفرياته على الخشب والمعدن معيارًا يُحتذى."
    ],
    [
     "رامبرانت فان راين",
     "هولندي، 1606–1669",
     "أعاد العمل على ألواحه مرارًا، فغيّر الضوء والمزاج."
    ],
    [
     "فرانسيسكو غويا",
     "إسباني، 1746–1828",
     "استعمل في سلاسله، مثل النزوات، تقنية الأكواتنت لدرجات دخانية عميقة."
    ],
    [
     "كاتسوشيكا هوكوساي",
     "ياباني، 1760–1849",
     "صارت موجته العظيمة من أشهر المطبوعات في العالم."
    ],
    [
     "أوتاغاوا هيروشيغي",
     "ياباني، 1797–1858",
     "ألهمت مناظره المطبوعة للمطر والثلج والطرق رسامي أوروبا."
    ]
   ],
   "guide": [
    "افحص الرقم. 5/30 تعني النسخة الخامسة من ثلاثين. الإصدارات الأصغر أندر.",
    "ابحث عن توقيع بقلم رصاص. التوقيع المطبوع يعني نسخة مصوّرة.",
    "استعمل عدسة مكبرة. المطبوعات اليدوية حبرها متصل، لا نقاط الملصقات."
   ]
  }
 },
 {
  "id": "tempera",
  "cat": "traditional",
  "type": "tempera",
  "level": "advanced",
  "en": {
   "name": "Tempera",
   "feel": "Luminous",
   "essence": "Pigment bound with egg yolk, built in fine, glowing layers.",
   "story": [
    "Tempera is one of the oldest painting methods we know. Painters mixed ground pigment with egg yolk. It dried fast, hard and bright.",
    "For centuries it was the main paint on wooden panels across Europe and Byzantium. Botticelli painted his finest works with it. Oil paint slowly took its place after the 1400s.",
    "In the twentieth century, painters like Andrew Wyeth brought it back. Today a small, devoted circle still grinds pigment and cracks eggs. Their patience shows in every stroke."
   ],
   "tech": [
    [
     "Egg yolk binder",
     "Pigment mixed with yolk and a little water, made fresh each day."
    ],
    [
     "Hatching",
     "Fine parallel strokes build tone, because tempera cannot be blended wet."
    ],
    [
     "Cross-hatching",
     "Strokes laid across each other for deeper shadows."
    ],
    [
     "Thin layering",
     "Many transparent layers build color slowly and add glow."
    ],
    [
     "Gesso ground",
     "A smooth white chalk ground that reflects light through the paint."
    ],
    [
     "Gilding",
     "Gold leaf laid on prepared areas, then polished to shine."
    ]
   ],
   "signs": [
    "Tiny hatched strokes you can see up close, like woven threads of color.",
    "A soft satin glow, not the deep shine of oil.",
    "A rigid wooden or board panel, since tempera can crack on bending canvas.",
    "Slight changes in stroke direction that follow the forms of the subject.",
    "Sharp, clean edges, because the paint dries almost the moment it lands."
   ],
   "tools": [
    [
     "Fresh eggs",
     "The yolk is the binder that holds the pigment."
    ],
    [
     "Dry pigments",
     "Pure powdered color, ground by hand with water."
    ],
    [
     "Glass muller and slab",
     "Grinds pigment into a smooth paste."
    ],
    [
     "Wooden panel",
     "A stiff support that keeps the paint from cracking."
    ],
    [
     "Traditional gesso",
     "Chalk and animal glue, sanded smooth as ivory."
    ],
    [
     "Small sable brushes",
     "Fine tips for the many tiny strokes."
    ],
    [
     "Gold leaf",
     "For gilded areas in traditional panels."
    ]
   ],
   "time": "Several weeks",
   "bestFor": "Patient painters who love detail, glow and slow, careful work.",
   "artists": [
    [
     "Sandro Botticelli",
     "Italian, 1445–1510",
     "He painted The Birth of Venus in tempera, full of light and grace."
    ],
    [
     "Andrew Wyeth",
     "American, 1917–2009",
     "He revived egg tempera with quiet, deeply detailed scenes of rural America."
    ],
    [
     "Jacob Lawrence",
     "American, 1917–2000",
     "He told the story of the Great Migration in bold tempera panels."
    ],
    [
     "Ben Shahn",
     "American, 1898–1969",
     "He used tempera for sharp, caring paintings about workers and justice."
    ]
   ],
   "guide": [
    "Look closely with a magnifier. Real tempera shows countless tiny hatched strokes.",
    "Ask what the panel is made of. A serious artist will describe the gesso ground.",
    "Check the surface sheen. Tempera has a soft satin glow, never a plastic gloss."
   ]
  },
  "ar": {
   "name": "التمبرا",
   "feel": "متوهّج",
   "essence": "صبغة ممزوجة بصفار البيض، تُبنى طبقات متوهّجة.",
   "story": [
    "التمبرا من أقدم طرق الرسم المعروفة. مزج الرسامون الصبغة المطحونة بصفار البيض. فجفّت بسرعة، صلبة ومشرقة.",
    "ظلت لقرون الطلاء الأساسي على الألواح الخشبية في أوروبا وبيزنطة. رسم بها بوتيتشيلي أجمل أعماله. ثم حلّ الزيت مكانها تدريجياً بعد القرن الخامس عشر.",
    "في القرن العشرين أعادها رسامون مثل أندرو وايث. واليوم ما زالت دائرة صغيرة مخلصة تطحن الصبغة وتكسر البيض. وصبرهم يظهر في كل لمسة."
   ],
   "tech": [
    [
     "رابط صفار البيض",
     "صبغة ممزوجة بالصفار وقليل من الماء، تُحضّر كل يوم."
    ],
    [
     "التهشير",
     "خطوط متوازية دقيقة تبني الظل، فالتمبرا لا تُمزج رطبة."
    ],
    [
     "التهشير المتقاطع",
     "خطوط متقاطعة لظلال أعمق."
    ],
    [
     "الطبقات الرقيقة",
     "طبقات شفافة كثيرة تبني اللون ببطء وتمنحه التوهّج."
    ],
    [
     "أرضية الجيسو",
     "أرضية بيضاء ناعمة من الطباشير تعكس الضوء عبر الطلاء."
    ],
    [
     "التذهيب",
     "رقائق ذهب توضع على مواضع مجهّزة ثم تُصقل لتلمع."
    ]
   ],
   "signs": [
    "خطوط تهشير صغيرة تراها عن قرب، كخيوط ملوّنة منسوجة.",
    "لمعة ساتان ناعمة، لا بريق الزيت العميق.",
    "لوح خشبي صلب، فالتمبرا قد تتشقق على القماش المرن.",
    "تغيّر خفيف في اتجاه اللمسات يتبع أشكال الموضوع.",
    "حواف حادة ونظيفة، لأن الطلاء يجف لحظة وضعه تقريباً."
   ],
   "tools": [
    [
     "بيض طازج",
     "الصفار هو الرابط الذي يمسك الصبغة."
    ],
    [
     "أصباغ جافة",
     "لون نقي مسحوق يُطحن يدوياً بالماء."
    ],
    [
     "مدقّة ولوح زجاجي",
     "لطحن الصبغة حتى تصير عجينة ناعمة."
    ],
    [
     "لوح خشبي",
     "حامل صلب يحمي الطلاء من التشقق."
    ],
    [
     "جيسو تقليدي",
     "طباشير وغراء حيواني، يُصقل ناعماً كالعاج."
    ],
    [
     "فرش سمور صغيرة",
     "رؤوس دقيقة للمسات الصغيرة الكثيرة."
    ],
    [
     "رقائق الذهب",
     "للمواضع المذهّبة في الألواح التقليدية."
    ]
   ],
   "time": "عدة أسابيع",
   "bestFor": "للرسامين الصبورين الذين يحبون التفاصيل والتوهّج والعمل الهادئ المتأني.",
   "artists": [
    [
     "ساندرو بوتيتشيلي",
     "إيطالي، 1445–1510",
     "رسم ولادة فينوس بالتمبرا، مليئة بالضوء والرقّة."
    ],
    [
     "أندرو وايث",
     "أمريكي، 1917–2009",
     "أعاد إحياء تمبرا البيض بمشاهد ريفية هادئة شديدة التفاصيل."
    ],
    [
     "جاكوب لورنس",
     "أمريكي، 1917–2000",
     "روى قصة الهجرة الكبرى في ألواح تمبرا جريئة."
    ],
    [
     "بن شان",
     "أمريكي، 1898–1969",
     "استخدم التمبرا في لوحات حادة ورحيمة عن العمّال والعدالة."
    ]
   ],
   "guide": [
    "انظر عن قرب بعدسة مكبّرة. التمبرا الحقيقية تُظهر خطوط تهشير صغيرة لا تُحصى.",
    "اسأل عن مادة اللوح. الفنان الجاد سيصف لك أرضية الجيسو.",
    "تأمّل لمعة السطح. للتمبرا توهّج ساتان ناعم، لا لمعان بلاستيكي أبداً."
   ]
  }
 },
 {
  "id": "fresco",
  "cat": "traditional",
  "type": "fresco",
  "level": "advanced",
  "en": {
   "name": "Fresco",
   "feel": "Timeless",
   "essence": "Color painted into wet plaster, becoming part of the wall.",
   "story": [
    "Fresco means fresh in Italian. The painter works on wet lime plaster. As it dries, the color locks into the wall forever.",
    "Painters at Knossos and Pompeii used it thousands of years ago. The Umayyad desert palace of Qusayr Amra in Jordan holds early Islamic frescoes. In Renaissance Italy, Raphael and Michelangelo raised it to its peak.",
    "In the 1920s, Mexican muralists painted their nation’s story on public walls. Today restorers and muralists keep the craft alive. Each wall is a race against drying plaster."
   ],
   "tech": [
    [
     "Buon fresco",
     "True fresco, painted on wet plaster so color binds as it dries."
    ],
    [
     "Fresco secco",
     "Painting on dry plaster with a binder, for details added later."
    ],
    [
     "Giornata",
     "One day’s patch of fresh plaster, painted before it sets."
    ],
    [
     "Cartoon transfer",
     "A full-size drawing pricked and dusted with charcoal onto the wall."
    ],
    [
     "Sinopia",
     "A red-brown underdrawing on the rough plaster layer below."
    ],
    [
     "Layered plaster",
     "Rough arriccio first, then the smooth intonaco that takes the paint."
    ]
   ],
   "signs": [
    "Faint seams between patches of plaster, each one a single day’s work.",
    "Tiny dotted lines from a pounced cartoon along the main contours.",
    "Matte, chalky color that looks part of the surface, not lying on top.",
    "Small differences in tone between patches painted on different days.",
    "Real weight and depth, on a wall, a lime panel or a framed slab."
   ],
   "tools": [
    [
     "Slaked lime",
     "The base of the plaster that binds the color."
    ],
    [
     "Fine sand or marble dust",
     "Mixed with lime for each plaster layer."
    ],
    [
     "Trowels",
     "To spread and smooth the fresh plaster."
    ],
    [
     "Lime-safe pigments",
     "Earth and mineral colors that survive the strong lime."
    ],
    [
     "Soft brushes",
     "Large ones for washes, fine ones for detail."
    ],
    [
     "Pouncing bag",
     "Charcoal dust tapped through holes in the cartoon."
    ],
    [
     "Water spray",
     "Keeps the plaster damp while the painter works."
    ]
   ],
   "time": "Several weeks to many months",
   "bestFor": "Painters who love large spaces, planning and working at speed.",
   "artists": [
    [
     "Raphael",
     "Italian, 1483–1520",
     "His School of Athens fresco is a masterpiece of space and calm."
    ],
    [
     "Michelangelo",
     "Italian, 1475–1564",
     "He painted a vast fresco ceiling in Rome over four years."
    ],
    [
     "Diego Rivera",
     "Mexican, 1886–1957",
     "He painted workers and Mexican history in huge public frescoes."
    ],
    [
     "José Clemente Orozco",
     "Mexican, 1883–1949",
     "His dramatic frescoes carry deep feeling about human struggle."
    ]
   ],
   "guide": [
    "Ask how the work was made. True fresco needs wet lime plaster, not paint on board.",
    "Look for day seams and pounce dots. They are the fingerprints of real fresco.",
    "Ask about framing and weight. Portable frescoes sit on heavy lime panels."
   ]
  },
  "ar": {
   "name": "الفريسكو",
   "feel": "خالد",
   "essence": "لون يُرسم في الجص الرطب، فيصبح جزءاً من الجدار.",
   "story": [
    "كلمة فريسكو تعني الطازج بالإيطالية. يرسم الفنان على جص جيري رطب. وحين يجف يثبت اللون في الجدار إلى الأبد.",
    "استخدمه رسامو كنوسوس وبومبي قبل آلاف السنين. ويضم قصر عمرة الأموي في الأردن جداريات إسلامية مبكرة. وفي إيطاليا عصر النهضة بلغ به رافائيل ومايكل أنجلو القمة.",
    "في عشرينيات القرن العشرين رسم فنانو الجداريات المكسيكيون قصة بلادهم على الجدران العامة. واليوم يحفظ المرمّمون والجداريون هذه الحرفة حيّة. كل جدار سباق مع جفاف الجص."
   ],
   "tech": [
    [
     "الفريسكو الحقيقي",
     "رسم على جص رطب ليرتبط اللون به وهو يجف."
    ],
    [
     "الفريسكو الجاف",
     "رسم على جص جاف بمادة رابطة، لتفاصيل تُضاف لاحقاً."
    ],
    [
     "الجورناتا",
     "رقعة جص طازج ليوم واحد، تُرسم قبل أن تتصلب."
    ],
    [
     "نقل الكرتون",
     "رسم بالحجم الكامل يُثقب ويُذرّ عليه الفحم فوق الجدار."
    ],
    [
     "السينوبيا",
     "رسم تمهيدي بلون أحمر بني على طبقة الجص الخشنة."
    ],
    [
     "طبقات الجص",
     "طبقة خشنة أولاً، ثم طبقة ناعمة تستقبل اللون."
    ]
   ],
   "signs": [
    "فواصل خفيفة بين رقع الجص، كل رقعة عمل يوم واحد.",
    "خطوط منقّطة صغيرة من الكرتون المذرور على الحدود الرئيسية.",
    "لون مطفي طباشيري يبدو جزءاً من السطح لا فوقه.",
    "فروق صغيرة في الدرجة بين رقع رُسمت في أيام مختلفة.",
    "ثقل وعمق حقيقيان، على جدار أو لوح جيري أو بلاطة مؤطّرة."
   ],
   "tools": [
    [
     "الجير المطفأ",
     "أساس الجص الذي يربط اللون."
    ],
    [
     "رمل ناعم أو مسحوق رخام",
     "يُخلط بالجير لكل طبقة جص."
    ],
    [
     "مالج",
     "لفرد الجص الطازج وتنعيمه."
    ],
    [
     "أصباغ تتحمّل الجير",
     "ألوان ترابية ومعدنية تصمد أمام الجير القوي."
    ],
    [
     "فرش ناعمة",
     "كبيرة للمساحات، ودقيقة للتفاصيل."
    ],
    [
     "كيس الذرّ",
     "غبار فحم يُنقر عبر ثقوب الكرتون."
    ],
    [
     "رذاذ الماء",
     "يُبقي الجص رطباً أثناء العمل."
    ]
   ],
   "time": "من عدة أسابيع إلى شهور طويلة",
   "bestFor": "للرسامين الذين يحبون المساحات الكبيرة والتخطيط والعمل السريع.",
   "artists": [
    [
     "رافائيل",
     "إيطالي، 1483–1520",
     "جداريته مدرسة أثينا تحفة في الفضاء والسكينة."
    ],
    [
     "مايكل أنجلو",
     "إيطالي، 1475–1564",
     "رسم سقفاً جدارياً هائلاً في روما خلال أربع سنوات."
    ],
    [
     "دييغو ريفيرا",
     "مكسيكي، 1886–1957",
     "رسم العمّال وتاريخ المكسيك في جداريات عامة ضخمة."
    ],
    [
     "خوسيه كليمنتي أوروزكو",
     "مكسيكي، 1883–1949",
     "جدارياته الدرامية تحمل شعوراً عميقاً بكفاح الإنسان."
    ]
   ],
   "guide": [
    "اسأل كيف صُنع العمل. الفريسكو الحقيقي يحتاج جصاً جيرياً رطباً، لا طلاء على لوح.",
    "ابحث عن فواصل الأيام ونقاط الذرّ. إنها بصمات الفريسكو الحقيقي.",
    "اسأل عن التأطير والوزن. الفريسكو المحمول يكون على ألواح جيرية ثقيلة."
   ]
  }
 },
 {
  "id": "pencil",
  "cat": "traditional",
  "type": "pencil",
  "level": "beginner",
  "en": {
   "name": "Pencil Drawing",
   "feel": "Honest",
   "essence": "Graphite on paper, the most direct line from hand to page.",
   "story": [
    "A huge deposit of pure graphite was found in Borrowdale, England, in the 1500s. People wrapped sticks of it in string or wood. The pencil was born.",
    "In 1795, Nicolas-Jacques Conté mixed graphite with clay and baked it. Now pencils could be hard or soft. Ingres used them for portraits of astonishing precision.",
    "Today almost every artist starts with a pencil. Some make it their whole life’s work. Their drawings reach a depth that surprises everyone who sees them."
   ],
   "tech": [
    [
     "Hatching",
     "Parallel lines that build tone and direction."
    ],
    [
     "Cross-hatching",
     "Layers of crossing lines for deeper shadow."
    ],
    [
     "Blending",
     "Smoothing graphite with a stump or tissue for soft tones."
    ],
    [
     "Lifting",
     "Using a kneaded eraser to pull out highlights."
    ],
    [
     "Graded pressure",
     "Changing hand pressure to move from light to dark."
    ],
    [
     "Contour drawing",
     "One flowing line that follows the edge of a form."
    ],
    [
     "Impressed lines",
     "Pressing fine lines into the paper first, so they stay white."
    ]
   ],
   "signs": [
    "A soft metallic sheen on dark areas when you tilt the paper.",
    "Visible stroke direction, especially in shadows and hair.",
    "Faint erased lines and small changes the artist made along the way.",
    "Slight indents in the paper where the pencil pressed hard.",
    "Light smudges near the edges where the hand rested."
   ],
   "tools": [
    [
     "Graphite pencils",
     "Grades from hard 9H to soft 9B for every tone."
    ],
    [
     "Kneaded eraser",
     "Lifts graphite gently without harming the paper."
    ],
    [
     "Blending stump",
     "Rolled paper for smoothing tones."
    ],
    [
     "Drawing paper",
     "Smooth for detail, textured for richer grain."
    ],
    [
     "Craft knife or sharpener",
     "Shapes a long, fine point."
    ],
    [
     "Fixative spray",
     "Protects the finished drawing from smudging."
    ]
   ],
   "time": "A few hours to several weeks",
   "bestFor": "Everyone, from first-time beginners to masters of fine detail.",
   "artists": [
    [
     "Jean-Auguste-Dominique Ingres",
     "French, 1780–1867",
     "His pencil portraits are famous for clean, graceful, exact lines."
    ],
    [
     "Adolph Menzel",
     "German, 1815–1905",
     "He filled sketchbooks with lively pencil studies of everyday life."
    ],
    [
     "Egon Schiele",
     "Austrian, 1890–1918",
     "His bold, nervous pencil lines gave figures raw emotion."
    ],
    [
     "David Hockney",
     "British, born 1937",
     "His pencil and colored pencil portraits of friends feel warm and close."
    ]
   ],
   "guide": [
    "Tilt the drawing under a light. Real graphite shines softly. A print stays flat.",
    "Look at the back of the paper. Strong lines may leave small ridges.",
    "Ask for the artist’s pencil grades. Real artists know exactly which they used."
   ]
  },
  "ar": {
   "name": "الرسم بالرصاص",
   "feel": "صادق",
   "essence": "جرافيت على ورق، أقصر طريق من اليد إلى الصفحة.",
   "story": [
    "وُجد مخزون ضخم من الجرافيت النقي في بوروديل بإنجلترا في القرن السادس عشر. لفّ الناس أعواده بالخيط أو الخشب. وهكذا وُلد قلم الرصاص.",
    "في عام 1795 مزج نيكولا جاك كونتيه الجرافيت بالطين وخبزه. فصار القلم صلباً أو ليّناً. واستخدمه آنغر في بورتريهات بدقة مذهلة.",
    "اليوم يبدأ كل فنان تقريباً بقلم رصاص. وبعضهم يجعله عمل حياته كلها. ورسومهم تبلغ عمقاً يدهش كل من يراها."
   ],
   "tech": [
    [
     "التهشير",
     "خطوط متوازية تبني الظل والاتجاه."
    ],
    [
     "التهشير المتقاطع",
     "طبقات من خطوط متقاطعة لظل أعمق."
    ],
    [
     "التدرّج بالمزج",
     "تنعيم الجرافيت بأداة مزج أو منديل لدرجات ناعمة."
    ],
    [
     "الرفع",
     "استخدام ممحاة عجينية لاستخراج الإضاءات."
    ],
    [
     "تدرّج الضغط",
     "تغيير ضغط اليد للانتقال من الفاتح إلى الداكن."
    ],
    [
     "رسم الحدود",
     "خط واحد منساب يتبع حافة الشكل."
    ],
    [
     "الخطوط المضغوطة",
     "ضغط خطوط دقيقة في الورق أولاً لتبقى بيضاء."
    ]
   ],
   "signs": [
    "لمعة معدنية ناعمة على المناطق الداكنة حين تميل الورقة.",
    "اتجاه لمسات واضح، خاصة في الظلال والشعر.",
    "آثار مسح خفيفة وتعديلات صغيرة أجراها الفنان أثناء العمل.",
    "انخفاضات طفيفة في الورق حيث ضغط القلم بقوة.",
    "تلطيخ خفيف قرب الحواف حيث استندت اليد."
   ],
   "tools": [
    [
     "أقلام جرافيت",
     "درجات من الصلب 9H إلى الليّن 9B لكل ظل."
    ],
    [
     "ممحاة عجينية",
     "ترفع الجرافيت بلطف دون أن تؤذي الورق."
    ],
    [
     "أداة المزج",
     "ورق ملفوف لتنعيم الدرجات."
    ],
    [
     "ورق رسم",
     "أملس للتفاصيل، ومحبّب لملمس أغنى."
    ],
    [
     "مشرط أو مبراة",
     "لصنع رأس طويل ودقيق."
    ],
    [
     "بخاخ مثبّت",
     "يحمي الرسم المكتمل من التلطيخ."
    ]
   ],
   "time": "من بضع ساعات إلى عدة أسابيع",
   "bestFor": "للجميع، من المبتدئين لأول مرة إلى أساتذة التفاصيل الدقيقة.",
   "artists": [
    [
     "جان أوغست دومينيك آنغر",
     "فرنسي، 1780–1867",
     "بورتريهاته بالرصاص مشهورة بخطوطها النظيفة الرشيقة الدقيقة."
    ],
    [
     "أدولف منتسل",
     "ألماني، 1815–1905",
     "ملأ دفاتره بدراسات رصاص حيّة للحياة اليومية."
    ],
    [
     "إيغون شيله",
     "نمساوي، 1890–1918",
     "خطوطه الجريئة القلقة منحت الأجساد عاطفة صادقة."
    ],
    [
     "ديفيد هوكني",
     "بريطاني، مواليد 1937",
     "بورتريهاته لأصدقائه بالرصاص والألوان الخشبية دافئة وقريبة."
    ]
   ],
   "guide": [
    "أمِل الرسم تحت الضوء. الجرافيت الحقيقي يلمع بنعومة، والطباعة تبقى مسطّحة.",
    "انظر إلى ظهر الورقة. الخطوط القوية قد تترك بروزاً صغيراً.",
    "اسأل الفنان عن درجات أقلامه. الفنان الحقيقي يعرف تماماً ما استخدم."
   ]
  }
 },
 {
  "id": "encaustic",
  "cat": "traditional",
  "type": "encaustic",
  "level": "intermediate",
  "en": {
   "name": "Encaustic (Wax)",
   "feel": "Warm",
   "essence": "Pigment melted into hot beeswax, fused with heat.",
   "story": [
    "Encaustic comes from a Greek word meaning to burn in. Painters melted beeswax with pigment and worked it while hot. Ancient Greeks used it on ships and statues.",
    "Its most moving works come from Roman Egypt. The Fayum portraits show real faces painted on wood. After nearly two thousand years, their eyes still feel alive.",
    "The method faded for centuries. Modern artists revived it, with Jasper Johns among the best known. Today electric palettes and heat guns make hot wax easier to master."
   ],
   "tech": [
    [
     "Fusing",
     "Reheating each layer so it bonds to the one below."
    ],
    [
     "Layering",
     "Building many thin wax layers for depth and glow."
    ],
    [
     "Scraping",
     "Cutting back wax to reveal colors underneath."
    ],
    [
     "Incising",
     "Drawing lines into the wax, then filling them with color."
    ],
    [
     "Embedding",
     "Pressing paper, fabric or objects into the warm wax."
    ],
    [
     "Texturing",
     "Brushing or pouring hot wax to raise a sculpted surface."
    ]
   ],
   "signs": [
    "A deep, milky glow, as if light lives inside the surface.",
    "Raised edges, drips and brush ridges frozen in the wax.",
    "A faint honey scent of beeswax, especially on warm days.",
    "Visible depth where lower colors show through upper layers.",
    "A rigid wooden panel, since wax cracks on flexible canvas."
   ],
   "tools": [
    [
     "Beeswax",
     "The main body of the paint."
    ],
    [
     "Damar resin",
     "Hardens the wax and raises its melting point."
    ],
    [
     "Heated palette",
     "Keeps the paint liquid while working."
    ],
    [
     "Natural bristle brushes",
     "Survive the heat of the molten wax."
    ],
    [
     "Heat gun or torch",
     "Fuses each layer to the last."
    ],
    [
     "Wooden panel",
     "A rigid, absorbent support for the wax."
    ],
    [
     "Scrapers and styluses",
     "For carving, smoothing and drawing lines."
    ]
   ],
   "time": "A few days to several weeks",
   "bestFor": "Artists who love texture, warmth and working with their hands.",
   "artists": [
    [
     "Jasper Johns",
     "American, born 1930",
     "His famous Flag paintings used encaustic over newspaper collage."
    ],
    [
     "Diego Rivera",
     "Mexican, 1886–1957",
     "His first major mural, Creation, was painted in encaustic in 1922."
    ],
    [
     "Lynda Benglis",
     "American, born 1941",
     "Her early poured wax paintings pushed encaustic into sculpture."
    ],
    [
     "Joanne Mattera",
     "American, born 1948",
     "She wrote a key modern guide to encaustic painting."
    ]
   ],
   "guide": [
    "Ask how the artist fused each layer. Real encaustic always uses heat.",
    "Look at the edges of the panel. Real wax builds up and drips there.",
    "Keep it away from direct sun and heat. Ask the artist about care."
   ]
  },
  "ar": {
   "name": "الإنكوستيك (الشمع)",
   "feel": "دافئ",
   "essence": "صبغة مذابة في شمع عسل ساخن، تُدمج بالحرارة.",
   "story": [
    "كلمة إنكوستيك يونانية الأصل وتعني الحرق في. أذاب الرسامون شمع العسل مع الصبغة وعملوا به ساخناً. واستخدمه الإغريق على السفن والتماثيل.",
    "أجمل أعماله وأكثرها تأثيراً جاءت من مصر الرومانية. صور الفيوم تُظهر وجوهاً حقيقية مرسومة على الخشب. وبعد قرابة ألفي عام ما زالت عيونها حيّة.",
    "خفت هذا الأسلوب قروناً طويلة. ثم أحياه فنانون معاصرون، وجاسبر جونز من أشهرهم. واليوم تجعل الألواح الكهربائية ومسدسات الحرارة إتقانه أسهل."
   ],
   "tech": [
    [
     "الدمج",
     "إعادة تسخين كل طبقة لتلتحم بما تحتها."
    ],
    [
     "الطبقات",
     "بناء طبقات شمع رقيقة كثيرة للعمق والتوهّج."
    ],
    [
     "الكشط",
     "قصّ الشمع لإظهار الألوان التي تحته."
    ],
    [
     "الحفر",
     "رسم خطوط في الشمع ثم ملؤها باللون."
    ],
    [
     "الدمج بالمواد",
     "ضغط ورق أو قماش أو أشياء في الشمع الدافئ."
    ],
    [
     "الملمس",
     "فرد الشمع الساخن أو صبّه لصنع سطح منحوت بارز."
    ]
   ],
   "signs": [
    "توهّج عميق حليبي، كأن الضوء يسكن داخل السطح.",
    "حواف بارزة وقطرات وأثلام فرشاة متجمّدة في الشمع.",
    "رائحة عسل خفيفة من شمع النحل، خاصة في الأيام الدافئة.",
    "عمق واضح حيث تظهر الألوان السفلية عبر الطبقات العليا.",
    "لوح خشبي صلب، فالشمع يتشقق على القماش المرن."
   ],
   "tools": [
    [
     "شمع العسل",
     "جسم الطلاء الأساسي."
    ],
    [
     "راتنج الدامار",
     "يقوّي الشمع ويرفع درجة انصهاره."
    ],
    [
     "لوح ألوان مسخّن",
     "يُبقي الطلاء سائلاً أثناء العمل."
    ],
    [
     "فرش شعر طبيعي",
     "تتحمّل حرارة الشمع المذاب."
    ],
    [
     "مسدس حرارة أو شعلة",
     "يدمج كل طبقة بما قبلها."
    ],
    [
     "لوح خشبي",
     "حامل صلب ماصّ للشمع."
    ],
    [
     "كاشطات وأقلام حفر",
     "للنحت والتنعيم ورسم الخطوط."
    ]
   ],
   "time": "من بضعة أيام إلى عدة أسابيع",
   "bestFor": "للفنانين الذين يحبون الملمس والدفء والعمل بأيديهم.",
   "artists": [
    [
     "جاسبر جونز",
     "أمريكي، مواليد 1930",
     "لوحاته الشهيرة للعلم رُسمت بالإنكوستيك فوق كولاج من الصحف."
    ],
    [
     "دييغو ريفيرا",
     "مكسيكي، 1886–1957",
     "رسم جداريته الكبرى الأولى الخلق بالإنكوستيك عام 1922."
    ],
    [
     "ليندا بنغليس",
     "أمريكية، مواليد 1941",
     "لوحاتها المبكرة بالشمع المصبوب دفعت الإنكوستيك نحو النحت."
    ],
    [
     "جوان ماتيرا",
     "أمريكية، مواليد 1948",
     "كتبت دليلاً حديثاً مهماً عن الرسم بالإنكوستيك."
    ]
   ],
   "guide": [
    "اسأل الفنان كيف دمج كل طبقة. الإنكوستيك الحقيقي يستخدم الحرارة دائماً.",
    "انظر إلى حواف اللوح. الشمع الحقيقي يتراكم ويقطر هناك.",
    "أبعده عن الشمس المباشرة والحرارة. واسأل الفنان عن طريقة العناية."
   ]
  }
 },
 {
  "id": "chinese_ink",
  "cat": "traditional",
  "type": "chinese_ink",
  "level": "intermediate",
  "en": {
   "name": "Chinese Ink",
   "feel": "Serene",
   "essence": "Black ink and water, painting mountains, mist and spirit.",
   "story": [
    "Chinese ink painting grew from calligraphy more than a thousand years ago. The same brush wrote poems and painted bamboo. Ink and water were enough.",
    "Song dynasty masters painted shan shui, meaning mountains and water. Fan Kuan’s towering peaks still amaze viewers today. Later, the art traveled to Japan and Korea.",
    "Modern masters like Qi Baishi and Zhang Daqian gave it fresh life. Today painters across Asia and the world still grind their ink stick. Each stroke is final."
   ],
   "tech": [
    [
     "Ink tones",
     "From deep black to pale grey, all by adding water."
    ],
    [
     "Boneless strokes",
     "Painting forms with washes, without outline."
    ],
    [
     "Texture strokes (cun)",
     "Special brush marks that show rock and mountain surfaces."
    ],
    [
     "Splashed ink",
     "Bold, loose washes that suggest mist and distance."
    ],
    [
     "Dry brush",
     "A nearly dry brush for rough, broken textures."
    ],
    [
     "Empty space",
     "Leaving paper blank for sky, water and breath."
    ]
   ],
   "signs": [
    "Soft bleeding edges where ink spread into absorbent paper.",
    "Strokes that thin and thicken in one confident movement.",
    "No going back over strokes, since rice paper shows every correction.",
    "Red seal stamps pressed by hand, slightly uneven in ink.",
    "Visible paper fibers and a faint texture under the ink."
   ],
   "tools": [
    [
     "Ink stick",
     "Pine soot and glue, ground fresh for each painting."
    ],
    [
     "Inkstone",
     "A stone where the ink stick is ground with water."
    ],
    [
     "Xuan paper",
     "Absorbent rice paper that drinks the ink."
    ],
    [
     "Brushes",
     "Soft and firm hair brushes with fine tips."
    ],
    [
     "Felt mat",
     "Soaks up excess ink under the paper."
    ],
    [
     "Paperweights",
     "Hold the thin paper flat while painting."
    ],
    [
     "Seal and red paste",
     "The artist’s carved name stamp."
    ]
   ],
   "time": "A few minutes to several days",
   "bestFor": "Calm, focused artists who love nature and expressive brushwork.",
   "artists": [
    [
     "Bada Shanren",
     "Chinese, 1626–1705",
     "His bold, lonely birds and fish carry deep feeling in few strokes."
    ],
    [
     "Shitao",
     "Chinese, 1642–1707",
     "He urged painters to find their own way, not copy the old masters."
    ],
    [
     "Qi Baishi",
     "Chinese, 1864–1957",
     "He painted shrimp, flowers and insects with joyful, lively ink."
    ],
    [
     "Xu Beihong",
     "Chinese, 1895–1953",
     "His galloping horses joined ink tradition with careful drawing."
    ],
    [
     "Zhang Daqian",
     "Chinese, 1899–1983",
     "He developed splashed ink and color into a bold modern style."
    ],
    [
     "Sesshū Tōyō",
     "Japanese, 1420–1506",
     "He carried ink landscape to Japan and made it his own."
    ]
   ],
   "guide": [
    "Look at the stroke edges. Real ink bleeds softly into the paper fibers.",
    "Check the seal. Hand-pressed seals show uneven red paste.",
    "Ask about mounting. Real works are often mounted as scrolls or on silk."
   ]
  },
  "ar": {
   "name": "الحبر الصيني",
   "feel": "هادئ",
   "essence": "حبر أسود وماء يرسمان الجبال والضباب والروح.",
   "story": [
    "نشأ الرسم بالحبر الصيني من الخط قبل أكثر من ألف عام. الفرشاة نفسها كتبت الشعر ورسمت الخيزران. وكان الحبر والماء كافيين.",
    "رسم أساتذة سلالة سونغ فن شان شوي، أي الجبال والماء. وما زالت قمم فان كوان الشاهقة تدهش الناس اليوم. ثم انتقل الفن إلى اليابان وكوريا.",
    "منحه أساتذة معاصرون مثل تشي بايشي وتشانغ داتشيان حياة جديدة. واليوم ما زال الرسامون في آسيا والعالم يطحنون أصابع الحبر. وكل لمسة نهائية."
   ],
   "tech": [
    [
     "درجات الحبر",
     "من الأسود العميق إلى الرمادي الشاحب، بإضافة الماء فقط."
    ],
    [
     "الضربات بلا عظم",
     "رسم الأشكال بالغسلات دون خطوط محيطة."
    ],
    [
     "ضربات الملمس",
     "علامات فرشاة خاصة تُظهر سطوح الصخور والجبال."
    ],
    [
     "الحبر المرشوش",
     "غسلات جريئة حرّة توحي بالضباب والبعد."
    ],
    [
     "الفرشاة الجافة",
     "فرشاة شبه جافة لملمس خشن متقطع."
    ],
    [
     "الفراغ",
     "ترك الورق أبيض للسماء والماء والتنفّس."
    ]
   ],
   "signs": [
    "حواف ناعمة سائلة حيث انتشر الحبر في الورق الماص.",
    "ضربات ترقّ وتغلظ في حركة واحدة واثقة.",
    "لا عودة على الضربات، فورق الأرز يكشف كل تصحيح.",
    "أختام حمراء مضغوطة باليد، غير متساوية الحبر قليلاً.",
    "ألياف ورق ظاهرة وملمس خفيف تحت الحبر."
   ],
   "tools": [
    [
     "أصبع الحبر",
     "سخام صنوبر وغراء، يُطحن طازجاً لكل لوحة."
    ],
    [
     "حجر الحبر",
     "حجر يُطحن عليه أصبع الحبر مع الماء."
    ],
    [
     "ورق شوان",
     "ورق أرز ماص يشرب الحبر."
    ],
    [
     "الفرش",
     "فرش شعر ليّنة وصلبة برؤوس دقيقة."
    ],
    [
     "حصيرة لباد",
     "تمتص الحبر الزائد تحت الورق."
    ],
    [
     "أثقال الورق",
     "تُبقي الورق الرقيق مسطّحاً أثناء الرسم."
    ],
    [
     "الختم والعجينة الحمراء",
     "اسم الفنان منحوتاً في ختم."
    ]
   ],
   "time": "من بضع دقائق إلى عدة أيام",
   "bestFor": "للفنانين الهادئين المركّزين الذين يحبون الطبيعة والفرشاة المعبّرة.",
   "artists": [
    [
     "بادا شانرن",
     "صيني، 1626–1705",
     "طيوره وأسماكه الجريئة الوحيدة تحمل شعوراً عميقاً في ضربات قليلة."
    ],
    [
     "شيتاو",
     "صيني، 1642–1707",
     "دعا الرسامين إلى إيجاد طريقهم الخاص، لا تقليد القدماء."
    ],
    [
     "تشي بايشي",
     "صيني، 1864–1957",
     "رسم الروبيان والزهور والحشرات بحبر مرح نابض بالحياة."
    ],
    [
     "شو بيهونغ",
     "صيني، 1895–1953",
     "خيوله الراكضة جمعت تقاليد الحبر والرسم الدقيق."
    ],
    [
     "تشانغ داتشيان",
     "صيني، 1899–1983",
     "طوّر الحبر واللون المرشوشين إلى أسلوب حديث جريء."
    ],
    [
     "سيسشو تويو",
     "ياباني، 1420–1506",
     "حمل رسم المناظر بالحبر إلى اليابان وجعله خاصاً به."
    ]
   ],
   "guide": [
    "انظر إلى حواف الضربات. الحبر الحقيقي يسيل بنعومة في ألياف الورق.",
    "افحص الختم. الأختام المضغوطة باليد تُظهر عجينة حمراء غير متساوية.",
    "اسأل عن التركيب. الأعمال الحقيقية تُركّب غالباً كلفائف أو على حرير."
   ]
  }
 },
 {
  "id": "mosaic",
  "cat": "traditional",
  "type": "mosaic",
  "level": "intermediate",
  "en": {
   "name": "Mosaic",
   "feel": "Enduring",
   "essence": "Thousands of small pieces set by hand into one image.",
   "story": [
    "Mosaic began in ancient Mesopotamia with colored clay cones pressed into walls. The Greeks later set river pebbles into floors. Each piece was placed by hand.",
    "The Romans cut stone and glass into small cubes called tesserae. Their floors showed hunts, sea life and daily scenes. Byzantine artists added gold glass that glows in candlelight.",
    "Early Islamic builders used glass mosaic beautifully, as in Damascus’s Umayyad Mosque. Gaudí broke tiles into bright, curving forms. Today mosaicists work in studios, homes and public spaces worldwide."
   ],
   "tech": [
    [
     "Direct method",
     "Each piece set face up straight into the adhesive."
    ],
    [
     "Indirect method",
     "Pieces glued face down on paper, then flipped into place."
    ],
    [
     "Andamento",
     "The flow of the rows, guiding the eye like brushstrokes."
    ],
    [
     "Opus tessellatum",
     "Even cubes in a regular grid, for backgrounds."
    ],
    [
     "Opus vermiculatum",
     "Tiny pieces following the outline of a form."
    ],
    [
     "Trencadís",
     "Broken tile and pottery pieces laid in free, curving patterns."
    ],
    [
     "Grouting",
     "Filling the gaps between pieces to lock them together."
    ]
   ],
   "signs": [
    "Pieces of slightly different sizes and shapes, each cut by hand.",
    "Gaps that change width gently, following the lines of the design.",
    "Rows that curve around forms, not a rigid printed grid.",
    "Real weight, coolness and a surface that catches light unevenly.",
    "Small chips and clean cut edges from nippers and hammer."
   ],
   "tools": [
    [
     "Tesserae",
     "Small pieces of stone, glass, smalti or ceramic."
    ],
    [
     "Wheeled nippers",
     "Cut glass and tile into precise shapes."
    ],
    [
     "Hammer and hardie",
     "The traditional way to split stone and smalti."
    ],
    [
     "Adhesive or mortar",
     "Holds each piece firmly in place."
    ],
    [
     "Grout",
     "Fills the gaps and strengthens the surface."
    ],
    [
     "Backing board",
     "A rigid base for portable work."
    ],
    [
     "Tweezers",
     "Place the smallest pieces with care."
    ]
   ],
   "time": "Several days to many months",
   "bestFor": "Patient makers who love color, pattern and working piece by piece.",
   "artists": [
    [
     "Antoni Gaudí",
     "Spanish, 1852–1926",
     "He covered Park Güell in Barcelona with bright broken-tile mosaic."
    ],
    [
     "Josep Maria Jujol",
     "Spanish, 1879–1949",
     "He crafted the famous winding mosaic bench in Park Güell."
    ],
    [
     "Boris Anrep",
     "Russian-British, 1883–1969",
     "He laid the much-loved mosaic floors of London’s National Gallery."
    ],
    [
     "Juan O’Gorman",
     "Mexican, 1905–1982",
     "He wrapped a university library in Mexico City with stone mosaic."
    ],
    [
     "Isaiah Zagar",
     "American, born 1939",
     "He turned Philadelphia walls into the joyful Magic Gardens mosaic."
    ]
   ],
   "guide": [
    "Look at the gaps. Hand-set mosaic has gentle, living variation in every line.",
    "Ask what the pieces are. Real smalti, stone or glass feels cool and heavy.",
    "Ask about the backing and grout. Good work is built to last generations."
   ]
  },
  "ar": {
   "name": "الفسيفساء",
   "feel": "راسخ",
   "essence": "آلاف القطع الصغيرة تُرصّ يدوياً في صورة واحدة.",
   "story": [
    "بدأت الفسيفساء في بلاد الرافدين القديمة بأقماع طين ملوّنة تُغرس في الجدران. ثم رصّ الإغريق حصى الأنهار في الأرضيات. وكل قطعة وُضعت باليد.",
    "قطع الرومان الحجر والزجاج إلى مكعبات صغيرة تُسمّى التيسيرا. وصوّرت أرضياتهم الصيد والحياة البحرية ومشاهد اليوم. وأضاف فنانو بيزنطة زجاجاً ذهبياً يتوهّج في ضوء الشموع.",
    "واستخدم البناؤون المسلمون الأوائل فسيفساء الزجاج بجمال بديع، كما في الجامع الأموي بدمشق. وكسّر غاودي البلاط إلى أشكال مشرقة منحنية. واليوم يعمل فنانو الفسيفساء في المراسم والبيوت والأماكن العامة حول العالم."
   ],
   "tech": [
    [
     "الطريقة المباشرة",
     "تُوضع كل قطعة ووجهها للأعلى مباشرة في المادة اللاصقة."
    ],
    [
     "الطريقة غير المباشرة",
     "تُلصق القطع مقلوبة على ورق، ثم تُقلب إلى مكانها."
    ],
    [
     "الأندامنتو",
     "انسياب الصفوف، يقود العين كضربات الفرشاة."
    ],
    [
     "الرصف المنتظم",
     "مكعبات متساوية في شبكة منتظمة، للخلفيات."
    ],
    [
     "الرصف الدقيق",
     "قطع صغيرة جداً تتبع حدود الشكل."
    ],
    [
     "الترينكاديس",
     "قطع بلاط وفخار مكسورة تُرصّ في أنماط حرّة منحنية."
    ],
    [
     "الترويب",
     "ملء الفراغات بين القطع لتثبيتها معاً."
    ]
   ],
   "signs": [
    "قطع مختلفة قليلاً في الحجم والشكل، كل منها مقطوعة يدوياً.",
    "فراغات يتغيّر عرضها بلطف، تتبع خطوط التصميم.",
    "صفوف تنحني حول الأشكال، لا شبكة مطبوعة جامدة.",
    "ثقل وبرودة حقيقيان، وسطح يلتقط الضوء بشكل متفاوت.",
    "شظايا صغيرة وحواف قطع نظيفة من القطّاعة والمطرقة."
   ],
   "tools": [
    [
     "التيسيرا",
     "قطع صغيرة من الحجر أو الزجاج أو السمالتي أو الخزف."
    ],
    [
     "قطّاعة بعجلات",
     "تقطع الزجاج والبلاط بأشكال دقيقة."
    ],
    [
     "المطرقة والسندان",
     "الطريقة التقليدية لشقّ الحجر والسمالتي."
    ],
    [
     "لاصق أو ملاط",
     "يثبّت كل قطعة بقوة في مكانها."
    ],
    [
     "الروبة",
     "تملأ الفراغات وتقوّي السطح."
    ],
    [
     "لوح القاعدة",
     "أساس صلب للأعمال المحمولة."
    ],
    [
     "ملقط",
     "لوضع أصغر القطع بعناية."
    ]
   ],
   "time": "من عدة أيام إلى شهور طويلة",
   "bestFor": "للصانعين الصبورين الذين يحبون اللون والنمط والعمل قطعة قطعة.",
   "artists": [
    [
     "أنطوني غاودي",
     "إسباني، 1852–1926",
     "كسا حديقة غويل في برشلونة بفسيفساء البلاط المكسور المشرقة."
    ],
    [
     "جوزيب ماريا جوجول",
     "إسباني، 1879–1949",
     "صنع مقعد الفسيفساء المتعرّج الشهير في حديقة غويل."
    ],
    [
     "بوريس أنريب",
     "روسي بريطاني، 1883–1969",
     "رصّ أرضيات الفسيفساء المحبوبة في المتحف الوطني بلندن."
    ],
    [
     "خوان أوغورمان",
     "مكسيكي، 1905–1982",
     "غلّف مكتبة جامعية في مكسيكو سيتي بفسيفساء من الحجر."
    ],
    [
     "إشعياء زاغار",
     "أمريكي، مواليد 1939",
     "حوّل جدران فيلادلفيا إلى فسيفساء الحدائق السحرية المبهجة."
    ]
   ],
   "guide": [
    "انظر إلى الفراغات. الفسيفساء اليدوية فيها تفاوت حيّ لطيف في كل خط.",
    "اسأل عن مادة القطع. السمالتي والحجر والزجاج الحقيقي بارد وثقيل.",
    "اسأل عن القاعدة والروبة. العمل الجيد يُبنى ليبقى أجيالاً."
   ]
  }
 },
 {
  "id": "stained_glass",
  "cat": "traditional",
  "type": "stained_glass",
  "level": "advanced",
  "en": {
   "name": "Stained Glass",
   "feel": "Radiant",
   "essence": "Colored glass and light, joined by hand into glowing pictures.",
   "story": [
    "Colored glass was made in the ancient world, from Egypt to Rome. Craftsmen learned to set small pieces into windows. Light turned them into living color.",
    "In medieval Europe, the craft grew in great cathedrals with tall glowing windows. In Islamic lands, makers set colored glass into carved plaster. These qamariya and shamsiya windows lit homes and mosques in Cairo and Yemen.",
    "In Iran, orosi windows fill rooms with color, as in Shiraz’s Nasir al-Mulk Mosque. Tiffany later made glass that looked painted. Today glass artists build windows, panels and lamps worldwide."
   ],
   "tech": [
    [
     "Glass cutting",
     "Scoring the glass with a wheel, then snapping it cleanly."
    ],
    [
     "Grozing",
     "Nibbling edges with pliers for a perfect fit."
    ],
    [
     "Leading",
     "Fitting pieces into soft H-shaped lead strips, called came."
    ],
    [
     "Copper foil",
     "Wrapping edges in copper tape, then soldering, as Tiffany did."
    ],
    [
     "Glass painting",
     "Painting details with vitreous paint, then firing it in a kiln."
    ],
    [
     "Silver stain",
     "A yellow stain fired into the glass for warm golden tones."
    ],
    [
     "Plaster setting",
     "Setting glass into carved gypsum, as in qamariya windows."
    ]
   ],
   "signs": [
    "Small bubbles, streaks and color shifts inside handmade glass.",
    "Solder joints that are smooth but not perfectly uniform.",
    "Lead or plaster lines that follow the design, not a printed film.",
    "Colors that change strongly as daylight moves through the day.",
    "Real weight and slight unevenness when you run a finger across it."
   ],
   "tools": [
    [
     "Colored glass",
     "Mouth-blown or rolled sheets, the heart of the work."
    ],
    [
     "Glass cutter",
     "A small wheel that scores the glass."
    ],
    [
     "Grozing and running pliers",
     "Shape and break the glass along the score."
    ],
    [
     "Lead came or copper foil",
     "Joins and holds each piece."
    ],
    [
     "Soldering iron",
     "Melts solder to fix every joint."
    ],
    [
     "Kiln",
     "Fires painted details permanently into the glass."
    ],
    [
     "Full-size cartoon",
     "The drawing that guides every cut."
    ],
    [
     "Gypsum plaster",
     "Carved frame for qamariya-style windows."
    ]
   ],
   "time": "Several days to several months",
   "bestFor": "Careful makers who love light, color and precise handwork.",
   "artists": [
    [
     "Louis Comfort Tiffany",
     "American, 1848–1933",
     "He created rich opalescent glass and famous lamps and windows."
    ],
    [
     "John La Farge",
     "American, 1835–1910",
     "He pioneered opalescent glass, giving windows painterly depth."
    ],
    [
     "Charles Rennie Mackintosh",
     "British, 1868–1928",
     "His elegant leaded glass shaped the Glasgow Style."
    ],
    [
     "Frank Lloyd Wright",
     "American, 1867–1959",
     "He designed geometric art glass windows for his houses."
    ],
    [
     "Brian Clarke",
     "British, born 1953",
     "He makes bold, modern glass for buildings around the world."
    ]
   ],
   "guide": [
    "Hold it to the light. Handmade glass shows bubbles, streaks and real depth.",
    "Check the joints. Real lead or foil lines are slightly raised and handmade.",
    "Ask about the glass source. Serious artists know their mouth-blown suppliers."
   ]
  },
  "ar": {
   "name": "الزجاج المعشّق",
   "feel": "مشعّ",
   "essence": "زجاج ملوّن وضوء، يُجمعان يدوياً في صور متوهّجة.",
   "story": [
    "صُنع الزجاج الملوّن في العالم القديم، من مصر إلى روما. وتعلّم الحرفيون رصّ قطعه الصغيرة في النوافذ. فحوّلها الضوء إلى لون حيّ.",
    "في أوروبا الوسيطة نمت هذه الحرفة في الكاتدرائيات الكبرى بنوافذها العالية المتوهّجة. وفي البلاد الإسلامية رصّ الصنّاع الزجاج الملوّن في الجص المنحوت. فأضاءت نوافذ القمريات والشمسيات البيوت والمساجد في القاهرة واليمن.",
    "وفي إيران تملأ نوافذ الأُرسي الغرف بالألوان، كما في مسجد نصير الملك بشيراز. ثم صنع تيفاني زجاجاً يبدو مرسوماً. واليوم يبني فنانو الزجاج النوافذ والألواح والمصابيح حول العالم."
   ],
   "tech": [
    [
     "قطع الزجاج",
     "حزّ الزجاج بعجلة صغيرة ثم كسره بنظافة."
    ],
    [
     "التشذيب",
     "قضم الحواف بالكمّاشة لتطابق تام."
    ],
    [
     "التعشيق بالرصاص",
     "تركيب القطع في شرائط رصاص ليّنة على شكل حرف H."
    ],
    [
     "شريط النحاس",
     "لفّ الحواف بشريط نحاسي ثم لحامها، كما فعل تيفاني."
    ],
    [
     "الرسم على الزجاج",
     "رسم التفاصيل بطلاء زجاجي ثم حرقه في الفرن."
    ],
    [
     "الصبغة الفضية",
     "صبغة صفراء تُحرق في الزجاج لدرجات ذهبية دافئة."
    ],
    [
     "التركيب في الجص",
     "رصّ الزجاج في جص منحوت، كما في القمريات."
    ]
   ],
   "signs": [
    "فقاعات صغيرة وخطوط وتحوّلات لون داخل الزجاج اليدوي.",
    "وصلات لحام ناعمة لكنها غير متطابقة تماماً.",
    "خطوط رصاص أو جص تتبع التصميم، لا غشاء مطبوع.",
    "ألوان تتغيّر بقوة مع حركة ضوء النهار.",
    "ثقل حقيقي وتفاوت خفيف حين تمرّر إصبعك عليه."
   ],
   "tools": [
    [
     "زجاج ملوّن",
     "ألواح منفوخة بالفم أو مدرفلة، قلب العمل."
    ],
    [
     "قاطع الزجاج",
     "عجلة صغيرة تحزّ الزجاج."
    ],
    [
     "كمّاشات التشذيب والكسر",
     "تشكّل الزجاج وتكسره على خط الحزّ."
    ],
    [
     "شرائط الرصاص أو شريط النحاس",
     "تجمع كل قطعة وتمسكها."
    ],
    [
     "كاوية اللحام",
     "تذيب القصدير لتثبيت كل وصلة."
    ],
    [
     "الفرن",
     "يثبّت التفاصيل المرسومة في الزجاج إلى الأبد."
    ],
    [
     "الرسم بالحجم الكامل",
     "المخطط الذي يقود كل قطعة."
    ],
    [
     "جص الجبس",
     "إطار منحوت لنوافذ القمريات."
    ]
   ],
   "time": "من عدة أيام إلى عدة شهور",
   "bestFor": "للصانعين الدقيقين الذين يحبون الضوء واللون والعمل اليدوي المتقن.",
   "artists": [
    [
     "لويس كومفرت تيفاني",
     "أمريكي، 1848–1933",
     "ابتكر زجاجاً أوبالياً غنياً ومصابيح ونوافذ شهيرة."
    ],
    [
     "جون لا فارج",
     "أمريكي، 1835–1910",
     "كان رائد الزجاج الأوبالي، فمنح النوافذ عمقاً كاللوحات."
    ],
    [
     "تشارلز ريني ماكنتوش",
     "بريطاني، 1868–1928",
     "زجاجه المعشّق الأنيق شكّل أسلوب غلاسكو."
    ],
    [
     "فرانك لويد رايت",
     "أمريكي، 1867–1959",
     "صمّم نوافذ زجاج فنية هندسية لبيوته."
    ],
    [
     "براين كلارك",
     "بريطاني، مواليد 1953",
     "يصنع زجاجاً حديثاً جريئاً لمبانٍ حول العالم."
    ]
   ],
   "guide": [
    "ارفعه نحو الضوء. الزجاج اليدوي يُظهر فقاعات وخطوطاً وعمقاً حقيقياً.",
    "افحص الوصلات. خطوط الرصاص أو النحاس الحقيقية بارزة قليلاً ومصنوعة يدوياً.",
    "اسأل عن مصدر الزجاج. الفنانون الجادون يعرفون مورّدي الزجاج المنفوخ بالفم."
   ]
  }
 },
 {
  "id": "digital_illustration",
  "cat": "digital",
  "type": "digital_illustration",
  "level": "beginner",
  "en": {
   "name": "Digital Illustration",
   "feel": "Vivid",
   "essence": "Drawing with a pen on a screen, line by line.",
   "story": [
    "Digital illustration is drawing on a tablet with a pressure-sensitive pen. The tool is new. The skill is as old as pencil on paper.",
    "It grew in the 1980s with the first home computers. Andy Warhol drew on an Amiga in 1985. Designers like April Greiman soon proved the screen could hold real art.",
    "Today it fills books, games, posters and magazines. David Hockney still draws on his iPad. The screen can undo a line. Taste and a steady hand come from practice."
   ],
   "tech": [
    [
     "Line art",
     "Clean outlines drawn by hand with a stylus."
    ],
    [
     "Layers",
     "Separate sheets for sketch, line, color and light."
    ],
    [
     "Flat coloring",
     "Even base colors laid down before any shading."
    ],
    [
     "Cel shading",
     "Hard-edged shadows, like classic animation."
    ],
    [
     "Custom brushes",
     "Brushes the artist builds to mimic pencil, ink or paper."
    ],
    [
     "Clipping masks",
     "Color that stays neatly inside a chosen shape."
    ]
   ],
   "signs": [
    "Working files with many layers, including the first rough sketch.",
    "A time-lapse recording that shows every stroke being drawn.",
    "Lines with natural pressure, tapering at the start and the end.",
    "Small wobbles and corrections that a steady but human hand leaves.",
    "A personal style that stays consistent across years of work."
   ],
   "tools": [
    [
     "Pen tablet or pen display",
     "Lets the hand draw straight onto a digital surface."
    ],
    [
     "Pressure-sensitive stylus",
     "Makes lines thicker or thinner with the force of the hand."
    ],
    [
     "Drawing software",
     "Holds the canvas, the brushes and the layers."
    ],
    [
     "Custom brush sets",
     "Give each artist a signature texture and line."
    ],
    [
     "Color-calibrated screen",
     "Shows colors truthfully, so prints match the file."
    ],
    [
     "Sketchbook",
     "Many illustrators still start ideas on paper."
    ]
   ],
   "time": "A few hours to several days",
   "bestFor": "Artists who love clean lines, bold color and storytelling.",
   "artists": [
    [
     "David Hockney",
     "British, born 1937",
     "He showed that iPhone and iPad drawings can hang in major museums."
    ],
    [
     "Andy Warhol",
     "American, 1928–1987",
     "He drew a famous portrait of Debbie Harry on an Amiga computer in 1985."
    ],
    [
     "April Greiman",
     "American, born 1948",
     "She was among the first designers to treat the early Macintosh as an art tool."
    ],
    [
     "Lois van Baarle",
     "Dutch, born 1985",
     "Known as Loish, she inspires a generation with warm, flowing digital characters."
    ]
   ],
   "guide": [
    "Ask to see the layered file or a recording of the drawing.",
    "Ask what you are buying: a signed limited print, or the file with its rights.",
    "Browse the artist’s older work. A real style grows slowly and stays recognizable."
   ]
  },
  "ar": {
   "name": "الرسم التوضيحي الرقمي",
   "feel": "زاهٍ",
   "essence": "رسم بقلم على شاشة، خطاً بعد خط.",
   "story": [
    "الرسم التوضيحي الرقمي رسم على لوح إلكتروني بقلم يستشعر الضغط. الأداة جديدة. لكن المهارة قديمة قِدم الرصاص على الورق.",
    "نما في الثمانينيات مع أول الحواسيب المنزلية. رسم آندي وارهول على حاسوب أميغا عام 1985. ثم أثبتت مصممات مثل أبريل غريمان أن الشاشة تحمل فناً حقيقياً.",
    "اليوم يملأ الكتب والألعاب والملصقات والمجلات. وما زال ديفيد هوكني يرسم على جهاز الآيباد. الشاشة تتراجع عن خط، لكن الذوق واليد الثابتة يأتيان من سنوات التدريب."
   ],
   "tech": [
    [
     "الخطوط الأساسية",
     "خطوط نظيفة تُرسم باليد بالقلم الرقمي."
    ],
    [
     "الطبقات",
     "أوراق منفصلة للاسكتش والخط واللون والضوء."
    ],
    [
     "التلوين المسطح",
     "ألوان أساسية متساوية قبل أي تظليل."
    ],
    [
     "التظليل الحاد",
     "ظلال بحواف حادة، كالرسوم المتحركة الكلاسيكية."
    ],
    [
     "الفرش المخصّصة",
     "فرش يصنعها الفنان لتحاكي الرصاص أو الحبر أو الورق."
    ],
    [
     "أقنعة القص",
     "لون يبقى داخل الشكل المختار بإتقان."
    ]
   ],
   "signs": [
    "ملفات عمل بطبقات كثيرة، ومعها الاسكتش الأول الخشن.",
    "تسجيل مُسرّع يُظهر رسم كل خط.",
    "خطوط بضغط طبيعي، تدقّ في بدايتها ونهايتها.",
    "اهتزازات وتصحيحات صغيرة تتركها يد ثابتة لكنها بشرية.",
    "أسلوب شخصي يبقى ثابتاً عبر سنوات من العمل."
   ],
   "tools": [
    [
     "لوح رسم أو شاشة رسم",
     "تتيح لليد أن ترسم مباشرة على سطح رقمي."
    ],
    [
     "قلم يستشعر الضغط",
     "يجعل الخط أعرض أو أدق حسب قوة اليد."
    ],
    [
     "برنامج رسم",
     "يحمل اللوحة والفرش والطبقات."
    ],
    [
     "مجموعات فرش مخصّصة",
     "تمنح كل فنان ملمساً وخطاً يميّزانه."
    ],
    [
     "شاشة بألوان مضبوطة",
     "تعرض الألوان بصدق، فتطابق الطباعةُ الملف."
    ],
    [
     "دفتر اسكتش",
     "كثير من الرسامين يبدؤون أفكارهم على الورق."
    ]
   ],
   "time": "من بضع ساعات إلى عدة أيام",
   "bestFor": "لمن يحب الخطوط النظيفة والألوان الجريئة ورواية القصص.",
   "artists": [
    [
     "ديفيد هوكني",
     "بريطاني، مواليد 1937",
     "أثبت أن رسوم الآيفون والآيباد تستحق جدران المتاحف الكبرى."
    ],
    [
     "آندي وارهول",
     "أمريكي، 1928–1987",
     "رسم صورة شهيرة لديبي هاري على حاسوب أميغا عام 1985."
    ],
    [
     "أبريل غريمان",
     "أمريكية، مواليد 1948",
     "من أوائل المصممين الذين عاملوا حاسوب ماكنتوش الأول كأداة فنية."
    ],
    [
     "لويس فان بارل",
     "هولندية، مواليد 1985",
     "تُعرف باسم لويش، وتُلهم جيلاً كاملاً بشخصياتها الرقمية الدافئة."
    ]
   ],
   "guide": [
    "اطلب رؤية الملف بطبقاته أو تسجيلاً لمراحل الرسم.",
    "اسأل عمّا تشتريه: طبعة موقّعة محدودة، أم الملف مع حقوقه.",
    "تصفّح أعمال الفنان القديمة. الأسلوب الحقيقي ينمو ببطء ويبقى مميّزاً."
   ]
  }
 },
 {
  "id": "digital_painting",
  "cat": "digital",
  "type": "digital_painting",
  "level": "intermediate",
  "en": {
   "name": "Digital Painting",
   "feel": "Cinematic",
   "essence": "Painting with light on a screen, brushstroke by brushstroke.",
   "story": [
    "Digital painting borrows everything from oil and watercolor: light, color, edges and mood. Only the canvas has changed.",
    "It grew up in film and video games in the 1990s. Artists there painted the first look of new worlds. Craig Mullins showed it could feel as rich as a classical canvas.",
    "Today painters work on large tablets and print their work on fine art paper. A strong digital painting still needs a human eye. Choosing what to show is the art."
   ],
   "tech": [
    [
     "Blocking in",
     "Big shapes of color first, details last."
    ],
    [
     "Value study",
     "A black and white version to test the light."
    ],
    [
     "Color grading",
     "Adjusting the mood of the whole picture, like in film."
    ],
    [
     "Texture brushes",
     "Brushes that imitate canvas, bristle or thick paint."
    ],
    [
     "Lost and found edges",
     "Some edges stay sharp, others melt away, to guide the eye."
    ],
    [
     "Speed painting",
     "Fast studies to explore an idea before the real work."
    ]
   ],
   "signs": [
    "A history you can follow, from a rough start to a refined finish.",
    "Visible brushstrokes and edges chosen by hand, not filters.",
    "Small, natural imperfections in light and anatomy.",
    "Sketches or color studies that came before the final piece.",
    "The same hand and color sense across the artist’s whole portfolio."
   ],
   "tools": [
    [
     "Pen display or tablet",
     "The surface where every brushstroke is painted."
    ],
    [
     "Pressure-sensitive stylus",
     "Controls the size and flow of each stroke."
    ],
    [
     "Painting software",
     "Offers brushes, layers and blending modes."
    ],
    [
     "Texture brush library",
     "Brings the feel of real bristles and paper."
    ],
    [
     "Calibrated monitor",
     "Keeps colors true from screen to print."
    ],
    [
     "Archival printer and paper",
     "Turns the file into a lasting fine art print."
    ]
   ],
   "time": "One day to several weeks",
   "bestFor": "Painters who love light, atmosphere and big cinematic scenes.",
   "artists": [
    [
     "Craig Mullins",
     "American, born 1964",
     "A pioneer whose painterly digital work shaped film and game art."
    ],
    [
     "David Hockney",
     "British, born 1937",
     "His iPad paintings of spring in Normandy filled galleries around the world."
    ],
    [
     "Jeremy Blake",
     "American, 1971–2007",
     "He made moving digital paintings that museums now treasure."
    ],
    [
     "Laurence Gartel",
     "American, born 1956",
     "An early pioneer who painted and collaged with computers from the 1970s."
    ]
   ],
   "guide": [
    "Ask for the journey: early sketches, layers or a recording.",
    "Prefer signed, numbered prints on archival paper, with a certificate.",
    "Make sure the license is clear, especially for any commercial use."
   ]
  },
  "ar": {
   "name": "الرسم الرقمي",
   "feel": "سينمائي",
   "essence": "رسم بالضوء على شاشة، ضربة فرشاة بعد أخرى.",
   "story": [
    "يستعير الرسم الرقمي كل شيء من الزيت والألوان المائية: الضوء واللون والحواف والإحساس. وحده القماش تغيّر.",
    "نشأ في السينما وألعاب الفيديو في التسعينيات. هناك رسم الفنانون أول صورة لعوالم جديدة. وأثبت كريغ مولينز أنه قد يكون غنياً كلوحة كلاسيكية.",
    "اليوم يرسم الفنانون على ألواح كبيرة ويطبعون أعمالهم على ورق فني. واللوحة الرقمية القوية تحتاج دائماً إلى عين إنسان. فاختيار ما يُظهَر هو الفن."
   ],
   "tech": [
    [
     "التكتيل الأولي",
     "أشكال لون كبيرة أولاً، والتفاصيل في النهاية."
    ],
    [
     "دراسة القيم",
     "نسخة بالأبيض والأسود لاختبار الضوء."
    ],
    [
     "ضبط الألوان",
     "تعديل مزاج الصورة كلها، كما في السينما."
    ],
    [
     "فرش الملمس",
     "فرش تحاكي القماش أو الشعيرات أو الطلاء الكثيف."
    ],
    [
     "الحواف الظاهرة والذائبة",
     "حواف تبقى حادة وأخرى تذوب، لتقود العين."
    ],
    [
     "الرسم السريع",
     "دراسات سريعة لاستكشاف فكرة قبل العمل الحقيقي."
    ]
   ],
   "signs": [
    "مسار يمكن تتبّعه، من بداية خشنة إلى نهاية متقنة.",
    "ضربات فرشاة وحواف اختارتها اليد، لا مرشّحات جاهزة.",
    "عيوب صغيرة طبيعية في الضوء والتشريح.",
    "اسكتشات أو دراسات لونية سبقت العمل النهائي.",
    "اليد نفسها وحسّ اللون نفسه في كل أعمال الفنان."
   ],
   "tools": [
    [
     "شاشة رسم أو لوح",
     "السطح الذي تُرسم عليه كل ضربة فرشاة."
    ],
    [
     "قلم يستشعر الضغط",
     "يتحكم بحجم كل ضربة وانسيابها."
    ],
    [
     "برنامج رسم",
     "يقدّم الفرش والطبقات وأنماط المزج."
    ],
    [
     "مكتبة فرش الملمس",
     "تمنح إحساس الشعيرات الحقيقية والورق."
    ],
    [
     "شاشة مضبوطة الألوان",
     "تحفظ صدق الألوان من الشاشة إلى الطباعة."
    ],
    [
     "طابعة وورق أرشيفي",
     "تحوّل الملف إلى طبعة فنية تدوم طويلاً."
    ]
   ],
   "time": "من يوم واحد إلى عدة أسابيع",
   "bestFor": "للرسامين الذين يعشقون الضوء والأجواء والمشاهد السينمائية الكبيرة.",
   "artists": [
    [
     "كريغ مولينز",
     "أمريكي، مواليد 1964",
     "رائد شكّلت أعماله الرقمية الشبيهة بالزيت فنون السينما والألعاب."
    ],
    [
     "ديفيد هوكني",
     "بريطاني، مواليد 1937",
     "ملأت لوحاته على الآيباد عن ربيع نورماندي صالات العرض حول العالم."
    ],
    [
     "جيريمي بليك",
     "أمريكي، 1971–2007",
     "صنع لوحات رقمية متحركة تحتفظ بها المتاحف اليوم."
    ],
    [
     "لورنس غارتل",
     "أمريكي، مواليد 1956",
     "رائد مبكر رسم وصنع الكولاج بالحاسوب منذ السبعينيات."
    ]
   ],
   "guide": [
    "اطلب رحلة العمل: اسكتشات أولى أو طبقات أو تسجيل.",
    "فضّل الطبعات الموقّعة والمرقّمة على ورق أرشيفي، مع شهادة.",
    "تأكد أن الترخيص واضح، خاصة لأي استخدام تجاري."
   ]
  }
 },
 {
  "id": "sculpting_3d",
  "cat": "digital",
  "type": "sculpting_3d",
  "level": "advanced",
  "en": {
   "name": "3D Sculpting",
   "feel": "Dimensional",
   "essence": "Shaping virtual clay by hand into forms you can turn.",
   "story": [
    "Digital sculpting works like clay. The artist pushes, pulls and smooths a surface with a stylus.",
    "Its rules come from classical sculpture. Michelangelo and Auguste Rodin studied weight, balance and the body for a lifetime. Digital sculptors study the very same things.",
    "Today many digital sculptures are 3D printed, or cast in resin or bronze. Artists like Bathsheba Grossman print shapes no chisel could reach. The idea travels from the screen into the room."
   ],
   "tech": [
    [
     "Digital clay",
     "Shaping a virtual surface with push and pull brushes."
    ],
    [
     "Blocking out",
     "Rough primary forms first, to fix the pose and proportion."
    ],
    [
     "Dynamic detail",
     "Adding fine wrinkles, pores and folds only where needed."
    ],
    [
     "Retopology",
     "Rebuilding a clean mesh over a finished sculpt."
    ],
    [
     "Texturing",
     "Painting color and surface detail onto the form."
    ],
    [
     "Lighting and rendering",
     "Placing virtual lights to reveal the form."
    ],
    [
     "3D printing",
     "Turning the sculpt into a real object you can hold."
    ]
   ],
   "signs": [
    "Turntable views that show the sculpt from every side.",
    "Work in progress: rough blocks first, then layers of detail.",
    "Asymmetry and personal touch, not a perfect mirrored shape.",
    "Anatomy and weight that come from study, not a ready-made model.",
    "Hand-finished prints with sanding, painting or patina by the artist."
   ],
   "tools": [
    [
     "Pen tablet",
     "Gives the hand pressure control over the virtual clay."
    ],
    [
     "Sculpting software",
     "Holds the digital clay and the sculpting brushes."
    ],
    [
     "Reference photos and anatomy books",
     "Keep forms true to life."
    ],
    [
     "Rendering software",
     "Lights and presents the finished sculpt."
    ],
    [
     "3D printer",
     "Builds the sculpt layer by layer in resin or plastic."
    ],
    [
     "Sanding and painting tools",
     "Finish the printed piece by hand."
    ]
   ],
   "time": "Several days to several weeks",
   "bestFor": "Patient artists who love form, anatomy and real-world objects.",
   "artists": [
    [
     "Michelangelo",
     "Italian, 1475–1564",
     "His study of the human body still guides every figure sculptor."
    ],
    [
     "Auguste Rodin",
     "French, 1840–1917",
     "He gave sculpture raw emotion, with surfaces full of the hand."
    ],
    [
     "Zaha Hadid",
     "Iraqi-British architect, 1950–2016",
     "She shaped flowing forms on screen and built them in the real world."
    ],
    [
     "Bathsheba Grossman",
     "American, born 1966",
     "She designs mathematical sculptures and prints them in metal."
    ]
   ],
   "guide": [
    "Ask for turntable videos and in-progress screenshots.",
    "For printed pieces, ask about the material, size and edition number.",
    "Check that the model is original, not bought from an online library."
   ]
  },
  "ar": {
   "name": "النحت الرقمي ثلاثي الأبعاد",
   "feel": "مجسّم",
   "essence": "تشكيل طين افتراضي باليد إلى أشكال تُدار.",
   "story": [
    "النحت الرقمي يشبه العمل بالطين. يدفع الفنان السطح ويسحبه وينعّمه بالقلم الرقمي.",
    "قواعده تأتي من النحت الكلاسيكي. درس مايكل أنجلو وأوغست رودان الوزن والتوازن والجسد طوال حياتهما. والنحّاتون الرقميون يدرسون الأمور نفسها تماماً.",
    "اليوم تُطبع منحوتات رقمية كثيرة، أو تُصبّ بالراتنج أو البرونز. وتطبع فنانات مثل باثشيبا غروسمان أشكالاً لا يبلغها إزميل. فتنتقل الفكرة من الشاشة إلى الغرفة."
   ],
   "tech": [
    [
     "الطين الرقمي",
     "تشكيل سطح افتراضي بفرش الدفع والسحب."
    ],
    [
     "الكتل الأولى",
     "أشكال خشنة أولاً لتثبيت الوضعية والنِّسب."
    ],
    [
     "التفاصيل الديناميكية",
     "إضافة التجاعيد والمسام والطيّات حيث تلزم فقط."
    ],
    [
     "إعادة بناء الشبكة",
     "بناء شبكة نظيفة فوق المنحوتة المكتملة."
    ],
    [
     "الإكساء",
     "رسم اللون وتفاصيل السطح على الشكل."
    ],
    [
     "الإضاءة والإخراج",
     "وضع أضواء افتراضية لإظهار الشكل."
    ],
    [
     "الطباعة ثلاثية الأبعاد",
     "تحويل المنحوتة إلى قطعة حقيقية تمسكها بيدك."
    ]
   ],
   "signs": [
    "عرض دوّار يُظهر المنحوتة من كل جانب.",
    "مراحل العمل: كتل خشنة أولاً ثم طبقات من التفاصيل.",
    "عدم تماثل ولمسة شخصية، لا شكلاً منعكساً مثالياً.",
    "تشريح ووزن يأتيان من الدراسة، لا من نموذج جاهز.",
    "طبعات أنهاها الفنان بيده بالصنفرة أو الطلاء أو التعتيق."
   ],
   "tools": [
    [
     "لوح رسم",
     "يمنح اليد تحكماً بالضغط على الطين الافتراضي."
    ],
    [
     "برنامج نحت",
     "يحمل الطين الرقمي وفرش النحت."
    ],
    [
     "صور مرجعية وكتب تشريح",
     "تُبقي الأشكال صادقة مع الحياة."
    ],
    [
     "برنامج إخراج",
     "يضيء المنحوتة المكتملة ويعرضها."
    ],
    [
     "طابعة ثلاثية الأبعاد",
     "تبني المنحوتة طبقة بعد طبقة من الراتنج أو البلاستيك."
    ],
    [
     "أدوات صنفرة وطلاء",
     "لإنهاء القطعة المطبوعة باليد."
    ]
   ],
   "time": "من عدة أيام إلى عدة أسابيع",
   "bestFor": "للفنانين الصبورين الذين يحبون الشكل والتشريح والقطع الحقيقية.",
   "artists": [
    [
     "مايكل أنجلو",
     "إيطالي، 1475–1564",
     "ما زالت دراسته لجسد الإنسان تهدي كل نحّات للأشكال البشرية."
    ],
    [
     "أوغست رودان",
     "فرنسي، 1840–1917",
     "منح النحت عاطفة صادقة، بأسطح مليئة بأثر اليد."
    ],
    [
     "زها حديد",
     "معمارية عراقية بريطانية، 1950–2016",
     "شكّلت على الشاشة أشكالاً منسابة وبنتها في العالم الحقيقي."
    ],
    [
     "باثشيبا غروسمان",
     "أمريكية، مواليد 1966",
     "تصمم منحوتات رياضية وتطبعها بالمعدن."
    ]
   ],
   "guide": [
    "اطلب فيديو عرض دوّار ولقطات لمراحل العمل.",
    "للقطع المطبوعة، اسأل عن المادة والحجم ورقم النسخة.",
    "تأكد أن النموذج أصلي، لا مشترى من مكتبة على الإنترنت."
   ]
  }
 },
 {
  "id": "pixel_art",
  "cat": "digital",
  "type": "pixel_art",
  "level": "beginner",
  "en": {
   "name": "Pixel Art",
   "feel": "Playful",
   "essence": "Pictures built one tiny square at a time, by hand.",
   "story": [
    "Pixel art was born with early computers and arcade games. Screens were small, so every square had to count.",
    "In the 1980s, Susan Kare drew the first Macintosh icons on graph paper. Japanese game artists gave life to heroes in just a few colors.",
    "Today pixel art is loved for its charm and discipline. Artists place each square with care. The street artist Invader even turns it into tile mosaics on city walls."
   ],
   "tech": [
    [
     "Limited palette",
     "A small set of colors chosen with intention."
    ],
    [
     "Clean outlines",
     "Lines one square wide, with no stray pixels."
    ],
    [
     "Dithering",
     "Checkered patterns that blend two colors into a third."
    ],
    [
     "Anti-aliasing by hand",
     "Placing in-between colors to soften rough edges."
    ],
    [
     "Sprite animation",
     "Drawing each frame of a small moving character."
    ],
    [
     "Tiling",
     "Making patterns that repeat seamlessly across a scene."
    ]
   ],
   "signs": [
    "Every pixel sits on purpose, with no blurry or stretched edges.",
    "Hand-picked in-between colors along curves, not automatic smoothing.",
    "A carefully limited palette that stays the same across a series.",
    "Early sketches and frame-by-frame drafts the artist can show.",
    "Small choices that give a tiny face real feeling and character."
   ],
   "tools": [
    [
     "Pixel art software",
     "A grid canvas made for placing single pixels."
    ],
    [
     "Mouse or pen tablet",
     "For precise clicks on each square."
    ],
    [
     "Graph paper",
     "For planning shapes and icons by hand."
    ],
    [
     "Palette files",
     "Store the chosen colors for a whole series."
    ],
    [
     "Grid and zoom view",
     "Lets the artist work square by square."
    ],
    [
     "Tile and bead kits",
     "Turn pixel designs into physical mosaics."
    ]
   ],
   "time": "A few hours to several weeks",
   "bestFor": "Patient artists who enjoy rules, games and small perfect details.",
   "artists": [
    [
     "Susan Kare",
     "American, born 1954",
     "She drew the original Macintosh icons, pixel by pixel."
    ],
    [
     "Shigeru Miyamoto",
     "Japanese, born 1952",
     "He created Mario, shaped by the tight pixel limits of 1980s games."
    ],
    [
     "Toru Iwatani",
     "Japanese, born 1955",
     "He designed Pac-Man, one of the most loved pixel characters ever."
    ],
    [
     "Invader",
     "French, born 1969",
     "He places pixel art mosaics on walls in cities around the world."
    ]
   ],
   "guide": [
    "Zoom in on the file. Clean, deliberate pixels show careful hand work.",
    "Ask for the original small file, not only an enlarged copy.",
    "For prints, ask for sharp scaling with no blurring at any size."
   ]
  },
  "ar": {
   "name": "فن البكسل",
   "feel": "مَرِح",
   "essence": "صور تُبنى مربعاً صغيراً بعد آخر، باليد.",
   "story": [
    "وُلد فن البكسل مع الحواسيب الأولى وألعاب الأركيد. كانت الشاشات صغيرة، فكان لكل مربع وزنه.",
    "في الثمانينيات رسمت سوزان كير أيقونات ماكنتوش الأولى على ورق مربعات. ومنح فنانو الألعاب اليابانيون الحياة لأبطال بألوان قليلة.",
    "اليوم يُحَب فن البكسل لسحره وانضباطه. يضع الفنان كل مربع بعناية. بل يحوّله فنان الشارع إنفيدر إلى فسيفساء على جدران المدن."
   ],
   "tech": [
    [
     "لوحة ألوان محدودة",
     "مجموعة صغيرة من الألوان تُختار بقصد."
    ],
    [
     "خطوط نظيفة",
     "خطوط بعرض مربع واحد، بلا نقاط شاردة."
    ],
    [
     "التنقيط المتناوب",
     "نقوش كرقعة الشطرنج تمزج لونين في لون ثالث."
    ],
    [
     "تنعيم الحواف يدوياً",
     "وضع ألوان وسيطة لتليين الحواف الخشنة."
    ],
    [
     "تحريك الشخصيات",
     "رسم كل إطار لشخصية صغيرة متحركة."
    ],
    [
     "البلاطات المتكررة",
     "صنع نقوش تتكرر بسلاسة عبر المشهد."
    ]
   ],
   "signs": [
    "كل بكسل في مكانه عن قصد، بلا حواف ضبابية أو ممطوطة.",
    "ألوان وسيطة مختارة باليد على المنحنيات، لا تنعيماً تلقائياً.",
    "لوحة ألوان محدودة بعناية تبقى ثابتة عبر السلسلة.",
    "اسكتشات أولى ومسودات إطاراً بإطار يستطيع الفنان عرضها.",
    "اختيارات صغيرة تمنح وجهاً صغيراً إحساساً وشخصية حقيقيين."
   ],
   "tools": [
    [
     "برنامج فن البكسل",
     "لوحة شبكية مصممة لوضع البكسل منفرداً."
    ],
    [
     "فأرة أو لوح رسم",
     "لنقرات دقيقة على كل مربع."
    ],
    [
     "ورق مربعات",
     "لتخطيط الأشكال والأيقونات باليد."
    ],
    [
     "ملفات لوحة الألوان",
     "تحفظ الألوان المختارة لسلسلة كاملة."
    ],
    [
     "عرض الشبكة والتكبير",
     "يتيح العمل مربعاً بمربع."
    ],
    [
     "قطع البلاط والخرز",
     "تحوّل تصاميم البكسل إلى فسيفساء ملموسة."
    ]
   ],
   "time": "من بضع ساعات إلى عدة أسابيع",
   "bestFor": "للفنانين الصبورين الذين يستمتعون بالقواعد والألعاب والتفاصيل الصغيرة المتقنة.",
   "artists": [
    [
     "سوزان كير",
     "أمريكية، مواليد 1954",
     "رسمت أيقونات ماكنتوش الأصلية، بكسلاً بعد بكسل."
    ],
    [
     "شيغيرو مياموتو",
     "ياباني، مواليد 1952",
     "ابتكر شخصية ماريو ضمن حدود البكسل الضيقة لألعاب الثمانينيات."
    ],
    [
     "تورو إيواتاني",
     "ياباني، مواليد 1955",
     "صمّم باك مان، من أحب شخصيات البكسل على الإطلاق."
    ],
    [
     "إنفيدر",
     "فرنسي، مواليد 1969",
     "يضع فسيفساء فن البكسل على جدران مدن حول العالم."
    ]
   ],
   "guide": [
    "كبّر الملف. البكسلات النظيفة المقصودة تكشف عملاً يدوياً متأنياً.",
    "اطلب الملف الأصلي الصغير، لا نسخة مكبّرة فقط.",
    "للطبعات، اطلب تكبيراً حاداً بلا ضبابية في أي حجم."
   ]
  }
 },
 {
  "id": "motion_graphics",
  "cat": "digital",
  "type": "motion_graphics",
  "level": "intermediate",
  "en": {
   "name": "Motion Graphics",
   "feel": "Rhythmic",
   "essence": "Shapes, type and color brought to life through timing.",
   "story": [
    "Motion graphics began long before computers. Oskar Fischinger and Norman McLaren drew and painted music onto film by hand.",
    "In the 1950s, Saul Bass turned film titles into small works of art. John Whitney then used early computers to make shapes dance.",
    "Today motion designers animate logos, music videos and film titles. Every second needs dozens of choices. Timing, rhythm and feeling still come from the artist."
   ],
   "tech": [
    [
     "Storyboarding",
     "Sketching key moments by hand before any animation."
    ],
    [
     "Keyframing",
     "Setting the start and end of each movement."
    ],
    [
     "Easing",
     "Making motion speed up and slow down naturally."
    ],
    [
     "Kinetic typography",
     "Letters that move to match the meaning of words."
    ],
    [
     "Shape morphing",
     "One form flowing smoothly into another."
    ],
    [
     "Sound sync",
     "Matching every cut and movement to the music."
    ]
   ],
   "signs": [
    "Hand-drawn storyboards and style frames made before the animation.",
    "Timing that breathes with the music, not stiff, even movement.",
    "Original shapes and type, not ready-made templates.",
    "Project files with the artist’s own layers and keyframes.",
    "A recognizable sense of rhythm across the artist’s other films."
   ],
   "tools": [
    [
     "Sketchbook",
     "For storyboards and first ideas."
    ],
    [
     "Animation software",
     "Sets keyframes, layers and timing."
    ],
    [
     "Graph editor",
     "Fine-tunes the speed curve of every movement."
    ],
    [
     "Pen tablet",
     "Draws frame-by-frame details by hand."
    ],
    [
     "Audio editor",
     "Cuts and marks the music for timing."
    ],
    [
     "Rendering computer",
     "Turns the project into the final video."
    ]
   ],
   "time": "Several days to several weeks",
   "bestFor": "Designers who love music, rhythm and bringing type to life.",
   "artists": [
    [
     "Oskar Fischinger",
     "German-American, 1900–1967",
     "He painted abstract shapes that danced to music on film."
    ],
    [
     "Norman McLaren",
     "Scottish-Canadian, 1914–1987",
     "He drew and scratched animation straight onto film strips."
    ],
    [
     "Saul Bass",
     "American, 1920–1996",
     "He turned opening titles into bold graphic art."
    ],
    [
     "John Whitney",
     "American, 1917–1995",
     "He used early computers to make shapes move in harmony."
    ],
    [
     "Kyle Cooper",
     "American, born 1962",
     "His title sequences made the film opening an art form again."
    ]
   ],
   "guide": [
    "Ask for storyboards and style frames from the earliest stage.",
    "Check what you are buying: the video file, a signed edition, or rights.",
    "Watch the artist’s reel. A personal sense of timing is hard to copy."
   ]
  },
  "ar": {
   "name": "الرسوم المتحركة الجرافيكية",
   "feel": "إيقاعي",
   "essence": "أشكال وحروف وألوان تنبض بالحياة عبر التوقيت.",
   "story": [
    "بدأت الرسوم المتحركة الجرافيكية قبل الحاسوب بزمن طويل. فقد رسم أوسكار فيشينغر ونورمان ماكلارين الموسيقى على الفيلم باليد.",
    "في الخمسينيات حوّل سول باس عناوين الأفلام إلى أعمال فنية صغيرة. ثم استخدم جون ويتني الحواسيب الأولى ليجعل الأشكال ترقص.",
    "اليوم يحرّك المصممون الشعارات ومقاطع الموسيقى وعناوين الأفلام. كل ثانية تحتاج عشرات القرارات. والتوقيت والإيقاع والإحساس ما زالت من الفنان."
   ],
   "tech": [
    [
     "لوحة القصة",
     "رسم اللحظات الأساسية باليد قبل أي تحريك."
    ],
    [
     "الإطارات المفتاحية",
     "تحديد بداية كل حركة ونهايتها."
    ],
    [
     "التسارع والتباطؤ",
     "جعل الحركة تسرع وتبطؤ بشكل طبيعي."
    ],
    [
     "الحروف المتحركة",
     "حروف تتحرك لتوافق معنى الكلمات."
    ],
    [
     "تحوّل الأشكال",
     "شكل ينساب بنعومة إلى شكل آخر."
    ],
    [
     "التزامن مع الصوت",
     "مطابقة كل قطع وحركة مع الموسيقى."
    ]
   ],
   "signs": [
    "لوحات قصة وإطارات أسلوب مرسومة باليد قبل التحريك.",
    "توقيت يتنفس مع الموسيقى، لا حركة جامدة متساوية.",
    "أشكال وحروف أصلية، لا قوالب جاهزة.",
    "ملفات مشروع فيها طبقات الفنان وإطاراته المفتاحية.",
    "إحساس بالإيقاع يُعرَف به الفنان في أفلامه الأخرى."
   ],
   "tools": [
    [
     "دفتر اسكتش",
     "للوحات القصة والأفكار الأولى."
    ],
    [
     "برنامج تحريك",
     "يضبط الإطارات المفتاحية والطبقات والتوقيت."
    ],
    [
     "محرر المنحنيات",
     "يضبط سرعة كل حركة بدقة."
    ],
    [
     "لوح رسم",
     "لرسم التفاصيل إطاراً بإطار باليد."
    ],
    [
     "محرر صوت",
     "لقطع الموسيقى ووضع علامات التوقيت."
    ],
    [
     "حاسوب للإخراج",
     "يحوّل المشروع إلى الفيديو النهائي."
    ]
   ],
   "time": "من عدة أيام إلى عدة أسابيع",
   "bestFor": "للمصممين الذين يحبون الموسيقى والإيقاع وبثّ الحياة في الحروف.",
   "artists": [
    [
     "أوسكار فيشينغر",
     "ألماني أمريكي، 1900–1967",
     "رسم أشكالاً تجريدية ترقص على الموسيقى في الأفلام."
    ],
    [
     "نورمان ماكلارين",
     "اسكتلندي كندي، 1914–1987",
     "رسم الرسوم المتحركة وحفرها مباشرة على شرائط الفيلم."
    ],
    [
     "سول باس",
     "أمريكي، 1920–1996",
     "حوّل عناوين الأفلام الافتتاحية إلى فن جرافيكي جريء."
    ],
    [
     "جون ويتني",
     "أمريكي، 1917–1995",
     "استخدم الحواسيب الأولى لتتحرك الأشكال في انسجام."
    ],
    [
     "كايل كوبر",
     "أمريكي، مواليد 1962",
     "أعادت مقدماته لافتتاحية الفيلم مكانتها كفن."
    ]
   ],
   "guide": [
    "اطلب لوحات القصة وإطارات الأسلوب من المرحلة الأولى.",
    "تأكد مما تشتريه: ملف الفيديو، أم نسخة موقّعة، أم الحقوق.",
    "شاهد أعمال الفنان المجمّعة. الإحساس الشخصي بالتوقيت يصعب تقليده."
   ]
  }
 },
 {
  "id": "concept_art",
  "cat": "digital",
  "type": "concept_art",
  "level": "advanced",
  "en": {
   "name": "Concept Art",
   "feel": "Visionary",
   "essence": "Imagining new worlds before anyone builds them.",
   "story": [
    "Concept art is the first picture of a world that does not exist yet. Films and games start here.",
    "In the 1970s, Ralph McQuarrie painted the look of Star Wars with gouache and brushes. Syd Mead drew the future cities of Blade Runner. Moebius dreamed up whole planets in ink.",
    "Today most concept artists paint digitally, often in hours, not weeks. The goal is the same. Solve a design problem and spark the imagination of a whole team."
   ],
   "tech": [
    [
     "Thumbnail sketches",
     "Dozens of tiny drawings to explore ideas fast."
    ],
    [
     "Silhouette design",
     "Testing a character or ship by its outline alone."
    ],
    [
     "Photo bashing",
     "Combining the artist’s own photos with paint as a quick base."
    ],
    [
     "Keyframe painting",
     "A single cinematic moment that sets the mood of a story."
    ],
    [
     "Orthographic views",
     "Front, side and back drawings so builders can make it real."
    ],
    [
     "Callouts",
     "Small notes and close-ups that explain materials and details."
    ]
   ],
   "signs": [
    "Pages of thumbnails and rejected ideas before the final design.",
    "Design choices that solve a real problem, like how a machine moves.",
    "A clear hand in brushwork, edges and lighting.",
    "Turnarounds and detail sheets made by the same artist.",
    "Photos used in the work are the artist’s own, or fully licensed."
   ],
   "tools": [
    [
     "Sketchbook and pens",
     "For quick thumbnails and first ideas."
    ],
    [
     "Pen display or tablet",
     "For painting final concepts."
    ],
    [
     "Painting software",
     "Offers layers, brushes and fast color changes."
    ],
    [
     "Simple 3D software",
     "Blocks out scenes to check perspective and light."
    ],
    [
     "Personal photo library",
     "The artist’s own textures and references."
    ],
    [
     "Gouache and markers",
     "Many artists still sketch with traditional materials."
    ]
   ],
   "time": "A few hours to several days per piece",
   "bestFor": "Imaginative artists who love films, games and world building.",
   "artists": [
    [
     "Syd Mead",
     "American, 1933–2019",
     "He designed the future cities and vehicles of Blade Runner."
    ],
    [
     "Ralph McQuarrie",
     "American, 1929–2012",
     "His paintings gave Star Wars its first look."
    ],
    [
     "Ron Cobb",
     "American-Australian, 1937–2020",
     "He designed ships and machines for Alien and other classic films."
    ],
    [
     "Moebius (Jean Giraud)",
     "French, 1938–2012",
     "His ink worlds inspired generations of film and comic designers."
    ],
    [
     "Craig Mullins",
     "American, born 1964",
     "He helped bring concept painting into the digital age."
    ]
   ],
   "guide": [
    "Ask to see thumbnails, sketches and the design steps behind the piece.",
    "Check who owns the rights. Studio work often belongs to the studio.",
    "Prefer personal pieces or signed prints the artist is free to sell."
   ]
  },
  "ar": {
   "name": "فن المفاهيم",
   "feel": "حالم",
   "essence": "تخيّل عوالم جديدة قبل أن يبنيها أحد.",
   "story": [
    "فن المفاهيم هو الصورة الأولى لعالم لم يوجد بعد. ومنه تبدأ الأفلام والألعاب.",
    "في السبعينيات رسم رالف مكواري ملامح حرب النجوم بالغواش والفرشاة. ورسم سيد ميد مدن المستقبل في بليد رانر. وتخيّل موبيوس كواكب كاملة بالحبر.",
    "اليوم يرسم معظم فناني المفاهيم رقمياً، غالباً في ساعات لا أسابيع. والهدف لم يتغير. حلّ مسألة تصميم وإشعال خيال فريق كامل."
   ],
   "tech": [
    [
     "الاسكتشات المصغّرة",
     "عشرات الرسوم الصغيرة لاستكشاف الأفكار بسرعة."
    ],
    [
     "تصميم الظل الخارجي",
     "اختبار الشخصية أو المركبة بحدودها وحدها."
    ],
    [
     "دمج الصور",
     "مزج صور الفنان الخاصة بالرسم كأساس سريع."
    ],
    [
     "اللقطة المفتاحية",
     "لحظة سينمائية واحدة تحدد مزاج القصة."
    ],
    [
     "المساقط الهندسية",
     "رسوم أمامية وجانبية وخلفية ليصنعها البنّاؤون."
    ],
    [
     "الملاحظات التوضيحية",
     "ملاحظات ولقطات قريبة تشرح المواد والتفاصيل."
    ]
   ],
   "signs": [
    "صفحات من الاسكتشات والأفكار المرفوضة قبل التصميم النهائي.",
    "قرارات تصميم تحل مسألة حقيقية، كطريقة حركة آلة.",
    "يد واضحة في ضربات الفرشاة والحواف والإضاءة.",
    "عروض دوّارة وأوراق تفاصيل من الفنان نفسه.",
    "الصور المستخدمة في العمل من تصوير الفنان، أو مرخّصة بالكامل."
   ],
   "tools": [
    [
     "دفتر اسكتش وأقلام",
     "للمصغّرات السريعة والأفكار الأولى."
    ],
    [
     "شاشة رسم أو لوح",
     "لرسم التصاميم النهائية."
    ],
    [
     "برنامج رسم",
     "يقدّم الطبقات والفرش وتغيير الألوان بسرعة."
    ],
    [
     "برنامج ثلاثي الأبعاد بسيط",
     "لتكتيل المشاهد وفحص المنظور والضوء."
    ],
    [
     "مكتبة صور شخصية",
     "ملامس الفنان ومراجعه الخاصة."
    ],
    [
     "الغواش وأقلام التحديد",
     "كثير من الفنانين ما زالوا يرسمون بالأدوات التقليدية."
    ]
   ],
   "time": "من بضع ساعات إلى عدة أيام للعمل الواحد",
   "bestFor": "للفنانين أصحاب الخيال الذين يحبون الأفلام والألعاب وبناء العوالم.",
   "artists": [
    [
     "سيد ميد",
     "أمريكي، 1933–2019",
     "صمّم مدن المستقبل ومركباته في فيلم بليد رانر."
    ],
    [
     "رالف مكواري",
     "أمريكي، 1929–2012",
     "منحت لوحاته حرب النجوم ملامحها الأولى."
    ],
    [
     "رون كوب",
     "أمريكي أسترالي، 1937–2020",
     "صمّم سفناً وآلات لفيلم إيليين وأفلام كلاسيكية أخرى."
    ],
    [
     "موبيوس (جان جيرو)",
     "فرنسي، 1938–2012",
     "ألهمت عوالمه بالحبر أجيالاً من مصممي الأفلام والقصص المصوّرة."
    ],
    [
     "كريغ مولينز",
     "أمريكي، مواليد 1964",
     "ساعد في نقل رسم المفاهيم إلى العصر الرقمي."
    ]
   ],
   "guide": [
    "اطلب رؤية المصغّرات والاسكتشات وخطوات التصميم خلف العمل.",
    "تأكد لمن الحقوق. أعمال الاستوديوهات غالباً ملك للاستوديو.",
    "فضّل الأعمال الشخصية أو الطبعات الموقّعة التي يحق للفنان بيعها."
   ]
  }
 },
 {
  "id": "generative_art",
  "cat": "digital",
  "type": "generative_art",
  "level": "advanced",
  "en": {
   "name": "Generative Art",
   "feel": "Curious",
   "essence": "Art where the artist writes the rules by hand.",
   "story": [
    "In generative art, the artist writes a set of rules, often as code. The rules draw, and the artist decides what stays.",
    "It began in the 1960s. Georg Nees, Frieder Nake and Vera Molnár wrote programs that guided pen plotters across paper. Molnár had already explored rule-based drawing by hand.",
    "Today artists write their own code and draw with pen plotters on fine paper. Every rule, every color and every chosen result comes from a human mind."
   ],
   "tech": [
    [
     "Hand-written code",
     "The artist writes every rule of the drawing personally."
    ],
    [
     "Controlled randomness",
     "Small surprises inside strict limits the artist sets."
    ],
    [
     "Pen plotting",
     "A moving pen draws the code onto real paper with real ink."
    ],
    [
     "Iteration",
     "Running the rules many times and studying each result."
    ],
    [
     "Curation",
     "The artist chooses the few outcomes worth keeping."
    ],
    [
     "Rule sketching",
     "Planning shapes and systems on paper before coding."
    ]
   ],
   "signs": [
    "Notebooks and sketches that show how the rules were planned.",
    "The artist’s own code, which they can explain line by line.",
    "A curated series with a clear voice, not endless random results.",
    "Plotter drawings with real ink, pen pressure and paper texture.",
    "Small signed editions, each one chosen by the artist."
   ],
   "tools": [
    [
     "Code editor",
     "Where the artist writes the rules of the drawing."
    ],
    [
     "Creative coding language",
     "Tools such as Processing, made for artists."
    ],
    [
     "Pen plotter",
     "Draws the result with real pens on paper."
    ],
    [
     "Archival paper and ink pens",
     "Give plotter drawings depth and lasting quality."
    ],
    [
     "Sketchbook",
     "For planning systems and shapes by hand."
    ],
    [
     "Archive of versions",
     "Keeps every rule change and chosen result."
    ]
   ],
   "time": "Several days to several months for a series",
   "bestFor": "Curious artists who love patterns, systems and mathematics.",
   "artists": [
    [
     "Vera Molnár",
     "Hungarian-French, 1924–2023",
     "A pioneer who explored order and disorder through rules and plotters."
    ],
    [
     "Georg Nees",
     "German, 1926–2016",
     "He showed some of the first computer drawings in 1965."
    ],
    [
     "Frieder Nake",
     "German, born 1938",
     "A pioneer of algorithmic drawing who exhibited in 1965."
    ],
    [
     "Manfred Mohr",
     "German, born 1938",
     "He has explored the cube through his own algorithms for decades."
    ],
    [
     "Casey Reas",
     "American, born 1972",
     "He co-created Processing, a coding language made for artists."
    ]
   ],
   "guide": [
    "Ask the artist to explain their rules and show their sketches.",
    "For plotter works, look closely for real ink and paper texture.",
    "Prefer small, curated, signed editions with clear numbering."
   ]
  },
  "ar": {
   "name": "الفن التوليدي",
   "feel": "فضولي",
   "essence": "فن يكتب فيه الفنان القواعد بيده.",
   "story": [
    "في الفن التوليدي يكتب الفنان مجموعة قواعد، غالباً على شكل شيفرة. القواعد ترسم، والفنان يقرر ما يبقى.",
    "بدأ في الستينيات. كتب غيورغ نيس وفريدر ناكه وفيرا مولنار برامج توجّه أقلام الراسمات على الورق. وكانت مولنار قد جرّبت الرسم بالقواعد يدوياً من قبل.",
    "اليوم يكتب الفنانون شيفرتهم بأنفسهم ويرسمون بالراسمات على ورق فاخر. وكل قاعدة وكل لون وكل نتيجة مختارة تأتي من عقل إنسان."
   ],
   "tech": [
    [
     "شيفرة مكتوبة باليد",
     "يكتب الفنان كل قاعدة في الرسم بنفسه."
    ],
    [
     "العشوائية المضبوطة",
     "مفاجآت صغيرة داخل حدود صارمة يضعها الفنان."
    ],
    [
     "الرسم بالراسمة",
     "قلم متحرك يرسم الشيفرة على ورق حقيقي بحبر حقيقي."
    ],
    [
     "التكرار والدراسة",
     "تشغيل القواعد مرات كثيرة ودراسة كل نتيجة."
    ],
    [
     "الانتقاء",
     "يختار الفنان النتائج القليلة التي تستحق البقاء."
    ],
    [
     "تخطيط القواعد",
     "رسم الأشكال والأنظمة على الورق قبل كتابة الشيفرة."
    ]
   ],
   "signs": [
    "دفاتر واسكتشات تُظهر كيف خُطّطت القواعد.",
    "شيفرة الفنان الخاصة، التي يشرحها سطراً بسطر.",
    "سلسلة منتقاة بصوت واضح، لا نتائج عشوائية بلا نهاية.",
    "رسوم بالراسمة بحبر حقيقي وضغط قلم وملمس ورق.",
    "نسخ موقّعة قليلة، اختار الفنان كل واحدة منها."
   ],
   "tools": [
    [
     "محرر الشيفرة",
     "حيث يكتب الفنان قواعد الرسم."
    ],
    [
     "لغة برمجة إبداعية",
     "أدوات مثل بروسيسنغ، صُممت للفنانين."
    ],
    [
     "الراسمة",
     "ترسم النتيجة بأقلام حقيقية على الورق."
    ],
    [
     "ورق أرشيفي وأقلام حبر",
     "تمنح رسوم الراسمة عمقاً وجودة تدوم."
    ],
    [
     "دفتر اسكتش",
     "لتخطيط الأنظمة والأشكال باليد."
    ],
    [
     "أرشيف النسخ",
     "يحفظ كل تعديل على القواعد وكل نتيجة مختارة."
    ]
   ],
   "time": "من عدة أيام إلى عدة أشهر للسلسلة",
   "bestFor": "للفنانين الفضوليين الذين يحبون النقوش والأنظمة والرياضيات.",
   "artists": [
    [
     "فيرا مولنار",
     "مجرية فرنسية، 1924–2023",
     "رائدة استكشفت النظام والفوضى عبر القواعد والراسمات."
    ],
    [
     "غيورغ نيس",
     "ألماني، 1926–2016",
     "عرض بعضاً من أوائل رسوم الحاسوب عام 1965."
    ],
    [
     "فريدر ناكه",
     "ألماني، مواليد 1938",
     "رائد للرسم الخوارزمي عرض أعماله عام 1965."
    ],
    [
     "مانفريد مور",
     "ألماني، مواليد 1938",
     "يستكشف المكعب بخوارزمياته الخاصة منذ عقود."
    ],
    [
     "كيسي ريس",
     "أمريكي، مواليد 1972",
     "شارك في ابتكار بروسيسنغ، لغة برمجة صُممت للفنانين."
    ]
   ],
   "guide": [
    "اطلب من الفنان أن يشرح قواعده ويعرض اسكتشاته.",
    "في أعمال الراسمة، ابحث عن الحبر الحقيقي وملمس الورق.",
    "فضّل النسخ القليلة المنتقاة الموقّعة بترقيم واضح."
   ]
  }
 },
 {
  "id": "nft_art",
  "cat": "digital",
  "type": "nft_art",
  "level": "intermediate",
  "en": {
   "name": "NFT Art",
   "feel": "Modern",
   "essence": "A digital certificate of ownership for a human-made digital artwork.",
   "story": [
    "An NFT is a digital certificate. It records who owns a digital artwork. The art itself is still made by a human artist.",
    "Kevin McCoy minted the first known NFT artwork, Quantum, in 2014. Years later, digital artists began using certificates to sign and sell their files.",
    "Today an NFT is simply one way to prove ownership. What matters most is still the artwork, the artist and their story."
   ],
   "tech": [
    [
     "Original artwork",
     "The artist first creates the piece by hand, in any digital medium."
    ],
    [
     "Minting",
     "Creating the certificate that links to the artwork."
    ],
    [
     "Editions",
     "Choosing one unique piece or a small numbered set."
    ],
    [
     "Metadata",
     "Writing the title, artist name and details into the certificate."
    ],
    [
     "File storage",
     "Keeping the original file safe and reachable over time."
    ],
    [
     "Clear terms",
     "Stating which rights the buyer receives."
    ]
   ],
   "signs": [
    "The certificate links back to the artist’s own verified profile.",
    "Process files, sketches or recordings that show the work being made.",
    "A long history of work in the same personal style.",
    "A clear statement of who made the work and which rights come with it."
   ],
   "tools": [
    [
     "Digital art tools",
     "Tablet, stylus or camera used to make the artwork."
    ],
    [
     "Digital wallet",
     "Holds the certificates the artist creates or the buyer owns."
    ],
    [
     "Minting platform",
     "Creates and records the certificate."
    ],
    [
     "Long-term file storage",
     "Keeps the original artwork file safe."
    ],
    [
     "Written license",
     "Explains what the buyer may and may not do."
    ]
   ],
   "time": "Same as the artwork itself, plus a short step to mint",
   "bestFor": "Digital artists who want a clear record of ownership for their work.",
   "artists": [
    [
     "Kevin McCoy",
     "American, born 1967",
     "He minted Quantum in 2014, often called the first NFT artwork."
    ],
    [
     "Beeple (Mike Winkelmann)",
     "American, born 1981",
     "He has made and shared a new digital artwork every day since 2007."
    ],
    [
     "Tyler Hobbs",
     "American, born 1987",
     "He writes his own code to make flowing generative drawings and paintings."
    ],
    [
     "Kevin Abosch",
     "Irish, born 1969",
     "A photographer and conceptual artist who explores digital ownership."
    ]
   ],
   "guide": [
    "Confirm the real artist. Check that the certificate comes from their verified account.",
    "Ask where the original file is stored and how you will receive it.",
    "Read the rights carefully. Owning the certificate rarely means owning the copyright."
   ]
  },
  "ar": {
   "name": "فن الرموز غير القابلة للاستبدال",
   "feel": "حديث",
   "essence": "شهادة ملكية رقمية لعمل فني رقمي صنعه إنسان.",
   "story": [
    "الرمز غير القابل للاستبدال شهادة رقمية. يسجّل من يملك العمل الفني الرقمي. والعمل نفسه يصنعه دائماً فنان إنسان.",
    "سكّ كيفن مكوي أول عمل فني معروف من هذا النوع، كوانتم، عام 2014. وبعد سنوات بدأ الفنانون الرقميون يوقّعون ملفاتهم ويبيعونها بشهادات.",
    "اليوم هذه الشهادة مجرد طريقة لإثبات الملكية. ويبقى الأهم هو العمل الفني والفنان وقصته."
   ],
   "tech": [
    [
     "العمل الأصلي",
     "يصنع الفنان القطعة أولاً بيده، بأي وسيط رقمي."
    ],
    [
     "السكّ",
     "إنشاء الشهادة التي ترتبط بالعمل الفني."
    ],
    [
     "النسخ",
     "اختيار قطعة فريدة أو مجموعة صغيرة مرقّمة."
    ],
    [
     "البيانات الوصفية",
     "كتابة العنوان واسم الفنان والتفاصيل في الشهادة."
    ],
    [
     "حفظ الملف",
     "إبقاء الملف الأصلي آمناً ومتاحاً مع الزمن."
    ],
    [
     "شروط واضحة",
     "توضيح الحقوق التي يحصل عليها المشتري."
    ]
   ],
   "signs": [
    "الشهادة مرتبطة بحساب الفنان الموثّق نفسه.",
    "ملفات مراحل أو اسكتشات أو تسجيلات تُظهر صنع العمل.",
    "تاريخ طويل من الأعمال بالأسلوب الشخصي نفسه.",
    "بيان واضح بمن صنع العمل وما الحقوق المرافقة له."
   ],
   "tools": [
    [
     "أدوات الفن الرقمي",
     "لوح أو قلم رقمي أو كاميرا لصنع العمل."
    ],
    [
     "محفظة رقمية",
     "تحفظ الشهادات التي يصدرها الفنان أو يملكها المشتري."
    ],
    [
     "منصة السكّ",
     "تُنشئ الشهادة وتسجّلها."
    ],
    [
     "تخزين طويل الأمد",
     "يحفظ ملف العمل الأصلي بأمان."
    ],
    [
     "ترخيص مكتوب",
     "يشرح ما يحق للمشتري فعله وما لا يحق."
    ]
   ],
   "time": "مدة صنع العمل نفسه، مع خطوة قصيرة للسكّ",
   "bestFor": "للفنانين الرقميين الذين يريدون سجلاً واضحاً لملكية أعمالهم.",
   "artists": [
    [
     "كيفن مكوي",
     "أمريكي، مواليد 1967",
     "سكّ عمل كوانتم عام 2014، ويُعدّ غالباً أول عمل من هذا النوع."
    ],
    [
     "بيبل (مايك وينكلمان)",
     "أمريكي، مواليد 1981",
     "يصنع عملاً رقمياً جديداً ويشاركه كل يوم منذ 2007."
    ],
    [
     "تايلر هوبز",
     "أمريكي، مواليد 1987",
     "يكتب شيفرته بنفسه ليصنع رسوماً ولوحات توليدية منسابة."
    ],
    [
     "كيفن أبوش",
     "إيرلندي، مواليد 1969",
     "مصوّر وفنان مفاهيمي يستكشف الملكية الرقمية."
    ]
   ],
   "guide": [
    "تأكد من الفنان الحقيقي. وتحقق أن الشهادة صادرة من حسابه الموثّق.",
    "اسأل أين يُحفظ الملف الأصلي وكيف ستستلمه.",
    "اقرأ الحقوق بعناية. امتلاك الشهادة نادراً ما يعني امتلاك حقوق النشر."
   ]
  }
 },
 {
  "id": "calligraphy",
  "cat": "heritage",
  "type": "calligraphy",
  "level": "advanced",
  "en": {
   "name": "Arabic Calligraphy",
   "feel": "Rhythmic",
   "essence": "The beloved art of Arabic script, measured and alive.",
   "story": [
    "Arabic calligraphy grew with the Quran. Writing the word beautifully became an act of devotion. Early scribes wrote in the angular Kufic script.",
    "In Abbasid Baghdad, Ibn Muqla set rules of proportion using the rhombic dot. Ibn al-Bawwab and Yaqut al-Musta’simi refined them. Ottoman masters carried the art forward for centuries.",
    "Today the art is taught from master to student, just as before. Calligraphers across the Arab world, Türkiye and beyond keep it alive. The reed pen, the ink and the calligrapher’s breath remain the same."
   ],
   "tech": [
    [
     "Thuluth",
     "A grand, curving script for titles, domes and mosque walls."
    ],
    [
     "Naskh",
     "A clear, balanced script used to copy the Quran and books."
    ],
    [
     "Kufic",
     "The early angular script, strong and architectural."
    ],
    [
     "Diwani",
     "A flowing, tightly packed script from the Ottoman court."
    ],
    [
     "Cutting the qalam",
     "The reed pen is cut at an angle that shapes every stroke."
    ],
    [
     "Rhombic dot",
     "A square dot made by the pen, used to measure each letter."
    ],
    [
     "Mashq",
     "Daily practice of letters under a teacher’s correction."
    ]
   ],
   "signs": [
    "Ink pools slightly where the pen first touches the paper.",
    "Strokes swell and thin smoothly with the angle of the reed pen.",
    "Repeated letters match in proportion but differ in tiny ways.",
    "A faint sheen on hand-polished, sized paper in classical works.",
    "Small corrections scraped away with a blade can show under raking light."
   ],
   "tools": [
    [
     "Reed pen (qalam)",
     "Cut from dried reed, it gives the thick and thin line."
    ],
    [
     "Penknife and cutting block",
     "Used to shape and slit the pen nib."
    ],
    [
     "Soot ink (midad)",
     "A deep black ink that sits richly on the paper."
    ],
    [
     "Silk fibres (liqa)",
     "Placed in the inkwell to hold just enough ink."
    ],
    [
     "Ahar paper",
     "Paper coated and polished so the pen glides and errors lift."
    ],
    [
     "Inkwell (dawat)",
     "Keeps the ink ready and protects it from dust."
    ]
   ],
   "time": "A few hours to several weeks",
   "bestFor": "Collectors who love the beauty of the word and patient, disciplined craft.",
   "artists": [
    [
     "Ibn Muqla",
     "Abbasid Baghdad, 885–940",
     "He set the first rules of letter proportion, still taught today."
    ],
    [
     "Yaqut al-Musta’simi",
     "Abbasid Baghdad, died 1298",
     "He perfected the six classical scripts with a new angled pen cut."
    ],
    [
     "Sheikh Hamdullah",
     "Ottoman, 1436–1520",
     "He shaped the Ottoman style of Naskh and Thuluth for centuries."
    ],
    [
     "Hamid Aytaç",
     "Turkish, 1891–1982",
     "A master of Thuluth who taught a generation of modern calligraphers."
    ],
    [
     "Hashem Muhammad al-Baghdadi",
     "Iraqi, 1917–1973",
     "His teaching books on the rules of the scripts are still used."
    ],
    [
     "Hassan Massoudy",
     "Iraqi, born 1944",
     "He brings bold, expressive letters into contemporary art."
    ]
   ],
   "guide": [
    "Ask about the ijaza. A trained calligrapher earns this licence from a master.",
    "Check the proportions. Letters should follow the classical rules of the script.",
    "Look closely at the ink. Hand strokes show pooling and a living edge."
   ]
  },
  "ar": {
   "name": "الخط العربي",
   "feel": "إيقاعي",
   "essence": "فن الحرف العربي الذي نحبه، موزون ونابض بالحياة.",
   "story": [
    "نما الخط العربي مع القرآن الكريم. وصارت كتابة الكلمة بجمال ضرباً من التعبّد. وكتب النسّاخ الأوائل بالخط الكوفي ذي الزوايا.",
    "في بغداد العباسية وضع ابن مقلة قواعد النِّسب بالنقطة المعيّنة. ثم هذّبها ابن البوّاب وياقوت المستعصمي. وحمل الأساتذة العثمانيون هذا الفن قروناً طويلة.",
    "واليوم ما زال الخط يُتعلّم من الأستاذ إلى التلميذ كما كان. ويحييه خطّاطون في العالم العربي وتركيا وخارجهما. ويبقى القلم القصب والحبر ونَفَس الخطّاط كما كانت."
   ],
   "tech": [
    [
     "الثلث",
     "خط مهيب منحنٍ للعناوين والقباب وجدران المساجد."
    ],
    [
     "النسخ",
     "خط واضح متوازن تُنسخ به المصاحف والكتب."
    ],
    [
     "الكوفي",
     "الخط الأول ذو الزوايا، قوي وكأنه بناء."
    ],
    [
     "الديواني",
     "خط منساب متداخل نشأ في الديوان العثماني."
    ],
    [
     "بري القلم",
     "يُبرى القلم القصب بزاوية تحدد شكل كل ضربة."
    ],
    [
     "النقطة المعيّنة",
     "نقطة مربعة يرسمها القلم وتُقاس بها نِسب كل حرف."
    ],
    [
     "المشق",
     "تمرين يومي على الحروف بتصحيح الأستاذ."
    ]
   ],
   "signs": [
    "يتجمّع الحبر قليلاً حيث لمس القلم الورق أول مرة.",
    "تتدرج الضربة بنعومة بين السميك والرقيق بزاوية القلم.",
    "الحروف المتكررة متطابقة في النِّسب، مع فروق دقيقة جداً.",
    "لمعان خفيف على ورق مصقول مُعدّ يدوياً في الأعمال الكلاسيكية.",
    "قد تظهر تحت الضوء الجانبي آثار تصحيح كُشطت بالسكين."
   ],
   "tools": [
    [
     "القلم القصب",
     "يُبرى من قصب مجفف ويعطي الخط السميك والرقيق."
    ],
    [
     "السكين والمقطّة",
     "لبري سنّ القلم وشقّه."
    ],
    [
     "المداد",
     "حبر أسود عميق يستقر بغنى على الورق."
    ],
    [
     "الليقة",
     "خيوط حرير في الدواة تحفظ قدر الحبر المناسب."
    ],
    [
     "الورق المُقهَّر",
     "ورق مطلي بالآهار ومصقول لينساب القلم وتُمحى الأخطاء."
    ],
    [
     "الدواة",
     "تحفظ الحبر جاهزاً وتحميه من الغبار."
    ]
   ],
   "time": "من بضع ساعات إلى عدة أسابيع",
   "bestFor": "لمن يعشق جمال الكلمة والصنعة الصبورة المنضبطة.",
   "artists": [
    [
     "ابن مقلة",
     "بغداد العباسية، 885–940",
     "وضع أول قواعد لنِسب الحروف، وما زالت تُدرَّس اليوم."
    ],
    [
     "ياقوت المستعصمي",
     "بغداد العباسية، توفي 1298",
     "أتقن الأقلام الستة وابتكر بري القلم بزاوية مائلة."
    ],
    [
     "الشيخ حمد الله الأماسي",
     "عثماني، 1436–1520",
     "رسم الطريقة العثمانية في النسخ والثلث لقرون."
    ],
    [
     "حامد الآمدي",
     "تركي، 1891–1982",
     "أستاذ الثلث الذي علّم جيلاً من الخطّاطين المعاصرين."
    ],
    [
     "هاشم محمد البغدادي",
     "عراقي، 1917–1973",
     "ما زالت كراريسه في قواعد الخط مرجعاً للتعلّم."
    ],
    [
     "حسن المسعود",
     "عراقي، مواليد 1944",
     "يحمل الحرف الجريء المعبّر إلى الفن المعاصر."
    ]
   ],
   "guide": [
    "اسأل عن الإجازة. الخطّاط المتمكّن ينالها من أستاذه.",
    "تحقق من النِّسب. يجب أن تتبع الحروف القواعد الكلاسيكية للخط.",
    "تأمّل الحبر عن قرب. ضربة اليد يتجمّع حبرها وحافتها حيّة."
   ]
  }
 },
 {
  "id": "islamic_geometric",
  "cat": "heritage",
  "type": "islamic_geometric",
  "level": "intermediate",
  "en": {
   "name": "Islamic Geometric Art",
   "feel": "Infinite",
   "essence": "Endless patterns from a circle and a line.",
   "story": [
    "Islamic geometric art begins with a single circle. From it, artists draw stars, polygons and patterns that never end. Many see in it a quiet reflection on unity and order.",
    "Mathematicians like Abu al-Wafa’ al-Buzjani wrote guides for craftsmen. Their ideas shine in the tiles of the Alhambra. They live in the carved wood of Cairo and the domes of Isfahan.",
    "Today artists and teachers still draw these patterns by hand, with compass and ruler. Workshops from Riyadh to London teach the old methods. Each new pattern joins a conversation that is a thousand years old."
   ],
   "tech": [
    [
     "Compass and straightedge",
     "Every pattern grows from circles and straight lines."
    ],
    [
     "Grid construction",
     "A base grid of circles sets where every line will fall."
    ],
    [
     "Fold symmetry",
     "Patterns repeat in 6, 8, 10, 12 or 16 directions."
    ],
    [
     "Girih",
     "Interlacing strapwork lines that form stars and polygons."
    ],
    [
     "Tessellation",
     "Shapes that fit together with no gaps and no overlaps."
    ],
    [
     "Interlace",
     "Bands that weave over and under each other."
    ],
    [
     "Zellige",
     "Hand-cut glazed tiles set into geometric patterns."
    ]
   ],
   "signs": [
    "Faint compass holes at the centres of circles.",
    "Light pencil construction lines still visible under the paint.",
    "Tiny variations between shapes that repeat across the pattern.",
    "Hand-painted colour with a slight brush edge along each line.",
    "Gold leaf or shell gold that catches the light unevenly."
   ],
   "tools": [
    [
     "Compass",
     "Draws the first circle and every circle after it."
    ],
    [
     "Straightedge and ruler",
     "Joins the points into clean lines."
    ],
    [
     "Hard pencil",
     "Draws fine construction lines that can be erased."
    ],
    [
     "Technical pen or fine brush",
     "Inks the final pattern lines."
    ],
    [
     "Gouache or natural pigments",
     "Fills the shapes with flat, rich colour."
    ],
    [
     "Gold leaf or shell gold",
     "Adds light to stars and borders."
    ],
    [
     "Smooth heavy paper",
     "Holds sharp lines without bleeding."
    ]
   ],
   "time": "A few days to several weeks",
   "bestFor": "Collectors who love order, calm and the beauty of mathematics.",
   "artists": [
    [
     "Abu al-Wafa’ al-Buzjani",
     "Persian mathematician, 940–998",
     "He wrote a guide to geometric constructions for craftsmen."
    ],
    [
     "The Nasrid masters of the Alhambra",
     "Andalusian, 13th–14th century",
     "Their tile and stucco patterns are among the finest ever made."
    ],
    [
     "Keith Critchlow",
     "British, 1933–2020",
     "His book Islamic Patterns brought this art to new students worldwide."
    ],
    [
     "Ahmed Moustafa",
     "Egyptian, born 1943",
     "He joins classical geometry and Arabic letters in luminous works."
    ],
    [
     "Monir Shahroudy Farmanfarmaian",
     "Iranian, 1922–2019",
     "She turned geometric patterns into dazzling mirror mosaics."
    ]
   ],
   "guide": [
    "Look closely for construction lines. Hand-drawn patterns show them faintly.",
    "Perfect repetition with no variation at all is likely a print.",
    "Ask the artist to explain the base grid, such as 8-fold or 12-fold."
   ]
  },
  "ar": {
   "name": "الفن الهندسي الإسلامي",
   "feel": "لانهائي",
   "essence": "أنماط لا تنتهي من دائرة وخط.",
   "story": [
    "يبدأ الفن الهندسي الإسلامي بدائرة واحدة. ومنها يرسم الفنان النجوم والمضلّعات وأنماطاً لا تنتهي. ويرى فيه كثيرون تأمّلاً هادئاً في الوحدة والنظام.",
    "كتب علماء رياضيات مثل أبي الوفاء البوزجاني أدلة للحرفيين. وتتألق أفكارهم في بلاط قصر الحمراء. وتحيا في خشب القاهرة المحفور وقباب أصفهان.",
    "واليوم ما زال الفنانون والمعلمون يرسمون هذه الأنماط باليد بالفرجار والمسطرة. وتُعلَّم الطرق القديمة في ورش من الرياض إلى لندن. وكل نمط جديد ينضم إلى حوار عمره ألف عام."
   ],
   "tech": [
    [
     "الفرجار والمسطرة",
     "كل نمط ينمو من دوائر وخطوط مستقيمة."
    ],
    [
     "بناء الشبكة",
     "شبكة أساسية من الدوائر تحدد موضع كل خط."
    ],
    [
     "التناظر الدوراني",
     "تتكرر الأنماط في 6 أو 8 أو 10 أو 12 أو 16 اتجاهاً."
    ],
    [
     "الكره",
     "شرائط متشابكة تشكّل النجوم والمضلّعات."
    ],
    [
     "التبليط",
     "أشكال تتلاءم معاً بلا فراغات ولا تداخل."
    ],
    [
     "الضفر",
     "أشرطة تمر فوق بعضها وتحتها كالنسيج."
    ],
    [
     "الزليج",
     "بلاط مزجج مقطوع باليد يُرصّ في أنماط هندسية."
    ]
   ],
   "signs": [
    "ثقوب فرجار خفيفة في مراكز الدوائر.",
    "خطوط بناء بالقلم الرصاص ما زالت تظهر تحت اللون.",
    "فروق صغيرة بين الأشكال المتكررة في النمط.",
    "لون مرسوم باليد بحافة فرشاة خفيفة على كل خط.",
    "ورق ذهب أو ذهب محلول يلتقط الضوء بشكل متفاوت."
   ],
   "tools": [
    [
     "الفرجار",
     "يرسم الدائرة الأولى وكل دائرة بعدها."
    ],
    [
     "المسطرة",
     "تصل النقاط بخطوط نظيفة."
    ],
    [
     "قلم رصاص صلب",
     "يرسم خطوط بناء رفيعة يمكن محوها."
    ],
    [
     "قلم تحبير أو فرشاة رفيعة",
     "يحبّر خطوط النمط النهائية."
    ],
    [
     "الغواش أو الأصباغ الطبيعية",
     "تملأ الأشكال بلون مسطح غني."
    ],
    [
     "ورق الذهب أو الذهب المحلول",
     "يضيف النور إلى النجوم والإطارات."
    ],
    [
     "ورق ثقيل أملس",
     "يحفظ الخطوط حادة بلا تسرّب."
    ]
   ],
   "time": "من بضعة أيام إلى عدة أسابيع",
   "bestFor": "لمن يحب النظام والسكينة وجمال الرياضيات.",
   "artists": [
    [
     "أبو الوفاء البوزجاني",
     "عالم رياضيات فارسي، 940–998",
     "كتب دليلاً في الإنشاءات الهندسية للحرفيين."
    ],
    [
     "صنّاع قصر الحمراء من بني نصر",
     "أندلسيون، القرنان 13–14",
     "أنماطهم في البلاط والجص من أجمل ما صُنع."
    ],
    [
     "كيث كريتشلو",
     "بريطاني، 1933–2020",
     "قرّب كتابه عن الأنماط الإسلامية هذا الفن لطلاب العالم."
    ],
    [
     "أحمد مصطفى",
     "مصري، مواليد 1943",
     "يجمع الهندسة الكلاسيكية والحرف العربي في أعمال مضيئة."
    ],
    [
     "منير شاهرودي فرمانفرمائيان",
     "إيرانية، 1922–2019",
     "حوّلت الأنماط الهندسية إلى فسيفساء مرايا مبهرة."
    ]
   ],
   "guide": [
    "ابحث عن خطوط البناء عن قرب. الأنماط المرسومة باليد تُظهرها بخفة.",
    "التكرار المثالي بلا أي فرق غالباً طباعة.",
    "اطلب من الفنان شرح الشبكة الأساسية، مثل الثمانية أو الاثني عشرية."
   ]
  }
 },
 {
  "id": "islamic_ornament",
  "cat": "heritage",
  "type": "islamic_ornament",
  "level": "advanced",
  "en": {
   "name": "Islamic Ornamental Art",
   "feel": "Blooming",
   "essence": "Endless vines and blossoms that make every surface sing.",
   "story": [
    "Islamic ornament grew from the vine and the leaf. Artists turned living plants into endless, flowing patterns. Westerners later called it arabesque, and Persian and Turkish masters call it islimi.",
    "Abbasid carvers cut bold patterns into the stucco walls of Samarra. Mamluk Cairo carved them into wood and stone. Ottoman and Persian illuminators painted golden borders called tazhib around sacred texts.",
    "Today illuminators and carvers still learn from masters, often for many years. Workshops in Istanbul, Cairo, Fez and the Gulf keep the craft alive. Each vine still grows by hand, one curl at a time."
   ],
   "tech": [
    [
     "Islimi",
     "Spiralling vines with split leaves that branch without end."
    ],
    [
     "Rumi",
     "Stylised leaf and half-palmette forms used in borders and fills."
    ],
    [
     "Tazhib (illumination)",
     "Gold and colour painted around texts and on page borders."
    ],
    [
     "Stucco carving",
     "Patterns cut into wet or set plaster on walls and arches."
    ],
    [
     "Wood carving and inlay",
     "Vines carved in wood, often joined with bone or mother of pearl."
    ],
    [
     "Pouncing",
     "A pricked paper pattern dusted with charcoal to transfer the design."
    ],
    [
     "Burnishing gold",
     "Gold is polished with a smooth stone until it glows."
    ]
   ],
   "signs": [
    "Mirrored halves of a design that differ by a hair.",
    "Faint pounce dots or pencil lines under the paint.",
    "Burnished gold with soft, uneven reflections, not a flat print shine.",
    "Fine brush lines that taper and swell along each tendril.",
    "Chisel marks or slight depth changes in carved stucco or wood."
   ],
   "tools": [
    [
     "Fine squirrel or cat-hair brushes",
     "Paint hair-thin outlines and tiny petals."
    ],
    [
     "Gold leaf and shell gold",
     "Give illumination its warm, living light."
    ],
    [
     "Agate burnisher",
     "Polishes gold until it shines like metal."
    ],
    [
     "Natural pigments",
     "Lapis blue, malachite green and others mixed with gum."
    ],
    [
     "Pricked paper stencil",
     "Transfers the design evenly across a border."
    ],
    [
     "Carving chisels and gouges",
     "Cut vines and leaves into wood or plaster."
    ],
    [
     "Ahar paper",
     "Polished paper that holds fine detail and gold."
    ]
   ],
   "time": "Several days to many months",
   "bestFor": "Collectors who love rich detail, gold and the patience of master craft.",
   "artists": [
    [
     "The stucco carvers of Samarra",
     "Abbasid Iraq, 9th century",
     "They created a bold, slanted carving style copied across the Islamic world."
    ],
    [
     "The Nasrid stucco masters of the Alhambra",
     "Andalusian, 13th–14th century",
     "Their lace-like plaster walls are among the wonders of ornament."
    ],
    [
     "The Mamluk woodcarvers of Cairo",
     "Egyptian, 13th–16th century",
     "Their minbars and doors join geometry and vines in carved wood."
    ],
    [
     "Kara Memi",
     "Ottoman, active 16th century",
     "He led the court illuminators and brought tulips and carnations into ornament."
    ],
    [
     "Owen Jones",
     "British, 1809–1874",
     "His book The Grammar of Ornament recorded the patterns of the Alhambra."
    ]
   ],
   "guide": [
    "Tilt the work under light. Real burnished gold shifts and glows.",
    "Ask to see the pattern stencil or early drawings. Masters keep them.",
    "Ask who trained the artist. Illumination is usually learned from a master."
   ]
  },
  "ar": {
   "name": "الزخرفة الإسلامية",
   "feel": "مزهر",
   "essence": "أغصان وأزهار لا تنتهي تجعل كل سطح يغنّي.",
   "story": [
    "نشأت الزخرفة الإسلامية من الغصن والورقة. فحوّل الفنانون النبات الحي إلى أنماط منسابة بلا نهاية. وسمّاها الغرب لاحقاً الأرابيسك، ويسمّيها الأساتذة الفرس والأتراك الإسليمي.",
    "حفر الصنّاع العباسيون أنماطاً جريئة في جدران الجص بسامرّاء. ونقشتها القاهرة المملوكية في الخشب والحجر. ورسم المذهّبون العثمانيون والفرس حواشي ذهبية حول النصوص المقدسة تُسمّى التذهيب.",
    "واليوم ما زال المذهّبون والنقّاشون يتعلّمون على أيدي الأساتذة سنوات طويلة. وتحفظ الورش في إسطنبول والقاهرة وفاس والخليج هذه الصنعة. وما زال كل غصن ينمو باليد، التفافة بعد التفافة."
   ],
   "tech": [
    [
     "الإسليمي",
     "أغصان حلزونية بأوراق مشقوقة تتفرع بلا نهاية."
    ],
    [
     "الرومي",
     "أشكال أوراق ونصف مراوح محوّرة للإطارات والحشوات."
    ],
    [
     "التذهيب",
     "ذهب وألوان تُرسم حول النصوص وعلى حواشي الصفحات."
    ],
    [
     "الحفر على الجص",
     "أنماط تُحفر في الجص الطري أو الجاف على الجدران والأقواس."
    ],
    [
     "الحفر والتطعيم في الخشب",
     "أغصان تُحفر في الخشب، وتُطعَّم أحياناً بالعظم أو الصدف."
    ],
    [
     "التثقيب والتنقيط",
     "ورقة نمط مثقوبة يُنثر عليها الفحم لنقل التصميم."
    ],
    [
     "صقل الذهب",
     "يُصقل الذهب بحجر أملس حتى يتوهّج."
    ]
   ],
   "signs": [
    "نصفا التصميم المتقابلان يختلفان بمقدار شعرة.",
    "نقاط تنقيط خفيفة أو خطوط رصاص تحت اللون.",
    "ذهب مصقول بانعكاسات ناعمة متفاوتة، لا لمعان طباعة مسطح.",
    "خطوط فرشاة رفيعة تضيق وتتسع على طول كل غصن.",
    "آثار إزميل أو فروق خفيفة في العمق بالجص أو الخشب المحفور."
   ],
   "tools": [
    [
     "فُرَش رفيعة من الشعر الطبيعي",
     "ترسم خطوطاً بدقة الشعرة وبتلات صغيرة."
    ],
    [
     "ورق الذهب والذهب المحلول",
     "يمنحان التذهيب نوره الدافئ الحي."
    ],
    [
     "مصقلة العقيق",
     "تصقل الذهب حتى يلمع كالمعدن."
    ],
    [
     "الأصباغ الطبيعية",
     "أزرق اللازورد وأخضر الملاخيت وغيرهما ممزوجة بالصمغ."
    ],
    [
     "ورقة النمط المثقوبة",
     "تنقل التصميم بانتظام على طول الإطار."
    ],
    [
     "أزاميل ومناقيش الحفر",
     "تحفر الأغصان والأوراق في الخشب أو الجص."
    ],
    [
     "الورق المُقهَّر",
     "ورق مصقول يحفظ التفاصيل الدقيقة والذهب."
    ]
   ],
   "time": "من عدة أيام إلى أشهر كثيرة",
   "bestFor": "لمن يحب التفاصيل الغنية والذهب وصبر الصنعة المتقنة.",
   "artists": [
    [
     "نقّاشو الجص في سامرّاء",
     "العراق العباسي، القرن التاسع",
     "ابتكروا أسلوب حفر مائلاً جريئاً انتشر في العالم الإسلامي."
    ],
    [
     "أساتذة الجص من بني نصر في قصر الحمراء",
     "أندلسيون، القرنان 13–14",
     "جدرانهم الجصية الشبيهة بالدانتيل من عجائب الزخرفة."
    ],
    [
     "نجّارو القاهرة المملوكية",
     "مصريون، القرون 13–16",
     "منابرهم وأبوابهم تجمع الهندسة والأغصان في الخشب المحفور."
    ],
    [
     "قره ممي",
     "عثماني، عاش في القرن السادس عشر",
     "قاد مذهّبي البلاط وأدخل الخزامى والقرنفل إلى الزخرفة."
    ],
    [
     "أوين جونز",
     "بريطاني، 1809–1874",
     "وثّق كتابه قواعد الزخرفة أنماط قصر الحمراء."
    ]
   ],
   "guide": [
    "أمِل العمل تحت الضوء. الذهب المصقول الحقيقي يتحرك ويتوهّج.",
    "اطلب رؤية ورقة النمط أو الرسوم الأولى. الأساتذة يحتفظون بها.",
    "اسأل عمّن درّب الفنان. التذهيب يُتعلَّم عادة على يد أستاذ."
   ]
  }
 },
 {
  "id": "manuscript",
  "cat": "heritage",
  "type": "manuscript",
  "level": "advanced",
  "en": {
   "name": "Manuscript Art",
   "feel": "Precious",
   "essence": "Books made by hand, where every page is a treasure.",
   "story": [
    "The art of the book grew from love of the Quran. Calligraphers, illuminators and binders worked together on one volume. Quran manuscripts carry no figures, only script, gold and ornament.",
    "Court workshops in Herat, Tabriz, Istanbul and Delhi made poetry and history books. Their miniature paintings often show people, gardens and stories. Marbled paper, called ebru, framed the pages with swirling colour.",
    "Today illuminators, miniature painters and marblers still train with masters. Ebru is on UNESCO’s list of intangible heritage. Contemporary artists also bring the miniature into new and personal work."
   ],
   "tech": [
    [
     "Illumination (tazhib)",
     "Gold and colour framing the text and opening pages."
    ],
    [
     "Miniature painting",
     "Tiny, detailed scenes painted with the finest brushes."
    ],
    [
     "Ebru marbling",
     "Colours floated on thickened water, then lifted onto paper."
    ],
    [
     "Ruling with a mistara",
     "Threads pressed into paper to mark straight writing lines."
    ],
    [
     "Margin cutting (katı‘)",
     "Paper cut and inlaid to make delicate frames."
    ],
    [
     "Leather binding",
     "Tooled and gilded leather covers that protect the pages."
    ]
   ],
   "signs": [
    "Ruled lines pressed lightly into the paper, felt with a fingertip.",
    "Pigment that sits slightly raised, with tiny brush marks under magnification.",
    "Gold that is burnished in some places and matte in others.",
    "Ebru patterns that never repeat exactly, with small drips at the edges.",
    "Hand-made paper with slight thickness changes when held to the light."
   ],
   "tools": [
    [
     "Reed pen (qalam)",
     "Writes the text that the whole page is built around."
    ],
    [
     "Fine brushes",
     "Paint miniatures and illumination down to single hairs."
    ],
    [
     "Natural pigments and gum arabic",
     "Give lasting, jewel-like colours."
    ],
    [
     "Gold leaf and shell gold",
     "Light up borders, headings and verse markers."
    ],
    [
     "Agate burnisher",
     "Polishes gold and paper to a soft shine."
    ],
    [
     "Marbling tray and size",
     "Holds the thickened water for ebru."
    ],
    [
     "Horsehair and rose-stem brushes",
     "Sprinkle colour onto the water for marbling."
    ]
   ],
   "time": "Several weeks to several years for one book",
   "bestFor": "Collectors who treasure books, fine detail and works made for a lifetime.",
   "artists": [
    [
     "Ibn al-Bawwab",
     "Abbasid Baghdad, died 1022",
     "He wrote and illuminated a famous Quran now in Dublin’s Chester Beatty."
    ],
    [
     "Kamal ud-Din Behzad",
     "Persian, about 1450–1535",
     "The most celebrated Persian miniature painter, master of rich composition."
    ],
    [
     "Reza Abbasi",
     "Persian, about 1565–1635",
     "He led the Isfahan school with graceful single-page paintings."
    ],
    [
     "Necmeddin Okyay",
     "Turkish, 1883–1976",
     "A master of ebru who created flower patterns on water."
    ],
    [
     "Shahzia Sikander",
     "Pakistani-American, born 1969",
     "She brings the miniature tradition into bold contemporary art."
    ]
   ],
   "guide": [
    "Ask about the paper, pigments and gold. Masters use traditional materials.",
    "Look with a magnifier. Hand painting shows tiny strokes, never printing dots.",
    "For ebru, ask for the artist’s other pulls. No two are identical."
   ]
  },
  "ar": {
   "name": "فن المخطوطات",
   "feel": "نفيس",
   "essence": "كتب تُصنع باليد، كل صفحة فيها كنز.",
   "story": [
    "نشأ فن الكتاب من محبة القرآن الكريم. وعمل الخطّاط والمذهّب والمجلِّد معاً على مجلد واحد. ولا تحمل المصاحف المخطوطة صوراً، بل خطاً وذهباً وزخرفة.",
    "صنعت ورش البلاط في هراة وتبريز وإسطنبول ودلهي كتب الشعر والتاريخ. وكثيراً ما تُظهر منمنماتها أشخاصاً وحدائق وحكايات. وأحاط ورق الإبرو المجزّع الصفحات بألوان متموّجة.",
    "واليوم ما زال المذهّبون ورسّامو المنمنمات وفنانو الإبرو يتدرّبون على أيدي الأساتذة. والإبرو مدرج في قائمة اليونسكو للتراث غير المادي. ويحمل فنانون معاصرون المنمنمة إلى أعمال جديدة وشخصية."
   ],
   "tech": [
    [
     "التذهيب",
     "ذهب وألوان تؤطّر النص والصفحات الافتتاحية."
    ],
    [
     "المنمنمات",
     "مشاهد صغيرة دقيقة تُرسم بأدق الفُرَش."
    ],
    [
     "الإبرو",
     "ألوان تطفو على ماء مُكثَّف ثم تُرفع على الورق."
    ],
    [
     "التسطير بالمسطرة",
     "خيوط تُضغط في الورق لتحديد سطور الكتابة."
    ],
    [
     "القطّاعي",
     "ورق يُقصّ ويُطعَّم لصنع إطارات رقيقة."
    ],
    [
     "التجليد بالجلد",
     "أغلفة جلدية منقوشة ومذهّبة تحمي الصفحات."
    ]
   ],
   "signs": [
    "سطور مضغوطة بخفة في الورق تُحَسّ بطرف الإصبع.",
    "صبغة بارزة قليلاً تُظهر ضربات فرشاة دقيقة تحت المكبّر.",
    "ذهب مصقول في مواضع وغير لامع في أخرى.",
    "أنماط إبرو لا تتكرر أبداً، مع قطرات صغيرة عند الحواف.",
    "ورق يدوي يتفاوت سمكه قليلاً حين تضعه أمام الضوء."
   ],
   "tools": [
    [
     "القلم القصب",
     "يكتب النص الذي تُبنى حوله الصفحة كلها."
    ],
    [
     "فُرَش رفيعة",
     "ترسم المنمنمات والتذهيب حتى بشعرة واحدة."
    ],
    [
     "الأصباغ الطبيعية والصمغ العربي",
     "تمنح ألواناً باقية كالجواهر."
    ],
    [
     "ورق الذهب والذهب المحلول",
     "يضيئان الإطارات والعناوين وفواصل الآيات."
    ],
    [
     "مصقلة العقيق",
     "تصقل الذهب والورق إلى لمعان ناعم."
    ],
    [
     "حوض الإبرو والماء المكثَّف",
     "يحمل الماء المُكثَّف لفن الإبرو."
    ],
    [
     "فُرَش شعر الخيل وعيدان الورد",
     "تنثر اللون على الماء في الإبرو."
    ]
   ],
   "time": "من عدة أسابيع إلى عدة سنوات للكتاب الواحد",
   "bestFor": "لمن يعتز بالكتب والتفاصيل الدقيقة وأعمال تبقى العمر كله.",
   "artists": [
    [
     "ابن البوّاب",
     "بغداد العباسية، توفي 1022",
     "كتب وذهّب مصحفاً شهيراً محفوظاً في مكتبة تشستر بيتي بدبلن."
    ],
    [
     "كمال الدين بهزاد",
     "فارسي، نحو 1450–1535",
     "أشهر رسّامي المنمنمات الفارسية، وأستاذ التكوين الغني."
    ],
    [
     "رضا عباسي",
     "فارسي، نحو 1565–1635",
     "قاد مدرسة أصفهان بلوحات منفردة رشيقة."
    ],
    [
     "نجم الدين أوقياي",
     "تركي، 1883–1976",
     "أستاذ في الإبرو ابتكر أنماط الأزهار على الماء."
    ],
    [
     "شازيا سيكاندر",
     "باكستانية أمريكية، مواليد 1969",
     "تحمل تقليد المنمنمات إلى فن معاصر جريء."
    ]
   ],
   "guide": [
    "اسأل عن الورق والأصباغ والذهب. الأساتذة يستخدمون المواد التقليدية.",
    "انظر بمكبّر. الرسم اليدوي يُظهر ضربات صغيرة، لا نقاط طباعة.",
    "في الإبرو، اطلب رؤية أوراق أخرى للفنان. لا تتطابق ورقتان أبداً."
   ]
  }
 },
 {
  "id": "egyptian_linen",
  "cat": "heritage",
  "type": "egyptian_linen",
  "level": "intermediate",
  "en": {
   "name": "Egyptian Linen Art",
   "feel": "Timeless",
   "essence": "Thousands of years of Egyptian weaving, still made by hand.",
   "story": [
    "Ancient Egyptians spun flax into linen of amazing fineness. It clothed the living and wrapped the dead. Some royal linen was so fine that light passed through it.",
    "Later, Coptic weavers wove lively tapestry bands of wool and linen. In Islamic Egypt, tiraz workshops wove linen with inscribed bands. Cairo’s tentmakers began stitching bright appliqué for tents and gatherings.",
    "Today the tradition lives on with pride. Since the 1950s, the weavers of Harrania have made free, hand-woven tapestries. In Cairo’s Street of the Tentmakers, khayamiya masters still stitch every piece by hand."
   ],
   "tech": [
    [
     "Flax spinning",
     "Flax fibres are twisted into strong, fine linen thread."
    ],
    [
     "Plain weave linen",
     "Simple over-under weaving that makes smooth, strong cloth."
    ],
    [
     "Tapestry weaving",
     "Coloured weft threads build the image as the cloth grows."
    ],
    [
     "Tiraz bands",
     "Inscribed bands woven or embroidered into fine fabric."
    ],
    [
     "Weaving without a cartoon",
     "Harrania weavers compose freely on the loom, with no drawing."
    ],
    [
     "Khayamiya appliqué",
     "Coloured cotton pieces stitched by hand onto a heavy backing."
    ],
    [
     "Natural dyeing",
     "Colours from plants such as madder, indigo and weld."
    ]
   ],
   "signs": [
    "Slight changes in thread thickness, typical of hand spinning.",
    "Tapestry colours that meet in small slits or stepped edges.",
    "In khayamiya, tiny even stitches visible around every shape.",
    "Natural dyes with gentle shifts of tone across the piece.",
    "A small irregular edge where the weaver turned the threads."
   ],
   "tools": [
    [
     "Flax",
     "The plant that gives linen its cool, strong fibre."
    ],
    [
     "Spindle",
     "Twists fibres into thread by hand."
    ],
    [
     "Upright tapestry loom",
     "Holds the warp while the weaver builds the image."
    ],
    [
     "Wool and linen yarns",
     "The threads of tapestry and fine cloth."
    ],
    [
     "Natural dye plants",
     "Give warm reds, deep blues and soft yellows."
    ],
    [
     "Needle and thimble",
     "The khayamiya maker’s main tools for appliqué."
    ],
    [
     "Heavy cotton backing",
     "Holds the appliqué shapes firm and flat."
    ]
   ],
   "time": "Several days to many months",
   "bestFor": "Collectors who love Egyptian heritage, natural fibres and slow, honest craft.",
   "artists": [
    [
     "The linen weavers of ancient Egypt",
     "Egyptian, about 3000 BC onward",
     "Their fine linen set a standard admired across the ancient world."
    ],
    [
     "The Coptic tapestry weavers",
     "Egyptian, about 3rd–7th century",
     "They wove bright tapestry bands and roundels for clothing and hangings."
    ],
    [
     "The tiraz workshops of Egypt",
     "Fatimid Egypt, 10th–12th century",
     "They wove fine linen with inscribed bands for the court."
    ],
    [
     "Ramses Wissa Wassef",
     "Egyptian, 1911–1974",
     "He founded the Harrania art centre, where village children learned to weave freely."
    ],
    [
     "The weavers of Harrania",
     "Egyptian, 1950s onward",
     "They weave nature and village life from their own imagination."
    ],
    [
     "The khayamiya masters of Cairo",
     "Egyptian, living tradition",
     "They hand-stitch appliqué near Bab Zuwayla, passing skills through families."
    ]
   ],
   "guide": [
    "Turn the piece over. Handwork shows knots, stitches and loose ends.",
    "Ask where it was made and by whom. Workshops know their makers.",
    "Real khayamiya is stitched by hand. Printed fabric copies have no stitches."
   ]
  },
  "ar": {
   "name": "فن الكتان المصري",
   "feel": "خالد",
   "essence": "آلاف السنين من النسيج المصري، ما زال يُصنع باليد.",
   "story": [
    "غزل المصريون القدماء الكتان حتى صار نسيجاً مدهش الرقة. فكسا الأحياء ولُفّ به الراحلون. وبعض الكتان الملكي كان رقيقاً يعبره الضوء.",
    "ثم نسج النسّاجون الأقباط أشرطة زاهية من الصوف والكتان. وفي مصر الإسلامية نسجت دُور الطراز كتاناً بأشرطة مكتوبة. وبدأ صنّاع الخيام في القاهرة يخيطون الزخارف الملوّنة للخيام والمناسبات.",
    "واليوم يعيش هذا التقليد بفخر. فمنذ خمسينيات القرن الماضي ينسج أهل الحرانية سجاداً حراً باليد. وفي شارع الخيامية بالقاهرة ما زال الأساتذة يخيطون كل قطعة باليد."
   ],
   "tech": [
    [
     "غزل الكتان",
     "تُبرم ألياف الكتان خيوطاً قوية ورقيقة."
    ],
    [
     "النسيج السادة",
     "نسج بسيط فوق وتحت يصنع قماشاً ناعماً متيناً."
    ],
    [
     "النسيج المرسوم",
     "خيوط اللُّحمة الملونة تبني الصورة مع نمو القماش."
    ],
    [
     "أشرطة الطراز",
     "أشرطة مكتوبة تُنسج أو تُطرّز في القماش الرقيق."
    ],
    [
     "النسج بلا رسم مسبق",
     "نسّاجو الحرانية يؤلّفون بحرية على النول دون رسم."
    ],
    [
     "خياطة الخيامية",
     "قطع قطن ملونة تُخاط باليد على بطانة سميكة."
    ],
    [
     "الصباغة الطبيعية",
     "ألوان من نباتات مثل الفوّة والنيلة والبليحاء."
    ]
   ],
   "signs": [
    "فروق خفيفة في سمك الخيط، وهي سمة الغزل اليدوي.",
    "ألوان تلتقي بفتحات صغيرة أو حواف متدرجة في النسيج المرسوم.",
    "في الخيامية، غرز صغيرة منتظمة حول كل شكل.",
    "أصباغ طبيعية تتغير درجاتها بلطف عبر القطعة.",
    "حافة غير منتظمة قليلاً حيث أدار النسّاج الخيوط."
   ],
   "tools": [
    [
     "الكتان",
     "النبات الذي يمنح القماش أليافه الباردة القوية."
    ],
    [
     "المغزل",
     "يبرم الألياف خيوطاً باليد."
    ],
    [
     "النول القائم",
     "يشدّ السدى بينما يبني النسّاج الصورة."
    ],
    [
     "خيوط الصوف والكتان",
     "خيوط النسيج المرسوم والقماش الرقيق."
    ],
    [
     "نباتات الصباغة الطبيعية",
     "تعطي أحمر دافئاً وأزرق عميقاً وأصفر ناعماً."
    ],
    [
     "الإبرة والكشتبان",
     "أداتا صانع الخيامية الأساسيتان."
    ],
    [
     "بطانة قطنية سميكة",
     "تثبّت قطع الزخرفة مسطحة ومشدودة."
    ]
   ],
   "time": "من عدة أيام إلى أشهر كثيرة",
   "bestFor": "لمن يحب التراث المصري والألياف الطبيعية والصنعة الهادئة الصادقة.",
   "artists": [
    [
     "نسّاجو الكتان في مصر القديمة",
     "مصريون، منذ نحو 3000 قبل الميلاد",
     "صار كتانهم الرقيق معياراً أعجب به العالم القديم."
    ],
    [
     "نسّاجو النسيج القبطي",
     "مصريون، نحو القرون 3–7",
     "نسجوا أشرطة ودوائر زاهية للثياب والستائر."
    ],
    [
     "دُور الطراز في مصر",
     "مصر الفاطمية، القرون 10–12",
     "نسجت كتاناً رقيقاً بأشرطة مكتوبة للبلاط."
    ],
    [
     "رمسيس ويصا واصف",
     "مصري، 1911–1974",
     "أسس مركز الحرانية الفني، حيث تعلّم أطفال القرية النسج بحرية."
    ],
    [
     "نسّاجو الحرانية",
     "مصريون، منذ الخمسينيات",
     "ينسجون الطبيعة وحياة القرية من خيالهم."
    ],
    [
     "أساتذة الخيامية في القاهرة",
     "مصريون، تقليد حي",
     "يخيطون الزخارف باليد قرب باب زويلة، وتتوارثها العائلات."
    ]
   ],
   "guide": [
    "اقلب القطعة. العمل اليدوي يُظهر العُقد والغرز والأطراف.",
    "اسأل أين صُنعت ومن صنعها. الورش تعرف صنّاعها.",
    "الخيامية الحقيقية مخيطة باليد. نسخ القماش المطبوع بلا غرز."
   ]
  }
 },
 {
  "id": "textile",
  "cat": "heritage",
  "type": "textile",
  "level": "intermediate",
  "en": {
   "name": "Textile Art",
   "feel": "Warm",
   "essence": "Thread, wool and patience woven into living stories.",
   "story": [
    "Across Arabia, Bedouin women wove Sadu on simple ground looms. They used wool from their own sheep, camels and goats. Their tents, rugs and saddle bags carried bold geometric bands.",
    "Persian and Turkish carpet weavers tied millions of knots by hand. Palestinian women stitched tatreez on their dresses, each village with its own motifs. Every thread held memory, place and family.",
    "Today Sadu weaving is on UNESCO’s list of intangible heritage. Saudi, Kuwaiti and Emirati weavers teach it to new generations. Textile artists worldwide also bring thread into galleries as fine art."
   ],
   "tech": [
    [
     "Sadu weaving",
     "Warp-faced weaving on a ground loom, with bold geometric bands."
    ],
    [
     "Hand-knotting",
     "Each knot tied around warp threads to build a carpet’s pile."
    ],
    [
     "Symmetric and asymmetric knots",
     "Turkish and Persian knots that shape the carpet’s detail."
    ],
    [
     "Tatreez cross-stitch",
     "Tiny crossed stitches counted on the fabric to form motifs."
    ],
    [
     "Hand-spinning",
     "Wool twisted into yarn with a drop spindle."
    ],
    [
     "Natural dyeing",
     "Colours from madder, indigo, henna and other plants."
    ],
    [
     "Tapestry weaving",
     "Coloured wefts woven in to build images and shapes."
    ]
   ],
   "signs": [
    "Small changes in yarn thickness and colour across the piece.",
    "On a carpet back, knots are clear and slightly uneven.",
    "Fringes that are the warp threads themselves, not sewn on.",
    "Edges and patterns that are not perfectly straight or identical.",
    "On embroidery, the back shows knots, crossings and real thread ends."
   ],
   "tools": [
    [
     "Ground loom",
     "A low loom pegged to the earth, used for Sadu."
    ],
    [
     "Upright carpet loom",
     "Holds warp threads tight for knotting."
    ],
    [
     "Drop spindle",
     "Spins wool into yarn by hand."
    ],
    [
     "Beater comb",
     "Packs each row of weft or knots firmly."
    ],
    [
     "Gazelle horn or pick",
     "Used in Sadu to lift and beat the threads."
    ],
    [
     "Embroidery needle and hoop",
     "Hold fabric taut for even stitches."
    ],
    [
     "Wool, cotton and silk yarns",
     "The threads of every weave and stitch."
    ]
   ],
   "time": "Several days to many months",
   "bestFor": "Collectors who love warmth, texture and heritage they can live with daily.",
   "artists": [
    [
     "The Bedouin Sadu weavers",
     "Arabian Peninsula, living tradition",
     "Women weavers whose skill is now honoured as world heritage."
    ],
    [
     "The Palestinian tatreez embroiderers",
     "Palestinian, living tradition",
     "Women who stitch village motifs and family memory into cloth."
    ],
    [
     "Maqsud Kashani",
     "Persian, active 16th century",
     "His name is woven into the famous Ardabil Carpet of 1539–40."
    ],
    [
     "Anni Albers",
     "German-American, 1899–1994",
     "She made weaving a modern art and wrote the book On Weaving."
    ],
    [
     "Sheila Hicks",
     "American, born 1934",
     "She creates large, sculptural works in fibre and colour."
    ]
   ],
   "guide": [
    "Turn the piece over. Handmade carpets and embroidery show the pattern on the back.",
    "Ask about the wool and dyes. Makers know where their materials came from.",
    "Count the knots in one square. Ask the seller to explain the density."
   ]
  },
  "ar": {
   "name": "فن النسيج",
   "feel": "دافئ",
   "essence": "خيط وصوف وصبر يُنسج حكايات حيّة.",
   "story": [
    "نسجت البدويات في الجزيرة العربية السدو على أنوال أرضية بسيطة. واستخدمن صوف أغنامهن ووبر إبلهن وشعر ماعزهن. فحملت بيوت الشعر والبُسط والخروج أشرطة هندسية جريئة.",
    "وعقد نسّاجو السجاد الفرس والأتراك ملايين العُقد باليد. وطرّزت الفلسطينيات التطريز على أثوابهن، ولكل قرية رموزها. فحمل كل خيط ذاكرة ومكاناً وعائلة.",
    "واليوم السدو مدرج في قائمة اليونسكو للتراث غير المادي. وتعلّمه نسّاجات سعوديات وكويتيات وإماراتيات للأجيال الجديدة. ويحمل فنانو النسيج حول العالم الخيط إلى المعارض فناً رفيعاً."
   ],
   "tech": [
    [
     "نسج السدو",
     "نسج تبرز فيه خيوط السدى على نول أرضي بأشرطة هندسية."
    ],
    [
     "العقد اليدوي",
     "كل عقدة تُربط حول خيوط السدى لبناء وبر السجادة."
    ],
    [
     "العقدة المتناظرة وغير المتناظرة",
     "العقدتان التركية والفارسية اللتان تحددان دقة السجادة."
    ],
    [
     "التطريز الفلسطيني",
     "غرز متصالبة صغيرة تُعدّ على القماش لتشكّل الرموز."
    ],
    [
     "الغزل اليدوي",
     "يُبرم الصوف خيطاً بالمغزل."
    ],
    [
     "الصباغة الطبيعية",
     "ألوان من الفوّة والنيلة والحنّاء ونباتات أخرى."
    ],
    [
     "النسيج المرسوم",
     "لُحمات ملونة تُنسج لبناء الصور والأشكال."
    ]
   ],
   "signs": [
    "فروق صغيرة في سمك الخيط ولونه عبر القطعة.",
    "على ظهر السجادة تظهر العُقد واضحة وغير متساوية قليلاً.",
    "الأهداب هي خيوط السدى نفسها، لا مخيطة لاحقاً.",
    "حواف وأنماط غير مستقيمة أو متطابقة تماماً.",
    "في التطريز يُظهر الظهر عُقداً وتقاطعات وأطراف خيوط حقيقية."
   ],
   "tools": [
    [
     "النول الأرضي",
     "نول منخفض يُثبَّت في الأرض، يُنسج عليه السدو."
    ],
    [
     "نول السجاد القائم",
     "يشدّ خيوط السدى لعقد السجادة."
    ],
    [
     "المغزل",
     "يغزل الصوف خيطاً باليد."
    ],
    [
     "مشط الدق",
     "يرصّ كل صف من اللُّحمة أو العُقد بإحكام."
    ],
    [
     "قرن الغزال أو المِنشزة",
     "يُستخدم في السدو لرفع الخيوط ودقّها."
    ],
    [
     "إبرة التطريز والطارة",
     "تشدّان القماش لغرز منتظمة."
    ],
    [
     "خيوط الصوف والقطن والحرير",
     "خيوط كل نسج وكل غرزة."
    ]
   ],
   "time": "من عدة أيام إلى أشهر كثيرة",
   "bestFor": "لمن يحب الدفء والملمس وتراثاً يعيش معه كل يوم.",
   "artists": [
    [
     "نسّاجات السدو البدويات",
     "الجزيرة العربية، تقليد حي",
     "نسّاجات تُكرَّم مهارتهن اليوم تراثاً عالمياً."
    ],
    [
     "مطرّزات التطريز الفلسطيني",
     "فلسطينيات، تقليد حي",
     "نساء يطرّزن رموز القرية وذاكرة العائلة على القماش."
    ],
    [
     "مقصود الكاشاني",
     "فارسي، عاش في القرن السادس عشر",
     "اسمه منسوج في سجادة أردبيل الشهيرة من 1539–1540."
    ],
    [
     "آني ألبرز",
     "ألمانية أمريكية، 1899–1994",
     "جعلت النسج فناً حديثاً وكتبت كتاب عن النسج."
    ],
    [
     "شيلا هيكس",
     "أمريكية، مواليد 1934",
     "تصنع أعمالاً نحتية كبيرة من الألياف واللون."
    ]
   ],
   "guide": [
    "اقلب القطعة. السجاد والتطريز اليدوي يُظهران النمط على الظهر.",
    "اسأل عن الصوف والأصباغ. الصانع يعرف مصدر مواده.",
    "عُدّ العُقد في مربع واحد. واطلب من البائع شرح الكثافة."
   ]
  }
 },
 {
  "id": "islamic_mosaic",
  "cat": "heritage",
  "type": "islamic_mosaic",
  "level": "advanced",
  "en": {
   "name": "Islamic Mosaic",
   "feel": "Radiant",
   "essence": "Thousands of hand-cut pieces that turn walls into light.",
   "story": [
    "Early Islamic builders covered walls with glass and gold mosaic. The Dome of the Rock in Jerusalem shines with vines and jewels. The Great Mosque of Damascus shows trees, rivers and palaces, with no people.",
    "Later, Moroccan masters cut glazed tiles into zellige stars. Persian craftsmen fitted coloured tile pieces into domes and portals. The mosques of Tabriz, Isfahan and Samarkand still glow with them.",
    "Today zellige masters in Fez still cut every piece by hand. Tile makers in Iran keep the old patterns alive. New artists bring mosaic into homes, galleries and public spaces worldwide."
   ],
   "tech": [
    [
     "Glass tesserae",
     "Small cubes of coloured glass set into plaster."
    ],
    [
     "Gold-leaf tesserae",
     "Gold sealed between glass layers for a glowing surface."
    ],
    [
     "Zellige cutting",
     "Glazed tiles chipped by hand into exact small shapes."
    ],
    [
     "Face-down setting",
     "Zellige pieces laid upside down, then backed with mortar."
    ],
    [
     "Tile mosaic (mo’arraq)",
     "Pieces of single-colour glazed tile fitted into patterns."
    ],
    [
     "Angled setting",
     "Tesserae tilted slightly so they catch light from many angles."
    ]
   ],
   "signs": [
    "Each piece differs slightly in size, edge and glaze.",
    "Small, uneven gaps of grout between the pieces.",
    "Glaze colour that shifts from tile to tile in the same area.",
    "Chipped back edges on zellige, sloped by the cutter’s hammer.",
    "A surface that sparkles unevenly as you move past it."
   ],
   "tools": [
    [
     "Glazed terracotta tiles",
     "The raw material for zellige and tile mosaic."
    ],
    [
     "Coloured and gold glass",
     "Cut into tesserae for wall mosaics."
    ],
    [
     "Sharpened hammer (menqach)",
     "Cuts zellige pieces with precise strikes."
    ],
    [
     "Paper template",
     "Guides each shape before it is cut."
    ],
    [
     "Lime plaster or mortar",
     "Holds every piece in place for centuries."
    ],
    [
     "Nippers and hardie",
     "Split glass and stone into small cubes."
    ]
   ],
   "time": "Several weeks to several months",
   "bestFor": "Collectors who love light, colour and art that becomes part of a space.",
   "artists": [
    [
     "The Umayyad mosaicists of the Dome of the Rock",
     "Jerusalem, completed 691–692",
     "They created shining gold and green vine mosaics with no figures."
    ],
    [
     "The Umayyad mosaicists of Damascus",
     "Damascus, 705–715",
     "Their golden landscapes in the Great Mosque are a wonder of early Islam."
    ],
    [
     "The mosaicists of the Córdoba mihrab",
     "Andalusian, 10th century",
     "They set gold mosaic around the mihrab of the Great Mosque."
    ],
    [
     "The tile mosaic masters of Tabriz",
     "Persian, 15th century",
     "Their Blue Mosque of 1465 is famed for its deep blue tiles."
    ],
    [
     "The zellige maalems of Fez",
     "Moroccan, living tradition",
     "Master cutters who pass the craft from father to son."
    ],
    [
     "Monir Shahroudy Farmanfarmaian",
     "Iranian, 1922–2019",
     "She revived mirror mosaic as glittering contemporary art."
    ]
   ],
   "guide": [
    "Look for small differences between pieces. Hand cutting is never identical.",
    "Ask whether the tiles are hand-cut zellige or printed tile sheets.",
    "Ask about the backing and mortar. Good work is built to last."
   ]
  },
  "ar": {
   "name": "الفسيفساء الإسلامية",
   "feel": "متألق",
   "essence": "آلاف القطع المقصوصة باليد تحوّل الجدران نوراً.",
   "story": [
    "غطّى البنّاؤون المسلمون الأوائل الجدران بفسيفساء الزجاج والذهب. فتتلألأ قبة الصخرة في القدس بالأغصان والجواهر. ويُظهر الجامع الأموي بدمشق أشجاراً وأنهاراً وقصوراً، بلا بشر.",
    "ثم قصّ الأساتذة المغاربة البلاط المزجج نجوماً من الزليج. ورصّ الحرفيون الفرس قطع البلاط الملوّن في القباب والبوابات. وما زالت مساجد تبريز وأصفهان وسمرقند تتوهّج بها.",
    "واليوم ما زال معلّمو الزليج في فاس يقصّون كل قطعة باليد. ويحفظ صنّاع البلاط في إيران الأنماط القديمة. ويحمل فنانون جدد الفسيفساء إلى البيوت والمعارض والأماكن العامة حول العالم."
   ],
   "tech": [
    [
     "قطع الزجاج",
     "مكعبات صغيرة من الزجاج الملون تُثبَّت في الجص."
    ],
    [
     "قطع الذهب",
     "ذهب محفوظ بين طبقتي زجاج لسطح متوهّج."
    ],
    [
     "قصّ الزليج",
     "بلاط مزجج يُنحت باليد أشكالاً صغيرة دقيقة."
    ],
    [
     "التركيب المقلوب",
     "تُرصّ قطع الزليج مقلوبة ثم تُصبّ عليها الملاط."
    ],
    [
     "فسيفساء البلاط (المعرّق)",
     "قطع بلاط مزجج بلون واحد تُرصّ في أنماط."
    ],
    [
     "التركيب المائل",
     "تُمال القطع قليلاً لتلتقط الضوء من زوايا كثيرة."
    ]
   ],
   "signs": [
    "كل قطعة تختلف قليلاً في الحجم والحافة والتزجيج.",
    "فراغات ملاط صغيرة غير متساوية بين القطع.",
    "لون تزجيج يتغير من قطعة إلى أخرى في المنطقة نفسها.",
    "حواف خلفية مشطوفة في الزليج بضربات مطرقة القاطع.",
    "سطح يتلألأ بتفاوت كلما تحركت أمامه."
   ],
   "tools": [
    [
     "البلاط الفخاري المزجج",
     "المادة الأولى للزليج وفسيفساء البلاط."
    ],
    [
     "الزجاج الملون والمذهّب",
     "يُقصّ قطعاً صغيرة لفسيفساء الجدران."
    ],
    [
     "المنقاش",
     "مطرقة حادة تقصّ قطع الزليج بضربات دقيقة."
    ],
    [
     "قالب ورقي",
     "يرشد كل شكل قبل قصّه."
    ],
    [
     "الجص الجيري أو الملاط",
     "يثبّت كل قطعة في مكانها قروناً."
    ],
    [
     "القطّاعة والسندان",
     "تقسمان الزجاج والحجر مكعبات صغيرة."
    ]
   ],
   "time": "من عدة أسابيع إلى عدة أشهر",
   "bestFor": "لمن يحب النور واللون وفناً يصبح جزءاً من المكان.",
   "artists": [
    [
     "فنانو فسيفساء قبة الصخرة الأمويون",
     "القدس، اكتملت 691–692",
     "صنعوا أغصاناً متلألئة بالذهب والأخضر بلا صور كائنات."
    ],
    [
     "فنانو فسيفساء دمشق الأمويون",
     "دمشق، 705–715",
     "مناظرهم الذهبية في الجامع الأموي من عجائب الإسلام المبكر."
    ],
    [
     "فنانو فسيفساء محراب قرطبة",
     "أندلسيون، القرن العاشر",
     "رصّعوا محراب المسجد الجامع بفسيفساء الذهب."
    ],
    [
     "أساتذة فسيفساء البلاط في تبريز",
     "فرس، القرن الخامس عشر",
     "مسجدهم الأزرق من عام 1465 شهير ببلاطه الأزرق العميق."
    ],
    [
     "معلّمو الزليج في فاس",
     "مغاربة، تقليد حي",
     "أساتذة قصّ يتوارثون الصنعة من الأب إلى الابن."
    ],
    [
     "منير شاهرودي فرمانفرمائيان",
     "إيرانية، 1922–2019",
     "أحيت فسيفساء المرايا فناً معاصراً متلألئاً."
    ]
   ],
   "guide": [
    "ابحث عن فروق صغيرة بين القطع. القصّ اليدوي لا يتطابق أبداً.",
    "اسأل هل البلاط زليج مقصوص باليد أم ألواح بلاط مطبوعة.",
    "اسأل عن البطانة والملاط. العمل الجيد يُبنى ليدوم."
   ]
  }
 },
 {
  "id": "street_art",
  "cat": "contemporary",
  "type": "street_art",
  "level": "intermediate",
  "en": {
   "name": "Street Art",
   "feel": "Fearless",
   "essence": "Art made on walls and city streets, for everyone who walks by.",
   "story": [
    "Street art grew from the graffiti of New York in the 1970s. Young artists painted trains and walls with their names and dreams.",
    "Keith Haring drew in subway stations with white chalk. Jean-Michel Basquiat wrote poetic street tags before galleries found him. Shepard Fairey and Banksy carried posters and stencils around the world.",
    "Today eL Seed paints Arabic calligraphy across buildings, from Tunisia to Cairo. Murals now brighten cities across the Gulf and beyond. Street art speaks to people who might never enter a gallery."
   ],
   "tech": [
    [
     "Spray paint",
     "Aerosol color for fast lines and soft fades."
    ],
    [
     "Cap switching",
     "Changing nozzles for thin lines or wide clouds of color."
    ],
    [
     "Stencilling",
     "Cut templates for sharp, repeatable images."
    ],
    [
     "Wheatpaste",
     "Paper posters glued to walls with flour paste."
    ],
    [
     "Muralism",
     "Large planned paintings that cover whole walls."
    ],
    [
     "Calligraffiti",
     "Calligraphy and graffiti joined in one flowing gesture."
    ]
   ],
   "signs": [
    "Overspray: a soft mist of color around the edges of lines.",
    "Drips that follow gravity where the paint was heavy.",
    "Hand-cut stencils with small, irregular curves and tiny bleeds.",
    "On canvas, a spray texture you can see at an angle.",
    "Color layers that overlap slightly out of line."
   ],
   "tools": [
    [
     "Spray cans",
     "Fast, bright color that sticks to almost any surface."
    ],
    [
     "Interchangeable caps",
     "Nozzles that make lines thin, wide, sharp or soft."
    ],
    [
     "Stencil card and craft knife",
     "For cutting crisp shapes by hand."
    ],
    [
     "Wheatpaste and brush",
     "Flour and water glue for paper posters."
    ],
    [
     "Rollers and poles",
     "To cover big walls quickly and reach high places."
    ],
    [
     "Respirator mask",
     "Protects the artist’s lungs from paint fumes."
    ]
   ],
   "time": "A few hours to several days",
   "bestFor": "Collectors who love bold color, city energy and art with a voice.",
   "artists": [
    [
     "Keith Haring",
     "American, 1958–1990",
     "His chalk drawings in New York subways made art feel open to all."
    ],
    [
     "Jean-Michel Basquiat",
     "American, 1960–1988",
     "He carried the raw energy of street writing into painting."
    ],
    [
     "Shepard Fairey",
     "American, born 1970",
     "His sticker and poster campaigns spread from skate shops to walls worldwide."
    ],
    [
     "Banksy",
     "British, identity unknown",
     "His sharp, witty stencils made street art famous everywhere."
    ],
    [
     "eL Seed",
     "French-Tunisian, born 1981",
     "He paints Arabic calligraphy across walls, rooftops and whole neighborhoods."
    ]
   ],
   "guide": [
    "For studio pieces, ask for a certificate from the artist or their official studio.",
    "Look at the surface at an angle. Real spray shows overspray and drips.",
    "Be careful with famous names. Fakes are common, so buy from trusted sources."
   ]
  },
  "ar": {
   "name": "فن الشارع",
   "feel": "جسور",
   "essence": "فن يُرسم على جدران المدينة، لكل من يمرّ به.",
   "story": [
    "نشأ فن الشارع من كتابات الجدران في نيويورك في السبعينيات. رسم فنانون شباب القطارات والجدران بأسمائهم وأحلامهم.",
    "رسم كيث هارينغ في محطات المترو بالطباشير الأبيض. وكتب جان ميشيل باسكيات عبارات شعرية على الجدران قبل أن تكتشفه المعارض. وحمل شيبرد فيري وبانكسي الملصقات والاستنسل إلى العالم كله.",
    "واليوم يرسم إل سيد الخط العربي على المباني، من تونس إلى القاهرة. وتزيّن الجداريات اليوم مدن الخليج وما بعدها. ففن الشارع يخاطب أناساً قد لا يدخلون معرضاً أبداً."
   ],
   "tech": [
    [
     "رذاذ الطلاء",
     "لون بالبخاخ لخطوط سريعة وتدرّجات ناعمة."
    ],
    [
     "تبديل الفوهات",
     "تغيير رأس البخاخ لخطوط رفيعة أو سحب لون واسعة."
    ],
    [
     "الاستنسل",
     "قوالب مفرّغة لصور حادة قابلة للتكرار."
    ],
    [
     "لصق الورق",
     "ملصقات ورقية تُلصق على الجدران بعجينة الدقيق."
    ],
    [
     "الجداريات",
     "لوحات كبيرة مخطّطة تغطي جدراناً كاملة."
    ],
    [
     "الكاليغرافيتي",
     "الخط العربي والغرافيتي في حركة واحدة منسابة."
    ]
   ],
   "signs": [
    "رذاذ زائد: ضباب لون ناعم حول حواف الخطوط.",
    "قطرات تتبع الجاذبية حيث كان اللون كثيفاً.",
    "استنسل مقصوص باليد بمنحنيات غير منتظمة وتسرّبات صغيرة.",
    "على القماش، ملمس رذاذ تراه حين تنظر بزاوية.",
    "طبقات لون تتداخل وتنحرف قليلاً عن مكانها."
   ],
   "tools": [
    [
     "علب البخاخ",
     "لون سريع زاهٍ يلتصق بأي سطح تقريباً."
    ],
    [
     "فوهات قابلة للتبديل",
     "رؤوس تجعل الخط رفيعاً أو عريضاً، حاداً أو ناعماً."
    ],
    [
     "ورق الاستنسل وسكين الحرف",
     "لقصّ أشكال حادة باليد."
    ],
    [
     "عجينة اللصق والفرشاة",
     "غراء من الدقيق والماء للملصقات الورقية."
    ],
    [
     "البكرات والعصي الطويلة",
     "لتغطية الجدران الكبيرة بسرعة وبلوغ الأماكن العالية."
    ],
    [
     "كمامة التنفس",
     "تحمي رئتي الفنان من أبخرة الطلاء."
    ]
   ],
   "time": "من بضع ساعات إلى عدة أيام",
   "bestFor": "لمن يحب الألوان الجريئة وطاقة المدينة والفن الذي له صوت.",
   "artists": [
    [
     "كيث هارينغ",
     "أمريكي، 1958–1990",
     "رسوماته بالطباشير في مترو نيويورك جعلت الفن مفتوحاً للجميع."
    ],
    [
     "جان ميشيل باسكيات",
     "أمريكي، 1960–1988",
     "نقل طاقة الكتابة في الشارع إلى فن الرسم."
    ],
    [
     "شيبرد فيري",
     "أمريكي، مواليد 1970",
     "انتشرت ملصقاته من متاجر التزلج إلى جدران العالم."
    ],
    [
     "بانكسي",
     "بريطاني، هويته مجهولة",
     "جعلت رسوماته الذكية بالاستنسل فن الشارع مشهوراً في كل مكان."
    ],
    [
     "إل سيد",
     "فرنسي تونسي، مواليد 1981",
     "يرسم الخط العربي على الجدران والأسطح وأحياء كاملة."
    ]
   ],
   "guide": [
    "للأعمال المرسومة في المرسم، اطلب شهادة من الفنان أو من مرسمه الرسمي.",
    "انظر إلى السطح بزاوية. الرذاذ الحقيقي يُظهر ضباباً زائداً وقطرات.",
    "احذر من الأسماء الشهيرة. التقليد شائع، فاشترِ من مصادر موثوقة."
   ]
  }
 },
 {
  "id": "hyperrealism",
  "cat": "contemporary",
  "type": "hyperrealism",
  "level": "advanced",
  "en": {
   "name": "Hyperrealism",
   "feel": "Exact",
   "essence": "Painting sharper than a photograph, built from thousands of patient marks.",
   "story": [
    "Photorealism began in America in the late 1960s. Painters copied photographs so closely that viewers doubted it was paint.",
    "Chuck Close painted giant faces from small grids. Richard Estes painted city windows full of reflections. Duane Hanson and Ron Mueck brought the same care to sculpture.",
    "Today hyperrealists go further than the camera. They add feeling, weight and focus. One painting can take many months."
   ],
   "tech": [
    [
     "Layered glazing",
     "Many thin layers for a glass-like depth."
    ],
    [
     "Airbrushing",
     "A fine spray for smooth gradients."
    ],
    [
     "Fine detailing",
     "Tiny brushes for hairs, pores and reflections."
    ],
    [
     "Value mapping",
     "Planning every shade from light to dark."
    ],
    [
     "Grid transfer",
     "A grid to copy the drawing in exact proportion."
    ],
    [
     "Reference study",
     "Working from many photos to study light and texture."
    ]
   ],
   "signs": [
    "Paint texture under a magnifier, even where it looks like a photo.",
    "Faint grid or pencil lines near the edges.",
    "Edges slightly softer or sharper than a camera would make.",
    "Progress photos that show months of patient work."
   ],
   "tools": [
    [
     "Tiny round brushes",
     "Brushes as small as 000 for hairs and highlights."
    ],
    [
     "Airbrush and compressor",
     "For smooth skies, skin and soft shadows."
    ],
    [
     "Reference photographs",
     "Many photos of the same subject, studied closely."
    ],
    [
     "Magnifying lamp",
     "Bright, enlarged light for the finest details."
    ],
    [
     "Smooth panel or fine canvas",
     "A surface with almost no weave showing."
    ],
    [
     "Ruler and grid",
     "To keep every proportion exact."
    ]
   ],
   "time": "Several weeks to many months",
   "bestFor": "Collectors who love patience, precision and the thrill of looking twice.",
   "artists": [
    [
     "Richard Estes",
     "American, born 1932",
     "He painted shop windows and city streets full of mirrored detail."
    ],
    [
     "Chuck Close",
     "American, 1940–2021",
     "He built huge portraits from grids, square by careful square."
    ],
    [
     "Audrey Flack",
     "American, 1931–2024",
     "She was one of the first photorealists, known for rich still lifes."
    ],
    [
     "Duane Hanson",
     "American, 1925–1996",
     "His life-size sculptures of everyday people surprise gallery visitors."
    ],
    [
     "Ron Mueck",
     "Australian, born 1958",
     "He sculpts people at surprising sizes, with every wrinkle in place."
    ]
   ],
   "guide": [
    "See it in person, or ask for close-up photos with light from the side.",
    "Ask for progress photos. Hyperrealism is slow, and artists document it.",
    "A perfectly flat surface with no texture may be a print, not paint."
   ]
  },
  "ar": {
   "name": "الواقعية المفرطة",
   "feel": "مُتقن",
   "essence": "رسم أدقّ من الصورة، يُبنى من آلاف اللمسات الصبورة.",
   "story": [
    "بدأت الواقعية الفوتوغرافية في أمريكا في أواخر الستينيات. نسخ الرسامون الصور بدقة جعلت الناس يشكّون أنها رسم.",
    "رسم تشاك كلوز وجوهاً ضخمة من شبكات صغيرة. ورسم ريتشارد إستس نوافذ المدن المليئة بالانعكاسات. وحمل دوان هانسون ورون ميويك العناية نفسها إلى النحت.",
    "واليوم يذهب فنانو الواقعية المفرطة أبعد من الكاميرا. يضيفون الإحساس والثقل والتركيز. وقد تستغرق لوحة واحدة شهوراً كثيرة."
   ],
   "tech": [
    [
     "التزجيج المتعدد",
     "طبقات رقيقة كثيرة لعمق يشبه الزجاج."
    ],
    [
     "الرذاذ الهوائي",
     "رذاذ دقيق لتدرّجات ناعمة."
    ],
    [
     "التفصيل الدقيق",
     "فرش صغيرة للشعر والمسام والانعكاسات."
    ],
    [
     "خريطة القيم الضوئية",
     "تخطيط كل درجة من الفاتح إلى الداكن."
    ],
    [
     "النقل بالشبكة",
     "شبكة لنقل الرسم بنسب دقيقة."
    ],
    [
     "دراسة المراجع",
     "العمل من صور كثيرة لدراسة الضوء والملمس."
    ]
   ],
   "signs": [
    "ملمس طلاء يظهر تحت العدسة، حتى حيث يبدو كالصورة.",
    "شبكة أو خطوط رصاص خفيفة قرب الحواف.",
    "حواف أنعم أو أحدّ قليلاً مما تصنعه الكاميرا.",
    "صور مراحل تُظهر شهوراً من العمل الصبور."
   ],
   "tools": [
    [
     "فرش مستديرة دقيقة",
     "فرش صغيرة جداً للشعر ونقاط الضوء."
    ],
    [
     "الفرشاة الهوائية والضاغط",
     "لسماء وبشرة وظلال ناعمة."
    ],
    [
     "الصور المرجعية",
     "صور كثيرة للموضوع نفسه تُدرس عن قرب."
    ],
    [
     "مصباح مكبّر",
     "ضوء ساطع مكبّر لأدق التفاصيل."
    ],
    [
     "لوح أملس أو قماش ناعم",
     "سطح لا يكاد نسيجه يظهر."
    ],
    [
     "المسطرة والشبكة",
     "للحفاظ على كل نسبة دقيقة."
    ]
   ],
   "time": "من عدة أسابيع إلى شهور كثيرة",
   "bestFor": "لمن يحب الصبر والدقة ومتعة النظر مرتين.",
   "artists": [
    [
     "ريتشارد إستس",
     "أمريكي، مواليد 1932",
     "رسم واجهات المتاجر وشوارع المدن المليئة بالانعكاسات."
    ],
    [
     "تشاك كلوز",
     "أمريكي، 1940–2021",
     "بنى صوراً شخصية ضخمة من شبكات، مربعاً بعد مربع."
    ],
    [
     "أودري فلاك",
     "أمريكية، 1931–2024",
     "من أوائل فناني الواقعية الفوتوغرافية، واشتهرت بطبيعتها الصامتة الغنية."
    ],
    [
     "دوان هانسون",
     "أمريكي، 1925–1996",
     "تفاجئ منحوتاته لأناس عاديين بالحجم الطبيعي زوار المعارض."
    ],
    [
     "رون ميويك",
     "أسترالي، مواليد 1958",
     "ينحت البشر بأحجام مدهشة، وكل تجعيدة في مكانها."
    ]
   ],
   "guide": [
    "شاهده بنفسك، أو اطلب صوراً قريبة والضوء من الجانب.",
    "اطلب صور المراحل. الواقعية المفرطة بطيئة، والفنانون يوثّقونها.",
    "السطح المسطح تماماً بلا ملمس قد يكون طباعة لا رسماً."
   ]
  }
 },
 {
  "id": "collage",
  "cat": "contemporary",
  "type": "collage",
  "level": "beginner",
  "en": {
   "name": "Collage & Mixed Media",
   "feel": "Playful",
   "essence": "Paper, fabric, paint and found objects, joined into one surface.",
   "story": [
    "In 1912, Pablo Picasso and Georges Braque glued real paper onto their pictures. Art was never the same again.",
    "Kurt Schwitters built works from tickets and scraps. Hannah Höch cut photographs into bold new pictures. Romare Bearden told stories of Harlem life in paper.",
    "Robert Rauschenberg joined paint with photos and objects. Today El Anatsui turns bottle caps into shining metal cloth. Every cut and every layer is a decision."
   ],
   "tech": [
    [
     "Assemblage",
     "Building with real three-dimensional objects."
    ],
    [
     "Layering",
     "Stacking paper, paint and image in depth."
    ],
    [
     "Found materials",
     "Tickets, fabric, wood and scraps from daily life."
    ],
    [
     "Papier collé",
     "Pasted paper as part of a drawing or painting."
    ],
    [
     "Photomontage",
     "Cut photographs joined into a new, surprising scene."
    ],
    [
     "Tearing",
     "Ripped edges for soft, natural shapes."
    ],
    [
     "Decoupage",
     "Cut paper glued down and sealed under varnish."
    ]
   ],
   "signs": [
    "Real edges you can feel: cut paper, torn fibers, raised layers.",
    "Glue marks or small wrinkles where paper met wet paste.",
    "Shadows cast by the layers when light comes from the side.",
    "Mixed surfaces: matte paper beside glossy photos or rough fabric.",
    "Materials that age differently: paper yellows, metal shines."
   ],
   "tools": [
    [
     "Scissors and craft knife",
     "For clean cuts and fine shapes."
    ],
    [
     "Cutting mat",
     "Protects the table and keeps the blade steady."
    ],
    [
     "Acid-free glue or gel medium",
     "Holds layers firmly without yellowing."
    ],
    [
     "Brayer",
     "A small roller that presses layers flat and removes bubbles."
    ],
    [
     "Found papers and fabric",
     "Maps, tickets, letters and cloth with their own history."
    ],
    [
     "Varnish",
     "Seals and protects the finished surface."
    ]
   ],
   "time": "A few hours to several days",
   "bestFor": "Collectors who love texture, stories and everyday things made new.",
   "artists": [
    [
     "Pablo Picasso",
     "Spanish, 1881–1973",
     "He helped invent collage by bringing real materials into painting."
    ],
    [
     "Kurt Schwitters",
     "German, 1887–1948",
     "He made his Merz works from tickets, labels and street scraps."
    ],
    [
     "Hannah Höch",
     "German, 1889–1978",
     "She pioneered photomontage, cutting magazine photos into sharp new images."
    ],
    [
     "Romare Bearden",
     "American, 1911–1988",
     "His paper collages told warm stories of African American life."
    ],
    [
     "Robert Rauschenberg",
     "American, 1925–2008",
     "His Combines joined painting with photos, fabric and found objects."
    ],
    [
     "El Anatsui",
     "Ghanaian, born 1944",
     "He joins bottle caps and metal into huge shimmering hangings."
    ]
   ],
   "guide": [
    "Look from the side. Real collage has physical depth and edges.",
    "Ask about the materials and glue. Acid-free materials last much longer.",
    "A flat, glossy surface with no depth is usually a print."
   ]
  },
  "ar": {
   "name": "الكولاج والوسائط المختلطة",
   "feel": "مرِح",
   "essence": "ورق وقماش وطلاء وأشياء موجودة، تجتمع في سطح واحد.",
   "story": [
    "في عام 1912 ألصق بابلو بيكاسو وجورج براك ورقاً حقيقياً على أعمالهما. ولم يعد الفن كما كان.",
    "بنى كورت شفيترز أعمالاً من التذاكر والقصاصات. وقصّت هانا هوخ الصور لتصنع منها صوراً جديدة جريئة. وروى روماري بيردن حكايات حي هارلم بالورق.",
    "جمع روبرت راوشنبرغ الطلاء مع الصور والأشياء. واليوم يحوّل إل أناتسوي أغطية القوارير إلى قماش معدني لامع. وكل قصّة وكل طبقة قرار."
   ],
   "tech": [
    [
     "التجميع",
     "البناء بأشياء حقيقية مجسّمة."
    ],
    [
     "الطبقات",
     "تراكم الورق والطلاء والصور في العمق."
    ],
    [
     "المواد الموجودة",
     "تذاكر وقماش وخشب وقصاصات من الحياة اليومية."
    ],
    [
     "الورق الملصق",
     "ورق ملصق كجزء من رسم أو لوحة."
    ],
    [
     "المونتاج الفوتوغرافي",
     "صور مقصوصة تجتمع في مشهد جديد مدهش."
    ],
    [
     "التمزيق",
     "حواف ممزقة لأشكال ناعمة طبيعية."
    ],
    [
     "الديكوباج",
     "ورق مقصوص يُلصق ويُغطّى بالورنيش."
    ]
   ],
   "signs": [
    "حواف حقيقية تلمسها: ورق مقصوص وألياف ممزقة وطبقات بارزة.",
    "آثار غراء أو تجاعيد صغيرة حيث لامس الورق الغراء الرطب.",
    "ظلال تلقيها الطبقات حين يأتي الضوء من الجانب.",
    "أسطح مختلفة: ورق مطفأ بجانب صور لامعة أو قماش خشن.",
    "مواد تشيخ بشكل مختلف: الورق يصفرّ والمعدن يلمع."
   ],
   "tools": [
    [
     "المقص وسكين الحرف",
     "لقصّ نظيف وأشكال دقيقة."
    ],
    [
     "لوح القص",
     "يحمي الطاولة ويثبّت النصل."
    ],
    [
     "غراء خالٍ من الحمض",
     "يثبّت الطبقات بقوة دون أن يصفرّ."
    ],
    [
     "البكرة اليدوية",
     "أسطوانة صغيرة تضغط الطبقات وتزيل الفقاعات."
    ],
    [
     "أوراق وأقمشة موجودة",
     "خرائط وتذاكر ورسائل وقماش لكل منها تاريخ."
    ],
    [
     "الورنيش",
     "يغلّف السطح النهائي ويحميه."
    ]
   ],
   "time": "من بضع ساعات إلى عدة أيام",
   "bestFor": "لمن يحب الملمس والحكايات والأشياء اليومية حين تولد من جديد.",
   "artists": [
    [
     "بابلو بيكاسو",
     "إسباني، 1881–1973",
     "ساعد في ابتكار الكولاج حين أدخل مواد حقيقية إلى اللوحة."
    ],
    [
     "كورت شفيترز",
     "ألماني، 1887–1948",
     "صنع أعماله المسمّاة ميرتس من التذاكر والملصقات وقصاصات الشارع."
    ],
    [
     "هانا هوخ",
     "ألمانية، 1889–1978",
     "رائدة المونتاج الفوتوغرافي، قصّت صور المجلات وصنعت صوراً جديدة حادة."
    ],
    [
     "روماري بيردن",
     "أمريكي، 1911–1988",
     "روت أعماله الورقية حكايات دافئة عن حياة الأمريكيين الأفارقة."
    ],
    [
     "روبرت راوشنبرغ",
     "أمريكي، 1925–2008",
     "جمعت أعماله المركّبة بين الرسم والصور والقماش والأشياء."
    ],
    [
     "إل أناتسوي",
     "غاني، مواليد 1944",
     "يربط أغطية القوارير والمعدن في ستائر ضخمة متلألئة."
    ]
   ],
   "guide": [
    "انظر من الجانب. الكولاج الحقيقي له عمق وحواف ملموسة.",
    "اسأل عن المواد والغراء. المواد الخالية من الحمض تدوم أطول بكثير.",
    "السطح المسطح اللامع بلا عمق غالباً طباعة."
   ]
  }
 },
 {
  "id": "abstract",
  "cat": "contemporary",
  "type": "abstract",
  "level": "beginner",
  "en": {
   "name": "Contemporary Abstract",
   "feel": "Free",
   "essence": "Color, line and form that speak without showing a single object.",
   "story": [
    "Around 1910, Wassily Kandinsky painted pictures with no objects at all. Color and line became the whole message, like music.",
    "After 1945, Mark Rothko made huge fields of glowing color in New York. In Baghdad, Shakir Hassan Al Said joined abstraction with Arabic letters. In Riyadh, Mohammed Al-Saleem turned desert horizons into calm, flowing shapes.",
    "Today abstract painters work in every country. Etel Adnan and Zao Wou-Ki showed it can carry deep memory. Every brushstroke is a personal choice you can feel."
   ],
   "tech": [
    [
     "Gestural brushwork",
     "Big, free strokes that record the artist’s movement."
    ],
    [
     "Color field",
     "Wide areas of color that fill the eye."
    ],
    [
     "Palette knife impasto",
     "Thick paint spread and sculpted with a knife."
    ],
    [
     "Layering and scraping",
     "Building layers, then scratching back to reveal colors below."
    ],
    [
     "Hard edge",
     "Crisp shapes made with tape and steady hands."
    ],
    [
     "Pouring",
     "Thin paint poured and tilted to flow across the canvas."
    ]
   ],
   "signs": [
    "Brush and knife ridges that catch light from the side.",
    "Lower layers peeking through scratches or thin areas.",
    "Paint drips and color running onto the canvas sides.",
    "Small changes of mind, like a covered shape faintly showing.",
    "Hard edges with tiny bleeds where the tape met the paint."
   ],
   "tools": [
    [
     "Wide flat brushes",
     "For broad, sweeping strokes and smooth fields."
    ],
    [
     "Palette knives",
     "For thick paint, scraping and sharp marks."
    ],
    [
     "Large canvas or linen",
     "Room for the painting to breathe."
    ],
    [
     "Acrylic or oil paint",
     "Fast drying or slow blending, by the artist’s choice."
    ],
    [
     "Masking tape",
     "For clean, straight edges."
    ],
    [
     "Scrapers and squeegees",
     "To drag, smooth and reveal layers."
    ]
   ],
   "time": "A few days to several weeks",
   "bestFor": "Collectors who want art that feels like music and changes with their mood.",
   "artists": [
    [
     "Wassily Kandinsky",
     "Russian, 1866–1944",
     "He was one of the first painters of pure abstraction."
    ],
    [
     "Mark Rothko",
     "American, 1903–1970",
     "His soft rectangles of color invite quiet, long looking."
    ],
    [
     "Zao Wou-Ki",
     "Chinese-French, 1920–2013",
     "He joined the spirit of Chinese ink with Western oil painting."
    ],
    [
     "Shakir Hassan Al Said",
     "Iraqi, 1925–2004",
     "He founded the One Dimension group, joining Arabic letters and abstraction."
    ],
    [
     "Mohammed Al-Saleem",
     "Saudi, 1939–1997",
     "A Saudi pioneer, he painted desert horizons in his own abstract style."
    ],
    [
     "Etel Adnan",
     "Lebanese-American, 1925–2021",
     "Poet and painter, she made small bright paintings of mountains and sun."
    ]
   ],
   "guide": [
    "Look closely at the surface. Real paint has ridges, layers and depth.",
    "Ask the artist about their colors and layers. They will remember each one.",
    "Check the canvas sides. Real paintings often show drips and color there."
   ]
  },
  "ar": {
   "name": "التجريد المعاصر",
   "feel": "حرّ",
   "essence": "لون وخط وشكل تتحدث دون أن تصوّر شيئاً.",
   "story": [
    "نحو عام 1910 رسم فاسيلي كاندينسكي لوحات بلا أشياء على الإطلاق. صار اللون والخط هما الرسالة كلها، مثل الموسيقى.",
    "بعد عام 1945 رسم مارك روثكو مساحات ضخمة من اللون المتوهج في نيويورك. وفي بغداد جمع شاكر حسن آل سعيد التجريد بالحرف العربي. وفي الرياض حوّل محمد السليم آفاق الصحراء إلى أشكال هادئة منسابة.",
    "واليوم يعمل الرسامون التجريديون في كل بلد. وأظهرت إيتل عدنان وزاو وو كي أنه يحمل ذاكرة عميقة. وكل ضربة فرشاة اختيار شخصي تشعر به."
   ],
   "tech": [
    [
     "الفرشاة الإيمائية",
     "ضربات كبيرة حرة تسجّل حركة الفنان."
    ],
    [
     "حقول اللون",
     "مساحات واسعة من اللون تملأ العين."
    ],
    [
     "الطلاء الكثيف بالسكين",
     "طلاء كثيف يُفرد ويُشكّل بالسكين."
    ],
    [
     "الطبقات والكشط",
     "بناء الطبقات ثم خدشها لكشف الألوان تحتها."
    ],
    [
     "الحواف الحادة",
     "أشكال واضحة تُصنع بالشريط ويد ثابتة."
    ],
    [
     "السكب",
     "طلاء رقيق يُسكب ويُمال لينساب على القماش."
    ]
   ],
   "signs": [
    "نتوءات الفرشاة والسكين تلتقط الضوء من الجانب.",
    "طبقات سفلية تظهر من الخدوش أو المناطق الرقيقة.",
    "قطرات طلاء ولون يسيل على جوانب القماش.",
    "تغييرات صغيرة في الرأي، كشكل مغطّى يظهر بخفوت.",
    "حواف حادة فيها تسرّب صغير حيث لامس الشريط الطلاء."
   ],
   "tools": [
    [
     "فرش مسطحة عريضة",
     "لضربات واسعة وحقول لون ناعمة."
    ],
    [
     "سكاكين الرسم",
     "للطلاء الكثيف والكشط والعلامات الحادة."
    ],
    [
     "قماش أو كتان كبير",
     "مساحة تتنفس فيها اللوحة."
    ],
    [
     "ألوان أكريليك أو زيت",
     "جفاف سريع أو مزج بطيء، حسب اختيار الفنان."
    ],
    [
     "شريط لاصق",
     "لحواف نظيفة مستقيمة."
    ],
    [
     "الكاشطات والممسحات",
     "لسحب الطبقات وتنعيمها وكشفها."
    ]
   ],
   "time": "من بضعة أيام إلى عدة أسابيع",
   "bestFor": "لمن يريد فناً يشبه الموسيقى ويتغيّر مع مزاجه.",
   "artists": [
    [
     "فاسيلي كاندينسكي",
     "روسي، 1866–1944",
     "من أوائل رسامي التجريد الخالص."
    ],
    [
     "مارك روثكو",
     "أمريكي، 1903–1970",
     "مستطيلاته اللونية الناعمة تدعوك إلى تأمل هادئ طويل."
    ],
    [
     "زاو وو كي",
     "صيني فرنسي، 1920–2013",
     "جمع روح الحبر الصيني مع الرسم الزيتي الغربي."
    ],
    [
     "شاكر حسن آل سعيد",
     "عراقي، 1925–2004",
     "أسّس جماعة البعد الواحد، جامعاً الحرف العربي والتجريد."
    ],
    [
     "محمد السليم",
     "سعودي، 1939–1997",
     "رائد سعودي رسم آفاق الصحراء بأسلوبه التجريدي الخاص."
    ],
    [
     "إيتل عدنان",
     "لبنانية أمريكية، 1925–2021",
     "شاعرة ورسامة، رسمت لوحات صغيرة مشرقة للجبال والشمس."
    ]
   ],
   "guide": [
    "انظر إلى السطح عن قرب. الطلاء الحقيقي له نتوءات وطبقات وعمق.",
    "اسأل الفنان عن ألوانه وطبقاته. سيتذكر كل واحدة منها.",
    "افحص جوانب القماش. اللوحات الحقيقية غالباً تحمل قطرات ولوناً هناك."
   ]
  }
 },
 {
  "id": "pop_art",
  "cat": "contemporary",
  "type": "pop_art",
  "level": "beginner",
  "en": {
   "name": "Pop Art",
   "feel": "Cheerful",
   "essence": "Everyday images, from ads to comics, turned into bright, bold art.",
   "story": [
    "Pop Art began in London and New York in the 1950s. Artists looked at ads, comics and shop shelves with fresh eyes.",
    "Richard Hamilton made early pop collages in Britain. Andy Warhol printed soup cans and famous faces. Roy Lichtenstein painted comic panels, dot by careful dot.",
    "Today Takashi Murakami and Hassan Hajjaj carry the spirit forward. Hajjaj mixes Moroccan street style with bright pop color. Pop Art still smiles at the world we shop in."
   ],
   "tech": [
    [
     "Screen printing",
     "Ink pushed through a mesh screen, one color at a time."
    ],
    [
     "Ben-Day dots",
     "Small dots of color, borrowed from old comic printing."
    ],
    [
     "Flat bold color",
     "Bright areas of color with no soft blending."
    ],
    [
     "Appropriation",
     "Borrowing familiar images and giving them new meaning."
    ],
    [
     "Repetition",
     "The same image repeated, like goods on a shelf."
    ],
    [
     "Strong outlines",
     "Thick black lines, like a comic panel."
    ]
   ],
   "signs": [
    "Screen prints with slight shifts between colors, called misregistration.",
    "Ink thickness that varies a little across the sheet.",
    "Hand-painted dots that differ slightly in size and edge.",
    "A pencil signature and edition number, like 12/50, on prints.",
    "Brush marks hiding inside flat color areas on paintings."
   ],
   "tools": [
    [
     "Silkscreen frame and squeegee",
     "To print bold color layers by hand."
    ],
    [
     "Acrylic paint",
     "Bright, fast-drying color for flat areas."
    ],
    [
     "Dot stencils",
     "Perforated sheets for even rows of dots."
    ],
    [
     "Masking tape",
     "For crisp outlines and clean shapes."
    ],
    [
     "Magazines and packaging",
     "Everyday images to borrow and transform."
    ]
   ],
   "time": "A few days to a few weeks",
   "bestFor": "Collectors who love humor, color and art that talks about daily life.",
   "artists": [
    [
     "Richard Hamilton",
     "British, 1922–2011",
     "His 1956 collage is often called one of the first Pop artworks."
    ],
    [
     "Andy Warhol",
     "American, 1928–1987",
     "He turned soup cans and celebrity faces into famous screen prints."
    ],
    [
     "Roy Lichtenstein",
     "American, 1923–1997",
     "He enlarged comic panels, painting every dot with care."
    ],
    [
     "Claes Oldenburg",
     "Swedish-American, 1929–2022",
     "He made giant sculptures of everyday objects, from spoons to clothespins."
    ],
    [
     "Takashi Murakami",
     "Japanese, born 1962",
     "His Superflat style blends Japanese tradition, cartoons and bright pop color."
    ],
    [
     "Hassan Hajjaj",
     "Moroccan, born 1961",
     "He mixes Moroccan street style with bold, joyful pop portraits."
    ]
   ],
   "guide": [
    "Check prints for a pencil signature and an edition number.",
    "Ask for the publisher or print studio. Real editions are recorded.",
    "Use a magnifier. Screen ink sits on top, while cheap copies show printer dots."
   ]
  },
  "ar": {
   "name": "فن البوب",
   "feel": "مُبهج",
   "essence": "صور يومية، من الإعلانات إلى القصص المصورة، تصير فناً زاهياً.",
   "story": [
    "بدأ فن البوب في لندن ونيويورك في الخمسينيات. نظر الفنانون إلى الإعلانات والقصص المصورة ورفوف المتاجر بعيون جديدة.",
    "صنع ريتشارد هاميلتون أعمال كولاج مبكرة في بريطانيا. وطبع آندي وارهول علب الحساء ووجوه المشاهير. ورسم روي ليختنشتاين لوحات القصص المصورة، نقطة بعد نقطة.",
    "واليوم يحمل تاكاشي موراكامي وحسن حجاج هذه الروح إلى الأمام. يمزج حجاج أزياء الشارع المغربي بألوان البوب الزاهية. وما زال فن البوب يبتسم لعالم التسوق من حولنا."
   ],
   "tech": [
    [
     "الطباعة بالشاشة الحريرية",
     "حبر يُدفع عبر شبكة دقيقة، لوناً بعد لون."
    ],
    [
     "نقاط بن داي",
     "نقاط لون صغيرة مأخوذة من طباعة القصص المصورة القديمة."
    ],
    [
     "اللون المسطح الجريء",
     "مساحات لون زاهية بلا مزج ناعم."
    ],
    [
     "الاقتباس",
     "استعارة صور مألوفة ومنحها معنى جديداً."
    ],
    [
     "التكرار",
     "الصورة نفسها مكرّرة، كالبضائع على الرف."
    ],
    [
     "الخطوط القوية",
     "خطوط سوداء سميكة مثل إطار القصة المصورة."
    ]
   ],
   "signs": [
    "طبعات بإزاحة خفيفة بين الألوان، تُسمى سوء التطابق.",
    "سماكة حبر تختلف قليلاً على امتداد الورقة.",
    "نقاط مرسومة باليد تختلف قليلاً في الحجم والحافة.",
    "توقيع بالرصاص ورقم إصدار مثل 12/50 على الطبعات.",
    "آثار فرشاة مخفية داخل مساحات اللون المسطح في اللوحات."
   ],
   "tools": [
    [
     "إطار الشاشة الحريرية والممسحة",
     "لطباعة طبقات لون جريئة باليد."
    ],
    [
     "ألوان الأكريليك",
     "لون زاهٍ سريع الجفاف للمساحات المسطحة."
    ],
    [
     "قوالب النقاط",
     "صفائح مثقّبة لصفوف نقاط منتظمة."
    ],
    [
     "شريط لاصق",
     "لخطوط خارجية حادة وأشكال نظيفة."
    ],
    [
     "المجلات والعبوات",
     "صور يومية تُستعار وتتحوّل."
    ]
   ],
   "time": "من بضعة أيام إلى بضعة أسابيع",
   "bestFor": "لمن يحب الدعابة واللون والفن الذي يتحدث عن الحياة اليومية.",
   "artists": [
    [
     "ريتشارد هاميلتون",
     "بريطاني، 1922–2011",
     "يُعدّ عمله الكولاجي عام 1956 من أوائل أعمال فن البوب."
    ],
    [
     "آندي وارهول",
     "أمريكي، 1928–1987",
     "حوّل علب الحساء ووجوه المشاهير إلى طبعات شهيرة."
    ],
    [
     "روي ليختنشتاين",
     "أمريكي، 1923–1997",
     "كبّر لوحات القصص المصورة ورسم كل نقطة بعناية."
    ],
    [
     "كلايس أولدنبورغ",
     "سويدي أمريكي، 1929–2022",
     "صنع منحوتات عملاقة لأشياء يومية، من الملاعق إلى مشابك الغسيل."
    ],
    [
     "تاكاشي موراكامي",
     "ياباني، مواليد 1962",
     "يمزج أسلوبه المسطّح الفائق التراث الياباني والرسوم المتحركة وألوان البوب."
    ],
    [
     "حسن حجاج",
     "مغربي، مواليد 1961",
     "يمزج أزياء الشارع المغربي بصور شخصية مبهجة بروح البوب."
    ]
   ],
   "guide": [
    "افحص الطبعات بحثاً عن توقيع بالرصاص ورقم إصدار.",
    "اسأل عن الناشر أو مرسم الطباعة. الإصدارات الحقيقية مسجّلة.",
    "استخدم عدسة مكبّرة. حبر الشاشة يعلو السطح، والنسخ الرخيصة تُظهر نقاط الطابعة."
   ]
  }
 },
 {
  "id": "minimalism",
  "cat": "contemporary",
  "type": "minimalism",
  "level": "intermediate",
  "en": {
   "name": "Minimalism",
   "feel": "Calm",
   "essence": "Less is more: simple forms, pure color and quiet space.",
   "story": [
    "Minimalism grew in New York in the 1960s. Artists stripped art down to shape, color, material and space.",
    "Frank Stella painted bold stripes with house paint. Donald Judd made boxes in clean rows. Dan Flavin shaped rooms with light, while Agnes Martin drew soft grids by hand.",
    "Lee Ufan, from Korea, sets stone beside steel in quiet works. Today minimal artists keep this calm alive worldwide. Every line is chosen with great care."
   ],
   "tech": [
    [
     "Hard-edge painting",
     "Clean, flat shapes with sharp borders."
    ],
    [
     "Monochrome",
     "One color only, explored in depth and texture."
    ],
    [
     "The grid",
     "Repeated lines that bring order and rhythm."
    ],
    [
     "Seriality",
     "Several works that repeat one idea with small changes."
    ],
    [
     "Negative space",
     "Empty space that matters as much as the form."
    ],
    [
     "Hand-ruled lines",
     "Pencil lines drawn slowly along a ruler."
    ]
   ],
   "signs": [
    "Hand-drawn lines that waver slightly under close looking.",
    "Even color fields with a faint brush direction.",
    "Tape edges with tiny bleeds or ridges.",
    "Small differences between pieces in the same series.",
    "Pencil marks showing through thin paint layers."
   ],
   "tools": [
    [
     "Pencil and long ruler",
     "For straight, quiet lines and grids."
    ],
    [
     "Masking tape",
     "For crisp edges between flat colors."
    ],
    [
     "Wide soft brushes",
     "To lay smooth, even color."
    ],
    [
     "Gesso",
     "A smooth white ground under the paint."
    ],
    [
     "Primed canvas or panel",
     "A clean surface where every mark shows."
    ]
   ],
   "time": "A few days to several weeks",
   "bestFor": "Collectors who want calm, space and art that rewards slow looking.",
   "artists": [
    [
     "Agnes Martin",
     "Canadian-American, 1912–2004",
     "Her hand-drawn grids feel like quiet breathing on canvas."
    ],
    [
     "Frank Stella",
     "American, 1936–2024",
     "His early black stripe paintings opened the way for Minimalism."
    ],
    [
     "Donald Judd",
     "American, 1928–1994",
     "He made simple boxes that changed how we see space."
    ],
    [
     "Dan Flavin",
     "American, 1933–1996",
     "He shaped whole rooms with plain fluorescent light."
    ],
    [
     "Sol LeWitt",
     "American, 1928–2007",
     "His wall drawings follow simple written instructions."
    ],
    [
     "Lee Ufan",
     "Korean, born 1936",
     "He pairs stone, steel and single brushstrokes in quiet harmony."
    ]
   ],
   "guide": [
    "Look closely. Real minimal work shows small traces of the hand.",
    "Ask about the materials. Surface quality matters greatly in this style.",
    "Check the edges and sides. Careful finishing is part of the art."
   ]
  },
  "ar": {
   "name": "التقليلية",
   "feel": "هادئ",
   "essence": "القليل أكثر: أشكال بسيطة ولون صافٍ ومساحة هادئة.",
   "story": [
    "نشأت التقليلية في نيويورك في الستينيات. جرّد الفنانون الفن حتى بقي الشكل واللون والمادة والمساحة.",
    "رسم فرانك ستيلا خطوطاً جريئة بطلاء المنازل. وصنع دونالد جاد صناديق في صفوف نظيفة. وشكّل دان فلافين الغرف بالضوء، بينما رسمت أغنيس مارتن شبكات ناعمة باليد.",
    "ويضع لي أوفان، القادم من كوريا، الحجر بجانب الفولاذ في أعمال هادئة. واليوم يحافظ فنانو التقليلية على هذا الهدوء حول العالم. وكل خط يُختار بعناية كبيرة."
   ],
   "tech": [
    [
     "رسم الحواف الحادة",
     "أشكال مسطحة نظيفة بحدود حادة."
    ],
    [
     "اللون الواحد",
     "لون واحد فقط يُستكشف في عمقه وملمسه."
    ],
    [
     "الشبكة",
     "خطوط متكررة تمنح النظام والإيقاع."
    ],
    [
     "السلسلة",
     "أعمال عدة تكرر فكرة واحدة بتغييرات صغيرة."
    ],
    [
     "الفراغ",
     "مساحة فارغة مهمة بقدر الشكل نفسه."
    ],
    [
     "الخطوط بالمسطرة",
     "خطوط رصاص تُرسم ببطء على امتداد المسطرة."
    ]
   ],
   "signs": [
    "خطوط مرسومة باليد ترتجف قليلاً حين تنظر عن قرب.",
    "حقول لون متساوية فيها اتجاه فرشاة خفيف.",
    "حواف الشريط فيها تسرّب صغير أو نتوءات.",
    "فروق صغيرة بين قطع السلسلة الواحدة.",
    "آثار رصاص تظهر من تحت طبقات الطلاء الرقيقة."
   ],
   "tools": [
    [
     "قلم رصاص ومسطرة طويلة",
     "لخطوط وشبكات مستقيمة هادئة."
    ],
    [
     "شريط لاصق",
     "لحواف حادة بين الألوان المسطحة."
    ],
    [
     "فرش عريضة ناعمة",
     "لفرد لون ناعم متساوٍ."
    ],
    [
     "الجيسو",
     "أرضية بيضاء ناعمة تحت الطلاء."
    ],
    [
     "قماش أو لوح مجهّز",
     "سطح نظيف تظهر عليه كل علامة."
    ]
   ],
   "time": "من بضعة أيام إلى عدة أسابيع",
   "bestFor": "لمن يريد الهدوء والمساحة وفناً يكافئ النظر المتأني.",
   "artists": [
    [
     "أغنيس مارتن",
     "كندية أمريكية، 1912–2004",
     "شبكاتها المرسومة باليد تشبه تنفساً هادئاً على القماش."
    ],
    [
     "فرانك ستيلا",
     "أمريكي، 1936–2024",
     "فتحت لوحاته المخططة بالأسود الطريق أمام التقليلية."
    ],
    [
     "دونالد جاد",
     "أمريكي، 1928–1994",
     "صنع صناديق بسيطة غيّرت طريقة رؤيتنا للمساحة."
    ],
    [
     "دان فلافين",
     "أمريكي، 1933–1996",
     "شكّل غرفاً كاملة بضوء الفلورسنت البسيط."
    ],
    [
     "سول لويت",
     "أمريكي، 1928–2007",
     "تتبع رسوماته الجدارية تعليمات مكتوبة بسيطة."
    ],
    [
     "لي أوفان",
     "كوري، مواليد 1936",
     "يجمع الحجر والفولاذ وضربات فرشاة مفردة في انسجام هادئ."
    ]
   ],
   "guide": [
    "انظر عن قرب. العمل التقليلي الحقيقي يُظهر آثاراً صغيرة لليد.",
    "اسأل عن المواد. جودة السطح مهمة جداً في هذا الأسلوب.",
    "افحص الحواف والجوانب. التشطيب المتقن جزء من الفن."
   ]
  }
 },
 {
  "id": "surrealism",
  "cat": "contemporary",
  "type": "surrealism",
  "level": "intermediate",
  "en": {
   "name": "Surrealism",
   "feel": "Mysterious",
   "essence": "Dreams, memories and the impossible, painted as if they were real.",
   "story": [
    "Surrealism began in Paris in 1924. The poet André Breton called on artists to paint the hidden world of dreams.",
    "Salvador Dalí painted melting clocks. René Magritte hid a face behind a floating apple. In Cairo, Ramses Younan helped found the Art and Liberty group in 1938.",
    "Today surreal ideas live on in painting, film and photography. Artists still invite us to see the strange in daily life. Every dream image comes from the artist’s own mind."
   ],
   "tech": [
    [
     "Automatism",
     "Drawing freely without planning, letting the hand lead."
    ],
    [
     "Frottage",
     "Rubbing pencil over textured surfaces, made popular by Max Ernst."
    ],
    [
     "Decalcomania",
     "Pressing paint between surfaces for strange, natural textures."
    ],
    [
     "Juxtaposition",
     "Placing unrelated things together to spark surprise."
    ],
    [
     "Trompe l’oeil",
     "Fine realism that makes the impossible look believable."
    ],
    [
     "Exquisite corpse",
     "A drawing game where each artist adds a hidden part."
    ]
   ],
   "signs": [
    "Fine brush detail in dream objects, never pixel-sharp edges.",
    "Visible underdrawing or changes beneath thin glazes.",
    "Frottage or decalcomania textures that never repeat exactly.",
    "Sketches and studies that show the idea growing over time."
   ],
   "tools": [
    [
     "Fine round brushes",
     "For smooth, detailed dream scenes."
    ],
    [
     "Oil paint and glazing medium",
     "For deep, glowing layers."
    ],
    [
     "Sketchbook",
     "To catch dream images before they fade."
    ],
    [
     "Pencil and textured surfaces",
     "Wood, leaves and fabric for frottage rubbings."
    ],
    [
     "Glass or smooth paper",
     "Pressed into wet paint for decalcomania."
    ],
    [
     "Wood panel or canvas",
     "A steady surface for fine detail."
    ]
   ],
   "time": "Several days to several months",
   "bestFor": "Collectors who love mystery, stories and art that feels like a dream.",
   "artists": [
    [
     "René Magritte",
     "Belgian, 1898–1967",
     "He painted ordinary things in puzzling, poetic ways."
    ],
    [
     "Salvador Dalí",
     "Spanish, 1904–1989",
     "His melting clocks became one of art’s most famous dream images."
    ],
    [
     "Max Ernst",
     "German, 1891–1976",
     "He developed frottage and many playful surreal techniques."
    ],
    [
     "Leonora Carrington",
     "British-Mexican, 1917–2011",
     "She painted magical worlds full of animals and myth."
    ],
    [
     "Ramses Younan",
     "Egyptian, 1913–1966",
     "He helped lead Egyptian Surrealism with the Art and Liberty group."
    ]
   ],
   "guide": [
    "Ask for sketches or studies. Surreal ideas usually grow over many drawings.",
    "Check fine details under a magnifier for real brush marks.",
    "Famous surrealists are often faked. Ask for a clear ownership history."
   ]
  },
  "ar": {
   "name": "السريالية",
   "feel": "غامض",
   "essence": "أحلام وذكريات ومستحيلات تُرسم كأنها حقيقية.",
   "story": [
    "بدأت السريالية في باريس عام 1924. دعا الشاعر أندريه بروتون الفنانين إلى رسم عالم الأحلام الخفي.",
    "رسم سلفادور دالي ساعات ذائبة. وأخفى رينيه ماغريت وجهاً خلف تفاحة طائرة. وفي القاهرة ساهم رمسيس يونان في تأسيس جماعة الفن والحرية عام 1938.",
    "واليوم تعيش الأفكار السريالية في الرسم والسينما والتصوير. وما زال الفنانون يدعوننا إلى رؤية الغريب في الحياة اليومية. وكل صورة حالمة تولد من خيال الفنان نفسه."
   ],
   "tech": [
    [
     "الآلية",
     "رسم حر بلا تخطيط، تقود فيه اليد."
    ],
    [
     "الحك",
     "تمرير الرصاص فوق أسطح محببة، وقد اشتهر به ماكس إرنست."
    ],
    [
     "الطبع بالضغط",
     "ضغط الطلاء بين سطحين لملمس غريب طبيعي."
    ],
    [
     "التجاور",
     "وضع أشياء لا رابط بينها معاً لإثارة الدهشة."
    ],
    [
     "خداع البصر",
     "واقعية دقيقة تجعل المستحيل يبدو قابلاً للتصديق."
    ],
    [
     "الجثة الرائعة",
     "لعبة رسم يضيف فيها كل فنان جزءاً مخفياً."
    ]
   ],
   "signs": [
    "تفاصيل فرشاة دقيقة في الأشياء الحالمة، لا حواف رقمية حادة.",
    "رسم تمهيدي ظاهر أو تغييرات تحت الطبقات الرقيقة.",
    "ملمس الحك أو الطبع بالضغط لا يتكرر أبداً بالضبط.",
    "اسكتشات ودراسات تُظهر الفكرة وهي تنمو مع الوقت."
   ],
   "tools": [
    [
     "فرش مستديرة دقيقة",
     "لمشاهد حالمة ناعمة مفصّلة."
    ],
    [
     "ألوان زيتية ووسيط تزجيج",
     "لطبقات عميقة متوهجة."
    ],
    [
     "كراسة الرسم",
     "لالتقاط صور الأحلام قبل أن تتلاشى."
    ],
    [
     "الرصاص والأسطح المحببة",
     "خشب وأوراق شجر وقماش لطبعات الحك."
    ],
    [
     "زجاج أو ورق أملس",
     "يُضغط على الطلاء الرطب للطبع بالضغط."
    ],
    [
     "لوح خشبي أو قماش",
     "سطح ثابت للتفاصيل الدقيقة."
    ]
   ],
   "time": "من عدة أيام إلى عدة شهور",
   "bestFor": "لمن يحب الغموض والحكايات والفن الذي يشبه الحلم.",
   "artists": [
    [
     "رينيه ماغريت",
     "بلجيكي، 1898–1967",
     "رسم الأشياء العادية بطرق محيّرة وشاعرية."
    ],
    [
     "سلفادور دالي",
     "إسباني، 1904–1989",
     "صارت ساعاته الذائبة من أشهر صور الأحلام في الفن."
    ],
    [
     "ماكس إرنست",
     "ألماني، 1891–1976",
     "طوّر تقنية الحك وتقنيات سريالية مرحة كثيرة."
    ],
    [
     "ليونورا كارينغتون",
     "بريطانية مكسيكية، 1917–2011",
     "رسمت عوالم ساحرة مليئة بالحيوانات والأساطير."
    ],
    [
     "رمسيس يونان",
     "مصري، 1913–1966",
     "ساعد في قيادة السريالية المصرية مع جماعة الفن والحرية."
    ]
   ],
   "guide": [
    "اطلب الاسكتشات أو الدراسات. الأفكار السريالية تنمو عادة عبر رسوم كثيرة.",
    "افحص التفاصيل الدقيقة بعدسة مكبّرة بحثاً عن ضربات فرشاة حقيقية.",
    "أعمال السرياليين المشهورين تُقلّد كثيراً. اطلب سجل ملكية واضحاً."
   ]
  }
 },
 {
  "id": "expressionism",
  "cat": "contemporary",
  "type": "expressionism",
  "level": "beginner",
  "en": {
   "name": "Expressionism",
   "feel": "Passionate",
   "essence": "Feeling first: bold color and strong lines that show the inner world.",
   "story": [
    "Expressionism rose in Germany in the early 1900s. Artists painted how the world felt, not how it looked.",
    "Edvard Munch painted fear and love in swirling color. The Bridge group in Dresden carved bold woodcuts. The Blue Rider group in Munich searched for spiritual color.",
    "After 1945, Willem de Kooning brought this energy to huge canvases. Today painters everywhere use expressive color to share real emotion. Every stroke carries the artist’s mood."
   ],
   "tech": [
    [
     "Distorted form",
     "Stretched or bent shapes that show emotion."
    ],
    [
     "Bold unnatural color",
     "Blue faces or red skies to show a feeling."
    ],
    [
     "Visible brushwork",
     "Fast, loaded strokes left proudly on the surface."
    ],
    [
     "Woodcut",
     "Images carved into wood, inked and printed by hand."
    ],
    [
     "Impasto",
     "Thick paint that stands up from the canvas."
    ],
    [
     "Strong outlines",
     "Dark, heavy lines that hold the shapes."
    ]
   ],
   "signs": [
    "Fast, loaded brushstrokes with ridges and dragged edges.",
    "Woodcut prints with wood grain and slightly uneven ink.",
    "Earlier colors showing under the top layer.",
    "Strong, uneven outlines made by a confident hand."
   ],
   "tools": [
    [
     "Stiff bristle brushes",
     "For bold, textured strokes."
    ],
    [
     "Oil paint",
     "Rich color that holds thick marks."
    ],
    [
     "Palette knife",
     "For heavy paint and sharp scrapes."
    ],
    [
     "Woodblock and gouges",
     "For carving prints by hand."
    ],
    [
     "Brayer and printing ink",
     "To roll ink evenly onto the block."
    ],
    [
     "Canvas",
     "A strong surface for energetic painting."
    ]
   ],
   "time": "A few hours to a few weeks",
   "bestFor": "Collectors who want art with strong feeling and honest emotion.",
   "artists": [
    [
     "Edvard Munch",
     "Norwegian, 1863–1944",
     "His painting The Scream became a symbol of modern anxiety."
    ],
    [
     "Ernst Ludwig Kirchner",
     "German, 1880–1938",
     "He co-founded The Bridge and painted tense city streets."
    ],
    [
     "Franz Marc",
     "German, 1880–1916",
     "He painted blue horses and bright animals full of feeling."
    ],
    [
     "Käthe Kollwitz",
     "German, 1867–1945",
     "Her prints and drawings gave a voice to grief and compassion."
    ],
    [
     "Willem de Kooning",
     "Dutch-American, 1904–1997",
     "He led Abstract Expressionism with fierce, sweeping brushwork."
    ]
   ],
   "guide": [
    "Look for real brush texture and ridges in light from the side.",
    "For woodcuts, check for wood grain and small ink variations.",
    "Ask for the work’s history. Famous Expressionists are often faked."
   ]
  },
  "ar": {
   "name": "التعبيرية",
   "feel": "شغوف",
   "essence": "الإحساس أولاً: لون جريء وخطوط قوية تُظهر العالم الداخلي.",
   "story": [
    "ظهرت التعبيرية في ألمانيا في أوائل القرن العشرين. رسم الفنانون شعورهم بالعالم، لا شكله الظاهر.",
    "رسم إدفارد مونك الخوف والحب بألوان دوّامة. ونحتت جماعة الجسر في دريسدن طبعات خشبية جريئة. وبحثت جماعة الفارس الأزرق في ميونخ عن اللون الروحي.",
    "بعد عام 1945 حمل ويليم دي كونينغ هذه الطاقة إلى لوحات ضخمة. واليوم يستخدم الرسامون في كل مكان اللون المعبّر لمشاركة إحساس صادق. وكل ضربة تحمل مزاج الفنان."
   ],
   "tech": [
    [
     "تحوير الشكل",
     "أشكال ممدودة أو منحنية تُظهر الإحساس."
    ],
    [
     "اللون الجريء غير الواقعي",
     "وجوه زرقاء أو سماء حمراء لتُظهر شعوراً."
    ],
    [
     "ضربات الفرشاة الظاهرة",
     "ضربات سريعة محمّلة تُترك بفخر على السطح."
    ],
    [
     "الحفر على الخشب",
     "صور تُحفر في الخشب وتُحبّر وتُطبع باليد."
    ],
    [
     "الطلاء الكثيف",
     "طلاء سميك يبرز من القماش."
    ],
    [
     "الخطوط القوية",
     "خطوط داكنة ثقيلة تحتضن الأشكال."
    ]
   ],
   "signs": [
    "ضربات فرشاة سريعة محمّلة بنتوءات وحواف مسحوبة.",
    "طبعات خشبية فيها عروق الخشب وحبر غير متساوٍ قليلاً.",
    "ألوان سابقة تظهر تحت الطبقة العليا.",
    "خطوط قوية غير منتظمة رسمتها يد واثقة."
   ],
   "tools": [
    [
     "فرش شعر خشنة",
     "لضربات جريئة ذات ملمس."
    ],
    [
     "ألوان زيتية",
     "لون غني يحتفظ بالعلامات الكثيفة."
    ],
    [
     "سكين الرسم",
     "للطلاء الثقيل والكشط الحاد."
    ],
    [
     "لوح خشب وأزاميل",
     "لحفر الطبعات باليد."
    ],
    [
     "البكرة وحبر الطباعة",
     "لفرد الحبر بالتساوي على اللوح."
    ],
    [
     "القماش",
     "سطح متين للرسم المفعم بالطاقة."
    ]
   ],
   "time": "من بضع ساعات إلى بضعة أسابيع",
   "bestFor": "لمن يريد فناً بإحساس قوي وعاطفة صادقة.",
   "artists": [
    [
     "إدفارد مونك",
     "نرويجي، 1863–1944",
     "صارت لوحته الصرخة رمزاً لقلق العصر الحديث."
    ],
    [
     "إرنست لودفيغ كيرشنر",
     "ألماني، 1880–1938",
     "شارك في تأسيس جماعة الجسر ورسم شوارع المدينة المتوترة."
    ],
    [
     "فرانز مارك",
     "ألماني، 1880–1916",
     "رسم خيولاً زرقاء وحيوانات زاهية مليئة بالإحساس."
    ],
    [
     "كيته كولفيتس",
     "ألمانية، 1867–1945",
     "منحت طبعاتها ورسومها صوتاً للحزن والرحمة."
    ],
    [
     "ويليم دي كونينغ",
     "هولندي أمريكي، 1904–1997",
     "قاد التعبيرية التجريدية بضربات فرشاة عنيفة واسعة."
    ]
   ],
   "guide": [
    "ابحث عن ملمس فرشاة حقيقي ونتوءات حين يأتي الضوء من الجانب.",
    "في الطبعات الخشبية، ابحث عن عروق الخشب وفروق صغيرة في الحبر.",
    "اسأل عن تاريخ العمل. أعمال التعبيريين المشهورين تُقلّد كثيراً."
   ]
  }
 },
 {
  "id": "conceptual",
  "cat": "contemporary",
  "type": "conceptual",
  "level": "advanced",
  "en": {
   "name": "Conceptual Art",
   "feel": "Thoughtful",
   "essence": "The idea is the artwork, and the object carries it to you.",
   "story": [
    "In 1917, Marcel Duchamp showed an everyday object as art. He asked a big question: what makes something art?",
    "In the 1960s, Sol LeWitt and Joseph Kosuth put ideas before objects. Artists worked with words, instructions, photos and found things.",
    "Today Saudi artists lead with strong ideas. Ahmed Mater, Manal AlDowayan and Abdulnasser Gharem speak to the world. They join memory, heritage and modern life with great care."
   ],
   "tech": [
    [
     "Readymade",
     "An everyday object chosen and shown in a new light."
    ],
    [
     "Text art",
     "Words and sentences used as the artwork itself."
    ],
    [
     "Instructions",
     "Written steps that let the work be made again."
    ],
    [
     "Installation",
     "A whole space arranged to carry one idea."
    ],
    [
     "Documentation",
     "Photos, maps and notes that record the work."
    ],
    [
     "Research",
     "Long study of history, places or people behind the work."
    ]
   ],
   "signs": [
    "A certificate or signed instructions from the artist.",
    "Handwritten notes, sketches and plans that show the idea growing.",
    "Hand-made parts, like stitched, drawn or carefully assembled elements.",
    "A clear record of where and when the work was first shown."
   ],
   "tools": [
    [
     "Notebooks and sketches",
     "Where every idea is tested first."
    ],
    [
     "Camera",
     "To record actions, places and finished installations."
    ],
    [
     "Found objects",
     "Everyday things given new meaning."
    ],
    [
     "Signed certificate",
     "Proves the work and explains how to show it."
    ],
    [
     "Chosen materials",
     "Anything from iron to paper, picked for the idea."
    ]
   ],
   "time": "Several weeks to several months",
   "bestFor": "Collectors who love ideas, conversation and art that makes them think.",
   "artists": [
    [
     "Marcel Duchamp",
     "French, 1887–1968",
     "His readymades made the idea the heart of the artwork."
    ],
    [
     "Joseph Kosuth",
     "American, born 1945",
     "His One and Three Chairs asks how words, images and objects differ."
    ],
    [
     "Ai Weiwei",
     "Chinese, born 1957",
     "His hand-painted porcelain sunflower seeds filled Tate Modern in 2010."
    ],
    [
     "Ahmed Mater",
     "Saudi, born 1979",
     "A doctor and artist, his Magnetism series uses magnets and iron filings."
    ],
    [
     "Manal AlDowayan",
     "Saudi, born 1973",
     "Her work on memory and women’s voices represented Saudi Arabia at Venice in 2024."
    ],
    [
     "Abdulnasser Gharem",
     "Saudi, born 1973",
     "He co-founded Edge of Arabia and turns everyday symbols into powerful ideas."
    ]
   ],
   "guide": [
    "Ask for the certificate. In conceptual art, it can be the key document.",
    "Ask the artist to explain the idea. A real work has clear thinking.",
    "Make sure the installation instructions come with the work."
   ]
  },
  "ar": {
   "name": "الفن المفاهيمي",
   "feel": "متأمّل",
   "essence": "الفكرة هي العمل الفني، والشيء يحملها إليك.",
   "story": [
    "في عام 1917 عرض مارسيل دوشامب شيئاً يومياً بوصفه فناً. وطرح سؤالاً كبيراً: ما الذي يجعل الشيء فناً؟",
    "في الستينيات قدّم سول لويت وجوزيف كوسوث الفكرة على الشيء. وعمل الفنانون بالكلمات والتعليمات والصور والأشياء الموجودة.",
    "واليوم يقود فنانون سعوديون بأفكار قوية. أحمد ماطر ومنال الضويان وعبدالناصر غارم يخاطبون العالم. ويجمعون الذاكرة والتراث والحياة الحديثة بعناية كبيرة."
   ],
   "tech": [
    [
     "الجاهز",
     "شيء يومي يُختار ويُعرض في ضوء جديد."
    ],
    [
     "فن النص",
     "كلمات وجمل تصبح هي العمل الفني نفسه."
    ],
    [
     "التعليمات",
     "خطوات مكتوبة تسمح بصنع العمل من جديد."
    ],
    [
     "التركيب",
     "مساحة كاملة تُرتّب لتحمل فكرة واحدة."
    ],
    [
     "التوثيق",
     "صور وخرائط وملاحظات تسجّل العمل."
    ],
    [
     "البحث",
     "دراسة طويلة للتاريخ أو الأماكن أو الناس خلف العمل."
    ]
   ],
   "signs": [
    "شهادة أو تعليمات موقّعة من الفنان.",
    "ملاحظات بخط اليد واسكتشات وخطط تُظهر الفكرة وهي تنمو.",
    "أجزاء مصنوعة باليد، مخيطة أو مرسومة أو مركّبة بعناية.",
    "سجل واضح لمكان أول عرض للعمل وزمانه."
   ],
   "tools": [
    [
     "الدفاتر والاسكتشات",
     "حيث تُختبر كل فكرة أولاً."
    ],
    [
     "الكاميرا",
     "لتسجيل الأفعال والأماكن والأعمال التركيبية المكتملة."
    ],
    [
     "الأشياء الموجودة",
     "أشياء يومية تُمنح معنى جديداً."
    ],
    [
     "الشهادة الموقّعة",
     "تُثبت العمل وتشرح طريقة عرضه."
    ],
    [
     "مواد مختارة",
     "أي شيء من الحديد إلى الورق، يُختار لخدمة الفكرة."
    ]
   ],
   "time": "من عدة أسابيع إلى عدة شهور",
   "bestFor": "لمن يحب الأفكار والحوار والفن الذي يدفعه إلى التفكير.",
   "artists": [
    [
     "مارسيل دوشامب",
     "فرنسي، 1887–1968",
     "جعلت أعماله الجاهزة الفكرة قلب العمل الفني."
    ],
    [
     "جوزيف كوسوث",
     "أمريكي، مواليد 1945",
     "يسأل عمله كرسي واحد وثلاثة كراسٍ عن الفرق بين الكلمة والصورة والشيء."
    ],
    [
     "آي ويوي",
     "صيني، مواليد 1957",
     "ملأت بذور عباد الشمس الخزفية المرسومة باليد قاعة تيت مودرن عام 2010."
    ],
    [
     "أحمد ماطر",
     "سعودي، مواليد 1979",
     "طبيب وفنان، تستخدم سلسلته مغناطيسية المغناطيس وبرادة الحديد."
    ],
    [
     "منال الضويان",
     "سعودية، مواليد 1973",
     "مثّل عملها عن الذاكرة وأصوات النساء السعودية في بينالي البندقية 2024."
    ],
    [
     "عبدالناصر غارم",
     "سعودي، مواليد 1973",
     "شارك في تأسيس حافة الجزيرة العربية، ويحوّل الرموز اليومية إلى أفكار قوية."
    ]
   ],
   "guide": [
    "اطلب الشهادة. في الفن المفاهيمي قد تكون هي الوثيقة الأهم.",
    "اطلب من الفنان شرح الفكرة. العمل الحقيقي يحمل تفكيراً واضحاً.",
    "تأكد أن تعليمات التركيب تأتي مع العمل."
   ]
  }
 },
 {
  "id": "performance",
  "cat": "contemporary",
  "type": "performance",
  "level": "advanced",
  "en": {
   "name": "Performance Art",
   "feel": "Present",
   "essence": "Art made live, where the artist’s presence and time are the medium.",
   "story": [
    "In the 1950s, artists began making art as live action. In Japan, the Gutai group painted with their whole bodies. Kazuo Shiraga even painted with his feet.",
    "In America, Allan Kaprow staged Happenings that invited the audience in. In Germany, Joseph Beuys used talks and actions to teach. Tehching Hsieh spent whole years on single, patient works.",
    "In 2010, Marina Abramović sat silently at MoMA for over two months. Thousands of visitors sat across from her, one by one. Today performance lives in museums, festivals and public spaces."
   ],
   "tech": [
    [
     "Live action",
     "The artwork happens in front of people, in real time."
    ],
    [
     "Endurance",
     "Long, patient actions that test time and focus."
    ],
    [
     "Audience participation",
     "Visitors become part of the work."
    ],
    [
     "Action painting",
     "Painting made live, where the movement is part of the art."
    ],
    [
     "Scores",
     "Written plans that let a work be performed again."
    ],
    [
     "Documentation",
     "Photos and video that keep the moment alive."
    ]
   ],
   "signs": [
    "Original photos or video signed or certified by the artist.",
    "Objects used in the performance, with clear records.",
    "Handwritten scores, notes or drawings for the action.",
    "Paint traces made by movement, like Gutai foot paintings.",
    "A dated record of where the performance took place."
   ],
   "tools": [
    [
     "The artist’s presence",
     "The heart of every performance."
    ],
    [
     "Camera and video",
     "To record the moment for the future."
    ],
    [
     "Simple props",
     "Chairs, tables or objects that carry meaning."
    ],
    [
     "Space and light",
     "A room, stage or street chosen with care."
    ],
    [
     "Written score",
     "Instructions that guide the action."
    ],
    [
     "Paint and large paper",
     "For action paintings made live."
    ]
   ],
   "time": "A few minutes to a whole year",
   "bestFor": "Collectors and institutions who value living moments, history and bold ideas.",
   "artists": [
    [
     "Kazuo Shiraga",
     "Japanese, 1924–2008",
     "A Gutai member, he painted with his feet while hanging from a rope."
    ],
    [
     "Allan Kaprow",
     "American, 1927–2006",
     "He coined the word Happening and brought audiences into art."
    ],
    [
     "Joseph Beuys",
     "German, 1921–1986",
     "He saw teaching and talking as art, calling it social sculpture."
    ],
    [
     "Tehching Hsieh",
     "Taiwanese-American, born 1950",
     "He punched a time clock every hour for a whole year."
    ],
    [
     "Marina Abramović",
     "Serbian, born 1946",
     "Her silent work at MoMA in 2010 moved thousands of visitors."
    ],
    [
     "Laurie Anderson",
     "American, born 1947",
     "She blends music, spoken stories and invented instruments in live shows."
    ]
   ],
   "guide": [
    "Ask what you are buying: photos, video, objects or the right to restage.",
    "Make sure every item comes with a signed certificate from the artist.",
    "Check edition numbers on photos and videos. Small editions hold value better."
   ]
  },
  "ar": {
   "name": "فن الأداء",
   "feel": "حاضر",
   "essence": "فن يُصنع حياً، وحضور الفنان ووقته هما مادته.",
   "story": [
    "في الخمسينيات بدأ الفنانون صنع الفن فعلاً حياً. وفي اليابان رسمت جماعة غوتاي بأجسادها كلها. بل رسم كازو شيراغا بقدميه.",
    "وفي أمريكا قدّم آلان كابرو عروضاً دعت الجمهور إلى المشاركة. وفي ألمانيا علّم يوزف بويس بالحوار والأفعال. وأمضى تيهشينغ هسيه أعواماً كاملة في أعمال صبورة.",
    "في عام 2010 جلست مارينا أبراموفيتش صامتة في متحف موما أكثر من شهرين. وجلس أمامها آلاف الزوار، واحداً بعد آخر. واليوم يعيش فن الأداء في المتاحف والمهرجانات والأماكن العامة."
   ],
   "tech": [
    [
     "الفعل الحي",
     "يحدث العمل الفني أمام الناس في الوقت نفسه."
    ],
    [
     "التحمّل",
     "أفعال طويلة صبورة تختبر الوقت والتركيز."
    ],
    [
     "مشاركة الجمهور",
     "يصبح الزوار جزءاً من العمل."
    ],
    [
     "الرسم الحركي",
     "رسم يُصنع حياً، والحركة جزء من الفن."
    ],
    [
     "النوتات المكتوبة",
     "خطط مكتوبة تسمح بأداء العمل من جديد."
    ],
    [
     "التوثيق",
     "صور وفيديو تُبقي اللحظة حية."
    ]
   ],
   "signs": [
    "صور أو فيديو أصلية موقّعة أو موثّقة من الفنان.",
    "أشياء استُخدمت في العرض، مع سجلات واضحة.",
    "نوتات أو ملاحظات أو رسوم بخط اليد للفعل.",
    "آثار طلاء صنعتها الحركة، مثل لوحات غوتاي المرسومة بالقدم.",
    "سجل مؤرّخ لمكان تقديم العرض."
   ],
   "tools": [
    [
     "حضور الفنان",
     "قلب كل عرض أدائي."
    ],
    [
     "الكاميرا والفيديو",
     "لتسجيل اللحظة للمستقبل."
    ],
    [
     "أدوات بسيطة",
     "كراسٍ أو طاولات أو أشياء تحمل معنى."
    ],
    [
     "المكان والضوء",
     "غرفة أو مسرح أو شارع يُختار بعناية."
    ],
    [
     "النوتة المكتوبة",
     "تعليمات توجّه الفعل."
    ],
    [
     "الطلاء والورق الكبير",
     "للوحات الحركية المرسومة حياً."
    ]
   ],
   "time": "من بضع دقائق إلى عام كامل",
   "bestFor": "للمقتنين والمؤسسات الذين يقدّرون اللحظات الحية والتاريخ والأفكار الجريئة.",
   "artists": [
    [
     "كازو شيراغا",
     "ياباني، 1924–2008",
     "عضو في جماعة غوتاي، رسم بقدميه وهو معلّق بحبل."
    ],
    [
     "آلان كابرو",
     "أمريكي، 1927–2006",
     "صاغ مصطلح الهابنينغ وأدخل الجمهور إلى قلب الفن."
    ],
    [
     "يوزف بويس",
     "ألماني، 1921–1986",
     "رأى التعليم والحوار فناً، وسمّاه النحت الاجتماعي."
    ],
    [
     "تيهشينغ هسيه",
     "تايواني أمريكي، مواليد 1950",
     "ضغط على ساعة تسجيل الحضور كل ساعة طوال عام كامل."
    ],
    [
     "مارينا أبراموفيتش",
     "صربية، مواليد 1946",
     "أثّر عملها الصامت في متحف موما عام 2010 في آلاف الزوار."
    ],
    [
     "لوري أندرسون",
     "أمريكية، مواليد 1947",
     "تمزج الموسيقى والحكايات المروية وآلات من ابتكارها في عروض حية."
    ]
   ],
   "guide": [
    "اسأل عمّا تشتريه: صور أم فيديو أم أشياء أم حق إعادة العرض.",
    "تأكد أن كل قطعة تأتي بشهادة موقّعة من الفنان.",
    "افحص أرقام الإصدار على الصور والفيديو. الإصدارات الصغيرة تحفظ قيمتها أكثر."
   ]
  }
 },
 {
  "id": "landscape",
  "cat": "nature",
  "type": "landscape",
  "level": "beginner",
  "en": {
   "name": "Landscape & Seascape",
   "feel": "Calm",
   "essence": "The land and the sea, seen through one patient pair of eyes.",
   "story": [
    "People have painted the land for thousands of years. Chinese masters made mountains and rivers the heart of painting. Their scrolls invited the eye to wander slowly.",
    "In Europe, landscape became its own subject in the 1600s. Constable painted English fields with honest weather. Turner and Hokusai gave the sea its full power.",
    "Today, artists still carry easels outdoors to paint from life. Many paint deserts, mountains and coasts across the Arab world. Each scene holds a real moment of light."
   ],
   "tech": [
    [
     "Plein air",
     "Painting outdoors, in front of the real scene."
    ],
    [
     "Atmospheric perspective",
     "Making far hills paler and bluer to show distance."
    ],
    [
     "Rule of thirds",
     "Placing the horizon off center for a calmer balance."
    ],
    [
     "Underpainting",
     "A thin first layer that sets the light and dark."
    ],
    [
     "Scumbling",
     "Dragging dry, light paint over a layer for haze and mist."
    ],
    [
     "Broken color",
     "Small touches of pure color that blend in the eye."
    ]
   ],
   "signs": [
    "Brushstrokes that change direction with the shape of hills, waves or clouds.",
    "Small changes of mind, such as a moved tree or a repainted horizon.",
    "Grit, sand or dust caught in paint made outdoors.",
    "Layers of paint you can see at the edges of the canvas.",
    "Light that matches one time of day, as if truly observed."
   ],
   "tools": [
    [
     "Field easel",
     "A folding stand for painting outdoors."
    ],
    [
     "Pochade box",
     "A small box that holds paints and a panel."
    ],
    [
     "Oil or watercolor paints",
     "The main media for most landscapes."
    ],
    [
     "Bristle brushes",
     "Stiff brushes for texture in skies and fields."
    ],
    [
     "Palette knife",
     "For mixing and for laying thick strokes."
    ],
    [
     "Viewfinder",
     "A small frame that helps choose the scene."
    ],
    [
     "Linen or cotton canvas",
     "The surface for studio landscapes."
    ]
   ],
   "time": "A few hours to several weeks",
   "bestFor": "Collectors who want a calm window onto a real place.",
   "artists": [
    [
     "John Constable",
     "British, 1776–1837",
     "He painted the English countryside with true weather and honest skies."
    ],
    [
     "J. M. W. Turner",
     "British, 1775–1851",
     "He turned storms, seas and sunsets into rivers of light."
    ],
    [
     "Caspar David Friedrich",
     "German, 1774–1840",
     "His lonely figures before mountains and seas made landscape feel quiet and deep."
    ],
    [
     "Thomas Cole",
     "American, 1801–1848",
     "He founded the Hudson River School, celebrating America’s wild valleys."
    ],
    [
     "Katsushika Hokusai",
     "Japanese, 1760–1849",
     "His Great Wave off Kanagawa is one of the most loved images of the sea."
    ]
   ],
   "guide": [
    "Look for paint built up in layers. A print has no raised strokes.",
    "Ask where and when it was painted. A real artist remembers the place.",
    "Check the back of the canvas for the artist’s signature, date and title."
   ]
  },
  "ar": {
   "name": "المناظر الطبيعية والبحرية",
   "feel": "هادئ",
   "essence": "الأرض والبحر كما تراهما عين فنان صبور.",
   "story": [
    "رسم الإنسان الأرض منذ آلاف السنين. وجعل أساتذة الصين الجبال والأنهار قلب الرسم. ودعت لفائفهم العين إلى التجوّل ببطء.",
    "في أوروبا صار المنظر الطبيعي موضوعاً مستقلاً في القرن السابع عشر. رسم كونستابل حقول إنجلترا بطقسها الصادق. ومنح تيرنر وهوكوساي البحر قوته الكاملة.",
    "اليوم ما زال الفنانون يحملون حواملهم إلى الخارج ليرسموا من الواقع. ويرسم كثيرون الصحارى والجبال والسواحل في العالم العربي. وكل مشهد يحمل لحظة ضوء حقيقية."
   ],
   "tech": [
    [
     "الرسم في الهواء الطلق",
     "الرسم في الخارج أمام المشهد الحقيقي."
    ],
    [
     "المنظور الجوي",
     "جعل التلال البعيدة أفتح وأكثر زرقة لإظهار المسافة."
    ],
    [
     "قاعدة الأثلاث",
     "وضع الأفق بعيداً عن المنتصف لتوازن أهدأ."
    ],
    [
     "الطبقة التحضيرية",
     "طبقة أولى رقيقة تحدد الضوء والظل."
    ],
    [
     "السحب الجاف",
     "سحب لون فاتح جاف فوق طبقة لصنع الضباب والغبش."
    ],
    [
     "اللون المتكسّر",
     "لمسات صغيرة من لون نقي تمتزج في العين."
    ]
   ],
   "signs": [
    "ضربات فرشاة يتغير اتجاهها مع شكل التلال والأمواج والغيوم.",
    "تعديلات صغيرة، كشجرة نُقلت أو أفق أُعيد رسمه.",
    "حبيبات رمل أو غبار علقت في لون رُسم في الخارج.",
    "طبقات لون تظهر عند حواف القماش.",
    "ضوء يطابق وقتاً واحداً من النهار، كأنه شوهد فعلاً."
   ],
   "tools": [
    [
     "حامل ميداني",
     "حامل قابل للطي للرسم في الخارج."
    ],
    [
     "صندوق الرسم الصغير",
     "صندوق يحمل الألوان ولوحاً للرسم."
    ],
    [
     "ألوان زيتية أو مائية",
     "الوسيطان الأساسيان لأغلب المناظر."
    ],
    [
     "فرش من الشعر الخشن",
     "فرش قاسية لملمس السماء والحقول."
    ],
    [
     "سكين الرسم",
     "للخلط ولوضع ضربات سميكة."
    ],
    [
     "إطار الرؤية",
     "إطار صغير يساعد على اختيار المشهد."
    ],
    [
     "قماش كتان أو قطن",
     "سطح اللوحات المرسومة في المرسم."
    ]
   ],
   "time": "من بضع ساعات إلى عدة أسابيع",
   "bestFor": "لمن يريد نافذة هادئة تطل على مكان حقيقي.",
   "artists": [
    [
     "جون كونستابل",
     "بريطاني، 1776–1837",
     "رسم ريف إنجلترا بطقسه الحقيقي وسمائه الصادقة."
    ],
    [
     "ويليام تيرنر",
     "بريطاني، 1775–1851",
     "حوّل العواصف والبحار والغروب إلى أنهار من الضوء."
    ],
    [
     "كاسبار ديفيد فريدريش",
     "ألماني، 1774–1840",
     "جعلت شخوصه الوحيدة أمام الجبال والبحار المنظر هادئاً وعميقاً."
    ],
    [
     "توماس كول",
     "أمريكي، 1801–1848",
     "أسس مدرسة نهر هدسون احتفاءً بوديان أمريكا البرية."
    ],
    [
     "كاتسوشيكا هوكوساي",
     "ياباني، 1760–1849",
     "موجته العظيمة قبالة كاناغاوا من أحب صور البحر في العالم."
    ]
   ],
   "guide": [
    "ابحث عن لون مبني في طبقات. المطبوعة بلا ضربات بارزة.",
    "اسأل أين ومتى رُسمت. الفنان الحقيقي يتذكر المكان.",
    "افحص ظهر القماش بحثاً عن توقيع الفنان والتاريخ والعنوان."
   ]
  }
 },
 {
  "id": "portrait",
  "cat": "nature",
  "type": "portrait",
  "level": "intermediate",
  "en": {
   "name": "Portrait & Figure Art",
   "feel": "Intimate",
   "essence": "A real face, painted by someone who truly looked.",
   "story": [
    "Portraits are among the oldest human images. Painters in Roman Egypt made lifelike faces in the Fayum portraits. Persian and Mughal painters drew courtiers with fine brushes.",
    "In Europe, Rembrandt and Vermeer gave ordinary faces deep feeling. Later, photography freed painters to look further. They painted character, not only likeness.",
    "Today, many artists paint from life sittings. They study one face for hours. Arab painters like Mahmoud Saïd made portraits full of warmth and place."
   ],
   "tech": [
    [
     "Life sitting",
     "Painting a real person who sits in front of the artist."
    ],
    [
     "Sight-size",
     "Placing the canvas beside the sitter to compare at one glance."
    ],
    [
     "Proportion measuring",
     "Using a pencil held out to check distances on the face."
    ],
    [
     "Chiaroscuro",
     "Strong light and shadow that shape the face."
    ],
    [
     "Skin glazing",
     "Thin warm and cool layers that make skin glow."
    ],
    [
     "Gesture drawing",
     "Quick lines that catch the pose of the whole figure."
    ]
   ],
   "signs": [
    "Eyes that feel alive, with small highlights placed by hand.",
    "Visible corrections around the jaw, hands or hairline.",
    "Brushstrokes that follow the shape of the cheek and brow.",
    "Small differences from any known photo of the sitter.",
    "Preparatory sketches or studies the artist can show you."
   ],
   "tools": [
    [
     "Oil paints",
     "The classic medium for rich skin tones."
    ],
    [
     "Charcoal or graphite",
     "For the first drawing and quick studies."
    ],
    [
     "Filbert brushes",
     "Rounded flat brushes for soft skin edges."
    ],
    [
     "Mahl stick",
     "A rest that steadies the hand for fine details."
    ],
    [
     "Mirror",
     "For self-portraits and for checking the drawing reversed."
    ],
    [
     "Plumb line",
     "A weighted string to check the figure stands straight."
    ],
    [
     "Primed canvas or panel",
     "A smooth surface for fine detail."
    ]
   ],
   "time": "Several days to several weeks",
   "bestFor": "Families and collectors who want a lasting, personal presence on their wall.",
   "artists": [
    [
     "Rembrandt van Rijn",
     "Dutch, 1606–1669",
     "His self-portraits show a lifetime of feeling in light and shadow."
    ],
    [
     "Johannes Vermeer",
     "Dutch, 1632–1675",
     "His Girl with a Pearl Earring glows with quiet, soft light."
    ],
    [
     "Élisabeth Vigée Le Brun",
     "French, 1755–1842",
     "She painted hundreds of warm, lively portraits across Europe."
    ],
    [
     "John Singer Sargent",
     "American, 1856–1925",
     "His fast, confident brushwork made his sitters feel alive."
    ],
    [
     "Mahmoud Saïd",
     "Egyptian, 1897–1964",
     "He painted Egyptian faces with warmth, golden light and strong form."
    ],
    [
     "Frida Kahlo",
     "Mexican, 1907–1954",
     "Her honest self-portraits turned her own story into art."
    ]
   ],
   "guide": [
    "Ask to see sketches or studies. Portrait artists usually keep them.",
    "Look closely at the eyes and hands. They show a painter’s true skill.",
    "For a commission, agree on sittings or your own photos in advance."
   ]
  },
  "ar": {
   "name": "البورتريه ورسم الشخوص",
   "feel": "حميم",
   "essence": "وجه حقيقي رسمه من أطال النظر إليه.",
   "story": [
    "البورتريه من أقدم الصور عند الإنسان. رسم فنانو مصر الرومانية وجوهاً نابضة في بورتريهات الفيوم. ورسم فنانو فارس والهند المغولية رجال البلاط بفرش دقيقة.",
    "في أوروبا منح رامبرانت وفيرمير الوجوه العادية مشاعر عميقة. ثم حرّر التصوير الرسامين لينظروا أبعد. فرسموا الشخصية لا الشبه فقط.",
    "اليوم يرسم كثير من الفنانين من جلسات حية. يدرسون وجهاً واحداً لساعات. ورسم فنانون عرب مثل محمود سعيد بورتريهات مليئة بالدفء والمكان."
   ],
   "tech": [
    [
     "الجلسة الحية",
     "رسم شخص حقيقي يجلس أمام الفنان."
    ],
    [
     "طريقة الحجم المرئي",
     "وضع اللوحة بجانب الجالس للمقارنة بنظرة واحدة."
    ],
    [
     "قياس النسب",
     "استخدام قلم ممدود لقياس المسافات في الوجه."
    ],
    [
     "الضوء والظل",
     "تباين قوي بين الضوء والظل يشكّل الوجه."
    ],
    [
     "تزجيج البشرة",
     "طبقات رقيقة دافئة وباردة تجعل البشرة تتوهج."
    ],
    [
     "الرسم الحركي",
     "خطوط سريعة تلتقط وضعية الجسم كله."
    ]
   ],
   "signs": [
    "عيون تبدو حية، فيها لمعات صغيرة وضعتها اليد.",
    "تصحيحات ظاهرة حول الفك أو اليدين أو خط الشعر.",
    "ضربات فرشاة تتبع شكل الخد والحاجب.",
    "اختلافات صغيرة عن أي صورة معروفة للشخص.",
    "رسوم تحضيرية أو دراسات يستطيع الفنان أن يريك إياها."
   ],
   "tools": [
    [
     "ألوان زيتية",
     "الوسيط الكلاسيكي لدرجات البشرة الغنية."
    ],
    [
     "فحم أو رصاص",
     "للرسم الأول والدراسات السريعة."
    ],
    [
     "فرش لوزية",
     "فرش مسطحة مستديرة الطرف لحواف البشرة الناعمة."
    ],
    [
     "عصا الإسناد",
     "مسند يثبّت اليد عند التفاصيل الدقيقة."
    ],
    [
     "مرآة",
     "للبورتريه الذاتي ولفحص الرسم معكوساً."
    ],
    [
     "خيط الشاقول",
     "خيط بثقل للتأكد من استقامة الشخص."
    ],
    [
     "قماش أو لوح مجهّز",
     "سطح أملس للتفاصيل الدقيقة."
    ]
   ],
   "time": "من عدة أيام إلى عدة أسابيع",
   "bestFor": "للعائلات والمقتنين الذين يريدون حضوراً شخصياً دائماً على جدرانهم.",
   "artists": [
    [
     "رامبرانت فان راين",
     "هولندي، 1606–1669",
     "تكشف صوره الذاتية عمراً كاملاً من المشاعر بالضوء والظل."
    ],
    [
     "يوهانس فيرمير",
     "هولندي، 1632–1675",
     "تتوهج لوحته الفتاة ذات القرط اللؤلؤي بضوء هادئ وناعم."
    ],
    [
     "إليزابيث فيجيه لوبران",
     "فرنسية، 1755–1842",
     "رسمت مئات البورتريهات الدافئة المفعمة بالحياة في أنحاء أوروبا."
    ],
    [
     "جون سينجر سارجنت",
     "أمريكي، 1856–1925",
     "جعلت ضرباته السريعة الواثقة شخوصه تنبض بالحياة."
    ],
    [
     "محمود سعيد",
     "مصري، 1897–1964",
     "رسم الوجوه المصرية بدفء وضوء ذهبي وشكل قوي."
    ],
    [
     "فريدا كاهلو",
     "مكسيكية، 1907–1954",
     "حوّلت بورتريهاتها الذاتية الصادقة قصتها إلى فن."
    ]
   ],
   "guide": [
    "اطلب رؤية الرسوم التحضيرية. رسامو البورتريه يحتفظون بها عادة.",
    "تأمل العيون واليدين. فيها تظهر مهارة الرسام الحقيقية.",
    "عند الطلب الخاص، اتفقا مسبقاً على الجلسات أو على صورك الخاصة."
   ]
  }
 },
 {
  "id": "botanical",
  "cat": "nature",
  "type": "botanical",
  "level": "intermediate",
  "en": {
   "name": "Botanical Art",
   "feel": "Delicate",
   "essence": "Plants drawn with love, accuracy and endless patience.",
   "story": [
    "Botanical art began as a way to know healing plants. Dioscorides wrote a famous Greek plant book. Scholars in the Islamic world later copied it in Arabic with painted plants.",
    "Explorers later sailed with artists to record new plants. Maria Sibylla Merian painted plants with the insects that live on them. Redouté made roses famous.",
    "Today, botanical artists still work from living plants. They count petals and measure leaves. Their work serves science and delights every plant lover."
   ],
   "tech": [
    [
     "Observational drawing",
     "Drawing from a living plant, never from memory."
    ],
    [
     "Measured drawing",
     "Using dividers to copy true sizes onto the paper."
    ],
    [
     "Dry brush",
     "Fine strokes with little water for veins and hairs."
    ],
    [
     "Layered washes",
     "Thin watercolor layers that build rich, true color."
    ],
    [
     "Dissection studies",
     "Small drawings of seeds, petals and cross sections."
    ],
    [
     "Graphite tonal study",
     "A pencil study that plans the light before color."
    ]
   ],
   "signs": [
    "True details a botanist could check, like petal counts and leaf veins.",
    "Fine pencil guidelines still faint under the paint.",
    "Tiny brush marks visible under a magnifier, never printer dots.",
    "Slight changes between leaves, because each was observed separately.",
    "Studies or a sketchbook of the same plant from life."
   ],
   "tools": [
    [
     "Hot-pressed watercolor paper",
     "Smooth paper for very fine detail."
    ],
    [
     "Fine round brushes",
     "Small pointed brushes for veins and edges."
    ],
    [
     "Dividers",
     "A measuring tool to copy exact sizes."
    ],
    [
     "Magnifying glass",
     "For studying hairs, seeds and tiny parts."
    ],
    [
     "Graphite pencils",
     "For the careful first drawing."
    ],
    [
     "Watercolor paints",
     "The classic medium for clear, true color."
    ],
    [
     "Living specimen",
     "The real plant, kept fresh in water."
    ]
   ],
   "time": "Several days to several weeks",
   "bestFor": "Nature lovers and collectors who enjoy quiet detail and calm beauty.",
   "artists": [
    [
     "Maria Sibylla Merian",
     "German, 1647–1717",
     "She painted plants and insects together, showing how they live."
    ],
    [
     "Pierre-Joseph Redouté",
     "Belgian-French, 1759–1840",
     "His paintings of roses and lilies are still loved worldwide."
    ],
    [
     "Ferdinand Bauer",
     "Austrian, 1760–1826",
     "He drew the plants of Australia with amazing accuracy."
    ],
    [
     "Margaret Mee",
     "British, 1909–1988",
     "She painted rare Amazon plants on journeys deep into the rainforest."
    ],
    [
     "Rory McEwen",
     "Scottish, 1932–1982",
     "He painted single flowers and leaves on vellum with fresh, modern grace."
    ]
   ],
   "guide": [
    "Use a magnifier. Real paint shows brush marks, not printer dots.",
    "Ask which plant it is. A botanical artist will know its full name.",
    "Ask if the artist painted from a living plant. Most proudly do."
   ]
  },
  "ar": {
   "name": "الرسم النباتي",
   "feel": "رقيق",
   "essence": "نباتات مرسومة بحب ودقة وصبر لا ينتهي.",
   "story": [
    "بدأ الرسم النباتي وسيلة لمعرفة نباتات الدواء. كتب ديسقوريدوس كتاباً يونانياً شهيراً عن النبات. ثم نسخه علماء العالم الإسلامي بالعربية مع رسوم ملونة للنباتات.",
    "لاحقاً أبحر المستكشفون مع فنانين لتسجيل نباتات جديدة. رسمت ماريا سيبيلا ميريان النباتات مع الحشرات التي تعيش عليها. وجعل ريدوتيه الورود مشهورة.",
    "اليوم ما زال الفنانون النباتيون يرسمون من نباتات حية. يعدّون البتلات ويقيسون الأوراق. وأعمالهم تخدم العلم وتسعد كل محب للنبات."
   ],
   "tech": [
    [
     "الرسم بالملاحظة",
     "الرسم من نبات حي، لا من الذاكرة."
    ],
    [
     "الرسم المقاس",
     "استخدام الفرجار لنقل المقاسات الحقيقية إلى الورق."
    ],
    [
     "الفرشاة الجافة",
     "ضربات دقيقة بماء قليل للعروق والشعيرات."
    ],
    [
     "الطبقات المائية",
     "طبقات شفافة تبني لوناً غنياً وصادقاً."
    ],
    [
     "دراسات التشريح",
     "رسوم صغيرة للبذور والبتلات والمقاطع."
    ],
    [
     "دراسة الظل بالرصاص",
     "دراسة بالقلم تخطط للضوء قبل اللون."
    ]
   ],
   "signs": [
    "تفاصيل صحيحة يستطيع عالم نبات التحقق منها، كعدد البتلات وعروق الأوراق.",
    "خطوط رصاص دقيقة ما زالت باهتة تحت اللون.",
    "آثار فرشاة صغيرة تظهر تحت المكبّر، لا نقاط طباعة.",
    "اختلافات طفيفة بين الأوراق، لأن كل ورقة رُسمت بالملاحظة.",
    "دراسات أو دفتر رسم للنبات نفسه من الواقع."
   ],
   "tools": [
    [
     "ورق مائي مضغوط على الساخن",
     "ورق أملس للتفاصيل الدقيقة جداً."
    ],
    [
     "فرش دائرية دقيقة",
     "فرش صغيرة مدببة للعروق والحواف."
    ],
    [
     "فرجار",
     "أداة قياس لنقل المقاسات بدقة."
    ],
    [
     "عدسة مكبّرة",
     "لدراسة الشعيرات والبذور والأجزاء الصغيرة."
    ],
    [
     "أقلام رصاص",
     "للرسم الأول الدقيق."
    ],
    [
     "ألوان مائية",
     "الوسيط الكلاسيكي للون صافٍ وصادق."
    ],
    [
     "عيّنة حية",
     "النبات الحقيقي، محفوظاً طازجاً في الماء."
    ]
   ],
   "time": "من عدة أيام إلى عدة أسابيع",
   "bestFor": "لمحبي الطبيعة والمقتنين الذين يستمتعون بالتفاصيل الهادئة والجمال الساكن.",
   "artists": [
    [
     "ماريا سيبيلا ميريان",
     "ألمانية، 1647–1717",
     "رسمت النباتات والحشرات معاً، وأظهرت كيف تعيش."
    ],
    [
     "بيير جوزيف ريدوتيه",
     "بلجيكي فرنسي، 1759–1840",
     "ما زالت لوحاته للورود والزنابق محبوبة في العالم كله."
    ],
    [
     "فرديناند باور",
     "نمساوي، 1760–1826",
     "رسم نباتات أستراليا بدقة مذهلة."
    ],
    [
     "مارغريت مي",
     "بريطانية، 1909–1988",
     "رسمت نباتات الأمازون النادرة في رحلات إلى عمق الغابة."
    ],
    [
     "روري ماك إيوين",
     "اسكتلندي، 1932–1982",
     "رسم زهوراً وأوراقاً مفردة على الرق برشاقة حديثة."
    ]
   ],
   "guide": [
    "استخدم مكبّراً. اللون الحقيقي يُظهر أثر الفرشاة لا نقاط الطباعة.",
    "اسأل عن اسم النبات. الفنان النباتي يعرف اسمه الكامل.",
    "اسأل إن كان الفنان رسم من نبات حي. أغلبهم يفخرون بذلك."
   ]
  }
 },
 {
  "id": "animal",
  "cat": "nature",
  "type": "animal",
  "level": "intermediate",
  "en": {
   "name": "Animal Art",
   "feel": "Alive",
   "essence": "The spirit of animals, caught by a patient, loving hand.",
   "story": [
    "Some of the oldest art in history shows animals. Ancient hunters painted horses and bulls on cave walls. In the Hail region, people carved camels and ibex into rock.",
    "Arabian horses and falcons have long been treasured in Arab culture. Painters of old manuscripts showed riders hunting with falcons. Later, George Stubbs painted horses of Arabian descent.",
    "Today, wildlife artists travel far to watch animals in the wild. Others paint beloved horses, falcons and pets. Each work shows long hours of patient looking."
   ],
   "tech": [
    [
     "Field sketching",
     "Quick drawings of living animals as they move."
    ],
    [
     "Anatomy study",
     "Learning bones and muscles so the pose feels true."
    ],
    [
     "Fur and feather texture",
     "Small directional strokes that follow how hair grows."
    ],
    [
     "Eye highlight",
     "A tiny spot of light that brings the animal to life."
    ],
    [
     "Gesture lines",
     "One flowing line that captures movement first."
    ],
    [
     "Negative painting",
     "Painting around a shape so pale fur or feathers glow."
    ]
   ],
   "signs": [
    "Fur or feathers that follow the true direction of growth.",
    "Small sketch lines or corrections around legs and wings.",
    "Anatomy that feels right, such as a horse’s joints or a falcon’s talons.",
    "Field sketches or studies of the same animal.",
    "Brushwork that changes between soft fur and hard hooves or beaks."
   ],
   "tools": [
    [
     "Sketchbook",
     "For quick drawings of animals in motion."
    ],
    [
     "Binoculars",
     "To watch wild birds and animals from a distance."
    ],
    [
     "Graphite and charcoal",
     "For gesture drawings and studies."
    ],
    [
     "Oil or acrylic paints",
     "For rich color and texture in finished works."
    ],
    [
     "Rigger brush",
     "A long thin brush for whiskers and fine hairs."
    ],
    [
     "Watercolor set",
     "Light and quick for outdoor studies."
    ],
    [
     "Anatomy books",
     "References for bones, muscles and feathers."
    ]
   ],
   "time": "Several days to a few weeks",
   "bestFor": "Animal lovers, horse owners and falconers who want a living spirit at home.",
   "artists": [
    [
     "George Stubbs",
     "British, 1724–1806",
     "His deep study of horse anatomy made his horses feel truly alive."
    ],
    [
     "Rosa Bonheur",
     "French, 1822–1899",
     "Her Horse Fair showed animals with strength and honest detail."
    ],
    [
     "John James Audubon",
     "French-American, 1785–1851",
     "His Birds of America painted birds at life size in their habitats."
    ],
    [
     "Xu Beihong",
     "Chinese, 1895–1953",
     "His galloping ink horses became symbols of energy and spirit."
    ],
    [
     "Albrecht Dürer",
     "German, 1471–1528",
     "His Young Hare shows every hair, drawn with deep care."
    ]
   ],
   "guide": [
    "Check the anatomy. Real study shows in the joints, eyes and posture.",
    "Ask to see field sketches. Animal artists usually keep many.",
    "Look closely at fur or feathers. Hand strokes vary, printed textures repeat."
   ]
  },
  "ar": {
   "name": "رسم الحيوانات",
   "feel": "نابض",
   "essence": "روح الحيوان، تلتقطها يد صبورة محبة.",
   "story": [
    "من أقدم الفنون في التاريخ صور الحيوانات. رسم الصيادون القدماء الخيول والثيران على جدران الكهوف. وفي منطقة حائل نقش الناس الجمال والوعول على الصخور.",
    "للخيل العربية والصقور مكانة عزيزة في الثقافة العربية منذ زمن طويل. صوّر رسامو المخطوطات القديمة فرساناً يصطادون بالصقور. ثم رسم جورج ستبس خيولاً من أصول عربية.",
    "اليوم يسافر فنانو الحياة البرية بعيداً لمراقبة الحيوانات في الطبيعة. ويرسم آخرون الخيول والصقور والحيوانات الأليفة المحبوبة. وكل عمل يحمل ساعات طويلة من التأمل الصبور."
   ],
   "tech": [
    [
     "الرسم الميداني",
     "رسوم سريعة لحيوانات حية وهي تتحرك."
    ],
    [
     "دراسة التشريح",
     "تعلّم العظام والعضلات لتبدو الوضعية صادقة."
    ],
    [
     "ملمس الفرو والريش",
     "ضربات صغيرة تتبع اتجاه نمو الشعر."
    ],
    [
     "لمعة العين",
     "نقطة ضوء صغيرة تبث الحياة في الحيوان."
    ],
    [
     "خطوط الحركة",
     "خط واحد منساب يلتقط الحركة أولاً."
    ],
    [
     "الرسم السلبي",
     "الرسم حول الشكل ليتوهج الفرو أو الريش الفاتح."
    ]
   ],
   "signs": [
    "فرو أو ريش يتبع الاتجاه الحقيقي للنمو.",
    "خطوط رسم صغيرة أو تصحيحات حول الأرجل والأجنحة.",
    "تشريح يبدو صحيحاً، كمفاصل الحصان أو مخالب الصقر.",
    "رسوم ميدانية أو دراسات للحيوان نفسه.",
    "ضربات فرشاة تتغير بين الفرو الناعم والحوافر أو المناقير الصلبة."
   ],
   "tools": [
    [
     "دفتر رسم",
     "لرسوم سريعة للحيوانات وهي تتحرك."
    ],
    [
     "منظار",
     "لمراقبة الطيور والحيوانات البرية من بعيد."
    ],
    [
     "رصاص وفحم",
     "لرسوم الحركة والدراسات."
    ],
    [
     "ألوان زيتية أو أكريليك",
     "للون غني وملمس واضح في الأعمال النهائية."
    ],
    [
     "فرشاة رفيعة طويلة",
     "فرشاة دقيقة للشوارب والشعيرات."
    ],
    [
     "علبة ألوان مائية",
     "خفيفة وسريعة للدراسات في الخارج."
    ],
    [
     "كتب التشريح",
     "مراجع للعظام والعضلات والريش."
    ]
   ],
   "time": "من عدة أيام إلى بضعة أسابيع",
   "bestFor": "لمحبي الحيوانات وأصحاب الخيل والصقّارين الذين يريدون روحاً حية في بيوتهم.",
   "artists": [
    [
     "جورج ستبس",
     "بريطاني، 1724–1806",
     "جعلته دراسته العميقة لتشريح الخيل يرسم خيولاً تنبض بالحياة."
    ],
    [
     "روزا بونور",
     "فرنسية، 1822–1899",
     "أظهرت لوحتها سوق الخيل الحيوانات بقوة وتفاصيل صادقة."
    ],
    [
     "جون جيمس أودوبون",
     "فرنسي أمريكي، 1785–1851",
     "رسم في كتابه طيور أمريكا الطيور بحجمها الحقيقي في بيئاتها."
    ],
    [
     "شو بيهونغ",
     "صيني، 1895–1953",
     "صارت خيوله الراكضة بالحبر رمزاً للطاقة والروح."
    ],
    [
     "ألبرخت دورر",
     "ألماني، 1471–1528",
     "تُظهر لوحته الأرنب الصغير كل شعرة مرسومة بعناية عميقة."
    ]
   ],
   "guide": [
    "افحص التشريح. الدراسة الحقيقية تظهر في المفاصل والعيون والوقفة.",
    "اطلب رؤية الرسوم الميدانية. فنانو الحيوانات يحتفظون بالكثير منها.",
    "تأمل الفرو أو الريش. ضربات اليد تتنوع، والملمس المطبوع يتكرر."
   ]
  }
 },
 {
  "id": "marine",
  "cat": "nature",
  "type": "marine",
  "level": "advanced",
  "en": {
   "name": "Marine Art",
   "feel": "Adventurous",
   "essence": "Ships, harbors and waves, painted by someone who knows the sea.",
   "story": [
    "People who live by the sea have always painted it. Ancient sailors drew boats on pots and walls. Ships also appear in old Arab manuscripts, like the Maqamat of al-Hariri.",
    "Marine painting grew strong in the Dutch Golden Age. The van de Velde family sketched real ships at sea. Later, Aivazovsky painted waves glowing with moonlight.",
    "In the Gulf, wooden dhows carried pearl divers for centuries. Artists across the region still paint these boats with pride. Today, marine artists study rigging, tides and weather closely."
   ],
   "tech": [
    [
     "Wave anatomy",
     "Understanding how waves rise, break and foam."
    ],
    [
     "Rigging drawing",
     "Drawing ropes, masts and sails with true structure."
    ],
    [
     "Wet-into-wet skies",
     "Soft blended skies that set the weather and mood."
    ],
    [
     "Glazed water",
     "Thin layers that make the sea look deep and clear."
    ],
    [
     "Foam highlights",
     "Thick light paint placed last on breaking waves."
    ],
    [
     "Reflection painting",
     "Mirrored shapes broken by ripples in harbor water."
    ]
   ],
   "signs": [
    "Rigging and hulls drawn with real knowledge of boats.",
    "Thick foam strokes raised above smoother water.",
    "Waves and wind that agree with the sails and flags.",
    "Sketches of real ships or harbors the artist visited.",
    "Small corrections in the masts or the horizon line."
   ],
   "tools": [
    [
     "Oil paints",
     "The traditional medium for deep, glowing water."
    ],
    [
     "Long flat brushes",
     "For broad skies and sweeping seas."
    ],
    [
     "Rigger brush",
     "A long thin brush for ropes and rigging."
    ],
    [
     "Palette knife",
     "For thick foam and rough waves."
    ],
    [
     "Ship plans and models",
     "References for correct hulls and sails."
    ],
    [
     "Sketchbook",
     "For drawing boats and harbors from life."
    ]
   ],
   "time": "Several days to several weeks",
   "bestFor": "Sea lovers, sailors and families from coastal cities who feel at home by the water.",
   "artists": [
    [
     "Willem van de Velde the Younger",
     "Dutch, 1633–1707",
     "He painted warships and calm seas with remarkable accuracy."
    ],
    [
     "Ivan Aivazovsky",
     "Russian, 1817–1900",
     "He painted thousands of seas, many glowing with moonlight or storm."
    ],
    [
     "Fitz Henry Lane",
     "American, 1804–1865",
     "His calm harbor scenes glow with clear, soft light."
    ],
    [
     "Winslow Homer",
     "American, 1836–1910",
     "His late seascapes show the raw power of the Atlantic."
    ],
    [
     "Montague Dawson",
     "British, 1890–1973",
     "He painted tall sailing ships racing through bright, rolling seas."
    ]
   ],
   "guide": [
    "Check the rigging. Real marine artists draw ropes and sails that make sense.",
    "Tilt the painting. Hand-painted foam stands slightly above the surface.",
    "Ask about the ship or harbor. Serious artists know the boat they painted."
   ]
  },
  "ar": {
   "name": "الرسم البحري",
   "feel": "مغامر",
   "essence": "سفن وموانئ وأمواج، يرسمها من يعرف البحر.",
   "story": [
    "من عاش قرب البحر رسمه دائماً. رسم البحارة القدماء القوارب على الفخار والجدران. وتظهر السفن في مخطوطات عربية قديمة، مثل مقامات الحريري.",
    "ازدهر الرسم البحري في العصر الذهبي الهولندي. رسمت عائلة فان دي فيلده سفناً حقيقية في البحر. ثم رسم أيفازوفسكي أمواجاً تتوهج بضوء القمر.",
    "في الخليج حملت السفن الخشبية غواصي اللؤلؤ لقرون. وما زال فنانو المنطقة يرسمونها بفخر. واليوم يدرس الرسامون البحريون الحبال والمد والطقس عن قرب."
   ],
   "tech": [
    [
     "تشريح الموجة",
     "فهم كيف ترتفع الأمواج وتنكسر وتزبد."
    ],
    [
     "رسم الحبال والصواري",
     "رسم الحبال والصواري والأشرعة ببنية صحيحة."
    ],
    [
     "السماء الرطبة",
     "سماء ناعمة ممتزجة تحدد الطقس والمزاج."
    ],
    [
     "تزجيج الماء",
     "طبقات رقيقة تجعل البحر عميقاً وصافياً."
    ],
    [
     "لمعات الزبد",
     "لون فاتح سميك يوضع أخيراً على الأمواج المتكسرة."
    ],
    [
     "رسم الانعكاس",
     "أشكال معكوسة تكسرها التموجات في ماء الميناء."
    ]
   ],
   "signs": [
    "حبال وأجسام سفن مرسومة بمعرفة حقيقية بالقوارب.",
    "ضربات زبد سميكة بارزة فوق ماء أنعم.",
    "أمواج وريح تتفق مع اتجاه الأشرعة والأعلام.",
    "رسوم لسفن أو موانئ حقيقية زارها الفنان.",
    "تصحيحات صغيرة في الصواري أو خط الأفق."
   ],
   "tools": [
    [
     "ألوان زيتية",
     "الوسيط التقليدي لماء عميق متوهج."
    ],
    [
     "فرش مسطحة طويلة",
     "للسماء الواسعة والبحر الممتد."
    ],
    [
     "فرشاة رفيعة طويلة",
     "للحبال وتفاصيل الأشرعة."
    ],
    [
     "سكين الرسم",
     "للزبد السميك والأمواج الهائجة."
    ],
    [
     "مخططات ونماذج السفن",
     "مراجع لأجسام وأشرعة صحيحة."
    ],
    [
     "دفتر رسم",
     "لرسم القوارب والموانئ من الواقع."
    ]
   ],
   "time": "من عدة أيام إلى عدة أسابيع",
   "bestFor": "لمحبي البحر والبحارة وأهل المدن الساحلية الذين يشعرون بالألفة قرب الماء.",
   "artists": [
    [
     "فيليم فان دي فيلده الابن",
     "هولندي، 1633–1707",
     "رسم السفن الحربية والبحار الهادئة بدقة لافتة."
    ],
    [
     "إيفان أيفازوفسكي",
     "روسي، 1817–1900",
     "رسم آلاف البحار، كثير منها يتوهج بضوء القمر أو العاصفة."
    ],
    [
     "فيتز هنري لين",
     "أمريكي، 1804–1865",
     "تتوهج مشاهده للموانئ الهادئة بضوء صافٍ وناعم."
    ],
    [
     "وينسلو هومر",
     "أمريكي، 1836–1910",
     "تُظهر لوحاته البحرية المتأخرة قوة الأطلسي الخام."
    ],
    [
     "مونتاغ داوسون",
     "بريطاني، 1890–1973",
     "رسم السفن الشراعية العالية وهي تسابق بحاراً لامعة متدحرجة."
    ]
   ],
   "guide": [
    "افحص الحبال. الرسام البحري الحقيقي يرسم حبالاً وأشرعة منطقية.",
    "أمِل اللوحة. الزبد المرسوم باليد يبرز قليلاً فوق السطح.",
    "اسأل عن السفينة أو الميناء. الفنان الجاد يعرف القارب الذي رسمه."
   ]
  }
 },
 {
  "id": "architectural",
  "cat": "nature",
  "type": "architectural",
  "level": "advanced",
  "en": {
   "name": "Architectural Drawing",
   "feel": "Precise",
   "essence": "Buildings drawn line by line, with a steady, careful hand.",
   "story": [
    "Builders have drawn plans for thousands of years. Islamic architects designed domes and arches with geometry. The Topkapı Scroll still holds their beautiful patterns.",
    "In Europe, Piranesi etched grand ruins and imagined prisons. Canaletto painted Venice with exact buildings. Architects learned to draw before they learned to build.",
    "Today, artists still sketch cities in pen and watercolor. Some draw old mud houses and souqs before they fade. Zaha Hadid showed that a building can begin as art."
   ],
   "tech": [
    [
     "One-point perspective",
     "All lines meet at one vanishing point on the horizon."
    ],
    [
     "Two-point perspective",
     "Two vanishing points for buildings seen from a corner."
    ],
    [
     "Line weight",
     "Thicker lines near you, thinner lines far away."
    ],
    [
     "Hatching",
     "Rows of fine lines that show shadow and depth."
    ],
    [
     "Elevation drawing",
     "A flat, straight-on view of one face of a building."
    ],
    [
     "Pen and wash",
     "Ink lines with soft watercolor for light and mood."
    ]
   ],
   "signs": [
    "Faint pencil guidelines and vanishing points under the ink.",
    "Ink lines that vary slightly in pressure, never perfectly even.",
    "Tiny overshoots where two lines cross at corners.",
    "Small erasures or paper wear around careful details.",
    "On-site sketches of the same building or street."
   ],
   "tools": [
    [
     "Technical pens",
     "Fine pens that give steady, even lines."
    ],
    [
     "T-square and set squares",
     "For straight and angled lines."
    ],
    [
     "Drawing board",
     "A flat, firm surface for exact work."
    ],
    [
     "Mechanical pencils",
     "For sharp, light guidelines."
    ],
    [
     "Scale ruler",
     "To draw buildings in true proportion."
    ],
    [
     "Dip pen and ink",
     "For expressive, varied lines."
    ],
    [
     "Watercolor",
     "For soft color and light."
    ]
   ],
   "time": "Several hours to several weeks",
   "bestFor": "Lovers of cities, heritage and design who enjoy clear lines and quiet detail.",
   "artists": [
    [
     "Giovanni Battista Piranesi",
     "Italian, 1720–1778",
     "His etchings of Rome’s ruins still inspire architects today."
    ],
    [
     "Canaletto",
     "Italian, 1697–1768",
     "He painted and drew Venice with careful, exact architecture."
    ],
    [
     "Hugh Ferriss",
     "American, 1889–1962",
     "His dramatic charcoal drawings imagined the skyscraper city."
    ],
    [
     "Hassan Fathy",
     "Egyptian, 1900–1989",
     "He drew mud-brick villages inspired by traditional Arab building."
    ],
    [
     "Zaha Hadid",
     "Iraqi-British, 1950–2016",
     "Her bold paintings and drawings changed how buildings were imagined."
    ]
   ],
   "guide": [
    "Look closely at the lines. Hand-drawn ink has gentle changes in pressure.",
    "Look for faint pencil guidelines. They show the drawing was built by hand.",
    "Ask if it was drawn on site. Many artists keep location sketchbooks."
   ]
  },
  "ar": {
   "name": "الرسم المعماري",
   "feel": "دقيق",
   "essence": "مبانٍ مرسومة خطاً بخط، بيد ثابتة متأنية.",
   "story": [
    "يرسم البناؤون المخططات منذ آلاف السنين. صمّم المعماريون المسلمون القباب والأقواس بالهندسة. وما زالت لفافة طوب قابي تحفظ أنماطهم الجميلة.",
    "في أوروبا حفر بيرانيزي أطلالاً عظيمة وسجوناً متخيلة. ورسم كاناليتو البندقية بمبانٍ دقيقة. وتعلّم المعماريون الرسم قبل البناء.",
    "اليوم ما زال الفنانون يرسمون المدن بالحبر والألوان المائية. ويرسم بعضهم البيوت الطينية القديمة والأسواق قبل أن تختفي. وأظهرت زها حديد أن المبنى قد يبدأ لوحة فنية."
   ],
   "tech": [
    [
     "المنظور بنقطة واحدة",
     "كل الخطوط تلتقي في نقطة تلاشٍ واحدة على الأفق."
    ],
    [
     "المنظور بنقطتين",
     "نقطتا تلاشٍ لمبانٍ تُرى من زاوية."
    ],
    [
     "سماكة الخط",
     "خطوط أسمك للقريب وأرفع للبعيد."
    ],
    [
     "التظليل بالخطوط",
     "صفوف من خطوط دقيقة تُظهر الظل والعمق."
    ],
    [
     "رسم الواجهة",
     "منظر مسطح مباشر لوجه واحد من المبنى."
    ],
    [
     "الحبر والغسل المائي",
     "خطوط حبر مع ألوان مائية ناعمة للضوء والمزاج."
    ]
   ],
   "signs": [
    "خطوط رصاص إرشادية ونقاط تلاشٍ باهتة تحت الحبر.",
    "خطوط حبر يتغير ضغطها قليلاً، ولا تكون متساوية تماماً.",
    "تجاوزات صغيرة حيث يتقاطع خطان عند الزوايا.",
    "آثار محو صغيرة أو تعب في الورق حول التفاصيل الدقيقة.",
    "رسوم ميدانية للمبنى أو الشارع نفسه."
   ],
   "tools": [
    [
     "أقلام هندسية",
     "أقلام دقيقة تعطي خطوطاً ثابتة متساوية."
    ],
    [
     "مسطرة T والمثلثات",
     "للخطوط المستقيمة والمائلة."
    ],
    [
     "لوح رسم",
     "سطح مستوٍ صلب للعمل الدقيق."
    ],
    [
     "أقلام رصاص ميكانيكية",
     "لخطوط إرشادية حادة وخفيفة."
    ],
    [
     "مسطرة المقياس",
     "لرسم المباني بنسب حقيقية."
    ],
    [
     "ريشة وحبر",
     "لخطوط معبّرة متنوعة."
    ],
    [
     "ألوان مائية",
     "للون الناعم والضوء."
    ]
   ],
   "time": "من عدة ساعات إلى عدة أسابيع",
   "bestFor": "لمحبي المدن والتراث والتصميم الذين يستمتعون بالخطوط الواضحة والتفاصيل الهادئة.",
   "artists": [
    [
     "جوفاني باتيستا بيرانيزي",
     "إيطالي، 1720–1778",
     "ما زالت محفوراته لأطلال روما تلهم المعماريين اليوم."
    ],
    [
     "كاناليتو",
     "إيطالي، 1697–1768",
     "رسم البندقية بعمارة دقيقة ومتأنية."
    ],
    [
     "هيو فيريس",
     "أمريكي، 1889–1962",
     "تخيّلت رسومه الدرامية بالفحم مدينة ناطحات السحاب."
    ],
    [
     "حسن فتحي",
     "مصري، 1900–1989",
     "رسم قرى من الطوب الطيني مستوحاة من البناء العربي التقليدي."
    ],
    [
     "زها حديد",
     "عراقية بريطانية، 1950–2016",
     "غيّرت لوحاتها ورسومها الجريئة طريقة تخيّل المباني."
    ]
   ],
   "guide": [
    "تأمل الخطوط. الحبر المرسوم باليد فيه تغيرات لطيفة في الضغط.",
    "ابحث عن خطوط رصاص باهتة. فهي تُظهر أن الرسم بُني باليد.",
    "اسأل إن كان مرسوماً في الموقع. كثير من الفنانين يحتفظون بدفاتر ميدانية."
   ]
  }
 },
 {
  "id": "fine_art_photo",
  "cat": "photography",
  "type": "fine_art_photo",
  "level": "intermediate",
  "en": {
   "name": "Fine Art Photography",
   "feel": "Reflective",
   "essence": "A photograph made as art, from one artist’s clear vision.",
   "story": [
    "Photography rests on an old idea. Ibn al-Haytham explained how light forms images in a dark chamber. Cameras finally captured those images in the 1830s.",
    "Alfred Stieglitz fought for photography to hang in galleries. Ansel Adams planned every print with great care. Photographers became artists with a voice of their own.",
    "Today, fine art photographers work in limited, signed editions. Many print by hand in darkrooms or studios. Each image carries a personal way of seeing."
   ],
   "tech": [
    [
     "Pre-visualization",
     "Seeing the final print in the mind before pressing the shutter."
    ],
    [
     "Zone System",
     "Ansel Adams’s method for controlling light and shadow in a print."
    ],
    [
     "Long exposure",
     "A slow shutter that turns water and clouds silky."
    ],
    [
     "Natural light study",
     "Returning to a place until the light is right."
    ],
    [
     "Careful printing",
     "Fine-tuning tone and contrast for each final print."
    ],
    [
     "Series work",
     "Building a body of images around one idea."
    ]
   ],
   "signs": [
    "The artist can show the original negative or camera file.",
    "A signed print with a clear edition number, such as 3/10.",
    "A certificate naming the print type, paper and date.",
    "A consistent personal vision across the artist’s whole series.",
    "Proof prints or contact sheets from the same shoot."
   ],
   "tools": [
    [
     "Camera",
     "Film or digital, chosen for the artist’s vision."
    ],
    [
     "Prime lenses",
     "Fixed lenses with sharp, clear rendering."
    ],
    [
     "Tripod",
     "Keeps the camera still for slow exposures."
    ],
    [
     "Light meter",
     "Measures light for exact exposure."
    ],
    [
     "Darkroom or editing screen",
     "Where the final print is shaped."
    ],
    [
     "Archival pigment or silver gelatin prints",
     "Long-lasting print types for collectors."
    ],
    [
     "Cotton rag or fiber paper",
     "Fine paper that lasts for generations."
    ]
   ],
   "time": "Days of shooting, then hours of printing",
   "bestFor": "Collectors who love quiet, considered images with lasting value.",
   "artists": [
    [
     "Julia Margaret Cameron",
     "British, 1815–1879",
     "Her soft-focus portraits showed that photographs could be poetic."
    ],
    [
     "Alfred Stieglitz",
     "American, 1864–1946",
     "He opened galleries and fought for photography as fine art."
    ],
    [
     "Ansel Adams",
     "American, 1902–1984",
     "His grand landscapes of the American West set the standard for printing."
    ],
    [
     "Hiroshi Sugimoto",
     "Japanese, born 1948",
     "His seascapes and theaters explore time with calm, long exposures."
    ],
    [
     "Andreas Gursky",
     "German, born 1955",
     "His huge, detailed prints reveal hidden patterns in modern life."
    ]
   ],
   "guide": [
    "Check the edition number and signature. Smaller editions usually hold more value.",
    "Ask for the print type and paper. Archival prints last for generations.",
    "Ask to see more of the series. A true artist has a clear vision."
   ]
  },
  "ar": {
   "name": "التصوير الفني",
   "feel": "متأمل",
   "essence": "صورة صُنعت فناً، من رؤية فنان واضحة.",
   "story": [
    "يقوم التصوير على فكرة قديمة. شرح ابن الهيثم كيف يكوّن الضوء صورة في غرفة مظلمة. ثم التقطت الكاميرات هذه الصور أخيراً في ثلاثينيات القرن التاسع عشر.",
    "ناضل ألفريد ستيغليتز ليُعلَّق التصوير في صالات العرض. وخطط أنسل آدامز لكل مطبوعة بعناية كبيرة. فصار المصورون فنانين لهم صوتهم الخاص.",
    "اليوم يعمل مصورو الفن في إصدارات محدودة موقّعة. ويطبع كثير منهم بأيديهم في غرف مظلمة أو استوديوهات. وكل صورة تحمل طريقة رؤية شخصية."
   ],
   "tech": [
    [
     "التصور المسبق",
     "رؤية المطبوعة النهائية في الذهن قبل الضغط على الزر."
    ],
    [
     "نظام المناطق",
     "طريقة أنسل آدامز للتحكم في الضوء والظل في المطبوعة."
    ],
    [
     "التعريض الطويل",
     "غالق بطيء يجعل الماء والغيوم حريرية."
    ],
    [
     "دراسة الضوء الطبيعي",
     "العودة إلى المكان حتى يصبح الضوء مناسباً."
    ],
    [
     "الطباعة المتأنية",
     "ضبط الدرجات والتباين لكل مطبوعة نهائية."
    ],
    [
     "العمل بالسلاسل",
     "بناء مجموعة صور حول فكرة واحدة."
    ]
   ],
   "signs": [
    "يستطيع الفنان أن يريك السالب الأصلي أو ملف الكاميرا.",
    "مطبوعة موقّعة برقم إصدار واضح، مثل 3/10.",
    "شهادة تذكر نوع الطباعة والورق والتاريخ.",
    "رؤية شخصية متسقة في سلسلة الفنان كلها.",
    "مطبوعات تجريبية أو أوراق تماس من الجلسة نفسها."
   ],
   "tools": [
    [
     "كاميرا",
     "فيلم أو رقمية، يختارها الفنان حسب رؤيته."
    ],
    [
     "عدسات ثابتة البعد البؤري",
     "عدسات حادة وواضحة."
    ],
    [
     "حامل ثلاثي",
     "يثبّت الكاميرا للتعريض البطيء."
    ],
    [
     "مقياس الضوء",
     "يقيس الضوء لتعريض دقيق."
    ],
    [
     "غرفة مظلمة أو شاشة تحرير",
     "حيث تتشكل المطبوعة النهائية."
    ],
    [
     "طباعة صبغية أرشيفية أو فضية جيلاتينية",
     "أنواع طباعة تدوم طويلاً للمقتنين."
    ],
    [
     "ورق قطني أو ليفي",
     "ورق فاخر يدوم لأجيال."
    ]
   ],
   "time": "أيام من التصوير، ثم ساعات من الطباعة",
   "bestFor": "للمقتنين الذين يحبون الصور الهادئة المدروسة ذات القيمة الدائمة.",
   "artists": [
    [
     "جوليا مارغريت كاميرون",
     "بريطانية، 1815–1879",
     "أظهرت بورتريهاتها الناعمة أن الصورة قد تكون شعرية."
    ],
    [
     "ألفريد ستيغليتز",
     "أمريكي، 1864–1946",
     "افتتح صالات عرض وناضل ليكون التصوير فناً رفيعاً."
    ],
    [
     "أنسل آدامز",
     "أمريكي، 1902–1984",
     "وضعت مناظره الكبرى للغرب الأمريكي معيار الطباعة المتقنة."
    ],
    [
     "هيروشي سوغيموتو",
     "ياباني، مواليد 1948",
     "تستكشف صوره للبحار والمسارح الزمن بتعريض طويل هادئ."
    ],
    [
     "أندرياس غورسكي",
     "ألماني، مواليد 1955",
     "تكشف مطبوعاته الضخمة المفصّلة أنماطاً خفية في الحياة الحديثة."
    ]
   ],
   "guide": [
    "افحص رقم الإصدار والتوقيع. الإصدارات الأصغر تحمل قيمة أكبر عادة.",
    "اسأل عن نوع الطباعة والورق. المطبوعات الأرشيفية تدوم لأجيال.",
    "اطلب رؤية المزيد من السلسلة. الفنان الحقيقي له رؤية واضحة."
   ]
  }
 },
 {
  "id": "bw_photo",
  "cat": "photography",
  "type": "bw_photo",
  "level": "intermediate",
  "en": {
   "name": "Black & White Photography",
   "feel": "Soulful",
   "essence": "Light and shadow alone, telling the whole story.",
   "story": [
    "For its first hundred years, nearly all photography was black and white. Artists learned to paint with tone alone. Shape, texture and light became everything.",
    "Henri Cartier-Bresson waited for the decisive moment on city streets. Dorothea Lange showed hard lives with deep respect. In Cairo, Van Leo made glamorous studio portraits.",
    "Today, many photographers still choose black and white on purpose. Some shoot film and print in the darkroom by hand. Without color, feeling speaks louder."
   ],
   "tech": [
    [
     "Seeing in tone",
     "Judging a scene by light and dark, not color."
    ],
    [
     "Color filters",
     "Red or yellow filters that darken skies on film."
    ],
    [
     "Dodging and burning",
     "Lightening or darkening parts of a print by hand."
    ],
    [
     "Push processing",
     "Developing film longer to gain contrast in low light."
    ],
    [
     "Split-grade printing",
     "Using two contrast settings for richer tones."
    ],
    [
     "Toning",
     "Selenium or sepia baths that warm and protect prints."
    ]
   ],
   "signs": [
    "Original negatives or camera files the artist can show.",
    "A signed print with an edition number on the back or margin.",
    "A stated print type, such as silver gelatin or archival pigment.",
    "Fine grain and deep, rich blacks on fiber paper.",
    "A consistent personal vision across the artist’s body of work."
   ],
   "tools": [
    [
     "Film or digital camera",
     "Many artists still shoot black and white film."
    ],
    [
     "Black and white film",
     "Gives classic grain and a wide range of tones."
    ],
    [
     "Enlarger",
     "Projects the negative onto paper in the darkroom."
    ],
    [
     "Developer, stop and fixer",
     "The chemical baths that make the print."
    ],
    [
     "Fiber-based paper",
     "Heavy paper with deep blacks and a long life."
    ],
    [
     "Contrast filters",
     "Shape the tones of the final print."
    ]
   ],
   "time": "A day of shooting, then hours in the darkroom",
   "bestFor": "Collectors who love timeless images, strong emotion and classic craft.",
   "artists": [
    [
     "Henri Cartier-Bresson",
     "French, 1908–2004",
     "He captured the decisive moment in everyday life."
    ],
    [
     "Dorothea Lange",
     "American, 1895–1965",
     "Her Migrant Mother became one of history’s most moving portraits."
    ],
    [
     "Edward Weston",
     "American, 1886–1958",
     "His peppers, shells and dunes turned simple forms into sculpture."
    ],
    [
     "Van Leo",
     "Armenian-Egyptian, 1921–2002",
     "His Cairo studio portraits shine with drama and elegant light."
    ],
    [
     "Fan Ho",
     "Chinese, 1931–2016",
     "His Hong Kong street scenes play beautifully with light and shadow."
    ]
   ],
   "guide": [
    "Ask if it is a silver gelatin print. Hand darkroom prints are highly valued.",
    "Check the edition number and signature, usually on the back or margin.",
    "Look at the blacks and whites. A fine print holds detail in both."
   ]
  },
  "ar": {
   "name": "التصوير بالأبيض والأسود",
   "feel": "عميق",
   "essence": "الضوء والظل وحدهما يرويان القصة كلها.",
   "story": [
    "في المئة عام الأولى كان التصوير كله تقريباً بالأبيض والأسود. تعلّم الفنانون الرسم بالدرجات وحدها. فصار الشكل والملمس والضوء كل شيء.",
    "انتظر هنري كارتييه بريسون اللحظة الحاسمة في شوارع المدن. وأظهرت دوروثيا لانج الحياة القاسية باحترام عميق. وفي القاهرة صنع فان ليو بورتريهات استوديو ساحرة.",
    "اليوم ما زال كثير من المصورين يختارون الأبيض والأسود عن قصد. ويصوّر بعضهم بالفيلم ويطبعون بأيديهم في الغرفة المظلمة. فبلا لون، يعلو صوت الشعور."
   ],
   "tech": [
    [
     "الرؤية بالدرجات",
     "الحكم على المشهد بالضوء والظل لا باللون."
    ],
    [
     "المرشحات الملونة",
     "مرشحات حمراء أو صفراء تغمّق السماء على الفيلم."
    ],
    [
     "التفتيح والتغميق",
     "تفتيح أجزاء من المطبوعة أو تغميقها باليد."
    ],
    [
     "التحميض الدافع",
     "تحميض الفيلم مدة أطول لزيادة التباين في الإضاءة الخافتة."
    ],
    [
     "الطباعة بدرجتين",
     "استخدام درجتي تباين لدرجات أغنى."
    ],
    [
     "التلوين الكيميائي",
     "أحواض السيلينيوم أو السيبيا التي تدفّئ المطبوعة وتحميها."
    ]
   ],
   "signs": [
    "سوالب أصلية أو ملفات كاميرا يستطيع الفنان أن يريك إياها.",
    "مطبوعة موقّعة برقم إصدار على الظهر أو الهامش.",
    "نوع طباعة معلن، كالفضية الجيلاتينية أو الصبغية الأرشيفية.",
    "حبيبات دقيقة وسواد عميق غني على ورق ليفي.",
    "رؤية شخصية متسقة في مجمل أعمال الفنان."
   ],
   "tools": [
    [
     "كاميرا فيلم أو رقمية",
     "ما زال كثير من الفنانين يصوّرون بفيلم الأبيض والأسود."
    ],
    [
     "فيلم أبيض وأسود",
     "يعطي حبيبات كلاسيكية ومدى واسعاً من الدرجات."
    ],
    [
     "جهاز التكبير",
     "يسقط السالب على الورق في الغرفة المظلمة."
    ],
    [
     "المظهّر والموقف والمثبّت",
     "الأحواض الكيميائية التي تصنع المطبوعة."
    ],
    [
     "ورق ليفي",
     "ورق ثقيل بسواد عميق وعمر طويل."
    ],
    [
     "مرشحات التباين",
     "تشكّل درجات المطبوعة النهائية."
    ]
   ],
   "time": "يوم من التصوير، ثم ساعات في الغرفة المظلمة",
   "bestFor": "للمقتنين الذين يحبون الصور الخالدة والمشاعر القوية والحرفة الكلاسيكية.",
   "artists": [
    [
     "هنري كارتييه بريسون",
     "فرنسي، 1908–2004",
     "التقط اللحظة الحاسمة في الحياة اليومية."
    ],
    [
     "دوروثيا لانج",
     "أمريكية، 1895–1965",
     "صارت صورتها الأم المهاجرة من أكثر البورتريهات تأثيراً في التاريخ."
    ],
    [
     "إدوارد ويستون",
     "أمريكي، 1886–1958",
     "حوّلت صوره للفلفل والأصداف والكثبان الأشكال البسيطة إلى منحوتات."
    ],
    [
     "فان ليو",
     "أرمني مصري، 1921–2002",
     "تتألق بورتريهاته في استوديو القاهرة بالدراما والضوء الأنيق."
    ],
    [
     "فان هو",
     "صيني، 1931–2016",
     "تلعب مشاهده لشوارع هونغ كونغ بالضوء والظل بجمال."
    ]
   ],
   "guide": [
    "اسأل إن كانت مطبوعة فضية جيلاتينية. مطبوعات الغرفة المظلمة اليدوية عالية القيمة.",
    "افحص رقم الإصدار والتوقيع، وغالباً يكونان على الظهر أو الهامش.",
    "تأمل الأسود والأبيض. المطبوعة الجيدة تحفظ التفاصيل في كليهما."
   ]
  }
 },
 {
  "id": "artistic_photo",
  "cat": "photography",
  "type": "artistic_photo",
  "level": "advanced",
  "en": {
   "name": "Artistic Photography",
   "feel": "Playful",
   "essence": "Photographs that bend time, light and motion on purpose.",
   "story": [
    "Artists began playing with the camera very early. In the 1920s, Man Ray and Moholy-Nagy made images without a camera. They laid objects straight onto light-sensitive paper.",
    "In 1949, Gjon Mili photographed Picasso drawing with light in the air. Ernst Haas blurred motion into pure color. Experiment became a language of its own.",
    "Today, artists use long exposure, double exposure and deliberate camera movement. Some paint with torches in the dark. Every image is planned, tested and made by hand."
   ],
   "tech": [
    [
     "Long exposure",
     "A slow shutter that records movement as trails and blur."
    ],
    [
     "Double exposure",
     "Two images recorded on one frame, inside the camera."
    ],
    [
     "Intentional camera movement",
     "Moving the camera during exposure to paint with blur."
    ],
    [
     "Light painting",
     "Drawing with a moving light during a long exposure."
    ],
    [
     "Photogram",
     "Placing objects on photo paper and exposing it to light."
    ],
    [
     "Solarization",
     "A brief flash of light during developing that reverses some tones."
    ]
   ],
   "signs": [
    "Original camera files or negatives showing the effect was made in camera.",
    "Test shots and failed frames from the same session.",
    "A signed print with an edition number and print details.",
    "Behind-the-scenes photos of the light painting or the setup.",
    "A clear personal style across many experiments."
   ],
   "tools": [
    [
     "Manual camera",
     "Gives full control of shutter and aperture."
    ],
    [
     "Tripod",
     "Holds the camera steady for long exposures."
    ],
    [
     "Remote shutter release",
     "Opens the shutter without shaking the camera."
    ],
    [
     "Neutral density filters",
     "Darken the lens for long exposures in daylight."
    ],
    [
     "Torches and light wands",
     "Tools for drawing with light in the dark."
    ],
    [
     "Photo paper and darkroom",
     "For photograms and solarized prints."
    ]
   ],
   "time": "One night to several weeks of testing",
   "bestFor": "Collectors who love surprise, motion and bold, experimental images.",
   "artists": [
    [
     "Man Ray",
     "American, 1890–1976",
     "His rayographs and solarized portraits made photography daring and new."
    ],
    [
     "László Moholy-Nagy",
     "Hungarian, 1895–1946",
     "He explored light itself through photograms at the Bauhaus."
    ],
    [
     "Gjon Mili",
     "Albanian-American, 1904–1984",
     "He photographed Picasso drawing with light in 1949."
    ],
    [
     "Ernst Haas",
     "Austrian-American, 1921–1986",
     "He turned motion into color poetry with slow, moving exposures."
    ],
    [
     "Michael Kenna",
     "British, born 1953",
     "His quiet long exposures turn landscapes into calm, dreamy scenes."
    ]
   ],
   "guide": [
    "Ask how the effect was made. The artist should explain it with pride.",
    "Ask to see test shots or the original file. Experiments leave a trail.",
    "Check for a signature, an edition number and an archival print type."
   ]
  },
  "ar": {
   "name": "التصوير التجريبي",
   "feel": "مرح",
   "essence": "صور تطوّع الزمن والضوء والحركة عن قصد.",
   "story": [
    "بدأ الفنانون اللعب بالكاميرا مبكراً جداً. في عشرينيات القرن العشرين صنع مان راي وموهولي ناجي صوراً بلا كاميرا. وضعوا الأشياء مباشرة على ورق حساس للضوء.",
    "في عام 1949 صوّر جون ميلي بيكاسو وهو يرسم بالضوء في الهواء. وحوّل إرنست هاس الحركة إلى لون خالص. فصار التجريب لغة قائمة بذاتها.",
    "اليوم يستخدم الفنانون التعريض الطويل والتعريض المزدوج وتحريك الكاميرا المقصود. ويرسم بعضهم بالمصابيح في الظلام. وكل صورة يُخطَّط لها وتُجرَّب وتُصنع باليد."
   ],
   "tech": [
    [
     "التعريض الطويل",
     "غالق بطيء يسجّل الحركة خطوطاً وضبابية."
    ],
    [
     "التعريض المزدوج",
     "صورتان على إطار واحد داخل الكاميرا."
    ],
    [
     "تحريك الكاميرا المقصود",
     "تحريك الكاميرا أثناء التعريض للرسم بالضبابية."
    ],
    [
     "الرسم بالضوء",
     "الرسم بضوء متحرك أثناء تعريض طويل."
    ],
    [
     "الفوتوغرام",
     "وضع الأشياء على ورق التصوير وتعريضه للضوء."
    ],
    [
     "التشميس",
     "ومضة ضوء قصيرة أثناء التحميض تعكس بعض الدرجات."
    ]
   ],
   "signs": [
    "ملفات كاميرا أو سوالب أصلية تُظهر أن التأثير صُنع داخل الكاميرا.",
    "لقطات تجريبية وإطارات فاشلة من الجلسة نفسها.",
    "مطبوعة موقّعة برقم إصدار وتفاصيل الطباعة.",
    "صور من خلف الكواليس للرسم بالضوء أو للتجهيز.",
    "أسلوب شخصي واضح عبر تجارب كثيرة."
   ],
   "tools": [
    [
     "كاميرا يدوية",
     "تعطي تحكماً كاملاً بالغالق والفتحة."
    ],
    [
     "حامل ثلاثي",
     "يثبّت الكاميرا للتعريض الطويل."
    ],
    [
     "زر تحرير عن بعد",
     "يفتح الغالق دون أن تهتز الكاميرا."
    ],
    [
     "مرشحات الكثافة المحايدة",
     "تعتّم العدسة للتعريض الطويل في النهار."
    ],
    [
     "مصابيح وعصي ضوئية",
     "أدوات للرسم بالضوء في الظلام."
    ],
    [
     "ورق تصوير وغرفة مظلمة",
     "للفوتوغرام والمطبوعات المشمّسة."
    ]
   ],
   "time": "من ليلة واحدة إلى عدة أسابيع من التجريب",
   "bestFor": "للمقتنين الذين يحبون المفاجأة والحركة والصور التجريبية الجريئة.",
   "artists": [
    [
     "مان راي",
     "أمريكي، 1890–1976",
     "جعلت صوره الرايوغرافية وبورتريهاته المشمّسة التصوير جريئاً وجديداً."
    ],
    [
     "لاسلو موهولي ناجي",
     "مجري، 1895–1946",
     "استكشف الضوء نفسه عبر الفوتوغرام في مدرسة باوهاوس."
    ],
    [
     "جون ميلي",
     "ألباني أمريكي، 1904–1984",
     "صوّر بيكاسو وهو يرسم بالضوء عام 1949."
    ],
    [
     "إرنست هاس",
     "نمساوي أمريكي، 1921–1986",
     "حوّل الحركة إلى شعر لوني بتعريضات بطيئة متحركة."
    ],
    [
     "مايكل كينا",
     "بريطاني، مواليد 1953",
     "تحوّل تعريضاته الطويلة الهادئة المناظر إلى مشاهد حالمة."
    ]
   ],
   "guide": [
    "اسأل كيف صُنع التأثير. سيشرحه الفنان بفخر.",
    "اطلب رؤية اللقطات التجريبية أو الملف الأصلي. التجارب تترك أثراً.",
    "افحص التوقيع ورقم الإصدار ونوع الطباعة الأرشيفية."
   ]
  }
 },
 {
  "id": "photo_manipulation",
  "cat": "photography",
  "type": "photo_manipulation",
  "level": "advanced",
  "en": {
   "name": "Photo Manipulation",
   "feel": "Surreal",
   "essence": "Real photographs, cut and joined by hand into new worlds.",
   "story": [
    "Photographers combined images almost from the start. In 1857, Oscar Rejlander joined over thirty negatives into one print. Henry Peach Robinson soon did the same.",
    "In the 1920s, Hannah Höch cut and pasted photos into bold montages. Later, Jerry Uelsmann blended negatives under several enlargers. His darkroom dreams felt magical yet real.",
    "Today, many artists composite on screen with layers and masks. They shoot every source photo themselves. One piece can take weeks of careful handwork."
   ],
   "tech": [
    [
     "Combination printing",
     "Printing several negatives onto one sheet of paper."
    ],
    [
     "Multiple enlargers",
     "Uelsmann’s method of moving paper between several enlargers."
    ],
    [
     "Photomontage",
     "Cutting and gluing photo pieces into a new image."
    ],
    [
     "Layer masking",
     "Hiding and revealing parts of photos to blend them."
    ],
    [
     "Light matching",
     "Making every piece share one direction of light."
    ],
    [
     "Perspective matching",
     "Shooting each element from the same angle and height."
    ],
    [
     "Hand retouching",
     "Painting fine details by hand to hide the seams."
    ]
   ],
   "signs": [
    "The artist’s own original source photos for every element.",
    "Layered working files that show how the image was built.",
    "Behind-the-scenes shots from the artist’s own photo shoots.",
    "Light, shadows and perspective that match across every element.",
    "A signed, numbered print with clear print details."
   ],
   "tools": [
    [
     "Camera",
     "For shooting every source photo yourself."
    ],
    [
     "Tripod",
     "Keeps angles matching between separate shots."
    ],
    [
     "Enlargers and darkroom",
     "For classic combination prints."
    ],
    [
     "Editing software with layers",
     "For masking and blending on screen."
    ],
    [
     "Graphics tablet and pen",
     "Gives the hand control for fine masking."
    ],
    [
     "Scalpel and glue",
     "For cut-and-paste photomontage."
    ],
    [
     "Fine art paper",
     "For the final signed print."
    ]
   ],
   "time": "Several days to several weeks",
   "bestFor": "Collectors who love dreamlike stories and the craft behind every seam.",
   "artists": [
    [
     "Oscar Rejlander",
     "Swedish, 1813–1875",
     "His Two Ways of Life joined dozens of negatives into one print."
    ],
    [
     "Henry Peach Robinson",
     "British, 1830–1901",
     "His combination prints, like Fading Away, shaped early art photography."
    ],
    [
     "Hannah Höch",
     "German, 1889–1978",
     "Her bold photomontages cut apart and rebuilt the images of her time."
    ],
    [
     "Jerry Uelsmann",
     "American, 1934–2022",
     "He blended negatives in the darkroom into surreal, dreamlike scenes."
    ],
    [
     "Maggie Taylor",
     "American, born 1961",
     "She builds dreamy scenes from scans and photos, layer by layer."
    ],
    [
     "Erik Johansson",
     "Swedish, born 1985",
     "He plans and shoots every piece of his impossible scenes himself."
    ]
   ],
   "guide": [
    "Ask to see the original source photos. Real artists shoot their own.",
    "Ask about the layers. The artist can explain how each part was joined.",
    "Check for a signature, an edition number and archival print details."
   ]
  },
  "ar": {
   "name": "التركيب الفوتوغرافي",
   "feel": "سريالي",
   "essence": "صور حقيقية، تُقص وتُجمع باليد في عوالم جديدة.",
   "story": [
    "جمع المصورون الصور منذ البدايات تقريباً. في عام 1857 دمج أوسكار ريلاندر أكثر من ثلاثين سالباً في مطبوعة واحدة. وسرعان ما فعل هنري بيتش روبنسون مثله.",
    "في عشرينيات القرن العشرين قصّت هانا هوخ الصور ولصقتها في تركيبات جريئة. ثم دمج جيري أولسمان السوالب تحت عدة أجهزة تكبير. فبدت أحلامه في الغرفة المظلمة ساحرة وحقيقية.",
    "اليوم يركّب كثير من الفنانين الصور على الشاشة بالطبقات والأقنعة. ويصوّرون كل صورة مصدر بأنفسهم. وقد تستغرق القطعة الواحدة أسابيع من العمل اليدوي المتأني."
   ],
   "tech": [
    [
     "الطباعة المركّبة",
     "طباعة عدة سوالب على ورقة واحدة."
    ],
    [
     "أجهزة التكبير المتعددة",
     "طريقة أولسمان في نقل الورق بين عدة أجهزة تكبير."
    ],
    [
     "المونتاج الفوتوغرافي",
     "قص قطع الصور ولصقها في صورة جديدة."
    ],
    [
     "أقنعة الطبقات",
     "إخفاء أجزاء من الصور وإظهارها لدمجها."
    ],
    [
     "مطابقة الضوء",
     "جعل كل القطع تشترك في اتجاه ضوء واحد."
    ],
    [
     "مطابقة المنظور",
     "تصوير كل عنصر من الزاوية والارتفاع نفسيهما."
    ],
    [
     "الرتوش اليدوي",
     "رسم تفاصيل دقيقة باليد لإخفاء خطوط الوصل."
    ]
   ],
   "signs": [
    "صور المصدر الأصلية الخاصة بالفنان لكل عنصر.",
    "ملفات عمل بطبقات تُظهر كيف بُنيت الصورة.",
    "صور من خلف الكواليس لجلسات التصوير الخاصة بالفنان.",
    "ضوء وظلال ومنظور متطابقة في كل العناصر.",
    "مطبوعة موقّعة ومرقّمة بتفاصيل طباعة واضحة."
   ],
   "tools": [
    [
     "كاميرا",
     "لتصوير كل صورة مصدر بنفسك."
    ],
    [
     "حامل ثلاثي",
     "يحافظ على تطابق الزوايا بين اللقطات المنفصلة."
    ],
    [
     "أجهزة تكبير وغرفة مظلمة",
     "للمطبوعات المركّبة الكلاسيكية."
    ],
    [
     "برنامج تحرير بالطبقات",
     "للإخفاء والدمج على الشاشة."
    ],
    [
     "لوح رسم وقلم",
     "يمنح اليد تحكماً في الإخفاء الدقيق."
    ],
    [
     "مشرط وغراء",
     "للمونتاج بالقص واللصق."
    ],
    [
     "ورق فني",
     "للمطبوعة النهائية الموقّعة."
    ]
   ],
   "time": "من عدة أيام إلى عدة أسابيع",
   "bestFor": "للمقتنين الذين يحبون القصص الحالمة والحرفة الكامنة خلف كل وصلة.",
   "artists": [
    [
     "أوسكار ريلاندر",
     "سويدي، 1813–1875",
     "جمعت صورته طريقا الحياة عشرات السوالب في مطبوعة واحدة."
    ],
    [
     "هنري بيتش روبنسون",
     "بريطاني، 1830–1901",
     "شكّلت مطبوعاته المركّبة، مثل الذبول، بدايات التصوير الفني."
    ],
    [
     "هانا هوخ",
     "ألمانية، 1889–1978",
     "قصّت تركيباتها الجريئة صور عصرها وأعادت بناءها."
    ],
    [
     "جيري أولسمان",
     "أمريكي، 1934–2022",
     "دمج السوالب في الغرفة المظلمة في مشاهد سريالية حالمة."
    ],
    [
     "ماغي تايلور",
     "أمريكية، مواليد 1961",
     "تبني مشاهد حالمة من الصور الممسوحة والفوتوغرافية طبقة بعد طبقة."
    ],
    [
     "إريك يوهانسون",
     "سويدي، مواليد 1985",
     "يخطط ويصوّر كل قطعة من مشاهده المستحيلة بنفسه."
    ]
   ],
   "guide": [
    "اطلب رؤية صور المصدر الأصلية. الفنانون الحقيقيون يصوّرون صورهم بأنفسهم.",
    "اسأل عن الطبقات. يستطيع الفنان شرح كيف وُصل كل جزء.",
    "افحص التوقيع ورقم الإصدار وتفاصيل الطباعة الأرشيفية."
   ]
  }
 }
];
