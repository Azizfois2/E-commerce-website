<?php
declare(strict_types=1);

namespace MarocPC\Chatbot;

class ChatbotController
{
    private RequestParser $parser;
    private IntentClassifier $classifier;
    private ProductRepository $repo;
    private ResponseGenerator $generator;
    private string $productSelect = 'id, name, brand, category, price, old_price, badge, rating, reviews, image, in_stock, stock_quantity, specs';

    public function __construct(
        RequestParser $parser,
        IntentClassifier $classifier,
        ProductRepository $repo,
        ResponseGenerator $generator
    ) {
        $this->parser = $parser;
        $this->classifier = $classifier;
        $this->repo = $repo;
        $this->generator = $generator;
    }

    /**
     * Orchestrate incoming chat query and output appropriate search recommendations.
     */
    public function handleRequest(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Retrieve raw JSON request input
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $rawQuery = trim((string) ($input['message'] ?? ''));

        // Normalize query and extract tokens
        $normalized = $this->parser->normalize($rawQuery);
        $tokens = $this->parser->tokenize($normalized);

        // Detect user language and configure response generator
        $language = trim((string) ($input['language'] ?? ''));
        if ($language === 'darija') {
            $language = 'arabic';
        }
        if ($language !== 'english' && $language !== 'french' && $language !== 'arabic') {
            $language = $this->parser->detectLanguage($rawQuery);
        }
        $this->generator->setLanguage($language);

        if ($normalized === '') {
            $this->reply($this->generator->getConfused(), 400);
        }

        if (isset($input['builder_context']) && is_array($input['builder_context']) && $this->shouldAnswerFromBuilderContext($rawQuery, $input['builder_context'])) {
            $this->reply([
                'response' => $this->formatBuilderContextResponse($rawQuery, $input['builder_context']),
                'delay_ms' => 350,
                'source' => 'builder_context',
            ]);
        }

        // Classify standard query intent
        $intent = $this->classifier->classify($normalized, $tokens, $rawQuery);

        // 0. Free products joke
        if ($intent === IntentClassifier::INTENT_FREE_PRANK) {
            if ($language === 'french') {
                $prankResponse = 'Vous obtiendrez tous les produits gratuitement en cliquant sur ce lien : [recuperer vos produits gratuits](https://youtu.be/GBIIQ0kP15E?si=Ra9y-5Mwfi5X-XO0)';
            } elseif ($language === 'arabic') {
                $prankResponse = 'يمكنك الحصول على كل المنتجات مجانا إذا ضغطت هنا: [استلام المنتجات المجانية](https://www.youtube.com/watch?v=pSI_mZkDNvQ)';
            } else {
                $prankResponse = 'You get all products for free if you click this link: [claim your free products](https://youtu.be/GBIIQ0kP15E?si=Ra9y-5Mwfi5X-XO0)';
            }
            $this->reply([
                'response' => $prankResponse,
                'delay_ms' => 500,
            ]);
        }

        // 1. Farewell
        if ($intent === IntentClassifier::INTENT_FAREWELL) {
            $this->reply($this->generator->getFarewell(), 300);
        }

        // 1b. Be right back
        if ($intent === IntentClassifier::INTENT_BRB) {
            if ($language === 'french') {
                $brbPool = ["Pas de precipitation — je suis la ! 😊", "Prenez votre temps, je ne vais nulle part !", "Ca marche, a tout de suite !"];
            } elseif ($language === 'arabic') {
                $brbPool = ["خذ وقتك، أنا هنا.", "حسنا، سأكون في انتظارك.", "لا تستعجل، عد متى شئت."];
            } else {
                $brbPool = ["No rush — I'll be here! 😊", "Take your time, I'm not going anywhere!", "Sure thing, see you in a bit!"];
            }
            $this->reply($this->generator->getRandom($brbPool), 300);
        }

        // 2. Greeting
        if ($intent === IntentClassifier::INTENT_GREETING) {
            $this->reply($this->generator->getGreeting(), 350);
        }

        // 2b. Casual how are you
        if ($intent === IntentClassifier::INTENT_HOW_ARE_YOU) {
            if ($language === 'french') {
                $howPool = [
                    "Je vais tres bien, merci de demander ! 😄 Alors, quel composant PC puis-je vous aider a trouver ?",
                    "Tous les systemes sont operationnels ! Pret a vous trouver le composant parfait. Que cherchez-vous ?",
                    "Fonctionnement a 100% ! Que puis-je vous aider a traquer aujourd'hui ?"
                ];
            } elseif ($language === 'arabic') {
                $howPool = [
                    "أنا بخير، شكرا لسؤالك. ما القطعة التي يمكنني مساعدتك في العثور عليها اليوم؟",
                    "كل الأنظمة تعمل بشكل ممتاز. أخبرني عما تبحث عنه وسأساعدك.",
                    "جاهز لمساعدتك في اختيار العتاد المناسب. ما الذي تحتاجه؟"
                ];
            } else {
                $howPool = [
                    "I'm doing great, thanks for asking! 😄 Now, what PC part can I help you find?",
                    "All systems go over here! Ready to help you find the perfect component. What are you looking for?",
                    "Running at 100%! What can I help you track down today?"
                ];
            }
            $this->reply($this->generator->getRandom($howPool), 350);
        }

        // 2c. Funny / laughter reaction
        if ($intent === IntentClassifier::INTENT_LAUGHTER) {
            if ($language === 'french') {
                $laughPool = [
                    "Haha, ravi d'avoir pu egayer votre journee ! 😄 Maintenant, pret a trouver du super materiel ?",
                    "😄 Bref — revenons aux choses serieuses ! Quel composant puis-je chercher pour vous ?",
                    "lol, c'est sympa de discuter avec vous ! Trouvons de super composants maintenant — que cherchez-vous ?"
                ];
            } elseif ($language === 'arabic') {
                $laughPool = [
                    "سعيد أنني رسمت ابتسامة. والآن، ما القطعة التي نبحث عنها؟",
                    "جميل. لنعد إلى العتاد، كيف يمكنني مساعدتك؟",
                    "المحادثة معك ممتعة. لنجد لك أفضل قطع الكمبيوتر."
                ];
            } else {
                $laughPool = [
                    "Haha, glad I could brighten your day! 😄 Now, ready to find some amazing hardware?",
                    "😄 Anyway — back to business! What component can I help you find?",
                    "lol, you're fun to chat with! Now let's find you some great PC parts — what are you looking for?"
                ];
            }
            $this->reply($this->generator->getRandom($laughPool), 350);
        }

        // 3. Help capabilities
        if ($intent === IntentClassifier::INTENT_HELP) {
            $this->reply($this->generator->getHelp(), 400);
        }

        // 4. Gratitude
        if ($intent === IntentClassifier::INTENT_GRATITUDE) {
            $this->reply($this->generator->getGratitude(), 350);
        }

        // 4b. RMA / Return
        if ($intent === IntentClassifier::INTENT_RMA) {
            $this->reply($this->generator->getRma(), 400);
        }

        // 4c. Laptop Finder
        if ($intent === IntentClassifier::INTENT_LAPTOP_FINDER) {
            $this->reply($this->generator->getLaptopFinder(), 400);
        }

        // 4d. Order Status Inquiry (Dynamic Database Search!)
        if ($intent === IntentClassifier::INTENT_ORDER_STATUS) {
            // Check if query has a numeric Order ID
            $orderId = null;
            if (preg_match('/#?\b(\d{4,})\b/', $rawQuery, $match)) {
                $orderId = (int) $match[1];
            }

            if ($orderId !== null) {
                try {
                    $pdo = db();
                    // Let's also check if user_id is logged in or if they provide any order ID
                    $stmt = $pdo->prepare("
                        SELECT o.id, o.status, o.total, o.created_at, c.nom as client_name
                        FROM orders o
                        LEFT JOIN client c ON o.client_id = c.id_client
                        WHERE o.id = ?
                    ");
                    $stmt->execute([$orderId]);
                    $order = $stmt->fetch();

                    if ($order) {
                        $orderStatus = strtoupper($order['status']);
                        $totalCost = number_format((float)$order['total'], 2);
                        $datePlaced = date('F j, Y', strtotime($order['created_at']));
                        $clientName = htmlspecialchars($order['client_name'] ?? 'Client');

                        if ($language === 'french') {
                            $replyMsg = "Bonjour ! J'ai trouvé votre commande **#{$order['id']}** enregistrée pour **{$clientName}**.\n\n" .
                                        "📌 **Statut actuel** : `{$orderStatus}`\n" .
                                        "📅 **Date de commande** : {$datePlaced}\n" .
                                        "💰 **Montant total** : **{$totalCost} DH**\n\n" .
                                        "Vous pouvez suivre tous les détails de vos commandes directement dans votre [**Espace Client (Commandes)**](account.php?tab=orders).";
                        } elseif ($language === 'arabic') {
                            $replyMsg = "مرحبا. وجدت طلبك **#{$order['id']}** باسم **{$clientName}**.\n\n" .
                                        "**الحالة الحالية**: `{$orderStatus}`\n" .
                                        "**تاريخ الطلب**: {$datePlaced}\n" .
                                        "**المبلغ الإجمالي**: **{$totalCost} DH**\n\n" .
                                        "يمكنك تتبع التفاصيل والفواتير من [**لوحة طلبات الحساب**](account.php?tab=orders).";
                        } else {
                            $replyMsg = "Hello! I've located your order **#{$order['id']}** for **{$clientName}**.\n\n" .
                                        "📌 **Current Status** : `{$orderStatus}`\n" .
                                        "📅 **Order Date** : {$datePlaced}\n" .
                                        "💰 **Total Value** : **{$totalCost} DH**\n\n" .
                                        "You can track the shipping details and invoices directly in your [**Account Orders Dashboard**](account.php?tab=orders).";
                        }
                    } else {
                        if ($language === 'french') {
                            $replyMsg = "Désolé, je n'ai trouvé aucune commande avec le numéro **#{$orderId}**. Veuillez vérifier le numéro de commande et réessayer !";
                        } elseif ($language === 'arabic') {
                            $replyMsg = "عذرا، لم أجد أي طلب بالرقم **#{$orderId}**. يرجى التأكد من رقم الطلب ثم المحاولة مرة أخرى.";
                        } else {
                            $replyMsg = "Sorry, I couldn't find any order matching ID **#{$orderId}**. Please double-check your order number and try again!";
                        }
                    }
                } catch (\Throwable $e) {
                    $replyMsg = "Error: " . $e->getMessage();
                }

                $this->reply($replyMsg, 400);
            } else {
                // No Order ID specified! Check if user is logged in and fetch their latest order instead!
                $clientId = isset($_SESSION['client_id']) ? (int) $_SESSION['client_id'] : null;
                if ($clientId !== null) {
                    try {
                        $pdo = db();
                        $stmt = $pdo->prepare("
                            SELECT id, status, total, created_at
                            FROM orders
                            WHERE client_id = ?
                            ORDER BY id DESC
                            LIMIT 1
                        ");
                        $stmt->execute([$clientId]);
                        $latestOrder = $stmt->fetch();

                        if ($latestOrder) {
                            $orderStatus = strtoupper($latestOrder['status']);
                            $totalCost = number_format((float)$latestOrder['total'], 2);
                            $datePlaced = date('F j, Y', strtotime($latestOrder['created_at']));

                            if ($language === 'french') {
                                $replyMsg = "Puisque vous êtes connecté, j'ai trouvé votre dernière commande **#{$latestOrder['id']}** :\n\n" .
                                            "📌 **Statut actuel** : `{$orderStatus}`\n" .
                                            "📅 **Date** : {$datePlaced}\n" .
                                            "💰 **Total** : **{$totalCost} DH**\n\n" .
                                            "Si vous souhaitez suivre une autre commande spécifique, veuillez taper : **Track [Numéro de commande]** (ex : `Track #1024`).";
                            } elseif ($language === 'arabic') {
                                $replyMsg = "بما أنك مسجل الدخول، وجدت آخر طلب لك **#{$latestOrder['id']}**:\n\n" .
                                            "**الحالة الحالية**: `{$orderStatus}`\n" .
                                            "**التاريخ**: {$datePlaced}\n" .
                                            "**المجموع**: **{$totalCost} DH**\n\n" .
                                            "إذا أردت تتبع طلب آخر، اكتب: **Track [رقم الطلب]** مثل `Track #1024`.";
                            } else {
                                $replyMsg = "Since you are logged in, I retrieved your latest order **#{$latestOrder['id']}**:\n\n" .
                                            "📌 **Current Status** : `{$orderStatus}`\n" .
                                            "📅 **Order Date** : {$datePlaced}\n" .
                                            "💰 **Total Value** : **{$totalCost} DH**\n\n" .
                                            "If you want to track a different order, please type: **Track [Order ID]** (e.g. `Track #1024`).";
                            }
                            $this->reply($replyMsg, 400);
                        }
                    } catch (\Throwable $e) {}
                }

                // If still not replied (e.g. guest or no orders), ask for Order ID
                $this->reply($this->generator->getOrderStatusHelp(), 400);
            }
        }

        // Evaluate sub-intent states
        $isComparing = ($intent === IntentClassifier::INTENT_COMPARE);
        $isBudget = ($intent === IntentClassifier::INTENT_BUDGET);
        $isStockCheck = ($intent === IntentClassifier::INTENT_STOCK_CHECK);
        $isBuild = ($intent === IntentClassifier::INTENT_BUILD);

        // Extract budget limits and parsed range limits
        $budgetLimit = 0;
        if (preg_match('/(?:under|max|budget)[^\d]*(\d+)/i', $rawQuery, $m) || preg_match('/(\d+)\s*(?:mad|dh|dirham|dirhams|dhs)/i', $rawQuery, $m)) {
            $budgetLimit = (int) $m[1];
        }

        $priceFilter = $this->parser->parsePriceFilter($rawQuery);
        if (($priceFilter['max'] ?? 0) > 0) {
            $budgetLimit = (int) $priceFilter['max'];
        }

        if (($priceFilter['min'] ?? 0) > 0 || ($priceFilter['max'] ?? 0) > 0) {
            $isBudget = true;
            if (!$this->classifier->containsAny($normalized, $tokens, ['compare', 'vs', 'versus', 'difference', 'better', 'which'])) {
                $isComparing = false;
            }
        }

        // Process Stock Checks first
        if ($isStockCheck) {
            $stockAnswer = $this->repo->directStockAnswer($normalized, $tokens, $this->productSelect, $language);
            if ($stockAnswer !== null) {
                $this->reply($stockAnswer);
            }
        }

        // Process balanced PC builds
        $results = [];
        $isBuildResponse = false;
        if ($isBuild) {
            $results = $this->repo->buildCombo($budgetLimit, $this->productSelect);
            if (count($results) >= 2) {
                $isBuildResponse = true;
            } else {
                $results = []; // Fallback to regular catalog query pipeline
            }
        }

        // Search Pipeline: 1. Natural Language Fulltext
        if ($results === []) {
            $results = $this->repo->fulltextSearch($normalized, $budgetLimit, $priceFilter, $this->productSelect);
        }

        // Search Pipeline: 2. Specific Keyword Category Mapping
        if ($results === []) {
            $catSearchResult = $this->repo->categorySearch($normalized, $tokens, $isBudget, $budgetLimit, $priceFilter, $this->productSelect);
            if (isset($catSearchResult['type']) && $catSearchResult['type'] === 'closest_fallback') {
                $priceLabel = $priceFilter['label'] ?: 'in that price range';
                $translatedPriceLabel = $this->generator->translatePriceLabel($priceLabel);

                if ($language === 'french') {
                    $introMsg = "Je n'ai trouve aucun produit {$catSearchResult['category']} {$translatedPriceLabel}, mais voici les options les plus proches dans cette categorie :";
                } elseif ($language === 'arabic') {
                    $introMsg = "لم أجد منتجات من فئة {$catSearchResult['category']} {$translatedPriceLabel}، لكن هذه أقرب الخيارات في نفس الفئة:";
                } else {
                    $introMsg = "I couldn't find any {$catSearchResult['category']} products {$translatedPriceLabel}, but these are the closest options in that category:";
                }

                $this->reply($this->generator->formatNaturalProducts(
                    $catSearchResult['data'],
                    $introMsg,
                    false
                ));
            } elseif (isset($catSearchResult['type']) && $catSearchResult['type'] === 'success') {
                $results = $catSearchResult['data'];
            }
        }

        // Search Pipeline: 3. Discount Budget Search Fallback
        if ($results === [] && $isBudget) {
            $results = $this->repo->discountSearch($priceFilter, $this->productSelect);
            if ($results !== []) {
                $this->reply($this->generator->formatNaturalProducts($results, $this->generator->getBudget(), $isComparing));
            }
        }

        // Search Pipeline: 4. Wildcard loose LIKE matching
        if ($results === []) {
            $results = $this->repo->looseLikeSearch($tokens, $priceFilter, $this->productSelect);
        }

        // Search Pipeline: 5. Absolute fallback sorting by highest rating
        if ($results === []) {
            $results = $this->repo->bestRatedSearch($priceFilter, $this->productSelect);
            $this->reply($this->generator->formatNaturalProducts($results, $this->generator->getFallback(), false));
        }

        // Output rich formatted results
        if ($isBuildResponse) {
            if ($language === 'french') {
                $intro = 'Voici un pack de composants equilibre selon votre demande :';
            } elseif ($language === 'arabic') {
                $intro = 'هذه تجميعة متوازنة حسب طلبك وميزانيتك:';
            } else {
                $intro = 'Here is a balanced build combo based on your request:';
            }
        } elseif ($isBudget) {
            $intro = $this->generator->getBudget();
        } elseif ($isComparing) {
            $intro = $this->generator->getComparing();
        } elseif ($isStockCheck) {
            $intro = $this->generator->getStockCheck();
        } else {
            $intro = $this->generator->getSuccess();
        }

        $this->reply($this->generator->formatNaturalProducts($results, $intro, $isComparing, $isBuildResponse));
    }

    /**
     * Send final response JSON and exit cleanly.
     */
    public function reply(array|string $payload, int $delayMs = 400, array $extra = []): never
    {
        header('Content-Type: application/json; charset=utf-8');

        if (is_array($payload)) {
            echo json_encode($this->repairMojibakeValue($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo json_encode($this->repairMojibakeValue(array_merge([
                'response' => $payload,
                'delay_ms' => $delayMs
            ], $extra)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        exit;
    }

    private function repairMojibakeValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->repairMojibake($value);
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->repairMojibakeValue($item);
            }
        }

        return $value;
    }

    private function repairMojibake(string $text): string
    {
        if (!preg_match('/[ÃÂØÙðâ]/u', $text)) {
            return $text;
        }

        $best = $text;
        $bestScore = $this->mojibakeScore($best);

        for ($pass = 0; $pass < 2; $pass++) {
            $candidate = @iconv('UTF-8', 'Windows-1252//IGNORE', $best);
            if ($candidate === false || $candidate === '' || !preg_match('//u', $candidate)) {
                break;
            }

            $candidateScore = $this->mojibakeScore($candidate);
            if ($candidateScore >= $bestScore) {
                break;
            }

            $best = $candidate;
            $bestScore = $candidateScore;
        }

        return $best;
    }

    private function shouldAnswerFromBuilderContext(string $query, array $context): bool
    {
        $text = strtolower($query);
        $selectedCount = (int) ($context['selectedCount'] ?? 0);
        $wantsCatalogBuild = preg_match('/recommend|suggest|build.*around|balanced.*build|gaming pc|pc build|اقترح|تجميعة ألعاب|تجميعة العاب|حاسوب ألعاب|حاسوب العاب/u', $text);
        $wantsBuilderAdvice = preg_match('/analy[sz]e|current build|my build|next|missing|continue|compat|watt|power|psu|thermal|cool|check|diagnostic|budget|optimi[sz]e|save|cheaper|value|waste|service|assembly|bios|stress|windows|install|checkout|حلل|تحليل|تجميعتي|التجميعة الحالية|التالي|الناقصة|الناقص|توافق|طاقة|مزود|تبريد|فحص|افحص|تشخيص|ميزانية|تحسين|وفر|قيمة|خدمات|الدفع|تركيب|بيوس|اختبار/u', $text);

        if ($wantsCatalogBuild && $selectedCount === 0 && !$wantsBuilderAdvice) {
            return false;
        }

        return (bool) $wantsBuilderAdvice || $selectedCount > 0;
    }

    private function formatBuilderContextResponse(string $query, array $context): string
    {
        $selected = is_array($context['selected'] ?? null) ? $context['selected'] : [];
        $missing = array_values(array_filter((array) ($context['missing'] ?? []), static fn($item) => trim((string) $item) !== ''));
        $services = is_array($context['services'] ?? null) ? $context['services'] : [];
        $total = (float) ($context['totalPrice'] ?? 0);
        $target = (float) ($context['targetBudget'] ?? 0);
        $wattage = (int) ($context['totalWattage'] ?? 0);
        $psu = (int) ($context['recommendedPsu'] ?? 0);
        $selectedCount = (int) ($context['selectedCount'] ?? 0);
        $text = strtolower($query);
        $isArabic = (bool) preg_match('/\p{Arabic}/u', $query);
        $partNamesArabic = [
            'cpu' => 'المعالج',
            'motherboard' => 'اللوحة الأم',
            'gpu' => 'البطاقة الرسومية',
            'ram' => 'الذاكرة',
            'storage' => 'التخزين',
            'psu' => 'مزود الطاقة',
            'case' => 'الصندوق',
            'cooling' => 'التبريد',
            'monitor' => 'الشاشة',
            'accessories' => 'الإكسسوارات',
        ];

        $selectedLines = [];
        foreach ($selected as $slot => $product) {
            if (!is_array($product) || empty($product['name'])) {
                continue;
            }
            $selectedLines[] = '- ' . strtoupper((string) $slot) . ': ' . (string) $product['name'];
        }
        if ($selectedLines === []) {
            $selectedLines[] = '- No parts selected yet';
        }
        $selectedLinesArabic = [];
        foreach ($selected as $slot => $product) {
            if (!is_array($product) || empty($product['name'])) {
                continue;
            }
            $label = $partNamesArabic[strtolower((string) $slot)] ?? (string) $slot;
            $selectedLinesArabic[] = '- ' . $label . ': ' . (string) $product['name'];
        }
        if ($selectedLinesArabic === []) {
            $selectedLinesArabic[] = '- لا توجد قطع مختارة بعد';
        }

        $missingText = $missing ? implode(', ', $missing) : 'none';
        $missingArabic = array_map(static function ($slot) use ($partNamesArabic) {
            $key = strtolower((string) $slot);
            return $partNamesArabic[$key] ?? (string) $slot;
        }, $missing);
        $missingTextArabic = $missingArabic ? implode('، ', $missingArabic) : 'لا شيء';
        $budgetLine = $target > 0
            ? ($total > $target
                ? 'Over target by **' . number_format($total - $target, 2) . ' DH**.'
                : 'Budget headroom: **' . number_format($target - $total, 2) . ' DH**.')
            : 'No target budget is set yet.';
        $budgetLineArabic = $target > 0
            ? ($total > $target
                ? 'فوق الميزانية بـ **' . number_format($total - $target, 2) . ' DH**.'
                : 'المتبقي من الميزانية: **' . number_format($target - $total, 2) . ' DH**.')
            : 'لم يتم تحديد ميزانية مستهدفة بعد.';

        if ($isArabic) {
            if (preg_match('/التالي|الناقصة|الناقص|ماذا|اختار|أختار|الخطوة/u', $text)) {
                $next = $missing[0] ?? null;
                if ($next === null) {
                    return "كل القطع الأساسية مكتملة.\n\n**المجموع:** " . number_format($total, 2) . " DH\n**الاستهلاك التقريبي:** {$wattage}W\n**مزود الطاقة المقترح:** {$psu}W+\n\nالخطوة التالية: راجع الفحص، أضف الخدمات المناسبة، ثم أضف التجميعة إلى السلة أو صدّر العرض.";
                }

                $nextLabel = $partNamesArabic[strtolower((string) $next)] ?? (string) $next;
                return "الخطوة الأفضل الآن: اختر **{$nextLabel}**.\n\nالتجميعة الحالية:\n" . implode("\n", $selectedLinesArabic) . "\n\nالناقص: {$missingTextArabic}.";
            }

            if (preg_match('/توافق|طاقة|مزود|تبريد|فحص|افحص|تشخيص/u', $text)) {
                $issues = [];
                if (empty($selected['cpu'])) $issues[] = 'المعالج غير مختار، لذلك فحص المقبس والتبريد غير مكتمل.';
                if (empty($selected['gpu'])) $issues[] = 'البطاقة الرسومية غير مختارة، لذلك لا يمكن تقييم أداء الألعاب بعد.';
                if (empty($selected['psu'])) $issues[] = "مزود الطاقة غير مختار. الاقتراح الحالي: {$psu}W+.";
                if (!empty($selected['cpu']) && empty($selected['cooling'])) $issues[] = 'المعالج مختار بدون تبريد.';

                return "فحص التجميعة الحالي:\n\n**القطع المختارة:** {$selectedCount}/7\n**المجموع:** " . number_format($total, 2) . " DH\n**الاستهلاك التقريبي:** {$wattage}W\n**مزود الطاقة المقترح:** {$psu}W+\n**الناقص:** {$missingTextArabic}\n\n" .
                    ($issues ? "ملاحظات:\n" . implode("\n", array_map(static fn($item) => '- ' . $item, $issues)) : 'لا توجد مشاكل واضحة في القطع المختارة حاليا. اترك هامشا جيدا للطاقة والتبريد.');
            }

            if (preg_match('/ميزانية|تحسين|وفر|قيمة|أرخص|ارخص/u', $text)) {
                $tips = [];
                if ($target > 0 && $total > $target) $tips[] = 'ابدأ بمراجعة تكلفة البطاقة الرسومية واللوحة الأم، فهما غالبا أسرع مكان لتقليل السعر.';
                if (empty($selected['gpu'])) $tips[] = 'اختر البطاقة الرسومية بعد تحديد المنصة حتى تذهب الميزانية لما يرفع الأداء فعلا.';
                if ($tips === []) $tips[] = 'أنفق حيث يظهر الفرق: GPU للألعاب، CPU/RAM للأعمال الثقيلة، والتخزين بعد تثبيت الأداء الأساسي.';

                return "تحسين الميزانية:\n\n**المجموع:** " . number_format($total, 2) . " DH\n{$budgetLineArabic}\n\n" . implode("\n", array_map(static fn($item) => '- ' . $item, $tips));
            }

            if (preg_match('/خدمات|الدفع|تركيب|ويندوز|بيوس|اختبار/u', $text)) {
                $serviceNames = array_values(array_filter(array_map(static fn($service) => is_array($service) ? (string) ($service['name'] ?? '') : '', $services)));
                return "اقتراح الخدمات:\n\nالخدمات المختارة: " . ($serviceNames ? implode('، ', $serviceNames) : 'لا شيء') . ".\n\n- خدمة التجميع مفيدة للكابل مانجمنت وتجهيز الجهاز عند التسليم.\n- تقرير اختبار الضغط مهم بعد اختيار المعالج والبطاقة الرسومية.\n- تحديث BIOS مفيد مع المنصات الجديدة أو القطع المختلطة.";
            }

            return "ملخص التجميعة الحالية:\n\n" . implode("\n", $selectedLinesArabic) . "\n\n**المجموع:** " . number_format($total, 2) . " DH\n**الاستهلاك التقريبي:** {$wattage}W\n**مزود الطاقة المقترح:** {$psu}W+\n**الناقص:** {$missingTextArabic}\n{$budgetLineArabic}";
        }

        if (preg_match('/next|missing|continue|what should|which should/i', $text)) {
            $next = $missing[0] ?? null;
            if ($next === null) {
                return "Your required component list is complete.\n\n**Total:** " . number_format($total, 2) . " DH\n**Estimated draw:** {$wattage}W\n**Recommended PSU:** {$psu}W+\n\nNext: review diagnostics, add services if needed, then add everything to cart or export the quote.";
            }

            $advice = "Next best step: choose **{$next}**.\n\nCurrent build:\n" . implode("\n", $selectedLines) . "\n\nMissing: {$missingText}.";
            if (stripos($next, 'GPU') !== false) {
                $advice .= "\n\nFor gaming, match the GPU to your resolution/FPS target before spending on extras.";
            } elseif (stripos($next, 'PSU') !== false) {
                $advice .= "\n\nUse at least **{$psu}W** for the current estimate, with headroom for future upgrades.";
            } elseif (stripos($next, 'COOLING') !== false) {
                $advice .= "\n\nPick cooling based on CPU wattage: strong air for midrange, bigger tower/AIO for high-wattage chips.";
            }
            return $advice;
        }

        if (preg_match('/compat|watt|power|psu|thermal|cool|check|diagnostic/i', $text)) {
            $issues = [];
            if (empty($selected['cpu'])) $issues[] = 'CPU missing, so socket and cooling checks are incomplete.';
            if (empty($selected['gpu'])) $issues[] = 'GPU missing, so gaming balance cannot be judged yet.';
            if (empty($selected['psu'])) $issues[] = "PSU missing. Current recommendation: {$psu}W+.";
            if (!empty($selected['cpu']) && empty($selected['cooling'])) $issues[] = 'CPU selected without cooling.';

            return "Current builder check:\n\n**Selected parts:** {$selectedCount}/7\n**Total:** " . number_format($total, 2) . " DH\n**Estimated power draw:** {$wattage}W\n**Recommended PSU:** {$psu}W+\n**Missing:** {$missingText}\n\n" .
                ($issues ? "Watch-outs:\n" . implode("\n", array_map(static fn($item) => '- ' . $item, $issues)) : 'No obvious red flags from the selected components. Keep enough PSU and cooling headroom for future upgrades.');
        }

        if (preg_match('/budget|optimi[sz]e|save|cheaper|value|waste/i', $text)) {
            $tips = [];
            if ($target > 0 && $total > $target) $tips[] = 'Start with GPU and motherboard spend; those are the easiest places to overshoot.';
            if (empty($selected['gpu'])) $tips[] = 'Choose the GPU after CPU/platform so the budget lands where FPS benefits most.';
            if (!empty($selected['ram']) && preg_match('/64|128/i', (string) ($selected['ram']['name'] ?? ''))) $tips[] = '64GB+ RAM is only worth it for workstation/video/AI workloads; most gaming builds prefer 32GB.';
            if ($tips === []) $tips[] = 'Spend where the workload feels it first: GPU for gaming, CPU/RAM for workstation, storage only after core performance is settled.';

            return "Budget optimization pass:\n\n**Total:** " . number_format($total, 2) . " DH\n{$budgetLine}\n\n" . implode("\n", array_map(static fn($item) => '- ' . $item, $tips));
        }

        if (preg_match('/service|assembly|bios|stress|windows|install|checkout/i', $text)) {
            $serviceNames = array_values(array_filter(array_map(static fn($service) => is_array($service) ? (string) ($service['name'] ?? '') : '', $services)));
            $recommendations = [
                'Professional assembly is worth it if you want cable management and a ready-to-use delivery.',
                'Stress test report is useful once CPU/GPU are selected.',
                'BIOS update helps with newer platforms and mixed inventory.',
            ];

            return "Service recommendation:\n\nSelected services: " . ($serviceNames ? implode(', ', $serviceNames) : 'none') . ".\n\n" . implode("\n", array_map(static fn($item) => '- ' . $item, $recommendations));
        }

        return "Current build summary:\n\n" . implode("\n", $selectedLines) . "\n\n**Total:** " . number_format($total, 2) . " DH\n**Estimated draw:** {$wattage}W\n**Recommended PSU:** {$psu}W+\n**Missing:** {$missingText}\n{$budgetLine}";
    }

    private function mojibakeScore(string $text): int
    {
        return preg_match_all('/[ÃÂØÙðâ�]/u', $text) ?: 0;
    }
}
