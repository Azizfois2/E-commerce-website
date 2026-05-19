-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2026 at 02:50 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `maroc_pc`
--
CREATE DATABASE IF NOT EXISTS `maroc_pc` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `maroc_pc`;

-- --------------------------------------------------------

--
-- Table structure for table `admin_login_attempts`
--

CREATE TABLE `admin_login_attempts` (
  `attempt_key` varchar(64) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `failed_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_failed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_login_attempts`
--

INSERT INTO `admin_login_attempts` (`attempt_key`, `email`, `ip_address`, `failed_attempts`, `locked_until`, `last_failed_at`) VALUES
('d154c2deec3c222fe75e87135b4d85e47c53e9e67ef55765c75776dbdca170dc', 'yahya@mazouzi.com', '::1', 3, '2026-05-12 17:56:33', '2026-05-12 17:41:33');

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE `client` (
  `id_client` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `moyen_paiement` varchar(50) DEFAULT 'not_set',
  `adresse` text DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `loyalty_tier` enum('bronze','silver','gold','platinum') DEFAULT 'bronze',
  `total_points` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_verified` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `failed_login_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client`
--

INSERT INTO `client` (`id_client`, `nom`, `email`, `mot_de_passe`, `date_naissance`, `moyen_paiement`, `adresse`, `telephone`, `loyalty_tier`, `total_points`, `created_at`, `email_verified`, `deleted_at`, `google_id`, `failed_login_attempts`, `locked_until`, `two_factor_enabled`) VALUES
(1, 'test', 'test@example.com', 'xxx', NULL, 'not_set', NULL, NULL, 'bronze', 0, '2026-05-06 18:13:04', 0, NULL, NULL, 0, NULL, 0),
(2, 'Yahya Mazouzi', 'yahya@mazouzi.com', '$2y$10$TJ4LRsOlykuwTsWkfRmYCufxq3DZePvP6QLoL7EBiIcQojyusRaSa', '2026-04-27', 'not_set', 'Ain Al Makan', NULL, 'bronze', 0, '2026-05-06 19:37:50', 0, NULL, NULL, 0, NULL, 0),
(3, 'Salah Al Mansouri', 'sala7mansouri123@gmail.com', '$2y$10$5SoAQnv2IqFfRYYDB1cREOQJh0tvr3/IWJ9hRjcMP6OTL381XLyI6', '2000-04-04', 'not_set', 'Ain Al Makan', NULL, 'bronze', 0, '2026-05-11 19:11:45', 1, NULL, NULL, 2, NULL, 0),
(4, 'Abdelaziz Al Harbi', 'aa7836944@gmail.com', '$2y$10$.76EfEtcVFJ0sfanRVe3guHc4FXZYq52Sb2iicyobyQ.CZexO3EdG', NULL, 'not_set', NULL, NULL, 'silver', 1457, '2026-05-12 00:43:03', 1, NULL, '117409897041843401204', 0, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verifications`
--

INSERT INTO `email_verifications` (`id`, `email`, `token_hash`, `expires_at`, `used`, `created_at`) VALUES
(1, 'yahya@mazouzi.com', '19c7f5a0acd26448b74d5a77effdf600407603291f93d00333f54137974b4c7c', '2026-05-12 21:07:02', 0, '2026-05-11 19:07:02'),
(2, 'sala7mansouri123@gmail.com', 'd5b894dc9260575de2d81caca38717c4f80cbbc9108bea6e6fa366f4aab8ed2d', '2026-05-12 21:11:45', 1, '2026-05-11 19:11:45');

-- --------------------------------------------------------

--
-- Table structure for table `flash_sales`
--

CREATE TABLE `flash_sales` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `sale_price` decimal(10,2) NOT NULL,
  `original_price` decimal(10,2) NOT NULL,
  `max_quantity` int(11) DEFAULT NULL,
  `sold_count` int(11) DEFAULT 0,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flash_sales`
--

INSERT INTO `flash_sales` (`id`, `product_id`, `sale_price`, `original_price`, `max_quantity`, `sold_count`, `starts_at`, `ends_at`, `created_at`) VALUES
(1, 1, 11199.93, 15999.90, 15, 0, '2026-05-11 20:54:58', '2026-05-15 02:54:58', '2026-05-11 20:54:58'),
(2, 2, 6749.92, 8999.90, 8, 0, '2026-05-11 20:54:58', '2026-05-15 02:54:58', '2026-05-11 20:54:58'),
(3, 3, 6399.92, 7999.90, NULL, 0, '2026-05-11 20:54:58', '2026-05-16 20:54:58', '2026-05-11 20:54:58'),
(4, 4, 4674.92, 5499.90, 20, 0, '2026-05-11 20:54:58', '2026-05-15 02:54:58', '2026-05-11 20:54:58');

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_points`
--

CREATE TABLE `loyalty_points` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `source` enum('purchase','review','referral','bonus','redemption') NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loyalty_points`
--

INSERT INTO `loyalty_points` (`id`, `client_id`, `points`, `source`, `order_id`, `description`, `created_at`) VALUES
(1, 4, 1731, 'purchase', 10, 'Points earned from order #10', '2026-05-13 09:52:42'),
(2, 4, -1000, 'redemption', 11, 'Redeemed for order #11', '2026-05-13 10:01:13'),
(3, 4, 477, 'purchase', 11, 'Points earned from order #11', '2026-05-13 10:01:13'),
(4, 4, 249, 'purchase', 12, 'Points earned from order #12', '2026-05-13 10:19:09');

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `newsletter_subscribers`
--

INSERT INTO `newsletter_subscribers` (`id`, `email`, `subscribed_at`) VALUES
(1, 'yahya@mazouzi.com', '2026-05-06 21:44:40'),
(2, 'wikiuser9@gmail.com', '2026-05-06 22:37:16');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `status` enum('pending','processing','shipped','out_for_delivery','delivered','cancelled') DEFAULT 'pending',
  `total` decimal(10,2) NOT NULL,
  `shipping_method` varchar(50) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `stock_reserved` tinyint(1) NOT NULL DEFAULT 0,
  `transaction_id` varchar(64) DEFAULT NULL,
  `paypal_order_id` varchar(64) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `estimated_delivery` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `client_id`, `status`, `total`, `shipping_method`, `shipping_address`, `billing_address`, `payment_method`, `payment_status`, `stock_reserved`, `transaction_id`, `paypal_order_id`, `notes`, `created_at`, `updated_at`, `estimated_delivery`) VALUES
(1, 2, 'cancelled', 5953.60, 'standard', 'Yahya Mazouzi\nBoulevard Ain Makan\n7da l3issas\nCasaNegro,  533490\nMA', 'Yahya Mazouzi\nBoulevard Ain Makan\n7da l3issas\nCasaNegro,  533490\nMA', 'bitcoin', 'pending', 0, NULL, NULL, '', '2026-05-06 19:40:10', '2026-05-07 18:24:11', NULL),
(2, 2, 'cancelled', 2706.14, 'standard', 'Yahya Mazouzi\nBoulevard Ain Makan\n7da l3issas\nCasaNegro,  533490\nMA', 'Yahya Mazouzi\nBoulevard Ain Makan\n7da l3issas\nCasaNegro,  533490\nMA', 'bitcoin', 'pending', 0, NULL, NULL, '', '2026-05-07 17:08:16', '2026-05-07 18:24:07', NULL),
(3, 2, 'cancelled', 5953.64, 'standard', 'Yahya Mazouzi\nBoulevard Ain Makan\n7da l3issas\nCasaNegro,  533490\nMA', 'Yahya Mazouzi\nBoulevard Ain Makan\n7da l3issas\nCasaNegro,  533490\nMA', 'bitcoin', 'pending', 0, NULL, NULL, '', '2026-05-07 18:18:19', '2026-05-07 18:24:01', NULL),
(4, 3, 'pending', 9742.39, 'standard', 'Salah Mansouri\nBoulevard Ain Makan\n7da l3issas\nCasablanca, CS 533490\nMA', 'Salah Mansouri\nBoulevard Ain Makan\n7da l3issas\nCasablanca, CS 533490\nMA', 'bitcoin', 'pending', 0, NULL, NULL, '', '2026-05-11 23:16:24', '2026-05-11 23:16:24', '2026-05-17'),
(5, 4, 'pending', 17319.89, 'standard', 'Abdelaziz Al Harbi\nBoulevard Ain Makan\n7da l3issas\nCasablanca, CS 533490\nMA', 'Abdelaziz Al Harbi\nBoulevard Ain Makan\n7da l3issas\nCasablanca, CS 533490\nMA', 'credit-card', 'paid', 0, 'TXN-SVTQEPWUDEPC', NULL, '', '2026-05-13 09:43:47', '2026-05-13 09:43:47', '2026-05-18'),
(10, 4, 'cancelled', 17319.89, 'standard', 'Abdelaziz Al Harbi\nBoulevard Ain Makan\n7da l3issas\nCasablanca, CS 533490\nMA', 'Abdelaziz Al Harbi\nBoulevard Ain Makan\n7da l3issas\nCasablanca, CS 533490\nMA', 'credit-card', 'paid', 0, 'TXN-L6W0NW7L7YG9', NULL, '', '2026-05-13 09:52:42', '2026-05-13 09:57:28', '2026-05-18'),
(11, 4, 'pending', 4771.14, 'free', 'Abdelaziz Al Harbi\nBoulevard Ain Makan\n7da l3issas\nCasablanca, CS 533490\nMA', 'Abdelaziz Al Harbi\nBoulevard Ain Makan\n7da l3issas\nCasablanca, CS 533490\nMA', 'credit-card', 'paid', 0, 'TXN-L9S25ZYA7FTH', NULL, '', '2026-05-13 10:01:13', '2026-05-13 10:01:13', '2026-05-16'),
(12, 4, 'pending', 2086.64, 'standard', 'Abdelaziz Al Harbi\nBoulevard Ain Makan\nChi blassa\nCasablanca, CS 533490\nMA', 'Abdelaziz Al Harbi\nBoulevard Ain Makan\nChi blassa\nCasablanca, CS 533490\nMA', 'cod', 'pending', 0, 'COD-1778667549224', NULL, '', '2026-05-13 10:19:09', '2026-05-13 10:19:09', '2026-05-18');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_time` decimal(10,2) NOT NULL,
  `name_at_time` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price_at_time`, `name_at_time`) VALUES
(1, 1, 4, 1, 5499.90, 'Intel Core i9-14900K'),
(2, 2, 6, 1, 2499.90, 'AMD Ryzen 5 7700X'),
(3, 3, 4, 1, 5499.90, 'Intel Core i9-14900K'),
(4, 4, 2, 1, 8999.90, 'AMD Radeon RX 7900 XTX'),
(5, 5, 1, 1, 15999.90, 'NVIDIA RTX 4090 Founders Edition'),
(10, 10, 1, 1, 15999.90, 'NVIDIA RTX 4090 Founders Edition'),
(11, 11, 16, 1, 4499.90, 'AMD Ryzen 7 7800X3D'),
(12, 12, 7, 1, 1899.90, 'Corsair Dominator Platinum DDR5 32 GB');

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `changed_by` enum('system','admin','customer') DEFAULT 'system',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `old_status`, `new_status`, `changed_at`, `changed_by`, `notes`) VALUES
(1, 4, NULL, 'pending', '2026-05-11 23:16:24', 'system', NULL),
(2, 5, NULL, 'pending', '2026-05-13 09:43:47', 'system', NULL),
(7, 10, NULL, 'pending', '2026-05-13 09:52:42', 'system', NULL),
(8, 11, NULL, 'pending', '2026-05-13 10:01:13', 'system', NULL),
(9, 12, NULL, 'pending', '2026-05-13 10:19:09', 'system', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token_hash`, `expires_at`, `used`, `created_at`) VALUES
(2, 'yahya@mazouzi.com', '41bc91619d6010c54a34c8fd1629534979293107fd09f3f5c602eacff367a5aa', '2026-05-06 23:04:06', 0, '2026-05-06 20:04:06');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `old_price` decimal(10,2) DEFAULT NULL,
  `badge` varchar(50) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT NULL,
  `reviews` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `in_stock` tinyint(1) DEFAULT 1,
  `specs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specs`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(11) NOT NULL DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `brand`, `category`, `price`, `old_price`, `badge`, `rating`, `reviews`, `image`, `featured`, `in_stock`, `specs`, `created_at`, `stock_quantity`, `reorder_level`) VALUES
