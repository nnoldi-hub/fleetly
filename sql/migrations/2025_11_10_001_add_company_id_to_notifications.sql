-- Migrare: Adăugare company_id în notifications pentru suport broadcast la nivel de companie
-- Data: 2025-11-10
-- Scop: Permite notificări globale pentru toți utilizatorii unei companii

-- Verificăm dacă coloana există deja
SET @dbname = DATABASE();
SET @tablename = 'notifications';
SET @columnname = 'company_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " INT NULL AFTER user_id, ADD INDEX idx_company_notifications(company_id)")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Permite user_id să fie NULL (când e notificare broadcast la companie)
ALTER TABLE notifications MODIFY COLUMN user_id INT NULL;

-- NOTE: Nu facem backfill pentru company_id pe notificările existente deoarece:
-- 1. Tabela users este în baza core (wclsgzyf_fleetly), nu în tenant (wclsgzyf_fm_tenant_X)
-- 2. Nu putem face JOIN cross-database în migrare
-- 3. Notificările existente vor rămâne cu company_id NULL (funcționare normală)
-- 4. Doar notificările noi create după migrare vor folosi broadcast

-- Comentariu final
SELECT 'Migrare completă: notifications.company_id adăugat cu succes' AS status;
