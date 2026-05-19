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
        header('Content-Type: application/json');

        // Retrieve raw JSON request input
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $rawQuery = trim((string) ($input['message'] ?? ''));

        // Normalize query and extract tokens
        $normalized = $this->parser->normalize($rawQuery);
        $tokens = $this->parser->tokenize($normalized);

        // Detect user language and configure response generator
        $language = trim((string) ($input['language'] ?? ''));
        if ($language !== 'english' && $language !== 'french' && $language !== 'darija') {
            $language = $this->parser->detectLanguage($rawQuery);
        }
        $this->generator->setLanguage($language);

        if ($normalized === '') {
            $this->reply($this->generator->getConfused(), 400);
        }

        // Classify standard query intent
        $intent = $this->classifier->classify($normalized, $tokens, $rawQuery);

        // 0. Free products joke
        if ($intent === IntentClassifier::INTENT_FREE_PRANK) {
            if ($language === 'french') {
                $prankResponse = 'Vous obtiendrez tous les produits gratuitement en cliquant sur ce lien : [recuperer vos produits gratuits](https://youtu.be/GBIIQ0kP15E?si=Ra9y-5Mwfi5X-XO0)';
            } elseif ($language === 'darija') {
                $prankResponse = 'Tqder ta5od kolchi fabor ila clickiti hna: [claim l-fabor dialk](https://www.youtube.com/watch?v=pSI_mZkDNvQ)';
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
            } elseif ($language === 'darija') {
                $brbPool = ["Hanya khoud wqtek — rani hna! 😊", "Wakha, thalla n-choufok mn b3d!", "Khoud wqtek m3a rasek!"];
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
            } elseif ($language === 'darija') {
                $howPool = [
                    "Labass rbi ykhalik, kolchi mzn! 😄 Chnou katqleb 3lih dial pieces PC lyouma?",
                    "Kolchi khddam 100%! Ready n-3awnak tlqa l-piece l-nadiya. Sowlni chnou khassak!",
                    "Khddam fl-mkhyr! Chnou n-3awnak n-traqi lyouma dial hardware?"
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
            } elseif ($language === 'darija') {
                $laughPool = [
                    "Haha, dahka nadiya! 😄 Iwa ready n-lqaw chi pieces wa3rin?",
                    "😄 Hahahaha, iwa chnou khassna dial pieces lyouma?",
                    "lol, wa3r l-qser m3ak! Yallah chouf pieces PC l-wa3rin — chnou khassak?"
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
                        } elseif ($language === 'darija') {
                            $replyMsg = "Salam ! Lqit l-order dialek **#{$order['id']}** b smiyat **{$clientName}**.\n\n" .
                                        "📌 **Status dyalo** : `{$orderStatus}`\n" .
                                        "📅 **Tariq l-order** : {$datePlaced}\n" .
                                        "💰 **Total dyal l-flous** : **{$totalCost} DH**\n\n" .
                                        "Tqder t-suivrer details dial l-orders dialek kamlin direct mn hna: [**Espace Client (Commandes)**](account.php?tab=orders).";
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
                        } elseif ($language === 'darija') {
                            $replyMsg = "Smehli, malqit hta order b had raqm **#{$orderId}**. T-checki l-raqm w re-try s'il vous plaît !";
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
                            } elseif ($language === 'darija') {
                                $replyMsg = "Hit rak connecté, lqit a5ir order dialek **#{$latestOrder['id']}** :\n\n" .
                                            "📌 **Status dyalo** : `{$orderStatus}`\n" .
                                            "📅 **Tariq** : {$datePlaced}\n" .
                                            "💰 **Total** : **{$totalCost} DH**\n\n" .
                                            "Ila bghiti t-suivrer order akhor specific, goliya : **Track [Raqm l-order]** (ex : `Track #1024`).";
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
                } elseif ($language === 'darija') {
                    $introMsg = "Malqitch pieces {$catSearchResult['category']} {$translatedPriceLabel}, walakin ha l-piece l-qriba f had l-category:";
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
            } elseif ($language === 'darija') {
                $intro = 'Ha combo mzn w balanced 3la hssab l-budget dialk:';
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
        if (is_array($payload)) {
            echo json_encode($payload);
        } else {
            echo json_encode(array_merge([
                'response' => $payload,
                'delay_ms' => $delayMs
            ], $extra));
        }
        exit;
    }
}