(1, 'NVIDIA RTX 4090 Founders Edition', 'NVIDIA', 'gpu', 15999.90, 17999.90, 'Hot', 4.9, 1284, 'images/products/rtx4090.png', 1, 1, '{\"VRAM\":\"24 GB GDDR6X\",\"Core Clock\":\"2.52 GHz\",\"TDP\":\"450 W\",\"Outputs\":\"3\\u00d7 DP 1.4 \\u00b7 1\\u00d7 HDMI 2.1\"}', '2026-05-06 17:34:52', 10, 5),
(2, 'AMD Radeon RX 7900 XTX', 'AMD', 'gpu', 8999.90, 9999.90, 'Sale', 4.7, 867, 'images/products/rx7900xtx.png', 1, 1, '{\"VRAM\":\"24 GB GDDR6\",\"Core Clock\":\"2.50 GHz\",\"TDP\":\"355 W\",\"Outputs\":\"2\\u00d7 DP 2.1 \\u00b7 1\\u00d7 HDMI 2.1\"}', '2026-05-06 17:34:52', 10, 5),
(3, 'NVIDIA RTX 4070 Ti Super', 'NVIDIA', 'gpu', 7999.90, NULL, 'New', 4.8, 432, 'images/products/rtx4070ti.png', 0, 1, '{\"VRAM\":\"16 GB GDDR6X\",\"Core Clock\":\"2.61 GHz\",\"TDP\":\"285 W\"}', '2026-05-06 17:34:52', 10, 5),
(4, 'Intel Core i9-14900K', 'Intel', 'cpu', 5499.90, 5899.90, 'Sale', 4.8, 2031, 'images/products/i9-14900k.png', 1, 1, '{\"Cores\":\"24 (8P + 16E)\",\"Boost Clock\":\"6.0 GHz\",\"TDP\":\"125 W\",\"Socket\":\"LGA 1700\"}', '2026-05-06 17:34:52', 10, 5),
(5, 'AMD Ryzen 9 7950X', 'AMD', 'cpu', 5999.90, NULL, NULL, 4.9, 1543, 'images/products/ryzen9-7950x.png', 0, 1, '{\"Cores\":\"16\",\"Boost Clock\":\"5.7 GHz\",\"TDP\":\"170 W\",\"Socket\":\"AM5\"}', '2026-05-06 17:34:52', 10, 5),
(6, 'AMD Ryzen 5 7700X', 'AMD', 'cpu', 2499.90, NULL, 'Hot', 4.6, 3200, 'images/products/ryzen5-7600x.png', 1, 1, '{\"Cores\":\"6\",\"Boost Clock\":\"5.3 GHz\",\"TDP\":\"105 W\",\"Socket\":\"AM5\"}', '2026-05-06 17:34:52', 10, 5),
(7, 'Corsair Dominator Platinum DDR5 32 GB', 'Corsair', 'ram', 1899.90, 2199.90, 'Sale', 4.7, 654, 'images/products/corsair-ddr5.png', 1, 1, '{\"Capacity\":\"32 GB (2 \\u00d7 16 GB)\",\"Speed\":\"DDR5-6000\",\"Latency\":\"CL36\",\"Voltage\":\"1.35 V\"}', '2026-05-06 17:34:52', 10, 5),
(8, 'G.Skill Trident Z5 RGB 64 GB', 'G.Skill', 'ram', 2999.90, NULL, 'New', 4.8, 312, 'images/products/gskill-ddr5.png', 0, 1, '{\"Capacity\":\"64 GB (2 \\u00d7 32 GB)\",\"Speed\":\"DDR5-6400\",\"Latency\":\"CL32\"}', '2026-05-06 17:34:52', 10, 5),
(9, 'Samsung 990 Pro NVMe SSD 2 TB', 'Samsung', 'storage', 1699.90, 1999.90, 'Sale', 4.9, 4871, 'images/products/samsung-990pro.png', 1, 1, '{\"Capacity\":\"2 TB\",\"Interface\":\"PCIe 4.0 \\u00d7 4 NVMe\",\"Seq. Read\":\"7 450 MB\\/s\",\"Seq. Write\":\"6 900 MB\\/s\"}', '2026-05-06 17:34:52', 10, 5),
(10, 'WD Black SN850X 1 TB', 'Western Digital', 'storage', 1099.90, NULL, NULL, 4.8, 2190, 'images/products/wd-sn850x.png', 0, 1, '{\"Capacity\":\"1 TB\",\"Interface\":\"PCIe 4.0 \\u00d7 4 NVMe\",\"Seq. Read\":\"7 300 MB\\/s\"}', '2026-05-06 17:34:52', 10, 5),
(11, 'Noctua NH-D15 CPU Cooler', 'Noctua', 'cooling', 999.90, NULL, NULL, 4.9, 6540, 'images/products/noctua-nhd15.png', 0, 1, '{\"Type\":\"Air (Dual Tower)\",\"Fan Size\":\"140 mm \\u00d7 2\",\"Max TDP\":\"250 W+\",\"Noise\":\"24.6 dB(A)\"}', '2026-05-06 17:34:52', 10, 5),
(12, 'NZXT Kraken Elite 360 AIO', 'NZXT', 'cooling', 2799.90, 3199.90, 'Sale', 4.6, 821, 'images/products/nzxt-kraken360.png', 1, 0, '{\"Type\":\"Liquid (AIO)\",\"Radiator\":\"360 mm\",\"Fan\":\"3 \\u00d7 120 mm\",\"Display\":\"2.36\\\" LCD\"}', '2026-05-06 17:34:52', 0, 5),
(13, 'Corsair RM1000x 1000W 80+ Gold', 'Corsair', 'psu', 1799.90, NULL, NULL, 4.8, 1120, 'images/products/corsair-rm1000x.png', 0, 1, '{\"Wattage\":\"1 000 W\",\"Efficiency\":\"80+ Gold\",\"Modular\":\"Full\",\"Fan\":\"135 mm Zero RPM\"}', '2026-05-06 17:34:52', 10, 5),
(14, 'Seasonic Focus GX-850 850W', 'Seasonic', 'psu', 1399.90, 1599.90, 'Low Stock', 4.7, 2380, 'images/products/seasonic-gx850.png', 1, 1, '{\"Wattage\":\"850 W\",\"Efficiency\":\"80+ Gold\",\"Modular\":\"Full\"}', '2026-05-06 17:34:52', 10, 5),
(15, 'Intel Core i5-14600K', 'Intel', 'cpu', 3299.90, NULL, 'Value', 4.7, 1180, 'images/products/placeholder-cpu.svg', 0, 1, '{\"Cores\":\"14 (6P + 8E)\",\"Boost Clock\":\"5.3 GHz\",\"TDP\":\"125 W\",\"Socket\":\"LGA 1700\"}', '2026-05-12 16:54:03', 0, 5),
(16, 'AMD Ryzen 7 7800X3D', 'AMD', 'cpu', 4499.90, NULL, 'Gaming', 4.9, 2145, 'images/products/placeholder-cpu.svg', 1, 1, '{\"Cores\":\"8\",\"Boost Clock\":\"5.0 GHz\",\"TDP\":\"120 W\",\"Socket\":\"AM5\"}', '2026-05-12 16:54:03', 0, 5),
(17, 'NVIDIA RTX 4080 Super', 'NVIDIA', 'gpu', 11999.90, NULL, 'New', 4.8, 706, 'images/products/placeholder-gpu.svg', 1, 1, '{\"VRAM\":\"16 GB GDDR6X\",\"Core Clock\":\"2.55 GHz\",\"TDP\":\"320 W\",\"Outputs\":\"3x DP 1.4a - 1x HDMI 2.1\"}', '2026-05-12 16:54:03', 0, 5),
(18, 'AMD Radeon RX 7800 XT', 'AMD', 'gpu', 5799.90, NULL, 'Value', 4.6, 930, 'images/products/placeholder-gpu.svg', 0, 1, '{\"VRAM\":\"16 GB GDDR6\",\"Core Clock\":\"2.43 GHz\",\"TDP\":\"263 W\",\"Outputs\":\"3x DP 2.1 - 1x HDMI 2.1\"}', '2026-05-12 16:54:03', 0, 5),
(19, 'Kingston Fury Beast DDR5 32 GB', 'Kingston', 'ram', 1499.90, NULL, 'Value', 4.6, 812, 'images/products/placeholder-ram.svg', 0, 1, '{\"Capacity\":\"32 GB (2 x 16 GB)\",\"Speed\":\"DDR5-5600\",\"Latency\":\"CL36\",\"Voltage\":\"1.25 V\"}', '2026-05-12 16:54:03', 0, 5),
(20, 'Crucial Pro DDR4 32 GB', 'Crucial', 'ram', 899.90, NULL, 'Budget', 4.5, 640, 'images/products/placeholder-ram.svg', 0, 1, '{\"Capacity\":\"32 GB (2 x 16 GB)\",\"Speed\":\"DDR4-3200\",\"Latency\":\"CL22\",\"Voltage\":\"1.2 V\"}', '2026-05-12 16:54:03', 0, 5),
(21, 'Crucial T500 NVMe SSD 1 TB', 'Crucial', 'storage', 1099.90, NULL, 'New', 4.7, 528, 'images/products/placeholder-storage.svg', 0, 1, '{\"Capacity\":\"1 TB\",\"Interface\":\"PCIe 4.0 x4 NVMe\",\"Seq. Read\":\"7 300 MB\\/s\",\"Seq. Write\":\"6 800 MB\\/s\"}', '2026-05-12 16:54:03', 0, 5),
(22, 'be quiet! Dark Rock Pro 5', 'be quiet!', 'cooling', 1199.90, NULL, 'Silent', 4.8, 402, 'images/products/placeholder-cooling.svg', 0, 1, '{\"Type\":\"Air (Dual Tower)\",\"Fan Size\":\"120 mm + 135 mm\",\"Max TDP\":\"270 W\",\"Noise\":\"23.3 dB(A)\"}', '2026-05-12 16:54:03', 0, 5),
(23, 'Cooler Master MWE Gold 750 V2', 'Cooler Master', 'psu', 999.90, NULL, 'Budget', 4.5, 785, 'images/products/placeholder-psu.svg', 0, 1, '{\"Wattage\":\"750 W\",\"Efficiency\":\"80+ Gold\",\"Modular\":\"Semi\",\"Fan\":\"120 mm\"}', '2026-05-12 16:54:03', 0, 5),
(24, 'Corsair RM750e 750W 80+ Gold', 'Corsair', 'psu', 1199.90, NULL, NULL, 4.6, 956, 'images/products/placeholder-psu.svg', 0, 1, '{\"Wattage\":\"750 W\",\"Efficiency\":\"80+ Gold\",\"Modular\":\"Full\",\"Fan\":\"120 mm Zero RPM\"}', '2026-05-12 16:54:03', 0, 5),
(25, 'ASUS ROG Strix B650E-F Gaming WiFi', 'ASUS', 'motherboard', 2899.90, NULL, 'New', 4.8, 364, 'images/products/placeholder-motherboard.svg', 1, 1, '{\"Socket\":\"AM5\",\"Chipset\":\"B650E\",\"Memory\":\"DDR5\",\"Form Factor\":\"ATX\",\"M.2 Slots\":\"3\"}', '2026-05-13 14:10:00', 10, 5),
(26, 'MSI MAG B650 Tomahawk WiFi', 'MSI', 'motherboard', 2399.90, NULL, 'Value', 4.7, 512, 'images/products/placeholder-motherboard.svg', 0, 1, '{\"Socket\":\"AM5\",\"Chipset\":\"B650\",\"Memory\":\"DDR5\",\"Form Factor\":\"ATX\",\"M.2 Slots\":\"3\"}', '2026-05-13 14:10:00', 10, 5),
(27, 'Gigabyte Z790 AORUS Elite AX', 'Gigabyte', 'motherboard', 2799.90, NULL, 'Gaming', 4.8, 438, 'images/products/placeholder-motherboard.svg', 1, 1, '{\"Socket\":\"LGA 1700\",\"Chipset\":\"Z790\",\"Memory\":\"DDR5\",\"Form Factor\":\"ATX\",\"M.2 Slots\":\"4\"}', '2026-05-13 14:10:00', 10, 5),
(28, 'MSI MAG B760 Tomahawk WiFi DDR4', 'MSI', 'motherboard', 1999.90, NULL, 'Budget', 4.6, 286, 'images/products/placeholder-motherboard.svg', 0, 1, '{\"Socket\":\"LGA 1700\",\"Chipset\":\"B760\",\"Memory\":\"DDR4\",\"Form Factor\":\"ATX\",\"M.2 Slots\":\"3\"}', '2026-05-13 14:10:00', 10, 5),
(29, 'ASUS TUF Gaming B650-Plus WiFi', 'ASUS', 'motherboard', 2199.90, NULL, NULL, 4.7, 341, 'images/products/placeholder-motherboard.svg', 0, 1, '{\"Socket\":\"AM5\",\"Chipset\":\"B650\",\"Memory\":\"DDR5\",\"Form Factor\":\"ATX\",\"M.2 Slots\":\"3\"}', '2026-05-13 14:10:00', 10, 5),
(30, 'Gigabyte B760M DS3H DDR4', 'Gigabyte', 'motherboard', 1299.90, NULL, 'Budget', 4.5, 219, 'images/products/placeholder-motherboard.svg', 0, 1, '{\"Socket\":\"LGA 1700\",\"Chipset\":\"B760\",\"Memory\":\"DDR4\",\"Form Factor\":\"Micro-ATX\",\"M.2 Slots\":\"2\"}', '2026-05-13 14:10:00', 10, 5);

