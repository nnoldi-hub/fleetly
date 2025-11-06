-- Sample Data for Fleet Management System
USE fleet_management;

-- Tipuri de vehicule
INSERT INTO vehicle_types (name, category, description) VALUES
('Autoturism', 'vehicle', 'Vehicul de transport persoane'),
('Autoutilitară', 'vehicle', 'Vehicul utilitar până la 3.5 tone'),
('Camion', 'vehicle', 'Vehicul de transport marfă peste 3.5 tone'),
('Autobuz', 'vehicle', 'Vehicul transport călători'),
('Motocicletă', 'vehicle', 'Motocicletă sau scuter'),
('Buldozer', 'equipment', 'Utilaj de construcții'),
('Excavator', 'equipment', 'Utilaj de construcții');

-- Vehicule
INSERT INTO vehicles (registration_number, vin_number, brand, model, year, vehicle_type_id, status, purchase_date, purchase_price, current_mileage, engine_capacity, fuel_type, color, notes) VALUES
('B123ABC', 'VF1LM1B0H36123456', 'Dacia', 'Logan', 2020, 1, 'active', '2020-03-15', 45000.00, 85000, '1.5', 'diesel', 'Alb', 'Vehicul serviciu'),
('B456DEF', 'VF3LCRHMB36789012', 'Renault', 'Master', 2019, 2, 'active', '2019-06-20', 75000.00, 120000, '2.3', 'diesel', 'Alb', 'Transport materiale'),
('B789GHI', 'WF0NXXGCCN9A12345', 'Ford', 'Transit', 2021, 2, 'active', '2021-01-10', 82000.00, 65000, '2.0', 'diesel', 'Albastru', NULL),
('B321JKL', 'VF1RFA00054321098', 'Dacia', 'Duster', 2022, 1, 'active', '2022-05-18', 68000.00, 35000, '1.5', 'diesel', 'Negru', 'SUV teren'),
('B654MNO', 'WBA3A5C50FK123456', 'BMW', '320d', 2018, 1, 'maintenance', '2018-09-25', 125000.00, 145000, '2.0', 'diesel', 'Gri', 'Vehicul directori'),
('B987PQR', 'WVWZZZ1KZ8W123456', 'Volkswagen', 'Crafter', 2020, 2, 'active', '2020-11-30', 95000.00, 98000, '2.0', 'diesel', 'Alb', NULL),
('B147STU', 'YV1MS382692123456', 'Volvo', 'FH16', 2017, 3, 'active', '2017-04-12', 285000.00, 425000, '16.0', 'diesel', 'Roșu', 'Camion transport mare'),
('B258VWX', 'WDB9631771L123456', 'Mercedes-Benz', 'Sprinter', 2021, 2, 'active', '2021-08-05', 105000.00, 72000, '2.2', 'diesel', 'Alb', 'Transport echipa'),
('B369YZA', 'VF1LM1B0H36654321', 'Dacia', 'Sandero', 2019, 1, 'inactive', '2019-02-14', 38000.00, 112000, '1.0', 'petrol', 'Roșu', 'Necesită reparații'),
('B741BCD', 'JTEHT05J202123456', 'Toyota', 'Hilux', 2020, 1, 'active', '2020-07-22', 115000.00, 95000, '2.4', 'diesel', 'Alb', 'Pickup teren');

-- Șoferi
INSERT INTO drivers (name, license_number, license_category, license_issue_date, license_expiry_date, phone, email, address, date_of_birth, hire_date, status, assigned_vehicle_id) VALUES
('Popescu Ion', 'B1234567', 'B,C,E', '2015-03-15', '2025-03-15', '0721234567', 'ion.popescu@email.com', 'Str. Mihai Eminescu, Nr. 15, București', '1985-05-20', '2018-06-01', 'active', 1),
('Ionescu Maria', 'B2345678', 'B', '2018-07-20', '2028-07-20', '0732345678', 'maria.ionescu@email.com', 'Str. Nicolae Bălcescu, Nr. 23, București', '1990-08-15', '2019-09-15', 'active', 3),
('Gheorghe Vasile', 'B3456789', 'B,C,D,E', '2012-11-10', '2022-11-10', '0743456789', 'vasile.gheorghe@email.com', 'Str. Victoriei, Nr. 8, București', '1982-03-12', '2017-04-20', 'active', 7),
('Dumitrescu Ana', 'B4567890', 'B', '2019-02-05', '2029-02-05', '0754567890', 'ana.dumitrescu@email.com', 'Bd. Unirii, Nr. 45, București', '1992-11-30', '2020-01-10', 'active', 2),
('Constantin Andrei', 'B5678901', 'B,C', '2016-09-18', '2026-09-18', '0765678901', 'andrei.constantin@email.com', 'Str. Libertății, Nr. 12, București', '1988-07-05', '2018-11-25', 'active', 6);

