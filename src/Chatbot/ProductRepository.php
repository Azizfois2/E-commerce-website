<?php
declare(strict_types=1);

namespace MarocPC\Chatbot;

use PDO;

class ProductRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Build direct stock checking answers for specific products/laptops.
     */
    public function directStockAnswer(string $normalized, array $tokens, string $productSelect, string $language = 'english'): ?array
    {
        $stopWords = [
            'do', 'you', 'have', 'got', 'is', 'are', 'the', 'a', 'an', 'in', 'stock', 'available',
            'availability', 'please', 'show', 'me', 'find', 'any', 'product', 'products', 'laptop', 'laptops'
        ];
        $genericProductWords = ['gpu', 'cpu', 'ram', 'ssd', 'hdd', 'nvme', 'processor', 'graphic', 'graphics', 'memory', 'storage', 'card', 'laptop', 'laptops'];
        $terms = [];

        foreach ($tokens as $token) {
            if (strlen($token) < 2 || in_array($token, $stopWords, true) || in_array($token, $genericProductWords, true)) {
                continue;
            }
            if (preg_match('/\d/', $token) || strlen($token) > 3) {
                $terms[] = $token;
            }
        }

        $terms = array_values(array_unique($terms));
        if ($terms === []) {
            if (in_array('laptop', $tokens, true) || in_array('laptops', $tokens, true)) {
                $terms = ['laptop'];
            } else {
                return null;
            }
        }

        $clauses = [];
        $scoreParts = [];
        $params = [];
        foreach ($terms as $i => $term) {
            $whereNameKey = ':term_where_name_' . $i;
            $whereBrandKey = ':term_where_brand_' . $i;
            $scoreNameKey = ':term_score_name_' . $i;
            $scoreBrandKey = ':term_score_brand_' . $i;
            $clauses[] = "(name LIKE {$whereNameKey} OR brand LIKE {$whereBrandKey})";
            $scoreParts[] = "(CASE WHEN name LIKE {$scoreNameKey} THEN 2 WHEN brand LIKE {$scoreBrandKey} THEN 1 ELSE 0 END)";
            $params[$whereNameKey] = '%' . $term . '%';
            $params[$whereBrandKey] = '%' . $term . '%';
            $params[$scoreNameKey] = '%' . $term . '%';
            $params[$scoreBrandKey] = '%' . $term . '%';
        }

        $scoreSql = implode(' + ', $scoreParts);
        
        // Search products table
        $sqlProducts = "SELECT {$productSelect}, ({$scoreSql}) AS match_score FROM products WHERE " . implode(' OR ', $clauses) . ' ORDER BY match_score DESC, in_stock DESC LIMIT 3';
        $stmt1 = $this->db->prepare($sqlProducts);
        $stmt1->execute($params);
        $productItems = $stmt1->fetchAll() ?: [];

        // Search laptops table
        $laptopSelect = "id, name, brand, 'laptop' AS category, price, old_price, NULL AS badge, 4.8 AS rating, 15 AS reviews, image, in_stock, stock_quantity, specs";
        $sqlLaptops = "SELECT {$laptopSelect}, ({$scoreSql}) AS match_score FROM laptops WHERE " . implode(' OR ', $clauses) . ' ORDER BY match_score DESC, in_stock DESC LIMIT 3';
        $stmt2 = $this->db->prepare($sqlLaptops);
        $stmt2->execute($params);
        $laptopItems = $stmt2->fetchAll() ?: [];

        $items = array_merge($productItems, $laptopItems);
        
        usort($items, function($a, $b) {
            return $b['match_score'] <=> $a['match_score'];
        });
        
        $items = array_slice($items, 0, 3);

        if ($items === []) {
            return null;
        }

        $lines = [];
        foreach ($items as $item) {
            $qty = (int) ($item['stock_quantity'] ?? 0);
            if ($language === 'french') {
                $stockText = ((int) $item['in_stock'] === 1 && $qty > 0)
                    ? "Oui, **{$item['name']}** est en stock avec {$qty} unite" . ($qty === 1 ? '' : 's') . " disponible" . ($qty === 1 ? '' : 's') . "."
                    : "**{$item['name']}** est actuellement en rupture de stock.";
                $lines[] = $stockText . " Prix : **{$item['price']} DH**.";
            } elseif ($language === 'darija') {
                $stockText = ((int) $item['in_stock'] === 1 && $qty > 0)
                    ? "Iyeh, **{$item['name']}** kayn f-stock khddam, bqat fiha {$qty} unit" . ($qty === 1 ? '' : 's') . "."
                    : "**{$item['name']}** makaynach f-stock lyouma.";
                $lines[] = $stockText . " Taman: **{$item['price']} DH**.";
            } else {
                $stockText = ((int) $item['in_stock'] === 1 && $qty > 0)
                    ? "Yes, **{$item['name']}** is in stock with {$qty} unit" . ($qty === 1 ? '' : 's') . " available."
                    : "**{$item['name']}** is currently out of stock.";
                $lines[] = $stockText . " Price: **{$item['price']} DH**.";
            }
        }

        if ($language === 'french') {
            $followUp = "\n\nSouhaitez-vous voir des alternatives egalement ?";
        } elseif ($language === 'darija') {
            $followUp = "\n\nBghiti n-wrik alternatives khrin?";
        } else {
            $followUp = "\n\nWant me to show alternatives too?";
        }

        return [
            'response' => implode("\n", $lines) . $followUp,
            'products' => array_map(fn($item) => [
                'id' => $item['id'],
                'name' => $item['name'],
                'image' => $item['image'],
                'price' => $item['price'],
                'in_stock' => $item['in_stock'],
                'category' => $item['category'],
            ], $items),
            'delay_ms' => 700,
        ];
    }

    /**
     * Construct a balanced build combination under a specific budget.
     */
    public function buildCombo(int $budgetLimit, string $productSelect): array
    {
        $results = [];
        $cats = ['cpu', 'gpu', 'motherboard', 'ram'];
        $allocations = [
            'gpu' => 0.45,
            'cpu' => 0.25,
            'motherboard' => 0.15,
            'ram' => 0.15
        ];
        
        foreach ($cats as $cat) {
            if ($budgetLimit > 0) {
                $catBudget = $budgetLimit * $allocations[$cat];
                $stmt = $this->db->prepare("SELECT {$productSelect} FROM products WHERE category LIKE ? AND price <= ? ORDER BY price DESC LIMIT 1");
                $stmt->execute(["%$cat%", $catBudget]);
            } else {
                $stmt = $this->db->prepare("SELECT {$productSelect} FROM products WHERE category LIKE ? ORDER BY rating DESC, reviews DESC LIMIT 1");
                $stmt->execute(["%$cat%"]);
            }
            $row = $stmt->fetch();
            if ($row) {
                $results[] = $row;
            }
        }
        return $results;
    }

    /**
     * Apply Min/Max price filters to SQL statement structure.
     */
    public function applyPriceFilter(string $sql, array &$params, array $priceFilter): string
    {
        if (($priceFilter['min'] ?? 0) > 0) {
            $sql .= ' AND price >= :price_min';
            $params[':price_min'] = (int) $priceFilter['min'];
        }
        if (($priceFilter['max'] ?? 0) > 0) {
            $sql .= ' AND price <= :price_max';
            $params[':price_max'] = (int) $priceFilter['max'];
        }
        return $sql;
    }

    /**
     * Execute natural language fulltext search.
     */
    public function fulltextSearch(string $normalized, ?int $budgetLimit, array $priceFilter, string $productSelect): array
    {
        try {
            $q = $normalized;
            $params = [':q' => $q];

            if (($priceFilter['min'] ?? 0) > 0 || ($priceFilter['max'] ?? 0) > 0) {
                $sql = "
                    SELECT {$productSelect}, MATCH(name, brand, category) AGAINST(:q IN NATURAL LANGUAGE MODE) AS score
                    FROM products
                    WHERE MATCH(name, brand, category) AGAINST(:q IN NATURAL LANGUAGE MODE)
                ";
                $sql = $this->applyPriceFilter($sql, $params, $priceFilter);
                $sql .= " ORDER BY score DESC LIMIT 4";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            } elseif ($budgetLimit > 0) {
                $stmt = $this->db->prepare("
                    SELECT {$productSelect}, MATCH(name, brand, category) AGAINST(:q IN NATURAL LANGUAGE MODE) AS score
                    FROM products
                    WHERE MATCH(name, brand, category) AGAINST(:q IN NATURAL LANGUAGE MODE) AND price <= :budget
                    ORDER BY score DESC
                    LIMIT 4
                ");
                $stmt->execute([':q' => $q, ':budget' => $budgetLimit]);
            } else {
                $stmt = $this->db->prepare("
                    SELECT {$productSelect}, MATCH(name, brand, category) AGAINST(:q IN NATURAL LANGUAGE MODE) AS score
                    FROM products
                    WHERE MATCH(name, brand, category) AGAINST(:q IN NATURAL LANGUAGE MODE)
                    ORDER BY score DESC
                    LIMIT 4
                ");
                $stmt->execute([':q' => $q]);
            }
            return $stmt->fetchAll() ?: [];
        } catch (\Exception $e) {
            return []; // Fulltext not supported/broken, fallback to next pipeline segment
        }
    }

    /**
     * Search specific product categories matching keywords.
     */
    public function categorySearch(
        string $normalized,
        array $tokens,
        bool $isBudget,
        ?int $budgetLimit,
        array $priceFilter,
        string $productSelect
    ): array {
        $categories = [
            'gpu'     => ['gpu', 'graphics', 'graphic', 'rtx', 'gtx', 'radeon', 'rx', 'nvidia', 'amd', 'video card', 'videocard'],
            'cpu'     => ['cpu', 'processor', 'intel', 'ryzen', 'core i', 'i3', 'i5', 'i7', 'i9'],
            'ram'     => ['ram', 'memory', 'ddr4', 'ddr5', 'dimm'],
            'storage' => ['ssd', 'hdd', 'storage', 'nvme', 'm2', 'm.2', 'hard drive', 'solid state'],
            'laptop'  => ['laptop', 'laptops', 'notebook', 'notebooks', 'ultrabook', 'ultrabooks', 'macbook', 'pavilion', 'zenbook', 'rog strix', 'thinkpad'],
        ];

        foreach ($categories as $key => $words) {
            $matchedPhrase = false;
            foreach ($words as $phrase) {
                if (strpos($normalized, $phrase) !== false || in_array($phrase, $tokens, true)) {
                    $matchedPhrase = true;
                    break;
                }
            }

            if ($matchedPhrase) {
                $orderBy = $isBudget ? 'price ASC' : 'rating DESC';
                
                if ($key === 'laptop') {
                    $laptopSelect = "id, name, brand, 'laptop' AS category, price, old_price, NULL AS badge, 4.8 AS rating, 15 AS reviews, image, in_stock, stock_quantity, specs";
                    $orderBy = $isBudget ? 'price ASC' : 'price DESC';
                    
                    if (($priceFilter['min'] ?? 0) > 0 || ($priceFilter['max'] ?? 0) > 0) {
                        $params = [];
                        $sql = "SELECT {$laptopSelect} FROM laptops WHERE 1=1";
                        $sql = $this->applyPriceFilter($sql, $params, $priceFilter);
                        $sql .= " ORDER BY {$orderBy} LIMIT 4";
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute($params);
                    } elseif ($budgetLimit > 0) {
                        $stmt = $this->db->prepare("SELECT {$laptopSelect} FROM laptops WHERE price <= ? ORDER BY $orderBy LIMIT 4");
                        $stmt->execute([$budgetLimit]);
                    } else {
                        $stmt = $this->db->prepare("SELECT {$laptopSelect} FROM laptops ORDER BY $orderBy LIMIT 4");
                        $stmt->execute();
                    }
                } else {
                    if (($priceFilter['min'] ?? 0) > 0 || ($priceFilter['max'] ?? 0) > 0) {
                        $params = [':category' => "%$key%"];
                        $sql = "SELECT {$productSelect} FROM products WHERE category LIKE :category";
                        $sql = $this->applyPriceFilter($sql, $params, $priceFilter);
                        $sql .= " ORDER BY {$orderBy} LIMIT 4";
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute($params);
                    } elseif ($budgetLimit > 0) {
                        $stmt = $this->db->prepare("SELECT {$productSelect} FROM products WHERE category LIKE ? AND price <= ? ORDER BY $orderBy LIMIT 4");
                        $stmt->execute(["%$key%", $budgetLimit]);
                    } else {
                        $stmt = $this->db->prepare("SELECT {$productSelect} FROM products WHERE category LIKE ? ORDER BY $orderBy LIMIT 4");
                        $stmt->execute(["%$key%"]);
                    }
                }
                $results = $stmt->fetchAll() ?: [];
                
                // If query is filter-based and returned nothing, fetch closest products in category
                if ($results === [] && (($priceFilter['min'] ?? 0) > 0 || ($priceFilter['max'] ?? 0) > 0)) {
                    if ($key === 'laptop') {
                        $laptopSelect = "id, name, brand, 'laptop' AS category, price, old_price, NULL AS badge, 4.8 AS rating, 15 AS reviews, image, in_stock, stock_quantity, specs";
                        $stmt = $this->db->prepare("SELECT {$laptopSelect} FROM laptops ORDER BY price ASC LIMIT 3");
                        $stmt->execute();
                    } else {
                        $stmt = $this->db->prepare("SELECT {$productSelect} FROM products WHERE category LIKE ? ORDER BY price ASC LIMIT 3");
                        $stmt->execute(["%$key%"]);
                    }
                    $closest = $stmt->fetchAll() ?: [];
                    return [
                        'type' => 'closest_fallback',
                        'category' => $key,
                        'data' => $closest
                    ];
                }

                return [
                    'type' => 'success',
                    'data' => $results
                ];
            }
        }
        return [];
    }

    /**
     * Search budget discounted products.
     */
    public function discountSearch(array $priceFilter, string $productSelect): array
    {
        $params = [];
        $sql = "SELECT {$productSelect} FROM products WHERE old_price > price";
        $sql = $this->applyPriceFilter($sql, $params, $priceFilter);
        $sql .= ' ORDER BY (old_price - price) DESC LIMIT 4';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Loose wildcard LIKE matching.
     */
    public function looseLikeSearch(array $tokens, array $priceFilter, string $productSelect): array
    {
        $words = array_filter($tokens, fn($t) => strlen($t) > 2);
        $clauses = [];
        $params = [];
        foreach ($words as $w) {
            $clauses[] = "name LIKE ? OR brand LIKE ? OR category LIKE ?";
            $params[]  = "%$w%";
            $params[]  = "%$w%";
            $params[]  = "%$w%";
        }

        $results = [];
        if ($clauses !== []) {
            $sql = "SELECT {$productSelect} FROM products WHERE (" . implode(' OR ', $clauses) . ")";
            if (($priceFilter['min'] ?? 0) > 0) {
                $sql .= ' AND price >= ?';
                $params[] = (int) $priceFilter['min'];
            }
            if (($priceFilter['max'] ?? 0) > 0) {
                $sql .= ' AND price <= ?';
                $params[] = (int) $priceFilter['max'];
            }
            $sql .= ' LIMIT 4';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll() ?: [];

            // Query laptops
            $laptopSelect = "id, name, brand, 'laptop' AS category, price, old_price, NULL AS badge, 4.8 AS rating, 15 AS reviews, image, in_stock, stock_quantity, specs";
            $laptopClauses = [];
            $laptopParams = [];
            foreach ($words as $w) {
                $laptopClauses[] = "name LIKE ? OR brand LIKE ?";
                $laptopParams[]  = "%$w%";
                $laptopParams[]  = "%$w%";
            }
            $sqlLaptops = "SELECT {$laptopSelect} FROM laptops WHERE (" . implode(' OR ', $laptopClauses) . ")";
            if (($priceFilter['min'] ?? 0) > 0) {
                $sqlLaptops .= ' AND price >= ?';
                $laptopParams[] = (int) $priceFilter['min'];
            }
            if (($priceFilter['max'] ?? 0) > 0) {
                $sqlLaptops .= ' AND price <= ?';
                $laptopParams[] = (int) $priceFilter['max'];
            }
            $sqlLaptops .= ' LIMIT 4';
            $stmtLaptops = $this->db->prepare($sqlLaptops);
            $stmtLaptops->execute($laptopParams);
            $laptopResults = $stmtLaptops->fetchAll() ?: [];

            $results = array_merge($results, $laptopResults);
        }
        return $results;
    }

    /**
     * Final fallback sorting by highest rating.
     */
    public function bestRatedSearch(array $priceFilter, string $productSelect): array
    {
        $params = [];
        $sql = "SELECT {$productSelect} FROM products WHERE 1=1";
        $sql = $this->applyPriceFilter($sql, $params, $priceFilter);
        $sql .= ' ORDER BY rating DESC LIMIT 4';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }
}
