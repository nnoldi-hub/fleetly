# 📱 Plan de Dezvoltare - Aplicație Mobilă Fleetly

> **Data început:** 18 Martie 2026  
> **Status:** 🟡 În Dezvoltare  
> **Tehnologie mobilă:** Flutter (recomandat)

---

## 📊 Sumar Progres

| Fază | Status | Progres |
|------|--------|---------|
| Faza 1 - Analiză & Arhitectură | ✅ Complet | 100% |
| Faza 2 - API REST Backend | ✅ Complet | 100% |
| Faza 3 - Aplicație Mobilă | ✅ Complet | 100% |
| Faza 4 - UI/UX Design | ✅ Complet (implementat în cod) | 100% |
| Faza 5 - Testare | ⚪ Neînceput | 0% |
| Faza 6 - Deploy | ⚪ Neînceput | 0% |

**Progres total: ~85%**

---

## 🧱 FAZA 1 — Analiză și Arhitectură
**Durată estimată:** 1 săptămână  
**Status:** ✅ Complet

### 1.1 Audit Backend PHP
- [x] Analiză structură proiect
- [x] Identificare controllere existente
- [x] Identificare modele de date
- [x] Documentare relații între entități
- [x] Identificare business logic critic

### 1.2 Identificare Funcționalități pentru API
- [x] Autentificare (LoginController)
- [x] Vehicule (VehicleController)
- [x] Șoferi (DriverController)
- [x] Documente (DocumentController)
- [x] Mentenanță (MaintenanceController)
- [x] Combustibil (FuelController)
- [x] Asigurări (InsuranceController)
- [x] Notificări (NotificationController)
- [x] Rapoarte (ReportController)
- [x] Service/Workshop (WorkOrderController)

### 1.3 Definire Arhitectură API
- [x] Documentare endpoint-uri complete
- [x] Definire structură răspunsuri JSON
- [x] Planificare autorizare JWT
- [x] Planificare rate limiting
- [x] Definire versionare API (v1)

### 📦 Rezultat Faza 1
- [x] Document de arhitectură API complet

---

## 🛠️ FAZA 2 — Construirea API-ului REST
**Durată estimată:** 4-5 săptămâni  
**Status:** ✅ Complet

### 2.1 Infrastructură API (Săptămâna 1)
- [x] Creare structură directoare `/api/v1/`
- [x] Implementare middleware CORS
- [x] Implementare sistem de routing API
- [x] Implementare handler erori JSON
- [x] Setup logging pentru API

### 2.2 Autentificare JWT (Săptămâna 1-2)
- [x] Instalare bibliotecă JWT (firebase/php-jwt)
- [x] Endpoint `POST /api/v1/auth/login`
- [ ] Endpoint `POST /api/v1/auth/register` (opțional)
- [x] Endpoint `POST /api/v1/auth/refresh`
- [x] Endpoint `POST /api/v1/auth/logout`
- [x] Endpoint `POST /api/v1/auth/forgot-password`
- [x] Middleware validare token JWT
- [x] Testare cu PowerShell/curl

### 2.3 API Vehicule (Săptămâna 2)
- [x] `GET /api/v1/vehicles` - Listă cu paginare
- [x] `GET /api/v1/vehicles/{id}` - Detalii vehicul
- [x] `POST /api/v1/vehicles` - Creare vehicul
- [x] `PUT /api/v1/vehicles/{id}` - Actualizare vehicul
- [x] `DELETE /api/v1/vehicles/{id}` - Ștergere vehicul
- [x] `GET /api/v1/vehicles/{id}/documents` - Documente vehicul
- [x] `GET /api/v1/vehicles/{id}/maintenance` - Istoric mentenanță
- [x] `POST /api/v1/vehicles/{id}/mileage` - Actualizare kilometraj
- [x] Implementare filtrare și căutare
- [x] Testare completă

### 2.4 API Șoferi (Săptămâna 3)
- [x] `GET /api/v1/drivers` - Listă cu paginare
- [x] `GET /api/v1/drivers/{id}` - Detalii șofer
- [x] `POST /api/v1/drivers` - Creare șofer
- [x] `PUT /api/v1/drivers/{id}` - Actualizare șofer
- [x] `DELETE /api/v1/drivers/{id}` - Ștergere șofer
- [x] `GET /api/v1/drivers/{id}/documents` - Documente șofer
- [x] Testare completă

### 2.5 API Documente (Săptămâna 3)
- [x] `GET /api/v1/documents` - Listă documente
- [x] `GET /api/v1/documents/{id}` - Detalii document
- [x] `POST /api/v1/documents` - Upload document
- [x] `PUT /api/v1/documents/{id}` - Actualizare metadate
- [x] `DELETE /api/v1/documents/{id}` - Ștergere document
- [x] `GET /api/v1/documents/expiring` - Documente care expiră
- [x] Implementare upload fișiere
- [x] Testare completă

