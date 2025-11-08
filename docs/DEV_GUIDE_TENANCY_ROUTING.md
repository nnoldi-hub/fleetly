# Developer guide: Multi‑tenancy and routing in Fleet Management

This project uses a split‑database architecture:

- Core DB (authentication, companies, roles, permissions, sessions, audit)
- Tenant DB per company (fleet data: vehicles, drivers, documents, maintenance, fuel, notifications)

The Database class routes queries based on the target table. To avoid subtle bugs, follow these rules.

## Golden rules for DB access

1) Prefer using Models
- `Model::find`, `findAll`, `create`, `update`, `delete`, `count` already call the tenant‑aware helpers internally.
- Example: `Driver::find($id)` automatically hits the tenant DB.

2) If you must write custom SQL (JOINs, reports), ALWAYS use the table‑scoped helpers
- Use the helpers that take a table name to select the correct connection:
  - `fetchOn($table, $sql, $params)`
  - `fetchAllOn($table, $sql, $params)`
  - `queryOn($table, $sql, $params)`
  - `lastInsertIdOn($table)`
- Pick a table name from the query that clearly lives in the tenant DB (e.g. `drivers`, `vehicles`).
- Do NOT call the generic `fetch()`/`fetchAll()`/`query()` for fleet data. Those use the core connection and will fail because fleet tables do not exist in the core DB.

Bad (will hit CORE DB):

```
// ❌ Do not do this for fleet data
$this->db->fetchAll("SELECT * FROM vehicles WHERE status='active'");
```

Good (tenant aware):

```
// ✅ Use a tenant table name to select the tenant connection
$this->db->fetchAllOn('vehicles', "SELECT * FROM vehicles WHERE status='active'");
```

Real bug fixed (Nov 2025):
- DriverController::getAvailableVehicles used `fetchAll(...)` on a query involving `vehicles`.
- Because `fetchAll` uses the core connection, the query threw an exception (core DB has no `vehicles` table).
- The exception bubbled up and showed a 404 screen.
- Fix: switched to `fetchAllOn('vehicles', ...)`.

## Ensure tenant is selected for each request
- Before hitting fleet data, select the tenant DB based on the logged‑in company.
- Pattern used in controllers:

```
Auth::getInstance()->requireAuth();
$companyId = Auth::getInstance()->effectiveCompanyId();
if ($companyId) {
    Database::getInstance()->setTenantDatabaseByCompanyId($companyId);
}
```

Consider moving this to a base controller/middleware if most controllers need it.

## Routing with or without mod_rewrite
- `BASE_URL` is for links to pages and static assets. It does not include `index.php`.
- `ROUTE_BASE` is for route actions (forms, action buttons). It includes `index.php/` so it works even when mod_rewrite is off.
- When generating links to controller actions or form actions, prefer `ROUTE_BASE`:

```
// ✅
<form action="<?= ROUTE_BASE ?>drivers/edit?id=<?= (int)$driver['id'] ?>" method="POST">

// ✅
<a href="<?= ROUTE_BASE ?>drivers/edit?id=<?= (int)$driver['id'] ?>">Editează</a>
```

- Router and index patches normalize URLs so both `/drivers/...` and `/index.php/drivers/...` work.

## Quick checklist
- [ ] Am I using a Model method? If not, use `*On($table, ...)` helpers for fleet tables.
- [ ] Did I select the tenant DB before running fleet queries?
- [ ] Are my form actions and action links using `ROUTE_BASE`?
- [ ] Avoid referencing `company_id` in tenant tables unless schema guarantees it (tenant boundary already isolates by company).

Following the above prevents the “works locally, 404 in production” class of issues for driver/vehicle pages and other modules.
