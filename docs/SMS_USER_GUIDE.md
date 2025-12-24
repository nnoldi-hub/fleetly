# Ghid Rapid: Activare Notificări SMS

## Pentru Administratori

### Pasul 1: Configurare Twilio (o singură dată)

1. **Creați cont Twilio**: [https://www.twilio.com/](https://www.twilio.com/)
2. **Obțineți credențialele**:
   - Account SID
   - Auth Token
   - Număr de telefon Twilio

3. **Configurați în aplicație**:
   - Accesați **Notificări** → **Setări** → **SMS**
   - Completați formularul cu datele de la Twilio
   - Salvați

4. **Testați**:
   - În aceeași pagină, introduceți un număr de test
   - Click "Trimite SMS de test"
   - Verificați că ați primit SMS-ul

### Pasul 2: Activare pentru utilizatori

1. **Verificați numerele de telefon**:
   - Accesați **Utilizatori**
   - Asigurați-vă că fiecare utilizator are număr de telefon completat
   - Formatul corect: **+40712345678** (cu +40 pentru România)

2. **Configurați cron job** (pentru trimitere automată):
   ```
   */5 * * * * php /path/to/fleet-management/scripts/process_notifications_queue.php
   ```

## Pentru Utilizatori

### Cum activez notificările SMS?

1. Accesați **Notificări** → **Preferințe**
2. În secțiunea "Metode de notificare", bifați **SMS**
3. Verificați că aveți număr de telefon în profil
4. Salvați preferințele

### Ce notificări voi primi pe SMS?

- Asigurări care expiră în curând
- Mentenanță programată
- Documente care expiră
- Alerte importante

### Cum opresc SMS-urile temporar?

1. Accesați **Notificări** → **Preferințe**
2. Debifați **SMS** din "Metode de notificare"
3. Salvați

**SAU**

1. Configurați **Ore liniștite** (Quiet Hours)
2. SMS-urile vor fi trimise doar în intervalul dorit

## Format număr de telefon

✅ **Corect**:
- +40712345678
- +40 712 345 678

❌ **Incorect**:
- 0712345678 (lipsește +40)
- 40712345678 (lipsește +)
- 712345678 (lipsește prefix țară)

## Întrebări frecvente

**Costă ceva SMS-urile?**
- Da, Twilio percepe o taxă mică per SMS (~$0.08)
- Compania decide dacă activează serviciul

**Cât de des primesc SMS-uri?**
- Sistemul are limite de protecție:
  - Maximum 20 SMS/oră
  - Maximum 100 SMS/zi per companie

**Pot primi SMS-uri și email-uri?**
- Da! Puteți activa ambele metode simultan

**Ce fac dacă nu primesc SMS-uri?**
1. Verificați că numărul de telefon este corect în profil
2. Verificați că SMS este activat în preferințe
3. Contactați administratorul să verifice configurarea Twilio

## Ajutor tehnic

Pentru probleme sau configurare, consultați:
- [Documentație completă](SMS_TWILIO_SETUP.md)
- Administrator sistem
- Support tehnic
