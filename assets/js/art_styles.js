/* =====================================================================
 *  art_styles.js — the single source for all art style content.
 *  Used by explore.php (cards + the style window). Never copy this data
 *  into a page: load this file instead.
 *
 *  Each style:
 *    id    — short name, also used in the web address (explore.php#watercolor)
 *    cat   — traditional | digital | mixed | modern (the filter bar)
 *    type  — the matching Gallery type from art_types.php (marketplace.php?type=…),
 *            or null when the Gallery has no type for it yet
 *    en/ar — name, feel (one word), essence (one line), story (paragraphs),
 *            tech [name, one-line explanation], signs (numbered list),
 *            artists [name, nationality and dates], guide (3 buyer tips)
 * ===================================================================== */
window.OW_ART_STYLES = [
{
  id: 'watercolor', cat: 'traditional', type: 'watercolor',
  en: {
    name: 'Watercolor', feel: 'Dreamy',
    essence: 'Pigment carried by water, where the light comes from the paper itself.',
    story: [
      'Watercolor is one of the oldest ways people have painted. Cave artists mixed earth with water. Painters in China and Persia used it on silk and paper for centuries.',
      'In Europe, Albrecht Dürer painted grass and hares with it in the 1500s. Later, British painters carried small paint boxes outdoors. Turner turned whole skies into pure light.',
      'The challenge is trust. Water moves on its own. The artist guides it, then lets go. A mistake cannot be hidden. It can only be built upon.'
    ],
    tech: [
      ['Wet-on-wet', 'Paint on wet paper, so colors bloom and blend.'],
      ['Wet-on-dry', 'Paint on dry paper for crisp, sharp edges.'],
      ['Glazing', 'Thin layers over dry ones to deepen the color.'],
      ['Lifting', 'Removing wet paint with a brush or tissue to bring back light.'],
      ['Reserving whites', 'Leaving the paper bare, because watercolor has no true white.']
    ],
    signs: [
      'Soft blooms where water pushed the pigment outward.',
      'Pigment settled into the paper grain, a texture no print can copy.',
      'Faint pencil lines under the paint, left from the first drawing.',
      'Paper that waves a little where it was soaked.'
    ],
    artists: [
      ['J. M. W. Turner', 'British, 1775–1851'],
      ['Winslow Homer', 'American, 1836–1910'],
      ['John Singer Sargent', 'American, 1856–1925'],
      ['Albrecht Dürer', 'German, 1471–1528']
    ],
    guide: [
      'Tilt the work under a light. Real paint sits in the paper grain. A print looks flat.',
      'Look at the edges. Real watercolor paper has uneven edges and a clear weight.',
      'Ask about the paper. A serious artist will name it, such as 300gsm cotton paper.'
    ]
  },
  ar: {
    name: 'الألوان المائية', feel: 'حالم',
    essence: 'صبغة يحملها الماء، والضوء فيها يأتي من الورق نفسه.',
    story: [
      'الألوان المائية من أقدم طرق الرسم عند الإنسان. خلط رسامو الكهوف التراب بالماء. واستخدمها فنانو الصين وفارس على الحرير والورق لقرون.',
      'في أوروبا رسم ألبرخت دورر بها العشب والأرانب في القرن السادس عشر. ثم حمل الرسامون البريطانيون علب ألوان صغيرة إلى الطبيعة. وحوّل تيرنر السماء كلها إلى ضوء خالص.',
      'التحدي هنا هو الثقة. الماء يتحرك وحده. يوجّهه الفنان ثم يتركه. لا يمكن إخفاء الخطأ. يمكن فقط البناء عليه.'
    ],
    tech: [
      ['الرطب على الرطب', 'الرسم على ورق مبلل لتتفتح الألوان وتمتزج.'],
      ['الرطب على الجاف', 'الرسم على ورق جاف لحواف واضحة وحادة.'],
      ['التزجيج', 'طبقات شفافة فوق طبقات جافة لتعميق اللون.'],
      ['الرفع', 'إزالة اللون الرطب بفرشاة أو منديل لاستعادة الضوء.'],
      ['حفظ البياض', 'ترك الورق دون لون، فالألوان المائية بلا أبيض حقيقي.']
    ],
    signs: [
      'تفتّحات ناعمة حيث دفع الماء الصبغة إلى الخارج.',
      'صبغة استقرت في حبيبات الورق، ملمس لا تنسخه أي طباعة.',
      'خطوط رصاص خفيفة تحت اللون، بقيت من الرسم الأول.',
      'ورق متموّج قليلاً حيث تشرّب الماء.'
    ],
    artists: [
      ['ويليام تيرنر', 'بريطاني، 1775–1851'],
      ['وينسلو هومر', 'أمريكي، 1836–1910'],
      ['جون سينجر سارجنت', 'أمريكي، 1856–1925'],
      ['ألبرخت دورر', 'ألماني، 1471–1528']
    ],
    guide: [
      'أمِل العمل تحت الضوء. اللون الحقيقي يستقر في حبيبات الورق، والطباعة تبدو مسطحة.',
      'انظر إلى الحواف. ورق الألوان المائية الحقيقي حوافه غير منتظمة وله وزن واضح.',
      'اسأل عن نوع الورق. الفنان الجاد يذكره، مثل ورق قطني بوزن 300 غرام.'
    ]
  }
},
{
  id: 'oil', cat: 'traditional', type: 'oil',
  en: {
    name: 'Oil Painting', feel: 'Timeless',
    essence: 'Slow-drying color with deep glow, built in layers over days or months.',
    story: [
      'Before the camera, people longed to stop time. They wanted to hold a face, a fold of cloth, a beam of light.',
      'Oil paint made that possible. Painters in Flanders perfected it in the 1400s. The classical masters spent years grinding pigments and stretching linen. One portrait could take months of thin, slow layers.',
      'Because oil dries slowly, the artist can return again and again. Each layer is a quiet act of devotion. The surface remembers every decision.'
    ],
    tech: [
      ['Underpainting', 'A first single-color layer that maps light and shadow.'],
      ['Glazing', 'Thin transparent layers that give a deep, glowing color.'],
      ['Impasto', 'Thick paint that stands up from the canvas and catches light.'],
      ['Chiaroscuro', 'Strong contrast between light and dark to shape form.'],
      ['Sfumato', 'Soft, smoky blending with no hard outlines.']
    ],
    signs: [
      'Brushstrokes with direction and ridges you can see from the side.',
      'Brush hairs caught in the paint, and colors left unmixed on purpose.',
      'Edges softened by a thumb or a rag, slightly imperfect.',
      'Fine cracks on older works, because layers age at different speeds.'
    ],
    artists: [
      ['Leonardo da Vinci', 'Italian, 1452–1519'],
      ['Rembrandt van Rijn', 'Dutch, 1606–1669'],
      ['Johannes Vermeer', 'Dutch, 1632–1675'],
      ['Mahmoud Saïd', 'Egyptian, 1897–1964']
    ],
    guide: [
      'Look at the canvas from the side. Real oil has raised texture and brush marks.',
      'Check the back. You should see raw canvas, a wooden frame and often a signature or date.',
      'Watch the shine. Real oil has uneven gloss, not one flat glossy coat.'
    ]
  },
  ar: {
    name: 'الرسم الزيتي', feel: 'خالد',
    essence: 'لون يجف ببطء وله توهّج عميق، يُبنى طبقة بعد طبقة على مدى أيام أو شهور.',
    story: [
      'قبل الكاميرا، تمنّى الإنسان أن يوقف الزمن. أراد أن يمسك وجهاً، وطيّة قماش، وخيط ضوء.',
      'جعلت الألوان الزيتية ذلك ممكناً. أتقنها رسامو الفلاندرز في القرن الخامس عشر. أمضى الأساتذة الكلاسيكيون سنوات يطحنون الأصباغ ويشدّون الكتان. وقد تستغرق لوحة واحدة شهوراً من الطبقات الرقيقة.',
      'لأن الزيت يجف ببطء، يعود الفنان إلى لوحته مرة بعد مرة. كل طبقة فعل حبّ هادئ. والسطح يحفظ كل قرار.'
    ],
    tech: [
      ['الطبقة التحتية', 'طبقة أولى بلون واحد ترسم خريطة الضوء والظل.'],
      ['التزجيج', 'طبقات شفافة رقيقة تمنح لوناً عميقاً متوهجاً.'],
      ['الإمباستو', 'طلاء سميك يبرز من القماش ويلتقط الضوء.'],
      ['كياروسكورو', 'تباين قوي بين الضوء والظل لإبراز الشكل.'],
      ['سفوماتو', 'مزج ناعم كالدخان بلا خطوط حادة.']
    ],
    signs: [
      'ضربات فرشاة لها اتجاه ونتوءات تراها من الجانب.',
      'شعيرات فرشاة عالقة في الطلاء، وألوان تُركت بلا مزج عن قصد.',
      'حواف نعّمها إبهام أو قطعة قماش، غير مثالية قليلاً.',
      'شقوق دقيقة في الأعمال القديمة، لأن الطبقات تشيخ بسرعات مختلفة.'
    ],
    artists: [
      ['ليوناردو دا فينشي', 'إيطالي، 1452–1519'],
      ['رامبرانت', 'هولندي، 1606–1669'],
      ['يوهانس فيرمير', 'هولندي، 1632–1675'],
      ['محمود سعيد', 'مصري، 1897–1964']
    ],
    guide: [
      'انظر إلى اللوحة من الجانب. الزيت الحقيقي له ملمس بارز وآثار فرشاة.',
      'افحص ظهر اللوحة. سترى قماشاً خاماً وإطاراً خشبياً، وغالباً توقيعاً أو تاريخاً.',
      'راقب اللمعان. الزيت الحقيقي لمعانه متفاوت، لا طبقة لامعة واحدة مسطحة.'
    ]
  }
},
{
  id: 'charcoal', cat: 'traditional', type: 'charcoal',
  en: {
    name: 'Charcoal', feel: 'Raw',
    essence: 'Burnt wood on paper, the most direct line between hand and surface.',
    story: [
      'Charcoal is the first drawing tool. The cave painters of Chauvet in France used it more than 30,000 years ago.',
      'For centuries, painters used it for sketches, then brushed it away. In the 1800s, artists like Odilon Redon made charcoal the final work. His dark drawings feel like dreams.',
      'Charcoal is honest. It smudges, breaks and stains the fingers. Every mark shows the pressure and speed of the hand.'
    ],
    tech: [
      ['Vine charcoal', 'Light, soft sticks for first lines that erase easily.'],
      ['Compressed charcoal', 'Dense sticks for deep, velvety blacks.'],
      ['Blending', 'Softening tones with fingers, cloth or a paper stump.'],
      ['Subtractive drawing', 'Pulling light out of a dark area with an eraser.'],
      ['Fixative', 'A light spray that stops the charcoal from smudging.']
    ],
    signs: [
      'Lines that change width with pressure, thick then thin.',
      'Fine charcoal dust resting on top of the paper texture.',
      'Faint ghost lines where the artist erased and drew again.',
      'Small smudges or fingerprints near the edges.'
    ],
    artists: [
      ['Odilon Redon', 'French, 1840–1916'],
      ['Käthe Kollwitz', 'German, 1867–1945'],
      ['William Kentridge', 'South African, born 1955']
    ],
    guide: [
      'Look closely with the light at an angle. Real charcoal has a matte, dusty sheen.',
      'Ask if it is fixed and framed under glass. Unfixed charcoal must never be touched.',
      'Prints of charcoal look too even. Real blacks move from soft grey to deep black.'
    ]
  },
  ar: {
    name: 'الفحم', feel: 'خام',
    essence: 'خشب محترق على الورق، أقصر طريق بين اليد والسطح.',
    story: [
      'الفحم هو أول أداة رسم عرفها الإنسان. استخدمه رسامو كهف شوفيه في فرنسا قبل أكثر من 30 ألف عام.',
      'لقرون، رسم به الفنانون اسكتشاتهم ثم مسحوه. وفي القرن التاسع عشر جعله فنانون مثل أوديلون رودون عملاً نهائياً. رسومه الداكنة تشبه الأحلام.',
      'الفحم صادق. ينتشر وينكسر ويلطّخ الأصابع. وكل علامة تُظهر ضغط اليد وسرعتها.'
    ],
    tech: [
      ['فحم الكرمة', 'أعواد خفيفة ناعمة للخطوط الأولى، تُمسح بسهولة.'],
      ['الفحم المضغوط', 'أعواد كثيفة لأسود عميق مخملي.'],
      ['المزج', 'تنعيم الدرجات بالأصابع أو القماش أو أداة ورقية.'],
      ['الرسم بالإزالة', 'استخراج الضوء من مساحة داكنة بالممحاة.'],
      ['المثبّت', 'رذاذ خفيف يمنع الفحم من الانتشار.']
    ],
    signs: [
      'خطوط يتغير عرضها مع الضغط، سميكة ثم رقيقة.',
      'غبار فحم ناعم يستقر فوق ملمس الورق.',
      'خطوط باهتة حيث مسح الفنان ثم أعاد الرسم.',
      'لطخات صغيرة أو بصمات أصابع قرب الحواف.'
    ],
    artists: [
      ['أوديلون رودون', 'فرنسي، 1840–1916'],
      ['كيته كولفيتس', 'ألمانية، 1867–1945'],
      ['ويليام كنتريدج', 'جنوب أفريقي، مواليد 1955']
    ],
    guide: [
      'انظر عن قرب والضوء مائل. الفحم الحقيقي له لمعة غبارية مطفأة.',
      'اسأل إن كان مثبّتاً ومؤطراً تحت زجاج. الفحم غير المثبّت لا يُلمس أبداً.',
      'طباعة الفحم تبدو متساوية جداً. الأسود الحقيقي يتدرج من رمادي ناعم إلى أسود عميق.'
    ]
  }
},
{
  id: 'acrylic', cat: 'traditional', type: 'acrylic',
  en: {
    name: 'Acrylic', feel: 'Bold',
    essence: 'Fast-drying, vivid color that can behave like watercolor or like oil.',
    story: [
      'Acrylic paint is young. It arrived in the mid-1900s, made from the same plastics as modern industry.',
      'Artists loved its speed. It dries in minutes, so an idea can be finished in a day. David Hockney used it for bright, sunlit scenes. Helen Frankenthaler poured it thin to stain raw canvas.',
      'Acrylic gave painters freedom. It works on canvas, wood, walls and paper. It can be thin as ink or thick as clay.'
    ],
    tech: [
      ['Flat color fields', 'Even, solid areas of color with clean edges.'],
      ['Staining', 'Very thin paint soaked into raw canvas.'],
      ['Impasto with gel', 'Thick gel mediums that build texture.'],
      ['Masking', 'Tape used to make sharp, hard edges.'],
      ['Dry brushing', 'A nearly dry brush dragged to show texture.']
    ],
    signs: [
      'Tape edges that are sharp but slightly lifted in places.',
      'Brush marks inside flat areas when you look from the side.',
      'Layers you can see at the edges of the canvas.',
      'Small changes of mind painted over, showing as raised ghost shapes.'
    ],
    artists: [
      ['David Hockney', 'British, born 1937'],
      ['Helen Frankenthaler', 'American, 1928–2011'],
      ['Andy Warhol', 'American, 1928–1987']
    ],
    guide: [
      'Look along the surface. Hand-painted acrylic shows brush direction, even in flat areas.',
      'Check the sides of the canvas. Real works often carry drips or wrapped color there.',
      'Ask if it is one of a kind. An original painting is unique, not a print with added gloss.'
    ]
  },
  ar: {
    name: 'الأكريليك', feel: 'جريء',
    essence: 'لون زاهٍ يجف بسرعة، قد يتصرف كالألوان المائية أو كالزيت.',
    story: [
      'ألوان الأكريليك حديثة العهد. ظهرت في منتصف القرن العشرين، من مواد البلاستيك نفسها التي تستخدمها الصناعة الحديثة.',
      'أحبها الفنانون لسرعتها. تجف في دقائق، فتكتمل الفكرة في يوم واحد. استخدمها ديفيد هوكني لمشاهد مشمسة زاهية. وسكبتها هيلين فرانكنثالر رقيقة لتصبغ القماش الخام.',
      'منح الأكريليك الرسامين حرية. يصلح للقماش والخشب والجدران والورق. وقد يكون رقيقاً كالحبر أو سميكاً كالطين.'
    ],
    tech: [
      ['مساحات اللون المسطحة', 'مساحات لون متساوية بحواف نظيفة.'],
      ['الصبغ', 'طلاء رقيق جداً يتشرّبه القماش الخام.'],
      ['الإمباستو بالجل', 'وسائط جل سميكة تبني الملمس.'],
      ['التغطية بالشريط', 'شريط لاصق يصنع حوافاً حادة واضحة.'],
      ['الفرشاة الجافة', 'فرشاة شبه جافة تُسحب لإظهار الملمس.']
    ],
    signs: [
      'حواف شريط حادة لكنها مرتفعة قليلاً في بعض المواضع.',
      'آثار فرشاة داخل المساحات المسطحة حين تنظر من الجانب.',
      'طبقات تظهر عند أطراف القماش.',
      'تعديلات صغيرة رُسم فوقها، تظهر كأشكال بارزة خفيفة.'
    ],
    artists: [
      ['ديفيد هوكني', 'بريطاني، مواليد 1937'],
      ['هيلين فرانكنثالر', 'أمريكية، 1928–2011'],
      ['آندي وارهول', 'أمريكي، 1928–1987']
    ],
    guide: [
      'انظر على امتداد السطح. الأكريليك المرسوم باليد يُظهر اتجاه الفرشاة حتى في المساحات المسطحة.',
      'افحص جوانب القماش. الأعمال الحقيقية غالباً عليها قطرات لون أو لون ممتد.',
      'اسأل إن كان العمل فريداً. اللوحة الأصلية واحدة فقط، لا طباعة مضاف إليها لمعان.'
    ]
  }
},
{
  id: 'ink', cat: 'traditional', type: 'ink',
  en: {
    name: 'Ink & Pen', feel: 'Precise',
    essence: 'Line in its purest form, where every stroke is final.',
    story: [
      'Ink has carried human thought for thousands of years. In China and Japan, ink painting grew beside poetry and calligraphy.',
      'Masters like Sesshū painted whole mountains in a few strokes. In Europe, Dürer and Rembrandt filled sketchbooks with pen drawings. Later, Aubrey Beardsley made black and white illustration an art of its own.',
      'Ink forgives nothing. Once it touches the paper, it stays. That is why a confident ink line feels so alive.'
    ],
    tech: [
      ['Hatching', 'Parallel lines that build shadow.'],
      ['Cross-hatching', 'Crossed lines for deeper darks.'],
      ['Stippling', 'Thousands of dots that create tone.'],
      ['Ink wash', 'Ink thinned with water for soft grey tones.'],
      ['Dip pen', 'A nib dipped in ink, so lines swell and thin.']
    ],
    signs: [
      'Lines that swell and thin with the pressure of the nib.',
      'Small blots or pooled ink at the start or end of a stroke.',
      'Slightly raised, shiny ink when light hits it at an angle.',
      'Faint pencil guidelines under the ink.'
    ],
    artists: [
      ['Sesshū Tōyō', 'Japanese, 1420–1506'],
      ['Rembrandt van Rijn', 'Dutch, 1606–1669'],
      ['Katsushika Hokusai', 'Japanese, 1760–1849'],
      ['Aubrey Beardsley', 'British, 1872–1898']
    ],
    guide: [
      'Use a magnifier. Printed lines are made of tiny dots. Real ink lines are solid.',
      'Look for small differences. No two hand-drawn hatching lines are ever the same.',
      'Check the back of the paper. Strong ink sometimes shows through faintly.'
    ]
  },
  ar: {
    name: 'الحبر والقلم', feel: 'دقيق',
    essence: 'الخط في أنقى صوره، حيث كل ضربة نهائية.',
    story: [
      'حمل الحبر فكر الإنسان لآلاف السنين. وفي الصين واليابان نما الرسم بالحبر إلى جانب الشعر والخط.',
      'رسم أساتذة مثل سيشو جبالاً كاملة ببضع ضربات. وفي أوروبا ملأ دورر ورامبرانت دفاترهما برسوم القلم. ثم جعل أوبري بيردسلي الرسم بالأبيض والأسود فناً قائماً بذاته.',
      'الحبر لا يغفر شيئاً. ما إن يلمس الورق حتى يبقى. لذلك يبدو خط الحبر الواثق نابضاً بالحياة.'
    ],
    tech: [
      ['التظليل بالخطوط', 'خطوط متوازية تبني الظل.'],
      ['التظليل المتقاطع', 'خطوط متقاطعة لظلال أعمق.'],
      ['التنقيط', 'آلاف النقاط التي تصنع الدرجات.'],
      ['الحبر المخفّف', 'حبر ممزوج بالماء لدرجات رمادية ناعمة.'],
      ['الريشة المغموسة', 'ريشة تُغمس في الحبر، فيتسع الخط ويضيق.']
    ],
    signs: [
      'خط يتسع ويضيق مع ضغط الريشة.',
      'بقع صغيرة أو حبر متجمّع في بداية الضربة أو نهايتها.',
      'حبر بارز ولامع قليلاً حين يسقط عليه الضوء مائلاً.',
      'خطوط رصاص خفيفة تحت الحبر.'
    ],
    artists: [
      ['سيشو تويو', 'ياباني، 1420–1506'],
      ['رامبرانت', 'هولندي، 1606–1669'],
      ['كاتسوشيكا هوكوساي', 'ياباني، 1760–1849'],
      ['أوبري بيردسلي', 'بريطاني، 1872–1898']
    ],
    guide: [
      'استخدم عدسة مكبّرة. الخطوط المطبوعة مكوّنة من نقاط صغيرة، وخط الحبر الحقيقي متصل.',
      'ابحث عن الفروق الصغيرة. لا يتطابق خطّا تظليل مرسومان باليد أبداً.',
      'افحص ظهر الورقة. الحبر القوي يظهر أحياناً بشكل خفيف.'
    ]
  }
},
{
  id: 'pastel', cat: 'traditional', type: 'pastel',
  en: {
    name: 'Pastel', feel: 'Soft',
    essence: 'Pure pigment in a stick, the closest thing to drawing with light.',
    story: [
      'Pastel is almost pure pigment, pressed into a stick with a little binder. Its colors stay bright and never yellow with age.',
      'In the 1700s, Rosalba Carriera made pastel portraits famous across Europe. Later, Edgar Degas drew his dancers in layer after layer of pastel. Mary Cassatt used it for tender scenes of mothers and children.',
      'Pastel is touched, not brushed. The artist draws, blends with a finger, then draws again. It is fragile, like a memory.'
    ],
    tech: [
      ['Soft pastel', 'Chalky sticks of rich, pure color.'],
      ['Oil pastel', 'Creamy, waxy sticks that do not dust.'],
      ['Scumbling', 'Light, broken layers over another color.'],
      ['Feathering', 'Short parallel strokes that mix in the eye.'],
      ['Toned paper', 'Colored paper that shows through as a middle tone.']
    ],
    signs: [
      'Chalky texture caught on the rough tooth of the paper.',
      'Finger blending marks, soft and slightly uneven.',
      'Separate strokes of color that only mix from a distance.',
      'Fine pastel dust inside the frame, near the bottom edge.'
    ],
    artists: [
      ['Rosalba Carriera', 'Venetian, 1673–1757'],
      ['Jean-Étienne Liotard', 'Swiss, 1702–1789'],
      ['Edgar Degas', 'French, 1834–1917'],
      ['Mary Cassatt', 'American, 1844–1926']
    ],
    guide: [
      'Ask for glass, not plastic. Static from plastic pulls pastel off the paper.',
      'Look from an angle. Real pastel has a velvety, matte surface.',
      'Check for spacers in the frame, so the glass never touches the work.'
    ]
  },
  ar: {
    name: 'الباستيل', feel: 'ناعم',
    essence: 'صبغة خالصة في أصبع، أقرب ما يكون إلى الرسم بالضوء.',
    story: [
      'الباستيل صبغة شبه خالصة، مضغوطة في أصبع مع قليل من المادة الرابطة. ألوانه تبقى زاهية ولا تصفرّ مع الزمن.',
      'في القرن الثامن عشر جعلت روزالبا كاريرا بورتريهات الباستيل مشهورة في أوروبا. ثم رسم إدغار ديغا راقصاته بطبقة بعد طبقة منه. واستخدمته ماري كاسات لمشاهد حانية بين الأمهات والأطفال.',
      'الباستيل يُلمس ولا يُفرش. يرسم الفنان، ثم يمزج بإصبعه، ثم يرسم من جديد. إنه هشّ، مثل الذكرى.'
    ],
    tech: [
      ['الباستيل الطري', 'أصابع طباشيرية بلون غني نقي.'],
      ['الباستيل الزيتي', 'أصابع كريمية شمعية لا تتغبّر.'],
      ['التغشية', 'طبقات خفيفة متقطعة فوق لون آخر.'],
      ['التريش', 'ضربات قصيرة متوازية تمتزج في العين.'],
      ['الورق الملوّن', 'ورق ملوّن يظهر كدرجة وسطى.']
    ],
    signs: [
      'ملمس طباشيري عالق في خشونة الورق.',
      'آثار مزج بالأصابع، ناعمة وغير متساوية قليلاً.',
      'ضربات لون منفصلة لا تمتزج إلا من بعيد.',
      'غبار باستيل ناعم داخل الإطار، قرب الحافة السفلية.'
    ],
    artists: [
      ['روزالبا كاريرا', 'من البندقية، 1673–1757'],
      ['جان إتيان ليوتار', 'سويسري، 1702–1789'],
      ['إدغار ديغا', 'فرنسي، 1834–1917'],
      ['ماري كاسات', 'أمريكية، 1844–1926']
    ],
    guide: [
      'اطلب زجاجاً لا بلاستيكاً. الكهرباء الساكنة في البلاستيك تسحب الباستيل عن الورق.',
      'انظر من زاوية مائلة. الباستيل الحقيقي سطحه مخملي مطفأ.',
      'تأكد من وجود فواصل في الإطار، حتى لا يلمس الزجاج العمل أبداً.'
    ]
  }
},
{
  id: 'gouache', cat: 'traditional', type: 'gouache',
  en: {
    name: 'Gouache', feel: 'Velvety',
    essence: 'Opaque watercolor with a soft, matte finish and bold flat color.',
    story: [
      'Gouache is watercolor made opaque with chalk or extra pigment. It covers the paper fully and dries flat and velvety.',
      'Persian and Mughal miniature painters used opaque watercolor for centuries. Kamāl ud-Dīn Behzād painted whole worlds in a few inches. In the 1900s, Mohammed Racim brought this art to life again in Algeria. Henri Matisse painted paper with gouache, then cut it into shapes.',
      'Gouache asks for planning. It dries fast, and lighter than it looks when wet. The artist must know the color before it lands.'
    ],
    tech: [
      ['Flat washes', 'Smooth, even areas of opaque color.'],
      ['Light over dark', 'Opaque paint lets light colors sit on dark ones.'],
      ['Fine detail', 'Tiny brushes for miniature-style detail.'],
      ['Dry brush', 'Very little water for crisp texture.'],
      ['Cut paper', 'Paper painted with gouache, then cut and arranged.']
    ],
    signs: [
      'A matte, chalky surface with no shine.',
      'Slight brush ridges at the edges of flat shapes.',
      'Tiny color changes inside areas that look flat from afar.',
      'Fine cracks where the paint was laid on too thick.'
    ],
    artists: [
      ['Kamāl ud-Dīn Behzād', 'Persian, c. 1450–1535'],
      ['Mohammed Racim', 'Algerian, 1896–1975'],
      ['Henri Matisse', 'French, 1869–1954']
    ],
    guide: [
      'Tilt it in the light. Gouache stays matte. Prints of it often shine.',
      'Use a magnifier. Real paint shows brush texture. Prints show dots.',
      'Ask how it is protected. Gouache can re-wet, so it needs glass and a dry room.'
    ]
  },
  ar: {
    name: 'الغواش', feel: 'مخملي',
    essence: 'ألوان مائية معتمة بلمسة مطفأة ناعمة ولون مسطح جريء.',
    story: [
      'الغواش ألوان مائية معتمة، تُضاف إليها مادة بيضاء أو صبغة أكثر. يغطي الورق تماماً ويجف مسطحاً مخملياً.',
      'استخدم رسامو المنمنمات الفارسية والمغولية الألوان المائية المعتمة لقرون. ورسم كمال الدين بهزاد عوالم كاملة في بضعة سنتيمترات. وفي القرن العشرين أعاد محمد راسم هذا الفن إلى الحياة في الجزائر. ولوّن هنري ماتيس الورق بالغواش ثم قصّه أشكالاً.',
      'الغواش يحتاج إلى تخطيط. يجف بسرعة، وأفتح مما يبدو وهو رطب. على الفنان أن يعرف اللون قبل أن يضعه.'
    ],
    tech: [
      ['المساحات المسطحة', 'مساحات لون معتمة ناعمة ومتساوية.'],
      ['الفاتح فوق الداكن', 'الطلاء المعتم يسمح بوضع الألوان الفاتحة فوق الداكنة.'],
      ['التفاصيل الدقيقة', 'فرش صغيرة جداً لتفاصيل على طريقة المنمنمات.'],
      ['الفرشاة الجافة', 'قليل جداً من الماء لملمس واضح.'],
      ['الورق المقصوص', 'ورق ملوّن بالغواش ثم يُقص ويُرتّب.']
    ],
    signs: [
      'سطح مطفأ طباشيري بلا لمعان.',
      'نتوءات فرشاة خفيفة عند حواف الأشكال المسطحة.',
      'فروق لون صغيرة داخل مساحات تبدو مسطحة من بعيد.',
      'شقوق دقيقة حيث وُضع اللون سميكاً جداً.'
    ],
    artists: [
      ['كمال الدين بهزاد', 'فارسي، نحو 1450–1535'],
      ['محمد راسم', 'جزائري، 1896–1975'],
      ['هنري ماتيس', 'فرنسي، 1869–1954']
    ],
    guide: [
      'أمِله تحت الضوء. الغواش يبقى مطفأً، وطباعته غالباً تلمع.',
      'استخدم عدسة مكبّرة. اللون الحقيقي يُظهر ملمس الفرشاة، والطباعة تُظهر نقاطاً.',
      'اسأل عن طريقة حمايته. الغواش قد يذوب بالماء، فيحتاج زجاجاً وغرفة جافة.'
    ]
  }
},
{
  id: 'printmaking', cat: 'traditional', type: 'printmaking',
  en: {
    name: 'Printmaking', feel: 'Patient',
    essence: 'An image carved or etched into a plate, then pressed by hand onto paper.',
    story: [
      'Printmaking lets an artist make a small number of originals from one plate. Each print is pulled by hand.',
      'Albrecht Dürer carved woodcuts so fine they looked like drawings. Rembrandt and Francisco Goya etched copper plates with light and darkness. In Japan, Hokusai’s Great Wave was printed from carved wooden blocks.',
      'It is slow, patient work. The artist works in reverse, thinking in mirror images. Only the press reveals the result, at the very end.'
    ],
    tech: [
      ['Woodcut', 'Carving away wood, so the raised parts print.'],
      ['Linocut', 'Like woodcut, but carved in soft linoleum.'],
      ['Etching', 'Lines bitten into metal with acid.'],
      ['Screen printing', 'Ink pushed through a fine mesh stencil.'],
      ['Edition numbering', 'Each print marked like 3/20 and signed in pencil.']
    ],
    signs: [
      'A plate mark: a pressed border around etchings.',
      'Ink that sits slightly raised, or pressed into the paper.',
      'Small differences between prints of the same edition.',
      'A pencil signature and edition number, never printed.'
    ],
    artists: [
      ['Albrecht Dürer', 'German, 1471–1528'],
      ['Rembrandt van Rijn', 'Dutch, 1606–1669'],
      ['Francisco Goya', 'Spanish, 1746–1828'],
      ['Katsushika Hokusai', 'Japanese, 1760–1849']
    ],
    guide: [
      'Check the number. 5/30 means print five of thirty. Smaller editions are rarer.',
      'Look for a pencil signature. A printed signature means a copy.',
      'Use a magnifier. Hand-pulled prints show solid ink, not the dots of a poster.'
    ]
  },
  ar: {
    name: 'الطباعة الفنية', feel: 'صبور',
    essence: 'صورة تُحفر في لوح، ثم تُضغط باليد على الورق.',
    story: [
      'الطباعة الفنية تتيح للفنان صنع عدد قليل من النسخ الأصلية من لوح واحد. وكل نسخة تُسحب باليد.',
      'حفر ألبرخت دورر طبعات خشبية دقيقة كأنها رسوم. وحفر رامبرانت وفرانثيسكو غويا ألواح النحاس بالضوء والعتمة. وفي اليابان طُبعت موجة هوكوساي العظيمة من قوالب خشبية محفورة.',
      'إنه عمل بطيء صبور. يعمل الفنان بالمقلوب، ويفكّر بصورة المرآة. ولا تكشف المطبعة النتيجة إلا في النهاية.'
    ],
    tech: [
      ['الحفر على الخشب', 'حفر الخشب لتطبع الأجزاء البارزة.'],
      ['الحفر على اللينوليوم', 'مثل الحفر على الخشب، لكن على مادة طرية.'],
      ['الحفر بالحمض', 'خطوط يحفرها الحمض في المعدن.'],
      ['الطباعة الحريرية', 'حبر يُدفع عبر شبكة دقيقة مفرّغة.'],
      ['ترقيم النسخ', 'كل نسخة تُرقّم مثل 3/20 وتوقَّع بالرصاص.']
    ],
    signs: [
      'أثر اللوح: إطار مضغوط حول طبعات الحفر.',
      'حبر بارز قليلاً، أو منضغط داخل الورق.',
      'فروق صغيرة بين نسخ الإصدار نفسه.',
      'توقيع ورقم نسخة بالرصاص، لا بالطباعة.'
    ],
    artists: [
      ['ألبرخت دورر', 'ألماني، 1471–1528'],
      ['رامبرانت', 'هولندي، 1606–1669'],
      ['فرانثيسكو غويا', 'إسباني، 1746–1828'],
      ['كاتسوشيكا هوكوساي', 'ياباني، 1760–1849']
    ],
    guide: [
      'تحقق من الرقم. 5/30 تعني النسخة الخامسة من ثلاثين. والإصدارات الأصغر أندر.',
      'ابحث عن توقيع بالرصاص. التوقيع المطبوع يعني نسخة مستنسخة.',
      'استخدم عدسة مكبّرة. الطباعة اليدوية حبرها متصل، لا نقاط كالملصقات.'
    ]
  }
},
{
  id: 'digital_illustration', cat: 'digital', type: 'digital_illustration',
  en: {
    name: 'Digital Illustration', feel: 'Vivid',
    essence: 'Drawing with a pen on a screen, line by line, by a human hand.',
    story: [
      'Digital illustration is drawing on a tablet with a pressure-sensitive pen. The tool is new. The skill is the same as pencil on paper.',
      'It grew with books, games, animation and magazines. Today many of the world’s favorite illustrators work this way. Even David Hockney has drawn landscapes on an iPad.',
      'The screen can undo a line. It cannot give an artist taste, patience or a steady hand. Those still come from years of practice.'
    ],
    tech: [
      ['Line art', 'Clean outlines drawn by hand with a stylus.'],
      ['Layers', 'Separate sheets for sketch, line, color and light.'],
      ['Flat coloring', 'Even base colors laid down before shading.'],
      ['Cel shading', 'Hard-edged shadows, like animation.'],
      ['Custom brushes', 'Brushes the artist builds to mimic real texture.']
    ],
    signs: [
      'Working files with many layers and early sketches.',
      'A time-lapse recording of the drawing process.',
      'Lines with natural pressure: tapered starts and ends.',
      'A personal style that stays consistent across many works.'
    ],
    artists: [
      ['David Hockney', 'British, born 1937'],
      ['Lois van Baarle', 'Dutch, born 1986'],
      ['Ilya Kuvshinov', 'Russian, born 1990']
    ],
    guide: [
      'Ask to see the layered file or a video of the process.',
      'Ask what you are buying: a signed limited print, or the file and its rights.',
      'Look at the artist’s other work. A real style stays consistent over years.'
    ]
  },
  ar: {
    name: 'الرسم التوضيحي الرقمي', feel: 'زاهٍ',
    essence: 'رسم بقلم على شاشة، خطاً بعد خط، بيد إنسان.',
    story: [
      'الرسم التوضيحي الرقمي رسم على لوح إلكتروني بقلم يستشعر الضغط. الأداة جديدة. والمهارة هي نفسها مهارة الرصاص على الورق.',
      'نما مع الكتب والألعاب والرسوم المتحركة والمجلات. واليوم يعمل به كثير من أحب الرسامين في العالم. حتى ديفيد هوكني رسم مناظر طبيعية على جهاز آيباد.',
      'الشاشة تستطيع التراجع عن خط. لكنها لا تمنح الفنان ذوقاً أو صبراً أو يداً ثابتة. هذه تأتي من سنوات التدريب.'
    ],
    tech: [
      ['الخطوط الأساسية', 'خطوط نظيفة تُرسم باليد بالقلم الرقمي.'],
      ['الطبقات', 'أوراق منفصلة للاسكتش والخط واللون والضوء.'],
      ['التلوين المسطح', 'ألوان أساسية متساوية قبل التظليل.'],
      ['التظليل الحاد', 'ظلال بحواف حادة، كالرسوم المتحركة.'],
      ['الفرش المخصّصة', 'فرش يصنعها الفنان لتحاكي ملمساً حقيقياً.']
    ],
    signs: [
      'ملفات عمل فيها طبقات كثيرة واسكتشات أولى.',
      'تسجيل مُسرّع لمراحل الرسم.',
      'خطوط بضغط طبيعي: بدايات ونهايات مدبّبة.',
      'أسلوب شخصي ثابت عبر أعمال كثيرة.'
    ],
    artists: [
      ['ديفيد هوكني', 'بريطاني، مواليد 1937'],
      ['لويس فان بارل', 'هولندية، مواليد 1986'],
      ['إيليا كوفشينوف', 'روسي، مواليد 1990']
    ],
    guide: [
      'اطلب رؤية الملف بطبقاته أو فيديو لمراحل العمل.',
      'اسأل عمّا تشتريه: طبعة موقّعة محدودة، أم الملف نفسه وحقوقه.',
      'تصفّح أعمال الفنان الأخرى. الأسلوب الحقيقي يبقى ثابتاً عبر السنين.'
    ]
  }
},
{
  id: 'digital_painting', cat: 'digital', type: 'digital_painting',
  en: {
    name: 'Digital Painting', feel: 'Cinematic',
    essence: 'Painting with light on a screen, brushstroke by brushstroke.',
    story: [
      'Digital painting borrows everything from oil and watercolor: light, color, edges and mood. Only the canvas has changed.',
      'It grew up in film and video games. There, artists paint the first look of new worlds. Painters like Craig Mullins showed it could feel as rich as a classical canvas.',
      'A strong digital painting still needs a human eye. Choosing what to show, and what to leave soft, is the art.'
    ],
    tech: [
      ['Blocking in', 'Big shapes of color first, details last.'],
      ['Value study', 'A black and white version to test the light.'],
      ['Color grading', 'Adjusting the whole mood, like in film.'],
      ['Texture brushes', 'Brushes that imitate canvas, paper or paint.'],
      ['Speed painting', 'Fast studies to explore an idea.']
    ],
    signs: [
      'A process you can follow: a rough start, then refinement.',
      'Visible brushstrokes and edges chosen by hand.',
      'Small, natural imperfections in light and anatomy.',
      'Sketches or studies that came before the final piece.'
    ],
    artists: [
      ['Craig Mullins', 'American, born 1964'],
      ['Sparth (Nicolas Bouvier)', 'French, born 1971'],
      ['David Hockney', 'British, born 1937']
    ],
    guide: [
      'Ask for the process: early sketches, layers or a recording.',
      'Prefer signed, numbered prints on archival paper.',
      'Make sure the license is clear, especially for commercial use.'
    ]
  },
  ar: {
    name: 'الرسم الرقمي', feel: 'سينمائي',
    essence: 'رسم بالضوء على شاشة، ضربة فرشاة بعد أخرى.',
    story: [
      'يستعير الرسم الرقمي كل شيء من الزيت والألوان المائية: الضوء واللون والحواف والإحساس. وحده القماش تغيّر.',
      'نشأ في السينما وألعاب الفيديو، حيث يرسم الفنانون أول صورة لعوالم جديدة. وأثبت رسامون مثل كريغ مولينز أنه قد يكون غنياً كلوحة كلاسيكية.',
      'اللوحة الرقمية القوية تحتاج دائماً إلى عين إنسان. فاختيار ما يُظهَر وما يُترك ناعماً هو الفن.'
    ],
    tech: [
      ['التكتيل الأولي', 'أشكال لون كبيرة أولاً، والتفاصيل في النهاية.'],
      ['دراسة القيم', 'نسخة بالأبيض والأسود لاختبار الضوء.'],
      ['ضبط الألوان', 'تعديل المزاج العام، كما في السينما.'],
      ['فرش الملمس', 'فرش تحاكي القماش أو الورق أو الطلاء.'],
      ['الرسم السريع', 'دراسات سريعة لاستكشاف فكرة.']
    ],
    signs: [
      'مراحل يمكن تتبّعها: بداية خشنة ثم تنقيح.',
      'ضربات فرشاة واضحة وحواف اختارتها اليد.',
      'عيوب صغيرة طبيعية في الضوء والتشريح.',
      'اسكتشات أو دراسات سبقت العمل النهائي.'
    ],
    artists: [
      ['كريغ مولينز', 'أمريكي، مواليد 1964'],
      ['سبارث (نيكولا بوفييه)', 'فرنسي، مواليد 1971'],
      ['ديفيد هوكني', 'بريطاني، مواليد 1937']
    ],
    guide: [
      'اطلب مراحل العمل: اسكتشات أولى أو طبقات أو تسجيل.',
      'فضّل الطبعات الموقّعة والمرقّمة على ورق يدوم طويلاً.',
      'تأكد أن الترخيص واضح، خاصة للاستخدام التجاري.'
    ]
  }
},
{
  id: 'sculpting_3d', cat: 'digital', type: '3d',
  en: {
    name: '3D Sculpting', feel: 'Dimensional',
    essence: 'Shaping virtual clay by hand, into forms you can turn and light.',
    story: [
      'Digital sculpting works like clay. The artist pushes, pulls and smooths a surface with a stylus.',
      'Its rules come from classical sculpture. Michelangelo and Auguste Rodin studied weight, balance and the body for a lifetime. Digital sculptors study the very same things.',
      'Many digital sculptures are later 3D printed, or cast in resin or bronze. The idea travels from the screen back into the real world.'
    ],
    tech: [
      ['Digital clay', 'Shaping a virtual surface with push and pull brushes.'],
      ['Retopology', 'Rebuilding a clean mesh over a finished sculpt.'],
      ['Texturing', 'Painting color and surface detail onto the form.'],
      ['Lighting', 'Placing virtual lights to reveal the form.'],
      ['3D printing', 'Turning the sculpt into a real object.']
    ],
    signs: [
      'Turntable views that show the sculpt from every side.',
      'Work in progress: rough blocks first, then detail.',
      'Asymmetry and personal touch, not a perfect mirrored shape.',
      'Anatomy and weight that come from study, not a ready-made model.'
    ],
    artists: [
      ['Michelangelo', 'Italian, 1475–1564'],
      ['Auguste Rodin', 'French, 1840–1917'],
      ['Zaha Hadid', 'Iraqi-British architect, 1950–2016']
    ],
    guide: [
      'Ask for turntable videos and in-progress screenshots.',
      'For printed pieces, ask about the material, size and edition number.',
      'Check that the model is original, not bought from an online library.'
    ]
  },
  ar: {
    name: 'النحت الرقمي ثلاثي الأبعاد', feel: 'مجسّم',
    essence: 'تشكيل طين افتراضي باليد، إلى أشكال تُدار وتُضاء.',
    story: [
      'النحت الرقمي يشبه العمل بالطين. يدفع الفنان السطح ويسحبه وينعّمه بالقلم الرقمي.',
      'قواعده تأتي من النحت الكلاسيكي. درس مايكل أنجلو وأوغست رودان الوزن والتوازن والجسد طوال حياتهما. والنحّاتون الرقميون يدرسون الأمور نفسها تماماً.',
      'كثير من المنحوتات الرقمية تُطبع لاحقاً بطابعة ثلاثية الأبعاد، أو تُصبّ بالراتنج أو البرونز. فتعود الفكرة من الشاشة إلى العالم الحقيقي.'
    ],
    tech: [
      ['الطين الرقمي', 'تشكيل سطح افتراضي بفرش الدفع والسحب.'],
      ['إعادة بناء الشبكة', 'بناء شبكة نظيفة فوق المنحوتة المكتملة.'],
      ['الإكساء', 'رسم اللون وتفاصيل السطح على الشكل.'],
      ['الإضاءة', 'وضع أضواء افتراضية لإظهار الشكل.'],
      ['الطباعة ثلاثية الأبعاد', 'تحويل المنحوتة إلى قطعة حقيقية.']
    ],
    signs: [
      'عرض دوّار يُظهر المنحوتة من كل جانب.',
      'مراحل العمل: كتل خشنة أولاً ثم تفاصيل.',
      'عدم تماثل ولمسة شخصية، لا شكلاً منعكساً مثالياً.',
      'تشريح ووزن يأتيان من الدراسة، لا من نموذج جاهز.'
    ],
    artists: [
      ['مايكل أنجلو', 'إيطالي، 1475–1564'],
      ['أوغست رودان', 'فرنسي، 1840–1917'],
      ['زها حديد', 'معمارية عراقية بريطانية، 1950–2016']
    ],
    guide: [
      'اطلب فيديو عرض دوّار ولقطات لمراحل العمل.',
      'للقطع المطبوعة، اسأل عن المادة والحجم ورقم النسخة.',
      'تأكد أن النموذج أصلي، لا مشترى من مكتبة على الإنترنت.'
    ]
  }
},
{
  id: 'collage', cat: 'mixed', type: 'collage',
  en: {
    name: 'Collage & Mixed Media', feel: 'Playful',
    essence: 'Paper, fabric, paint and found objects, joined into one surface.',
    story: [
      'In 1912, Pablo Picasso and Georges Braque glued real paper onto their pictures. Art was never the same again.',
      'Kurt Schwitters built works from tickets and scraps. Robert Rauschenberg joined paint with photos and objects. El Anatsui turns bottle caps into shining metal cloth.',
      'Collage turns everyday things into poetry. The artist chooses, cuts, places and glues. Every layer is a decision.'
    ],
    tech: [
      ['Assemblage', 'Building with real three-dimensional objects.'],
      ['Layering', 'Stacking paper, paint and image in depth.'],
      ['Found materials', 'Tickets, fabric, wood and scraps from daily life.'],
      ['Decoupage', 'Cut paper glued down and sealed under varnish.'],
      ['Papier collé', 'Pasted paper as part of a drawing or painting.']
    ],
    signs: [
      'Real edges you can feel: cut paper, torn fibers, raised layers.',
      'Glue marks or small wrinkles where paper met wet paste.',
      'Shadows cast by the layers when light comes from the side.',
      'Materials that age differently: paper yellows, metal shines.'
    ],
    artists: [
      ['Pablo Picasso', 'Spanish, 1881–1973'],
      ['Georges Braque', 'French, 1882–1963'],
      ['Kurt Schwitters', 'German, 1887–1948'],
      ['Robert Rauschenberg', 'American, 1925–2008'],
      ['El Anatsui', 'Ghanaian, born 1944']
    ],
    guide: [
      'Look from the side. Real collage has physical depth and edges.',
      'Ask about the materials and glue. Acid-free materials last much longer.',
      'A flat, glossy surface with no depth is usually a print.'
    ]
  },
  ar: {
    name: 'الكولاج والوسائط المختلطة', feel: 'مرِح',
    essence: 'ورق وقماش وطلاء وأشياء وجدها الفنان، تجتمع في سطح واحد.',
    story: [
      'في عام 1912 ألصق بابلو بيكاسو وجورج براك ورقاً حقيقياً على أعمالهما. ولم يعد الفن كما كان.',
      'بنى كورت شفيترز أعمالاً من التذاكر والقصاصات. وجمع روبرت راوشنبرغ الطلاء مع الصور والأشياء. ويحوّل إل أناتسوي أغطية القوارير إلى قماش معدني لامع.',
      'الكولاج يحوّل الأشياء اليومية إلى شعر. يختار الفنان ويقصّ ويضع ويلصق. وكل طبقة قرار.'
    ],
    tech: [
      ['التجميع', 'البناء بأشياء حقيقية مجسّمة.'],
      ['الطبقات', 'تراكم الورق والطلاء والصور في العمق.'],
      ['المواد الموجودة', 'تذاكر وقماش وخشب وقصاصات من الحياة اليومية.'],
      ['الديكوباج', 'ورق مقصوص يُلصق ويُغطّى بالورنيش.'],
      ['الورق الملصق', 'ورق ملصق كجزء من رسم أو لوحة.']
    ],
    signs: [
      'حواف حقيقية تلمسها: ورق مقصوص وألياف ممزقة وطبقات بارزة.',
      'آثار غراء أو تجاعيد صغيرة حيث لامس الورق الغراء الرطب.',
      'ظلال تلقيها الطبقات حين يأتي الضوء من الجانب.',
      'مواد تشيخ بشكل مختلف: الورق يصفرّ والمعدن يلمع.'
    ],
    artists: [
      ['بابلو بيكاسو', 'إسباني، 1881–1973'],
      ['جورج براك', 'فرنسي، 1882–1963'],
      ['كورت شفيترز', 'ألماني، 1887–1948'],
      ['روبرت راوشنبرغ', 'أمريكي، 1925–2008'],
      ['إل أناتسوي', 'غاني، مواليد 1944']
    ],
    guide: [
      'انظر من الجانب. الكولاج الحقيقي له عمق وحواف ملموسة.',
      'اسأل عن المواد والغراء. المواد الخالية من الحمض تدوم أطول بكثير.',
      'السطح المسطح اللامع بلا عمق غالباً طباعة.'
    ]
  }
},
{
  id: 'street_art', cat: 'modern', type: null,
  en: {
    name: 'Street Art', feel: 'Fearless',
    essence: 'Art made on walls and city streets, for everyone who walks by.',
    story: [
      'Street art grew from the graffiti of New York in the 1970s. Young artists painted trains and walls with their names and dreams.',
      'Keith Haring drew in subway stations with white chalk. Jean-Michel Basquiat began as a street poet before the galleries found him. Today eL Seed paints Arabic calligraphy across buildings, from Tunisia to Cairo.',
      'Street art is brave and public. It is made fast, often at night, in wind and heat. It speaks to people who might never enter a gallery.'
    ],
    tech: [
      ['Spray paint', 'Aerosol color for fast lines and soft fades.'],
      ['Stencilling', 'Cut templates for sharp, repeatable images.'],
      ['Wheatpaste', 'Paper posters glued to walls with flour paste.'],
      ['Muralism', 'Large planned paintings that cover whole walls.'],
      ['Calligraffiti', 'Calligraphy and graffiti joined in one gesture.']
    ],
    signs: [
      'Overspray: a soft mist of color around the edges of lines.',
      'Drips that follow gravity where the paint was heavy.',
      'Stencil edges with tiny bleeds and lifted corners.',
      'Hand-cut stencils with small, irregular curves.'
    ],
    artists: [
      ['Keith Haring', 'American, 1958–1990'],
      ['Jean-Michel Basquiat', 'American, 1960–1988'],
      ['eL Seed', 'French-Tunisian, born 1981'],
      ['Banksy', 'British, identity unknown']
    ],
    guide: [
      'For studio pieces, ask for a certificate from the artist or their official studio.',
      'Look for real spray texture: overspray and drips, not flat printed color.',
      'Be careful with famous names. Fakes are common, so buy from trusted sources.'
    ]
  },
  ar: {
    name: 'فن الشارع', feel: 'جسور',
    essence: 'فن يُرسم على الجدران وفي شوارع المدينة، لكل من يمرّ به.',
    story: [
      'نشأ فن الشارع من كتابات الجدران في نيويورك في السبعينيات. رسم فنانون شباب القطارات والجدران بأسمائهم وأحلامهم.',
      'رسم كيث هارينغ في محطات المترو بالطباشير الأبيض. وبدأ جان ميشيل باسكيات شاعراً في الشوارع قبل أن تكتشفه المعارض. واليوم يرسم إل سيد الخط العربي على المباني، من تونس إلى القاهرة.',
      'فن الشارع شجاع وعلني. يُصنع بسرعة، غالباً في الليل، وسط الريح والحر. ويخاطب أناساً قد لا يدخلون معرضاً أبداً.'
    ],
    tech: [
      ['رذاذ الطلاء', 'لون بالبخاخ لخطوط سريعة وتدرّجات ناعمة.'],
      ['الاستنسل', 'قوالب مفرّغة لصور حادة قابلة للتكرار.'],
      ['لصق الورق', 'ملصقات ورقية تُلصق على الجدران بعجينة الدقيق.'],
      ['الجداريات', 'لوحات كبيرة مخطّطة تغطي جدراناً كاملة.'],
      ['الكاليغرافيتي', 'الخط والغرافيتي في حركة واحدة.']
    ],
    signs: [
      'رذاذ زائد: ضباب لون ناعم حول حواف الخطوط.',
      'قطرات تتبع الجاذبية حيث كان اللون كثيفاً.',
      'حواف استنسل بتسرّبات صغيرة وزوايا مرتفعة.',
      'استنسل مقصوص باليد بمنحنيات صغيرة غير منتظمة.'
    ],
    artists: [
      ['كيث هارينغ', 'أمريكي، 1958–1990'],
      ['جان ميشيل باسكيات', 'أمريكي، 1960–1988'],
      ['إل سيد', 'فرنسي تونسي، مواليد 1981'],
      ['بانكسي', 'بريطاني، هويته مجهولة']
    ],
    guide: [
      'للأعمال المرسومة في المرسم، اطلب شهادة من الفنان أو من مرسمه الرسمي.',
      'ابحث عن ملمس الرذاذ الحقيقي: رذاذ زائد وقطرات، لا لون مطبوع مسطح.',
      'احذر من الأسماء الشهيرة. التقليد شائع، فاشترِ من مصادر موثوقة.'
    ]
  }
},
{
  id: 'hyperrealism', cat: 'modern', type: null,
  en: {
    name: 'Hyperrealism', feel: 'Exact',
    essence: 'Painting sharper than a photograph, built from thousands of patient marks.',
    story: [
      'Photorealism began in America in the late 1960s. Painters copied photographs so closely that people could not believe it was paint.',
      'Chuck Close painted giant faces, one small square at a time. Richard Estes painted city windows full of reflections. Ron Mueck sculpts people so real they seem to breathe.',
      'Hyperrealism goes further than the camera. The artist adds feeling, weight and focus. One painting can take many months.'
    ],
    tech: [
      ['Layered glazing', 'Many thin layers for a glass-like depth.'],
      ['Airbrushing', 'A fine spray for smooth gradients.'],
      ['Fine detailing', 'Tiny brushes for hairs, pores and reflections.'],
      ['Value mapping', 'Planning every shade from light to dark.'],
      ['Grid transfer', 'A grid to copy the drawing in exact proportion.']
    ],
    signs: [
      'Paint texture under a magnifier, even where it looks like a photo.',
      'Faint grid or pencil lines near the edges.',
      'Edges slightly softer or sharper than a camera would make.',
      'Progress photos that show months of work.'
    ],
    artists: [
      ['Richard Estes', 'American, born 1932'],
      ['Chuck Close', 'American, 1940–2021'],
      ['Ron Mueck', 'Australian, born 1958']
    ],
    guide: [
      'See it in person, or ask for close-up photos with light from the side.',
      'Ask for progress photos. Hyperrealism is slow, and artists document it.',
      'A perfectly flat surface with no texture may be a print, not paint.'
    ]
  },
  ar: {
    name: 'الواقعية المفرطة', feel: 'مُتقن',
    essence: 'رسم أدقّ من الصورة الفوتوغرافية، يُبنى من آلاف اللمسات الصبورة.',
    story: [
      'بدأت الواقعية الفوتوغرافية في أمريكا في أواخر الستينيات. نسخ الرسامون الصور بدقة جعلت الناس لا يصدّقون أنها رسم.',
      'رسم تشاك كلوز وجوهاً ضخمة، مربعاً صغيراً بعد آخر. ورسم ريتشارد إستس نوافذ المدن المليئة بالانعكاسات. وينحت رون ميويك أناساً حقيقيين حتى تكاد تسمع أنفاسهم.',
      'الواقعية المفرطة تذهب أبعد من الكاميرا. يضيف الفنان الإحساس والثقل والتركيز. وقد تستغرق لوحة واحدة شهوراً كثيرة.'
    ],
    tech: [
      ['التزجيج المتعدد', 'طبقات رقيقة كثيرة لعمق يشبه الزجاج.'],
      ['الرذاذ الهوائي', 'رذاذ دقيق لتدرّجات ناعمة.'],
      ['التفصيل الدقيق', 'فرش صغيرة للشعر والمسام والانعكاسات.'],
      ['خريطة القيم الضوئية', 'تخطيط كل درجة من الفاتح إلى الداكن.'],
      ['النقل بالشبكة', 'شبكة لنقل الرسم بنسب دقيقة.']
    ],
    signs: [
      'ملمس طلاء يظهر تحت العدسة، حتى حيث يبدو كالصورة.',
      'شبكة أو خطوط رصاص خفيفة قرب الحواف.',
      'حواف أنعم أو أحدّ قليلاً مما تصنعه الكاميرا.',
      'صور مراحل تُظهر شهوراً من العمل.'
    ],
    artists: [
      ['ريتشارد إستس', 'أمريكي، مواليد 1932'],
      ['تشاك كلوز', 'أمريكي، 1940–2021'],
      ['رون ميويك', 'أسترالي، مواليد 1958']
    ],
    guide: [
      'شاهده بنفسك، أو اطلب صوراً قريبة والضوء من الجانب.',
      'اطلب صور المراحل. الواقعية المفرطة بطيئة، والفنانون يوثّقونها.',
      'السطح المسطح تماماً بلا ملمس قد يكون طباعة لا رسماً.'
    ]
  }
},
{
  id: 'calligraphy', cat: 'modern', type: 'calligraphy',
  en: {
    name: 'Arabic Calligraphy', feel: 'Rhythmic',
    essence: 'The beloved art of Arabic script, where every letter is measured and alive.',
    story: [
      'Arabic calligraphy grew with the Quran. Writing the word beautifully became an act of devotion.',
      'In Baghdad, Ibn Muqla set rules of proportion based on the rhombic dot. Ibn al-Bawwab refined them further. Ottoman masters like Hamid Aytaç carried the tradition into modern times.',
      'Today, artists like Hassan Massoudy bring the letter into contemporary art. The reed pen, the ink and the breath of the calligrapher remain the same.'
    ],
    tech: [
      ['Thuluth', 'A grand, curving script for titles and monuments.'],
      ['Naskh', 'A clear, balanced script used to copy books.'],
      ['Diwani', 'A flowing script once used in Ottoman courts.'],
      ['Reed pen (qalam)', 'A cut reed whose angle shapes every stroke.'],
      ['Rhombic dot', 'The dot used to measure every letter’s proportion.']
    ],
    signs: [
      'Ink that pools slightly at the start of a stroke.',
      'Thick-to-thin strokes made by the angle of the reed pen.',
      'Tiny differences between letters that repeat.',
      'Hand-prepared, polished paper in classical works.'
    ],
    artists: [
      ['Ibn Muqla', 'Abbasid Baghdad, 885–940'],
      ['Ibn al-Bawwab', 'Abbasid Baghdad, died 1022'],
      ['Hamid Aytaç', 'Turkish, 1891–1982'],
      ['Hassan Massoudy', 'Iraqi, born 1944']
    ],
    guide: [
      'Ask about the ijaza. A trained calligrapher earns this license from a master.',
      'Check the proportions. Letters should follow the classical rules of the script.',
      'Ask about the materials: hand-made ink, reed pen and paper.'
    ]
  },
  ar: {
    name: 'الخط العربي', feel: 'إيقاعي',
    essence: 'فن الحرف العربي الذي نحبه، حيث كل حرف موزون ونابض بالحياة.',
    story: [
      'نما الخط العربي مع القرآن الكريم. وصارت كتابة الكلمة بجمال ضرباً من التعبّد.',
      'في بغداد وضع ابن مقلة قواعد النِّسب معتمداً على النقطة المعيّنة. ثم هذّبها ابن البوّاب. وحمل أساتذة عثمانيون مثل حامد الآمدي التقليد إلى العصر الحديث.',
      'واليوم يحمل فنانون مثل حسن المسعود الحرف إلى الفن المعاصر. ويبقى القلم القصب والحبر ونَفَس الخطّاط كما كانت.'
    ],
    tech: [
      ['الثلث', 'خط مهيب منحنٍ للعناوين والمباني.'],
      ['النسخ', 'خط واضح متوازن تُنسخ به الكتب.'],
      ['الديواني', 'خط منساب استُخدم في الدواوين العثمانية.'],
      ['القلم القصب', 'قصبة مبرية تحدد زاويتها شكل كل ضربة.'],
      ['النقطة المعيّنة', 'النقطة التي تُقاس بها نِسب كل حرف.']
    ],
    signs: [
      'حبر يتجمّع قليلاً عند بداية الضربة.',
      'ضربات تتدرج من السميك إلى الرقيق بفعل زاوية القلم.',
      'فروق صغيرة بين الحروف المتكررة.',
      'ورق مصقول مُعدّ يدوياً في الأعمال الكلاسيكية.'
    ],
    artists: [
      ['ابن مقلة', 'بغداد العباسية، 885–940'],
      ['ابن البوّاب', 'بغداد العباسية، توفي 1022'],
      ['حامد الآمدي', 'تركي، 1891–1982'],
      ['حسن المسعود', 'عراقي، مواليد 1944']
    ],
    guide: [
      'اسأل عن الإجازة. الخطّاط المتمكّن ينالها من أستاذه.',
      'تحقق من النِّسب. يجب أن تتبع الحروف القواعد الكلاسيكية للخط.',
      'اسأل عن المواد: الحبر اليدوي والقلم القصب والورق.'
    ]
  }
},
{
  id: 'islamic_geometric', cat: 'modern', type: 'islamic_geometric',
  en: {
    name: 'Islamic Geometric Art', feel: 'Infinite',
    essence: 'Endless patterns from a circle and a line, a quiet meditation on harmony.',
    story: [
      'Islamic geometric art begins with a single circle. From it, artists draw stars, polygons and patterns that never end.',
      'Mathematicians like Abu al-Wafa’ al-Buzjani wrote guides for craftsmen. Their work shines in the tiles of the Alhambra. It lives in the carved wood of Cairo and the domes of Isfahan.',
      'In the 20th century, Monir Shahroudy Farmanfarmaian turned these patterns into mirror mosaics. The tradition lives on, drawn by hand with compass and ruler.'
    ],
    tech: [
      ['Compass and straightedge', 'Every pattern starts from circles and lines.'],
      ['Girih', 'Interlacing lines that form stars and polygons.'],
      ['Tessellation', 'Shapes that fit together with no gaps.'],
      ['Arabesque', 'Flowing plant forms that grow endlessly.'],
      ['Zellige', 'Hand-cut glazed tiles set into patterns.']
    ],
    signs: [
      'Faint compass holes and construction lines under the paint.',
      'Tiny variations between shapes that repeat.',
      'Hand-painted color with slight brush texture at the edges.',
      'Gold leaf that catches the light unevenly.'
    ],
    artists: [
      ['Abu al-Wafa’ al-Buzjani', 'Persian mathematician, 940–998'],
      ['The Nasrid masters of the Alhambra', 'Andalusian, 13th–14th century'],
      ['Monir Shahroudy Farmanfarmaian', 'Iranian, 1922–2019']
    ],
    guide: [
      'Look closely for construction lines. Hand-drawn patterns show them faintly.',
      'Perfect machine repetition with no variation is likely a print.',
      'Ask the artist to explain the base grid, such as an 8-fold or 12-fold pattern.'
    ]
  },
  ar: {
    name: 'الفن الهندسي الإسلامي', feel: 'لانهائي',
    essence: 'أنماط لا تنتهي من دائرة وخط، تأمّل هادئ في الانسجام.',
    story: [
      'يبدأ الفن الهندسي الإسلامي بدائرة واحدة. ومنها يرسم الفنان النجوم والمضلّعات وأنماطاً لا تنتهي.',
      'كتب علماء رياضيات مثل أبي الوفاء البوزجاني أدلة للحرفيين. ويتألق عملهم في بلاط قصر الحمراء وخشب القاهرة المحفور وقباب أصفهان.',
      'وفي القرن العشرين حوّلت منير شاهرودي فرمانفرمائيان هذه الأنماط إلى فسيفساء من المرايا. ويستمر التقليد مرسوماً باليد، بالفرجار والمسطرة.'
    ],
    tech: [
      ['الفرجار والمسطرة', 'كل نمط يبدأ من دوائر وخطوط.'],
      ['الكره', 'خطوط متشابكة تشكّل النجوم والمضلّعات.'],
      ['التبليط', 'أشكال تتلاءم معاً بلا فراغات.'],
      ['الأرابيسك', 'أشكال نباتية منسابة تنمو بلا نهاية.'],
      ['الزليج', 'بلاط مزجج مقطوع باليد يُرصّ في أنماط.']
    ],
    signs: [
      'ثقوب فرجار صغيرة وخطوط بناء خفيفة تحت اللون.',
      'فروق صغيرة بين الأشكال المتكررة.',
      'لون مرسوم باليد بملمس فرشاة خفيف عند الحواف.',
      'ورق ذهب يلتقط الضوء بشكل متفاوت.'
    ],
    artists: [
      ['أبو الوفاء البوزجاني', 'عالم رياضيات فارسي، 940–998'],
      ['صنّاع قصر الحمراء من بني نصر', 'أندلسيون، القرنان 13–14'],
      ['منير شاهرودي فرمانفرمائيان', 'إيرانية، 1922–2019']
    ],
    guide: [
      'ابحث عن خطوط البناء عن قرب. الأنماط المرسومة باليد تُظهرها بخفة.',
      'التكرار الآلي المثالي بلا أي فرق غالباً طباعة.',
      'اطلب من الفنان شرح الشبكة الأساسية للنمط، مثل الثمانية أو الاثني عشرية.'
    ]
  }
}
];
