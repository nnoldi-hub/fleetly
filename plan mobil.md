PLAN DE DEZVOLTARE – Aplicație Mobilă Nativă pentru Fleetly
🧱 FAZA 1 — Analiza și arhitectura
🎯 Obiective:
Înțelegerea completă a codului PHP existent

Identificarea modulelor care trebuie expuse prin API

Stabilirea arhitecturii aplicației mobile

🔍 Activități:
Audit backend PHP (structură, modele, controllere)

Identificare funcționalități critice:

Autentificare

Vehicule

Șoferi

Documente

Mentenanță

Notificări

Rapoarte

Definirea arhitecturii API REST:

Endpoint-uri

Structura răspunsurilor

Autorizare (JWT recomandat)

Rate limiting

Versionare API (ex: /api/v1/...)

📦 Rezultat:
Document de arhitectură + listă completă de endpoint-uri.

🛠️ FAZA 2 — Construirea API-ului REST în PHP
Backend-ul actual probabil nu este pregătit pentru mobile. Aici trebuie:

🔧 Ce trebuie implementat:
Autentificare JWT

CRUD complet pentru:

Vehicule

Șoferi

Documente

Service-uri

Combustibil

Asigurări

Notificări

Endpoint-uri pentru rapoarte

Upload fișiere (documente, poze)

Pagination, filtering, sorting

Protecție API:

Rate limiting

CORS

Validare input

Logging

📦 Rezultat:
Backend complet pregătit pentru aplicație mobilă.

📱 FAZA 3 — Dezvoltarea aplicației mobile
Aici alegi tehnologia:

🔹 Flutter (recomandat)
Performanță excelentă

UI identic pe Android și iOS

Dezvoltare rapidă

🔹 React Native
Ecosistem mare

Integrare bună cu web

🔹 Swift/Kotlin
Performanță maximă

Două codebase-uri separate

🧩 Structura aplicației mobile
1. Onboarding + Login
Login cu email/parolă

Resetare parolă

Salvare token JWT

2. Dashboard
Vehicule totale

Șoferi activi

Mentenanță programată

Alerte active

Shortcut-uri către module

3. Module principale
🚗 Vehicule
Listă + căutare + filtre

Detalii vehicul

Documente asociate

Istoric mentenanță

Adăugare / editare vehicul

👨‍✈️ Șoferi
Listă + detalii

Documente

Asignări vehicule

🧾 Documente
Upload

Expirări

Notificări

🛠️ Mentenanță
Programări

Service-uri partenere

Costuri

⛽ Combustibil
Alimentări

Consumul pe vehicul

🔔 Notificări
Push notifications

Alerte expirări

Alerte mentenanță

🎨 FAZA 4 — UI/UX Design
Design modern, mobile-first

Dark mode

Navigație intuitivă

Componente reutilizabile

🧪 FAZA 5 — Testare
Tipuri de testare:
Testare API (Postman)

Testare UI

Testare performanță

Testare offline mode (dacă se implementează)

Testare pe device-uri reale

🚀 FAZA 6 — Deploy
Backend:
Deploy API pe server separat (ideal subdomeniu: api.fleetly.ro)

Configurare SSL

Monitorizare (Grafana, Prometheus)

Mobile:
Build Android (Play Store)

Build iOS (App Store)

Configurare push notifications (Firebase)

📈 FAZA 7 — Optimizări ulterioare
Modul GPS tracking

Modul telemetrie

Modul facturare

Modul rutare

Integrare cu ERP (Pluriva, SAP, etc.)

---

## 📊 STATUS IMPLEMENTARE (Actualizat: Iunie 2025)

### ✅ FAZA 1 — Analiză și arhitectură: **COMPLET 100%**
- ✅ Audit backend PHP complet
- ✅ Identificare funcționalități critice
- ✅ Arhitectură API REST definită
- ✅ Endpoint-uri documentate

### ✅ FAZA 2 — API REST: **COMPLET 100%**
- ✅ Autentificare JWT implementată
- ✅ CRUD complet pentru toate modulele
- ✅ Upload fișiere funcțional
- ✅ Pagination, filtering, sorting
- ✅ Rate limiting, CORS, validare input

### ✅ FAZA 3 — Aplicație mobilă Flutter: **COMPLET 100%**
Module implementate:
- ✅ Auth (login, logout, token refresh)
- ✅ Dashboard (statistici, widgeturi)
- ✅ Vehicles (CRUD, detalii, căutare/filtre)
- ✅ Drivers (CRUD, detalii, asignări)
- ✅ Documents (upload, expirări, filtre)
- ✅ Maintenance (programări, istoric)
- ✅ Fuel (alimentări, consum)
- ✅ Insurance (asigurări, expirări) ✨ NOU
- ✅ Notifications (push, alerte) ✨ NOU
- ✅ Reports (rapoarte interactive, grafice) ✨ NOU
- ✅ Settings (profil, preferințe)

### ✅ FAZA 4 — UI/UX Design: **COMPLET 85%**
- ✅ Design modern Material 3
- ✅ Dark mode funcțional
- ✅ Navigație intuitivă cu GoRouter
- ✅ Componente reutilizabile

### 🟡 FAZA 5 — Testare: **ÎN PROGRES**
- ✅ Analiză cod (flutter analyze) - 0 erori
- ⏳ Testare UI browser (în curs)
- ⏳ Testare pe device-uri reale
- ⏳ Testare API (Postman)

### ⏳ FAZA 6 — Deploy: **AȘTEPTARE**
- ⏳ Configurare Android SDK pentru build APK
- ⏳ Build Android (Play Store)
- ⏳ Build iOS (App Store) - necesită Mac
- ⏳ Configurare Firebase push notifications

### ⏳ FAZA 7 — Optimizări: **PLANIFICAT**
- ⏳ GPS tracking
- ⏳ Telemetrie
- ⏳ Facturare
- ⏳ Rutare

---

### 📝 Note tehnice:
- **Flutter SDK**: v3.41.4
- **State Management**: flutter_riverpod
- **HTTP Client**: Dio cu interceptori JWT
- **Routing**: go_router
- **Locație proiect**: `mobile/`
- **Test în browser**: `flutter run -d chrome --web-port=8090`

### ⚠️ Observații pentru deploy:
1. **Android SDK** nu este instalat pe acest sistem - necesar pentru build APK
2. **iOS build** necesită macOS cu Xcode
3. Aplicația funcționează corect în browser pentru testare