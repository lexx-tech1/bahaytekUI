-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 17, 2026 at 06:36 AM
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
-- Database: `bahaytek_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `consultations`
--

CREATE TABLE `consultations` (
  `id` int(10) UNSIGNED NOT NULL,
  `client_name` varchar(160) NOT NULL,
  `email` varchar(160) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `service` varchar(120) NOT NULL,
  `date` date DEFAULT NULL,
  `time` varchar(40) DEFAULT NULL,
  `topic` text DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL COMMENT 'internal admin notes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `consultations`
--

INSERT INTO `consultations` (`id`, `client_name`, `email`, `phone`, `service`, `date`, `time`, `topic`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Maria Santos', 'maria.santos@gmail.com', '09171234567', 'Product Development', '2025-05-15', '10:00 AM', 'Biogas system for small farm', 'confirmed', NULL, '2026-05-17 03:49:21', '2026-05-17 03:49:21'),
(2, 'Juan dela Cruz', 'juan.delacruz@gmail.com', '09221234567', 'Research', '2025-05-16', '2:00 PM', 'Solar dryer efficiency study', 'pending', NULL, '2026-05-17 03:49:21', '2026-05-17 03:49:21'),
(3, 'Ana Reyes', 'ana.reyes@gmail.com', '09301234567', 'Consultancy', '2025-05-17', '9:00 AM', 'Floating garden for coastal community', 'confirmed', NULL, '2026-05-17 03:49:21', '2026-05-17 03:49:21'),
(4, 'Pedro Villanueva', 'pedro.v@gmail.com', '09151234567', 'Training & Workshops', '2025-05-20', '1:00 PM', 'Group training for barangay officials', 'pending', NULL, '2026-05-17 03:49:21', '2026-05-17 03:49:21'),
(5, 'Liza Corpuz', 'liza.c@gmail.com', '09181234567', 'Product Development', '2025-05-22', '11:00 AM', 'Custom brick stove design', 'cancelled', NULL, '2026-05-17 03:49:21', '2026-05-17 03:49:21');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `client_name` varchar(160) NOT NULL,
  `email` varchar(160) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(160) NOT NULL COMMENT 'snapshot of product name at time of order',
  `price` decimal(10,2) NOT NULL,
  `quantity` smallint(5) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(160) NOT NULL,
  `category` enum('Solar','Water','Biogas','Cooking','Garden','Kiln','Fuel','Tools','Compost') NOT NULL,
  `icon` varchar(10) NOT NULL DEFAULT '?',
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('in','low','out') NOT NULL DEFAULT 'in',
  `image_file` varchar(255) DEFAULT NULL COMMENT 'filename inside BahayTek Inventions/ folder',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `icon`, `description`, `price`, `stock`, `status`, `image_file`, `created_at`, `updated_at`) VALUES
(1, 'Activated Carbon Water Filter Loop', 'Water', '🏺', 'Clay pot gravity water filter with activated carbon filtration loop. Removes contaminants through natural charcoal media. Comes with stainless steel stand and tap. No electricity needed.', 2800.00, 7, 'in', 'Fuel Stove Activated Carbon Water Filter Loop.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(2, 'Orchid Poles from Waste Biomass', 'Garden', '🌿', 'Upcycled biomass orchid growing poles. Compressed organic waste material provides ideal substrate for orchid root attachment. Comes with PVC core and cement base for stability.', 380.00, 20, 'in', 'Orchid Poles from Waste Biomass Materials.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(3, 'Parabolic Solar Dish Cooker', 'Solar', '🔆', 'Hexagonal mirror-tile parabolic dish solar cooker. Focuses sunlight to 300°C+ focal point. Mounted on upcycled car rim and tire base for stability. Adjustable pot support arm included.', 4500.00, 5, 'in', 'Parabolic Solar Dish Cooker.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(4, 'PizzaHex Oven', 'Cooking', '🍕', 'Hexagonal interlocking brick pizza and baking oven. Fan-assisted combustion for efficient heat. Reaches 250°C+ oven temperatures. Compact footprint. Built-in stainless steel frame. Perfect for bakeries and food businesses.', 6800.00, 3, 'low', 'PizzaHex Oven.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(5, 'Pocket Garden Barrel', 'Garden', '🪴', 'Repurposed 60L drum converted into a multi-pocket vertical garden. 20+ planting pockets around the barrel. Ideal for pechay, herbs, and strawberries. Integrated irrigation tube and drainage. Maximizes small urban spaces.', 1200.00, 15, 'in', 'Pocket Garden.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(6, 'Seedling Briquettes', 'Garden', '🌱', 'Compressed organic soil briquettes for seedling propagation. Dense yet breathable structure encourages healthy root development. Biodegradable — plant directly into the ground. Available in packs of 10.', 180.00, 50, 'in', 'Seedling Briquettes.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(7, 'Solar Food Dehydrator Cabinet', 'Solar', '🌞', 'Large-capacity solar-assisted food dehydration cabinet. Black-body heat absorption with ventilation. Compatible with wood-fired heat assist for cloudy days. Ideal for drying fish, fruits, and herbs commercially.', 7500.00, 2, 'low', 'Solar Food Dehydrator.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(8, 'Brick Box Kiln', 'Kiln', '🧱', 'Arched red-brick kiln for firing ceramics and clay products. Tunnel vault design retains heat efficiently. Wood or biomass fuel. Suitable for small-scale pottery, tile, and biochar production. Reaches 900°C+.', 18500.00, 2, 'low', 'The Brick Box Kiln.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(9, 'PapaBrick Stove', 'Cooking', '🔥', 'Heavy-duty cylindrical biomass combustion stove. Insulated clay lining inside steel drum. Rocket stove principle with side fuel inlet. Produces tall, clean flame. Ideal for large-batch cooking or wok cooking.', 2900.00, 8, 'in', 'The PapaBrick Stove Kitchen.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(10, 'Solar Box Oven', 'Solar', '☀️', 'Flat-panel reflector solar box oven. Multi-panel reflective walls concentrate sunlight into insulated baking chamber. Glass lid retains heat. Reaches 120–150°C. Ideal for baking bread, rice, and slow-cooking.', 3200.00, 6, 'in', 'The Solar Box Oven 2.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(11, 'Stove Truck (Mobile Brick Stove)', 'Cooking', '🚚', 'Compact portable brick cooking stove with metal frame stand. Designed for street vendors and mobile kitchens. Efficient combustion chamber with adjustable air intake. Supports large kawali or kawa. Easy to set up and move.', 3500.00, 6, 'in', 'The Stove Truck 2.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(12, 'Terra Cotta Micro Oven', 'Cooking', '🏺', 'Hand-built terra cotta micro oven with arched baking chamber. Integrates with a rocket stove base. Ideal for baking pan de sal, cookies, and small pastries. Made from locally sourced clay. Comes with stainless steel cage support.', 4200.00, 4, 'in', 'The Terra Cotta Micro Oven.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(13, 'Brick InStove for Braising', 'Cooking', '🫕', 'Cylindrical brick rocket stove specifically designed for slow braising and clay pot cooking. Top-loading fuel. Supports heavy palayok and cast-iron pots. Stainless steel pot rest included. Fuel-efficient design.', 3100.00, 9, 'in', 'Brick InStove for Braising.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(14, 'Floating Garden System', 'Garden', '🛶', 'Flood-resilient floating garden platform. Built from upcycled plastic bottles and lumber frame with polycarbonate greenhouse cover. Grow vegetables during flooding. Buoyant and stable. Ideal for flood-prone barangays.', 5800.00, 3, 'low', 'Floating Garden.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(15, 'Solar Dryer in Wings', 'Solar', '🦋', 'Wing-panel solar dryer with multi-shelf drying trays. Foldable reflective side panels maximize solar exposure. Transparent polycarbonate roof. Dries fish, rice, herbs, and spices efficiently. Portable and weather-resistant.', 3900.00, 5, 'in', 'Solar Dryer in Wings.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(16, 'Solar Tyre Oven', 'Solar', '🔆', 'Large parabolic solar concentrator built from foil-coated insulation and an upcycled car tire base. Deep concave dish focuses intense solar heat. Ideal for boiling, steaming, and frying. No fuel cost. DIY-buildable design.', 2200.00, 8, 'in', 'Solar Tyre Oven.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(17, 'Stove Lantern', 'Cooking', '🏮', 'Biomass combustion stove with glass-chimney lantern design. Transparent glass cylinder channels heat upward for clean, efficient combustion. Doubles as ambient lighting at night. Steel drum base with side fuel inlet.', 3400.00, 7, 'in', 'Stove Lantern.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(18, 'Stove Rook', 'Cooking', '♜', 'Castle-tower-shaped multi-tier clay rocket stove. Stacked interlocking sections with multiple air-intake holes. Stainless steel safety cage. Burns cleanly with small biomass fuel. Distinctive design for any kitchen.', 4800.00, 4, 'in', 'Stove Rook.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(19, 'The 4n2 Stove Oven', 'Cooking', '🔥', 'Barrel-shaped clay-and-sand cob oven with integrated rocket stove base. Arched baking chamber sealed with adobe insulation. Reaches 300°C+. Ideal for baking bread, pizza, and roasting meats.', 5500.00, 3, 'low', 'The 4n2 Stove Oven.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(20, 'Holey Briquettes (Biomass)', 'Fuel', '⭕', 'Ring/donut-shaped compressed biomass briquettes. Center hole improves airflow for more complete combustion. Made from agricultural waste — rice husk, coconut coir, or paper pulp. Burns longer and cleaner than raw firewood. Pack of 6.', 120.00, 100, 'in', 'The Holey Briquettes.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(21, 'Tablet Briquettes (Charcoal)', 'Fuel', '🔲', 'Compact cylindrical charcoal briquettes with center hole for maximum airflow. Charred from local biomass waste. Longer burn time than commercial charcoal. Pack of 10 tablets. Ideal for compact stoves.', 95.00, 150, 'in', 'The Tablet Briquettes.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(22, 'Holey Rocket Stove', 'Cooking', '🔥', 'Compact open-top clay rocket stove with characteristic holey body for maximum draft and combustion air. Hand-sculpted terracotta with built-in fuel shelf. Clean-burning with small sticks or briquettes. Lightweight and portable.', 1650.00, 12, 'in', 'The Holey Rocket Stove.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(23, 'Peanut Tumbler Roaster', 'Cooking', '🥜', 'Rotating drum roaster for peanuts, coffee, and cacao. Stainless steel tumbler barrel on motorized axle. Heat from attached biomass stove circulates evenly. Motor-driven rotation ensures even roasting. Ideal for small food processors.', 6200.00, 4, 'in', 'The Peanut Tumbler Roaster.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(24, 'Stove Fish', 'Cooking', '🐟', 'Artisan fish-shaped terra cotta rocket stove. Sculpted body of a fish forms the combustion chamber and fuel intake. Functional art piece — cooks efficiently while serving as a decorative outdoor stove. Handmade from local clay.', 3800.00, 5, 'in', 'The Stove Fish.jpg', '2026-05-17 03:49:20', '2026-05-17 03:49:20');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(10) UNSIGNED NOT NULL,
  `icon` varchar(10) NOT NULL DEFAULT '?',
  `title` varchar(120) NOT NULL,
  `description` text NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `icon`, `title`, `description`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, '🔬', 'Research', 'Collaborate on studies, experiments, and applied projects focused on sustainable and practical technology for agriculture and environment.', 'active', 1, '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(2, '📋', 'Consultancy', 'Work with our experts to define directions, plan experiments, and receive structured guidance — from problem identification to solution framing.', 'active', 2, '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(3, '⚙️', 'Product Development', 'Turn your ideas into working prototypes. We support design, fabrication, and lab testing of agricultural and appropriate technology products.', 'active', 3, '2026-05-17 03:49:20', '2026-05-17 03:49:20'),
(4, '🎓', 'Training & Workshops', 'Hands-on learning programs in biogas, solar, water systems, composting, and sustainable farming for students, farmers, and communities.', 'active', 4, '2026-05-17 03:49:20', '2026-05-17 03:49:20');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `email` varchar(160) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL COMMENT 'bcrypt hash',
  `provider` enum('email','google','facebook') NOT NULL DEFAULT 'email',
  `provider_id` varchar(255) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `role` enum('customer','trainer','admin') NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `joined_date` date NOT NULL DEFAULT curdate(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone`, `password`, `provider`, `provider_id`, `avatar_url`, `email_verified`, `role`, `status`, `joined_date`, `created_at`, `updated_at`) VALUES
