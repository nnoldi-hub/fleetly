# Modul Gestiune Piese - Atelier Intern

## Prezentare Generala

Modulul de gestiune piese permite evidenta completa a pieselor folosite in atelierul intern, oferind:
- Inventar complet de piese cu preturi si stocuri
- Monitorizare stoc minim si alerte automate
- Istoric utilizare piese in ordine de lucru
- Tranzactii stoc (intrari, iesiri, ajustari)
- Rapoarte valoare stoc si consumuri

## Structura Tabel Database

### Tabel: `service_parts`
Inventarul principal de piese:
- `id` - ID unic piesa
- `part_number` - Cod piesa (unic)
- `name` - Nume piesa
- `description` - Descriere detaliata
- `category` - Categorie (Lubrifianti, Filtre, Frane, etc.)
- `manufacturer` - Producator
- `unit_price` - Pret achizitie
- `sale_price` - Pret vanzare
- `quantity_in_stock` - Cantitate totala in stoc
- `minimum_quantity` - Nivel minim pentru alerta
- `unit_of_measure` - Unitate masura (buc, set, kg, l, m)
- `location` - Locatie depozitare
- `supplier` - Furnizor
- `supplier_part_number` - Cod piesa la furnizor
- `notes` - Observatii

### Tabel: `service_parts_usage`
Utilizare piese in ordine de lucru:
- `id` - ID unic
- `work_order_id` - Referinta la comanda lucru
- `part_id` - Referinta la piesa
- `quantity` - Cantitate folosita
- `unit_price` - Pret unitar la utilizare
- `total_price` - Pret total
- `notes` - Observatii
- `created_at` - Data utilizare

### Tabel: `service_parts_transactions`
Istoric tranzactii stoc:
- `id` - ID unic
- `part_id` - Referinta la piesa
- `transaction_type` - Tip: 'in' (intrare), 'out' (iesire), 'adjustment' (ajustare), 'return' (retur)
- `quantity` - Cantitate
- `reference_number` - Numar document (optional)
- `notes` - Observatii
- `created_by` - Utilizator
- `created_at` - Data tranzactie

## Functionalitati

### 1. Gestionare Inventar
- **Lista Piese** (`/service/parts`)
  - Vizualizare toate piesele cu stocuri si preturi
  - Filtrare dupa nume, cod, categorie, producator
  - Indicator stoc minim (alerta rosie pentru piese sub stoc)
  - Statistici: total piese, valoare stoc, piese sub stoc minim
  
- **Adaugare Piesa** (`/service/parts/add`)
  - Cod piesa unic
  - Informatii generale (nume, descriere, categorie, producator)
  - Preturi (achizitie si vanzare cu calcul marja)
  - Stoc initial si nivel minim
  - Locatie depozitare si informatii furnizor

- **Editare Piesa** (`/service/parts/edit/{id}`)
  - Actualizare toate informatiile piesei
  - Vizualizare statistica rapida (stoc, folosit, disponibil)

### 2. Gestiune Stoc
- **Ajustare Stoc** (modal sau `/service/parts/adjustStock/{id}`)
  - Adaugare stoc (intrare): achizitii noi, returnari
  - Scadere stoc (iesire): utilizare, pierderi, defecte
  - Validare cantitate disponibila
  - Obligativitate observatii pentru audit trail

- **Monitorizare Stoc Minim**
  - Alerte vizuale pentru piese sub nivel minim
  - Badge-uri colorate (verde/galben/rosu) dupa nivel stoc
  - Lista separata piese cu stoc minim

### 3. Detalii Piesa
- **Pagina Detalii** (`/service/parts/view/{id}`)
  - Informatii complete piesa
  - Card-uri statistica (stoc total, folosit, disponibil)
  - Valoare stoc (achizitie vs vanzare)
  - Marja profit (%)

- **Istoric Utilizare**
  - Lista toate utilizarile in ordine de lucru
  - Link catre comanda de lucru
  - Vehicul pentru care s-a folosit
  - Cantitate si pret

- **Istoric Tranzactii Stoc**
  - Toate intrarile si iesirile
  - Tip tranzactie (intrare/iesire/ajustare/retur)
  - Data si observatii

### 4. Integrare Work Orders
- **Utilizare Piese in Comenzi**
  - La adaugare/editare Work Order: selectare piese folosite
  - Auto-completare piese cu search
  - Verificare stoc disponibil inainte de salvare
  - Calcul automat cost piese in comanda

- **Triggers Database**
  - Auto-update `parts_cost` in `service_work_orders` cand se adauga/sterge piesa
  - Sincronizare automata costuri

### 5. Raportare
- **Dashboard Piese**
  - Total piese in inventar
  - Valoare totala stoc
  - Numar piese sub stoc minim
  