-- Documente
INSERT INTO documents (vehicle_id, document_type, document_number, issue_date, expiry_date, provider, cost, status, reminder_days) VALUES
(1, 'insurance_rca', 'RCA123456789', '2024-01-15', '2025-01-15', 'Allianz Tiriac', 850.00, 'active', 30),
(1, 'itp', 'ITP2024001', '2024-06-10', '2025-06-10', 'RAR București', 150.00, 'active', 30),
(2, 'insurance_rca', 'RCA987654321', '2024-02-20', '2025-02-20', 'Groupama', 1250.00, 'active', 30),
(3, 'insurance_rca', 'RCA456789123', '2024-03-05', '2025-03-05', 'Omniasig', 1100.00, 'active', 30),
(3, 'insurance_casco', 'CASCO123456', '2024-03-05', '2025-03-05', 'Omniasig', 3200.00, 'active', 30),
(4, 'insurance_rca', 'RCA789123456', '2024-05-12', '2025-05-12', 'Allianz Tiriac', 950.00, 'active', 30),
(5, 'insurance_rca', 'RCA321654987', '2023-09-20', '2024-09-20', 'City Insurance', 1450.00, 'expired', 30),
(7, 'insurance_rca', 'RCA147258369', '2024-04-08', '2025-04-08', 'Groupama', 2850.00, 'active', 30);

-- Întreținere
INSERT INTO maintenance (vehicle_id, driver_id, maintenance_type, description, cost, mileage_at_service, service_date, next_service_date, next_service_mileage, service_provider, status) VALUES
(1, 1, 'preventive', 'Revizie tehnică periodică - schimb ulei, filtre', 450.00, 80000, '2024-08-15', '2025-02-15', 95000, 'Service Auto Dacia', 'completed'),
(2, 4, 'corrective', 'Reparație sistem frânare', 850.00, 115000, '2024-09-20', NULL, NULL, 'Service Renault', 'completed'),
(3, 2, 'preventive', 'Revizie 60.000 km', 520.00, 60000, '2024-07-10', '2025-01-10', 75000, 'Ford Service', 'completed'),
(5, NULL, 'repair', 'Reparație motor - înlocuire distribuție', 3200.00, 140000, '2024-10-05', NULL, NULL, 'BMW Service', 'in_progress'),
(7, 3, 'preventive', 'Revizie camion - service complet', 1850.00, 420000, '2024-09-01', '2025-03-01', 450000, 'Volvo Truck Center', 'completed');

-- Consum combustibil
INSERT INTO fuel_consumption (vehicle_id, driver_id, fuel_date, fuel_type, liters, price_per_liter, total_cost, mileage, fuel_station, receipt_number) VALUES
(1, 1, '2024-10-01', 'diesel', 45.5, 7.20, 327.60, 82000, 'OMV Pipera', 'OMV123456'),
(1, 1, '2024-10-15', 'diesel', 48.2, 7.25, 349.45, 82650, 'Petrom Baneasa', 'PTR789012'),
(2, 4, '2024-10-03', 'diesel', 65.0, 7.18, 466.70, 117000, 'Rompetrol Militari', 'ROM456789'),
(3, 2, '2024-10-08', 'diesel', 52.3, 7.22, 377.61, 64500, 'OMV Unirii', 'OMV654321'),
(4, NULL, '2024-10-12', 'diesel', 42.8, 7.20, 308.16, 34200, 'Petrom Colentina', 'PTR321654'),
(6, NULL, '2024-10-05', 'diesel', 58.5, 7.19, 420.62, 96500, 'Rompetrol Pantelimon', 'ROM147258'),
(7, 3, '2024-10-10', 'diesel', 285.0, 7.15, 2037.75, 423500, 'Lukoil DN1', 'LUK852963');

-- Asigurări
INSERT INTO insurance (vehicle_id, insurance_type, policy_number, provider, start_date, end_date, premium_amount, coverage_amount, status) VALUES
(1, 'rca', 'RCA123456789', 'Allianz Tiriac', '2024-01-15', '2025-01-15', 850.00, NULL, 'active'),
(2, 'rca', 'RCA987654321', 'Groupama', '2024-02-20', '2025-02-20', 1250.00, NULL, 'active'),
(3, 'rca', 'RCA456789123', 'Omniasig', '2024-03-05', '2025-03-05', 1100.00, NULL, 'active'),
(3, 'casco', 'CASCO123456', 'Omniasig', '2024-03-05', '2025-03-05', 3200.00, 80000.00, 'active'),
(4, 'rca', 'RCA789123456', 'Allianz Tiriac', '2024-05-12', '2025-05-12', 950.00, NULL, 'active'),
(5, 'rca', 'RCA321654987', 'City Insurance', '2023-09-20', '2024-09-20', 1450.00, NULL, 'expired'),
(7, 'rca', 'RCA147258369', 'Groupama', '2024-04-08', '2025-04-08', 2850.00, NULL, 'active');