### 2.6 API Mentenanță (Săptămâna 4)
- [x] `GET /api/v1/maintenance` - Listă înregistrări
- [x] `GET /api/v1/maintenance/{id}` - Detalii mentenanță
- [x] `POST /api/v1/maintenance` - Creare înregistrare
- [x] `PUT /api/v1/maintenance/{id}` - Actualizare
- [x] `DELETE /api/v1/maintenance/{id}` - Ștergere
- [x] `GET /api/v1/maintenance/scheduled` - Mentenanță programată
- [x] Testare completă

### 2.7 API Combustibil (Săptămâna 4)
- [x] `GET /api/v1/fuel` - Listă alimentări
- [x] `GET /api/v1/fuel/{id}` - Detalii alimentare
- [x] `POST /api/v1/fuel` - Înregistrare alimentare
- [x] `PUT /api/v1/fuel/{id}` - Actualizare
- [x] `DELETE /api/v1/fuel/{id}` - Ștergere
- [x] `GET /api/v1/fuel/consumption/{vehicle_id}` - Consum vehicul
- [x] Testare completă

### 2.8 API Asigurări (Săptămâna 5)
- [x] `GET /api/v1/insurance` - Listă asigurări
- [x] `GET /api/v1/insurance/{id}` - Detalii
- [x] `POST /api/v1/insurance` - Creare
- [x] `PUT /api/v1/insurance/{id}` - Actualizare
- [x] `DELETE /api/v1/insurance/{id}` - Ștergere
- [x] `GET /api/v1/insurance/expiring` - Asigurări care expiră
- [x] Testare completă

### 2.9 API Notificări (Săptămâna 5)
- [x] `GET /api/v1/notifications` - Listă notificări
- [x] `GET /api/v1/notifications/unread-count` - Număr necitite
- [x] `POST /api/v1/notifications/{id}/read` - Marchează citită
- [x] `POST /api/v1/notifications/read-all` - Marchează toate citite
- [x] `DELETE /api/v1/notifications/{id}` - Ștergere
- [x] Endpoint pentru push notification registration
- [x] Testare completă

### 2.10 API Dashboard & Rapoarte (Săptămâna 5)
- [x] `GET /api/v1/dashboard/stats` - Statistici generale
- [x] `GET /api/v1/dashboard/alerts` - Alerte active
- [ ] `GET /api/v1/reports/fleet-overview` - Raport flotă (opțional)
- [ ] `GET /api/v1/reports/costs` - Raport costuri (opțional)
- [ ] `GET /api/v1/reports/fuel-consumption` - Raport consum (opțional)
- [x] Testare completă

### 2.11 Securitate & Optimizare
- [x] Validare input pe toate endpoint-urile
- [x] Sanitizare output
- [ ] Implementare rate limiting (opțional)
- [ ] Implementare cache (opțional)
- [ ] Documentare API (Swagger/OpenAPI) (opțional)

### 📦 Rezultat Faza 2
- [x] API REST complet funcțional
- [ ] Documentație Swagger/Postman (opțional)
- [ ] Colecție Postman pentru testare (opțional)

---

## 📱 FAZA 3 — Dezvoltare Aplicație Mobilă (Flutter)
**Durată estimată:** 4-5 săptămâni  
**Status:** ✅ Complet

### 3.1 Setup Proiect Flutter (Săptămâna 1)
- [x] Creare proiect Flutter
- [x] Configurare structură folders (Clean Architecture)
- [x] Setup state management (Riverpod)
- [x] Configurare HTTP client (Dio)
- [x] Setup local storage (flutter_secure_storage)
- [x] Configurare environment (dev/prod)

### 3.2 Modul Autentificare (Săptămâna 1)
- [x] Ecran Login
- [x] Ecran Forgot Password
- [x] Logică JWT token management
- [x] Auto-refresh token
- [x] Remember me functionality
- [x] Logout

### 3.3 Dashboard (Săptămâna 2)
- [x] Layout dashboard
- [x] Carduri statistici
- [x] Lista alerte active
- [x] Shortcut-uri către module
- [x] Pull-to-refresh
- [x] Loading states

### 3.4 Modul Vehicule (Săptămâna 2)
- [x] Listă vehicule cu căutare
- [x] Filtre (tip, status)
- [x] Detalii vehicul
- [x] Formular adăugare/editare
- [x] Istoric mentenanță pe vehicul
- [x] Documente asociate
- [x] Confirmare ștergere