(1, 'Maria', 'Santos', 'maria.santos@gmail.com', '09171234567', NULL, 'email', NULL, NULL, 1, 'customer', 'active', '2025-03-10', '2026-05-17 03:49:20', '2026-05-17 03:49:33'),
(2, 'Juan', 'dela Cruz', 'juan.delacruz@gmail.com', '09221234567', NULL, 'email', NULL, NULL, 1, 'customer', 'active', '2025-03-14', '2026-05-17 03:49:20', '2026-05-17 03:49:33'),
(3, 'Ana', 'Reyes', 'ana.reyes@gmail.com', '09301234567', NULL, 'email', NULL, NULL, 1, 'customer', 'active', '2025-04-01', '2026-05-17 03:49:20', '2026-05-17 03:49:33'),
(4, 'Pedro', 'Villanueva', 'pedro.v@gmail.com', '09151234567', NULL, 'email', NULL, NULL, 1, 'customer', 'active', '2025-04-08', '2026-05-17 03:49:20', '2026-05-17 03:49:33'),
(5, 'Liza', 'Corpuz', 'liza.c@gmail.com', '09181234567', NULL, 'email', NULL, NULL, 1, 'customer', 'inactive', '2025-04-15', '2026-05-17 03:49:20', '2026-05-17 03:49:33'),
(6, 'Ben', 'Reyes', 'ben.reyes@bahaytek.ph', '09251234567', NULL, 'email', NULL, NULL, 1, 'trainer', 'active', '2024-01-01', '2026-05-17 03:49:20', '2026-05-17 03:49:33'),
(7, 'Clara', 'Chua', 'clara.chua@bahaytek.ph', '09261234567', NULL, 'email', NULL, NULL, 1, 'trainer', 'active', '2024-01-01', '2026-05-17 03:49:20', '2026-05-17 03:49:33'),
(8, 'Eduardo', 'Santos', 'eduardo.santos@bahaytek.ph', '09271234567', NULL, 'email', NULL, NULL, 1, 'trainer', 'active', '2024-01-01', '2026-05-17 03:49:20', '2026-05-17 03:49:33'),
(9, 'Jose', 'Bautista', 'jose.bautista@bahaytek.ph', '09281234567', NULL, 'email', NULL, NULL, 1, 'trainer', 'active', '2024-02-15', '2026-05-17 03:49:20', '2026-05-17 03:49:33'),
(10, 'Admin', 'BAHAYTEK', 'admin@bahaytek.ph', '09991234567', NULL, 'email', NULL, NULL, 1, 'admin', 'active', '2024-01-01', '2026-05-17 03:49:20', '2026-05-17 03:49:33');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 30 day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_inventory_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_inventory_summary` (
`category` enum('Solar','Water','Biogas','Cooking','Garden','Kiln','Fuel','Tools','Compost')
,`total_products` bigint(21)
,`total_units` decimal(32,0)
,`inventory_value` decimal(42,2)
,`low_stock_count` decimal(22,0)
,`out_of_stock_count` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_workshop_stats`
-- (See below for the actual view)
--
CREATE TABLE `v_workshop_stats` (
`id` int(10) unsigned
,`title` varchar(160)
,`trainer` varchar(100)
,`status` enum('open','ongoing','full','completed','soon')
,`max_participants` tinyint(3) unsigned
,`enrolled` tinyint(3) unsigned
,`seats_available` int(4) unsigned
,`fill_pct` decimal(8,1)
,`fee` decimal(8,2)
,`revenue_collected` decimal(11,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `workshops`
--

CREATE TABLE `workshops` (
  `id` int(10) UNSIGNED NOT NULL,
  `icon` varchar(10) NOT NULL DEFAULT '?',
  `title` varchar(160) NOT NULL,
  `description` text NOT NULL,
  `date` varchar(60) NOT NULL COMMENT 'display date string',
  `time` varchar(60) NOT NULL DEFAULT 'TBD',
  `duration` varchar(40) NOT NULL DEFAULT '1 day',
  `location` varchar(160) NOT NULL,
  `max_participants` tinyint(3) UNSIGNED NOT NULL DEFAULT 20,
  `enrolled` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `fee` decimal(8,2) NOT NULL DEFAULT 0.00,
  `status` enum('open','ongoing','full','completed','soon') NOT NULL DEFAULT 'open',
  `trainer` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workshops`
--

INSERT INTO `workshops` (`id`, `icon`, `title`, `description`, `date`, `time`, `duration`, `location`, `max_participants`, `enrolled`, `fee`, `status`, `trainer`, `created_at`, `updated_at`) VALUES
(1, '🌿', 'Biogas Digester Construction', 'Build a functional household-scale biogas system from scratch. Covers design, materials sourcing, installation, and safety protocols.', 'May 10, 2025', '8:00 AM – 5:00 PM', '1 day', 'Bahay Teknik Lab A, Camarines Norte', 20, 14, 500.00, 'open', 'Engr. Ben Reyes', '2026-05-17 03:49:21', '2026-05-17 03:49:21'),
(2, '☀️', 'Off-Grid Solar Installation', 'Panel mounting, wiring, battery setup, and charge controller configuration for rural homes and farm sheds.', 'May 17, 2025', '9:00 AM – 4:00 PM', '1 day', 'Bahay Teknik Lab B', 15, 9, 600.00, 'open', 'Engr. Eduardo Santos', '2026-05-17 03:49:21', '2026-05-17 03:49:21'),
(3, '💧', 'Rainwater Harvesting Systems', 'Design and assembly of 3-stage rainwater filtration and storage systems for agricultural and household use.', 'May 24, 2025', '8:00 AM – 1:00 PM', 'Half-day', 'Lab A & B', 18, 18, 400.00, 'full', 'Engr. Ben Reyes', '2026-05-17 03:49:21', '2026-05-17 03:49:21'),
(4, '♻️', 'Composting & Soil Health', 'Practical guide to vermicomposting, organic waste management, and soil amendment for small-scale farms.', 'June 7, 2025', '8:00 AM – 12:00 PM', 'Half-day', 'Bahay Teknik Lab A', 25, 11, 300.00, 'open', 'Dr. Clara Chua', '2026-05-17 03:49:21', '2026-05-17 03:49:21'),
(5, '🌾', 'Precision Irrigation Basics', 'Introduction to drip irrigation, moisture sensing, and water-efficient farming techniques for vegetable growers.', 'June 14, 2025', '9:00 AM – 3:00 PM', '1 day', 'Farm Site, Daet', 20, 5, 450.00, 'open', 'Engr. Jose Bautista', '2026-05-17 03:49:21', '2026-05-17 03:49:21'),
(6, '🔬', 'Appropriate Tech for Agriculture', 'Overview of low-cost, locally-buildable tools for improving farm productivity sustainably.', 'April 5, 2025', '8:00 AM – 5:00 PM', '1 day', 'Bahay Teknik Lab', 20, 20, 400.00, 'completed', 'Engr. Ben Reyes', '2026-05-17 03:49:21', '2026-05-17 03:49:21'),
(7, '🔋', 'Biogas Production for Homes', 'Setting up biogas digesters using organic household waste — a practical multi-day workshop.', 'Apr 1–7, 2025', '8:00 AM – 5:00 PM', '1 week', 'Bahay Teknik Lab', 20, 16, 1200.00, 'ongoing', 'Engr. Ben Reyes', '2026-05-17 03:49:21', '2026-05-17 03:49:21'),
(8, '⚡', 'Green Home Design Principles', 'Passive design, ventilation, and sustainable materials for local housing contexts in Bicol Region.', 'July 2025 – TBD', 'TBD', '2 days', 'Bahay Teknik Lab', 20, 3, 600.00, 'soon', 'Arch. Lim', '2026-05-17 03:49:21', '2026-05-17 03:49:21');

-- --------------------------------------------------------

--
-- Table structure for table `workshop_enrollments`
--

CREATE TABLE `workshop_enrollments` (
  `id` int(10) UNSIGNED NOT NULL,
  `workshop_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(160) NOT NULL,
  `email` varchar(160) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','waitlist') NOT NULL DEFAULT 'pending',
  `paid` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure for view `v_inventory_summary`
--
DROP TABLE IF EXISTS `v_inventory_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_inventory_summary`  AS SELECT `products`.`category` AS `category`, count(0) AS `total_products`, sum(`products`.`stock`) AS `total_units`, sum(`products`.`price` * `products`.`stock`) AS `inventory_value`, sum(case when `products`.`status` = 'low' then 1 else 0 end) AS `low_stock_count`, sum(case when `products`.`status` = 'out' then 1 else 0 end) AS `out_of_stock_count` FROM `products` GROUP BY `products`.`category` ;

-- --------------------------------------------------------

--
-- Structure for view `v_workshop_stats`
--
DROP TABLE IF EXISTS `v_workshop_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_workshop_stats`  AS SELECT `workshops`.`id` AS `id`, `workshops`.`title` AS `title`, `workshops`.`trainer` AS `trainer`, `workshops`.`status` AS `status`, `workshops`.`max_participants` AS `max_participants`, `workshops`.`enrolled` AS `enrolled`, `workshops`.`max_participants`- `workshops`.`enrolled` AS `seats_available`, round(`workshops`.`enrolled` / `workshops`.`max_participants` * 100,1) AS `fill_pct`, `workshops`.`fee` AS `fee`, `workshops`.`enrolled`* `workshops`.`fee` AS `revenue_collected` FROM `workshops` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `consultations`
--
ALTER TABLE `consultations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date` (`date`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_provider_id` (`provider`,`provider_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `workshops`
--
ALTER TABLE `workshops`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `workshop_enrollments`
--
ALTER TABLE `workshop_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workshop_id` (`workshop_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `consultations`
--
ALTER TABLE `consultations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `workshops`
--
ALTER TABLE `workshops`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `workshop_enrollments`
--
ALTER TABLE `workshop_enrollments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workshop_enrollments`
--
ALTER TABLE `workshop_enrollments`
  ADD CONSTRAINT `workshop_enrollments_ibfk_1` FOREIGN KEY (`workshop_id`) REFERENCES `workshops` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workshop_enrollments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