INSERT INTO `products` (`id`, `name`, `brand`, `category`, `price`, `old_price`, `badge`, `rating`, `reviews`, `image`, `featured`, `in_stock`, `specs`, `created_at`, `stock_quantity`, `reorder_level`) VALUES
(31, 'Intel Xeon E5-2640 v4', 'Intel', 'cpu', 399.90, NULL, 'CN Value', 4.2, 184, 'images/products/placeholder-cpu.svg', 0, 1, '{\"Cores\":\"10 / 20 threads\",\"Boost Clock\":\"3.4 GHz\",\"TDP\":\"90 W\",\"Socket\":\"LGA 2011-3\"}', '2026-05-15 00:00:00', 6, 2),
(32, 'Intel Xeon E5-2680 v4', 'Intel', 'cpu', 599.90, NULL, 'CN Value', 4.3, 268, 'images/products/placeholder-cpu.svg', 0, 1, '{\"Cores\":\"14 / 28 threads\",\"Boost Clock\":\"3.3 GHz\",\"TDP\":\"120 W\",\"Socket\":\"LGA 2011-3\"}', '2026-05-15 00:00:00', 4, 2),
(33, 'HUANANZHI X99 4MF Plus DDR4', 'HUANANZHI', 'motherboard', 699.90, NULL, 'CN X99', 4.1, 142, 'images/products/placeholder-motherboard.svg', 0, 1, '{\"Socket\":\"LGA 2011-3\",\"Chipset\":\"X99 / C612-class\",\"Memory\":\"DDR4 ECC / non-ECC\",\"Form Factor\":\"Micro-ATX\",\"M.2 Slots\":\"1\"}', '2026-05-15 00:00:00', 5, 2),
(34, 'Kllisre DDR4 ECC Registered 16 GB', 'Kllisre', 'ram', 299.90, NULL, 'CN Value', 4.0, 211, 'images/products/placeholder-ram.svg', 0, 1, '{\"Capacity\":\"16 GB (1 x 16 GB)\",\"Speed\":\"DDR4-2133 ECC REG\",\"Latency\":\"Server JEDEC\",\"Voltage\":\"1.2 V\"}', '2026-05-15 00:00:00', 12, 3),
(35, 'MAXSUN Radeon RX 550 4 GB', 'MAXSUN', 'gpu', 699.90, NULL, 'Ultra Low', 4.0, 176, 'images/products/placeholder-gpu.svg', 0, 1, '{\"VRAM\":\"4 GB GDDR5\",\"Core Clock\":\"1.18 GHz\",\"TDP\":\"50 W\",\"Outputs\":\"HDMI / DVI / DP\"}', '2026-05-15 00:00:00', 5, 2),
(36, 'MLLSE Radeon RX 580 2048SP 8 GB', 'MLLSE', 'gpu', 1299.90, NULL, '1080p Low', 4.1, 304, 'images/products/placeholder-gpu.svg', 0, 1, '{\"VRAM\":\"8 GB GDDR5\",\"Core Clock\":\"1.28 GHz\",\"TDP\":\"150 W\",\"Outputs\":\"HDMI / DP\"}', '2026-05-15 00:00:00', 5, 2),
(37, 'KingSpec P3 SATA SSD 512 GB', 'KingSpec', 'storage', 299.90, NULL, 'Ultra Low', 4.1, 386, 'images/products/placeholder-storage.svg', 0, 1, '{\"Capacity\":\"512 GB\",\"Interface\":\"SATA III 6 Gb/s\",\"Seq. Read\":\"550 MB/s\",\"Seq. Write\":\"500 MB/s\"}', '2026-05-15 00:00:00', 10, 3),
(38, 'Aigo GP550 500W 80+ Bronze', 'Aigo', 'psu', 449.90, NULL, 'Budget', 4.0, 126, 'images/products/placeholder-psu.svg', 0, 1, '{\"Wattage\":\"500 W\",\"Efficiency\":\"80+ Bronze\",\"Modular\":\"No\",\"Fan\":\"120 mm\"}', '2026-05-15 00:00:00', 6, 2),
(39, 'Snowman M-T4 120mm Tower Cooler', 'Snowman', 'cooling', 169.90, NULL, 'CN Value', 4.0, 198, 'images/products/placeholder-cooling.svg', 0, 1, '{\"Type\":\"Air Tower\",\"Fan Size\":\"120 mm\",\"Max TDP\":\"150 W\",\"Noise\":\"Budget PWM\"}', '2026-05-15 00:00:00', 8, 3);

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `review_text` text DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `is_verified_purchase` tinyint(1) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `helpful_count` int(11) DEFAULT 0,
  `unhelpful_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`id`, `product_id`, `client_id`, `rating`, `review_text`, `photo_url`, `is_verified_purchase`, `status`, `helpful_count`, `unhelpful_count`, `created_at`) VALUES
(1, 1, 3, 5, 'Produit Nadi!', NULL, 0, 'approved', 0, 0, '2026-05-11 21:30:53');

-- --------------------------------------------------------

--
-- Table structure for table `restock_notifications`
--

CREATE TABLE `restock_notifications` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `notified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_votes`
--

CREATE TABLE `review_votes` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `vote` enum('helpful','unhelpful') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_builds`
--

