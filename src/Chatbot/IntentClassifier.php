<?php
declare(strict_types=1);

namespace MarocPC\Chatbot;

class IntentClassifier
{
    public const INTENT_FREE_PRANK   = 'free_prank';
    public const INTENT_FAREWELL     = 'farewell';
    public const INTENT_BRB          = 'brb';
    public const INTENT_GREETING     = 'greeting';
    public const INTENT_HOW_ARE_YOU  = 'how_are_you';
    public const INTENT_LAUGHTER     = 'laughter';
    public const INTENT_HELP         = 'help';
    public const INTENT_GRATITUDE    = 'gratitude';
    public const INTENT_COMPARE      = 'compare';
    public const INTENT_BUDGET       = 'budget';
    public const INTENT_STOCK_CHECK  = 'stock_check';
    public const INTENT_BUILD        = 'build';
    public const INTENT_RMA          = 'rma';
    public const INTENT_ORDER_STATUS = 'order_status';
    public const INTENT_LAPTOP_FINDER = 'laptop_finder';
    public const INTENT_UNKNOWN      = 'unknown';

    private array $greetWords = ['hi', 'hello', 'hey', 'yo', 'salam', 'bonjour', 'howdy', 'hiya', 'greetings', 'sup', 'wassup', 'wazzup', 'ssalam', 'salamo', 'salamou', 'marhaba', 'salut', 'ahlan', 'wa fin', 'wafin', 'cc', 'coucou', 'wesh', 'wsh', 'kikou'];
    
    private array $farewellWords = ['bye', 'goodbye', 'cya', 'later', 'farewell', 'adieu', 'ttyl', 'gtg', 'g2g', 'bbl', 'au revoir', 'chaw', 'ciao', 'layhna', 'thalla', 'thallaw', 'besslama', 'bslama', 'allah i3awn', 'lay3awn', 'a plus', 'a+'];

    private array $laughterWords = ['funny', 'lol', 'lmao', 'haha', 'hehe', 'mdr', 'ptdr', 'harya', 'mdy3', 'hhhh', 'hhhhh', 'hahaha', 'hh'];

    private array $helpPhrases = ['what can you do', 'how do you work', 'help me', 'how can you help', 'what do you know', 'what can i ask', 'aidez moi', 'aide', 'a3wini', '3awni', 'kifach nkhdem'];

    private array $gratitudeWords = ['thanks', 'thank', 'thx', 'perfect', 'awesome', 'great', 'nice', 'sweet', 'cheers', 'brilliant',
                                    'excellent', 'appreciate', 'helpful', 'good job', 'well done', 'no problem', 'no worries', 'merci', 'chokran', 'shokran', 'shukran', 'chokrane', 'baraka laho fik', 'lah yhfdk', 'layhfdk', 'lah ykhelik', 'tbarkellah', 'cimer'];

    private array $productWords = ['gpu', 'cpu', 'ram', 'ssd', 'hdd', 'nvme', 'processor', 'graphic', 'memory', 'storage', 'card', 'laptop', 'laptops', 'processeur', 'proco', 'memoire', 'stockage', 'disque', 'portable', 'ordinateur', 'ordi', 'piece', 'pieces'];

    private array $compareWords = ['compare', 'vs', 'versus', 'difference', 'between', 'better', 'which', 'comparer', 'diff', 'entre', 'meilleur', 'mieux', 'ahsan', 'hsen'];

    private array $budgetWords = ['cheap', 'cheapest', 'budget', 'sale', 'deal', 'discount', 'affordable', 'low price', 'inexpensive', 'low cost', 'pas cher', 'moins cher', 'rkhis', 'rkhiss', 'rkhi5', 'rkhess', 'r5is', 'rkhissa', 'promo', 'promotion'];

    private array $stockWords = ['stock', 'available', 'availability', 'in stock', 'do you have', 'have you got', 'kayn', 'kaynin', 'kyn', 'kynin', 'dispo', 'disponible', 'disponibles', '3ndkom', 'andkom', '3ndkum', 'andkum', 'wach kayn', 'wesh kayn'];

