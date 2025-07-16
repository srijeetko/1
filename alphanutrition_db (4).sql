-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 12, 2025 at 08:40 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `alphanutrition_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `address_id` char(36) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `type` enum('shipping','billing') DEFAULT NULL,
  `line1` text NOT NULL,
  `line2` text,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `admin_id` char(36) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` text NOT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `login_attempts` int DEFAULT '0',
  `last_login_attempt` timestamp NULL DEFAULT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`admin_id`, `name`, `email`, `password_hash`, `password_changed_at`, `login_attempts`, `last_login_attempt`, `role`, `created_at`) VALUES
('626a81c1-589a-11f0-8cbc-f439091252f6', 'Admin', 'admin@example.com', 'abcd@1234', NULL, 0, NULL, 'admin', '2025-07-04 05:47:34');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` char(36) NOT NULL,
  `actor_type` varchar(50) DEFAULT NULL,
  `actor_id` char(36) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `target_table` varchar(100) DEFAULT NULL,
  `target_id` char(36) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `banner_images`
--

CREATE TABLE `banner_images` (
  `id` int NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `banner_images`
--

INSERT INTO `banner_images` (`id`, `image_path`, `title`, `status`, `display_order`, `created_at`) VALUES
(1, 'banner_68676bb1636a60.47577134.webp', '1', 'active', 1, '2025-07-04 05:50:41'),
(2, 'banner_68676bc3df9022.83725660.jpg', '2', 'active', 2, '2025-07-04 05:50:59'),
(5, 'banner_68677f4da44d61.58356605.jpg', 'tablet', 'active', 3, '2025-07-04 07:14:21');

-- --------------------------------------------------------

--
-- Table structure for table `best_sellers`
--

CREATE TABLE `best_sellers` (
  `product_id` char(36) NOT NULL,
  `sales_count` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `best_sellers`
--

INSERT INTO `best_sellers` (`product_id`, `sales_count`) VALUES
('594fd8a912b127f9187d4e03795097cd', 0);

-- --------------------------------------------------------

--
-- Table structure for table `blogs`
--

CREATE TABLE `blogs` (
  `blog_id` char(36) NOT NULL,
  `author_id` char(36) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `content` text,
  `image_url` text,
  `published_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_categories`
--

CREATE TABLE `blog_categories` (
  `category_id` char(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text,
  `color` varchar(7) DEFAULT '#333333',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `blog_categories`
--

INSERT INTO `blog_categories` (`category_id`, `name`, `slug`, `description`, `color`, `created_at`, `updated_at`) VALUES
('cat-fitness', 'Fitness', 'fitness', 'Fitness tips and workout guides', '#ff6b35', '2025-07-10 12:12:49', '2025-07-10 12:12:49'),
('cat-lifestyle', 'Healthy Lifestyle', 'healthy-lifestyle', 'Tips for maintaining a healthy lifestyle', '#007bff', '2025-07-10 12:12:49', '2025-07-10 12:12:49'),
('cat-nutrition', 'Nutrition', 'nutrition', 'Nutrition advice and supplement guides', '#28a745', '2025-07-10 12:12:49', '2025-07-10 12:12:49'),
('cat-supplements', 'Supplements', 'supplements', 'Information about various supplements', '#6f42c1', '2025-07-10 12:12:49', '2025-07-10 12:12:49');

-- --------------------------------------------------------

--
-- Table structure for table `blog_comments`
--

CREATE TABLE `blog_comments` (
  `comment_id` char(36) NOT NULL,
  `post_id` char(36) NOT NULL,
  `author_name` varchar(100) NOT NULL,
  `author_email` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `status` enum('pending','approved','spam') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE `blog_posts` (
  `post_id` char(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text,
  `content` longtext NOT NULL,
  `featured_image` varchar(500) DEFAULT NULL,
  `category_id` char(36) DEFAULT NULL,
  `author_id` char(36) DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text,
  `tags` text,
  `view_count` int DEFAULT '0',
  `is_featured` tinyint(1) DEFAULT '0',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `blog_posts`
--

INSERT INTO `blog_posts` (`post_id`, `title`, `slug`, `excerpt`, `content`, `featured_image`, `category_id`, `author_id`, `status`, `meta_title`, `meta_description`, `tags`, `view_count`, `is_featured`, `published_at`, `created_at`, `updated_at`) VALUES
('post-686fb72033e04', 'Trial One', 'trial-one', 'Please WORK!', '<h2><em><strong>iiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiidjhdfbisdbflisbfisbdlfibsdlfsdlkbnsdlijblisblisdbfgsdbisbglsbhgisdbngisiksdj</strong></em></h2>\r\n<p>&nbsp;</p>\r\n<p>&nbsp;</p>\r\n<p>&nbsp;</p>\r\n<p>bglisdjbfinfopagiAsbdbasldjbasudbalsbkanifliadb ibadiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiidjhdfbisdbflisbfisbdlfibsdlfsdlkbnsdlijblisblisdbfgsdbisbglsbhgisdbngisiksdjbglisd</p>\r\n<p><img style=\"float: right;\" src=\"../assets/blog/content/content-686fb150733a6-1752150352.jpg\" alt=\"\" width=\"359\" height=\"359\"></p>\r\n<p>jbfinfopagiAsbdbasldjbasudbalsbkanifliadb ibadiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiidjhdfbisdbflisbfisbdlfibsdlfsdlkbnsdlijblisblisdbfgsdbisbglsbhgisdbngisiksdjbglisdjbfinfopagiAsbdbasldjbasudbalsbkanifliadb ibadiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiidjhdfbisdbflisbfisbdlfibsdlfsdlkbnsdlijblisblisdbfgsdbisbglsbhgisdbngisiksdjbglisdjbfinfopagiAsbdbasldjbasudbalsbkanifliadb ibadiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiidjhdfbisdbflisbfisbdlfibsdlfsdlkbnsdlijblisblisdbfgsdbisbglsbhgisdbngisiksdjbglisdjbfinfopagiAsbdbasldjbasudbalsbkanifliadb ibadi</p>\r\n<p>&nbsp;</p>\r\n<p>&nbsp;</p>\r\n<p><img src=\"../assets/blog/content/content-686fb173c702c-1752150387.jpg\" alt=\"\" width=\"318\" height=\"318\"> ufiuabnhfpubhafubasdifbliufbsdifbsliufbhsdufbsdubhfsdufbisdubfiosdubfibusdf</p>', 'assets/blog/trial-one-1752151840.jpg', 'cat-fitness', '626a81c1-589a-11f0-8cbc-f439091252f6', 'published', '', '', '', 5, 0, '2025-07-10 12:50:40', '2025-07-10 12:50:40', '2025-07-10 13:37:14'),
('post-probiotic-guide', 'Probiotic Supplement: Everything You Need To Know', 'probiotic-supplement-complete-guide', 'Overview The human gut — a complex and fascinating ecosystem teeming with billions of microorganisms. These microscopic, friendly and not-so-friendly residents...', '<p>The human gut — a complex and fascinating ecosystem teeming with billions of microorganisms. These microscopic, friendly and not-so-friendly residents play a crucial role in our overall health and well-being.</p><h2>Understanding Probiotics</h2><p>Probiotics are live microorganisms that, when administered in adequate amounts, confer a health benefit on the host. They are often called \"good\" or \"friendly\" bacteria.</p><h2>Benefits of Probiotic Supplements</h2><ul><li>Improved digestive health</li><li>Enhanced immune function</li><li>Better nutrient absorption</li><li>Potential mood benefits</li></ul><h2>Choosing the Right Probiotic</h2><p>When selecting a probiotic supplement, consider factors such as strain diversity, CFU count, and storage requirements...</p>', 'assets/blog-lifestyle.jpg', 'cat-lifestyle', '626a81c1-589a-11f0-8cbc-f439091252f6', 'published', NULL, NULL, NULL, 0, 0, '2025-07-10 12:12:49', '2025-07-10 12:12:49', '2025-07-10 12:12:49'),
('post-taurine-benefits', 'Taurine Benefits And Side effects', 'taurine-benefits-side-effects', 'If you\'re an energy drink lover, chances are you\'ve encountered the word \"taurine\" for a line or two. Sometimes, the phrase \"With taurine\" is printed on the can or bottle...', '<p>If you\'re an energy drink lover, chances are you\'ve encountered the word \"taurine\" for a line or two. Sometimes, the phrase \"With taurine\" is printed on the can or bottle...</p><p>Taurine is a naturally occurring amino acid that plays crucial roles in various bodily functions. From supporting cardiovascular health to enhancing athletic performance, taurine offers numerous benefits when consumed appropriately.</p><h2>What is Taurine?</h2><p>Taurine is a semi-essential amino acid that your body produces naturally. It\'s found in high concentrations in the brain, heart, muscles, and other tissues.</p><h2>Benefits of Taurine</h2><ul><li>Supports heart health</li><li>May improve exercise performance</li><li>Supports brain function</li><li>May help with diabetes management</li></ul><h2>Potential Side Effects</h2><p>While taurine is generally safe for most people, some may experience mild side effects when consuming large amounts...</p>', 'assets/blog-fitness.jpg', 'cat-fitness', '626a81c1-589a-11f0-8cbc-f439091252f6', 'published', NULL, NULL, NULL, 2, 0, '2025-07-10 12:12:49', '2025-07-10 12:12:49', '2025-07-10 12:58:34'),
('post-whey-protein-2024', 'Best Affordable Whey Protein Powders For 2024', 'best-affordable-whey-protein-2024', 'Whey protein, a supplement that has been the subject of extensive global research, is notable for its high nutritional value and the wide range of health benefits it provides...', '<p>Whey protein, a supplement that has been the subject of extensive global research, is notable for its high nutritional value and the wide range of health benefits it provides.</p><h2>What Makes Whey Protein Special?</h2><p>Whey protein is a complete protein containing all nine essential amino acids. It\'s rapidly absorbed by the body, making it ideal for post-workout recovery.</p><h2>Top Affordable Options for 2024</h2><ol><li>Alpha Nutrition Premium Whey</li><li>Budget-friendly alternatives</li><li>Value for money considerations</li></ol><h2>How to Choose the Right Whey Protein</h2><p>Consider factors such as protein content per serving, flavor options, additional ingredients, and third-party testing...</p>', 'assets/blog-nutrition.jpg', 'cat-nutrition', '626a81c1-589a-11f0-8cbc-f439091252f6', 'published', NULL, NULL, NULL, 0, 0, '2025-07-10 12:12:49', '2025-07-10 12:12:49', '2025-07-10 12:12:49');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_item_id` char(36) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `product_id` char(36) DEFAULT NULL,
  `variant_id` char(36) DEFAULT NULL,
  `quantity` int NOT NULL,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `checkout_orders`
--

