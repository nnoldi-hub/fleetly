-- Migration: Set explicit database_name for existing companies
-- This migration sets the database_name field in companies table to match the actual tenant database name on the server
-- 
-- For production server: Update the database_name to 'wclsgzyf_fleetly' or the actual tenant DB name
-- For local development: Keep as 'fm_tenant_1' or update based on your local setup

-- Example for production (Hostico cPanel with prefix wclsgzyf_):
-- CORE database: wclsgzyf_fleetly (contains companies, users, roles)
-- TENANT database: wclsgzyf_fm_tenant_1 (contains vehicles, documents, maintenance, fuel, etc.)
UPDATE companies SET database_name = 'wclsgzyf_fm_tenant_1' WHERE id = 1;

-- Example for local development:
-- CORE database: fleet_management_core
-- TENANT database: fm_tenant_1
-- UPDATE companies SET database_name = 'fm_tenant_1' WHERE id = 1;

-- Generic update query (replace 'YOUR_TENANT_DB_NAME' with actual TENANT database name):
-- UPDATE companies SET database_name = 'YOUR_TENANT_DB_NAME' WHERE id = YOUR_COMPANY_ID;

-- Instructions:
-- 1. Connect to your CORE database (contains table 'companies'):
--    - Production: wclsgzyf_fleetly
--    - Local: fleet_management_core
-- 2. Identify your company ID (usually 1 for the main company)
-- 3. Set database_name to your TENANT database name (contains vehicles, documents, etc.):
--    - Production: wclsgzyf_fm_tenant_1 (or wclsgzyf_fm_tenant_2, etc.)
--    - Local: fm_tenant_1
-- 4. Verify with: SELECT id, name, database_name FROM companies;

-- IMPORTANT: database_name should point to TENANT DB (fleet data), NOT to CORE DB!
