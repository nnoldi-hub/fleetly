# DEPLOYMENT CHECKLIST: Notification System V2
## Fleet Management System - Production Deployment

---

## 📋 Pre-Deployment Checklist

### 1. Code Review ✓

- [ ] All code changes reviewed and approved
- [ ] No debug statements or test code in production files
- [ ] Error handling implemented for all critical paths
- [ ] Logging configured appropriately

### 2. Database Preparation ✓

- [ ] **BACKUP CURRENT DATABASE** (critical!)
  ```bash
  mysqldump -u root -p fleet_management > backup_$(date +%Y%m%d_%H%M%S).sql
  ```
- [ ] Review migration script: `sql/migrations/2025_01_12_001_notification_system_v2.sql`
- [ ] Test migration on staging/development first
- [ ] Verify rollback script availability

### 3. Dependencies ✓

- [ ] PHP version >= 7.4
- [ ] MySQL version >= 5.7
- [ ] Required PHP extensions installed:
  - `mysqli`
  - `json`
  - `mbstring`
- [ ] Composer dependencies updated (if applicable)

### 4. Configuration ✓

- [ ] SMTP settings configured in `config/mail.php`
- [ ] SMS gateway credentials configured (if using SMS)
- [ ] Timezone settings correct in `config/config.php`
- [ ] Email templates reviewed and approved

### 5. File Permissions ✓

```bash
# Scripts directory
chmod +x scripts/process_notifications_queue.php
chmod +x scripts/retry_failed_notifications.php
chmod +x scripts/cleanup_notifications.php
chmod +x scripts/migrate_notification_preferences.php

# Logs directory (create if not exists)
mkdir -p logs/cron_logs
chmod 755 logs/cron_logs
```

---

## 🚀 Deployment Steps

### STEP 1: Database Migration (15 minutes)

**1.1. Backup Production Database**
```bash
# On production server
mysqldump -u [DB_USER] -p [DB_NAME] > backup_pre_v2_$(date +%Y%m%d).sql
```

**1.2. Upload Migration File**
```bash
# Upload SQL migration to server
scp sql/migrations/2025_01_12_001_notification_system_v2.sql user@server:/path/to/project/sql/migrations/
```

**1.3. Run Migration**
```bash
# On production server
mysql -u [DB_USER] -p [DB_NAME] < sql/migrations/2025_01_12_001_notification_system_v2.sql
```

**1.4. Verify Migration**
```sql
-- Check tables created
SHOW TABLES LIKE 'notification%';

-- Check default templates
SELECT COUNT(*) FROM notification_templates WHERE company_id IS NULL;
-- Expected: 4

-- Check columns added
DESCRIBE documents;
DESCRIBE insurance;
```

**1.5. Rollback Plan (if needed)**
```sql
-- Rollback commands at bottom of migration file
-- Only use if migration fails!
```

---

### STEP 2: Deploy Code Files (10 minutes)

**2.1. Upload New Files**

```bash
# Models
modules/notifications/models/NotificationPreference.php
modules/notifications/models/NotificationQueue.php
modules/notifications/models/NotificationTemplate.php

# Services
modules/notifications/services/DocumentStatusUpdater.php
modules/notifications/services/NotificationQueueProcessor.php

# Views
modules/notifications/views/preferences.php
modules/superadmin/views/notifications_dashboard.php

# Scripts
scripts/process_notifications_queue.php
scripts/retry_failed_notifications.php
scripts/cleanup_notifications.php
scripts/migrate_notification_preferences.php

# Docs
docs/NOTIFICATION_ARCHITECTURE.md
docs/NOTIFICATION_V2_IMPLEMENTATION.md
docs/CRON_SETUP_GUIDE.md
docs/USER_GUIDE_NOTIFICATIONS.md
docs/TESTING_GUIDE.md
docs/DEPLOYMENT_CHECKLIST.md
```

**2.2. Update Modified Files**

```bash
# Controllers
modules/notifications/controllers/NotificationController.php
modules/superadmin/controllers/SuperAdminController.php

# Config
config/routes.php

# Core Models
modules/notifications/models/Notification.php (refactored createSingle)
```

**2.3. Verify File Upload**
```bash
# Check critical files exist
ls -la modules/notifications/models/NotificationQueue.php
ls -la scripts/process_notifications_queue.php
```

---

### STEP 3: Data Migration (5 minutes)

**3.1. Migrate User Preferences**

```bash
# On production server
php scripts/migrate_notification_preferences.php
```

**Expected Output:**
```
=== NOTIFICATION PREFERENCES MIGRATION START ===
Total users to migrate: [N]

[001] User: admin ✅ MIGRATED
...

=== MIGRATION SUMMARY ===
Total users: [N]
Migrated: [M]
Skipped: [K]
Errors: 0
Success rate: 100%
```

