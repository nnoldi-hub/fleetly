
Fleet Management - Engineering Plan (updated)

Summary of what’s done recently
- Reports backend routes and JSON endpoints wired; BASE_URL issues fixed; Bootstrap upgraded to 5.3; dark mode and Chart.js theme polish complete.
- Notifications module implemented end‑to‑end: routes, settings (SMTP/SMS + test send), preferences filtering, unread counters/actions, Notifier service, and background dispatcher script.
- User profile now stores per‑user SMS phone; numbers normalized; immediate send on notification create; batch processor for pending items.

Quickstart
1) Seed & verify database
- Import sql/schema.sql (and optional sql/sample_data.sql)
- Visit /test_db.php to validate connection and tables

2) Configure notifications delivery
- App → Notificări → Setări: complete “Email (SMTP)” and/or “SMS” sections; use “Trimite email/SMS de test” to verify
- Optional per‑user phone: Profil utilizator → Telefon SMS (salvat în system_settings user_{id}_sms_to)

3) Background sender (Windows Task Scheduler)
- Script: scripts\process_notifications.php
- Trigger: every 5 minutes (recommended)
- Action: Program/script → php; Arguments → "c:\\wamp64\\www\\fleet-management\\scripts\\process_notifications.php"; Start in → "c:\\wamp64\\www\\fleet-management"
- Logs: script prints a summary; check Windows Event Viewer or run manually in a terminal for debugging

4) Smoke test
- Run scripts\test_notifications.php (added below) then open Notificări → Alertări; emails/SMS should be sent immediately if preferences allow, or by the background sender

Reports module — scope and data design
Report types and filters
- Fleet overview: vehicle status distribution; filters: status, type
- Fuel report: consumption trend per vehicle; filters: date range, vehicle(s)
- Maintenance report: planned vs completed by month; filters: date range, vehicle(s), type
- Cost analysis: stacked costs (fuel, maintenance, other) by month; filters: date range, vehicle(s)
- Documents expiring: upcoming expirations; filters: date range, type, vehicle

Planned queries (MySQL)
- Fleet overview
	SELECT status, COUNT(*) cnt FROM vehicles GROUP BY status
- Fuel consumption
	SELECT v.id, v.registration_number, f.fill_date, (f.liters / NULLIF(f.distance_km,0))*100 AS l_per_100
	FROM fuel_logs f JOIN vehicles v ON v.id=f.vehicle_id
	WHERE f.fill_date BETWEEN :from AND :to AND (v.id IN (:vehicleIds) OR :vehicleIds IS NULL)
- Maintenance trend
	SELECT DATE_FORMAT(date, '%Y-%m') ym,
				 SUM(status='planned') planned,
				 SUM(status='completed') completed
	FROM maintenance
	WHERE date BETWEEN :from AND :to
	GROUP BY ym ORDER BY ym
- Cost analysis (stacked)
	SELECT ym,
				 SUM(cost_type='fuel'*amount) fuel,
				 SUM(cost_type='maintenance'*amount) maintenance,
				 SUM(cost_type NOT IN ('fuel','maintenance')*amount) other
	FROM (
		SELECT DATE_FORMAT(fill_date,'%Y-%m') ym, 'fuel' cost_type, total_cost amount FROM fuel_logs WHERE fill_date BETWEEN :from AND :to
		UNION ALL
		SELECT DATE_FORMAT(service_date,'%Y-%m'), 'maintenance', cost FROM maintenance WHERE service_date BETWEEN :from AND :to
	) x GROUP BY ym ORDER BY ym
- Documents expiring
	SELECT d.*, v.registration_number
	FROM documents d JOIN vehicles v ON v.id=d.vehicle_id
	WHERE d.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)

Implementation status
- JS entrypoint: assets/js/modules/reports.js (charts, filters, export hooks)
- Routes: ReportController returns JSON for charts; /reports/generate for HTML tables; /reports/export for downloads
- Views: ensure modules/reports/views/*.php provide containers with IDs referenced by the JS (e.g., #costChart, #fuelChart)

Notifications — architecture notes
- Data: notifications table with user_id, type, priority, status, related_*; system_settings for smtp_settings, sms_settings, notifications_prefs_user_{id}, user_{id}_sms_to
- Delivery: Notifier service sends SMTP (or mail()) and SMS (Twilio or generic HTTP); numbers normalized to +E.164‑like
- Dispatch: immediate attempt on create(); scripts/process_notifications.php for pending queue

Troubleshooting
- “Acțiune necunoscută” on saving settings: fixed by guarding direct POST in MaintenanceController; ensure routes pass through index.php
- Email doesn’t send: verify SMTP host/port/encryption and From; some providers require app passwords
- SMS doesn’t send: check provider credentials; ensure phone saved as +407… format

Next up
- Finalize report views HTML/filters and exports; add minimal PHPUnit‑less smoke tests under scripts/
- Optional: swap to PHPMailer for richer SMTP and better errors

