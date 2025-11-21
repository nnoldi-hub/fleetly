# 🚗 Modul Service Auto - Fleet Management

## 📋 Prezentare Generală

Modulul **Service Auto** este un sistem complet de gestionare a intervențiilor de service pentru flotele de vehicule, suportând atât **servicii externe** (parteneri) cât și **servicii interne** (atelier propriu).

## ✨ Funcționalități Principale

### 1. **Gestionare Servicii** 
- ✅ Adăugare servicii externe (parteneri)
- ✅ Configurare service intern (atelier propriu)
- ✅ Evaluare și rating servicii externe
- ✅ Gestionare contacte și locații

### 2. **Atelier Intern (Workshop)**
- ✅ Dashboard în timp real cu statistici
- ✅ Ordine de lucru cu numerotare automată (WO-2025-001)
- ✅ Tracking costuri automat (piese + manoperă)
- ✅ Alocare mecanici și monitorizare sarcină
- ✅ Checklist inspecție cu 3 statusuri (OK/Atenție/Critic)
- ✅ Cronometru manoperă cu start/stop
- ✅ Gestionare piese utilizate
- ✅ Workflow statusuri: Pending → În Lucru → Așteptare Piese → Finalizat → Livrat

### 3. **Multi-Tenant**
- ✅ Izolare completă pe companii (tenant_id)
- ✅ Fiecare companie are propriile servicii și date

## 🗂️ Structura Fișierelor

```
modules/service/
├── controllers/
│   ├── ServiceController.php      # CRUD servicii (externe/interne)
│   └── WorkOrderController.php    # Gestionare ordine atelier
├── models/
│   ├── Service.php                # Model servicii
│   └── WorkOrder.php              # Model ordine de lucru
├── views/
│   ├── services/
│   │   ├── index.php              # Listă servicii cu filtre
│   │   ├── add.php                # Formular adăugare
│   │   ├── edit.php               # Formular editare
│   │   └── view.php               # Detalii service + statistici
│   └── workshop/
│       ├── dashboard.php          # Dashboard atelier cu stats
│       ├── work_order_add.php     # Formular ordine nouă
│       └── work_order_view.php    # Detalii ordine (checklist/piese/manoperă)
├── api/
├── SERVICE_MODULE_PLAN.md         # Plan complet 18 secțiuni
├── INTERNAL_SERVICE_WORKFLOW.md   # Workflow operațional cu scenarii
└── README.md                      # Acest fișier
```

## 🗄️ Structura Bazei de Date

### Tabele Principale

1. **`services`** - Servicii externe/interne
2. **`work_orders`** - Ordine de lucru atelier
3. **`work_order_parts`** - Piese utilizate
4. **`work_order_labor`** - Înregistrări manoperă (cu cronometru)
5. **`work_order_checklist`** - Checklist inspecție
6. **`service_mechanics`** - Mecanici din atelier
7. **`service_history`** - Istoric intervenții
8. **`service_appointments`** - Programări servicii externe
9. **`maintenance_rules`** - Reguli întreținere automată
10. **`service_notifications`** - Notificări service

### Triggere Automate

- ✅ **Calcul automat costuri** (piese + manoperă = total)
- ✅ **Actualizare ore lucrate** la oprire cronometru
- ✅ **Tracking capacitate atelier** (posturi ocupate)
- ✅ **Generare notificări** la modificări status

### View-uri SQL

- ✅ **`v_maintenance_due`** - Întrețineri scadente
- ✅ **`v_active_work_orders`** - Ordine active cu detalii complete

## 🚀 Instalare și Configurare

### 1. Rulare Migrare SQL

```sql
-- Executați în baza de date tenant
mysql -u root -p fleet_management < sql/migrations/service_module_schema.sql
```

### 2. Verificare Rute

Rutele sunt configurate automat în `config/routes.php`:

```
/service/services           → Listă servicii
/service/services/add       → Adăugare service
/service/services/view?id=X → Detalii service
/service/workshop           → Dashboard atelier
/service/workshop/add       → Ordine nouă
/service/workshop/view?id=X → Detalii ordine
```

### 3. Adăugare Link în Meniu

Editați `includes/sidebar.php` și adăugați:

```php
<li class="nav-item">
    <a class="nav-link" href="<?= ROUTE_BASE ?>/service/services">
        <i class="fas fa-tools"></i> Service Auto
    </a>
</li>
```

## 📊 Utilizare

### Configurare Service Intern

