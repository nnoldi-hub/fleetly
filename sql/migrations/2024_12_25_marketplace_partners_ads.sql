-- ============================================================================
-- MARKETPLACE PARTENERI & RECLAME - SISTEM NOU
-- ============================================================================
-- Sistem simplificat pentru:
-- - Parteneri/Furnizori (piese, cauciucuri, asigurări, roviniete etc.)
-- - Reclame/Linkuri promoționale gestionate de SuperAdmin
-- - Vizibile pentru toți utilizatorii care gestionează flote
-- ============================================================================

SELECT 'Installing Marketplace Partners & Ads System...' as status;

-- ============================================================================
-- CATEGORII PARTENERI
-- ============================================================================
CREATE TABLE IF NOT EXISTS `mp_partner_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'Ex: Piese Auto, Cauciucuri, Asigurări, Roviniete',
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'fa-handshake' COMMENT 'FontAwesome icon class',
  `color` varchar(20) DEFAULT '#007bff' COMMENT 'Culoare pentru badge/card',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_active` (`is_active`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PARTENERI / FURNIZORI
-- ============================================================================
CREATE TABLE IF NOT EXISTS `mp_partners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Numele firmei/partenerului',
  `slug` varchar(255) NOT NULL,
  `logo` varchar(255) DEFAULT NULL COMMENT 'Path către logo',
  `description` text DEFAULT NULL COMMENT 'Descriere scurtă a firmei',
  `promotional_text` text DEFAULT NULL COMMENT 'Text promoțional detaliat',
  `website_url` varchar(500) DEFAULT NULL COMMENT 'Link către site-ul partenerului',
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `discount_info` varchar(255) DEFAULT NULL COMMENT 'Ex: 10% discount pentru clienții FleetManagement',
  `promo_code` varchar(100) DEFAULT NULL COMMENT 'Cod promoțional dacă există',
  `banner_image` varchar(255) DEFAULT NULL COMMENT 'Imagine banner pentru reclamă',
  `is_featured` tinyint(1) DEFAULT 0 COMMENT 'Partener evidențiat/promovat',
  `is_active` tinyint(1) DEFAULT 1,
  `views_count` int(11) DEFAULT 0 COMMENT 'Număr de vizualizări',
  `clicks_count` int(11) DEFAULT 0 COMMENT 'Număr de click-uri pe link',
  `valid_from` date DEFAULT NULL COMMENT 'Data de început a promoției',
  `valid_until` date DEFAULT NULL COMMENT 'Data de expirare a promoției',
  `sort_order` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL COMMENT 'ID SuperAdmin care a creat',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_featured` (`is_featured`),
  KEY `idx_valid_dates` (`valid_from`, `valid_until`),
  CONSTRAINT `mp_partners_category_fk` FOREIGN KEY (`category_id`) REFERENCES `mp_partner_categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TRACKING CLICKS/VIEWS (pentru statistici)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `mp_partner_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action_type` enum('view','click') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `partner_id` (`partner_id`),
  KEY `idx_action` (`action_type`),
  KEY `idx_date` (`created_at`),
  CONSTRAINT `mp_partner_stats_fk` FOREIGN KEY (`partner_id`) REFERENCES `mp_partners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- DATE INIȚIALE - CATEGORII DEFAULT
-- ============================================================================
INSERT INTO `mp_partner_categories` (`name`, `slug`, `description`, `icon`, `color`, `sort_order`) VALUES
('Piese Auto', 'piese-auto', 'Furnizori de piese auto și accesorii pentru vehicule', 'fa-cogs', '#28a745', 1),
('Cauciucuri', 'cauciucuri', 'Magazine și service-uri specializate în anvelope', 'fa-circle', '#17a2b8', 2),
('Asigurări Auto', 'asigurari-auto', 'Companii de asigurări RCA, CASCO și alte polițe', 'fa-shield-alt', '#6f42c1', 3),
('Roviniete', 'roviniete', 'Platforme pentru achiziționarea de roviniete', 'fa-road', '#fd7e14', 4),
('Combustibil', 'combustibil', 'Stații de carburanți și carduri de flotă', 'fa-gas-pump', '#dc3545', 5),
('Service Auto', 'service-auto', 'Service-uri și ateliere pentru reparații', 'fa-wrench', '#20c997', 6),
('Leasing & Finanțare', 'leasing-finantare', 'Companii de leasing și soluții financiare', 'fa-hand-holding-usd', '#007bff', 7),
('GPS & Monitorizare', 'gps-monitorizare', 'Sisteme GPS și soluții de tracking', 'fa-map-marker-alt', '#e83e8c', 8)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ============================================================================
-- PARTENERI DEMO (opțional - poate fi șters)
-- ============================================================================
INSERT INTO `mp_partners` (`category_id`, `name`, `slug`, `description`, `promotional_text`, `website_url`, `is_featured`, `discount_info`) VALUES
(1, 'AutoParts Pro', 'autoparts-pro', 'Magazin online cu peste 500.000 de piese auto', 'AutoParts Pro oferă cea mai largă gamă de piese auto din România. Transport gratuit pentru comenzi peste 200 RON. Piese originale și aftermarket pentru toate mărcile.', 'https://example.com/autoparts', 1, '15% discount cu codul FLEET15'),
(2, 'TyreKing România', 'tyreking-romania', 'Specialist în anvelope pentru toate tipurile de vehicule', 'Găsește cauciucurile perfecte pentru flota ta! Anvelope de vară, iarnă și all-season de la branduri premium. Montaj gratuit și echilibrare inclusă.', 'https://example.com/tyreking', 1, 'Montaj gratuit pentru clienții FleetManagement'),
(3, 'Asigurări Rapid', 'asigurari-rapid', 'Asigurări auto online în câteva minute', 'Calculează prețul RCA și CASCO în doar 2 minute. Comparăm ofertele de la toate companiile de asigurări din România pentru a-ți oferi cel mai bun preț.', 'https://example.com/asigurari', 1, NULL),
(4, 'eRovinieta.ro', 'erovinieta-ro', 'Platforma oficială pentru achiziția rovinietelor', 'Cumpără rovinieta online simplu și rapid. Plată cu cardul, livrare instant pe email. Toate tipurile de roviniete disponibile.', 'https://example.com/rovinieta', 0, NULL)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

SELECT '✅ Marketplace Partners & Ads System installed successfully!' as status;
SELECT 'Tables created: mp_partner_categories, mp_partners, mp_partner_stats' as info;
