<?php
declare(strict_types=1);

namespace MarocPC\Chatbot;

class ResponseGenerator
{
    private string $language = 'english';

    public function setLanguage(string $lang): void
    {
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
            "Welcome to Maroc PC! Laptops, GPUs, CPUs, RAM — what are we hunting for today?",
            "Hey! Ready to build something great or find an awesome laptop? Tell me what you need.",
            "Hello! I'm your hardware guide. What laptop or component are you after?"
        ],
        'french' => [
            "Bonjour ! Quel composant PC ou ordinateur portable puis-je vous aider à trouver aujourd'hui ?",
            "Salut ! Vous cherchez un ordinateur portable premium, du matériel spécifique, ou vous voulez que je vous propose mes meilleurs choix ?",
            "Bienvenue chez Maroc PC ! Ordinateurs portables, GPU, CPU, RAM — que cherchons-nous aujourd'hui ?",
            "Salut ! Prêt à monter une super config ou à trouver un ordinateur portable génial ? Dites-moi ce qu'il vous faut.",
            "Bonjour ! Je suis votre guide matériel. Quel ordinateur portable ou composant recherchez-vous ?"
        ],
        'darija' => [
            "Wa fin! Chnou khassak dial PC portable wla PC part lyouma? N3awnak tlqah.",
            "Salam! Katqleb 3la laptop jdid wla chi piece specifique, wla bghiti nqtrh 3lik l-top picks dialna?",
            "Marhaba bik f Maroc PC! Laptops, GPUs, CPUs, RAM — chnou kanshunting lyouma? 😄",
            "Yo! Bghiti t-monter chi config nadiya wla tlqa laptop wa3r? Goli chnou khassak.",
            "Salam! Ghadi n3wnek bach tl9a ahssan l-pieces PC. Chnou khassak dial laptops wla hardware lyouma?"
        ]
    ];

    private array $farewellPool = [
        'english' => [
            "Take care! Come back when you're ready to upgrade. 👋",
            "Goodbye! Happy building!",
            "See you around! Feel free to ask if anything else comes to mind.",
            "Catch you later! May your FPS always be high. 😄"
        ],
        'french' => [
            "Prenez soin de vous ! Revenez quand vous serez prêt pour une mise à niveau. 👋",
            "Au revoir ! Bon montage !",
            "À bientôt ! N'hésitez pas à me solliciter si vous avez d'autres questions.",
            "À la prochaine ! Que votre FPS soit toujours élevé. 😄"
        ],
        'darija' => [
            "Thalla f rasek! Rje3 3ndna melli tbghi t-upgrader. 👋",
            "M3a salama! Bon montage dial PC!",
            "Thalla! Goliya ila khtajitini f chi haja khora.",
            "Thalla m3a rasek! Nchofok mn b3d inchaallah. 😄"
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
            "Je vous en prie ! N'hésitez pas si vous voulez comparer des spécifications ou vérifier le stock d'un autre article.",
            "Avec plaisir ! Avez-vous d'autres composants ou ordinateurs portables sur votre liste ?",
            "Content que cela ait été utile ! Faites-moi signe si vous avez besoin d'autre chose.",
            "Tout le plaisir est pour moi ! Besoin d'aide pour une autre pièce ou un autre laptop ?"
        ],
        'darija' => [
            "Hanya, hada wajeb! Chi haja khora nqleb lik 3liha?",
            "Marhba bik! Goliya ila bghiti t-comparer l-specs wla tchouf l-stock dial chi haja khora.",
            "Dima fl-khedma! Khassak chi piece khora dial PC?",
            "Rbi ykhalik! Khassak chi haja khora n3awnak fiha?",
            "Hanya, dima fl-khedma! Chi laptop akhor wla piece khora?"
        ]
    ];

    private array $helpPool = [
        'english' => [
            "Sure! You can ask me things like:\n• \"Show me a premium gaming laptop\"\n• \"Cheapest SSD you have\"\n• \"Ryzen 5 CPUs on sale\"\n• \"Best rated RAM\"\n• \"ASUS ROG Strix laptops\"\n\nWhat would you like to search for?",
            "I can help you find premium laptops, CPUs, GPUs, RAM, storage, and more. Just describe what you're after — brand, type, budget — and I'll pull up the best options from our inventory!",
            "Try asking something like \"gaming laptop under 15000\", \"RTX 4070\", \"budget RAM\", or even \"MacBook Pro\". I'll take it from there!"
        ],
        'french' => [
            "Bien sûr ! Vous pouvez me demander des choses comme :\n• « Montre-moi un ordinateur portable de jeu premium »\n• « Le SSD le moins cher que vous avez »\n• « CPU Ryzen 5 en promo »\n• « RAM la mieux notée »\n• « Laptops ASUS ROG Strix »\n\nQue souhaitez-vous rechercher ?",
            "Je peux vous aider à trouver des ordinateurs portables premium, des processeurs, des cartes graphiques, de la RAM, du stockage, etc. Décrivez simplement ce que vous cherchez (marque, type, budget) et je trouverai les meilleures options dans notre stock !",
            "Essayez de demander « PC portable gamer à moins de 15000 », « RTX 4070 », « RAM pas cher » ou même « MacBook Pro ». Je m'occupe du reste !"
        ],
        'darija' => [
            "Wakha! Tqder tsowlni 3la had l-hajat:\n• \"Wrrini chi laptop gaming wa3r\"\n• \"SSD rkhis 3ndkom\"\n• \"Ryzen 5 CPU fih promotion\"\n• \"RAM fiha rating mezyan\"\n• \"ASUS ROG Strix laptops\"\n\n3la chnou katqleb?",
            "Nqder n3awnak tlqa laptops wa3rin, CPUs, GPUs, RAM, stockage, w piece khrin. Goli ghir chnou bghiti (brand, type, budget) w ana n-jib lik l-best options mn inventory dialna!",
            "Jrreb sowlni bhal \"laptop gaming ql mn 15000\", \"RTX 4070\", \"RAM rkhissa\", wla ghir \"MacBook Pro\". Khlli l-baqi 3liya!"
        ]
    ];

    private array $rmaPool = [
        'english' => [
            "Need to return an item or request technical support? Maroc PC has your back! We offer a **14-day return window** for complete packages and a **48h diagnostic promise** for warranty or repair tickets.\n\nYou can easily open an after-sales ticket and track your request directly on our dedicated page: [**Returns, Refunds & After-Sales Desk**](returns-refunds.php).\n\nIf you have a damaged package or missing items, we triage these priority cases within 24 hours!"
        ],
        'french' => [
            "Besoin de retourner un article ou de demander une assistance technique ? Maroc PC s'occupe de tout ! Nous offrons une **période de retour de 14 jours** pour les colis complets et une **garantie de diagnostic sous 48h** pour les cas de garantie ou de réparation.\n\nVous pouvez facilement ouvrir un ticket SAV et suivre votre demande sur notre page dédiée : [**Service Après-Vente & Retours**](returns-refunds.php).\n\nEn cas de colis endommagé ou d'articles manquants, nous traitons ces dossiers en priorité absolue sous 24h !"
        ],
        'darija' => [
            "Bghiti t-reje3 chi piece wla 3ndek chi mouchkil technique? Maroc PC dima m3ak! 3ndna **14 lyoum dial l-khyar** bach trje3 l-colis dialek kamel, w **48h dial diagnostic** f l-garantie wla l-isla7.\n\nTqder t-hell ticket dial service après-vente (SAV) w t-suivih direct f l-page dialna: [**Service SAV & Retours**](returns-refunds.php).\n\nIla wsalek l-colis mherres wla naqsa chi piece, kankhedmo had priority cases f aqal mn 24h!"
        ]
    ];

    private array $laptopFinderPool = [
        'english' => [
            "Looking for the perfect laptop? Instead of raw specs, our outcome-oriented **Laptop Finder** matches your life and usage! Tell me what you need (Gaming, Office, Creativity, or Portability) and what screen quality and battery life you want.\n\nAlternatively, you can open and run our interactive [**Laptop Finder Wizard**](laptop-finder.php) right now to see the best matching choices computed instantly using outcome-based scores!"
        ],
        'french' => [
            "Vous cherchez l'ordinateur portable idéal ? Au lieu de simples caractéristiques techniques, notre **Laptop Finder** basé sur vos besoins réels cible parfaitement votre profil ! Dites-moi l'usage prévu (Jeux, Bureau, Création, ou Portabilité), l'autonomie et la qualité d'écran recherchées.\n\nVous pouvez également lancer notre [**Laptop Finder Interactif**](laptop-finder.php) dès maintenant pour trouver le modèle qui vous convient le mieux en quelques clics !"
        ],
        'darija' => [
            "Katqleb 3la laptop l-kamil lik? Bla mat-dokh f l-specs, l-**Laptop Finder** dialna Outcome-Oriented kikhayr lik 3la hsab l-usage dialek! Goli chnou l-usage (Gaming, Office, Creative wla Portative) w chnou l-autonomie w qualite d-ecran li bghiti.\n\nTqder t-hell l-[**Laptop Finder Wizard**](laptop-finder.php) interactive lyouma bach tchouf ahssan choice computed f l-hine!"
        ]
    ];

    private array $orderStatusHelpPool = [
        'english' => [
            "I'd love to help you track your order! Please type **Track [Order ID]** (for example: `Track #1024` or `Track 1024`) and I will immediately query our real-time database to give you its status!"
        ],
        'french' => [
            "Je serais ravi de vous aider à suivre votre commande ! Veuillez taper **Track [ID de commande]** (par exemple : `Track #1024` ou `Track 1024`) et je vais immédiatement interroger notre base de données pour vous donner le statut en direct !"
        ],
        'darija' => [
            "N3awnak t-suivrer l-order dialek b l-farah! Goliya **Track [Raqm l-order]** (masalan: `Track #1024` wla `Track 1024`) w ghadi nqleb f l-base de données f l-hine bach n-gollik fin wsal!"
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
            "Je n'ai pas tout à fait compris. Essayez quelque chose comme « RTX 3060 », « Ryzen 5 », « pc portable » ou « SSD pas cher ».",
            "Hmm, je suis spécialisé dans la recherche de composants PC et d'ordinateurs portables. Précisez une catégorie (Laptop, GPU, CPU, RAM) ou une marque.",
            "Je ne suis pas sûr de vous avoir suivi. Pourriez-vous reformuler ? Par exemple : « montre-moi une bonne carte graphique » ou « pc portable le moins cher ».",
            "Je suis configuré pour parler matériel et ordinateurs portables ! Demandez-moi des pièces spécifiques, des marques ou des catégories."
        ],
        'darija' => [
            "Mafhemtch mezyan, smehli. Matb9ach t5bi9 wswlni chi haja wad7a b7al: \"RTX 3060\", \"Ryzen 5\", \"pc portable\" wla \"SSD rkhis\".",
            "Hmm, ana mezyan ghir f pieces dial PC w laptops. Sowlni 3la chi category bhal Laptop, GPU, CPU, RAM wla chi brand.",
            "Maktabtch l-fhma mezyan. Tqder t-rephrase-ha? Bhal: \"wrrini chi graphics card mezyana\" wla \"laptop rkhis\".",
            "Ana ready ghir l-pieces PC w laptops! Sowlni 3la piece, brand wla category b-dbt."
        ]
    ];

    private array $successPool = [
        'english' => [
            "Here's what I found in our inventory:",
            "Nice, I pulled up some great options for you:",
            "These caught my eye based on what you said:",
            "Found a few solid matches — take a look:",
            "Here are some top picks from our stock:"
        ],
        'french' => [
            "Voici ce que j'ai trouvé dans notre inventaire :",
            "Super, j'ai déniché d'excellentes options pour vous :",
            "Voici les meilleurs choix selon vos critères :",
            "J'ai trouvé quelques bonnes correspondances — jetez-y un œil :",
            "Voici les meilleurs choix disponibles dans notre stock :"
        ],
        'darija' => [
            "Ha chnou lqit lik f-stock dialna:",
            "Lqit lik chi options mezyanin, chouf hado:",
            "Hado homa l-top picks 3la hssab chnou glti:",
            "Lqina options nadyin — chouf hado:",
            "Ha pieces l-mkhyrin li kaynin f-stock dialna daba:"
        ]
    ];

    private array $fallbackPool = [
        'english' => [
            "I couldn't find an exact match, but here are some popular picks that might work:",
            "That specific item is a bit elusive — here's what's hot right now instead:",
            "Couldn't nail that down exactly, but these highly-rated alternatives might do the trick:"
        ],
        'french' => [
            "Je n'ai pas trouvé de correspondance exacte, mais voici des articles très populaires qui pourraient vous intéresser :",
            "Cet article spécifique est introuvable pour le moment — voici les meilleures ventes actuelles :",
            "Impossible de trouver exactement cela, mais ces alternatives très bien notées feront peut-être l'affaire :"
        ],
        'darija' => [
            "Malqitch hadakchi b-dbt, walakin ha options popular li tqder t-choufha:",
            "Had piece qlila chwiya fl-stock — ha chnou mezyan lyouma f-blassha:",
            "Malqitch l-match exact, walakin had options ratings dialhom wa3rin yqdro ykhdmo lik:"
        ]
    ];

    private array $budgetPool = [
        'english' => [
            "Love a good deal! Here are the best-value options we have right now:",
            "Bargain hunting? Here are the biggest discounts in our store:",
            "I've filtered for the best price drops — check these out:"
        ],
        'french' => [
            "On adore les bonnes affaires ! Voici les options offrant le meilleur rapport qualité-prix en ce moment :",
            "À la recherche d'un bon plan ? Voici les plus grosses réductions de notre boutique :",
            "J'ai filtré les meilleures baisses de prix — regardez ceci :",
        ],
        'darija' => [
            "Katqleb 3la rkhis? Ha l-best value options li 3ndna lyouma:",
            "Bghiti l-promotions? Ha l-akbar discounts f-store dialna lyouma:",
            "Filtrait lik rkhis w l-solde l-kbir — chouf hado:"
        ]
    ];

    private array $stockCheckPool = [
        'english' => [
            "Let me check what we have in stock for you...",
        ],
        'french' => [
            "Laissez-moi vérifier nos disponibilités en stock...",
        ],
        'darija' => [
            "Khllini nchouf l-stock chnou fih...",
        ]
    ];

    private array $comparingPool = [
        'english' => [
            "Here are some options you can compare side by side:",
            "Good call comparing before buying — here's what I found:",
            "Let me line up a few options for you:"
        ],
        'french' => [
            "Voici des options que vous pouvez comparer côte à côte :",
            "Excellente idée de comparer avant d'acheter — voici mes trouvailles :",
            "Laissez-moi aligner quelques options pour vous :"
        ],
        'darija' => [
            "Ha options li tqder t-comparer binat-hom:",
            "Mezyan t-comparer qbel ma t-chri — ha chnou lqit lik:",
            "Khllini n-stt-f lik had options hna n-comparohom:"
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
                return "à moins de {$m[1]} DH";
            }
            if (preg_match('/over (\d+) DH/i', $label, $m)) {
                return "à plus de {$m[1]} DH";
            }
            if ($label === 'in that price range') {
                return "dans cette tranche de prix";
            }
        } elseif ($this->language === 'darija') {
            if (preg_match('/between (\d+) and (\d+) DH/i', $label, $m)) {
                return "bin {$m[1]} o {$m[2]} DH";
            }
            if (preg_match('/under (\d+) DH/i', $label, $m)) {
                return "ql mn {$m[1]} DH";
            }
            if (preg_match('/over (\d+) DH/i', $label, $m)) {
                return "ktar mn {$m[1]} DH";
            }
            if ($label === 'in that price range') {
                return "f had l-budget";
            }
        }
        return $label;
    }

    /**
     * Format search query records into a highly rich natural response.
     */
    public function formatNaturalProducts(array $items, string $intro, bool $isComparing, bool $isBuildResponse = false): array
    {
        if ($this->language === 'french') {
            $adjectives = ['impressionnant', 'fiable', 'robuste', 'populaire', 'capable', 'apprecie', 'puissant'];
        } elseif ($this->language === 'darija') {
            $adjectives = ['wa3r', 'nadi', 'mzn', 'popular', 'mkhyr', 'matloub', 'jahiz'];
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
            } elseif ($this->language === 'darija') {
                $text .= "| Piece | Taman | Rating | Stock |\n";
                $text .= "|-------|-------|--------|-------|\n";
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
                    $priceInfo = "maintenant **{$item['price']} DH** (au lieu de {$item['old_price']} DH — economisez {$save} DH / {$pct}% de reduction)";
                } elseif ($this->language === 'darija') {
                    $priceInfo = "daba ghir b **{$item['price']} DH** (hbat mn {$item['old_price']} DH — rbe7 {$save} DH / {$pct}% discount)";
                } else {
                    $priceInfo = "now **{$item['price']} DH** (down from {$item['old_price']} DH — save {$save} DH / {$pct}% off)";
                }
            } else {
                if ($this->language === 'french') {
                    $priceInfo = "au prix de **{$item['price']} DH**";
                } elseif ($this->language === 'darija') {
                    $priceInfo = "b taman dial **{$item['price']} DH**";
                } else {
                    $priceInfo = "priced at **{$item['price']} DH**";
                }
            }

            // Stock info
            if ($this->language === 'french') {
                $stockInfo = $item['in_stock'] ? "✅ en stock" : "⚠️ actuellement en rupture de stock";
            } elseif ($this->language === 'darija') {
                $stockInfo = $item['in_stock'] ? "✅ kayn f-stock" : "⚠️ out of stock lyouma";
            } else {
                $stockInfo = $item['in_stock'] ? "✅ in stock" : "⚠️ currently out of stock";
            }

            // Rating info
            if (!empty($item['rating']) && $item['rating'] > 0) {
                $stars = str_repeat('⭐', min(5, (int) round((float) $item['rating'])));
                if ($this->language === 'french') {
                    $ratingInfo = "note {$item['rating']}/5 {$stars}";
                } elseif ($this->language === 'darija') {
                    $ratingInfo = "fih {$item['rating']}/5 {$stars}";
                } else {
                    $ratingInfo = "rated {$item['rating']}/5 {$stars}";
                }
            } else {
                if ($this->language === 'french') {
                    $ratingInfo = "tres recommande par notre communaute";
                } elseif ($this->language === 'darija') {
                    $ratingInfo = "rating dyalo wa3r 3nd l-clyian";
                } else {
                    $ratingInfo = "highly recommended by our community";
                }
            }

            // Badge
            if (!empty($item['badge'])) {
                if ($this->language === 'french') {
                    $badge = " — marque comme **" . strtolower($item['badge']) . "**";
                } elseif ($this->language === 'darija') {
                    $badge = " — mbsouma 3liha ka **" . strtolower($item['badge']) . "**";
                } else {
                    $badge = " — marked as **" . strtolower($item['badge']) . "**";
                }
            } else {
                $badge = "";
            }

            if ($isComparing && count($items) > 1) {
                $stockIcon = $item['in_stock'] ? "✅" : "⚠️";
                $text .= "| **{$brand} {$name}** | {$item['price']} DH | {$item['rating']} ⭐ | {$stockIcon} |\n";
            } elseif ($isBuildResponse) {
                $text .= "🔹 **{$item['category']}**: {$brand} {$name} — {$item['price']} DH\n";
            } else {
                if ($this->language === 'french') {
                    $structures = [
                        "**{$brand} {$name}** — {$stockInfo}, {$ratingInfo}, {$priceInfo}{$badge}.",
                        "Le **{$brand} {$name}** est un choix {$adj} : {$priceInfo}, {$ratingInfo}. {$stockInfo}{$badge}.",
                        "Vous aimerez peut-etre le **{$brand} {$name}**. Il est a {$priceInfo} et {$ratingInfo}. {$stockInfo}{$badge}.",
                        "Considerez le **{$brand} {$name}** : {$stockInfo}, {$ratingInfo} et {$priceInfo}{$badge}.",
                    ];
                } elseif ($this->language === 'darija') {
                    $structures = [
                        "**{$brand} {$name}** — {$stockInfo}, {$ratingInfo}, {$priceInfo}{$badge}.",
                        "Had **{$brand} {$name}** option {$adj}: {$priceInfo}, {$ratingInfo}. {$stockInfo}{$badge}.",
                        "Tqder t-khter **{$brand} {$name}**. Rah {$priceInfo} w {$ratingInfo}. {$stockInfo}{$badge}.",
                        "Chouf had **{$brand} {$name}**: {$stockInfo}, {$ratingInfo} w {$priceInfo}{$badge}.",
                    ];
                } else {
                    $structures = [
                        "**{$brand} {$name}** — {$stockInfo}, {$ratingInfo}, {$priceInfo}{$badge}.",
                        "The **{$brand} {$name}** is {$article} {$adj} choice: {$priceInfo}, {$ratingInfo}. {$stockInfo}{$badge}.",
                        "You might like the **{$brand} {$name}**. It's {$priceInfo} and {$ratingInfo}. {$stockInfo}{$badge}.",
                        "Consider the **{$brand} {$name}**: {$stockInfo}, {$ratingInfo} and {$priceInfo}{$badge}.",
                    ];
                }
                $text .= "🔹 " . $this->getRandom($structures) . "\n\n";
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
            } elseif ($this->language === 'darija') {
                $text .= "\n**Total Combo l-kamla: " . number_format($totalBuildCost, 2) . " DH**\n";
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
            } elseif ($this->language === 'darija') {
                $followUps = [
                    "Kayn chi haja 3jbatk, wla n-ziyed n-sfi l-qelba?",
                    "Bghitini n-filtri b taman wla b chi brand?",
                    "Bghiti n-golk details ktr 3la chi whda f hado?",
                    "Goliya ila bghiti t-comparer binat-hom wla nchofo l-stock!"
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