CREATE TABLE `saved_builds` (
  `id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `share_code` varchar(12) NOT NULL,
  `build_name` varchar(100) DEFAULT NULL,
  `use_case` enum('gaming','streaming','editing','office','general') DEFAULT 'general',
  `components` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`components`)),
  `total_price` decimal(10,2) DEFAULT 0.00,
  `total_wattage` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `client_id`, `product_id`, `added_at`) VALUES
(45, 3, 3, '2026-05-11 21:53:20'),
(55, 3, 1, '2026-05-11 21:59:51'),
(86, 4, 1, '2026-05-12 00:43:36'),
(87, 4, 3, '2026-05-12 00:43:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_login_attempts`
--
ALTER TABLE `admin_login_attempts`
  ADD PRIMARY KEY (`attempt_key`),
  ADD KEY `idx_locked_until` (`locked_until`);

--
-- Indexes for table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`id_client`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token` (`token_hash`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `flash_sales`
--
ALTER TABLE `flash_sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `loyalty_points`
--
ALTER TABLE `loyalty_points`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `restock_notifications`
--
ALTER TABLE `restock_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_notify` (`product_id`,`email`);

--
-- Indexes for table `review_votes`
--
ALTER TABLE `review_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vote` (`review_id`,`client_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `saved_builds`
--
ALTER TABLE `saved_builds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `share_code` (`share_code`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wish` (`client_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `client`
--
ALTER TABLE `client`
  MODIFY `id_client` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `flash_sales`
--
ALTER TABLE `flash_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `loyalty_points`
--
ALTER TABLE `loyalty_points`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `restock_notifications`
--
ALTER TABLE `restock_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review_votes`
--
ALTER TABLE `review_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `saved_builds`
--
ALTER TABLE `saved_builds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=190;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `flash_sales`
--
ALTER TABLE `flash_sales`
  ADD CONSTRAINT `flash_sales_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loyalty_points`
--
ALTER TABLE `loyalty_points`
  ADD CONSTRAINT `loyalty_points_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `client` (`id_client`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `client` (`id_client`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `client` (`id_client`) ON DELETE CASCADE;

--
-- Constraints for table `restock_notifications`
--
ALTER TABLE `restock_notifications`
  ADD CONSTRAINT `restock_notifications_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_votes`
--
ALTER TABLE `review_votes`
  ADD CONSTRAINT `review_votes_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `product_reviews` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_votes_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `client` (`id_client`) ON DELETE CASCADE;

--
-- Constraints for table `saved_builds`
--
ALTER TABLE `saved_builds`
  ADD CONSTRAINT `saved_builds_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `client` (`id_client`) ON DELETE SET NULL;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `client` (`id_client`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