**3.2. Verify Migration**
```sql
SELECT COUNT(*) FROM notification_preferences;
-- Should match number of active users with preferences
```

---

### STEP 4: Configure Cron Jobs (10 minutes)

**4.1. Access cPanel Cron Jobs**
1. Login to cPanel
2. Navigate to **Advanced → Cron Jobs**

**4.2. Add Cron Jobs** (4 jobs total)

**Job 1: Queue Processor (Every 5 minutes)**
```bash
*/5 * * * * php /home/[USERNAME]/public_html/scripts/process_notifications_queue.php >> /home/[USERNAME]/logs/cron_logs/queue_processor.log 2>&1
```

**Job 2: Retry Failed (Every hour at :15)**
```bash
15 * * * * php /home/[USERNAME]/public_html/scripts/retry_failed_notifications.php >> /home/[USERNAME]/logs/cron_logs/retry_failed.log 2>&1
```

**Job 3: Daily Notifications (6:00 AM)**
```bash
0 6 * * * php /home/[USERNAME]/public_html/scripts/cron_generate_notifications.php >> /home/[USERNAME]/logs/cron_logs/daily_notifications.log 2>&1
```

**Job 4: Cleanup (4:00 AM daily)**
```bash
0 4 * * * php /home/[USERNAME]/public_html/scripts/cleanup_notifications.php >> /home/[USERNAME]/logs/cron_logs/cleanup.log 2>&1
```

**4.3. Set Email Alerts**
- Configure cron email to: `admin@yourdomain.com`
- Receives alerts on script failures

---

### STEP 5: Initial Testing (15 minutes)

**5.1. Test User Preferences**
1. Login as regular user
2. Navigate to: `Notifications → Preferințe`
3. Save preferences
4. Verify success message

**5.2. Test Notification Creation**
```bash
# Manually trigger generation
curl https://yourdomain.com/notifications/generate
```

**5.3. Test Queue Processing**
```bash
# Manual run
php scripts/process_notifications_queue.php

# Check output
tail -50 logs/cron_logs/queue_processor.log
```

**5.4. Test Email Delivery**
1. Create test notification
2. Wait 5 minutes (or manual process)
3. Check email inbox

**5.5. Test SuperAdmin Dashboard**
1. Login as SuperAdmin
2. Navigate to: `SuperAdmin → Notifications Dashboard`
3. Verify KPIs load correctly

---

### STEP 6: Monitoring Setup (5 minutes)

**6.1. Database Monitoring Queries**

```sql
-- Save as monitoring script: scripts/monitor_notifications.sql

-- Queue backlog
SELECT status, COUNT(*) as count 
FROM notification_queue 
GROUP BY status;

-- Recent errors
SELECT * FROM notification_logs 
WHERE status = 'error' 
ORDER BY created_at DESC 
LIMIT 10;

-- Delivery rates (last 24h)
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status='sent' THEN 1 ELSE 0 END) as sent,
    ROUND(SUM(CASE WHEN status='sent' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as rate
FROM notifications
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR);
```

**6.2. Log Monitoring**

```bash
# Create monitoring script
cat > scripts/monitor_logs.sh << 'EOF'
#!/bin/bash
echo "=== QUEUE PROCESSOR (Last 20 lines) ==="
tail -20 /home/[USERNAME]/logs/cron_logs/queue_processor.log

echo ""
echo "=== RETRY FAILED (Last 10 lines) ==="
tail -10 /home/[USERNAME]/logs/cron_logs/retry_failed.log

echo ""
echo "=== QUEUE STATUS ==="
mysql -u [DB_USER] -p[DB_PASS] [DB_NAME] -e "SELECT status, COUNT(*) FROM notification_queue GROUP BY status;"
EOF

chmod +x scripts/monitor_logs.sh
```

---

## ✅ Post-Deployment Verification

### Immediate Checks (within 30 minutes)

- [ ] **Website accessible:** No 500 errors
- [ ] **Users can login:** Authentication works
- [ ] **Notifications page loads:** No fatal errors
- [ ] **Preferences page loads:** Form renders correctly
- [ ] **First cron run successful:** Check logs after 5 minutes
- [ ] **Database queries respond:** No performance degradation

### Short-term Monitoring (first 24 hours)

- [ ] **Queue processing rate:** Check every 2 hours
- [ ] **Email delivery:** Verify users receiving emails
- [ ] **SMS delivery:** Verify SMS working (if enabled)
- [ ] **Error logs:** Check for unexpected errors
- [ ] **Database size:** Monitor table growth
- [ ] **Server load:** CPU/Memory within normal range

### Long-term Monitoring (first week)

