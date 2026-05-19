<?php
declare(strict_types=1);

namespace MarocPC\Chatbot;

class RequestParser
{
    /**
     * @var array Abbreviation and tech slang mapping.
     */
    private array $abbrevMap = [
        // ── Gratitude ──────────────────────────────────────────────────
        'tnx'       => 'thanks',
        'thnx'      => 'thanks',
        'thx'       => 'thanks',
        'ty'        => 'thanks',
        'tysm'      => 'thanks so much',
        'tyvm'      => 'thanks very much',
        'tyvvm'     => 'thanks very very much',
        'ty so much'=> 'thanks so much',
        'kk'        => 'ok',
        'k'         => 'ok',

        // ── Farewells ──────────────────────────────────────────────────
        'cya'       => 'goodbye',
        'c ya'      => 'goodbye',
        'cu'        => 'goodbye',
        'ttyl'      => 'goodbye',
        'ttys'      => 'goodbye',
        'gtg'       => 'goodbye',
        'g2g'       => 'goodbye',
        'brb'       => 'be right back',
        'bbl'       => 'be back later',

        // ── Greetings ──────────────────────────────────────────────────
        'hru'       => 'how are you',
        'wyd'       => 'what you doing',
        'wbu'       => 'what about you',
        'wb'        => 'welcome back',
        'sup'       => 'hello',
        'wazzup'    => 'hello',
        'wassup'    => 'hello',
        'wsp'       => 'hello',

        // ── Reactions / Fillers ────────────────────────────────────────
        'lol'       => 'funny',
        'lmao'      => 'funny',
        'lmfao'     => 'funny',
        'rofl'      => 'funny',
        'haha'      => 'funny',
        'hehe'      => 'funny',
        'xd'        => 'funny',
        'omg'       => 'wow',
        'omfg'      => 'wow',
        'wtf'       => 'what',
        'wth'       => 'what',
        'idk'       => 'i do not know',
        'ngl'       => 'honestly',
        'tbh'       => 'honestly',
        'imo'       => 'in my opinion',
        'imho'      => 'in my opinion',
        'afaik'     => 'as far as i know',
        'fwiw'      => 'for what it is worth',
        'iirc'      => 'if i remember correctly',
        'ikr'       => 'i know right',
        'smh'       => 'disappointed',
        'fml'       => 'frustrated',
        'rn'        => 'right now',
        'atm'       => 'at the moment',
        'asap'      => 'as soon as possible',
        'irl'       => 'in real life',
        'tl;dr'     => 'summary',
        'tldr'      => 'summary',
        'aka'       => 'also known as',
        'eta'       => 'estimated time',
        'diy'       => 'do it yourself',
        'faq'       => 'frequently asked questions',
        'gg'        => 'good game',
        'gl'        => 'good luck',
        'gj'        => 'good job',
        'np'        => 'no problem',
        'nw'        => 'no worries',
        'nbd'       => 'no big deal',
        'ez'        => 'easy',
        'pls'       => 'please',
        'plz'       => 'please',
        'plzz'      => 'please',
        'u'         => 'you',
        'ur'        => 'your',
        'r'         => 'are',
        'b4'        => 'before',
        'bc'        => 'because',
        'bcz'       => 'because',
        'cuz'       => 'because',
        'tho'       => 'though',
        'thru'      => 'through',
        'w/'        => 'with',
        'w/o'       => 'without',
        'tbf'       => 'to be fair',
        'fr'        => 'for real',
        'frfr'      => 'for real',
        'lowkey'    => 'somewhat',
        'highkey'   => 'very',
        'rly'       => 'really',
        'rlly'      => 'really',
        'istg'      => 'i swear',
        'nvm'       => 'never mind',
        'nm'        => 'never mind',
        'lemme'     => 'let me',
        'gimme'     => 'give me',
        'gonna'     => 'going to',
        'wanna'     => 'want to',
        'gotta'     => 'got to',
        'coulda'    => 'could have',
        'shoulda'   => 'should have',
        'woulda'    => 'would have',
        'kinda'     => 'kind of',
        'sorta'     => 'sort of',

        // ── PC / Tech slang ───────────────────────────────────────────
        'mobo'      => 'motherboard',
        'mb'        => 'motherboard',
        'gfx'       => 'graphics',
        'vram'      => 'video memory',
        'fps'       => 'frames per second',
        'pc'        => 'computer',
        'rig'       => 'computer',
        'build'     => 'computer build',
        'psu'       => 'power supply',
        'hdd'       => 'hard drive',
        'os'        => 'operating system',
        'tb'        => 'terabyte',
        'gb'        => 'gigabyte',
        'mb storage'=> 'megabyte',
        'aio'       => 'all in one cooler',
        'rgb'       => 'rgb lighting',
        'oc'        => 'overclocking',
        'overclock' => 'overclocking',
        'ocing'     => 'overclocking',

        // ── Typos ─────────────────────────────────────────────────────
        'processer' => 'processor',
        'procesor'  => 'processor',
        'grafic'    => 'graphic',
        'grafix'    => 'graphic',
        'memorry'   => 'memory',
        'memorie'   => 'memory',
        'hardrive'  => 'hard drive',
        'harddrive' => 'hard drive',
        'nvida'     => 'nvidia',
        'nvdia'     => 'nvidia',
        'amdd'      => 'amd',
        'intell'    => 'intel',
        'cheep'     => 'cheap',
        'chep'      => 'cheap',
        'disount'   => 'discount',
        'grahics'   => 'graphics',
        'vidoe'     => 'video',
        'reccomend' => 'recommend',
        'recomend'  => 'recommend',
        'expensiv'  => 'expensive',
        'avalable'  => 'available',
        'availble'  => 'available',

        // ── French & Franco-Darija translations ────────────────────────
        // Multi-word phrases first to prevent partial word collision
        'merci beaucoup'  => 'thanks so much',
        'baraka laho fik' => 'thanks',
        'au revoir'       => 'goodbye',
        'a bientot'       => 'goodbye',
        'aidez moi'       => 'help me',
        'carte graphique' => 'gpu',
        'carte graphik'   => 'gpu',
        'carte ecran'     => 'gpu',
        'kart ecran'      => 'gpu',
        'carte d\'ecran'  => 'gpu',
        'disque dur'      => 'hdd',
        'dix dur'         => 'hdd',
        'ordinateur portable' => 'laptop',
        'pc portable'     => 'laptop',
        'le moins cher'   => 'cheap',
        'moins cher'      => 'cheap',
        'pas cher'        => 'cheap',
        'combien coute'   => 'how much',
        'a quel prix'     => 'price',
        'ma bin'          => 'between',
        'moins de'        => 'under',
        'plus de'         => 'over',
        'ql mn'           => 'under',
        'qel men'         => 'under',
        'sghar mn'        => 'under',
        'ktar mn'         => 'over',
        'ktar men'        => 'over',
        'kber mn'         => 'over',
        'en stock'        => 'in stock',

        // Single words
        'bonjour'     => 'hello',
        'salut'       => 'hello',
        'salam'       => 'hello',
        'ssalam'      => 'hello',
        'salamo'      => 'hello',
        'salamou'     => 'hello',
        'marhaba'     => 'hello',
        'chaw'        => 'goodbye',
        'ciao'        => 'goodbye',
        'layhna'      => 'goodbye',
        'thalla'      => 'goodbye',
        'thallaw'     => 'goodbye',
        'merci'       => 'thanks',
        'chokran'     => 'thanks',
        'shokran'     => 'thanks',
        'shukran'     => 'thanks',
        'chokrane'    => 'thanks',
        'aide'        => 'help',
        'a3wini'      => 'help me',
        '3awni'       => 'help me',
        'combien'     => 'how much',
        'coute'       => 'cost',
        'prix'        => 'price',
        'taman'       => 'price',
        'chhal'       => 'how much',
        'bghit'       => 'i want',
        'khassni'     => 'i need',
        'khasni'      => 'i need',
        'khsni'       => 'i need',
        '5asni'       => 'i need',
        '5asny'       => 'i need',
        'nebghi'      => 'i want',
        '3ndkom'      => 'do you have',
        'andkom'      => 'do you have',
        '3ndkum'      => 'do you have',
        'andkum'      => 'do you have',
        '3ndi'        => 'i have',
        'andi'        => 'i have',
        'kayn'        => 'available',
        'kaynin'      => 'available',
        'kyn'         => 'available',
        'kynin'       => 'available',
        'dispo'       => 'available',
        'disponible'  => 'available',
        'disponibles' => 'available',
        'stock'       => 'stock',
        'comparer'    => 'compare',
        'diff'        => 'difference',
        'entre'       => 'between',
        'meilleur'    => 'best',
        'mieux'       => 'better',
        'ahsan'       => 'best',
        'hsen'        => 'better',
        'rkhis'       => 'cheap',
        'rkhiss'      => 'cheap',
        'rkhi5'       => 'cheap',
        'rkhess'      => 'cheap',
        'r5is'        => 'cheap',
        'ghali'       => 'expensive',
        'ghaly'       => 'expensive',
        '8ali'        => 'expensive',
        'cher'        => 'expensive',
        'processeur'  => 'cpu',
        'proco'       => 'cpu',
        'memoire'     => 'ram',
        'stockage'    => 'storage',
        'disque'      => 'drive',
        'portable'    => 'laptop',
        'ordinateur'  => 'computer',
        'ordi'        => 'computer',
        'et'          => 'and',
        'bin'         => 'between',
    ];