### 3.5 Modul Șoferi (Săptămâna 3)
- [x] Listă șoferi
- [x] Căutare și filtrare
- [x] Detalii șofer
- [x] Formular adăugare/editare
- [x] Documente șofer
- [ ] Asignări vehicule (opțional)

### 3.6 Modul Documente (Săptămâna 3)
- [x] Listă documente
- [x] Filtrare pe tip/status
- [x] Vizualizare document
- [x] Formular adăugare/editare
- [x] Alerte expirare

### 3.7 Modul Mentenanță (Săptămâna 4)
- [x] Listă înregistrări mentenanță
- [x] Detalii mentenanță
- [x] Formular adăugare/editare
- [x] Filtrare pe tip/status
- [x] Costuri și statistici

### 3.8 Modul Combustibil (Săptămâna 4)
- [x] Listă alimentări
- [x] Formular alimentare cu calcul automat
- [x] Detalii alimentare
- [x] Statistici pe vehicul

### 3.9 Modul Asigurări
- [x] Listă asigurări cu filtre (tip, status)
- [x] Detalii asigurare cu status și zile rămase
- [x] Formular adăugare/editare
- [x] Alerte expirare
- [x] Statistici (active, expirând, expirate)

### 3.10 Ecran Notificări
- [x] Listă notificări cu pull-to-refresh
- [x] Marchează ca citită (single tap)
- [x] Marchează toate ca citite
- [x] Ștergere notificare (swipe)
- [x] Filtru necitite
- [x] Badge prioritate (normal, high, urgent)
- [x] Time ago display (acum X minute/ore/zile)

### 3.11 Modul Rapoarte
- [x] Prezentare Flotă (distribuție costuri - pie chart)
- [x] Analiza Costurilor (ultimele 6 luni - bar chart)
- [x] Statistici Mentenanță (planificat vs completat)
- [x] Consum Combustibil (top 3 vehicule)
- [x] Tab selector pentru tipuri rapoarte

### 3.12 Modul Setări
- [x] Profil utilizator
- [x] Setări notificări (toggle-uri)
- [x] Mod întunecat
- [x] Selector limbă (RO/EN/HU)
- [x] Autentificare biometrică
- [x] Deconectare automată
- [x] Schimbare parolă
- [x] Despre/Versiune
- [x] Logout cu confirmare

### 3.13 Notificări Push (Opțional)
- [ ] Integrare Firebase Cloud Messaging
- [ ] Handling notificări în foreground
- [ ] Handling notificări în background
- [ ] Deep linking din notificări
- [x] Listare notificări in-app

### 3.14 Funcționalități Generale
- [ ] Offline mode basic (cache) - opțional
- [x] Error handling global
- [x] Loading states & Pull-to-refresh
- [ ] Analytics integration - opțional
- [ ] Crash reporting (Crashlytics) - opțional

### 📦 Rezultat Faza 3
- [x] Aplicație Flutter funcțională cu 11 module complete
- [ ] Build Android APK (în așteptare testare)
- [ ] Build iOS (necesită Mac)

---

## 🎨 FAZA 4 — UI/UX Design
**Durată estimată:** Paralel cu Faza 3  
**Status:** ⚪ Neînceput

### 4.1 Design System
- [ ] Definire paletă culori
- [ ] Definire tipografie
- [ ] Design componente reutilizabile
- [ ] Iconografie consistentă

### 4.2 Screens Design
- [ ] Login/Register screens
- [ ] Dashboard layout
- [ ] Liste standard
- [ ] Formulare standard
- [ ] Detalii standard
- [ ] Empty states
- [ ] Error states
- [ ] Loading states

### 4.3 Responsive & Adaptiv
- [ ] Suport tablete
- [ ] Landscape mode (unde e cazul)
- [ ] Dark mode

### 📦 Rezultat Faza 4
- [ ] Design system complet
- [ ] Toate ecranele implementate

---

## 🧪 FAZA 5 — Testare
**Durată estimată:** 2 săptămâni  
**Status:** ⚪ Neînceput

### 5.1 Testare API
- [ ] Teste unitare endpoint-uri
- [ ] Teste integrare
- [ ] Teste load/stress
- [ ] Colecție Postman completă

### 5.2 Testare Aplicație Mobilă
- [ ] Teste unitare
- [ ] Teste widget
- [ ] Teste integrare
- [ ] Testare pe emulator Android
- [ ] Testare pe emulator iOS
- [ ] Testare pe device-uri fizice
- [ ] User acceptance testing

### 5.3 Security Testing
- [ ] Penetration testing API
- [ ] Verificare storage securizat
- [ ] Verificare certificate pinning

### 📦 Rezultat Faza 5
- [ ] Raport testare
- [ ] Toate bug-urile critice rezolvate