    private array $buildWords = ['build', 'setup', 'pc', 'gaming pc', 'rig', 'combo', 'config', 'configuration', 'monter'];

    private array $rmaWords = ['rma', 'return', 'refund', 'exchange', 'warranty', 'sav', 'diagnostic', 'damaged', 'missing', 'retour', 'remboursement', 'echange', 'garantie', 'dommage', 'rje3', 'rj3', 'rembourser', 'reparation'];
    
    private array $orderStatusWords = ['track', 'order', 'status', 'package', 'amana', 'tracking', 'commande', 'suivi', 'colis', 'fin wsal', 'fin wsel', 'suivre'];

    private array $laptopFinderWords = ['laptop finder', 'choose laptop', 'which laptop', 'find laptop', 'portatif', 'finder', 'curator', 'optimisation', 'golden filters'];

    /**
     * Check if tokens contain any single word, or if text contains any multi-word phrase.
     */
    public function containsAny(string $text, array $tokens, array $words): bool
    {
        foreach ($words as $w) {
            if (strpos($w, ' ') !== false) {
                if (strpos($text, $w) !== false) {
                    return true;
                }
            } else {
                if (in_array($w, $tokens, true)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check across BOTH normalized and raw text (for pre-translation Darija/French matches).
     */
    private function matchesAny(string $normalized, array $normalizedTokens, string $rawLower, array $rawTokens, array $words): bool
    {
        return $this->containsAny($normalized, $normalizedTokens, $words)
            || $this->containsAny($rawLower, $rawTokens, $words);
    }

    /**
     * Check if a phrase is present in either normalized or raw text.
     */
    public function phraseIn(string $normalized, array $phrases): bool
    {
        foreach ($phrases as $p) {
            if (strpos($normalized, $p) !== false) {
                return true;
            }
        }
        return false;
    }

    private function phraseInAny(string $normalized, string $rawLower, array $phrases): bool
    {
        return $this->phraseIn($normalized, $phrases) || $this->phraseIn($rawLower, $phrases);
    }

    /**
     * Classify query intent using heuristic scoring matching index logic.
     */
    public function classify(string $normalized, array $tokens, string $query): string
    {
        // Build a lowercased, accent-stripped version of the raw query for Darija/French matching
        $rawLower = strtolower(trim($query));
        $rawLower = strtr($rawLower, [
            'é' => 'e', 'è' => 'e', 'à' => 'a', 'ç' => 'c', 'ù' => 'u',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
            'ë' => 'e', 'ï' => 'i', 'ü' => 'u', 'œ' => 'oe',
        ]);
        $rawTokens = preg_split('/[\s\?\!\.,;:]+/', $rawLower, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // 0. Free products prank
        $freeProductIntent = (
                $this->matchesAny($normalized, $tokens, $rawLower, $rawTokens, ['free', 'gratis', 'fabor', 'gratuit', 'batel', 'bla flous'])
                && $this->matchesAny($normalized, $tokens, $rawLower, $rawTokens, ['product', 'products', 'items', 'everything', 'all', 'kolchi', 'tout', 'haja', '7aja', 'pc', 'laptop', 'piece', 'pieces'])
            )
            || $this->phraseInAny($normalized, $rawLower, [
                'all products', 'products for free', 'products in free', 'free products',
                'kolchi fabor', 'tout gratuit', 'chi haja fabor', 'chi 7aja fabor',
                'quelque chose gratuit', 'un truc gratuit', 'un pc gratuit', 'des pc gratuit',
                'haja fabor', '7aja fabor', 'haja batel', 'produit gratuit', 'produits gratuits'
            ]);

        if ($freeProductIntent) {
            return self::INTENT_FREE_PRANK;
        }

        // 1. Farewell
        if ($this->matchesAny($normalized, $tokens, $rawLower, $rawTokens, $this->farewellWords)) {
            return self::INTENT_FAREWELL;
        }

        // 1b. BRB holding reply
        if ($this->phraseInAny($normalized, $rawLower, ['be right back', 'brb', 'je reviens', 'tsnani', 'blati', 'hna m3ak', 'hadi nji', 'd9i9a'])) {
            return self::INTENT_BRB;
        }

        // 2. Greeting
        if (count($tokens) <= 3 && $this->matchesAny($normalized, $tokens, $rawLower, $rawTokens, $this->greetWords)) {
            return self::INTENT_GREETING;
        }

        // 2b. Casual "how are you" variants
        if ($this->phraseInAny($normalized, $rawLower, ['how are you', 'how r u', 'hru', 'you ok', 'u ok', 'ca va', 'comment ca va', 'kidayr', 'kidayra', 'labass', 'labas', 'cv', 'ki rak', 'ki raki'])) {
            return self::INTENT_HOW_ARE_YOU;
        }

        // 2c. Laughter reaction
        if ($this->matchesAny($normalized, $tokens, $rawLower, $rawTokens, $this->laughterWords)) {
            return self::INTENT_LAUGHTER;
        }

        // 3. Help capabilities
        if ($this->phraseInAny($normalized, $rawLower, $this->helpPhrases) || (isset($tokens[0]) && $tokens[0] === 'help' && count($tokens) <= 2)) {
            return self::INTENT_HELP;
        }

        // 4. Gratitude (check tokens and ensure it's not actually an inquiry mentioning a part)
        $hasGratitude = $this->matchesAny($normalized, $tokens, $rawLower, $rawTokens, $this->gratitudeWords);
        $hasProduct = $this->matchesAny($normalized, $tokens, $rawLower, $rawTokens, $this->productWords);
        if ($hasGratitude && !$hasProduct) {
            return self::INTENT_GRATITUDE;
        }

        // 5. Stock check intent
        if ($this->matchesAny($normalized, $tokens, $rawLower, $rawTokens, $this->stockWords) || $this->phraseInAny($normalized, $rawLower, ['do you have', 'have you got', 'in stock', 'wach kayn', 'wesh kayn', 'est ce disponible'])) {
            return self::INTENT_STOCK_CHECK;
        }

        // 6. Build intent
        if ($this->matchesAny($normalized, $tokens, $rawLower, $rawTokens, $this->buildWords) || $this->phraseInAny($normalized, $rawLower, ['gaming pc', 'build a pc', 'monter un pc'])) {
            return self::INTENT_BUILD;
        }

        // 7. Compare intent
        if ($this->matchesAny($normalized, $tokens, $rawLower, $rawTokens, $this->compareWords)) {
            return self::INTENT_COMPARE;
        }

        // 8. Budget intent
        if ($this->matchesAny($normalized, $tokens, $rawLower, $rawTokens, $this->budgetWords) || $this->phraseInAny($normalized, $rawLower, ['low price', 'low cost', 'good price', 'best deal', 'pas cher', 'moins cher'])) {
            return self::INTENT_BUDGET;
        }

        // 9. Order status intent
        if ($this->matchesAny($normalized, $tokens, $rawLower, $rawTokens, $this->orderStatusWords) || preg_match('/#?\b\d{4,}\b/', $rawLower) || preg_match('/\b(track|suivi|commande|colis|order)\b/i', $rawLower)) {
            return self::INTENT_ORDER_STATUS;
        }

        // 10. RMA / Return intent
        if ($this->matchesAny($normalized, $tokens, $rawLower, $rawTokens, $this->rmaWords) || $this->phraseInAny($normalized, $rawLower, ['returns and refunds', 'returns refunds', 'return policy', 'after sales', 'after-sales', 'diagnostic plan', 'rma ticket'])) {
            return self::INTENT_RMA;
        }

        // 11. Laptop finder intent
        if ($this->matchesAny($normalized, $tokens, $rawLower, $rawTokens, $this->laptopFinderWords) || $this->phraseInAny($normalized, $rawLower, ['outcome-oriented', 'find a laptop', 'choose a laptop', 'golden filters'])) {
            return self::INTENT_LAPTOP_FINDER;
        }

        return self::INTENT_UNKNOWN;
    }
}
