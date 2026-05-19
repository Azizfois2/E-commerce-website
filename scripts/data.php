<?php
require_once dirname(__DIR__) . '/bootstrap.php';

// ── Step 1: Read data.js and extract the array ────────────────
function parseDataJs(string $filePath): array {
    if (!file_exists($filePath)) {
        throw new RuntimeException("File not found: $filePath");
    }

    $js = file_get_contents($filePath);

    // Remove single-line comments  ( // ... )
    $js = preg_replace('/\/\/[^\n]*/', '', $js);

    // Remove multi-line comments   ( /* ... */ )
    $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);

    // Extract everything between the first [ and the last ]
    $start = strpos($js, '[');
    $end   = strrpos($js, ']');

    if ($start === false || $end === false) {
        throw new RuntimeException("Could not find a JS array in the file.");
    }

    $jsonLike = substr($js, $start, $end - $start + 1);

    // Fix JS quirks that break JSON:
    // 1. Remove trailing commas before } or ]
    $jsonLike = preg_replace('/,\s*([\]}])/', '$1', $jsonLike);

    // 2. Wrap unquoted keys in double quotes  e.g.  name: → "name":
    $jsonLike = preg_replace('/([{,]\s*)([a-zA-Z_][a-zA-Z0-9_ ]*)(\s*:)/', '$1"$2"$3', $jsonLike);

    // 3. Replace single quotes with double quotes
    $jsonLike = preg_replace("/'/", '"', $jsonLike);

    $data = json_decode($jsonLike, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException("JSON decode failed: " . json_last_error_msg());
    }

    return $data;
}

// ── Step 2: Load products straight from data.js ───────────────
$products = parseDataJs(dirname(__DIR__) . '/assets/js/data.js');

// ── Step 3: Create table ──────────────────────────────────────
$pdo = db();

$pdo->exec("
  CREATE TABLE IF NOT EXISTS products (
    id         INT           PRIMARY KEY,
    name       VARCHAR(255)  NOT NULL,
    brand      VARCHAR(100)  NOT NULL,
    category   VARCHAR(50)   NOT NULL,
    price      DECIMAL(10,2) NOT NULL,
    old_price  DECIMAL(10,2) DEFAULT NULL,
    badge      VARCHAR(50)   DEFAULT NULL,
    rating     DECIMAL(2,1)  DEFAULT NULL,
    reviews    INT           DEFAULT 0,
    image      VARCHAR(255)  DEFAULT NULL,
    featured   TINYINT(1)    DEFAULT 0,
    in_stock   TINYINT(1)    DEFAULT 1,
    specs      JSON          DEFAULT NULL,
    created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ── Step 4: ACID import ───────────────────────────────────────
$sql = "
  INSERT INTO products
    (id, name, brand, category, price, old_price, badge,
     rating, reviews, image, featured, in_stock, specs)
  VALUES
    (:id, :name, :brand, :category, :price, :old_price, :badge,
     :rating, :reviews, :image, :featured, :in_stock, :specs)
  ON DUPLICATE KEY UPDATE
    name       = VALUES(name),
    brand      = VALUES(brand),
    category   = VALUES(category),
    price      = VALUES(price),
    old_price  = VALUES(old_price),
    badge      = VALUES(badge),
    rating     = VALUES(rating),
    reviews    = VALUES(reviews),
    image      = VALUES(image),
    featured   = VALUES(featured),
    in_stock   = VALUES(in_stock),
    specs      = VALUES(specs)
";

try {
    $pdo->beginTransaction();                        // 🔒 START

    $stmt = $pdo->prepare($sql);

    foreach ($products as $p) {
        $stmt->execute([
            ':id'        => $p['id'],
            ':name'      => $p['name'],
            ':brand'     => $p['brand'],
            ':category'  => $p['category'],
            ':price'     => $p['price'],
            ':old_price' => $p['oldPrice']  ?? null,
            ':badge'     => $p['badge']     ?? null,
            ':rating'    => $p['rating']    ?? null,
            ':reviews'   => $p['reviews']   ?? 0,
            ':image'     => $p['image']     ?? null,
            ':featured'  => $p['featured']  ? 1 : 0,
            ':in_stock'  => $p['inStock']   ? 1 : 0,
            ':specs'     => json_encode($p['specs'] ?? []),
        ]);
    }

    $pdo->commit();                                  // ✅ COMMIT
    echo "✔ Imported " . count($products) . " products from data.js\n";

} catch (Exception $e) {
    $pdo->rollBack();                                // ❌ ROLLBACK
    echo "✘ Failed: " . $e->getMessage() . "\n";
    exit(1);
}