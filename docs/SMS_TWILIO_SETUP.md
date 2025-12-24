# Configurare SMS prin Twilio

Acest document descrie cum să configurați notificările SMS folosind Twilio în aplicația Fleet Management.

## Cerințe

- Cont Twilio (gratuit pentru testare, apoi pay-as-you-go)
- PHP >= 8.0
- Extensia cURL activată
- Composer

## 1. Instalare

Twilio SDK este deja instalat prin Composer. Dacă aveți nevoie să-l reinstalați:

```bash
composer require twilio/sdk
```

## 2. Obținerea credențialelor Twilio

### Pasul 1: Crearea contului
1. Accesați [https://www.twilio.com/](https://www.twilio.com/)
2. Click pe "Sign up" și creați un cont
3. Verificați adresa de email

### Pasul 2: Obținerea Account SID și Auth Token
1. După autentificare, veți fi redirecționat către Dashboard
2. În secțiunea "Account Info" veți găsi:
   - **Account SID** (ex: ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx)
   - **Auth Token** (click pe "Show" pentru a-l vizualiza)
3. Salvați aceste valori - veți avea nevoie de ele

### Pasul 3: Obținerea unui număr de telefon
1. În meniul lateral, accesați "Phone Numbers" > "Manage" > "Buy a number"
2. Selectați țara (pentru România: Romania)
3. Bifați "SMS" în capabilities
4. Click pe "Search" și selectați un număr disponibil
5. Click pe "Buy" (Pentru testare, Twilio oferă credit gratuit)
6. Notați numărul (format: +40xxxxxxxxx sau internațional)

## 3. Configurare în aplicație

### Metodă 1: Prin interfața web (Recomandat)

1. Autentificați-vă ca **superadmin**
2. Accesați **Notificări** > **Setări**
3. Click pe tab-ul **SMS**
4. Completați formularul:
   - **Provider**: Twilio
   - **From Number**: Numărul Twilio obținut (ex: +12345678901)
   - **Account SID**: Account SID-ul din Twilio Dashboard
   - **Auth Token**: Auth Token-ul din Twilio Dashboard
5. Click pe **"Salvează setările SMS"**

### Metodă 2: Prin linia de comandă

Rulați scriptul de test interactiv:

```bash
cd C:\wamp64\www\fleet-management
php test_sms_twilio.php
```

Urmați instrucțiunile de pe ecran pentru a introduce credențialele.

## 4. Testare

### Test prin interfața web

1. După salvarea setărilor SMS, rămâneți pe pagina de setări
2. În secțiunea **"Test SMS"**:
   - Introduceți un număr de telefon valabil (format: +40712345678)
   - Introduceți un mesaj de test (sau lăsați mesajul implicit)
   - Click pe **"Trimite SMS de test"**
3. Verificați telefonul pentru mesajul primit

### Test prin linia de comandă

```bash
php test_sms_twilio.php
```

Urmați instrucțiunile pentru a trimite un SMS de test.

## 5. Activare notificări SMS pentru utilizatori

### Pentru administratori

1. Accesați **Notificări** > **Preferințe**
2. În secțiunea **"Metode de notificare"**, bifați **SMS**
3. Asigurați-vă că aveți un număr de telefon completat în profilul utilizatorului
4. Salvați preferințele

### Configurare număr de telefon

Utilizatorii trebuie să aibă numărul de telefon completat în profilul lor:

1. Accesați **Utilizatori** sau **Profil**
2. Completați câmpul **Telefon** (format internațional: +40712345678)
3. Salvați

## 6. Procesarea cozii de notificări

Pentru ca SMS-urile să fie trimise automat, configurați un cron job:

### Windows (Task Scheduler)

```
Program: php.exe
Arguments: C:\wamp64\www\fleet-management\scripts\process_notifications_queue.php
Schedule: La fiecare 5 minute
```

### Linux (crontab)

```bash
*/5 * * * * cd /path/to/fleet-management && php scripts/process_notifications_queue.php
```

## 7. Monitorizare și depanare

### Log-uri

SMS-urile trimise sunt înregistrate în:
- `logs/notifications.log` - Log general notificări
- `notification_queue` - Tabela din baza de date
- `notification_logs` - Istoric complet

### Verificare status

```bash
php scripts/debug_queue.php
```

### Probleme comune

#### 1. "Număr de telefon invalid"
- **Cauză**: Formatul numărului nu este corect
- **Soluție**: Folosiți formatul internațional E.164: +40712345678

#### 2. "Configurați Twilio: Account SID, Auth Token..."
- **Cauză**: Credențialele Twilio lipsesc sau sunt incorecte
- **Soluție**: Verificați și re-introduceți credențialele în setări

#### 3. "Twilio API 401"
- **Cauză**: Auth Token incorect sau expirat
- **Soluție**: Verificați Auth Token în Twilio Dashboard

#### 4. "Twilio API 400 - From number not verified"
- **Cauză**: În trial mode, puteți trimite doar către numere verificate
- **Soluție**: 
  - Verificați numărul destinatar în Twilio Dashboard
  - SAU upgrade contul la paid account

#### 5. SMS-urile nu se trimit automat
- **Cauză**: Cron job nu este configurat
- **Soluție**: Configurați cron job-ul pentru procesarea cozii

## 8. Costuri

### Twilio Pricing (aproximativ)

- **SMS România (outbound)**: ~$0.08 per SMS
- **Credit trial**: $15 gratuit la înregistrare
- **Număr de telefon**: ~$1/lună

### Optimizare costuri

1. **Limitare rate**: Aplicația limitează automat:
   - 20 SMS/oră per companie
   - 100 SMS/zi per companie

2. **Mesaje concise**: SMS-urile sunt limitate automat la 160 caractere

3. **Ore liniștite**: Configurați quiet hours pentru a evita trimiterea SMS-urilor noaptea

## 9. Alternative la Twilio

Aplicația suportă și alte gateway-uri SMS prin HTTP. Pentru a configura:

1. În setările SMS, selectați **Provider: HTTP**
2. Completați:
   - **URL**: URL-ul gateway-ului
   - **Method**: GET sau POST
   - **Params**: Parametri (folosiți {to} și {message} ca placeholder)

Exemplu pentru un gateway generic:
```
URL: https://api.example.com/send
Method: POST
Params: phone={to}&text={message}&api_key=YOUR_KEY
```

## 10. Securitate

- **Auth Token**: Nu partajați niciodată Auth Token-ul
- **HTTPS**: Twilio folosește doar conexiuni securizate
- **Rate limiting**: Protejează împotriva spam-ului și costurilor excesive
- **Validare numere**: Toate numerele sunt validate înainte de trimitere

## 11. Suport

Pentru probleme sau întrebări:
- Consultați [Twilio Documentation](https://www.twilio.com/docs/sms)
- Verificați log-urile aplicației
- Contactați suportul tehnic

## 12. Checklist configurare completă

- [ ] Cont Twilio creat
- [ ] Account SID și Auth Token obținute
- [ ] Număr de telefon Twilio achiziționat
- [ ] Credențiale configurate în aplicație
- [ ] Test SMS trimis cu succes
- [ ] Utilizatori au număr de telefon completat
- [ ] Preferințe SMS activate pentru utilizatori
- [ ] Cron job configurat pentru procesarea cozii
- [ ] Log-uri verificate

**Felicitări! Notificările SMS prin Twilio sunt acum configurate și funcționale! 🎉**
