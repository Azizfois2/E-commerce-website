-- ============================================================
--  Maroc PC — Currency migration: USD → MAD (rate: 1 USD = 10 MAD)
--  Run this script ONCE against your XAMPP MySQL database.
--  Database: maroc_pc  (adjust if your DB name is different)
-- ============================================================

USE maroc_pc;

-- ── 1. products table ──────────────────────────────────────
--  Convert price and old_price columns × 10
UPDATE products
SET
    price     = ROUND(price     * 10, 2),
    old_price = CASE
                    WHEN old_price IS NOT NULL THEN ROUND(old_price * 10, 2)
                    ELSE NULL
                END;

-- ── 2. orders table ───────────────────────────────────────
--  Convert the stored order totals × 10
UPDATE orders
SET total = ROUND(total * 10, 2);

-- ── 3. order_items table ──────────────────────────────────
--  Convert the price_at_time snapshot × 10
UPDATE order_items
SET price_at_time = ROUND(price_at_time * 10, 2);

-- ── Verify (optional — uncomment to check) ─────────────────
-- SELECT id, name, price, old_price FROM products ORDER BY id;
-- SELECT id, total FROM orders ORDER BY id;
-- SELECT id, price_at_time FROM order_items ORDER BY id;

-- ============================================================
--  Done. All prices are now stored in Moroccan Dirham (MAD).
-- ============================================================
