# Modul Import CSV - Ghid de utilizare

## Descriere
Modulul de import CSV permite încărcarea în masă a datelor din fișiere Excel/CSV pentru:
- **Vehicule** - mașini, camioane, utilaje
- **Documente** - ITP, RCA, roviniete, tahografe
- **Șoferi** - personal, permise, date angajare

## Acces
Meniul: **Import CSV** (icon: 📥)
URL: `http://localhost/fleet-management/import`

## Cum funcționează

### 1. Pentru Vehicule

#### Pasul 1: Descarcă template
- Click pe butonul **"Descarcă Template Vehicule"**
- Se va descărca `template_vehicule.csv` cu coloanele corecte și un exemplu

#### Pasul 2: Completează datele
Deschide fișierul în Excel și completează coloanele:

| Coloană | Obligatoriu | Descriere | Exemple |
|---------|-------------|-----------|---------|
| `marca` | ✅ DA | Producător | Dacia, Ford, Mercedes |
| `model` | ✅ DA | Model vehicul | Logan, Focus, Sprinter |
| `an_fabricatie` | ❌ Nu | An producție | 2020, 2019 |
| `numar_inmatriculare` | ✅ DA | Nr. înmatriculare UNIC | B-123-ABC |
| `vin` | ❌ Nu | Cod șasiu (17 caractere) | UU1LSDA12ABC123456 |
| `tip_vehicul` | ❌ Nu | Tip | sedan, suv, van, camion, autoutilitara, motocicleta |
| `culoare` | ❌ Nu | Culoare caroserie | Alb, Negru, Roșu |
| `capacitate_cilindrica` | ❌ Nu | cm³ | 1500, 2000 |
| `putere_motor` | ❌ Nu | CP | 90, 150 |
| `tip_combustibil` | ❌ Nu | Combustibil | benzina, motorina, hibrid, electric, gpl |
| `numar_locuri` | ❌ Nu | Locuri pe scaune | 5, 7, 2 |
| `capacitate_incarcatura` | ❌ Nu | kg | 450, 1000 |
| `kilometraj_initial` | ❌ Nu | km la achiziție | 50000, 0 |
| `data_achizitie` | ❌ Nu | Format YYYY-MM-DD | 2020-01-15 |
| `pret_achizitie` | ❌ Nu | Lei (fără separatori) | 45000, 120000 |
| `status` | ❌ Nu | Status | activ, inactiv, service, scos_din_uz |

#### Pasul 3: Salvează CSV
- **File → Save As**
- Selectează: **CSV UTF-8 (Comma delimited) (*.csv)**
- Salvează fișierul

#### Pasul 4: Încarcă fișierul
- Click pe **"Choose File"** și selectează CSV-ul
- Click pe **"Începe Import Vehicule"**
- Așteaptă procesarea

#### Rezultat
- Mesaj de succes: **"Import finalizat: X vehicule adaugate, Y erori"**
- Dacă sunt erori, vei vedea lista detaliată pentru fiecare linie

---

### 2. Pentru Documente

#### Pasul 1: Descarcă template
- Click pe **"Descarcă Template Documente"**
- Se descarcă `template_documente.csv`

#### Pasul 2: Completează datele

| Coloană | Obligatoriu | Descriere | Exemple |
|---------|-------------|-----------|---------|
| `numar_inmatriculare_vehicul` | ✅ DA | Trebuie să existe în baza de date | B-123-ABC |
| `tip_document` | ✅ DA | Tip document | ITP, RCA, Carte Identitate, Rovinieta, Tahograf |
| `numar_document` | ❌ Nu | Număr document | ITP-2024-12345 |
| `data_emitere` | ❌ Nu | Format YYYY-MM-DD | 2024-01-15 |
| `data_expirare` | ✅ DA | Format YYYY-MM-DD | 2025-01-15 |
| `emitent` | ❌ Nu | Instituția emitentă | RAR București, Asigurări XYZ |
| `observatii` | ❌ Nu | Note suplimentare | ITP valabil 1 an |

⚠️ **IMPORTANT**: Vehiculele trebuie să existe deja în baza de date! Importă mai întâi vehiculele.

#### Pasul 3-4: Salvează CSV și încarcă
- Salvează ca **CSV UTF-8**
- Încarcă pe pagina Import → **"Începe Import Documente"**

---

### 3. Pentru Șoferi

#### Pasul 1: Descarcă template
- Click pe **"Descarcă Template Șoferi"**
- Se descarcă `template_soferi.csv`

#### Pasul 2: Completează datele

| Coloană | Obligatoriu | Descriere | Exemple |
|---------|-------------|-----------|---------|
| `nume` | ✅ DA | Nume familie | Popescu, Ionescu |
| `prenume` | ✅ DA | Prenume | Ion, Maria |
| `cnp` | ❌ Nu | CNP unic (13 cifre) | 1850101123456 |
| `data_nasterii` | ❌ Nu | Format YYYY-MM-DD | 1985-01-01 |
| `telefon` | ❌ Nu | Format 07xxxxxxxx | 0721234567 |
| `email` | ❌ Nu | Email valid | ion.popescu@email.ro |
| `adresa` | ❌ Nu | Adresă completă | Str. Exemplu nr. 10 |
| `oras` | ❌ Nu | Oraș reședință | București, Cluj-Napoca |
| `numar_permis` | ❌ Nu | Seria permis | AB123456 |
| `tip_permis` | ❌ Nu | Categorii (separate prin virgulă) | B,C,D sau B, C, D |
| `data_emitere_permis` | ❌ Nu | Format YYYY-MM-DD | 2015-03-15 |
| `data_expirare_permis` | ❌ Nu | Format YYYY-MM-DD | 2025-03-15 |
| `data_angajare` | ❌ Nu | Format YYYY-MM-DD | 2020-06-01 |
| `salariu` | ❌ Nu | Lei (fără separatori) | 3500, 5000 |
| `observatii` | ❌ Nu | Note | Experiență 10 ani |