---

## 🚀 FAZA 6 — Deploy
**Durată estimată:** 1-2 săptămâni  
**Status:** ⚪ Neînceput

### 6.1 Deploy Backend API
- [ ] Setup server API (api.fleetly.ro)
- [ ] Configurare SSL
- [ ] Configurare Nginx/Apache
- [ ] Setup monitoring (opțional)
- [ ] Documentație deployment

### 6.2 Deploy Android
- [ ] Pregătire assets (icon, splash)
- [ ] Configurare Play Console
- [ ] Build release APK/AAB
- [ ] Pregătire listing (descriere, screenshots)
- [ ] Submit pentru review
- [ ] Publicare

### 6.3 Deploy iOS
- [ ] Pregătire assets
- [ ] Configurare App Store Connect
- [ ] Build release IPA
- [ ] Pregătire listing
- [ ] Submit pentru review
- [ ] Publicare

### 6.4 Post-Deploy
- [ ] Monitorizare crash reports
- [ ] Colectare feedback
- [ ] Planificare update-uri

### 📦 Rezultat Faza 6
- [ ] API live pe server
- [ ] App publicată pe Play Store
- [ ] App publicată pe App Store

---

## 📈 FAZA 7 — Optimizări Ulterioare (Backlog)
**Status:** ⚪ Planificat pentru viitor

- [ ] Modul GPS tracking
- [ ] Modul telemetrie OBD-II
- [ ] Modul facturare
- [ ] Modul rutare/navigație
- [ ] Integrare ERP (SAP, etc.)
- [ ] Modul chat intern
- [ ] Rapoarte avansate cu grafice
- [ ] Export PDF din aplicație

---

## 📝 Jurnal Dezvoltare

### 18 Martie 2026
- ✅ Creat plan de dezvoltare
- ✅ Analizat structura proiectului existent
- ✅ Identificat toate controllerele și rute
- ✅ Creat structură `/api/v1/` cu subdirectoare (controllers, middleware, core)
- ✅ Implementat `ApiRouter.php` - sistem de routing pentru API
- ✅ Implementat `ApiResponse.php` - helper pentru răspunsuri JSON standardizate
- ✅ Implementat `JwtHandler.php` - generare și validare JWT tokens
- ✅ Implementat `AuthMiddleware.php` - middleware pentru autentificare
- ✅ Implementat `AuthController.php` - login, logout, refresh, me, profile
- ✅ Implementat `VehicleController.php` - CRUD complet + documents + maintenance + mileage
- ✅ Implementat `DriverController.php` - CRUD complet + documents
- ✅ Implementat `DashboardController.php` - stats + alerts
- ✅ Instalat biblioteca `firebase/php-jwt` v6.11.1
- ✅ Configurat `.htaccess` pentru rutare API
- ✅ Creat proiect Flutter `fleetly_mobile` în `/mobile/`
- ✅ Configurat dependencies: flutter_riverpod, dio, go_router, flutter_secure_storage
- ✅ Implementat structură Clean Architecture (core, features)
- ✅ Implementat modul Auth complet (login, repository, provider)
- ✅ Implementat Dashboard screen cu statistici
- ✅ Implementat modul Vehicles complet:
  - `VehicleModel` - model cu fromJson/toJson
  - `VehiclesRepository` - CRUD operations
  - `VehiclesProvider` - state management
  - `VehiclesListScreen` - listă cu căutare și filtre
  - `VehicleDetailScreen` - detalii vehicul
  - `VehicleFormScreen` - adăugare/editare vehicul
- ✅ Configurat routing cu GoRouter
- 📋 Următorul pas: Implementare modul Șoferi (Drivers)

---

## 📋 Note și Decizii

### Decizii tehnice:
1. **Framework mobil:** Flutter - cross-platform, performanță bună
2. **Autentificare:** JWT cu refresh tokens
3. **State management:** Riverpod (recomandat) sau Bloc
4. **HTTP client:** Dio
5. **Versionare API:** `/api/v1/`

### Dependențe externe necesare:
- `firebase/php-jwt` - pentru JWT în PHP
- Firebase Cloud Messaging - pentru push notifications
- Flutter SDK 3.x+

### Riscuri identificate:
1. Complexitatea migrării de la sesiuni la JWT
2. Timpul necesar pentru testare pe device-uri multiple
3. Procesul de aprobare App Store poate dura

---

## 🔗 Resurse

- [Documentație Flutter](https://flutter.dev/docs)
- [JWT.io](https://jwt.io/)
- [Firebase Cloud Messaging](https://firebase.google.com/docs/cloud-messaging)
- [OpenAPI Specification](https://swagger.io/specification/)

