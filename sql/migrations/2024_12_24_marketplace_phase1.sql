-- ============================================================================
-- Fleet Management - Marketplace MVP (Faza 1)
-- Created: 2024-12-24
-- Description: Schema pentru marketplace B2B - catalog, coș, comenzi
-- ============================================================================

-- Categorii produse
CREATE TABLE IF NOT EXISTS mp_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Produse
CREATE TABLE IF NOT EXISTS mp_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    sku VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    specifications TEXT COMMENT 'JSON pentru specificații tehnice',
    price DECIMAL(12,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'RON',
    stock_quantity INT DEFAULT 0 COMMENT 'Pentru viitor - tracking stoc',
    image_main VARCHAR(255),
    image_gallery TEXT COMMENT 'JSON array cu imagini suplimentare',
    meta_title VARCHAR(255),
    meta_description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES mp_categories(id) ON DELETE RESTRICT,
    INDEX idx_category (category_id),
    INDEX idx_active (is_active),
    INDEX idx_featured (is_featured),
    INDEX idx_slug (slug),
    FULLTEXT idx_search (name, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Coș de cumpărături
CREATE TABLE IF NOT EXISTS mp_cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(12,2) NOT NULL COMMENT 'Price snapshot la momentul adăugării',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES mp_products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (company_id, user_id, product_id),
    INDEX idx_company (company_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comenzi
CREATE TABLE IF NOT EXISTS mp_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    company_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending','confirmed','processing','completed','cancelled') DEFAULT 'pending',
    
    -- Totals
    subtotal DECIMAL(12,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 19.00 COMMENT 'TVA %',
    tax_amount DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) NOT NULL,
    
    -- Delivery & notes
    delivery_address TEXT,
    delivery_notes TEXT,
    admin_notes TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_company (company_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at),
    INDEX idx_order_number (order_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Items comandă
CREATE TABLE IF NOT EXISTS mp_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    
    -- Product snapshot (salvăm detaliile la momentul comenzii)
    product_name VARCHAR(255) NOT NULL,
    product_sku VARCHAR(100),
    product_description TEXT,
    
    -- Pricing
    quantity INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES mp_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES mp_products(id) ON DELETE RESTRICT,
    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SEED DATA - Categorii
-- ============================================================================

INSERT INTO mp_categories (name, slug, description, icon, sort_order) VALUES
('Asigurări', 'asigurari', 'Asigurări RCA, CASCO și alte tipuri pentru flotă', 'shield-check', 1),
('Roviniete', 'roviniete', 'Roviniete electronice pentru țări europene', 'ticket-perforated', 2),
('Anvelope', 'anvelope', 'Anvelope pentru orice tip de vehicul din flotă', 'circle', 3),
('Piese Auto', 'piese-auto', 'Piese de schimb și accesorii auto', 'tools', 4);

-- ============================================================================
-- SEED DATA - Produse Test
-- ============================================================================

-- Asigurări
INSERT INTO mp_products (category_id, sku, name, slug, description, specifications, price, is_featured) VALUES
(1, 'RCA-FLEET-2025', 'RCA Flotă - Asigurare Obligatorie', 'rca-flota-2025', 
 'Asigurare RCA pentru vehicule din flotă. Acoperire conform legislației române. Gestiune online simplificată, documente electronic. Prețuri speciale pentru flote peste 10 vehicule.',
 '{"valabilitate": "12 luni", "acoperire": "Daune terți conform legislație", "livrare": "Instant - certificat electronic", "suport": "Telefonic și email"}',
 450.00, 1),

(1, 'CASCO-FLEET-FULL', 'CASCO Flotă - Asigurare Completă', 'casco-flota-completa',
 'Asigurare CASCO completă pentru protecție maximă. Acoperă daune totale, furt, incendiu, vandalism, calamități naturale. Franșiză negociabilă în funcție de istoric.',
 '{"valabilitate": "12 luni", "acoperire": "Daune totale, furt, incendiu, vandalism, calamități", "franșiza": "Negociabilă 500-2000 EUR", "asistenţă": "24/7 Rutieră inclusă"}',
 1200.00, 1),

(1, 'RCA-CAMION-2025', 'RCA Camioane și Autoutilitare', 'rca-camioane',
 'RCA special pentru camioane și autoutilitare peste 3.5 tone. Acoperire conform legislației pentru transport comercial.',
 '{"valabilitate": "12 luni", "tonaj": "Până la 40 tone", "livrare": "Instant - certificat electronic"}',
 680.00, 0);

-- Roviniete
INSERT INTO mp_products (category_id, sku, name, slug, description, specifications, price, is_featured) VALUES
(2, 'ROV-HU-12M', 'Rovinieta Ungaria - 12 Luni', 'rovinieta-ungaria-12-luni',
 'Rovinieta electronică Ungaria valabilă 12 luni pentru toate categoriile de vehicule. Livrare instant prin email. Valabilă pentru toate drumurile naționale din Ungaria.',
 '{"valabilitate": "365 zile", "categorii": "Toate (D1, D2, B2, U)", "livrare": "Instant - cod electronic", "activare": "Automat în maxim 15 minute"}',
 180.00, 1),

(2, 'ROV-BG-12M', 'Rovinieta Bulgaria - 12 Luni', 'rovinieta-bulgaria-12-luni',
 'Rovinieta electronică Bulgaria (e-vinieta) valabilă 12 luni. Acceptată la toate punctele de control. Livrare instant.',
 '{"valabilitate": "365 zile", "categorii": "Cat 3 (până la 12t)", "livrare": "Instant - certificat PDF", "verificare": "Online pe bgtoll.bg"}',
 150.00, 1),

(2, 'ROV-RO-12M', 'Rovinieta România - 12 Luni', 'rovinieta-romania-12-luni',
 'Rovinieta electronică pentru drumurile naționale din România. Valabilă 12 luni pentru toate categoriile.',
 '{"valabilitate": "365 zile", "categorii": "Toate categoriile de vehicule", "livrare": "Instant prin SMS și email", "rețea": "Toate drumurile naționale"}',
 96.00, 0),

(2, 'ROV-AT-12M', 'Vinieta Austria - 12 Luni', 'vinieta-austria-12-luni',
 'Vinieta digitală Austria valabilă 12 luni pentru autostrada austriacă. Alternativă la vinieta fizică.',
 '{"valabilitate": "365 zile", "categorii": "Vehicule până la 3.5t", "livrare": "Instant - confirmare email", "rețea": "Toate autostrăzile și drumuri expres"}',
 96.40, 0);

-- Anvelope
INSERT INTO mp_products (category_id, sku, name, slug, description, specifications, price, is_featured) VALUES
(3, 'TIRE-MICHELIN-205-55-R16', 'Michelin Primacy 4 - 205/55 R16', 'michelin-primacy-4-205-55-r16',
 'Anvelope premium Michelin pentru sedanuri și SUV-uri compacte. Performanță excelentă pe umed, consum redus, durabilitate superioară.',
 '{"dimensiune": "205/55 R16 91V", "sezon": "Vară", "indice_viteza": "V (240 km/h)", "indice_sarcina": "91 (615 kg)", "rezistenta": "A", "aderenta_umed": "B", "zgomot": "69 dB"}',
 420.00, 1),

(3, 'TIRE-CONTINENTAL-195-65-R15', 'Continental ContiEcoContact - 195/65 R15', 'continental-eco-195-65-r15',
 'Anvelope economice pentru flotă cu focus pe eficiență. Rezistență redusă la rulare, durată lungă de viață.',
 '{"dimensiune": "195/65 R15 91H", "sezon": "Vară", "indice_viteza": "H (210 km/h)", "eficiență": "A", "aderenta": "B", "garantie": "5 ani"}',
 340.00, 0),

(3, 'TIRE-WINTER-NOKIAN-205-55-R16', 'Nokian WR Snowproof - 205/55 R16', 'nokian-winter-205-55-r16',
 'Anvelope de iarnă premium pentru condiții severe. Aderență maximă pe zăpadă și gheață, comportament stabil.',
 '{"dimensiune": "205/55 R16 94H XL", "sezon": "Iarnă", "omologare": "3PMSF + M+S", "test_zapada": "Excelent", "durata": "50.000 km"}',
 480.00, 1);

-- Piese Auto
INSERT INTO mp_products (category_id, sku, name, slug, description, specifications, price, is_featured) VALUES
(4, 'FILTER-OIL-BOSCH-0451103336', 'Filtru Ulei Bosch 0451103336', 'filtru-ulei-bosch-universal',
 'Filtru ulei universal Bosch compatibil cu majoritatea vehiculelor din flotă. Calitate OE, filtrare superioară.',
 '{"tip": "Filtru ulei motor", "brand": "Bosch", "compatibilitate": "Universal - VW, Audi, Skoda, Seat", "cod_oem": "0451103336", "garantie": "2 ani"}',
 28.50, 0),

(4, 'BRAKE-PAD-ATE-13-0460-7240', 'Placute Frana ATE Ceramic', 'placute-frana-ate-ceramic',
 'Placuțe frână ceramice ATE pentru durabilitate maximă. Zgomot redus, performanță constantă, uzură minimă.',
 '{"tip": "Placuțe frână față", "material": "Ceramic", "brand": "ATE", "garantie": "3 ani / 50.000 km", "avantaje": "Fără praf, silențioase"}',
 185.00, 1),

(4, 'WIPER-BOSCH-AEROTWIN-24', 'Stergatoare Bosch Aerotwin 60cm', 'stergatoare-bosch-aerotwin',
 'Ștergătoare premium Bosch Aerotwin tehnologie flat blade. Ștergere perfectă, durată lungă, montaj rapid.',
 '{"lungime": "60 cm (24 inch)", "tip": "Flat blade", "brand": "Bosch", "compatibilitate": "Universal hook", "durata": "12 luni"}',
 65.00, 0),

(4, 'BATTERY-VARTA-BLUE-60AH', 'Baterie Auto Varta Blue Dynamic 60Ah', 'baterie-varta-60ah',
 'Baterie auto Varta 60Ah pentru vehicule mici și medii din flotă. Tehnologie AGM, pornire la rece excelentă.',
 '{"capacitate": "60 Ah", "curent_pornire": "540 A", "tehnologie": "AGM", "dimensiuni": "242x175x190mm", "garantie": "2 ani"}',
 385.00, 1);

-- ============================================================================
-- Verificare instalare
-- ============================================================================

SELECT 'Migration completed successfully!' as status;
SELECT COUNT(*) as categories_count FROM mp_categories;
SELECT COUNT(*) as products_count FROM mp_products;