⚠️ **CNP trebuie să fie unic!** Dacă există deja, șoferul nu va fi importat.

#### Pasul 3-4: Salvează CSV și încarcă
- Salvează ca **CSV UTF-8**
- Încarcă pe pagina Import → **"Începe Import Șoferi"**

---

## Sfaturi și recomandări

### ✅ Bune practici

1. **Testează cu 2-3 rânduri** înainte de import masiv (100+ rânduri)
2. **Verifică duplicate** (numere înmatriculare, CNP-uri)
3. **Folosește formatul corect pentru date**: `YYYY-MM-DD` (ex: 2024-11-07)
4. **Nu folosi separatori de mii**: scrie `45000` în loc de `45.000`
5. **Pentru zecimale folosește punct**: `1500.50` în loc de `1500,50`
6. **Lasă celulele goale** pentru valori lipsă (nu scrie "NULL", "-", "N/A")
7. **Importă în ordine**: Vehicule → Documente → Șoferi

### ❌ Greșeli comune

- ❌ Nu salvezi ca **CSV UTF-8** → caracterele românești (ă, â, î, ș, ț) nu apar corect
- ❌ Folosești virgulă pentru zecimale → `1500,50` se va interpreta greșit
- ❌ Scrii date în format românesc → `15.01.2024` în loc de `2024-01-15`
- ❌ Adaugi documente înainte de vehicule → vehiculul nu există în baza de date
- ❌ CNP duplicat → șoferul nu se va importa

### 🔧 Depanare erori

**Eroare: "Fisier CSV invalid"**
- Verifică că prima linie conține header-ul (numele coloanelor)
- Folosește template-ul descărcat ca bază

**Eroare: "Campuri obligatorii lipsa"**
- Completează toate coloanele marcate cu ✅ DA
- Pentru vehicule: marca, model, numar_inmatriculare
- Pentru documente: numar_inmatriculare_vehicul, tip_document, data_expirare
- Pentru șoferi: nume, prenume

**Eroare: "Vehicul negasit: B-123-ABC"**
- Vehiculul nu există în baza de date
- Importă mai întâi vehiculele sau verifică numărul de înmatriculare

**Caractere românești afișate greșit (Ã, Å¢)**
- La salvare selectează **CSV UTF-8** (nu doar CSV)
- În Excel: File → Save As → CSV UTF-8 (Comma delimited)

**Import reușit parțial (ex: "10 vehicule adaugate, 5 erori")**
- Verifică lista de erori afișată sub mesajul de succes
- Corectează rândurile cu erori în CSV
- Reimportă doar rândurile corectate

---

## Exemple de fișiere

### Exemplu vehicule.csv
```csv
marca,model,an_fabricatie,numar_inmatriculare,vin,tip_vehicul,culoare,tip_combustibil,status
Dacia,Logan,2020,B-123-ABC,UU1LSDA12ABC123456,sedan,Alb,benzina,activ
Ford,Transit,2019,B-456-DEF,WF0XXXGB1XAB12345,van,Albastru,motorina,activ
Mercedes,Sprinter,2021,B-789-GHI,WDB9066331S123456,autoutilitara,Negru,motorina,activ
```

### Exemplu documente.csv
```csv
numar_inmatriculare_vehicul,tip_document,numar_document,data_emitere,data_expirare,emitent
B-123-ABC,ITP,ITP-2024-12345,2024-01-15,2025-01-15,RAR București
B-123-ABC,RCA,RCA-XYZ-2024-001,2024-01-01,2025-01-01,Allianz Țiriac
B-456-DEF,ITP,ITP-2024-12346,2024-02-20,2025-02-20,RAR Ilfov
```

### Exemplu soferi.csv
```csv
nume,prenume,cnp,telefon,email,numar_permis,tip_permis,data_angajare
Popescu,Ion,1850101123456,0721234567,ion.popescu@email.ro,AB123456,"B,C,D",2020-06-01
Ionescu,Maria,2900515234567,0731987654,maria.ionescu@email.ro,CD789012,"B,C",2021-03-15
```

---

## Limitări tehnice

- **Dimensiune maximă fișier**: 2 MB
- **Format acceptat**: Doar CSV (UTF-8)
- **Separator**: Virgulă (`,`)
- **Encoding**: UTF-8 (pentru caractere românești)
- **Multi-tenant**: Datele se importă doar pentru compania curentă

---

## Suport

Dacă întâmpini probleme:
1. Verifică acest ghid pentru soluții
2. Testează cu template-ul original (doar 1 rând de exemplu)
3. Verifică mesajele de eroare detaliate după import
4. Contactează echipa: **office@fleetly.ro** | **0740173581**

---

**Versiune**: 1.0  
**Data**: 07 Noiembrie 2024  
**Creat de**: [conectica-it.ro](https://conectica-it.ro)
