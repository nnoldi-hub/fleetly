# TESTING GUIDE: Notification System V2
## Fleet Management System

---

## 📖 Table of Contents

1. [Testing Overview](#testing-overview)
2. [Pre-Deployment Testing](#pre-deployment-testing)
3. [Manual Testing Procedures](#manual-testing-procedures)
4. [Automated Testing](#automated-testing)
5. [Performance Testing](#performance-testing)
6. [Security Testing](#security-testing)
7. [Regression Testing](#regression-testing)
8. [Test Case Library](#test-case-library)

---

## 1. Testing Overview

### Objectives

- ✅ Verify notification queue processing
- ✅ Validate user preferences functionality
- ✅ Test multi-channel delivery (Email, SMS, In-App)
- ✅ Confirm rate limiting works correctly
- ✅ Ensure multi-tenancy isolation
- ✅ Validate template rendering
- ✅ Test retry logic for failed notifications
- ✅ Verify cron job execution

### Test Environments

| Environment | Purpose | Database |
|-------------|---------|----------|
| **Local** | Development testing | Local MySQL |
| **Staging** | Pre-production validation | Staging DB (copy of prod) |
| **Production** | Post-deployment smoke tests | Production DB |

---

## 2. Pre-Deployment Testing

### Database Migration Test

**Objective:** Ensure SQL migration runs without errors.

**Procedure:**

```bash
# 1. Backup current database
mysqldump -u root -p fleet_management > backup_pre_migration.sql

# 2. Run migration script
mysql -u root -p fleet_management < sql/migrations/2025_01_12_001_notification_system_v2.sql

# 3. Verify tables created
mysql -u root -p fleet_management -e "SHOW TABLES LIKE 'notification%';"
```

**Expected Results:**
```
+----------------------------------------+
| Tables_in_fleet_management             |
+----------------------------------------+
| notification_logs                      |
| notification_preferences               |
| notification_queue                     |
| notification_rate_limits               |
| notification_templates                 |
| notifications                          |
+----------------------------------------+
```

**Verification Queries:**

```sql
-- Check default templates inserted
SELECT slug, name, enabled FROM notification_templates WHERE company_id IS NULL;
-- Expected: 4 rows (document_expiry, insurance_expiry, maintenance_due, system_alert)

-- Check columns added to existing tables
DESCRIBE documents;
-- Expected: expiry_status column present

DESCRIBE insurance;
-- Expected: expiry_status column present
```

---

### Data Migration Test

**Objective:** Migrate legacy preferences from system_settings to notification_preferences.

**Procedure:**

```bash
# Run migration script
php scripts/migrate_notification_preferences.php
```

**Expected Output:**
```
=== NOTIFICATION PREFERENCES MIGRATION START ===
Total users to migrate: 15

[001] User: admin ✅ MIGRATED
[002] User: manager1 ✅ MIGRATED
[003] User: driver1 ⏭️ SKIP (no preferences)
...

=== MIGRATION SUMMARY ===
Total users: 15
Migrated: 12
Skipped: 3
Errors: 0
Success rate: 100%
```

**Verification:**

```sql
-- Check migrated preferences count
SELECT COUNT(*) FROM notification_preferences;
-- Should match number of users with existing preferences

-- Sample verification
SELECT user_id, email_enabled, sms_enabled, frequency 
FROM notification_preferences 
LIMIT 5;
```

---

## 3. Manual Testing Procedures

### Test Case 1: User Preferences - Save & Retrieve

**Steps:**

1. Login as regular user
2. Navigate to: `Notifications → Preferințe`
3. Enable Email and SMS
4. Select notification types: Document Expiry, Insurance Expiry
5. Set frequency: Daily
6. Set quiet hours: 22:00 - 08:00
7. Set days before expiry: 45
8. Click **Salvează Preferințe**
9. Refresh page

**Expected Results:**
- ✅ Success message: "Preferințele au fost salvate cu succes!"
- ✅ Form shows saved values after refresh
- ✅ Database record created/updated in `notification_preferences`

**Verification Query:**
```sql
SELECT * FROM notification_preferences WHERE user_id = [YOUR_USER_ID];
```

---

### Test Case 2: Notification Creation & Queue

**Steps:**

1. Login as admin
2. Create a document with expiry date = TODAY + 20 days
3. Navigate to: `Notifications → Generează Notificări`
4. Click **Generează** button
5. Check notification created

**Expected Results:**
- ✅ Notification created in `notifications` table (status=pending)
- ✅ Queue entries created in `notification_queue` for enabled channels
- ✅ Template rendered (title/message populated)

**Verification Queries:**

```sql
-- Check notification created
SELECT id, type, title, message, status, template_id 
FROM notifications 
WHERE user_id = [USER_ID] 
ORDER BY created_at DESC 
LIMIT 1;

-- Check queue entries
SELECT id, channel, status, scheduled_at, recipients 
FROM notification_queue 
WHERE notification_id = [NOTIFICATION_ID];
```

---

### Test Case 3: Queue Processing (Email)

**Steps:**

1. Ensure at least 1 pending notification in queue
2. Run queue processor manually:
   ```bash
   php scripts/process_notifications_queue.php
   ```
3. Check output logs
4. Check email inbox

**Expected Results:**
- ✅ Console output shows: `Sent: 1`
- ✅ Queue item status changed to `sent`
- ✅ Email received in inbox (check spam folder)
- ✅ Notification status in `notifications` table = `sent`

**Verification Queries:**

```sql
-- Check queue processed
SELECT id, status, processed_at, attempts 
FROM notification_queue 
WHERE id = [QUEUE_ID];

-- Check notification logs
SELECT * FROM notification_logs 
WHERE notification_id = [NOTIFICATION_ID] 
ORDER BY created_at DESC;
```

---

### Test Case 4: Quiet Hours Enforcement

**Steps:**

1. Set user preferences: Quiet Hours = 22:00 - 08:00
2. Change system time to 23:00 (or create notification scheduled for 23:00)
3. Create notification
4. Run queue processor
5. Check queue status

**Expected Results:**
- ✅ Queue item status = `pending`
- ✅ `scheduled_at` updated to tomorrow 09:00
- ✅ Console output: `Skipped: 1` (quiet hours)

**Verification Query:**
```sql
SELECT id, scheduled_at, status 
FROM notification_queue 
WHERE id = [QUEUE_ID];
-- scheduled_at should be next day 09:00
```

---

### Test Case 5: Rate Limiting

**Steps:**

1. Check rate limit settings:
   ```sql
   SELECT * FROM notification_rate_limits WHERE company_id = 1 AND channel = 'email';
   ```
2. Send 105 notifications quickly (via script or manual loop)
3. Run queue processor
4. Check how many were sent

**Expected Results:**
- ✅ First 100 sent successfully (hourly limit)
- ✅ Remaining 5 rescheduled for +1 hour
- ✅ `notification_rate_limits` table updated (count_current=100)

**Verification Queries:**

```sql
-- Check rate limit counter
SELECT channel, count_current, limit_hourly, reset_at 
FROM notification_rate_limits 
WHERE company_id = 1;

-- Check rescheduled items
SELECT id, scheduled_at, status 
FROM notification_queue 
WHERE status = 'pending' AND scheduled_at > NOW();
```

---

### Test Case 6: Failed Notification Retry

**Steps:**

1. Simulate email failure (use invalid SMTP settings temporarily)
2. Create notification
3. Run queue processor → will fail
4. Restore correct SMTP settings
5. Wait 1 hour (or manually trigger):
   ```bash
   php scripts/retry_failed_notifications.php
   ```

**Expected Results:**
- ✅ First attempt: status=`failed`, attempts=1
- ✅ Retry script: status changed to `pending`
- ✅ Queue processor: successfully sent, status=`sent`
- ✅ Attempts counter incremented

**Verification Query:**
```sql
SELECT id, status, attempts, error_message, processed_at 
FROM notification_queue 
WHERE id = [QUEUE_ID];
```

---

### Test Case 7: Template Rendering

**Steps:**

1. Create custom template in database:
   ```sql
   INSERT INTO notification_templates 
   (slug, name, email_subject, email_body, company_id, enabled) 
   VALUES 
   ('test_template', 'Test Template', 
    'Hello {{user_name}}', 
    'Vehicle {{vehicle_number}} expires on {{expiry_date}}', 
    NULL, 1);
   ```
2. Create notification with template variables:
   ```php
   $notification->create([
       'type' => 'test_template',
       'template_vars' => [
           'user_name' => 'John Doe',
           'vehicle_number' => 'B123ABC',
           'expiry_date' => '2025-02-01'
       ]
   ]);
   ```
3. Check rendered message

**Expected Results:**
- ✅ Variables replaced: `Hello John Doe`
- ✅ Body: `Vehicle B123ABC expires on 2025-02-01`

---

### Test Case 8: Multi-Tenancy Isolation

**Objective:** Ensure Company A cannot see Company B's notifications.

**Steps:**

1. Login as Company A user
2. Note user_id and company_id
3. Create notification for Company A
4. Login as Company B user
5. Navigate to Notifications page
6. Try to access Company A notification by direct URL manipulation

**Expected Results:**
- ✅ Company B user sees 0 notifications (or only their own)
- ✅ Direct URL access to Company A notification returns 403 or 404
- ✅ Database queries filtered by company_id

**Verification:**
```sql
-- Check no cross-company data leak
SELECT * FROM notifications 
WHERE company_id = [COMPANY_A_ID];
-- Should return rows only when logged in as Company A
```

---

### Test Case 9: Cleanup Script

**Steps:**

1. Create old test data:
   ```sql
   -- Old queue items
   INSERT INTO notification_queue 
   (notification_id, user_id, company_id, channel, status, processed_at) 
   VALUES (999, 1, 1, 'email', 'sent', DATE_SUB(NOW(), INTERVAL 60 DAY));
   
   -- Old notifications
   INSERT INTO notifications 
   (user_id, company_id, type, title, message, is_read, read_at) 
   VALUES (1, 1, 'test', 'Old', 'Test', 1, DATE_SUB(NOW(), INTERVAL 100 DAY));
   ```

2. Run cleanup script:
   ```bash
   php scripts/cleanup_notifications.php
   ```

3. Check results

**Expected Results:**
- ✅ Old queue items deleted (>30 days)
- ✅ Old read notifications deleted (>90 days)
- ✅ Console output shows counts
- ✅ Tables optimized

---

### Test Case 10: SuperAdmin Dashboard

**Steps:**

1. Login as SuperAdmin
2. Navigate to: `SuperAdmin → Notifications Dashboard`
3. Select date range: Last 30 days
4. Select company filter: All Companies
5. Check KPI cards, charts, tables

**Expected Results:**
- ✅ KPI cards show correct totals (Total, Sent, Failed, Queue)
- ✅ Timeline chart renders with data
- ✅ Channel distribution chart shows percentages
- ✅ Company comparison table shows top 10
- ✅ Export button downloads CSV

**Verification:**
- Cross-check dashboard numbers with direct SQL queries
- Ensure multi-tenant data aggregation works

---

## 4. Automated Testing

### Unit Tests (PHPUnit)

**Setup:**

```bash
# Install PHPUnit
composer require --dev phpunit/phpunit

# Run tests
./vendor/bin/phpunit tests/
```

**Test Structure:**

```
tests/
├── Unit/
│   ├── NotificationPreferenceTest.php
│   ├── NotificationQueueTest.php
│   └── NotificationTemplateTest.php
├── Integration/
│   ├── NotificationFlowTest.php
│   └── QueueProcessorTest.php
└── bootstrap.php
```

**Sample Unit Test:**

```php
// tests/Unit/NotificationPreferenceTest.php
class NotificationPreferenceTest extends PHPUnit\Framework\TestCase {
    
    public function testCreateOrUpdate() {
        $model = new NotificationPreference();
        $result = $model->createOrUpdate(1, 1, [
            'email_enabled' => 1,
            'sms_enabled' => 0,
            'frequency' => 'daily'
        ]);
        
        $this->assertTrue($result);
    }
    
    public function testIsInQuietHours() {
        $model = new NotificationPreference();
        $prefs = [
            'quiet_hours' => ['start' => '22:00', 'end' => '08:00'],
            'timezone' => 'Europe/Bucharest'
        ];
        
        // Simulate 23:00 (in quiet hours)
        $result = $model->isInQuietHours($prefs, '2025-01-12 23:00:00');
        $this->assertTrue($result);
        
        // Simulate 10:00 (not in quiet hours)
        $result = $model->isInQuietHours($prefs, '2025-01-12 10:00:00');
        $this->assertFalse($result);
    }
}
```

---

### Integration Tests

**Sample Integration Test:**

```php
// tests/Integration/NotificationFlowTest.php
class NotificationFlowTest extends PHPUnit\Framework\TestCase {
    
    public function testEndToEndFlow() {
        // 1. Set user preferences
        $prefsModel = new NotificationPreference();
        $prefsModel->createOrUpdate(1, 1, [
            'email_enabled' => 1,
            'frequency' => 'immediate'
        ]);
        
        // 2. Create notification
        $notifModel = new Notification();
        $notifId = $notifModel->create([
            'user_id' => 1,
            'company_id' => 1,
            'type' => 'document_expiry',
            'title' => 'Test',
            'message' => 'Test message'
        ]);
        
        $this->assertGreaterThan(0, $notifId);
        
        // 3. Verify queue entry
        $queueModel = new NotificationQueue();
        $queueItems = $queueModel->getPending(10, 'email');
        $this->assertNotEmpty($queueItems);
        
        // 4. Process queue
        $processor = new NotificationQueueProcessor();
        $result = $processor->processQueue(1);
        $this->assertEquals(1, $result['sent']);
    }
}
```

---

## 5. Performance Testing

### Load Testing

**Objective:** Test system under high notification volume.

**Tools:** Apache JMeter or custom PHP script

**Test Scenario:**

```php
// scripts/load_test_notifications.php
for ($i = 0; $i < 1000; $i++) {
    $notifModel->create([
        'user_id' => rand(1, 50),
        'company_id' => 1,
        'type' => 'system_alert',
        'title' => "Load Test $i",
        'message' => "Testing notification $i"
    ]);
}

$startTime = microtime(true);
$processor = new NotificationQueueProcessor();
$result = $processor->processQueue(1000);
$endTime = microtime(true);

echo "Processed 1000 notifications in " . ($endTime - $startTime) . " seconds\n";
echo "Throughput: " . (1000 / ($endTime - $startTime)) . " notifications/second\n";
```

**Acceptance Criteria:**
- ✅ Process 100 notifications in <10 seconds
- ✅ No database deadlocks
- ✅ Queue remains stable (no stuck items)

---

### Memory Testing

```bash
# Monitor memory usage during processing
php -d memory_limit=128M scripts/process_notifications_queue.php
```

**Acceptance Criteria:**
- ✅ Memory usage < 64MB for 100 notifications
- ✅ No memory leaks (memory returns to baseline after processing)

---

## 6. Security Testing

### Test Case: SQL Injection

**Test Input:**
```php
$_POST['email'] = "test@example.com'; DROP TABLE notifications; --";
```

**Expected:** Input sanitized, no SQL executed

---

### Test Case: XSS in Notifications

**Test Input:**
```php
$title = "<script>alert('XSS')</script>";
```

**Expected:** HTML entities escaped in output

---

### Test Case: Cross-Company Access

**Attempt:** User from Company A tries to access Company B's notification

**Expected:** 403 Forbidden or 404 Not Found

---

## 7. Regression Testing

### After Each Deployment

**Checklist:**

- [ ] User can login
- [ ] Notifications page loads
- [ ] Preferences page loads and saves
- [ ] New notifications appear in list
- [ ] Email delivery works (send test)
- [ ] SMS delivery works (send test)
- [ ] Queue processor runs without errors
- [ ] Retry script runs without errors
- [ ] Cleanup script runs without errors
- [ ] SuperAdmin dashboard loads

---

## 8. Test Case Library

### Quick Reference

| Test ID | Test Name | Priority | Automation |
|---------|-----------|----------|------------|
| TC-001 | Save user preferences | High | Yes |
| TC-002 | Create notification & queue | High | Yes |
| TC-003 | Process email notification | High | Partial |
| TC-004 | Process SMS notification | High | Partial |
| TC-005 | Quiet hours enforcement | Medium | Yes |
| TC-006 | Rate limiting | Medium | Yes |
| TC-007 | Retry failed notifications | High | Yes |
| TC-008 | Template rendering | Medium | Yes |
| TC-009 | Multi-tenancy isolation | Critical | Yes |
| TC-010 | Cleanup script | Low | Yes |
| TC-011 | SuperAdmin dashboard | Medium | No |
| TC-012 | Export reports | Low | No |

---

### Test Execution Log Template

```
┌─────────────────────────────────────────────┐
│  TEST EXECUTION LOG                         │
├─────────────────────────────────────────────┤
│ Date: _______________                       │
│ Tester: _______________                     │
│ Environment: Local / Staging / Production   │
│ Build Version: _______________              │
├─────────────────────────────────────────────┤
│ Test Results:                               │
│                                             │
│ [ ] TC-001: PASS / FAIL / SKIP              │
│ [ ] TC-002: PASS / FAIL / SKIP              │
│ [ ] TC-003: PASS / FAIL / SKIP              │
│ ...                                         │
├─────────────────────────────────────────────┤
│ Issues Found:                               │
│ 1. _________________________________        │
│ 2. _________________________________        │
├─────────────────────────────────────────────┤
│ Sign-off: _______________                   │
└─────────────────────────────────────────────┘
```

---

**Document Version:** 1.0  
**Last Updated:** January 12, 2025  
**Notification System:** V2
