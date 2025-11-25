-- Migration: Set explicit database_name for existing companies
-- This migration sets the database_name field in companies table to match the actual tenant database name on the server
-- 
-- For production server: Update the database_name to 'wclsgzyf_fleetly' or the actual tenant DB name
-- For local development: Keep as 'fm_tenant_1' or update based on your local setup

-- Example for production (Hostico cPanel with prefix wclsgzyf_):
-- UPDATE companies SET database_name = 'wclsgzyf_fleetly' WHERE id = 1;

-- Example for local development:
-- UPDATE companies SET database_name = 'fm_tenant_1' WHERE id = 1;

-- Generic update query (replace 'YOUR_TENANT_DB_NAME' with actual database name):
-- UPDATE companies SET database_name = 'YOUR_TENANT_DB_NAME' WHERE id = YOUR_COMPANY_ID;

-- Instructions:
-- 1. Connect to your database (core database: fleet_management_core or wclsgzyf_core)
-- 2. Identify your company ID (usually 1 for the main company)
-- 3. Run the appropriate UPDATE command based on your environment
-- 4. Verify with: SELECT id, name, database_name FROM companies;

-- For Hostico production server, run this command:
-- UPDATE companies SET database_name = 'wclsgzyf_fleetly' WHERE id = 1;
