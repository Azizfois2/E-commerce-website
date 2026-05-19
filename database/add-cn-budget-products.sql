-- Adds conservative Chinese / ultra-budget hardware options for the PC builder.
-- Safe to run multiple times on MariaDB/MySQL.

INSERT INTO `products` (`id`, `name`, `brand`, `category`, `price`, `old_price`, `badge`, `rating`, `reviews`, `image`, `featured`, `in_stock`, `specs`, `created_at`, `stock_quantity`, `reorder_level`) VALUES
(31, 'Intel Xeon E5-2640 v4', 'Intel', 'cpu', 399.90, NULL, 'CN Value', 4.2, 184, 'images/products/placeholder-cpu.svg', 0, 1, '{"Cores":"10 / 20 threads","Boost Clock":"3.4 GHz","TDP":"90 W","Socket":"LGA 2011-3"}', NOW(), 6, 2),
(32, 'Intel Xeon E5-2680 v4', 'Intel', 'cpu', 599.90, NULL, 'CN Value', 4.3, 268, 'images/products/placeholder-cpu.svg', 0, 1, '{"Cores":"14 / 28 threads","Boost Clock":"3.3 GHz","TDP":"120 W","Socket":"LGA 2011-3"}', NOW(), 4, 2),
(33, 'HUANANZHI X99 4MF Plus DDR4', 'HUANANZHI', 'motherboard', 699.90, NULL, 'CN X99', 4.1, 142, 'images/products/placeholder-motherboard.svg', 0, 1, '{"Socket":"LGA 2011-3","Chipset":"X99 / C612-class","Memory":"DDR4 ECC / non-ECC","Form Factor":"Micro-ATX","M.2 Slots":"1"}', NOW(), 5, 2),
(34, 'Kllisre DDR4 ECC Registered 16 GB', 'Kllisre', 'ram', 299.90, NULL, 'CN Value', 4.0, 211, 'images/products/placeholder-ram.svg', 0, 1, '{"Capacity":"16 GB (1 x 16 GB)","Speed":"DDR4-2133 ECC REG","Latency":"Server JEDEC","Voltage":"1.2 V"}', NOW(), 12, 3),
(35, 'MAXSUN Radeon RX 550 4 GB', 'MAXSUN', 'gpu', 699.90, NULL, 'Ultra Low', 4.0, 176, 'images/products/placeholder-gpu.svg', 0, 1, '{"VRAM":"4 GB GDDR5","Core Clock":"1.18 GHz","TDP":"50 W","Outputs":"HDMI / DVI / DP"}', NOW(), 5, 2),
(36, 'MLLSE Radeon RX 580 2048SP 8 GB', 'MLLSE', 'gpu', 1299.90, NULL, '1080p Low', 4.1, 304, 'images/products/placeholder-gpu.svg', 0, 1, '{"VRAM":"8 GB GDDR5","Core Clock":"1.28 GHz","TDP":"150 W","Outputs":"HDMI / DP"}', NOW(), 5, 2),
(37, 'KingSpec P3 SATA SSD 512 GB', 'KingSpec', 'storage', 299.90, NULL, 'Ultra Low', 4.1, 386, 'images/products/placeholder-storage.svg', 0, 1, '{"Capacity":"512 GB","Interface":"SATA III 6 Gb/s","Seq. Read":"550 MB/s","Seq. Write":"500 MB/s"}', NOW(), 10, 3),
(38, 'Aigo GP550 500W 80+ Bronze', 'Aigo', 'psu', 449.90, NULL, 'Budget', 4.0, 126, 'images/products/placeholder-psu.svg', 0, 1, '{"Wattage":"500 W","Efficiency":"80+ Bronze","Modular":"No","Fan":"120 mm"}', NOW(), 6, 2),
(39, 'Snowman M-T4 120mm Tower Cooler', 'Snowman', 'cooling', 169.90, NULL, 'CN Value', 4.0, 198, 'images/products/placeholder-cooling.svg', 0, 1, '{"Type":"Air Tower","Fan Size":"120 mm","Max TDP":"150 W","Noise":"Budget PWM"}', NOW(), 8, 3)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `brand` = VALUES(`brand`),
  `category` = VALUES(`category`),
  `price` = VALUES(`price`),
  `old_price` = VALUES(`old_price`),
  `badge` = VALUES(`badge`),
  `rating` = VALUES(`rating`),
  `reviews` = VALUES(`reviews`),
  `image` = VALUES(`image`),
  `featured` = VALUES(`featured`),
  `in_stock` = VALUES(`in_stock`),
  `specs` = VALUES(`specs`),
  `stock_quantity` = VALUES(`stock_quantity`),
  `reorder_level` = VALUES(`reorder_level`);