CREATE TABLE `checkout_orders` (
  `order_id` char(36) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `address_id` char(36) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `coupon_id` char(36) DEFAULT NULL,
  `shipping_method_id` char(36) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `collection_products`
--

CREATE TABLE `collection_products` (
  `collection_id` char(36) NOT NULL,
  `product_id` char(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `message_id` char(36) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `coupon_id` char(36) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percentage','fixed') DEFAULT NULL,
  `discount_value` decimal(10,2) DEFAULT NULL,
  `usage_limit` int DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `featured_collections`
--

CREATE TABLE `featured_collections` (
  `collection_id` char(36) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` char(36) NOT NULL,
  `order_id` char(36) DEFAULT NULL,
  `product_id` char(36) DEFAULT NULL,
  `variant_id` char(36) DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_gateway_logs`
--

CREATE TABLE `payment_gateway_logs` (
  `payment_id` char(36) NOT NULL,
  `order_id` char(36) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT NULL,
  `transaction_id` text,
  `gateway_name` varchar(100) DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `sr_no` int NOT NULL,
  `product_id` char(36) NOT NULL,
  `name` varchar(150) NOT NULL,
  `short_description` text,
  `short_description_image` varchar(255) DEFAULT NULL,
  `long_description` text,
  `long_description_image` varchar(255) DEFAULT NULL,
  `key_benefits` text,
  `key_benefits_image` varchar(255) DEFAULT NULL,
  `how_to_use` text,
  `how_to_use_images` text,
  `ingredients` text,
  `ingredients_image` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category_id` char(36) DEFAULT NULL,
  `stock_quantity` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `is_featured` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`sr_no`, `product_id`, `name`, `short_description`, `short_description_image`, `long_description`, `long_description_image`, `key_benefits`, `key_benefits_image`, `how_to_use`, `how_to_use_images`, `ingredients`, `ingredients_image`, `price`, `category_id`, `stock_quantity`, `is_active`, `is_featured`, `created_at`) VALUES
(1, '07240ca09e34ce3dc9500eb0f0feea7f', 'Pcos Balance ', 'azccasassssdfsddsdsd', 'assets/product_1fee8fef7a3115a5_1752214963.jpg', 'sdsdsdasdasddasdsdasdasd', 'assets/product_1fee8fef7a3115a5_1752214963.jpg', 'asddsdasasdsdasdsassdasdsdasdasdsdasd', 'assets/product_1fee8fef7a3115a5_1752214963.jpg', 'asdsdasdasdasdasdasdasdasdsaddds', '[]', 'dsdssdasdasdasdsdggdgdddfasfffv', 'assets/product_1fee8fef7a3115a5_1752214963.jpg', 1400.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 10, 1, 1, '2025-07-11 06:22:43'),
(2, '0b18a826111bd18731e5d42cc503a7ce', 'L- Arginine ', 'fffjcxyxdyfuuu yuyuuuuuxftfuftugg', 'assets/product_1badd63e157c889c_1752212572.jpg', 'gjfgfgjgjg gjgjfgjgj gjfgjgjgjfgjgjgjgjjj', 'assets/product_1badd63e157c889c_1752212572.jpg', 'gjfgjjgjfdytt hh hgjggfdfg', 'assets/product_1badd63e157c889c_1752212572.jpg', 'sdffndfgbcdf ', '[]', 'fgghhghghg', 'assets/product_1badd63e157c889c_1752212572.jpg', 920.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 10, 1, 1, '2025-07-11 05:42:52'),
(3, '0e26db87d6e1916374a91b8ca743eac7', 'Triple Stregth Fish Oil ', 'sdffasfsfsfsfsfsf', 'assets/product_7ae08ab089684755_1752214563.jpg', 'sfsfsfasfasfasfasfasfasfasfsf', 'assets/product_7ae08ab089684755_1752214563.jpg', 'asfasfasfasfsfasfasfas', 'assets/product_7ae08ab089684755_1752214563.jpg', 'ssfsfsffsff', '[]', 'asfsafsfsfasff', 'assets/product_7ae08ab089684755_1752214563.jpg', 1460.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 10, 1, 1, '2025-07-11 06:16:03'),
(4, '22e337976e401272877b7efc0c0b6aab', 'Multi Collagen Complex', 'dad dadadddaddsdfgjhkjl;kjjgghiyhnn ghm m bhbn', 'assets/product_698eb593e84c5095_1752213226.jpg', 'b bjhjjjgjjjgjg fggjfghjghjgjgjgjjhjj ', 'assets/product_698eb593e84c5095_1752213226.jpg', 'ghghghfghghghfghgfhgh ghghgh', 'assets/product_698eb593e84c5095_1752213226.jpg', 'hgfhgfhhghghfghg ghghhghhfghghhfghfghghgh', '[]', 'gfhghhfghgf hfgghghg ghnfgff', 'assets/product_698eb593e84c5095_1752213226.jpg', 920.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 16, 1, 1, '2025-07-11 05:53:46'),
(5, '2494ee2d98944a9f65015a9785c63f92', 'Spirulina', 'csfsaswet beggdgg', 'assets/product_1f390971deca9593_1752214396.jpg', 'gfhfhfrryhyhrrreyry', 'assets/product_1f390971deca9593_1752214396.jpg', 'hfhryrrrrryyry ryr', 'assets/product_1f390971deca9593_1752214396.jpg', 'reryttertrttrtrttrtre', '[]', 'tterrr fgdfgfdgdfgfgf fgfgdfgf', 'assets/product_1f390971deca9593_1752214396.jpg', 770.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 10, 1, 0, '2025-07-11 06:13:16'),
(6, '3e68d4edae4deec4f93e294c9a0104dd', 'Joint Support', 'sff sfsaff sfsfsfsafsfssfsffsfasfasffsdsfvsdfdgdfdgsdgdfdfdghgsdgdgds', 'assets/product_1e4feaafeec50dc9_1752213081.jpg', 'gdgsddsdg gdgddgg gdgdgdddd', 'assets/product_1e4feaafeec50dc9_1752213081.jpg', 'dddgdgsddgdgsgsdg', 'assets/product_1e4feaafeec50dc9_1752213081.jpg', 'dggds dgd gdgg dggg', '[]', 'ddgdgdgdgdggdgd', 'assets/product_1e4feaafeec50dc9_1752213081.jpg', 1075.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 20, 1, 0, '2025-07-11 05:51:21'),
(7, '502e7eb4e3859af1bad9ee52b0c885a8', 'Melatonin ', 'sdgsdgdsg dsdgdsgsdgd dg', 'assets/product_bae86abf1d7f7a41_1752213697.jpg', 'dgdgdg sdfgsdgsdgsdgsdgsd', 'assets/product_bae86abf1d7f7a41_1752213697.jpg', 'sdgdggsg dgsdgdgd dgdsdg', 'assets/product_bae86abf1d7f7a41_1752213697.jpg', 'sdgsdgsdgsdg dgsdggsdgdd', '[]', 'gsdgdgsdgdsdgsdgd', 'assets/product_bae86abf1d7f7a41_1752213697.jpg', 770.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 10, 1, 0, '2025-07-11 06:01:37'),
(8, '594fd8a912b127f9187d4e03795097cd', 'Hardcore-Mass-Gainer-1-Kg', 'Explosive energy, razor-sharp focus, and insane pumps – the ultimate formula to power your workout intensity', 'assets/product_e5a6f42e4a246867_1751888888.jpg', 'Black Preworkout is a hard-hitting energy amplifier designed for serious athletes and gym-goers who demand peak performance and total focus. Powered by potent stimulants, pump-enhancers, and cognitive boosters, it prepares your body and mind for the most intense training sessions.\r\n\r\nEach scoop delivers a synergistic combination of caffeine, beta-alanine, L-citrulline, and taurine to elevate endurance, amplify muscle pumps, and keep fatigue at bay. The formula also contains L-tyrosine and B-vitamins for mental clarity and mood elevation, ensuring that every set and rep is executed with laser-sharp precision.\r\n\r\nWhether you\'re lifting heavy, training for hypertrophy, or pushing through grueling cardio, Black Preworkout ensures you stay energized, focused, and driven. No crash, no jitters—just pure, clean power when you need it most.', 'assets/product_e5a6f42e4a246867_1751888888.jpg', '● Explosive energy and focus\r\n● Enhanced blood flow and pumps\r\n● Increases workout intensity and endurance\r\n● Mental alertness without energy crashes\r\n● Ideal for strength and resistance training', 'assets/product_e5a6f42e4a246867_1751888888.jpg', 'Mix 1 scoop (approx. 10g) with 250ml of cold water. Consume 20–30 minutes before your workout. Do not exceed 1 serving per day.', '[]', 'Caffeine Anhydrous\r\n● Beta-Alanine\r\n● L-Citrulline Malate\r\n● L-Arginine\r\n● Taurine\r\n● L-Tyrosine\r\n● Niacinamide (Vitamin B3)\r\n● Vitamin B6 & B12\r\n● Black pepper extract (Piperine)\r\n● Natural flavoring & sweeteners', 'assets/product_e5a6f42e4a246867_1751888888.jpg', 0.00, '67f85013-589a-11f0-8cbc-f439091252f6', 30, 1, 0, '2025-07-07 11:48:08'),
(9, '7025538976de53a9befc304843f98d09', 'Biotin ', 'sfsfsfsfasfsfsfsfsfs', 'assets/product_26d9572f51be49ed_1752214264.jpg', 'fsfsfasfasfsfsafffsf', 'assets/product_26d9572f51be49ed_1752214264.jpg', 'sfasfsfsfasfffsafasf', 'assets/product_26d9572f51be49ed_1752214264.jpg', 'fsfsafsfsfasffsasffsfasf', '[]', 'sfsfsfsffasfsffffs', 'assets/product_26d9572f51be49ed_1752214264.jpg', 845.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 10, 1, 0, '2025-07-11 06:11:04'),
(10, '719e4de8-589a-11f0-8cbc-f439091252f6', 'Black Powder Pre-Workout', 'Explosive energy, razor-sharp focus, and insane pumps –\r\nthe ultimate formula to power your workout intensity', 'assets/Black-Powder.jpg', 'Black Preworkout is a hard-hitting energy amplifier\r\ndesigned for serious athletes and gym-goers who demand\r\npeak performance and total focus. Powered by potent\r\nstimulants, pump-enhancers, and cognitive boosters, it\r\nprepares your body and mind for the most intense training\r\nsessions.\r\nEach scoop delivers a synergistic combination of caffeine,\r\nbeta-alanine, L-citrulline, and taurine to elevate endurance,\r\namplify muscle pumps, and keep fatigue at bay. The\r\nformula also contains L-tyrosine and B-vitamins for mental\r\nclarity and mood elevation, ensuring that every set and rep\r\nis executed with laser-sharp precision.\r\nWhether you’re lifting heavy, training for hypertrophy, or\r\npushing through grueling cardio, Black Preworkout ensures\r\nyou stay energized, focused, and driven. No crash, no\r\njitters—just pure, clean power when you need it most.\r\n', 'assets/Black-Powder.jpg', '● Explosive energy and focus\r\n● Enhanced blood flow and pumps\r\n● Increases workout intensity and endurance\r\n● Mental alertness without energy crashes\r\n● Ideal for strength and resistance training', 'assets/Black-Powder.jpg', 'Mix 1 scoop (approx. 10g) with 250ml of cold water.\r\nConsume 20–30 minutes before your workout. Do not\r\nexceed 1 serving per day.\r\n', '[]', 'Caffeine Anhydrous\r\n● Beta-Alanine\r\n● L-Citrulline Malate\r\n● L-Arginine\r\n● Taurine\r\n● L-Tyrosine\r\n● Niacinamide (Vitamin B3)\r\n● Vitamin B6 & B12\r\n● Black pepper extract (Piperine)\r\n● Natural flavoring & sweeteners\r\n', 'assets/Black-Powder.jpg', 749.00, '67f8540e-589a-11f0-8cbc-f439091252f6', 20, 1, 0, '2025-07-04 05:48:00'),
(11, '719e5341-589a-11f0-8cbc-f439091252f6', 'Cratein 100g', '', 'assets/Cratein-100g.jpg', '', 'assets/Cratein-100g.jpg', '', 'assets/Cratein-100g.jpg', '', '[\"assets\\/how-to-use\\/15f8dfb6c0ff1c9b_L - Arginine 1.jpg\",\"assets\\/how-to-use\\/c9f6faa8755e86ae_L - Arginine 2.jpg\",\"assets\\/how-to-use\\/aff5cea16bbc5de6_L - Arginine 3.jpg\",\"assets\\/how-to-use\\/23cf3fb245722d8d_L - Arginine 4.jpg\",\"assets\\/how-to-use\\/21dae1ef9db4b242_Meltaonin 1 - Copy.jpg\",\"assets\\/how-to-use\\/a4868fbacc8ef797_Meltaonin 1.jpg\",\"assets\\/how-to-use\\/ef22fb74931f442a_Meltaonin 2.jpg\",\"assets\\/how-to-use\\/61ca6d0bd6a31232_Meltaonin 3.jpg\"]', '', 'assets/Cratein-100g.jpg', 19.99, '7199a18d-589a-11f0-8cbc-f439091252f6', 0, 1, 0, '2025-07-04 05:48:00'),
(12, '719e54c0-589a-11f0-8cbc-f439091252f6', 'G-One Gainer 1Kg', NULL, 'assets/G-One-Gainer-1-Kg.jpg', NULL, 'assets/G-One-Gainer-1-Kg.jpg', NULL, 'assets/G-One-Gainer-1-Kg.jpg', NULL, NULL, NULL, 'assets/G-One-Gainer-1-Kg.jpg', 39.99, '7199a18d-589a-11f0-8cbc-f439091252f6', 100, 1, 0, '2025-07-04 05:48:00'),
(13, '719e5597-589a-11f0-8cbc-f439091252f6', 'G-One Gainer 3Kg', '', 'assets/G-One-Gainer-3-Kg.jpg', '', 'assets/G-One-Gainer-3-Kg.jpg', '', 'assets/G-One-Gainer-3-Kg.jpg', '', '[\"assets\\/how-to-use\\/G_One_Gainer_3Kg_howto_1751865154_1.jpg\",\"assets\\/how-to-use\\/G_One_Gainer_3Kg_howto_1751865154_2.jpg\",\"assets\\/how-to-use\\/G_One_Gainer_3Kg_howto_1751865154_3.jpg\",\"assets\\/how-to-use\\/G_One_Gainer_3Kg_howto_1751865154_4.jpg\",\"assets\\/how-to-use\\/G_One_Gainer_3Kg_howto_1751865154_5.jpg\",\"assets\\/how-to-use\\/G_One_Gainer_3Kg_howto_1751865215_1.jpg\",\"assets\\/how-to-use\\/G_One_Gainer_3Kg_howto_1751865215_2.jpg\",\"assets\\/how-to-use\\/G_One_Gainer_3Kg_howto_1751865215_3.jpg\",\"assets\\/how-to-use\\/G_One_Gainer_3Kg_howto_1751865215_4.jpg\",\"assets\\/how-to-use\\/G_One_Gainer_3Kg_howto_1751865215_5.jpg\",\"assets\\/how-to-use\\/G_One_Gainer_3Kg_howto_1751865285_1.jpg\",\"assets\\/how-to-use\\/G_One_Gainer_3Kg_howto_1751865285_2.jpg\",\"assets\\/how-to-use\\/G_One_Gainer_3Kg_howto_1751865285_3.jpg\",\"assets\\/how-to-use\\/G_One_Gainer_3Kg_howto_1751865285_4.jpg\",\"assets\\/how-to-use\\/G_One_Gainer_3Kg_howto_1751865285_5.jpg\"]', '', 'assets/G-One-Gainer-3-Kg.jpg', 89.99, '7199a18d-589a-11f0-8cbc-f439091252f6', 0, 1, 0, '2025-07-04 05:48:00'),
(14, '719e58c5-589a-11f0-8cbc-f439091252f6', 'Intense Pre-workout', NULL, 'assets/Intense-Pre-workout.jpg', NULL, 'assets/Intense-Pre-workout.jpg', NULL, 'assets/Intense-Pre-workout.jpg', NULL, NULL, NULL, 'assets/Intense-Pre-workout.jpg', 54.99, '7199a18d-589a-11f0-8cbc-f439091252f6', 100, 1, 0, '2025-07-04 05:48:00'),
(15, '719e5989-589a-11f0-8cbc-f439091252f6', 'Lean Mass Gainer 3Kg', NULL, 'assets/Lean-Mass-Gainer.jpg-3-kg.jpg', NULL, 'assets/Lean-Mass-Gainer.jpg-3-kg.jpg', NULL, 'assets/Lean-Mass-Gainer.jpg-3-kg.jpg', NULL, NULL, NULL, 'assets/Lean-Mass-Gainer.jpg-3-kg.jpg', 94.99, '7199a18d-589a-11f0-8cbc-f439091252f6', 80, 1, 0, '2025-07-04 05:48:00'),
(16, '719e5a3e-589a-11f0-8cbc-f439091252f6', 'Massive Gain 1Kg', NULL, 'assets/Massive-Gain--1Kg.jpg', NULL, 'assets/Massive-Gain--1Kg.jpg', NULL, 'assets/Massive-Gain--1Kg.jpg', NULL, NULL, NULL, 'assets/Massive-Gain--1Kg.jpg', 49.99, '7199a18d-589a-11f0-8cbc-f439091252f6', 100, 1, 0, '2025-07-04 05:48:00'),
(17, '719e5afc-589a-11f0-8cbc-f439091252f6', 'Massive Gain 3Kg', NULL, 'assets/Massive-Gain--3Kg.jpg', NULL, 'assets/Massive-Gain--3Kg.jpg', NULL, 'assets/Massive-Gain--3Kg.jpg', NULL, NULL, NULL, 'assets/Massive-Gain--3Kg.jpg', 109.99, '7199a18d-589a-11f0-8cbc-f439091252f6', 60, 1, 0, '2025-07-04 05:48:00'),
(18, '719e5bb1-589a-11f0-8cbc-f439091252f6', 'Real Bulk Gain 1Kg', NULL, 'assets/Real-Bulk-Gain-1-Kg.jpg', NULL, 'assets/Real-Bulk-Gain-1-Kg.jpg', NULL, 'assets/Real-Bulk-Gain-1-Kg.jpg', NULL, NULL, NULL, 'assets/Real-Bulk-Gain-1-Kg.jpg', 45.99, '7199a18d-589a-11f0-8cbc-f439091252f6', 100, 1, 0, '2025-07-04 05:48:00'),
(19, '719e5c5d-589a-11f0-8cbc-f439091252f6', 'Real Bulk Gain 3Kg', NULL, 'assets/Real-Bulk-Gain-3-Kg.jpg', NULL, 'assets/Real-Bulk-Gain-3-Kg.jpg', NULL, 'assets/Real-Bulk-Gain-3-Kg.jpg', NULL, NULL, NULL, 'assets/Real-Bulk-Gain-3-Kg.jpg', 99.99, '7199a18d-589a-11f0-8cbc-f439091252f6', 70, 1, 0, '2025-07-04 05:48:00'),
(20, '719e5d0b-589a-11f0-8cbc-f439091252f6', 'Shootup Pre-Workout', NULL, 'assets/Shootup-pre-Workout.jpg', NULL, 'assets/Shootup-pre-Workout.jpg', NULL, 'assets/Shootup-pre-Workout.jpg', NULL, NULL, NULL, 'assets/Shootup-pre-Workout.jpg', 52.99, '7199a18d-589a-11f0-8cbc-f439091252f6', 100, 1, 0, '2025-07-04 05:48:00'),
(21, '719e5dc2-589a-11f0-8cbc-f439091252f6', 'Whey Gold 1kg', NULL, 'assets/Whey-gold-1kg.jpg', NULL, 'assets/Whey-gold-1kg.jpg', NULL, 'assets/Whey-gold-1kg.jpg', NULL, NULL, NULL, 'assets/Whey-gold-1kg.jpg', 59.99, '7199a18d-589a-11f0-8cbc-f439091252f6', 120, 1, 0, '2025-07-04 05:48:00'),
(22, '889e7987aaf93ebbcbdff0e309af6565', 'Liver Detox ', 'sffafv tdstdtsdt dfsddfdsd', 'assets/product_67455d08a728c0fa_1752213464.jpg', 'dddsdfsdfsdfdfsdfdfsdfdfd', 'assets/product_67455d08a728c0fa_1752213464.jpg', 'fdsffsdfdfddfdfdsdsdsdfdsdf', 'assets/product_67455d08a728c0fa_1752213464.jpg', 'sddsfdfsdddfsdfsdfsdfsdfsdfdsd', '[]', 'fsdfdfsdfsdfsdfsdfsdfsdfsdfsd', 'assets/product_67455d08a728c0fa_1752213464.jpg', 1530.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 10, 1, 0, '2025-07-11 05:57:44'),
(23, '89787f755db7e678f4fbb777df77c09b', 'Vitamin C', 'zsffsffsfa', 'assets/product_5df94b6b6f9d6436_1752214315.jpg', 'sffsafssafsfsfsf', 'assets/product_5df94b6b6f9d6436_1752214315.jpg', 'asfsfasfsfasfasfasf', 'assets/product_5df94b6b6f9d6436_1752214315.jpg', 'sfasffasasfsfasfsf', '[]', 'asfsffsfasfsffas', 'assets/product_5df94b6b6f9d6436_1752214315.jpg', 770.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 10, 1, 0, '2025-07-11 06:11:55'),
(24, '8e621e91b8132a0291e21b82640c0498', 'Multivitamin Probiotics Ginseng', 'ssfsfsssfssfffsfsfsffsffasfs', 'assets/product_9366c4380746bec8_1752214206.jpg', 'ffsffasfsfsfasfsfsfasffsf fsffsffsfsfffsf', 'assets/product_9366c4380746bec8_1752214206.jpg', 'sffsffsasfsfsfsf ffsfsaasfsf', 'assets/product_9366c4380746bec8_1752214206.jpg', 'sffdddfdfdfdfdffdfdfdf', '[]', 'dfdfdfdsfdfdfdfdfdfdfdffdfd', 'assets/product_9366c4380746bec8_1752214206.jpg', 1065.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 10, 1, 0, '2025-07-11 06:10:06'),
(25, '92830a6d94c1dec07e04b409b8e2c2ad', 'Curcumin ', 'fsdfsdfdfds eee aweeae ', 'assets/product_f82ed4a46e158c12_1752212470.jpg', 'eerwe weerwe aweetta taert etwe', 'assets/product_f82ed4a46e158c12_1752212470.jpg', 'etwet ttwetttet twert gffggffd', 'assets/product_f82ed4a46e158c12_1752212470.jpg', 'ertterttrrtterertrr ttrtt trtrtrt', '[]', 'rtrt rffgdfderetrt rtre', 'assets/product_f82ed4a46e158c12_1752212470.jpg', 1375.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 20, 1, 0, '2025-07-11 05:41:10'),
(26, 'b707c95da7361342a89ac017588a9c3e', 'Multivitamin with Probiotics ', 'dadsdsdsfsf sdsfasff sfsffsff', 'assets/product_54c228303dfa27ae_1752214069.jpg', 'asfffasfffasfsfsfsfsfs', 'assets/product_54c228303dfa27ae_1752214069.jpg', 'fsfsfffffsfasfsfsfsfsf ', 'assets/product_54c228303dfa27ae_1752214069.jpg', 'ffsfsfffff sfsffsfsfssfsfsfsfsfasfsf', '[]', 'assffsfsfasffsfsfsfsaf', 'assets/product_54c228303dfa27ae_1752214069.jpg', 920.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 10, 1, 0, '2025-07-11 06:07:49'),
(27, 'be03ff851681b2480a85ef4833fc39c8', 'Hydrolysed Collagen', '', 'assets/product_83056bf31cef3999_1752143571.png', '', 'assets/product_83056bf31cef3999_1752143571.png', '', 'assets/product_83056bf31cef3999_1752143571.png', '', '[]', '', 'assets/product_83056bf31cef3999_1752143571.png', 1400.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 0, 1, 0, '2025-07-10 10:32:51'),
(28, 'c45ad538dfe0e578238571a28cc5b602', 'Vitamin D3 + K2', 'sfsdffg rm[yo ytjy[pt yt[p tkypylktp]y', 'assets/product_3f480265b9e4dba2_1752212254.png', 'ytyt;y ytytytrytyṭty\\ lt\'tyttyty    tmym,', 'assets/product_3f480265b9e4dba2_1752212254.png', 'khslidf giiiiiierieriiiititit', 'assets/product_3f480265b9e4dba2_1752212254.png', 'errerwerrrerwererrrwer', '[]', 'errereererererewrerrererwewerrwerrwerr', 'assets/product_3f480265b9e4dba2_1752212254.png', 650.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 100, 1, 0, '2025-07-11 05:37:34'),
(29, 'c772841e240a8abe59760e8effa52ada', 'Salmon Fish Oil ', 'High-strength Omega 3 fish oil from wild-caught salmon with\r\nessential EPA & DHA to support heart, joint, brain, and skin health.\r\nNon-enteric coated for better absorption and certified\r\nmercury-free.', 'assets/product_8627619ba986f1cc_1752215063.jpg', 'Better Absorption with Non-Enteric Coated Softgels – Unlike\r\nsynthetic enteric-coated capsules, our Omega 3 softgels are free\r\nfrom methacrylic acid copolymers, ensuring natural and quicker\r\nabsorption for improved digestion and efficacy.\r\n✅ 100% Wild-Caught & Fresh-Pressed Salmon Oil – Sourced from\r\ndeep ocean waters using sustainable fishing practices, our\r\nsalmon oil is extra virgin and fresh-pressed to preserve potency\r\nand bioavailability.\r\n✅ Mercury-Free & Pure Quality – These softgel capsules are free\r\nfrom mercury, heavy metals, GMOs, krill oil, and cod liver oil.\r\nProcessed without high heat to retain the integrity of essential fatty\r\nacids.\r\n\r\n✅ 4-in-1 Health Support – May help support cardiovascular\r\nwellness, joint flexibility, bone strength, and skin nourishment. Ideal\r\nfor adults seeking a holistic approach to daily health.\r\n✅ Rich in EPA & DHA Omega 3 Fatty Acids – Delivers potent doses\r\nof EPA and DHA, which help improve Omega-3 index levels in just\r\ntwo weeks, supporting reduced inflammation and long-term heart\r\nhealth.', 'assets/product_8627619ba986f1cc_1752215063.jpg', 'hghghgfhghghghghfgh', 'assets/product_8627619ba986f1cc_1752215063.jpg', 'hghghfghghfghghghfgfghh', '[]', 'Salmon Fish Oil, Ingredients of capsule shell (Gelatin),\r\nHumectants (INS 420 (i) & INS 422), Glazing Agent (INS 901), Natural\r\nFlavour (Lemon Citrus) & Preservative (INS 202).', 'assets/product_8627619ba986f1cc_1752215063.jpg', 995.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 10, 1, 0, '2025-07-11 06:24:23'),
(30, 'd59cf2ab0212266abec523726b502969', 'Calcium Magnesium Zinc', 'dsdgsddsd fd', 'assets/product_98755a8d85945df1_1752214479.jpg', 'sgsdfddsdsddfdfddf', 'assets/product_98755a8d85945df1_1752214479.jpg', 'fsdffsdfsd', 'assets/product_98755a8d85945df1_1752214479.jpg', 'fdfdfsdfsdfsdsd', '[]', 'fsdfsdffdfdsfdd', 'assets/product_98755a8d85945df1_1752214479.jpg', 495.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 10, 1, 0, '2025-07-11 06:14:39'),
(31, 'dae17aaddcd31552010904a650127005', 'Chelated Iron Plus', 'dgggdggddggdgdgdgdgdgdgdg', 'assets/product_46ba5a6516859d70_1752214681.jpg', 'gdgsdggdsdggsdgsdg', 'assets/product_46ba5a6516859d70_1752214681.jpg', 'dgsdgdsdgsdgdgdsgdgdgsdg', 'assets/product_46ba5a6516859d70_1752214681.jpg', 'sdgdgdsgdgsdgsdgsdgsdgsdg', '[]', 'dgdgsdgsdgggd', 'assets/product_46ba5a6516859d70_1752214681.jpg', 770.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 10, 1, 0, '2025-07-11 06:18:01'),
(32, 'e0f37ea477c50964dce63e821adc6051', 'ZMA', 'dgsdgdggsdgdgdsggsddg', 'assets/product_eb623007cb191722_1752213849.jpg', 'sdgdgdsgsdgsddgdggdsd', 'assets/product_eb623007cb191722_1752213849.jpg', 'gdgsdgsdgdsgggdgdgdgd', 'assets/product_eb623007cb191722_1752213849.jpg', 'gdgdgdsggsdggdgdsgdsggdg', '[]', 'sdgdsggggdggdgs', 'assets/product_eb623007cb191722_1752213849.jpg', 890.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 10, 1, 0, '2025-07-11 06:03:02'),
(33, 'ea35ca5c59f92e74d18cbafe850364b3', 'Plant Based Vitamin B12', 'azssasas asfss', 'assets/product_910d86a9bf157535_1752212967.jpg', 'sdsdassddasasd sf dfg ggf5t gj ', 'assets/product_910d86a9bf157535_1752212967.jpg', 'mbbcvbhhdfjhklldrfvvnxd', 'assets/product_910d86a9bf157535_1752212967.jpg', 'rrrrrwrr hhghgfh', '[]', 'yhfghhfghg fghfghhfghfg', 'assets/product_910d86a9bf157535_1752212967.jpg', 980.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 11, 1, 0, '2025-07-11 05:49:27'),
(34, 'eca5b9abdc6b5613704b83f20c1223e8', 'Chelated Magnesium Glycinate', 'fdgdfggf ttrtty tyffghghhh hggtfthfg h', 'assets/product_169e31f7ec5875b1_1752212371.jpg', 'ghgh thfghfhffghfghfghfghfghfghfghfghfghh', 'assets/product_169e31f7ec5875b1_1752212371.jpg', 'hghfghfgh fghfghgfh fghfhfgh ghfghfghfghgh', 'assets/product_169e31f7ec5875b1_1752212371.jpg', 'hfghfghfghfggfhfghfghfghhgghg', '[]', 'hghghghhghghfghfgfg', 'assets/product_169e31f7ec5875b1_1752212371.jpg', 1075.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 10, 1, 0, '2025-07-11 05:39:31'),
(35, 'f05ed8d948f1c4f6e33fc710c221cea9', 'L-Glutathione Reduced', 'daddasddssadsdf', 'assets/product_483ea64e6b7d6de1_1752214846.jpg', 'asdasdsdasdasdsdasds', 'assets/product_483ea64e6b7d6de1_1752214846.jpg', 'ddasddasdasdsd', 'assets/product_483ea64e6b7d6de1_1752214846.jpg', 'ddasdsdsdsadsdsdsaddasd', '[]', 'asddsasdsdsadsdsasdsdaswrgtgsdfghdfdsgd', 'assets/product_483ea64e6b7d6de1_1752214846.jpg', 920.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 10, 1, 0, '2025-07-11 06:20:46'),
(36, 'f4c37d2802bad30f3fe9806bee85c92b', 'B - Complex', 'ffffeerrrrrereerrr   dfufhfus dsdsipdfdhhdf fhusdfsdff dhdf hfu uss8def sdn dunm duseyu ee', 'assets/product_0aedfbda0b63d40c_1752212080.png', 'ugdfdfggdgy dgdsgffgif uuhu uu uddsdfh dufud ', 'assets/product_0aedfbda0b63d40c_1752212080.png', 'dfdsfsdfffsffdffdfff', 'assets/product_0aedfbda0b63d40c_1752212080.png', 'dffdfffsdfsdfdfdfdfd', '[]', 'ffdffsdfsdffsdsdfsfdfff', 'assets/product_0aedfbda0b63d40c_1752212080.png', 770.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 10, 1, 0, '2025-07-11 05:34:40'),
(37, 'f9cd6a64cae37796eb4516f08348a207', 'Probiotics 50 Billion', 'sfsfsf gdggg dsgds', 'assets/product_559ad842c05c2845_1752213617.jpg', 'dgdgdgdsgdgsdgdgdsdgsdgg', 'assets/product_559ad842c05c2845_1752213617.jpg', 'sdgdgsdgsdgsdsdgdsgdgdg', 'assets/product_559ad842c05c2845_1752213617.jpg', 'sdgsdgggsdgsdgd', '[]', 'sdgdddgdgdgsdgsdd', 'assets/product_559ad842c05c2845_1752213617.jpg', 1065.00, '67f8553d-589a-11f0-8cbc-f439091252f6', 10, 1, 0, '2025-07-11 06:00:17');

-- --------------------------------------------------------

--
-- Table structure for table `product_details`
--

CREATE TABLE `product_details` (
  `detail_id` char(36) NOT NULL,
  `product_id` char(36) DEFAULT NULL,
  `weight_value` decimal(10,2) DEFAULT NULL,
  `weight_unit` enum('g','kg','lb','oz') DEFAULT NULL,
  `servings_per_container` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `image_id` char(36) NOT NULL,
  `product_id` char(36) DEFAULT NULL,
  `image_url` text NOT NULL,
  `alt_text` text,
  `is_primary` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`image_id`, `product_id`, `image_url`, `alt_text`, `is_primary`) VALUES
('05d37d64289753425b3647db84a77e6c', 'f05ed8d948f1c4f6e33fc710c221cea9', 'assets/product_483ea64e6b7d6de1_1752214846.jpg', NULL, 1),
('0a9a1a113dff9d1a013342fc4c2ca46e', '0b18a826111bd18731e5d42cc503a7ce', 'assets/product_1badd63e157c889c_1752212572.jpg', NULL, 1),
('0ef8cb0ade766fe55f1fec7247773557', 'eca5b9abdc6b5613704b83f20c1223e8', 'assets/product_169e31f7ec5875b1_1752212371.jpg', NULL, 1),
('1cdb5cc83c109875ab96f0187c121df4', 'e0f37ea477c50964dce63e821adc6051', 'assets/product_eb623007cb191722_1752213849.jpg', NULL, 1),
('1d8a383e195f6ea63948c04d02bca1e1', '92830a6d94c1dec07e04b409b8e2c2ad', 'assets/product_f82ed4a46e158c12_1752212470.jpg', NULL, 1),
('2469a8581167832e00e958e421870398', 'c772841e240a8abe59760e8effa52ada', 'assets/product_8627619ba986f1cc_1752215063.jpg', NULL, 1),
('3cfc8d2e87721a811b411030bdabcd2f', '0e26db87d6e1916374a91b8ca743eac7', 'assets/product_7ae08ab089684755_1752214563.jpg', NULL, 1),
('52a447f5b0f73a7209fc0581241beced', 'f9cd6a64cae37796eb4516f08348a207', 'assets/product_559ad842c05c2845_1752213617.jpg', NULL, 1),
('55fe151bb90e9394b9fabc7adae818ba', '22e337976e401272877b7efc0c0b6aab', 'assets/product_698eb593e84c5095_1752213226.jpg', NULL, 1),
('5701311786b057ff6ce735fe58fe1ef8', '889e7987aaf93ebbcbdff0e309af6565', 'assets/product_67455d08a728c0fa_1752213464.jpg', NULL, 1),
('5927e49731cbcad2fb3aaec55c6e5ea0', 'dae17aaddcd31552010904a650127005', 'assets/product_46ba5a6516859d70_1752214681.jpg', NULL, 1),
('60df4a64e7d871d3ccdd13bd5c75c3c8', '502e7eb4e3859af1bad9ee52b0c885a8', 'assets/product_bae86abf1d7f7a41_1752213697.jpg', NULL, 1),
('71a09f80-589a-11f0-8cbc-f439091252f6', '719e4de8-589a-11f0-8cbc-f439091252f6', 'assets/Black-Powder.jpg', NULL, 1),
('71a0a2b2-589a-11f0-8cbc-f439091252f6', '719e5341-589a-11f0-8cbc-f439091252f6', 'assets/Cratein-100g.jpg', NULL, 1),
('71a0a40f-589a-11f0-8cbc-f439091252f6', '719e54c0-589a-11f0-8cbc-f439091252f6', 'assets/G-One-Gainer-1-Kg.jpg', NULL, 1),
('71a0a4e8-589a-11f0-8cbc-f439091252f6', '719e5597-589a-11f0-8cbc-f439091252f6', 'assets/G-One-Gainer-3-Kg.jpg', NULL, 1),
('71a0a76a-589a-11f0-8cbc-f439091252f6', '719e58c5-589a-11f0-8cbc-f439091252f6', 'assets/Intense-Pre-workout.jpg', NULL, 1),
('71a0a83d-589a-11f0-8cbc-f439091252f6', '719e5989-589a-11f0-8cbc-f439091252f6', 'assets/Lean-Mass-Gainer.jpg-3-kg.jpg', NULL, 1),
('71a0a8ff-589a-11f0-8cbc-f439091252f6', '719e5a3e-589a-11f0-8cbc-f439091252f6', 'assets/Massive-Gain--1Kg.jpg', NULL, 1),
('71a0a9ba-589a-11f0-8cbc-f439091252f6', '719e5afc-589a-11f0-8cbc-f439091252f6', 'assets/Massive-Gain--3Kg.jpg', NULL, 1),
('71a0aa8c-589a-11f0-8cbc-f439091252f6', '719e5bb1-589a-11f0-8cbc-f439091252f6', 'assets/Real-Bulk-Gain-1-Kg.jpg', NULL, 1),
('71a0ab49-589a-11f0-8cbc-f439091252f6', '719e5c5d-589a-11f0-8cbc-f439091252f6', 'assets/Real-Bulk-Gain-3-Kg.jpg', NULL, 1),
('71a0ac11-589a-11f0-8cbc-f439091252f6', '719e5d0b-589a-11f0-8cbc-f439091252f6', 'assets/Shootup-pre-Workout.jpg', NULL, 1),
('71a0accf-589a-11f0-8cbc-f439091252f6', '719e5dc2-589a-11f0-8cbc-f439091252f6', 'assets/Whey-gold-1kg.jpg', NULL, 1),
('73fc0535d04964fe5a47e87b553aed01', '8e621e91b8132a0291e21b82640c0498', 'assets/product_9366c4380746bec8_1752214206.jpg', NULL, 1),
('759eca8a62c3f072f645a9d9986a0715', '594fd8a912b127f9187d4e03795097cd', 'assets/product_e5a6f42e4a246867_1751888888.jpg', NULL, 1),
('8f6fd3224f39732121e759e8ee97018a', '89787f755db7e678f4fbb777df77c09b', 'assets/product_5df94b6b6f9d6436_1752214315.jpg', NULL, 1),
('94dafe70d8cc4f78fef70ea6fc87676c', 'c45ad538dfe0e578238571a28cc5b602', 'assets/product_3f480265b9e4dba2_1752212254.png', NULL, 1),
('9b9e240cb56886e38d4c11954eb09224', '7025538976de53a9befc304843f98d09', 'assets/product_26d9572f51be49ed_1752214264.jpg', NULL, 1),
('b1bc0b122a2eae0c51c701290aecb9df', '07240ca09e34ce3dc9500eb0f0feea7f', 'assets/product_1fee8fef7a3115a5_1752214963.jpg', NULL, 1),
('ba42c126b30365a31d0e188728c2fb44', 'be03ff851681b2480a85ef4833fc39c8', 'assets/product_83056bf31cef3999_1752143571.png', NULL, 1),
('c2d811f7e7e92aa9d56d2d6706cc27da', '2494ee2d98944a9f65015a9785c63f92', 'assets/product_1f390971deca9593_1752214396.jpg', NULL, 1),
('def344feb4ace8b4d0f2b7fe97372edb', 'ea35ca5c59f92e74d18cbafe850364b3', 'assets/product_910d86a9bf157535_1752212967.jpg', NULL, 1),
('e670da65c7cf2c7012c918672daba28f', 'f4c37d2802bad30f3fe9806bee85c92b', 'assets/product_0aedfbda0b63d40c_1752212080.png', NULL, 1),
('e9dde5a871fa7ead4ef7cb281518f774', 'd59cf2ab0212266abec523726b502969', 'assets/product_98755a8d85945df1_1752214479.jpg', NULL, 1),
('f22e75f4030998febff4a2f9706f3425', '3e68d4edae4deec4f93e294c9a0104dd', 'assets/product_1e4feaafeec50dc9_1752213081.jpg', NULL, 1),
('f46d0e87b31573a7dba1dd3cde9d60e4', 'b707c95da7361342a89ac017588a9c3e', 'assets/product_54c228303dfa27ae_1752214069.jpg', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `product_usage_steps`
--

CREATE TABLE `product_usage_steps` (
  `step_id` char(36) NOT NULL,
  `product_id` char(36) NOT NULL,
  `step_number` int NOT NULL,
  `step_title` varchar(100) NOT NULL,
  `step_description` text NOT NULL,
  `step_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_usage_steps`
--

INSERT INTO `product_usage_steps` (`step_id`, `product_id`, `step_number`, `step_title`, `step_description`, `step_image`, `is_active`, `created_at`, `updated_at`) VALUES
('050fbe3b5ff50fc1ca72f8c73b55a4ac', '889e7987aaf93ebbcbdff0e309af6565', 1, 'Step 1', '', 'assets/how-to-use/050fbe3b5ff50fc1ca72f8c73b55a4ac_step_1.jpg', 1, '2025-07-11 09:44:03', '2025-07-11 09:44:03'),
('07857572bc676f02d09102a68ff3c1d0', 'eca5b9abdc6b5613704b83f20c1223e8', 2, 'Step 2', '', 'assets/how-to-use/07857572bc676f02d09102a68ff3c1d0_step_2.jpg', 1, '2025-07-11 09:38:33', '2025-07-11 09:38:33'),
('0a8d2ec248c8e8ce9689a8d6fbd1dd87', 'f9cd6a64cae37796eb4516f08348a207', 1, 'Step 1', '', 'assets/how-to-use/0a8d2ec248c8e8ce9689a8d6fbd1dd87_step_1.jpg', 1, '2025-07-11 09:57:25', '2025-07-11 09:57:25'),
('13ce755f6ec9c9993bd5349f7c15340d', '7025538976de53a9befc304843f98d09', 3, 'Step 3', '', 'assets/how-to-use/13ce755f6ec9c9993bd5349f7c15340d_step_3.jpg', 1, '2025-07-11 09:10:46', '2025-07-11 09:10:46'),
('16d6c66159acfc8e5fccf1bee232caf4', 'be03ff851681b2480a85ef4833fc39c8', 1, 'Step 1', '', 'assets/how-to-use/16d6c66159acfc8e5fccf1bee232caf4_step_1.jpg', 1, '2025-07-11 09:40:19', '2025-07-11 09:40:19'),
('184b078781ed65d3c48dee8fe77248fe', '889e7987aaf93ebbcbdff0e309af6565', 4, 'Step 4', '', 'assets/how-to-use/184b078781ed65d3c48dee8fe77248fe_step_4.jpg', 1, '2025-07-11 09:44:03', '2025-07-11 09:44:03'),
('18d85a92d34eaebd0bd1cdf3fa6ad2b3', '22e337976e401272877b7efc0c0b6aab', 3, 'Step 3', '', 'assets/how-to-use/18d85a92d34eaebd0bd1cdf3fa6ad2b3_step_3.jpg', 1, '2025-07-11 09:45:55', '2025-07-11 09:45:55'),
('19b87ce273ee445a047066983c65bf18', 'eca5b9abdc6b5613704b83f20c1223e8', 1, 'Step 1', '', 'assets/how-to-use/19b87ce273ee445a047066983c65bf18_step_1.jpg', 1, '2025-07-11 09:38:33', '2025-07-11 09:38:33'),
('1bafc16d64022049fde781e829131eb6', '7025538976de53a9befc304843f98d09', 2, 'Step 2', '', 'assets/how-to-use/1bafc16d64022049fde781e829131eb6_step_2.jpg', 1, '2025-07-11 09:10:46', '2025-07-11 09:10:46'),
('1bef3db2f81b770db1d66203b0919cb7', '0e26db87d6e1916374a91b8ca743eac7', 4, 'Step 4', '', 'assets/how-to-use/1bef3db2f81b770db1d66203b0919cb7_step_4.jpg', 1, '2025-07-11 10:00:09', '2025-07-11 10:00:09'),
('1c01d04f9ff2e379b9ee4f255df7d832', 'c45ad538dfe0e578238571a28cc5b602', 4, 'Step 4', '', 'assets/how-to-use/1c01d04f9ff2e379b9ee4f255df7d832_step_4.jpg', 1, '2025-07-11 10:01:32', '2025-07-11 10:01:32'),
('1e93adedfe70251bce8c5e52264be1ac', 'be03ff851681b2480a85ef4833fc39c8', 4, 'Step 4', '', 'assets/how-to-use/1e93adedfe70251bce8c5e52264be1ac_step_4.jpg', 1, '2025-07-11 09:40:19', '2025-07-11 09:40:19'),
('1ef421cde3f781397e2b723bd102d287', '89787f755db7e678f4fbb777df77c09b', 1, 'Step 1', '', 'assets/how-to-use/1ef421cde3f781397e2b723bd102d287_step_1.jpg', 1, '2025-07-11 10:30:16', '2025-07-11 10:30:16'),
('23e0fce5252785c07d372aefd37abe96', '0b18a826111bd18731e5d42cc503a7ce', 4, 'Step 4', '', 'assets/how-to-use/23e0fce5252785c07d372aefd37abe96_step_4.jpg', 1, '2025-07-11 09:42:34', '2025-07-11 09:42:34'),
('256c19b443dce12c689b135e13042a5d', 'd59cf2ab0212266abec523726b502969', 1, 'Step 1', '', 'assets/how-to-use/256c19b443dce12c689b135e13042a5d_step_1.jpg', 1, '2025-07-11 09:19:03', '2025-07-11 09:19:03'),
('2bfc0cda3ca985886fe2141466a19837', 'b707c95da7361342a89ac017588a9c3e', 4, 'Step 4', '', 'assets/how-to-use/2bfc0cda3ca985886fe2141466a19837_step_4.jpg', 1, '2025-07-11 09:52:16', '2025-07-11 09:52:16'),
('2d53146eb1eb96feb6443aacd7659105', 'c772841e240a8abe59760e8effa52ada', 1, 'Step 1', '', 'assets/how-to-use/2d53146eb1eb96feb6443aacd7659105_step_1.jpg', 1, '2025-07-11 09:58:44', '2025-07-11 09:58:44'),
('30f9a48fb557fac59f9406ca97ce4c88', 'ea35ca5c59f92e74d18cbafe850364b3', 3, 'Step 3', '', 'assets/how-to-use/30f9a48fb557fac59f9406ca97ce4c88_step_3.jpg', 1, '2025-07-11 10:23:43', '2025-07-11 10:23:43'),
('3134890db24adf6a514d437473a226ba', 'f05ed8d948f1c4f6e33fc710c221cea9', 3, 'Step 3', '', 'assets/how-to-use/3134890db24adf6a514d437473a226ba_step_3.jpg', 1, '2025-07-11 10:33:17', '2025-07-11 10:33:17'),
('313b5e51ae46072eaedb0b60858d14c8', 'be03ff851681b2480a85ef4833fc39c8', 2, 'Step 2', '', 'assets/how-to-use/313b5e51ae46072eaedb0b60858d14c8_step_2.jpg', 1, '2025-07-11 09:40:19', '2025-07-11 09:40:19'),
('3456d098d679458400d4478ed11bca0d', '3e68d4edae4deec4f93e294c9a0104dd', 3, 'Step 3', '', 'assets/how-to-use/3456d098d679458400d4478ed11bca0d_step_3.jpg', 1, '2025-07-11 09:41:51', '2025-07-11 09:41:51'),
('34745bf79aed0fc3f7d5f6ded4d32a4c', 'b707c95da7361342a89ac017588a9c3e', 2, 'Step 2', '', 'assets/how-to-use/34745bf79aed0fc3f7d5f6ded4d32a4c_step_2.jpg', 1, '2025-07-11 09:52:16', '2025-07-11 09:52:16'),
('355e88d6326c4abc017477043320a07c', '889e7987aaf93ebbcbdff0e309af6565', 2, 'Step 2', '', 'assets/how-to-use/355e88d6326c4abc017477043320a07c_step_2.jpg', 1, '2025-07-11 09:44:03', '2025-07-11 09:44:03'),
('35bb0c0e16df6279b8fc265a28a63dce', '92830a6d94c1dec07e04b409b8e2c2ad', 3, 'Step 3', '', 'assets/how-to-use/35bb0c0e16df6279b8fc265a28a63dce_step_3.jpg', 1, '2025-07-11 09:39:15', '2025-07-11 09:39:15'),
('36a50ef05b4cb1d373cf47f6ea42d06e', 'c45ad538dfe0e578238571a28cc5b602', 2, 'Step 2', '', 'assets/how-to-use/36a50ef05b4cb1d373cf47f6ea42d06e_step_2.jpg', 1, '2025-07-11 10:01:32', '2025-07-11 10:01:32'),
('381dcf49a37e3266b5bfb5fb765b3f9e', '3e68d4edae4deec4f93e294c9a0104dd', 2, 'Step 2', '', 'assets/how-to-use/381dcf49a37e3266b5bfb5fb765b3f9e_step_2.jpg', 1, '2025-07-11 09:41:51', '2025-07-11 09:41:51'),
('38f34c8f26c1d2d20e147241f801e0bb', '719e5597-589a-11f0-8cbc-f439091252f6', 1, 'Mix with water', 'wewewewewsfsasggddgdg', 'assets/how-to-use/38f34c8f26c1d2d20e147241f801e0bb_step_1.jpg', 1, '2025-07-07 05:43:05', '2025-07-07 05:43:05'),
('3d3b950b8a2c1b261348bb6f8b17bd54', '07240ca09e34ce3dc9500eb0f0feea7f', 3, 'Step 3', '', 'assets/how-to-use/3d3b950b8a2c1b261348bb6f8b17bd54_step_3.jpg', 1, '2025-07-11 09:55:35', '2025-07-11 09:55:35'),
('3d5461fb468b7f6de7321973308c6b42', '502e7eb4e3859af1bad9ee52b0c885a8', 2, 'Step 2', '', 'assets/how-to-use/3d5461fb468b7f6de7321973308c6b42_step_2.jpg', 1, '2025-07-11 09:45:09', '2025-07-11 09:45:09'),
('44817cae23035cab40316058e674b833', 'f9cd6a64cae37796eb4516f08348a207', 5, 'Step 5', '', 'assets/how-to-use/44817cae23035cab40316058e674b833_step_5.jpg', 1, '2025-07-11 09:57:25', '2025-07-11 09:57:25'),
('45329310be1dc980445ece749dcf7a36', 'f9cd6a64cae37796eb4516f08348a207', 3, 'Step 3', '', 'assets/how-to-use/45329310be1dc980445ece749dcf7a36_step_3.jpg', 1, '2025-07-11 09:57:25', '2025-07-11 09:57:25'),
('49529899c349d7b9892c009a83f1ba44', 'eca5b9abdc6b5613704b83f20c1223e8', 3, 'Step 3', '', 'assets/how-to-use/49529899c349d7b9892c009a83f1ba44_step_3.jpg', 1, '2025-07-11 09:38:33', '2025-07-11 09:38:33'),
('4cbc5b2a23e30506d43e35be74ea6d93', 'ea35ca5c59f92e74d18cbafe850364b3', 1, 'Step 1', '', 'assets/how-to-use/4cbc5b2a23e30506d43e35be74ea6d93_step_1.jpg', 1, '2025-07-11 10:23:43', '2025-07-11 10:23:43'),
('4cea30e669165b2b8031088b723da254', '0b18a826111bd18731e5d42cc503a7ce', 2, 'Step 2', '', 'assets/how-to-use/4cea30e669165b2b8031088b723da254_step_2.jpg', 1, '2025-07-11 09:42:34', '2025-07-11 09:42:34'),
('52066d96dfb7f2e79cce650290c73a6d', 'f9cd6a64cae37796eb4516f08348a207', 4, 'Step 4', '', 'assets/how-to-use/52066d96dfb7f2e79cce650290c73a6d_step_4.jpg', 1, '2025-07-11 09:57:25', '2025-07-11 09:57:25'),
('526da21ae34eb86473044532b64724a2', 'c772841e240a8abe59760e8effa52ada', 4, 'Step 4', '', 'assets/how-to-use/526da21ae34eb86473044532b64724a2_step_4.jpg', 1, '2025-07-11 09:58:44', '2025-07-11 09:58:44'),
('5e8b7b9ec6642043b457b376bc2944b2', 'd59cf2ab0212266abec523726b502969', 2, 'Step 2', '', 'assets/how-to-use/5e8b7b9ec6642043b457b376bc2944b2_step_2.jpg', 1, '2025-07-11 09:19:03', '2025-07-11 09:19:03'),
('6a312c91f167b0b762bbcab67ff0c630', 'dae17aaddcd31552010904a650127005', 2, 'Step 2', '', 'assets/how-to-use/6a312c91f167b0b762bbcab67ff0c630_step_2.jpg', 1, '2025-07-11 09:37:24', '2025-07-11 09:37:24'),
('6def5aca6b4b81a322448820159be5a1', '719e4de8-589a-11f0-8cbc-f439091252f6', 5, 'Step 5', '', 'assets/how-to-use/6def5aca6b4b81a322448820159be5a1_step_5.jpg', 1, '2025-07-10 08:04:01', '2025-07-10 08:04:01'),
('7103ea6d558e5e6621408e298a20d9c9', 'f4c37d2802bad30f3fe9806bee85c92b', 4, 'Step 4', '', 'assets/how-to-use/7103ea6d558e5e6621408e298a20d9c9_step_4.jpg', 1, '2025-07-11 08:22:14', '2025-07-11 08:22:14'),
('7138a66e3e986ae095f04f77b68e044b', '89787f755db7e678f4fbb777df77c09b', 4, 'Step 4', '', 'assets/how-to-use/7138a66e3e986ae095f04f77b68e044b_step_4.jpg', 1, '2025-07-11 10:30:16', '2025-07-11 10:30:16'),
('739454a6adee266f969c50419b4a7df6', '719e4de8-589a-11f0-8cbc-f439091252f6', 3, 'Step 3', '', 'assets/how-to-use/739454a6adee266f969c50419b4a7df6_step_3.jpg', 1, '2025-07-10 08:04:01', '2025-07-10 08:04:01'),
('73bbd80fe366265bc8b32a6195fac3ca', '22e337976e401272877b7efc0c0b6aab', 1, 'Step 1', '', 'assets/how-to-use/73bbd80fe366265bc8b32a6195fac3ca_step_1.jpg', 1, '2025-07-11 09:45:55', '2025-07-11 09:45:55'),
('7426272e7eead66350853560a5a56399', '719e4de8-589a-11f0-8cbc-f439091252f6', 1, 'Step 1', '', 'assets/how-to-use/7426272e7eead66350853560a5a56399_step_1.jpg', 1, '2025-07-10 08:04:01', '2025-07-10 08:04:01'),
('7cc9d42e28178f17a61def6c55b19a15', '3e68d4edae4deec4f93e294c9a0104dd', 1, 'Step 1', '', 'assets/how-to-use/7cc9d42e28178f17a61def6c55b19a15_step_1.jpg', 1, '2025-07-11 09:41:51', '2025-07-11 09:41:51'),
('7de14fee6f20604833f386c82533ea84', 'f9cd6a64cae37796eb4516f08348a207', 2, 'Step 2', '', 'assets/how-to-use/7de14fee6f20604833f386c82533ea84_step_2.jpg', 1, '2025-07-11 09:57:25', '2025-07-11 09:57:25'),
('9087a35fe5384a4e28b04df5698bec87', '8e621e91b8132a0291e21b82640c0498', 3, 'Step 3', '', 'assets/how-to-use/9087a35fe5384a4e28b04df5698bec87_step_3.jpg', 1, '2025-07-11 09:51:14', '2025-07-11 09:51:14'),
('90e46757de96f6e0989a9228e29d3911', 'c772841e240a8abe59760e8effa52ada', 3, 'Step 3', '', 'assets/how-to-use/90e46757de96f6e0989a9228e29d3911_step_3.jpg', 1, '2025-07-11 09:58:44', '2025-07-11 09:58:44'),
('9908c086143766d1bf62c3151c146644', 'e0f37ea477c50964dce63e821adc6051', 3, 'Step 3', '', 'assets/how-to-use/9908c086143766d1bf62c3151c146644_step_3.jpg', 1, '2025-07-11 10:02:38', '2025-07-11 10:02:38'),
('9bb733537c5824dd150af7d0aee9f134', '7025538976de53a9befc304843f98d09', 4, 'Step 4', '', 'assets/how-to-use/9bb733537c5824dd150af7d0aee9f134_step_4.jpg', 1, '2025-07-11 09:10:46', '2025-07-11 09:10:46'),
('9e1e805e29cfb22ac15717f65d47c92c', '8e621e91b8132a0291e21b82640c0498', 1, 'Step 1', '', 'assets/how-to-use/9e1e805e29cfb22ac15717f65d47c92c_step_1.jpg', 1, '2025-07-11 09:51:14', '2025-07-11 09:51:14'),
('a371ad2e454f28293cbd17713466a5e5', '719e4de8-589a-11f0-8cbc-f439091252f6', 2, 'Step 2', '', 'assets/how-to-use/a371ad2e454f28293cbd17713466a5e5_step_2.jpg', 1, '2025-07-10 08:04:01', '2025-07-10 08:04:01'),
('a461f2ab288b93a0c059df99a4154055', 'eca5b9abdc6b5613704b83f20c1223e8', 5, 'Step 5', '', 'assets/how-to-use/a461f2ab288b93a0c059df99a4154055_step_5.jpg', 1, '2025-07-11 09:38:33', '2025-07-11 09:38:33'),
('a657bf4bfe479d6a9af5683b251ad019', 'be03ff851681b2480a85ef4833fc39c8', 3, 'Step 3', '', 'assets/how-to-use/a657bf4bfe479d6a9af5683b251ad019_step_3.jpg', 1, '2025-07-11 09:40:19', '2025-07-11 09:40:19'),
('a8d87d3b347b3835fe2430ce17555665', 'c45ad538dfe0e578238571a28cc5b602', 3, 'Step 3', '', 'assets/how-to-use/a8d87d3b347b3835fe2430ce17555665_step_3.jpg', 1, '2025-07-11 10:01:32', '2025-07-11 10:01:32'),
('a9152ce0a8fb6980177c61f42c61ca15', '89787f755db7e678f4fbb777df77c09b', 3, 'Step 3', '', 'assets/how-to-use/a9152ce0a8fb6980177c61f42c61ca15_step_3.jpg', 1, '2025-07-11 10:30:16', '2025-07-11 10:30:16'),
('ad99169169f7cc39aeb3e152eb0d5db6', 'b707c95da7361342a89ac017588a9c3e', 3, 'Step 3', '', 'assets/how-to-use/ad99169169f7cc39aeb3e152eb0d5db6_step_3.jpg', 1, '2025-07-11 09:52:16', '2025-07-11 09:52:16'),
('b4c96916534ba3606191309368b17cd4', 'd59cf2ab0212266abec523726b502969', 3, 'Step 3', '', 'assets/how-to-use/b4c96916534ba3606191309368b17cd4_step_3.jpg', 1, '2025-07-11 09:19:03', '2025-07-11 09:19:03'),
('b5a8dec233be888f08873781c4e505ad', '719e4de8-589a-11f0-8cbc-f439091252f6', 4, 'Step 4', '', 'assets/how-to-use/b5a8dec233be888f08873781c4e505ad_step_4.jpg', 1, '2025-07-10 08:04:01', '2025-07-10 08:04:01'),
('b749c4a84aaf5a676614f25a3a6ba040', 'e0f37ea477c50964dce63e821adc6051', 4, 'Step 4', '', 'assets/how-to-use/b749c4a84aaf5a676614f25a3a6ba040_step_4.jpg', 1, '2025-07-11 10:02:38', '2025-07-11 10:02:38'),
('b8b55340bcd6d152a5b0601b04074452', 'ea35ca5c59f92e74d18cbafe850364b3', 4, 'Step 4', '', 'assets/how-to-use/b8b55340bcd6d152a5b0601b04074452_step_4.jpg', 1, '2025-07-11 10:23:43', '2025-07-11 10:23:43'),
('b8e3b354f162d88c0c54840e3ed49937', '0b18a826111bd18731e5d42cc503a7ce', 3, 'Step 3', '', 'assets/how-to-use/b8e3b354f162d88c0c54840e3ed49937_step_3.jpg', 1, '2025-07-11 09:42:34', '2025-07-11 09:42:34'),
('bbec408206b585cc10cb16cebaac722f', '502e7eb4e3859af1bad9ee52b0c885a8', 3, 'Step 3', '', 'assets/how-to-use/bbec408206b585cc10cb16cebaac722f_step_3.jpg', 1, '2025-07-11 09:45:09', '2025-07-11 09:45:09'),
('bcaecb5502e3baec2b1dada97112df29', '0e26db87d6e1916374a91b8ca743eac7', 2, 'Step 2', '', 'assets/how-to-use/bcaecb5502e3baec2b1dada97112df29_step_2.jpg', 1, '2025-07-11 10:00:09', '2025-07-11 10:00:09'),
('be25dc92af6d7daa3b4396d7a7cf7bc7', 'b707c95da7361342a89ac017588a9c3e', 5, 'Step 5', '', 'assets/how-to-use/be25dc92af6d7daa3b4396d7a7cf7bc7_step_5.jpg', 1, '2025-07-11 09:52:16', '2025-07-11 09:52:16'),
('c078f3f0c866d210b13c496f6e0d1986', 'b707c95da7361342a89ac017588a9c3e', 1, 'Step 1', '', 'assets/how-to-use/c078f3f0c866d210b13c496f6e0d1986_step_1.jpg', 1, '2025-07-11 09:52:16', '2025-07-11 09:52:16'),
('c1475405a91a5b97da7260163acaca6e', 'eca5b9abdc6b5613704b83f20c1223e8', 4, 'Step 4', '', 'assets/how-to-use/c1475405a91a5b97da7260163acaca6e_step_4.jpg', 1, '2025-07-11 09:38:33', '2025-07-11 09:38:33'),
('c16b52283dd1191bbbeb77625e029201', '07240ca09e34ce3dc9500eb0f0feea7f', 2, 'Step 2', '', 'assets/how-to-use/c16b52283dd1191bbbeb77625e029201_step_2.jpg', 1, '2025-07-11 09:55:35', '2025-07-11 09:55:35'),
('c1de08a507d67ccf2429fdd9639d90b6', 'd59cf2ab0212266abec523726b502969', 4, 'Step 4', '', 'assets/how-to-use/c1de08a507d67ccf2429fdd9639d90b6_step_4.jpg', 1, '2025-07-11 09:19:03', '2025-07-11 09:19:03'),
('c85d45e31ae4a3e15f4a476026f22ead', '7025538976de53a9befc304843f98d09', 1, 'Step 1', '', 'assets/how-to-use/c85d45e31ae4a3e15f4a476026f22ead_step_1.jpg', 1, '2025-07-11 09:10:46', '2025-07-11 09:10:46'),
('c9d51612cb39ac759e97a2c19981b925', 'dae17aaddcd31552010904a650127005', 1, 'Step 1', '', 'assets/how-to-use/c9d51612cb39ac759e97a2c19981b925_step_1.jpg', 1, '2025-07-11 09:37:24', '2025-07-11 09:37:24'),
('ce6c70861c12fc970bf502153c88ba33', 'f05ed8d948f1c4f6e33fc710c221cea9', 1, 'Step 1', '', 'assets/how-to-use/ce6c70861c12fc970bf502153c88ba33_step_1.jpg', 1, '2025-07-11 10:33:17', '2025-07-11 10:33:17'),
('d0e3763706c6b1716251ee04889e38d7', '22e337976e401272877b7efc0c0b6aab', 2, 'Step 2', '', 'assets/how-to-use/d0e3763706c6b1716251ee04889e38d7_step_2.jpg', 1, '2025-07-11 09:45:55', '2025-07-11 09:45:55'),
('d1018ff2ac019900e6cdacaf0160fd19', '92830a6d94c1dec07e04b409b8e2c2ad', 1, 'Step 1', '', 'assets/how-to-use/d1018ff2ac019900e6cdacaf0160fd19_step_1.jpg', 1, '2025-07-11 09:39:15', '2025-07-11 09:39:15'),
('d4374aea0c413048c85b6181751ca7d9', 'd59cf2ab0212266abec523726b502969', 5, 'Step 5', '', 'assets/how-to-use/d4374aea0c413048c85b6181751ca7d9_step_5.jpg', 1, '2025-07-11 09:19:03', '2025-07-11 09:19:03'),
('d75a36ad00aa8252c20999413e1886e9', '07240ca09e34ce3dc9500eb0f0feea7f', 4, 'Step 4', '', 'assets/how-to-use/d75a36ad00aa8252c20999413e1886e9_step_4.jpg', 1, '2025-07-11 09:55:35', '2025-07-11 09:55:35'),
('d7927c09b5073ae18ed435b563b0ab7a', 'e0f37ea477c50964dce63e821adc6051', 2, 'Step 2', '', 'assets/how-to-use/d7927c09b5073ae18ed435b563b0ab7a_step_2.jpg', 1, '2025-07-11 10:02:38', '2025-07-11 10:02:38'),
('d9d64befbe8bd130d8008c45ca7957f1', 'c45ad538dfe0e578238571a28cc5b602', 1, 'Step 1', '', 'assets/how-to-use/d9d64befbe8bd130d8008c45ca7957f1_step_1.jpg', 1, '2025-07-11 10:01:32', '2025-07-11 10:01:32'),
('dd77053107af999b8563d7edc311f9a3', '889e7987aaf93ebbcbdff0e309af6565', 3, 'Step 3', '', 'assets/how-to-use/dd77053107af999b8563d7edc311f9a3_step_3.jpg', 1, '2025-07-11 09:44:03', '2025-07-11 09:44:03'),
('dde17418b7c67d4eff2601788967292d', 'f4c37d2802bad30f3fe9806bee85c92b', 3, 'Step 3', '', 'assets/how-to-use/dde17418b7c67d4eff2601788967292d_step_3.jpg', 1, '2025-07-11 08:22:14', '2025-07-11 08:22:14'),
('e202fb7cfd3c22e5b5434a50b8becca1', 'f05ed8d948f1c4f6e33fc710c221cea9', 2, 'Step 2', '', 'assets/how-to-use/e202fb7cfd3c22e5b5434a50b8becca1_step_2.jpg', 1, '2025-07-11 10:33:17', '2025-07-11 10:33:17'),
('e210cbe551833a3fbb537987ed37bfa3', 'f4c37d2802bad30f3fe9806bee85c92b', 2, 'Step 2', '', 'assets/how-to-use/e210cbe551833a3fbb537987ed37bfa3_step_2.jpg', 1, '2025-07-11 08:22:14', '2025-07-11 08:22:14'),
('e404981bba511299106b12112873c48d', 'be03ff851681b2480a85ef4833fc39c8', 5, 'Step 5', '', 'assets/how-to-use/e404981bba511299106b12112873c48d_step_5.jpg', 1, '2025-07-11 09:40:19', '2025-07-11 09:40:19'),
('e623aeb1943fee5fd24306ca4c4d23a0', 'e0f37ea477c50964dce63e821adc6051', 1, 'Step 1', '', 'assets/how-to-use/e623aeb1943fee5fd24306ca4c4d23a0_step_1.jpg', 1, '2025-07-11 10:02:38', '2025-07-11 10:02:38'),
('e785a692f0336855fbb064afdbfd6f9e', '07240ca09e34ce3dc9500eb0f0feea7f', 1, 'Step 1', '', 'assets/how-to-use/e785a692f0336855fbb064afdbfd6f9e_step_1.jpg', 1, '2025-07-11 09:55:35', '2025-07-11 09:55:35'),
('e825e7dee7ed93e7dd2d00799901bf95', '502e7eb4e3859af1bad9ee52b0c885a8', 1, 'Step 1', '', 'assets/how-to-use/e825e7dee7ed93e7dd2d00799901bf95_step_1.jpg', 1, '2025-07-11 09:45:09', '2025-07-11 09:45:09'),
('ea47cc503fa3424e1622bc321bf90016', '8e621e91b8132a0291e21b82640c0498', 2, 'Step 2', '', 'assets/how-to-use/ea47cc503fa3424e1622bc321bf90016_step_2.jpg', 1, '2025-07-11 09:51:14', '2025-07-11 09:51:14'),
('ea965d30cf0e70467564b0d20d508b27', 'f4c37d2802bad30f3fe9806bee85c92b', 1, 'Step 1', '', 'assets/how-to-use/ea965d30cf0e70467564b0d20d508b27_step_1.jpg', 1, '2025-07-11 08:22:14', '2025-07-11 08:22:14'),
('eba7f227771923603083a28b49440f84', 'ea35ca5c59f92e74d18cbafe850364b3', 2, 'Step 2', '', 'assets/how-to-use/eba7f227771923603083a28b49440f84_step_2.jpg', 1, '2025-07-11 10:23:43', '2025-07-11 10:23:43'),
('efef66b1a895caa50875ebb8ea258903', 'c772841e240a8abe59760e8effa52ada', 2, 'Step 2', '', 'assets/how-to-use/efef66b1a895caa50875ebb8ea258903_step_2.jpg', 1, '2025-07-11 09:58:44', '2025-07-11 09:58:44'),
('f40901a4791c90ec12c66445cdd45da7', '92830a6d94c1dec07e04b409b8e2c2ad', 4, 'Step 4', '', 'assets/how-to-use/f40901a4791c90ec12c66445cdd45da7_step_4.jpg', 1, '2025-07-11 09:39:15', '2025-07-11 09:39:15'),
('f48370427a945e1ed85b6294c9ed3a3f', '89787f755db7e678f4fbb777df77c09b', 2, 'Step 2', '', 'assets/how-to-use/f48370427a945e1ed85b6294c9ed3a3f_step_2.jpg', 1, '2025-07-11 10:30:16', '2025-07-11 10:30:16'),
('f788dfd315a1faa10c2b4fcd41a5bcc5', '0e26db87d6e1916374a91b8ca743eac7', 3, 'Step 3', '', 'assets/how-to-use/f788dfd315a1faa10c2b4fcd41a5bcc5_step_3.jpg', 1, '2025-07-11 10:00:09', '2025-07-11 10:00:09'),
('fade02d43f0bcdc189780ad7eb23729b', '92830a6d94c1dec07e04b409b8e2c2ad', 2, 'Step 2', '', 'assets/how-to-use/fade02d43f0bcdc189780ad7eb23729b_step_2.jpg', 1, '2025-07-11 09:39:15', '2025-07-11 09:39:15'),
('fc4aaa4edf21ef3cf39732388f1cd71d', '0b18a826111bd18731e5d42cc503a7ce', 1, 'Step 1', '', 'assets/how-to-use/fc4aaa4edf21ef3cf39732388f1cd71d_step_1.jpg', 1, '2025-07-11 09:42:34', '2025-07-11 09:42:34'),
('fc9432fe285f36408fe8185c2ce76a53', '0e26db87d6e1916374a91b8ca743eac7', 1, 'Step 1', '', 'assets/how-to-use/fc9432fe285f36408fe8185c2ce76a53_step_1.jpg', 1, '2025-07-11 10:00:09', '2025-07-11 10:00:09'),
('step-6868cf449afde-719e5341-589a-11f', '719e5341-589a-11f0-8cbc-f439091252f6', 1, 'Mix with Water', 'Add 1 scoop (30g) to 200-250ml of cold water in a shaker bottle or glass.', 'assets/how-to-use/686238c5559f2_B - Complex 1.jpg', 1, '2025-07-05 07:07:48', '2025-07-05 07:07:48'),
('step-6868cf449c89a-719e5341-589a-11f', '719e5341-589a-11f0-8cbc-f439091252f6', 2, 'Shake Well', 'Shake vigorously for 30 seconds until the powder is completely dissolved and mixed.', 'assets/how-to-use/686238c555f2c_B - Complex 2.jpg', 1, '2025-07-05 07:07:48', '2025-07-05 07:07:48'),
('step-6868cf449d056-719e5341-589a-11f', '719e5341-589a-11f0-8cbc-f439091252f6', 3, 'Consume Immediately', 'Drink the mixture immediately after preparation for optimal absorption and effectiveness.', 'assets/how-to-use/686238c556100_B - Complex 3.jpg', 1, '2025-07-05 07:07:48', '2025-07-05 07:07:48'),
('step-6868cf449d7af-719e5341-589a-11f', '719e5341-589a-11f0-8cbc-f439091252f6', 4, 'Best Time to Take', 'Take 30 minutes before workout or as directed by your healthcare professional.', 'assets/how-to-use/686238c5562b2_B - Complex 4.jpg', 1, '2025-07-05 07:07:48', '2025-07-05 07:07:48'),
('step-6868cf44a6e55-719e58c5-589a-11f', '719e58c5-589a-11f0-8cbc-f439091252f6', 1, 'Mix with Water', 'Add 1 scoop (30g) to 200-250ml of cold water in a shaker bottle or glass.', 'assets/how-to-use/686238c5559f2_B - Complex 1.jpg', 1, '2025-07-05 07:07:48', '2025-07-05 07:07:48'),
('step-6868cf44a75d7-719e58c5-589a-11f', '719e58c5-589a-11f0-8cbc-f439091252f6', 2, 'Shake Well', 'Shake vigorously for 30 seconds until the powder is completely dissolved and mixed.', 'assets/how-to-use/686238c555f2c_B - Complex 2.jpg', 1, '2025-07-05 07:07:48', '2025-07-05 07:07:48'),
('step-6868cf44a7dea-719e58c5-589a-11f', '719e58c5-589a-11f0-8cbc-f439091252f6', 3, 'Consume Immediately', 'Drink the mixture immediately after preparation for optimal absorption and effectiveness.', 'assets/how-to-use/686238c556100_B - Complex 3.jpg', 1, '2025-07-05 07:07:48', '2025-07-05 07:07:48'),
('step-6868cf44a84b5-719e58c5-589a-11f', '719e58c5-589a-11f0-8cbc-f439091252f6', 4, 'Best Time to Take', 'Take 30 minutes before workout or as directed by your healthcare professional.', 'assets/how-to-use/686238c5562b2_B - Complex 4.jpg', 1, '2025-07-05 07:07:48', '2025-07-05 07:07:48'),
('step-6868cf44a8b0f-719e5989-589a-11f', '719e5989-589a-11f0-8cbc-f439091252f6', 1, 'Mix with Water', 'Add 1 scoop (30g) to 200-250ml of cold water in a shaker bottle or glass.', 'assets/how-to-use/686238c5559f2_B - Complex 1.jpg', 1, '2025-07-05 07:07:48', '2025-07-05 07:07:48'),
('step-6868cf44a9233-719e5989-589a-11f', '719e5989-589a-11f0-8cbc-f439091252f6', 2, 'Shake Well', 'Shake vigorously for 30 seconds until the powder is completely dissolved and mixed.', 'assets/how-to-use/686238c555f2c_B - Complex 2.jpg', 1, '2025-07-05 07:07:48', '2025-07-05 07:07:48'),
('step-6868cf44a9823-719e5989-589a-11f', '719e5989-589a-11f0-8cbc-f439091252f6', 3, 'Consume Immediately', 'Drink the mixture immediately after preparation for optimal absorption and effectiveness.', 'assets/how-to-use/686238c556100_B - Complex 3.jpg', 1, '2025-07-05 07:07:48', '2025-07-05 07:07:48'),
('step-6868cf44a9dc1-719e5989-589a-11f', '719e5989-589a-11f0-8cbc-f439091252f6', 4, 'Best Time to Take', 'Take 30 minutes before workout or as directed by your healthcare professional.', 'assets/how-to-use/686238c5562b2_B - Complex 4.jpg', 1, '2025-07-05 07:07:48', '2025-07-05 07:07:48');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `variant_id` char(36) NOT NULL,
  `product_id` char(36) DEFAULT NULL,
  `size` varchar(20) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `price_modifier` decimal(10,2) DEFAULT '0.00',
  `stock` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`variant_id`, `product_id`, `size`, `color`, `price_modifier`, `stock`) VALUES
('042e079e68ed2fb05cf24aad0e9c5336', '502e7eb4e3859af1bad9ee52b0c885a8', '60 TABLETS ', NULL, 770.00, 10),
('047b7cc2c8b85d2e774f2fce972f9423', '7025538976de53a9befc304843f98d09', '120 TABLETS', NULL, 845.00, 10),
('06b4516eb931dd1a90845f6c245089d4', '07240ca09e34ce3dc9500eb0f0feea7f', '60 TABLETS ', NULL, 1400.00, 10),
('0802648b3f95d86d01754a34bb52b751', '92830a6d94c1dec07e04b409b8e2c2ad', '90', NULL, 1375.00, 20),
('1cda1d96e14e88ced9f3113195c04bbf', 'c45ad538dfe0e578238571a28cc5b602', '120', NULL, 650.00, 100),
('1dd8c30a1541addb07da05ba8dc4f05a', '889e7987aaf93ebbcbdff0e309af6565', ' 120 TABLETS', NULL, 1530.00, 10),
('2eaf7a80302aa08b8e7901d76df2ea4f', 'c772841e240a8abe59760e8effa52ada', '150 TABLETS  ', NULL, 995.00, 10),
('2f1f63633659988332a31b748b66197a', '8e621e91b8132a0291e21b82640c0498', '180 TABLETS', NULL, 1065.00, 10),
('3202cdd371cb8751ec797df11192a3f3', 'eca5b9abdc6b5613704b83f20c1223e8', '120 TABLETS', NULL, 1075.00, 10),
('4894770a1a09783c669923e013e2358c', '22e337976e401272877b7efc0c0b6aab', '90 TABLETS', NULL, 920.00, 16),
('56ce3c0282025df7277f2c928a81bb01', 'b707c95da7361342a89ac017588a9c3e', '60 TABLETS ', NULL, 920.00, 10),
('61d4e77f271e5bf1795a4a9e679177fd', '0b18a826111bd18731e5d42cc503a7ce', '120', NULL, 920.00, 10),
('64dc386131fe64592febd49cde172bb0', 'e0f37ea477c50964dce63e821adc6051', '60 TABLETS ', NULL, 890.00, 10),
('75a43a30ff15324cb05841271edca640', '719e4de8-589a-11f0-8cbc-f439091252f6', '90g', NULL, 749.00, 20),
('7e8385a2274520497d6ebd0bd2bbc7fa', 'd59cf2ab0212266abec523726b502969', '120', NULL, 495.00, 10),
('8a59e64f831f09f7c019c921c55ed8be', 'be03ff851681b2480a85ef4833fc39c8', '180 TABLETS', NULL, 1400.00, 0),
('8c8313eb039a171471c0f8e4b413be63', '89787f755db7e678f4fbb777df77c09b', '120', NULL, 770.00, 10),
('90dc765644a7bcfaee3db7101e79a585', '594fd8a912b127f9187d4e03795097cd', '1kg', NULL, 1699.00, 10),
('96d62f9a8d1ec6b181face5a0cf05954', '3e68d4edae4deec4f93e294c9a0104dd', '90 TABLETS', NULL, 1075.00, 20),
('9b291740943361cb4c051706900b4866', '2494ee2d98944a9f65015a9785c63f92', '180 TABLETS', NULL, 770.00, 10),
('aabce5ddfce6b8741a17c4d047025066', '594fd8a912b127f9187d4e03795097cd', '3kg', NULL, 4199.00, 10),
('c5a5ea044f122e6d473a8d9ba34cada3', '0e26db87d6e1916374a91b8ca743eac7', '90 TABLETS', NULL, 1460.00, 10),
('c8480a6ec1f8ae5b98b0f99d37dc4e9c', 'f4c37d2802bad30f3fe9806bee85c92b', '120 TABLETS', NULL, 770.00, 10),
('ce5692d1eb12674a0193f6e09f5970ba', 'ea35ca5c59f92e74d18cbafe850364b3', '120 TABLETS', NULL, 980.00, 11),
('e71b526051ddcd306e09f76aaa6d5cc5', '594fd8a912b127f9187d4e03795097cd', '5kg', NULL, 6299.00, 10),
('ec8d97cd0ab8480f933fb9859045abfc', 'f9cd6a64cae37796eb4516f08348a207', '60 TABLETS ', NULL, 1065.00, 10),
('f75dc41de7e56cad0cacf005f77111c5', 'dae17aaddcd31552010904a650127005', '100 TABLETS', NULL, 770.00, 10),
('ff367fad38dd266e998d98e2205d5a04', 'f05ed8d948f1c4f6e33fc710c221cea9', '30 TABLETS', NULL, 920.00, 10);

-- --------------------------------------------------------

--
-- Table structure for table `quotation_requests`
--

CREATE TABLE `quotation_requests` (
  `quote_id` char(36) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `product_id` char(36) DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `message` text,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `returned_products`
--

CREATE TABLE `returned_products` (
  `return_id` char(36) NOT NULL,
  `order_item_id` char(36) DEFAULT NULL,
  `user_id` char(36) DEFAULT NULL,
  `reason` text,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` char(36) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `product_id` char(36) DEFAULT NULL,
  `rating` int DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `content` text,
  `is_approved` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipping_methods`
--

CREATE TABLE `shipping_methods` (
  `method_id` char(36) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text,
  `base_price` decimal(10,2) DEFAULT NULL,
  `estimated_days` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sub_category`
--

CREATE TABLE `sub_category` (
  `category_id` char(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `parent_id` char(36) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sub_category`
--

INSERT INTO `sub_category` (`category_id`, `name`, `parent_id`, `description`, `created_at`) VALUES
('67f85013-589a-11f0-8cbc-f439091252f6', 'Gainer', NULL, 'Mass and weight gain supplements - Available in various sizes', '2025-07-04 05:47:44'),
('67f8540e-589a-11f0-8cbc-f439091252f6', 'Pre-Workout', NULL, 'Pre-workout supplements and energizers - Available in various sizes', '2025-07-04 05:47:44'),
('67f8553d-589a-11f0-8cbc-f439091252f6', 'Tablets', NULL, 'Medicine tablets and capsules - Available in different quantities', '2025-07-04 05:47:44'),
('7199a18d-589a-11f0-8cbc-f439091252f6', 'Protein', NULL, 'Nutritional and workout supplements', '2025-07-04 05:48:00');

-- --------------------------------------------------------

--
-- Table structure for table `supplement_categories`
--

CREATE TABLE `supplement_categories` (
  `category_id` char(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `supplement_categories`
--

INSERT INTO `supplement_categories` (`category_id`, `name`, `description`, `created_at`) VALUES
('7f595dff-589a-11f0-8cbc-f439091252f6', 'Mass Gainers', 'Supplements designed for muscle mass gain', '2025-07-04 05:48:23'),
('7f5960c9-589a-11f0-8cbc-f439091252f6', 'Whey Protein', 'Pure protein supplements for muscle recovery', '2025-07-04 05:48:23'),
('7f5961dc-589a-11f0-8cbc-f439091252f6', 'Pre-Workout', 'Energy and focus boosting supplements', '2025-07-04 05:48:23'),
('7f59624d-589a-11f0-8cbc-f439091252f6', 'BCAA', 'Branch Chain Amino Acids for muscle recovery', '2025-07-04 05:48:23'),
('7f5962b2-589a-11f0-8cbc-f439091252f6', 'Creatine', 'Strength and performance enhancement supplements', '2025-07-04 05:48:23'),
('7f596317-589a-11f0-8cbc-f439091252f6', 'Weight Loss', 'Fat burning and weight management supplements', '2025-07-04 05:48:23'),
('7f596376-589a-11f0-8cbc-f439091252f6', 'Amino Acids', 'Essential amino acids for muscle growth', '2025-07-04 05:48:23'),
('7f5963d3-589a-11f0-8cbc-f439091252f6', 'Protein Bars', 'Protein-rich snack bars', '2025-07-04 05:48:23');

-- --------------------------------------------------------

--
-- Table structure for table `supplement_details`
--

CREATE TABLE `supplement_details` (
  `detail_id` char(36) NOT NULL,
  `product_id` char(36) DEFAULT NULL,
  `weight_value` decimal(10,2) DEFAULT NULL,
  `weight_unit` enum('g','kg','lb','oz') DEFAULT NULL,
  `servings_per_container` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tablets`
--

CREATE TABLE `tablets` (
  `tablet_id` char(36) NOT NULL,
  `product_id` char(36) DEFAULT NULL,
  `os` varchar(50) DEFAULT NULL,
  `ram` varchar(50) DEFAULT NULL,
  `storage` varchar(50) DEFAULT NULL,
  `battery` varchar(50) DEFAULT NULL,
  `screen_size` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` char(36) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` text NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `wishlist_id` char(36) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `product_id` char(36) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `banner_images`
--
ALTER TABLE `banner_images`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `best_sellers`
--
ALTER TABLE `best_sellers`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `blogs`
--
ALTER TABLE `blogs`
  ADD PRIMARY KEY (`blog_id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `blog_categories`
--
ALTER TABLE `blog_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `blog_comments`
--
ALTER TABLE `blog_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `idx_post_status` (`post_id`,`status`);

--
-- Indexes for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_published_at` (`published_at`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_featured` (`is_featured`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indexes for table `checkout_orders`
--
ALTER TABLE `checkout_orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `address_id` (`address_id`),
  ADD KEY `coupon_id` (`coupon_id`),
  ADD KEY `shipping_method_id` (`shipping_method_id`);

--
-- Indexes for table `collection_products`
--
ALTER TABLE `collection_products`
  ADD PRIMARY KEY (`collection_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`coupon_id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `featured_collections`
--
ALTER TABLE `featured_collections`
  ADD PRIMARY KEY (`collection_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Indexes for table `payment_gateway_logs`
--
ALTER TABLE `payment_gateway_logs`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `sr_no` (`sr_no`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_details`
--
ALTER TABLE `product_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD UNIQUE KEY `product_id` (`product_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_usage_steps`
--
ALTER TABLE `product_usage_steps`
  ADD PRIMARY KEY (`step_id`),
  ADD UNIQUE KEY `unique_product_step` (`product_id`,`step_number`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`variant_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `quotation_requests`
--
ALTER TABLE `quotation_requests`
  ADD PRIMARY KEY (`quote_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `returned_products`
--
ALTER TABLE `returned_products`
  ADD PRIMARY KEY (`return_id`),
  ADD KEY `order_item_id` (`order_item_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `shipping_methods`
--
ALTER TABLE `shipping_methods`
  ADD PRIMARY KEY (`method_id`);

--
-- Indexes for table `sub_category`
--
ALTER TABLE `sub_category`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `supplement_categories`
--
ALTER TABLE `supplement_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `idx_supplement_category` (`name`);

--
-- Indexes for table `supplement_details`
--
ALTER TABLE `supplement_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD UNIQUE KEY `product_id` (`product_id`),
  ADD KEY `idx_supplement_weight` (`weight_value`,`weight_unit`);

--
-- Indexes for table `tablets`
--
ALTER TABLE `tablets`
  ADD PRIMARY KEY (`tablet_id`),
  ADD UNIQUE KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `banner_images`
--
ALTER TABLE `banner_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `sr_no` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `best_sellers`
--
ALTER TABLE `best_sellers`
  ADD CONSTRAINT `best_sellers_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `blogs`
--
ALTER TABLE `blogs`
  ADD CONSTRAINT `blogs_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `admin_users` (`admin_id`);

--
-- Constraints for table `blog_comments`
--
ALTER TABLE `blog_comments`
  ADD CONSTRAINT `blog_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`post_id`) ON DELETE CASCADE;

--
-- Constraints for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD CONSTRAINT `blog_posts_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `blog_posts_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `admin_users` (`admin_id`) ON DELETE SET NULL;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `cart_items_ibfk_3` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`variant_id`);

--
-- Constraints for table `checkout_orders`
--
ALTER TABLE `checkout_orders`
  ADD CONSTRAINT `checkout_orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `checkout_orders_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`address_id`),
  ADD CONSTRAINT `checkout_orders_ibfk_3` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`coupon_id`),
  ADD CONSTRAINT `checkout_orders_ibfk_4` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_methods` (`method_id`);

--
-- Constraints for table `collection_products`
--
ALTER TABLE `collection_products`
  ADD CONSTRAINT `collection_products_ibfk_1` FOREIGN KEY (`collection_id`) REFERENCES `featured_collections` (`collection_id`),
  ADD CONSTRAINT `collection_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD CONSTRAINT `contact_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `checkout_orders` (`order_id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`variant_id`);

--
-- Constraints for table `payment_gateway_logs`
--
ALTER TABLE `payment_gateway_logs`
  ADD CONSTRAINT `payment_gateway_logs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `checkout_orders` (`order_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `sub_category` (`category_id`);

--
-- Constraints for table `product_details`
--
ALTER TABLE `product_details`
  ADD CONSTRAINT `product_details_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `product_usage_steps`
--
ALTER TABLE `product_usage_steps`
  ADD CONSTRAINT `product_usage_steps_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `quotation_requests`
--
ALTER TABLE `quotation_requests`
  ADD CONSTRAINT `quotation_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `quotation_requests_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `returned_products`
--
ALTER TABLE `returned_products`
  ADD CONSTRAINT `returned_products_ibfk_1` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`order_item_id`),
  ADD CONSTRAINT `returned_products_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `sub_category`
--
ALTER TABLE `sub_category`
  ADD CONSTRAINT `sub_category_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `sub_category` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `supplement_details`
--
ALTER TABLE `supplement_details`
  ADD CONSTRAINT `supplement_details_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `tablets`
--
ALTER TABLE `tablets`
  ADD CONSTRAINT `tablets_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `wishlists_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