    /**
     * Strip accents from string (e.g. é -> e).
     */
    public function stripAccents(string $str): string
    {
        $map = [
            'é' => 'e', 'è' => 'e', 'à' => 'a', 'ç' => 'c', 'ù' => 'u',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
            'ë' => 'e', 'ï' => 'i', 'ü' => 'u', 'œ' => 'oe',
            'É' => 'e', 'È' => 'e', 'À' => 'a', 'Ç' => 'c', 'Ù' => 'u',
            'Â' => 'a', 'Ê' => 'e', 'Î' => 'i', 'Ô' => 'o', 'Û' => 'u',
            'Ë' => 'e', 'Ï' => 'i', 'Ü' => 'u', 'Œ' => 'oe'
        ];
        return strtr($str, $map);
    }

    /**
     * Detect user's language from raw query.
     * Returns 'english', 'french', or 'darija'.
     */
    public function detectLanguage(string $rawQuery): string
    {
        $query = strtolower(trim($rawQuery));
        $query = $this->stripAccents($query);

        // Darija unique words/slang
        $darijaWords = [
            'salam', 'ssalam', 'bghit', 'khassni', 'khasni', 'khsni', '5asni', '5asny', 'nebghi', '3ndkom', 
            'andkom', '3ndkum', 'andkum', '3ndi', 'andi', 'kayn', 'kaynin', 'kyn', 'kynin', 'chhal', 'taman', 
            'rkhis', 'rkhiss', 'rkhi5', 'rkhess', 'r5is', 'ghali', 'ghaly', '8ali', 'chokran', 'shokran', 
            'shukran', 'chokrane', 'thalla', 'thallaw', 'hsen', 'ahsan', 'diali', 'dyali', 'fabor'
        ];

        // French unique words
        $frenchWords = [
            'bonjour', 'salut', 'cherche', 'combien', 'coute', 'prix', 'portable', 'carte', 'graphique', 
            'processeur', 'memoire', 'stockage', 'disque', 'pour', 'avec', 'dans', 'est', 'les', 'des', 
            'une', 'un', 'le', 'la', 'en', 'de', 'et', 'merci', 'beaucoup', 'disponible', 'disponibles',
            'mieux', 'meilleur', 'comparer'
        ];

        $tokens = preg_split('/\s+/', $query) ?: [];

        $darijaCount = 0;
        $frenchCount = 0;

        foreach ($tokens as $t) {
            $cleanToken = preg_replace('/[^a-z0-9]/', '', $t);
            if (in_array($cleanToken, $darijaWords, true)) {
                $darijaCount++;
            }
            if (in_array($cleanToken, $frenchWords, true)) {
                $frenchCount++;
            }
        }

        foreach ($darijaWords as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $query)) {
                $darijaCount += 0.5;
            }
        }
        foreach ($frenchWords as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $query)) {
                $frenchCount += 0.5;
            }
        }

        if ($darijaCount > 0 && $darijaCount >= $frenchCount) {
            return 'darija';
        }
        if ($frenchCount > 0 && $frenchCount > $darijaCount) {
            return 'french';
        }

        return 'english';
    }

    /**
     * Normalize the incoming query (slang expansion, lowercase, strip junk).
     */
    public function normalize(string $rawQuery): string
    {
        $query = strtolower(trim($rawQuery));
        $query = $this->stripAccents($query);

        // Phase 1: Expand slang/abbreviations
        foreach ($this->abbrevMap as $abbrev => $expansion) {
            $pattern = '/\b' . preg_quote($abbrev, '/') . '\b/i';
            $query = (string) preg_replace($pattern, $expansion, $query);
        }

        // Phase 2: Normalize punctuation and whitespace
        $normalized = str_replace(['-', '_', '/'], ' ', $query);
        $normalized = (string) preg_replace('/[^a-z0-9 ]/', '', $normalized);
        $normalized = (string) preg_replace('/\s+/', ' ', trim($normalized));

        return $normalized;
    }

    /**
     * Tokenize query into array of words.
     */
    public function tokenize(string $normalized): array
    {
        if (strlen($normalized) < 1) {
            return [];
        }
        return explode(' ', $normalized);
    }

    /**
     * Parse price amount taking 'k' suffix into account.
     */
    public function parsePriceAmount(string $value, string $suffix = ''): int
    {
        $amount = (float) str_replace(',', '.', $value);
        if (strtolower($suffix) === 'k') {
            $amount *= 1000;
        }
        return (int) round($amount);
    }

    /**
     * Parse min/max price range limits from queries.
     */
    public function parsePriceFilter(string $query): array
    {
        $filter = ['min' => 0, 'max' => 0, 'label' => ''];

        if (preg_match('/between\s+(\d+(?:[.,]\d+)?)\s*(k)?\s*(?:and|to|-)\s*(\d+(?:[.,]\d+)?)\s*(k)?/i', $query, $m)) {
            $min = $this->parsePriceAmount($m[1], $m[2] ?? '');
            $max = $this->parsePriceAmount($m[3], $m[4] ?? '');
            if ($min > $max) {
                [$min, $max] = [$max, $min];
            }
            return ['min' => $min, 'max' => $max, 'label' => "between {$min} and {$max} DH"];
        }

        if (preg_match('/(?:under|below|less than|max|maximum|budget)\s+(\d+(?:[.,]\d+)?)\s*(k)?/i', $query, $m)) {
            $max = $this->parsePriceAmount($m[1], $m[2] ?? '');
            return ['min' => 0, 'max' => $max, 'label' => "under {$max} DH"];
        }

        if (preg_match('/(?:over|above|more than|min|minimum)\s+(\d+(?:[.,]\d+)?)\s*(k)?/i', $query, $m)) {
            $min = $this->parsePriceAmount($m[1], $m[2] ?? '');
            return ['min' => $min, 'max' => 0, 'label' => "over {$min} DH"];
        }

        return $filter;
    }
}
