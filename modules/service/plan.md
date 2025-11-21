# Modul „Service Auto” pentru companii

## 1. Funcționalități principale

### Programări service
- Adăugare/gestionare programări pentru revizii, reparații, schimburi de ulei, etc.
- Selectare service partener sau intern
- Notificări automate înainte de programare

### Istoric intervenții
- Fiecare vehicul are un jurnal cu:
  - Tip intervenție
  - Data
  - Cost
  - Service efectuat
  - Observații

### Mentenanță periodică
- Setare intervale (km/luni) pentru revizii
- Generare automată de notificări când se apropie scadența

### Service parteneri
- Adminul poate înregistra service-uri colaboratoare
- Fiecare service are profil: adresă, contact, tipuri de lucrări

### Costuri și facturi
- Înregistrare costuri per intervenție
- Posibilitate de atașare facturi PDF
- Export rapoarte costuri per vehicul, per lună, etc.

---

## 2. Structură în baza de date

### services
- id
- name
- address
- contact_info
- tenant_id

### service_appointments
- id
- vehicle_id
- service_id
- date
- type (revizie, reparație, etc.)
- status (programat, efectuat, anulat)
- tenant_id

### service_history
- id
- vehicle_id
- date
- type
- cost
- notes
- invoice_file
- tenant_id

### maintenance_rules
- id
- vehicle_id
- interval_km
- interval_months
- last_service_date
- next_due_date
- tenant_id

---

## 3. Interfață pentru admin și superadmin

### Admin firmă
- Vizualizează și gestionează programările
- Adaugă intervenții efectuate
- Primește notificări automate

### Superadmin
- Poate vedea toate service-urile înregistrate
- Poate seta reguli globale de mentenanță
- Poate exporta rapoarte cross-tenant

---

## 4. Automatizări și notificări
- Script zilnic care:
  - Verifică mentenanțele scadente
  - Trimite notificări către admini
  - Sugerează programări

---

## 5. Extensii viitoare
- Integrare cu API-uri de service-uri externe (ex. Bosch, Autonet)
- Estimare costuri pe baza tipului de intervenție
- Modul de aprobare internă înainte de programare

---

## 6. Roadmap dezvoltare
1. Definire tabele SQL și migrări
2. Implementare CRUD pentru service-uri partener
3. Implementare programări service (UI + backend)
4. Jurnal intervenții și atașare facturi
5. Script notificări automate
6. Export rapoarte costuri
7. Interfață superadmin (cross-tenant)
8. Extensii API și estimări costuri

---

> Acest fișier va fi actualizat pe măsură ce modulul evoluează. Fiecare etapă va fi bifată și documentată.
