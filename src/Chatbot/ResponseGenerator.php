<?php
declare(strict_types=1);

namespace MarocPC\Chatbot;

class ResponseGenerator
{
    private string $language = 'english';

    public function setLanguage(string $lang): void
    {
        if ($lang === 'darija') {
            $lang = 'arabic';
        }
        $this->language = $lang;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    private array $greetingPool = [
        'english' => [
            "Hey! What PC part or laptop can I help you track down today?",
            "Hi there! Looking for a premium laptop, specific hardware, or want me to suggest some top picks?",
            "Welcome to Maroc PC! Laptops, GPUs, CPUs, RAM â€” what are we hunting for today?",
            "Hey! Ready to build something great or find an awesome laptop? Tell me what you need.",
            "Hello! I'm your hardware guide. What laptop or component are you after?"
        ],
        'french' => [
            "Bonjour ! Quel composant PC ou ordinateur portable puis-je vous aider Ã  trouver aujourd'hui ?",
            "Salut ! Vous cherchez un ordinateur portable premium, du matÃ©riel spÃ©cifique, ou vous voulez que je vous propose mes meilleurs choix ?",
            "Bienvenue chez Maroc PC ! Ordinateurs portables, GPU, CPU, RAM â€” que cherchons-nous aujourd'hui ?",
            "Salut ! PrÃªt Ã  monter une super config ou Ã  trouver un ordinateur portable gÃ©nial ? Dites-moi ce qu'il vous faut.",
            "Bonjour ! Je suis votre guide matÃ©riel. Quel ordinateur portable ou composant recherchez-vous ?"
        ],
        'arabic' => [
            "مرحبا. ما قطعة الكمبيوتر أو الحاسوب المحمول الذي تريد العثور عليه اليوم؟",
            "أهلا بك. هل تبحث عن حاسوب محمول مميز، قطعة معينة، أم تريد اقتراحات مناسبة؟",
            "مرحبا بك في Maroc PC. حواسيب محمولة، بطاقات رسومية، معالجات، ذاكرة، ماذا نبحث عنه اليوم؟",
            "جاهز لبناء جهاز ممتاز أو اختيار حاسوب محمول مناسب؟ أخبرني بما تحتاجه.",
            "أنا دليلك للعتاد. ما الحاسوب المحمول أو المكون الذي تبحث عنه؟"
        ]
    ];

    private array $farewellPool = [
        'english' => [
            "Take care! Come back when you're ready to upgrade. ðŸ‘‹",
            "Goodbye! Happy building!",
            "See you around! Feel free to ask if anything else comes to mind.",
            "Catch you later! May your FPS always be high. ðŸ˜„"
        ],
        'french' => [
            "Prenez soin de vous ! Revenez quand vous serez prÃªt pour une mise Ã  niveau. ðŸ‘‹",
            "Au revoir ! Bon montage !",
            "Ã€ bientÃ´t ! N'hÃ©sitez pas Ã  me solliciter si vous avez d'autres questions.",
            "Ã€ la prochaine ! Que votre FPS soit toujours Ã©levÃ©. ðŸ˜„"
        ],
        'arabic' => [
            "إلى اللقاء. عد إلينا عندما تكون مستعدا للترقية.",
            "وداعا، ونتمنى لك تجميعة موفقة.",
            "أراك لاحقا. اسألني في أي وقت إذا احتجت إلى شيء آخر.",
            "إلى اللقاء. أتمنى أن تبقى إطاراتك عالية دائما."
        ]
    ];

    private array $gratitudePool = [
        'english' => [
            "Happy to help! Anything else I can dig up for you?",
            "You're welcome! Let me know if you want to compare specs or check stock on something else.",
            "Anytime! Got more components or laptops on your list?",
            "Glad that was useful! Just shout if you need anything else.",
            "My pleasure! Need help with another part or laptop?"
        ],
        'french' => [
            "Ravi de vous aider ! Y a-t-il autre chose que je puisse chercher pour vous ?",
            "Je vous en prie ! N'hÃ©sitez pas si vous voulez comparer des spÃ©cifications ou vÃ©rifier le stock d'un autre article.",
            "Avec plaisir ! Avez-vous d'autres composants ou ordinateurs portables sur votre liste ?",
            "Content que cela ait Ã©tÃ© utile ! Faites-moi signe si vous avez besoin d'autre chose.",
            "Tout le plaisir est pour moi ! Besoin d'aide pour une autre piÃ¨ce ou un autre laptop ?"
        ],
        'arabic' => [
            "يسعدني ذلك. هل تريد أن أبحث لك عن شيء آخر؟",
            "على الرحب والسعة. أخبرني إذا أردت مقارنة المواصفات أو التحقق من توفر منتج آخر.",
            "في خدمتك دائما. هل لديك مكونات أو حواسيب محمولة أخرى في قائمتك؟",
            "سعيد أن ذلك كان مفيدا. أخبرني إذا احتجت إلى أي شيء آخر.",
            "بكل سرور. هل تحتاج مساعدة في قطعة أو حاسوب محمول آخر؟"
        ]
    ];

    private array $helpPool = [
        'english' => [
            "Sure! You can ask me things like:\nâ€¢ \"Show me a premium gaming laptop\"\nâ€¢ \"Cheapest SSD you have\"\nâ€¢ \"Ryzen 5 CPUs on sale\"\nâ€¢ \"Best rated RAM\"\nâ€¢ \"ASUS ROG Strix laptops\"\n\nWhat would you like to search for?",
            "I can help you find premium laptops, CPUs, GPUs, RAM, storage, and more. Just describe what you're after â€” brand, type, budget â€” and I'll pull up the best options from our inventory!",
            "Try asking something like \"gaming laptop under 15000\", \"RTX 4070\", \"budget RAM\", or even \"MacBook Pro\". I'll take it from there!"
        ],
        'french' => [
            "Bien sÃ»r ! Vous pouvez me demander des choses comme :\nâ€¢ Â« Montre-moi un ordinateur portable de jeu premium Â»\nâ€¢ Â« Le SSD le moins cher que vous avez Â»\nâ€¢ Â« CPU Ryzen 5 en promo Â»\nâ€¢ Â« RAM la mieux notÃ©e Â»\nâ€¢ Â« Laptops ASUS ROG Strix Â»\n\nQue souhaitez-vous rechercher ?",
            "Je peux vous aider Ã  trouver des ordinateurs portables premium, des processeurs, des cartes graphiques, de la RAM, du stockage, etc. DÃ©crivez simplement ce que vous cherchez (marque, type, budget) et je trouverai les meilleures options dans notre stock !",
            "Essayez de demander Â« PC portable gamer Ã  moins de 15000 Â», Â« RTX 4070 Â», Â« RAM pas cher Â» ou mÃªme Â« MacBook Pro Â». Je m'occupe du reste !"
        ],
        'arabic' => [
            "بالتأكيد. يمكنك أن تسألني مثلا:\n• \"اعرض لي حاسوب ألعاب ممتاز\"\n• \"أرخص SSD متوفر\"\n• \"معالجات Ryzen 5 في التخفيض\"\n• \"أفضل ذاكرة RAM تقييما\"\n• \"حواسيب ASUS ROG Strix\"\n\nما الذي تريد البحث عنه؟",
            "أستطيع مساعدتك في العثور على حواسيب محمولة، معالجات، بطاقات رسومية، ذاكرة، تخزين وأكثر. صف ما تبحث عنه من حيث العلامة أو النوع أو الميزانية، وسأعرض أفضل الخيارات من المخزون.",
            "جرب أن تسأل مثلا: \"حاسوب ألعاب أقل من 15000\" أو \"RTX 4070\" أو \"RAM اقتصادية\" أو \"MacBook Pro\"، وسأتولى الباقي."
        ]
    ];

    private array $rmaPool = [
        'english' => [
            "Need to return an item or request technical support? Maroc PC has your back! We offer a **14-day return window** for complete packages and a **48h diagnostic promise** for warranty or repair tickets.\n\nYou can easily open an after-sales ticket and track your request directly on our dedicated page: [**Returns, Refunds & After-Sales Desk**](returns-refunds.php).\n\nIf you have a damaged package or missing items, we triage these priority cases within 24 hours!"
        ],
        'french' => [
            "Besoin de retourner un article ou de demander une assistance technique ? Maroc PC s'occupe de tout ! Nous offrons une **pÃ©riode de retour de 14 jours** pour les colis complets et une **garantie de diagnostic sous 48h** pour les cas de garantie ou de rÃ©paration.\n\nVous pouvez facilement ouvrir un ticket SAV et suivre votre demande sur notre page dÃ©diÃ©e : [**Service AprÃ¨s-Vente & Retours**](returns-refunds.php).\n\nEn cas de colis endommagÃ© ou d'articles manquants, nous traitons ces dossiers en prioritÃ© absolue sous 24h !"
        ],
        'arabic' => [
            "هل تحتاج إلى إرجاع منتج أو طلب دعم تقني؟ Maroc PC معك. نوفر **مهلة إرجاع 14 يوما** للطلبات الكاملة و **وعد تشخيص خلال 48 ساعة** لتذاكر الضمان أو الإصلاح.\n\nيمكنك فتح تذكرة ما بعد البيع وتتبعها مباشرة من صفحة: [**الإرجاع والاسترداد وخدمة ما بعد البيع**](returns-refunds.php).\n\nإذا وصل الطرد متضررا أو ناقصا، نعالج هذه الحالات ذات الأولوية خلال 24 ساعة."
        ]
    ];

    private array $laptopFinderPool = [
        'english' => [
            "Looking for the perfect laptop? Instead of raw specs, our outcome-oriented **Laptop Finder** matches your life and usage! Tell me what you need (Gaming, Office, Creativity, or Portability) and what screen quality and battery life you want.\n\nAlternatively, you can open and run our interactive [**Laptop Finder Wizard**](laptop-finder.php) right now to see the best matching choices computed instantly using outcome-based scores!"
        ],
        'french' => [
            "Vous cherchez l'ordinateur portable idÃ©al ? Au lieu de simples caractÃ©ristiques techniques, notre **Laptop Finder** basÃ© sur vos besoins rÃ©els cible parfaitement votre profil ! Dites-moi l'usage prÃ©vu (Jeux, Bureau, CrÃ©ation, ou PortabilitÃ©), l'autonomie et la qualitÃ© d'Ã©cran recherchÃ©es.\n\nVous pouvez Ã©galement lancer notre [**Laptop Finder Interactif**](laptop-finder.php) dÃ¨s maintenant pour trouver le modÃ¨le qui vous convient le mieux en quelques clics !"
        ],
        'arabic' => [
            "تبحث عن الحاسوب المحمول المناسب؟ بدلا من التركيز على المواصفات فقط، يساعدك **Laptop Finder** على الاختيار حسب استخدامك الحقيقي. أخبرني هل تحتاجه للألعاب، المكتب، الإبداع، أو التنقل، وما جودة الشاشة والبطارية التي تريدها.\n\nيمكنك أيضا فتح [**معالج اختيار الحاسوب المحمول**](laptop-finder.php) الآن لرؤية أفضل الخيارات المحسوبة فورا."
        ]
    ];

    private array $orderStatusHelpPool = [
        'english' => [
            "I'd love to help you track your order! Please type **Track [Order ID]** (for example: `Track #1024` or `Track 1024`) and I will immediately query our real-time database to give you its status!"
        ],
        'french' => [
            "Je serais ravi de vous aider Ã  suivre votre commande ! Veuillez taper **Track [ID de commande]** (par exemple : `Track #1024` ou `Track 1024`) et je vais immÃ©diatement interroger notre base de donnÃ©es pour vous donner le statut en direct !"
        ],
        'arabic' => [
            "يسعدني مساعدتك في تتبع طلبك. اكتب **Track [رقم الطلب]** مثل `Track #1024` أو `Track 1024`، وسأبحث في قاعدة البيانات فورا لأعرض حالته."
        ]
    ];

    private array $confusedPool = [
        'english' => [
            "I didn't quite catch that. Try something like \"RTX 3060\", \"Ryzen 5\", \"gaming laptop\", or \"budget SSD\".",
            "Hmm, I'm best at finding PC components and laptops. Try specifying a category like Laptop, GPU, CPU, RAM, or a brand name.",
            "Not sure I followed that. Could you rephrase? For example: \"show me a good graphics card\" or \"cheapest laptop\".",
            "I'm tuned for hardware and laptop talk! Ask me about specific parts, brands, or categories."
        ],
        'french' => [
            "Je n'ai pas tout Ã  fait compris. Essayez quelque chose comme Â« RTX 3060 Â», Â« Ryzen 5 Â», Â« pc portable Â» ou Â« SSD pas cher Â».",
            "Hmm, je suis spÃ©cialisÃ© dans la recherche de composants PC et d'ordinateurs portables. PrÃ©cisez une catÃ©gorie (Laptop, GPU, CPU, RAM) ou une marque.",
            "Je ne suis pas sÃ»r de vous avoir suivi. Pourriez-vous reformuler ? Par exemple : Â« montre-moi une bonne carte graphique Â» ou Â« pc portable le moins cher Â».",
            "Je suis configurÃ© pour parler matÃ©riel et ordinateurs portables ! Demandez-moi des piÃ¨ces spÃ©cifiques, des marques ou des catÃ©gories."
        ],
        'arabic' => [
            "لم أفهم تماما. جرب كتابة شيء مثل \"RTX 3060\" أو \"Ryzen 5\" أو \"حاسوب ألعاب\" أو \"SSD اقتصادي\".",
            "أنا أفضل في البحث عن مكونات الكمبيوتر والحواسيب المحمولة. حدد فئة مثل Laptop أو GPU أو CPU أو RAM أو اسم علامة تجارية.",
            "لست متأكدا أنني فهمت. هل يمكنك إعادة الصياغة؟ مثلا: \"اعرض لي بطاقة رسومية جيدة\" أو \"أرخص حاسوب محمول\".",
            "أنا مخصص لمساعدتك في العتاد والحواسيب المحمولة. اسألني عن قطعة، علامة تجارية، أو فئة محددة."
        ]
    ];

    private array $successPool = [
        'english' => [
            "Here's what I found in our inventory:",
            "Nice, I pulled up some great options for you:",
            "These caught my eye based on what you said:",
            "Found a few solid matches â€” take a look:",
            "Here are some top picks from our stock:"
        ],
        'french' => [
            "Voici ce que j'ai trouvÃ© dans notre inventaire :",
            "Super, j'ai dÃ©nichÃ© d'excellentes options pour vous :",
            "Voici les meilleurs choix selon vos critÃ¨res :",
            "J'ai trouvÃ© quelques bonnes correspondances â€” jetez-y un Å“il :",
            "Voici les meilleurs choix disponibles dans notre stock :"
        ],
        'arabic' => [
            "هذا ما وجدته في مخزوننا:",
            "وجدت لك بعض الخيارات الممتازة:",
            "هذه أفضل الترشيحات حسب ما ذكرت:",
            "وجدت بعض النتائج المناسبة. تفضل:",
            "هذه بعض أفضل الخيارات المتوفرة حاليا:"
        ]
    ];

    private array $fallbackPool = [
        'english' => [
            "I couldn't find an exact match, but here are some popular picks that might work:",
            "That specific item is a bit elusive â€” here's what's hot right now instead:",
            "Couldn't nail that down exactly, but these highly-rated alternatives might do the trick:"
        ],
        'french' => [
            "Je n'ai pas trouvÃ© de correspondance exacte, mais voici des articles trÃ¨s populaires qui pourraient vous intÃ©resser :",
            "Cet article spÃ©cifique est introuvable pour le moment â€” voici les meilleures ventes actuelles :",
            "Impossible de trouver exactement cela, mais ces alternatives trÃ¨s bien notÃ©es feront peut-Ãªtre l'affaire :"
        ],
        'arabic' => [
            "لم أجد تطابقا دقيقا، لكن هذه خيارات شائعة قد تناسبك:",
            "هذا المنتج غير متوفر بسهولة حاليا، وهذه أفضل البدائل المتاحة:",
            "لم أتمكن من إيجاد نفس الطلب تماما، لكن هذه البدائل ذات تقييم جيد وقد تفي بالغرض:"
        ]
    ];

    private array $budgetPool = [
        'english' => [
            "Love a good deal! Here are the best-value options we have right now:",
            "Bargain hunting? Here are the biggest discounts in our store:",
            "I've filtered for the best price drops â€” check these out:"
        ],
        'french' => [
            "On adore les bonnes affaires ! Voici les options offrant le meilleur rapport qualitÃ©-prix en ce moment :",
            "Ã€ la recherche d'un bon plan ? Voici les plus grosses rÃ©ductions de notre boutique :",
            "J'ai filtrÃ© les meilleures baisses de prix â€” regardez ceci :",
        ],
        'arabic' => [
            "تحب الصفقات الجيدة؟ هذه أفضل الخيارات من حيث القيمة المتوفرة الآن:",
            "تبحث عن عرض مناسب؟ هذه أكبر التخفيضات في متجرنا حاليا:",
            "قمت بتصفية أفضل انخفاضات الأسعار لك. تفضل:"
        ]
    ];

    private array $stockCheckPool = [
        'english' => [
            "Let me check what we have in stock for you...",
        ],
        'french' => [
            "Laissez-moi vÃ©rifier nos disponibilitÃ©s en stock...",
        ],
        'arabic' => [
            "دعني أتحقق من المخزون المتوفر لك...",
        ]
    ];

    private array $comparingPool = [
        'english' => [
            "Here are some options you can compare side by side:",
            "Good call comparing before buying â€” here's what I found:",
            "Let me line up a few options for you:"
        ],
        'french' => [
            "Voici des options que vous pouvez comparer cÃ´te Ã  cÃ´te :",
            "Excellente idÃ©e de comparer avant d'acheter â€” voici mes trouvailles :",
            "Laissez-moi aligner quelques options pour vous :"
        ],
        'arabic' => [
            "هذه خيارات يمكنك مقارنتها جنبا إلى جنب:",
            "قرار جيد أن تقارن قبل الشراء. هذا ما وجدته:",
            "دعني أرتب لك بعض الخيارات للمقارنة:"
        ]
    ];

    /**
     * Pick a random item from array pool.
     */
    public function getRandom(array $array): string
    {
        return $array[array_rand($array)];
    }

    public function getGreeting(): string
    {
        return $this->getRandom($this->greetingPool[$this->language] ?? $this->greetingPool['english']);
    }
    public function getFarewell(): string
    {
        return $this->getRandom($this->farewellPool[$this->language] ?? $this->farewellPool['english']);
    }
    public function getGratitude(): string
    {
        return $this->getRandom($this->gratitudePool[$this->language] ?? $this->gratitudePool['english']);
    }
    public function getHelp(): string
    {
        return $this->getRandom($this->helpPool[$this->language] ?? $this->helpPool['english']);
    }
    public function getConfused(): string
    {
        return $this->getRandom($this->confusedPool[$this->language] ?? $this->confusedPool['english']);
    }
    public function getRma(): string
    {
        return $this->getRandom($this->rmaPool[$this->language] ?? $this->rmaPool['english']);
    }
    public function getLaptopFinder(): string
    {
        return $this->getRandom($this->laptopFinderPool[$this->language] ?? $this->laptopFinderPool['english']);
    }
    public function getOrderStatusHelp(): string
    {
        return $this->getRandom($this->orderStatusHelpPool[$this->language] ?? $this->orderStatusHelpPool['english']);
    }
    public function getSuccess(): string
    {
        return $this->getRandom($this->successPool[$this->language] ?? $this->successPool['english']);
    }
    public function getFallback(): string
    {
        return $this->getRandom($this->fallbackPool[$this->language] ?? $this->fallbackPool['english']);
    }
    public function getBudget(): string
    {
        return $this->getRandom($this->budgetPool[$this->language] ?? $this->budgetPool['english']);
    }
    public function getStockCheck(): string
    {
        return $this->getRandom($this->stockCheckPool[$this->language] ?? $this->stockCheckPool['english']);
    }
    public function getComparing(): string
    {
        return $this->getRandom($this->comparingPool[$this->language] ?? $this->comparingPool['english']);
    }

    /**
     * Translate a price filter label to the target language.
     */
    public function translatePriceLabel(string $label): string
    {
        if ($this->language === 'french') {
            if (preg_match('/between (\d+) and (\d+) DH/i', $label, $m)) {
                return "entre {$m[1]} et {$m[2]} DH";
            }
            if (preg_match('/under (\d+) DH/i', $label, $m)) {
                return "Ã  moins de {$m[1]} DH";
            }
            if (preg_match('/over (\d+) DH/i', $label, $m)) {
                return "Ã  plus de {$m[1]} DH";
            }
            if ($label === 'in that price range') {
                return "dans cette tranche de prix";
            }
        } elseif ($this->language === 'arabic') {
            if (preg_match('/between (\d+) and (\d+) DH/i', $label, $m)) {
                return "بين {$m[1]} و {$m[2]} DH";
            }
            if (preg_match('/under (\d+) DH/i', $label, $m)) {
                return "أقل من {$m[1]} DH";
            }
            if (preg_match('/over (\d+) DH/i', $label, $m)) {
                return "أكثر من {$m[1]} DH";
            }
            if ($label === 'in that price range') {
                return "ضمن هذه الميزانية";
            }
        }
        return $label;
    }

    private function translateCategoryName(string $category): string
    {
        $key = strtolower(trim($category));

        $labels = [
            'french' => [
                'cpu' => 'Processeur',
                'gpu' => 'Carte graphique',
                'motherboard' => 'Carte mère',
                'ram' => 'Mémoire RAM',
                'storage' => 'Stockage',
                'psu' => 'Alimentation',
                'case' => 'Boîtier',
                'cooler' => 'Refroidissement',
            ],
            'arabic' => [
                'cpu' => 'المعالج',
                'gpu' => 'البطاقة الرسومية',
                'motherboard' => 'اللوحة الأم',
                'ram' => 'الذاكرة',
                'storage' => 'التخزين',
                'psu' => 'مزود الطاقة',
                'case' => 'الصندوق',
                'cooler' => 'التبريد',
            ],
        ];

        return $labels[$this->language][$key] ?? strtoupper($category);
    }

    /**
     * Format search query records into a highly rich natural response.
     */
    public function formatNaturalProducts(array $items, string $intro, bool $isComparing, bool $isBuildResponse = false): array
    {
        if ($this->language === 'french') {
            $adjectives = ['impressionnant', 'fiable', 'robuste', 'populaire', 'capable', 'apprecie', 'puissant'];
        } elseif ($this->language === 'arabic') {
            $adjectives = ['ممتاز', 'موثوق', 'قوي', 'شائع', 'مناسب', 'مطلوب', 'جاهز'];
        } else {
            $adjectives = ['impressive', 'reliable', 'solid', 'popular', 'capable', 'well-reviewed', 'powerful'];
        }

        $text = "$intro\n\n";
        $products = [];
        $totalBuildCost = 0;

        if ($isComparing && count($items) > 1) {
            if ($this->language === 'french') {
                $text .= "| Produit | Prix | Note | Stock |\n";
                $text .= "|---------|------|------|-------|\n";
            } elseif ($this->language === 'arabic') {
                $text .= "| المنتج | السعر | التقييم | المخزون |\n";
                $text .= "|--------|-------|---------|---------|\n";
            } else {
                $text .= "| Product | Price | Rating | Stock |\n";
                $text .= "|---------|-------|--------|-------|\n";
            }
        }

        foreach ($items as $item) {
            $brand = $item['brand'];
            $name = $item['name'];

            // De-duplicate: if the name already starts with the brand, strip it
            if ($brand !== '' && stripos($name, $brand) === 0) {
                $name = ltrim(substr($name, strlen($brand)));
            }
            $adj = $this->getRandom($adjectives);

            if ($this->language === 'french') {
                $article = in_array($adj, ['impressionnant'], true) ? 'un' : 'un';
            } else {
                $article = in_array($adj, ['impressive'], true) ? 'an' : 'a';
            }

            // Price info
            if (!empty($item['old_price']) && $item['old_price'] > $item['price']) {
                $save = number_format($item['old_price'] - $item['price'], 0);
                $pct = round(($item['old_price'] - $item['price']) / $item['old_price'] * 100);

                if ($this->language === 'french') {
                    $priceInfo = "maintenant **{$item['price']} DH** (au lieu de {$item['old_price']} DH â€” economisez {$save} DH / {$pct}% de reduction)";
                } elseif ($this->language === 'arabic') {
                    $priceInfo = "الآن بسعر **{$item['price']} DH** بدلا من {$item['old_price']} DH، بتوفير {$save} DH / خصم {$pct}%";
                } else {
                    $priceInfo = "now **{$item['price']} DH** (down from {$item['old_price']} DH â€” save {$save} DH / {$pct}% off)";
                }
            } else {
                if ($this->language === 'french') {
                    $priceInfo = "au prix de **{$item['price']} DH**";
                } elseif ($this->language === 'arabic') {
                    $priceInfo = "بسعر **{$item['price']} DH**";
                } else {
                    $priceInfo = "priced at **{$item['price']} DH**";
                }
            }

            // Stock info
            if ($this->language === 'french') {
                $stockInfo = $item['in_stock'] ? "âœ… en stock" : "âš ï¸ actuellement en rupture de stock";
            } elseif ($this->language === 'arabic') {
                $stockInfo = $item['in_stock'] ? "متوفر في المخزون" : "غير متوفر حاليا";
            } else {
                $stockInfo = $item['in_stock'] ? "âœ… in stock" : "âš ï¸ currently out of stock";
            }

            // Rating info
            if (!empty($item['rating']) && $item['rating'] > 0) {
                $stars = str_repeat('â­', min(5, (int) round((float) $item['rating'])));
                if ($this->language === 'french') {
                    $ratingInfo = "note {$item['rating']}/5 {$stars}";
                } elseif ($this->language === 'arabic') {
                    $ratingInfo = "تقييمه {$item['rating']}/5 {$stars}";
                } else {
                    $ratingInfo = "rated {$item['rating']}/5 {$stars}";
                }
            } else {
                if ($this->language === 'french') {
                    $ratingInfo = "tres recommande par notre communaute";
                } elseif ($this->language === 'arabic') {
                    $ratingInfo = "موصى به بشدة من مجتمعنا";
                } else {
                    $ratingInfo = "highly recommended by our community";
                }
            }

            // Badge
            if (!empty($item['badge'])) {
                if ($this->language === 'french') {
                    $badge = " â€” marque comme **" . strtolower($item['badge']) . "**";
                } elseif ($this->language === 'arabic') {
                    $badge = "، ومصنف كـ **" . strtolower($item['badge']) . "**";
                } else {
                    $badge = " â€” marked as **" . strtolower($item['badge']) . "**";
                }
            } else {
                $badge = "";
            }

            if ($isComparing && count($items) > 1) {
                $stockIcon = $item['in_stock'] ? "âœ…" : "âš ï¸";
                $text .= "| **{$brand} {$name}** | {$item['price']} DH | {$item['rating']} â­ | {$stockIcon} |\n";
            } elseif ($isBuildResponse) {
                $categoryLabel = $this->translateCategoryName((string) $item['category']);
                if ($this->language === 'arabic') {
                    $text .= "• **{$categoryLabel}**: {$brand} {$name} - {$item['price']} DH\n";
                } elseif ($this->language === 'french') {
                    $text .= "• **{$categoryLabel}** : {$brand} {$name} - {$item['price']} DH\n";
                } else {
                    $text .= "• **{$categoryLabel}**: {$brand} {$name} - {$item['price']} DH\n";
                }
            } else {
                if ($this->language === 'french') {
                    $structures = [
                        "**{$brand} {$name}** â€” {$stockInfo}, {$ratingInfo}, {$priceInfo}{$badge}.",
                        "Le **{$brand} {$name}** est un choix {$adj} : {$priceInfo}, {$ratingInfo}. {$stockInfo}{$badge}.",
                        "Vous aimerez peut-etre le **{$brand} {$name}**. Il est a {$priceInfo} et {$ratingInfo}. {$stockInfo}{$badge}.",
                        "Considerez le **{$brand} {$name}** : {$stockInfo}, {$ratingInfo} et {$priceInfo}{$badge}.",
                    ];
                } elseif ($this->language === 'arabic') {
                    $structures = [
                        "**{$brand} {$name}** â€” {$stockInfo}, {$ratingInfo}, {$priceInfo}{$badge}.",
                        "**{$brand} {$name}** خيار {$adj}: {$priceInfo}، {$ratingInfo}. {$stockInfo}{$badge}.",
                        "قد يناسبك **{$brand} {$name}**. {$priceInfo} و{$ratingInfo}. {$stockInfo}{$badge}.",
                        "ضع **{$brand} {$name}** في الحسبان: {$stockInfo}، {$ratingInfo}، و{$priceInfo}{$badge}.",
                    ];
                } else {
                    $structures = [
                        "**{$brand} {$name}** â€” {$stockInfo}, {$ratingInfo}, {$priceInfo}{$badge}.",
                        "The **{$brand} {$name}** is {$article} {$adj} choice: {$priceInfo}, {$ratingInfo}. {$stockInfo}{$badge}.",
                        "You might like the **{$brand} {$name}**. It's {$priceInfo} and {$ratingInfo}. {$stockInfo}{$badge}.",
                        "Consider the **{$brand} {$name}**: {$stockInfo}, {$ratingInfo} and {$priceInfo}{$badge}.",
                    ];
                }
                $text .= "ðŸ”¹ " . $this->getRandom($structures) . "\n\n";
            }

            $totalBuildCost += $item['price'];

            $products[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'image' => $item['image'],
                'price' => $item['price'],
                'in_stock' => $item['in_stock'],
                'category' => $item['category']
            ];
        }

        if ($isBuildResponse) {
            if ($this->language === 'french') {
                $text .= "\n**Cout total du pack : " . number_format($totalBuildCost, 2) . " DH**\n";
            } elseif ($this->language === 'arabic') {
                $text .= "\n**إجمالي تكلفة التجميعة: " . number_format($totalBuildCost, 2) . " DH**\n";
            } else {
                $text .= "\n**Total Combo Cost: " . number_format($totalBuildCost, 2) . " DH**\n";
            }
        }

        if (!$isComparing && !$isBuildResponse) {
            if ($this->language === 'french') {
                $followUps = [
                    "Quelque chose vous interesse, ou dois-je affiner la recherche ?",
                    "Voulez-vous que je filtre par fourchette de prix ou par marque specifique ?",
                    "Souhaitez-vous plus de details sur l'un de ces produits ?",
                    "Faites-moi savoir si vous souhaitez comparer deux de ces articles ou verifier leur disponibilite !"
                ];
            } elseif ($this->language === 'arabic') {
                $followUps = [
                    "هل أعجبك شيء منها، أم تريد أن أضيق البحث أكثر؟",
                    "هل تريد التصفية حسب السعر أو علامة تجارية محددة؟",
                    "هل تريد تفاصيل أكثر عن أحد هذه المنتجات؟",
                    "أخبرني إذا أردت مقارنة منتجين أو التحقق من التوفر."
                ];
            } else {
                $followUps = [
                    "Anything catch your eye, or shall I narrow it down further?",
                    "Want me to filter by price range or a specific brand?",
                    "Would you like more details on any of these?",
                    "Let me know if you'd like to compare two of these or check availability!",
                    "Need help deciding? I can highlight the best value pick for you.",
                    "Any of these fit what you had in mind?"
                ];
            }
            $text .= "\n" . $this->getRandom($followUps);
        }

        // Estimate realistic typing delay
        $charCount = strlen($text);
        $delay = min(2200, max(600, (int) ($charCount * 18)));

        return [
            'response' => $text,
            'products' => $products,
            'delay_ms' => $delay,
            'is_build' => $isBuildResponse
        ];
    }
}
