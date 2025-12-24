# QUICK START: Notificări SMS prin Twilio

## ✅ Ce a fost instalat

1. **Twilio SDK** - Instalat prin Composer (v8.10.0)
2. **SmsService** - Clasă nouă în `core/SmsService.php` pentru gestionarea SMS-urilor
3. **Integrare completă** - SMS-urile sunt procesate automat prin coada de notificări

## 🚀 Configurare rapidă (5 minute)

### Pas 1: Obține credențiale Twilio

1. Creează cont gratuit: https://www.twilio.com/try-twilio
2. După verificare, accesează Dashboard
3. Notează:
   - **Account SID** (începe cu AC...)
   - **Auth Token** (click "Show" pentru a-l vedea)

### Pas 2: Obține număr de telefon

1. În Twilio Console: Phone Numbers > Manage > Buy a number
2. Selectează țara (Romania pentru +40)
3. Bifează "SMS" în capabilities
4. Cumpără numărul (trial oferă credit gratuit $15)
5. Notează numărul (ex: +40xxxxxxxxx)

### Pas 3: Configurează în aplicație

**Metoda 1: Interfață Web (Recomandat)**

1. Autentifică-te ca **superadmin**
2. Meniu: **Notificări** > **Setări**
3. Click pe tab-ul **SMS**
4. Completează:
   ```
   Provider: Twilio
   From Number: [numărul tău Twilio]
   Account SID: [din Twilio Dashboard]
   Auth Token: [din Twilio Dashboard]
   ```
5. Click **"Salvează setările SMS"**
6. Testează cu butonul **"Trimite SMS de test"**

**Metoda 2: Linia de comandă**

```powershell
cd C:\wamp64\www\fleet-management
php test_sms_twilio.php
```

Urmează instrucțiunile interactive.

### Pas 4: Configurează utilizatorii

1. Fiecare utilizator trebuie să aibă număr de telefon în profil
2. Format corect: **+40712345678** (cu +40 pentru România)
3. Activează SMS în **Notificări** > **Preferințe** > bifează "SMS"

### Pas 5: Activează procesarea automată

**Windows Task Scheduler:**

```
Program: C:\wamp64\bin\php\php8.1.0\php.exe
Arguments: C:\wamp64\www\fleet-management\scripts\process_notifications_queue.php
Schedule: La fiecare 5 minute
```

**Linux cron:**

```bash
*/5 * * * * cd /var/www/fleet-management && php scripts/process_notifications_queue.php
```

## 🧪 Testare

### Test rapid (CLI):

```powershell
php test_sms_twilio.php
```

### Test prin interfață:

1. **Notificări** > **Setări** > **SMS**
2. Secțiunea "Test SMS"
3. Introdu numărul tău (+40712345678)
4. Click "Trimite SMS de test"
5. Verifică telefonul

### Test notificare completă:

1. Creează o asigurare care expiră în 30 zile
2. **Notificări** > Click "Generează Notificări"
3. Verifică în **Notificări** > Lista
4. Așteaptă 5 minute (sau rulează manual procesorul)
5. Verifică SMS-ul pe telefon

## 📊 Monitorizare

### Verifică coada de notificări:

```powershell
php scripts/debug_queue.php
```

### Verifică log-urile:

- `logs/notifications.log` - Log general
- Baza de date: tabelele `notification_queue` și `notification_logs`

### Status în aplicație:

- **Notificări** > **Setări** - Vezi configurarea
- **Notificări** > Listă - Vezi istoricul

## 💰 Costuri Twilio

- **Credit trial**: $15 gratuit
- **SMS România**: ~$0.08 per mesaj
- **Număr telefon**: ~$1/lună
- **Trial limitations**: Poți trimite doar către numere verificate în Twilio

**Protecție costuri:**
- Limită automată: 20 SMS/oră per companie
- Limită zilnică: 100 SMS/zi per companie
- Mesaje truncate automat la 160 caractere

## ❓ Probleme comune

### "Număr de telefon invalid"
✅ **Soluție**: Folosește formatul +40712345678

### "Configurați Twilio: Account SID..."
✅ **Soluție**: Verifică credențialele în Setări > SMS

### "Twilio API 400 - From number not verified"
✅ **Soluție**: În trial mode, verifică numărul destinatar în Twilio Console

### SMS-urile nu se trimit automat
✅ **Soluție**: Configurează cron job pentru `process_notifications_queue.php`

## 📚 Documentație completă

- **Ghid complet setup**: `docs/SMS_TWILIO_SETUP.md`
- **Ghid utilizatori**: `docs/SMS_USER_GUIDE.md`
- **README principal**: `README.md`

## 🎯 Checklist final

- [ ] Twilio SDK instalat (composer require twilio/sdk)
- [ ] Cont Twilio creat
- [ ] Număr de telefon Twilio achiziționat
- [ ] Credențiale configurate în aplicație
- [ ] SMS de test trimis cu succes
- [ ] Utilizatori au număr de telefon în profil (format +40...)
- [ ] Preferințe SMS activate pentru utilizatori
- [ ] Cron job configurat pentru procesarea cozii
- [ ] Test notificare completă efectuat

**🎉 Gata! Sistemul de notificări SMS este funcțional!**

---

## Suport

Pentru probleme sau întrebări:
- Consultă documentația completă în `docs/`
- Verifică log-urile în `logs/`
- Twilio docs: https://www.twilio.com/docs/sms
