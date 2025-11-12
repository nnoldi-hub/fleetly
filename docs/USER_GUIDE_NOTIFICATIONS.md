# USER GUIDE: Notification System V2
## Fleet Management System

---

## 📖 Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [User Preferences](#user-preferences)
4. [Notification Types](#notification-types)
5. [Managing Notifications](#managing-notifications)
6. [Troubleshooting](#troubleshooting)
7. [FAQ](#faq)

---

## 1. Introduction

### What's New in V2?

The new notification system offers:
- ✅ **Personalized Settings**: Control how and when you receive notifications
- ✅ **Multi-Channel Delivery**: Email, SMS, Push, In-App
- ✅ **Smart Scheduling**: Daily/weekly summaries, quiet hours
- ✅ **Template-Based Messages**: Consistent, professional notifications
- ✅ **Rate Limiting**: No spam, controlled delivery
- ✅ **Reliable Queue**: Automatic retry on failures

---

## 2. Getting Started

### Accessing Notifications

1. **View Notifications:**
   - Click the **Bell icon** (🔔) in top navigation
   - Or navigate to: **Menu → Notifications**

2. **Notification Badge:**
   - Red badge shows unread count
   - Updates in real-time

### First-Time Setup

**Recommended Steps:**

1. Go to **Notifications → Preferințe** (Preferences)
2. Enable your preferred channels (Email, SMS)
3. Select notification types you want to receive
4. Set quiet hours if needed
5. Click **Salvează Preferințe** (Save)

---

## 3. User Preferences

### Accessing Preferences

**Path:** `Notifications → Preferințe` or direct URL: `/notifications/preferences`

### Channel Settings

#### 📱 **In-App Notifications**
- **Default:** Enabled
- **Description:** Shows in notification center
- **Best for:** Real-time alerts while using the app

#### 📧 **Email Notifications**
- **Setup:**
  1. Check "Email" toggle
  2. (Optional) Enter alternative email
  3. Leave blank to use account email
- **Best for:** Important updates, daily summaries

#### 💬 **SMS Notifications**
- **Setup:**
  1. Check "SMS" toggle
  2. Enter phone number (format: +40712345678)
  3. Ensure correct international format
- **⚠️ Note:** May incur carrier charges
- **Best for:** Critical alerts, urgent expiries

#### 🔔 **Push Notifications**
- **Status:** Coming soon (requires mobile app)

---

### Notification Types

Select which categories you want to receive:

| Type | Description | Priority |
|------|-------------|----------|
| **📄 Expirare Documente** | Document expiration alerts | High |
| **🛡️ Expirare Asigurări** | Insurance expiration | High |
| **🔧 Mentenanță Scadentă** | Maintenance due | Medium |
| **⚠️ Alerte Sistem** | System alerts | Varies |
| **⛽ Cheltuieli Combustibil** | Fuel expense alerts | Low |
| **🪪 Permise Conducere** | Driver license expiration | High |

**Tip:** Enable all types and use **Priority Filter** to control volume.

---

### Frequency Settings

#### **⚡ Imediat (Immediate)**
- ✅ Instant delivery as events occur
- ✅ Best for time-sensitive alerts
- ❌ May receive many notifications

#### **📅 Rezumat Zilnic (Daily Summary)**
- ✅ One email/SMS per day at 6:00 AM
- ✅ Reduces inbox clutter
- ❌ Less immediate for urgent items

#### **📆 Rezumat Săptămânal (Weekly Summary)**
- ✅ One email/SMS per week (Monday 9:00 AM)
- ✅ Minimal interruption
- ❌ Not suitable for urgent alerts

**Recommendation:** Start with **Immediate** for 1 week, then switch to **Daily** if volume is high.

---

### Quiet Hours (Do Not Disturb)

**Purpose:** No notifications during sleep/off-hours.

**Settings:**
- **Start Time:** When to begin quiet mode (default: 22:00)
- **End Time:** When to resume notifications (default: 08:00)
- **Timezone:** Select your local timezone

**Example:**
```
Quiet Hours: 22:00 - 08:00 (Europe/Bucharest)
```
- Notification at 23:30 → Delayed until 08:00 next day
- Notification at 09:00 → Delivered immediately

**Note:** In-app notifications are NOT affected (always visible).

---

### Advanced Settings

#### **Prioritate Minimă (Minimum Priority)**
- **Scăzută (Low):** Receive all notifications
- **Medie (Medium):** Skip low-priority items
- **Înaltă (High):** Only critical/urgent alerts

#### **Zile înainte de expirare (Days Before Expiry)**
- **Range:** 7 - 90 days
- **Default:** 30 days
- **Example:** Set to 60 days for insurance renewals

---

### Testing Configuration

**After saving preferences:**

1. Click **Trimite Notificare Test** (Send Test)
2. Check enabled channels:
   - In-App: Notification center
   - Email: Inbox (check spam folder)
   - SMS: Phone message
3. Verify delivery within 5 minutes

**Troubleshooting Test:**
- ❌ Email not received → Check spam, verify email address
- ❌ SMS not received → Verify phone format (+40...), check carrier
- ❌ Delayed delivery → Check quiet hours, cron jobs running

---

## 4. Notification Types

### 📄 Document Expiry Notifications

**Triggered when:**
- Document expires in X days (per your setting)
- Document has expired

**Information included:**
- Document type (e.g., "ITP", "RCA")
- Vehicle registration number
- Expiry date
- Days remaining

**Actions:**
- Click notification → View document details
- Renew document before expiry

---

### 🛡️ Insurance Expiry Notifications

**Triggered when:**
- Insurance policy expires in X days
- Policy has expired

**Information included:**
- Insurance type (RCA, CASCO, etc.)
- Policy number
- Vehicle details
- Expiry date

**Priority:** High (critical for legal compliance)

---

### 🔧 Maintenance Due Notifications

**Triggered when:**
- Scheduled maintenance approaching
- Maintenance overdue

**Information included:**
- Maintenance type
- Vehicle registration
- Due date or mileage
- Last service date

**Tip:** Set reminder 14 days before to book appointment.

---

### ⚠️ System Alerts

**Types:**
- Security alerts (login from new location)
- System updates
- Feature announcements
- Important policy changes

**Priority:** Varies (low to high)

---

## 5. Managing Notifications

### Viewing Notifications

**Notification Center:**
1. Click bell icon (🔔)
2. See list of recent notifications
3. Red dot = unread

**Full List:**
- Navigate to **Notifications → Alerte**
- Filter by type, priority, status
- Pagination for older items

---

### Marking as Read

**Single notification:**
- Click notification in list
- Automatically marked as read

**Mark all as read:**
- Click **Marchează toate ca citite** button
- Clears unread badge

---

### Filtering Notifications

**Available filters:**

| Filter | Options |
|--------|---------|
| **Tip Notificare** | All types / specific category |
| **Prioritate** | All / High / Medium / Low |
| **Status** | All / Unread / Read |
| **Per pagină** | 10 / 25 / 50 / 100 |

**Quick filters:**
- **🔴 Urgent** → High priority + Unread
- **📄 Documente** → Document expiry type
- **🛡️ Asigurări** → Insurance expiry type

---

### Bulk Actions

**Mark all as read:**
```
Notifications → Alerts → Marchează toate ca citite
```

**Generate notifications manually (Admin):**
```
Notifications → Generează Notificări
```
- Scans all vehicles, documents, insurance
- Creates notifications for expiring items

---

## 6. Troubleshooting

### Not Receiving Email Notifications

**Check 1: Preferences**
- Go to Preferences
- Verify "Email" is enabled
- Check email address is correct

**Check 2: Spam Folder**
- Search for "Fleet Management" or sender email
- Mark as "Not Spam" if found

**Check 3: Quiet Hours**
- Check if current time is in quiet hours
- Notifications will be delayed until end time

**Check 4: Queue Status (Admin)**
- Ask admin to check notification queue
- May be processing delays

---

### Not Receiving SMS Notifications

**Check 1: Phone Number Format**
- Must include country code: `+40712345678`
- No spaces or special characters

**Check 2: SMS Credits (Admin)**
- Company may have run out of SMS credits
- Contact admin to refill

**Check 3: Carrier Issues**
- Some carriers block automated SMS
- Try alternative phone number

---

### Too Many Notifications

**Solution 1: Change Frequency**
- Switch to "Daily Summary"
- Receive one email per day

**Solution 2: Adjust Priority**
- Set minimum priority to "Medium"
- Reduces notification volume by 50-70%

**Solution 3: Disable Types**
- Uncheck less important categories
- Keep only critical alerts (Insurance, Documents)

**Solution 4: Enable Quiet Hours**
- Set quiet hours: 20:00 - 08:00
- Notifications delivered only in morning

---

### Delayed Notifications

**Normal delays:**
- Queue processes every 5 minutes
- Email delivery: 1-5 minutes
- SMS delivery: 1-10 minutes

**Excessive delays (>30 min):**
- Contact system administrator
- May indicate cron job issues

---

### Duplicate Notifications

**Cause:** Multiple channels enabled

**Example:**
- Email + SMS + In-App = 3 notifications for same event

**Solution:**
- Keep only preferred channel enabled
- Or use "Daily Summary" to consolidate

---

## 7. FAQ

### Q1: Can I receive notifications for multiple vehicles?

**A:** Yes, if you have access to multiple vehicles (assigned driver, manager role), you'll receive notifications for all of them.

---

### Q2: Can I customize notification messages?

**A:** No, users cannot customize messages. Templates are managed by administrators to ensure consistency. However, you can control WHICH notifications you receive.

---

### Q3: What happens if I change my email?

**A:** Update your email in **Profile Settings**. Notification preferences will automatically use the new email. No need to re-configure.

---

### Q4: Can I mute notifications temporarily?

**A:** Yes, two options:
1. **Quiet Hours:** Set to 00:00 - 23:59 (mutes for 24h)
2. **Disable All Channels:** Uncheck Email/SMS (keep In-App for reference)

**Remember to re-enable when needed!**

---

### Q5: Do notifications work on mobile?

**A:** Currently:
- ✅ Email: Yes (mobile email app)
- ✅ SMS: Yes (native SMS)
- ❌ Push: Not yet (requires mobile app - coming soon)
- ✅ In-App: Yes (via mobile browser)

---

### Q6: Can I export notification history?

**A:** Contact administrator. SuperAdmin can export reports with:
- Date range
- Notification types
- Delivery status
- Company-wide statistics

---

### Q7: What if I miss a critical notification?

**A:** System retries failed notifications up to 3 times. If still failed:
1. Check **Notification Center** (in-app always visible)
2. Check **Notifications → Alerts** page
3. Review vehicles/documents directly for expiry dates

---

### Q8: Are notifications secure?

**A:** Yes:
- ✅ Multi-tenant isolation (you only see your company data)
- ✅ Encrypted email transmission (TLS)
- ✅ No sensitive data in SMS (only IDs/dates)
- ✅ Audit logs for all actions

---

### Q9: Can managers see my notification preferences?

**A:** No, preferences are private. However:
- Admins can see delivery statistics (sent/failed counts)
- SuperAdmin can see aggregate data (not individual settings)

---

### Q10: How do I stop receiving test notifications?

**A:** Test notifications are one-time only. Triggered manually via "Trimite Notificare Test" button. They do not repeat automatically.

---

## 📞 Support

**Need Help?**

1. **Check this guide first** (most issues covered)
2. **Contact your company admin** (for company-specific settings)
3. **Email support:** support@fleetmanagement.com
4. **Phone:** +40 XXX XXX XXX (business hours)

---

## 📝 Quick Reference Card

```
┌─────────────────────────────────────────────┐
│  NOTIFICATION PREFERENCES QUICK SETUP       │
├─────────────────────────────────────────────┤
│ 1. Enable channels (Email ✓, SMS ✓)        │
│ 2. Select all types (or customize)          │
│ 3. Set frequency: Immediate (recommended)   │
│ 4. Set quiet hours: 22:00 - 08:00          │
│ 5. Set days before expiry: 30 days         │
│ 6. Save & send test notification            │
└─────────────────────────────────────────────┘

RECOMMENDED SETTINGS FOR DIFFERENT ROLES:
┌──────────────┬─────────┬──────────┬──────────┐
│ Role         │ Email   │ SMS      │ Freq     │
├──────────────┼─────────┼──────────┼──────────┤
│ Fleet Mgr    │ ✓       │ ✓        │ Immediate│
│ Driver       │ ✓       │ ✗        │ Daily    │
│ Maintenance  │ ✓       │ ✓        │ Immediate│
│ Admin        │ ✓       │ ✗        │ Daily    │
└──────────────┴─────────┴──────────┴──────────┘
```

---

**Document Version:** 2.0  
**Last Updated:** January 12, 2025  
**Notification System:** V2 (Queue-based Architecture)
