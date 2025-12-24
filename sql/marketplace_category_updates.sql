-- Marketplace Category Updates
-- Adaugă câmpuri pentru tipul de preț și necesitatea selectării vehiculului

-- Adaugă câmpul price_type: 'fixed' = preț fix, 'quote' = solicită ofertă
ALTER TABLE mp_categories ADD COLUMN price_type ENUM('fixed', 'quote') DEFAULT 'fixed' AFTER icon;

-- Adaugă câmpul requires_vehicle: 1 = trebuie selectat vehicul din flotă
ALTER TABLE mp_categories ADD COLUMN requires_vehicle TINYINT(1) DEFAULT 0 AFTER price_type;

-- Actualizează categoriile existente
-- Asigurări: preț variabil (solicită ofertă), necesită vehicul
UPDATE mp_categories SET price_type = 'quote', requires_vehicle = 1 WHERE slug = 'asigurari';

-- Roviniete: preț variabil (solicită ofertă), necesită vehicul (depinde de tipul vehiculului)
UPDATE mp_categories SET price_type = 'quote', requires_vehicle = 1 WHERE slug = 'roviniete';

-- Anvelope: preț fix afișat, dar necesită vehicul pentru a cunoaște dimensiunile
UPDATE mp_categories SET price_type = 'fixed', requires_vehicle = 1 WHERE slug = 'anvelope';

-- Piese Auto: preț fix afișat, dar necesită vehicul pentru compatibilitate
UPDATE mp_categories SET price_type = 'fixed', requires_vehicle = 1 WHERE slug = 'piese-auto';

-- Adaugă câmp vehicle_id în mp_cart pentru a stoca vehiculul selectat
ALTER TABLE mp_cart ADD COLUMN vehicle_id INT NULL AFTER quantity;
ALTER TABLE mp_cart ADD CONSTRAINT fk_cart_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL;

-- Adaugă câmp vehicle_id în mp_order_items pentru a stoca vehiculul pentru fiecare produs comandat
ALTER TABLE mp_order_items ADD COLUMN vehicle_id INT NULL AFTER quantity;
ALTER TABLE mp_order_items ADD COLUMN vehicle_plate VARCHAR(20) NULL AFTER vehicle_id;
ALTER TABLE mp_order_items ADD COLUMN vehicle_info TEXT NULL AFTER vehicle_plate;
