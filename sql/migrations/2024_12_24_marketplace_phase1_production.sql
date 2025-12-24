-- ============================================================================
-- MARKETPLACE MVP - PRODUCTION DEPLOYMENT SQL
-- ============================================================================
-- Database: CORE (fleet_management sau u123456_fleetmanagement pe Hostico)
-- Safe for production: Uses IF NOT EXISTS, no DROP statements
-- Can be run multiple times safely
-- ============================================================================

-- Version check
SELECT 'Starting Marketplace Production Installation...' as status;

-- ============================================================================
-- TABLE CREATION (SAFE - IF NOT EXISTS)
-- ============================================================================

-- Categories Table
CREATE TABLE IF NOT EXISTS `mp_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_active` (`is_active`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products Table
CREATE TABLE IF NOT EXISTS `mp_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `sku` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `specifications` text DEFAULT NULL COMMENT 'JSON pentru specificații tehnice',
  `price` decimal(12,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'RON',
  `stock_quantity` int(11) DEFAULT 0 COMMENT 'Pentru viitor - tracking stoc',
  `image_main` varchar(255) DEFAULT NULL,
  `image_gallery` text DEFAULT NULL COMMENT 'JSON array cu imagini suplimentare',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_featured` (`is_featured`),
  KEY `idx_slug` (`slug`),
  FULLTEXT KEY `idx_search` (`name`,`description`),
  CONSTRAINT `mp_products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `mp_categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cart Table
CREATE TABLE IF NOT EXISTS `mp_cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(12,2) NOT NULL COMMENT 'Price snapshot la momentul adăugării',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cart_item` (`company_id`,`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `mp_cart_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mp_cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `mp_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders Table
CREATE TABLE IF NOT EXISTS `mp_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `company_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','confirmed','processing','completed','cancelled') DEFAULT 'pending',
  `subtotal` decimal(12,2) NOT NULL,
  `tax_rate` decimal(5,2) DEFAULT 19.00 COMMENT 'TVA %',
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL,
  `delivery_address` text DEFAULT NULL,
  `delivery_notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `company_id` (`company_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`),
  KEY `idx_order_number` (`order_number`),
  CONSTRAINT `mp_orders_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `mp_orders_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Items Table
CREATE TABLE IF NOT EXISTS `mp_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_sku` varchar(100) DEFAULT NULL,
  `product_description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_product` (`product_id`),
  CONSTRAINT `mp_order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `mp_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mp_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `mp_products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Tables created successfully!' as status;

-- ============================================================================
-- SEED DATA - Categories (Only if table is empty)
-- ============================================================================

INSERT IGNORE INTO `mp_categories` (`id`, `name`, `slug`, `description`, `icon`, `sort_order`)
VALUES
(1, 'Asigurări', 'asigurari', 'Asigurări RCA, CASCO și alte tipuri pentru flotă', 'shield-check', 1),
(2, 'Roviniete', 'roviniete', 'Roviniete electronice pentru țări europene', 'ticket-perforated', 2),
(3, 'Anvelope', 'anvelope', 'Anvelope pentru orice tip de vehicul din flotă', 'circle', 3),
(4, 'Piese Auto', 'piese-auto', 'Piese de schimb și accesorii auto', 'tools', 4);

SELECT 'Categories seeded!' as status;

-- ============================================================================
-- SEED DATA - Products (Only if table is empty)
-- ============================================================================

-- Asigurări
INSERT IGNORE INTO `mp_products` (`id`, `category_id`, `sku`, `name`, `slug`, `description`, `specifications`, `price`, `is_featured`)
VALUES
(1, 1, 'RCA-FLEET-2025', 'RCA Flotă - Asigurare Obligatorie', 'rca-flota-2025', 
 'Asigurare RCA pentru vehicule din flotă. Acoperire conform legislației române. Gestiune online simplificată, documente electronic. Prețuri speciale pentru flote peste 10 vehicule.',
 '{"valabilitate": "12 luni", "acoperire": "Daune terți conform legislație", "livrare": "Instant - certificat electronic", "suport": "Telefonic și email"}',
 450.00, 1),

(2, 1, 'CASCO-FLEET-FULL', 'CASCO Flotă - Asigurare Completă', 'casco-flota-completa',
 'Asigurare CASCO completă pentru protecție maximă. Acoperă daune totale, furt, incendiu, vandalism, calamități naturale. Franșiză negociabilă în funcție de istoric.',
 '{"valabilitate": "12 luni", "acoperire": "Daune totale, furt, incendiu, vandalism, calamități", "franșiza": "Negociabilă 500-2000 EUR", "asistenţă": "24/7 Rutieră inclusă"}',
 1200.00, 1),

(3, 1, 'RCA-CAMION-2025', 'RCA Camioane și Autoutilitare', 'rca-camioane',
 'RCA special pentru camioane și autoutilitare peste 3.5 tone. Acoperire conform legislației pentru transport comercial.',
 '{"valabilitate": "12 luni", "tonaj": "Până la 40 tone", "livrare": "Instant - certificat electronic"}',
 680.00, 0);

-- Roviniete
INSERT IGNORE INTO `mp_products` (`id`, `category_id`, `sku`, `name`, `slug`, `description`, `specifications`, `price`, `is_featured`)
VALUES
(4, 2, 'ROV-HU-12M', 'Rovinieta Ungaria - 12 Luni', 'rovinieta-ungaria-12-luni',
 'Rovinieta electronică Ungaria valabilă 12 luni pentru toate categoriile de vehicule. Livrare instant prin email. Valabilă pentru toate drumurile naționale din Ungaria.',
 '{"valabilitate": "365 zile", "categorii": "Toate (D1, D2, B2, U)", "livrare": "Instant - cod electronic", "activare": "Automat în maxim 15 minute"}',
 180.00, 1),

(5, 2, 'ROV-BG-12M', 'Rovinieta Bulgaria - 12 Luni', 'rovinieta-bulgaria-12-luni',
 'Rovinieta electronică Bulgaria (e-vinieta) valabilă 12 luni. Acceptată la toate punctele de control. Livrare instant.',
 '{"valabilitate": "365 zile", "categorii": "Cat 3 (până la 12t)", "livrare": "Instant - certificat PDF", "verificare": "Online pe bgtoll.bg"}',
 150.00, 1),

(6, 2, 'ROV-RO-12M', 'Rovinieta România - 12 Luni', 'rovinieta-romania-12-luni',
 'Rovinieta electronică pentru drumurile naționale din România. Valabilă 12 luni pentru toate categoriile.',
 '{"valabilitate": "365 zile", "categorii": "Toate categoriile de vehicule", "livrare": "Instant prin SMS și email", "rețea": "Toate drumurile naționale"}',
 96.00, 0),

(7, 2, 'ROV-AT-12M', 'Vinieta Austria - 12 Luni', 'vinieta-austria-12-luni',
 'Vinieta digitală Austria valabilă 12 luni pentru autostrada austriacă. Alternativă la vinieta fizică.',
 '{"valabilitate": "365 zile", "categorii": "Vehicule până la 3.5t", "livrare": "Instant - confirmare email", "rețea": "Toate autostrăzile și drumuri expres"}',
 96.40, 0);

-- Anvelope
INSERT IGNORE INTO `mp_products` (`id`, `category_id`, `sku`, `name`, `slug`, `description`, `specifications`, `price`, `is_featured`)
VALUES
(8, 3, 'TIRE-MICHELIN-205-55-R16', 'Michelin Primacy 4 - 205/55 R16', 'michelin-primacy-4-205-55-r16',
 'Anvelope premium Michelin pentru sedanuri și SUV-uri compacte. Performanță excelentă pe umed, consum redus, durabilitate superioară.',
 '{"dimensiune": "205/55 R16 91V", "sezon": "Vară", "indice_viteza": "V (240 km/h)", "indice_sarcina": "91 (615 kg)", "rezistenta": "A", "aderenta_umed": "B", "zgomot": "69 dB"}',
 420.00, 1),

(9, 3, 'TIRE-CONTINENTAL-195-65-R15', 'Continental ContiEcoContact - 195/65 R15', 'continental-eco-195-65-r15',
 'Anvelope economice pentru flotă cu focus pe eficiență. Rezistență redusă la rulare, durată lungă de viață.',
 '{"dimensiune": "195/65 R15 91H", "sezon": "Vară", "indice_viteza": "H (210 km/h)", "eficiență": "A", "aderenta": "B", "garantie": "5 ani"}',
 340.00, 0),

(10, 3, 'TIRE-WINTER-NOKIAN-205-55-R16', 'Nokian WR Snowproof - 205/55 R16', 'nokian-winter-205-55-r16',
 'Anvelope de iarnă premium pentru condiții severe. Aderență maximă pe zăpadă și gheață, comportament stabil.',
 '{"dimensiune": "205/55 R16 94H XL", "sezon": "Iarnă", "omologare": "3PMSF + M+S", "test_zapada": "Excelent", "durata": "50.000 km"}',
 480.00, 1);

-- Piese Auto
INSERT IGNORE INTO `mp_products` (`id`, `category_id`, `sku`, `name`, `slug`, `description`, `specifications`, `price`, `is_featured`)
VALUES
(11, 4, 'FILTER-OIL-BOSCH-0451103336', 'Filtru Ulei Bosch 0451103336', 'filtru-ulei-bosch-universal',
 'Filtru ulei universal Bosch compatibil cu majoritatea vehiculelor din flotă. Calitate OE, filtrare superioară.',
 '{"tip": "Filtru ulei motor", "brand": "Bosch", "compatibilitate": "Universal - VW, Audi, Skoda, Seat", "cod_oem": "0451103336", "garantie": "2 ani"}',
 28.50, 0),

(12, 4, 'BRAKE-PAD-ATE-13-0460-7240', 'Placute Frana ATE Ceramic', 'placute-frana-ate-ceramic',
 'Placuțe frână ceramice ATE pentru durabilitate maximă. Zgomot redus, performanță constantă, uzură minimă.',
 '{"tip": "Placuțe frână față", "material": "Ceramic", "brand": "ATE", "garantie": "3 ani / 50.000 km", "avantaje": "Fără praf, silențioase"}',
 185.00, 1),

(13, 4, 'WIPER-BOSCH-AEROTWIN-24', 'Stergatoare Bosch Aerotwin 60cm', 'stergatoare-bosch-aerotwin',
 'Ștergătoare premium Bosch Aerotwin tehnologie flat blade. Ștergere perfectă, durată lungă, montaj rapid.',
 '{"lungime": "60 cm (24 inch)", "tip": "Flat blade", "brand": "Bosch", "compatibilitate": "Universal hook", "durata": "12 luni"}',
 65.00, 0),

(14, 4, 'BATTERY-VARTA-BLUE-60AH', 'Baterie Auto Varta Blue Dynamic 60Ah', 'baterie-varta-60ah',
 'Baterie auto Varta 60Ah pentru vehicule mici și medii din flotă. Tehnologie AGM, pornire la rece excelentă.',
 '{"capacitate": "60 Ah", "curent_pornire": "540 A", "tehnologie": "AGM", "dimensiuni": "242x175x190mm", "garantie": "2 ani"}',
 385.00, 1);

SELECT 'Products seeded!' as status;

-- ============================================================================
-- VERIFICATION
-- ============================================================================

SELECT 'Installation verification:' as status;
SELECT COUNT(*) as categories_count FROM mp_categories;
SELECT COUNT(*) as products_count FROM mp_products;

-- ============================================================================
-- COMPLETION MESSAGE
-- ============================================================================

SELECT '✅ Marketplace Production Installation Complete!' as status;
SELECT 'Database: CORE (fleet_management)' as info;
SELECT '5 tables created, 4 categories, 14 products' as summary;
SELECT 'Next: Test at /modules/marketplace/test-installation.php' as next_step;