- [ ] **Delivery rate:** Maintain >95% success rate
- [ ] **Queue backlog:** Should remain near 0
- [ ] **Failed notifications:** Review and fix errors
- [ ] **User feedback:** Collect feedback on new system
- [ ] **Performance:** Response times acceptable

---

## 🔧 Troubleshooting Common Issues

### Issue 1: Cron Jobs Not Running

**Symptoms:** Queue backlog growing, no log files

**Solution:**
```bash
# Check cron logs
tail -50 /var/log/cron

# Test script manually
php scripts/process_notifications_queue.php

# Check PHP path
which php
# Use full path in cron: /usr/bin/php or /usr/local/bin/php
```

---

### Issue 2: Email Not Sending

**Symptoms:** Queue items marked as 'sent' but no emails received

**Check:**
1. SMTP settings in `config/mail.php`
2. Email provider logs (Mailgun, SendGrid, etc.)
3. Spam folder
4. Email quotas not exceeded

**Test:**
```php
// Test SMTP directly
php -r "
require 'config/mail.php';
require 'core/Mailer.php';
\$mailer = new Mailer();
\$result = \$mailer->sendEmail('test@example.com', 'Test', 'Testing');
var_dump(\$result);
"
```

---

### Issue 3: Database Migration Failed

**Symptoms:** Tables not created, errors in migration

**Rollback:**
```bash
# Restore backup
mysql -u [DB_USER] -p [DB_NAME] < backup_pre_v2_[DATE].sql
```

**Fix and Retry:**
1. Review error message
2. Fix SQL syntax or conflicts
3. Re-run migration on clean backup

---

### Issue 4: High Queue Backlog

**Symptoms:** Queue grows faster than processing

**Solution:**
```bash
# Increase processing frequency (every 2 min instead of 5)
*/2 * * * * php scripts/process_notifications_queue.php

# Or increase batch size
php scripts/process_notifications_queue.php 500
```

---

### Issue 5: Memory Exhaustion

**Symptoms:** PHP Fatal Error: Allowed memory size exhausted

**Solution:**
```bash
# Increase memory limit in cron
*/5 * * * * php -d memory_limit=256M scripts/process_notifications_queue.php
```

---

## 📊 Success Metrics

### Key Performance Indicators (KPIs)

| Metric | Target | Critical Threshold |
|--------|--------|-------------------|
| Delivery Rate | >95% | <90% (investigate) |
| Queue Backlog | <100 items | >1000 (alert) |
| Processing Time | <5 sec/100 items | >30 sec (optimize) |
| Failed Retry Success | >80% | <50% (fix root cause) |
| Email Bounce Rate | <5% | >10% (review contacts) |
| User Satisfaction | >4/5 | <3/5 (improve UX) |

---

## 🔄 Rollback Plan

### If Critical Issues Occur

**Step 1: Stop Cron Jobs**
```bash
# Disable all notification cron jobs in cPanel
# Prevents further processing while investigating
```

**Step 2: Restore Database**
```bash
mysql -u [DB_USER] -p [DB_NAME] < backup_pre_v2_[DATE].sql
```

**Step 3: Revert Code Files**
```bash
# Restore previous versions of modified files:
# - Notification.php (restore original createSingle)
# - NotificationController.php
# - config/routes.php
```

**Step 4: Clear Cache**
```bash
# Clear any cached config
rm -f cache/*.cache
```

**Step 5: Notify Users**
- Post maintenance message
- Estimate resolution time
- Communicate via email/status page

---

## 📞 Support Contacts

**Development Team:**
- Technical Lead: [Name] - [Email]
- Backend Developer: [Name] - [Email]

**Infrastructure:**
- Server Admin: [Name] - [Email]
- Database Admin: [Name] - [Email]

**Business:**
- Product Owner: [Name] - [Email]
- Support Manager: [Name] - [Email]

---

## 📝 Deployment Sign-off

```
┌─────────────────────────────────────────────┐
│  DEPLOYMENT APPROVAL                        │
├─────────────────────────────────────────────┤
│ Deployment Date: _______________            │
│ Deployment Time: _______________            │
│                                             │
│ Deployed By:                                │
│   Name: _______________                     │
│   Signature: _______________                │
│   Date: _______________                     │
│                                             │
│ Approved By:                                │
│   Technical Lead: _______________           │
│   Product Owner: _______________            │
│   Date: _______________                     │
│                                             │
│ Post-Deployment Verification:               │
│   [ ] All checks passed                     │
│   [ ] Issues found (attach report)          │
│                                             │
│ Sign-off: _______________                   │
└─────────────────────────────────────────────┘
```

---

**Document Version:** 1.0  
**Last Updated:** January 12, 2025  
**Notification System:** V2 Deployment Guide