1. Navigați la **Service Auto** → **Servicii**
2. Click **Service Nou**
3. Selectați tip: **Service Intern**
4. Completați:
   - Nume atelier
   - Capacitate posturi (ex: 4)
   - Tarif orar manoperă (ex: 150 RON/h)
   - Program lucru
   - Echipamente disponibile
5. Salvați

### Creare Ordine de Lucru

1. Click **Dashboard Atelier** → **Ordine de Lucru Nouă**
2. Selectați vehicul și introduceți kilometraj
3. Descrieți problema raportată
4. Setați prioritate (Normală/Ridicată/Urgentă)
5. Alocați mecanic (opțional)
6. Estimați costuri și ore
7. Salvați → Se generează automat număr WO-YYYY-NNN

### Lucru pe Ordine

**În pagina de detalii ordine:**

1. **Start Manoperă**: 
   - Click "Start Lucru" → Descriere + tarif orar
   - Cronometru pornește automat
   - Click "Stop" când e gata → Ore calculate automat

2. **Adăugare Piese**:
   - Click "Adaugă Piesă" 
   - Completați: cod, denumire, cantitate, preț
   - Cost actualizat automat în sumar

3. **Checklist Inspecție**:
   - Completați fiecare element cu status: OK/Atenție/Critic
   - Adăugați observații
   - Click "Salvează"

4. **Schimbare Status**:
   - **Pending** → Click "Începe Lucru" → **În Lucru**
   - **În Lucru** → Click "Așteptare Piese" → **Waiting Parts**
   - **În Lucru** → Click "Finalizat" → **Completed**
   - **Finalizat** → Click "Livrat" → **Delivered**

## 🔐 Permisiuni

### Admin
- ✅ Creare/editare/ștergere servicii
- ✅ Configurare atelier intern
- ✅ Gestionare completă ordine de lucru
- ✅ Acces toate statistici

### User (Mecanic)
- ✅ Vizualizare servicii
- ✅ Lucru pe ordinele alocate
- ✅ Adăugare piese și manoperă
- ✅ Actualizare checklist
- ❌ Nu poate șterge ordine

## 📈 Raportare și Statistici

### Dashboard Atelier Afișează:
- Posturi ocupate / Capacitate totală
- Ordine finalizate astăzi
- Ordine în lucru acum
- Venit generat astăzi
- Liste filtrabile (status, prioritate, mecanic, dată)

### Pagina Detalii Service Afișează:
- Total intervenții (all-time)
- Servicii luna/anul curent
- Cost total și cost mediu
- Istoric recent (ultimele 10 intervenții)
- Rating și evaluări

## 🔄 Workflow Recomandat

### Pentru Service Extern:
1. Adăugați serviciul ca partener
2. Evaluați cu rating (1-5 stele)
3. Adăugați specialități (mărci specializate)
4. Folosiți pentru programări externe

### Pentru Service Intern:
1. Configurați atelierul (capacitate, tarife)
2. Adăugați mecanici în sistem
3. Creați ordine de lucru pentru vehicule
4. Urmăriți progres în timp real
5. Finalizați și livrați

## 🛠️ Dezvoltare Viitoare

### Următoarele Funcționalități (Opțional):
- [ ] Module programări pentru servicii externe
- [ ] Rapoarte PDF pentru ordine de lucru
- [ ] Notificări push pentru schimbări status
- [ ] Drag & drop alocare mecanici (Kanban)
- [ ] API REST pentru integrări externe
- [ ] Mobile app pentru mecanici
- [ ] Scanner QR pentru piese

## 📞 Suport

Pentru probleme sau întrebări despre modul:
- Consultați documentația detaliată: `SERVICE_MODULE_PLAN.md`
- Workflow-uri operaționale: `INTERNAL_SERVICE_WORKFLOW.md`
- Exemple cod: verificați controller-ele și model-ele

## 📝 Changelog

### Versiunea 1.0 (Ianuarie 2025)
- ✅ Implementare completă CRUD servicii
- ✅ Atelier intern cu ordine de lucru
- ✅ Tracking costuri automat
- ✅ Checklist inspecție
- ✅ Cronometru manoperă
- ✅ Dashboard statistici real-time
- ✅ Multi-tenant support
- ✅ 8 triggere SQL automate
- ✅ 2 view-uri SQL raportare

---

**Dezvoltat pentru Fleet Management System**  
**© 2025 - Toate drepturile rezervate**