- **Export/Rapoarte** (viitor)
  - Export lista piese (CSV/Excel)
  - Raport valoare stoc
  - Raport consumuri lunare
  - Raport piese utilizate per vehicul

## Fluxul de Lucru

### Achizitie Piese Noi
1. Accesati `/service/parts/add`
2. Completati informatii piesa (cod, nume, categorie, preturi)
3. Setati cantitate initiala in stoc
4. Salvare → Se creeaza automat tranzactie "intrare" in istoric

### Reaprovizionare Stoc
1. Accesati piesa din lista (`/service/parts`)
2. Click "Ajusteaza Stoc" (buton verde)
3. Selectati "Adauga in Stoc"
4. Introduceti cantitate si observatii (ex: "Achizitie furnizor X, factura Y")
5. Salvare → Stoc actualizat + tranzactie inregistrata

### Utilizare Piese in Reparatii
1. Accesati Work Order (`/service/workshop/edit/{id}`)
2. Sectiunea "Piese Folosite": cautati piesa
3. Selectati piesa si introduceti cantitate
4. Sistem verifica stoc disponibil
5. La salvare: cantitate scazuta din stoc + cost adaugat in comanda

### Monitorizare Stoc Minim
1. Dashboard piese arata alerta daca exista piese sub nivel minim
2. Lista piese: badge rosu pentru piese critice
3. Filtru "Doar stoc minim" pentru lista rapida
4. Reaprovizionati piesele cu stoc scazut

## Permisiuni si Securitate

### Roluri
- **Admin/Manager**: Acces complet (adaugare, editare, stergere piese)
- **Mecanic**: Vizualizare piese, utilizare in work orders
- **Viewer**: Doar vizualizare (fara modificari)

### Validari
- **Cod piesa unic**: Nu pot exista 2 piese cu acelasi cod
- **Stoc disponibil**: Nu se poate scoate mai mult decat exista
- **Piese folosite**: Nu se pot sterge piese deja folosite in comenzi
- **Campuri obligatorii**: Cod, nume, categorie, preturi

## Instalare si Configurare

### Pasul 1: Creare Tabele Database
```bash
mysql -u username -p database_name < sql/migrations/010_service_parts_inventory.sql
```

Sau executati manual SQL-ul din:
`sql/migrations/010_service_parts_inventory.sql`

### Pasul 2: Verificare Rute
Rutele sunt deja adaugate in `config/routes.php`:
- `/service/parts` - Lista piese
- `/service/parts/add` - Adaugare piesa
- `/service/parts/edit/{id}` - Editare piesa
- `/service/parts/view/{id}` - Detalii piesa
- `/service/parts/adjustStock/{id}` - Ajustare stoc
- `/api/parts` - API pentru autocomplete

### Pasul 3: Link in Meniu
Adaugati in sidebar (`includes/sidebar.php`) sub sectiunea Atelier:
```php
<li class="nav-item">
    <a class="nav-link" href="/service/parts">
        <i class="bi bi-boxes"></i> Piese
    </a>
</li>
```

### Pasul 4: Test
1. Accesati `/service/parts`
2. Adaugati cateva piese de test
3. Ajustati stocul
4. Verificati istoricul tranzactiilor

## Date Demo

SQL-ul de migrare include 8 piese demo:
- Ulei motor 5W30
- Filtre ulei si aer
- Placute frana fata si spate
- Antigel
- Lamele stergator
- Baterie 12V

Puteti sterge sau modifica aceste date dupa testare.

## Tehnologii Folosite

- **Backend**: PHP 8.1+ cu arhitectura MVC
- **Database**: MySQL 8.0+ cu tabele InnoDB si triggers
- **Frontend**: Bootstrap 5.3, Bootstrap Icons
- **JavaScript**: Vanilla JS pentru modals si autocomplete

## Extinderi Viitoare

### V2.0
- [ ] Scanare coduri de bare pentru piese
- [ ] Comandare automata piese la stoc minim
- [ ] Integrare furnizori (API)
- [ ] Istoric preturi (evolutie pret achizitie/vanzare)
- [ ] Alerte email pentru stoc minim

### V3.0
- [ ] Locatii multiple (depozite diferite)
- [ ] Transfer piese intre locatii
- [ ] Rezervari piese pentru comenzi planificate
- [ ] Rapoarte avansate (ABC analysis, piese inactive)
- [ ] Mobile app pentru inventariere rapida

## Support

Pentru probleme sau intrebari:
- Verificati logs in `logs/` folder
- Verificati erori PHP in browser console
- Contactati echipa de suport tehnic

## Changelog

### v1.0.0 (2025-01-XX)
- Release initial
- Inventar piese cu CRUD complet
- Gestiune stoc (intrari/iesiri/ajustari)
- Integrare cu Work Orders
- Istoric utilizare si tranzactii
- Alerte stoc minim
- Dashboard statistici
