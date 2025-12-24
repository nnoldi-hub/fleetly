# Plan Dezvoltare Marketplace B2B pentru Flote

**Versiune**: 1.0  
**Data**: 24 Decembrie 2025  
**Status**: Planificare

## 📋 Viziune Generală

### Obiectiv
Dezvoltarea unui marketplace B2B integrat în sistemul Fleet Management unde:
- **SuperAdmin** = Furnizor/Vânzător (poate adăuga produse/servicii)
- **Companii (flote)** = Cumpărători (pot cumpăra conform nevoilor)

### Produse/Servicii Principale
1. **Asigurări** (RCA, CASCO, Cargo)
2. **Roviniete** (România, Ungaria, Bulgaria, etc.)
3. **Cauciucuri** (Vară, Iarnă, All-season)
4. **Piese auto** (Filtre, lichide, baterii, etc.)
5. **Servicii mentenanță** (Revizii, reparații)
6. **Combustibil** (Carduri fleet, contracte)

## 🏗️ Arhitectură Sistem

### Componente Principale

```
┌─────────────────────────────────────────────────────────┐
│                    MARKETPLACE                          │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │   Catalog    │  │   Orders     │  │   Quotes     │ │
│  │   Produse    │  │   Comenzi    │  │   Oferte     │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
│                                                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │  Suppliers   │  │   Cart       │  │  Invoicing   │ │
│  │  Furnizori   │  │   Coș        │  │  Facturare   │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
│                                                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │ Recommendations│ │  Analytics   │  │  Integration│ │
│  │  Recomandări  │  │  Rapoarte    │  │  API        │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### Fluxuri Principale

1. **SuperAdmin** (Furnizor):
   - Adaugă produse/servicii în catalog
   - Setează prețuri (poate fi dinamic, pe bază de volum)
   - Gestionează stocuri (opțional)
   - Vezi cereri de ofertă (RFQ - Request for Quote)
   - Generează oferte personalizate
   - Procesează comenzi
   - Emite facturi

2. **Company Admin** (Cumpărător):
   - Browsing catalog
   - Adaugă produse în coș
   - Request quote pentru volume mari
   - Plasează comenzi
   - Tracking comenzi
   - Vezi facturi
   - Rapoarte achiziții

3. **Integrare cu Fleet Management**:
   - Auto-suggest produse bazat pe:
     - Vehicule (marca, model, an)
     - Asigurări ce expiră
     - Mentenanță programată
     - Istoric achiziții
   - Notificări automate
   - Rapoarte cost total ownership (TCO)

## 📊 Schema Bază de Date

### Tabele Noi (Core DB)

```sql
-- ============================================
-- MARKETPLACE TABLES
-- ============================================

-- 1. Categorii produse
CREATE TABLE mp_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    parent_id INT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES mp_categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_parent (parent_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Furnizori (SuperAdmin poate adăuga furnizori externi)
CREATE TABLE mp_suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    description TEXT,
    logo VARCHAR(255),
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(30),
    website VARCHAR(255),
    address TEXT,
    tax_id VARCHAR(50),
    commission_rate DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Comision % pentru marketplace',
    is_verified TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_orders INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Produse/Servicii
CREATE TABLE mp_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    category_id INT NOT NULL,
    sku VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    short_description TEXT,
    description TEXT,
    
    -- Prețuri
    price DECIMAL(12,2) NOT NULL,
    old_price DECIMAL(12,2) NULL COMMENT 'Pentru reduceri',
    currency VARCHAR(3) DEFAULT 'RON',
    
    -- Prețuri volume (tier pricing)
    tier_pricing JSON COMMENT '[{"min_qty":10,"price":95.00},{"min_qty":50,"price":90.00}]',
    
    -- Stoc
    track_inventory TINYINT(1) DEFAULT 1,
    stock_quantity INT DEFAULT 0,
    low_stock_threshold INT DEFAULT 10,
    
    -- Specificații
    specifications JSON COMMENT 'Specificații tehnice {"brand":"Michelin","size":"195/65R15"}',
    attributes JSON COMMENT 'Atribute filtrabile {"season":"winter","type":"passenger"}',
    
    -- Compatibilitate vehicule
    vehicle_compatibility JSON COMMENT 'Pentru piese/cauciucuri: brands, models, years',
    
    -- Media
    image_main VARCHAR(255),
    images JSON COMMENT 'Array de URL-uri imagini',
    documents JSON COMMENT 'Fișe tehnice, certificate',
    
    -- Livrare
    shipping_weight DECIMAL(8,2) COMMENT 'kg',
    shipping_dimensions VARCHAR(50) COMMENT 'LxWxH cm',
    shipping_time_days INT DEFAULT 3,
    
    -- SEO
    meta_title VARCHAR(255),
    meta_description TEXT,
    
    -- Status
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    is_available TINYINT(1) DEFAULT 1,
    
    -- Stats
    view_count INT DEFAULT 0,
    order_count INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0.00,
    review_count INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (supplier_id) REFERENCES mp_suppliers(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES mp_categories(id),
    INDEX idx_sku (sku),
    INDEX idx_slug (slug),
    INDEX idx_category (category_id),
    INDEX idx_supplier (supplier_id),
    INDEX idx_active (is_active, is_available),
    INDEX idx_featured (is_featured),
    FULLTEXT idx_search (name, short_description, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Coș de cumpărături
CREATE TABLE mp_cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(12,2) NOT NULL COMMENT 'Prețul la momentul adăugării',
    options JSON COMMENT 'Opțiuni selectate (culoare, mărime, etc.)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES mp_products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (company_id, user_id, product_id),
    INDEX idx_company (company_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Request for Quote (Cereri de ofertă)
CREATE TABLE mp_quotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_number VARCHAR(50) NOT NULL UNIQUE,
    company_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('draft','submitted','processing','quoted','accepted','rejected','expired') DEFAULT 'draft',
    
    -- Detalii cerere
    title VARCHAR(255) NOT NULL,
    description TEXT,
    requirements JSON COMMENT 'Cerințe specifice',
    
    -- Produse solicitate
    items JSON COMMENT '[{"product_id":1,"quantity":100,"notes":"..."}]',
    
    -- Delivery
    delivery_address TEXT,
    delivery_deadline DATE,
    
    -- Quote response (de la SuperAdmin)
    quoted_at TIMESTAMP NULL,
    quoted_by INT NULL COMMENT 'user_id SuperAdmin',
    quote_total DECIMAL(12,2),
    quote_notes TEXT,
    quote_valid_until DATE,
    quote_document VARCHAR(255) COMMENT 'PDF ofertă',
    
    -- Acceptance
    accepted_at TIMESTAMP NULL,
    rejected_at TIMESTAMP NULL,
    rejection_reason TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_quote_number (quote_number),
    INDEX idx_company (company_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Comenzi
CREATE TABLE mp_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    company_id INT NOT NULL,
    user_id INT NOT NULL,
    quote_id INT NULL COMMENT 'Dacă comanda vine din quote',
    
    -- Status
    status ENUM('pending','confirmed','processing','shipped','delivered','cancelled','refunded') DEFAULT 'pending',
    payment_status ENUM('pending','paid','partial','refunded') DEFAULT 'pending',
    
    -- Sume
    subtotal DECIMAL(12,2) NOT NULL,
    discount DECIMAL(12,2) DEFAULT 0.00,
    tax DECIMAL(12,2) DEFAULT 0.00,
    shipping DECIMAL(12,2) DEFAULT 0.00,
    total DECIMAL(12,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'RON',
    
    -- Discount codes
    coupon_code VARCHAR(50),
    coupon_discount DECIMAL(12,2) DEFAULT 0.00,
    
    -- Livrare
    shipping_method VARCHAR(100),
    shipping_address TEXT,
    shipping_tracking VARCHAR(255),
    shipping_company VARCHAR(100),
    
    -- Billing
    billing_address TEXT,
    company_name VARCHAR(200),
    tax_id VARCHAR(50),
    
    -- Plată
    payment_method VARCHAR(50),
    payment_ref VARCHAR(255),
    paid_at TIMESTAMP NULL,
    
    -- Tracking events
    confirmed_at TIMESTAMP NULL,
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    cancellation_reason TEXT,
    
    -- Notes
    customer_notes TEXT,
    admin_notes TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (quote_id) REFERENCES mp_quotes(id) ON DELETE SET NULL,
    INDEX idx_order_number (order_number),
    INDEX idx_company (company_id),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Order Items (Produsele din comandă)
CREATE TABLE mp_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    supplier_id INT NOT NULL,
    
    -- Produs snapshot (la momentul comenzii)
    product_name VARCHAR(255) NOT NULL,
    product_sku VARCHAR(100),
    product_image VARCHAR(255),
    
    -- Preț și cantitate
    quantity INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    discount DECIMAL(12,2) DEFAULT 0.00,
    tax DECIMAL(12,2) DEFAULT 0.00,
    total DECIMAL(12,2) NOT NULL,
    
    -- Opțiuni
    options JSON COMMENT 'Opțiuni selectate',
    
    -- Commission tracking
    commission_rate DECIMAL(5,2),
    commission_amount DECIMAL(12,2),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES mp_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES mp_products(id),
    FOREIGN KEY (supplier_id) REFERENCES mp_suppliers(id),
    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Facturi
CREATE TABLE mp_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    order_id INT NOT NULL,
    company_id INT NOT NULL,
    
    -- Status
    status ENUM('draft','sent','paid','overdue','cancelled') DEFAULT 'draft',
    
    -- Sume
    subtotal DECIMAL(12,2) NOT NULL,
    tax DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'RON',
    
    -- Dates
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    paid_at TIMESTAMP NULL,
    
    -- Document
    document_path VARCHAR(255) COMMENT 'PDF factură',
    
    -- Payment
    payment_method VARCHAR(50),
    payment_ref VARCHAR(255),
    
    notes TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES mp_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_order (order_id),
    INDEX idx_company (company_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Review-uri produse
CREATE TABLE mp_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    company_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT NULL,
    
    rating INT NOT NULL COMMENT '1-5 stele',
    title VARCHAR(255),
    review TEXT,
    
    -- Răspuns de la furnizor/admin
    response TEXT,
    response_at TIMESTAMP NULL,
    
    is_verified_purchase TINYINT(1) DEFAULT 0,
    is_approved TINYINT(1) DEFAULT 1,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (product_id) REFERENCES mp_products(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (order_id) REFERENCES mp_orders(id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_approved (is_approved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Recomandări automate (AI/Rule-based)
CREATE TABLE mp_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    product_id INT NOT NULL,
    reason_type ENUM('expiring_insurance','due_maintenance','similar_purchase','trending','seasonal') NOT NULL,
    reason_details JSON COMMENT 'Detalii despre recomandare',
    priority INT DEFAULT 0,
    shown_count INT DEFAULT 0,
    clicked TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES mp_products(id) ON DELETE CASCADE,
    INDEX idx_company_priority (company_id, priority),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Wishlist / Favorite products
CREATE TABLE mp_wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES mp_products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (company_id, user_id, product_id),
    INDEX idx_company_user (company_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Contracte frame (pentru volume mari, prețuri negociate)
CREATE TABLE mp_contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_number VARCHAR(50) NOT NULL UNIQUE,
    company_id INT NOT NULL,
    supplier_id INT NOT NULL,
    
    title VARCHAR(255) NOT NULL,
    description TEXT,
    
    -- Perioada valabilitate
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    
    -- Termeni
    terms TEXT,
    discount_rate DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Discount global %',
    
    -- Produse în contract cu prețuri speciale
    products JSON COMMENT '[{"product_id":1,"special_price":95.00,"min_quantity":10}]',
    
    -- Limite
    minimum_order_value DECIMAL(12,2),
    maximum_credit_limit DECIMAL(12,2),
    payment_terms VARCHAR(100) COMMENT 'ex: Net 30 zile',
    
    -- Status
    status ENUM('draft','active','suspended','expired','terminated') DEFAULT 'draft',
    
    -- Document
    document_path VARCHAR(255) COMMENT 'PDF contract scanat',
    
    created_by INT,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES mp_suppliers(id),
    INDEX idx_contract_number (contract_number),
    INDEX idx_company (company_id),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 🎨 Structură Module Frontend

```
modules/
  marketplace/
    index.php                    # Router marketplace
    
    controllers/
      MarketplaceController.php  # Catalog, browse
      ProductController.php      # Detalii produs
      CartController.php         # Coș cumpărături
      CheckoutController.php     # Finalizare comandă
      OrderController.php        # Istoric comenzi
      QuoteController.php        # Request for Quote
      ReviewController.php       # Review-uri
      
      admin/
        CatalogAdminController.php    # Administrare catalog
        OrderAdminController.php      # Procesare comenzi
        QuoteAdminController.php      # Răspuns la cereri ofertă
        SupplierController.php        # Gestionare furnizori
        InvoiceController.php         # Generare facturi
        AnalyticsController.php       # Rapoarte vânzări
    
    models/
      Product.php
      Category.php
      Supplier.php
      Cart.php
      Order.php
      OrderItem.php
      Quote.php
      Invoice.php
      Review.php
      Contract.php
      Recommendation.php
    
    services/
      PricingService.php         # Calcul prețuri (tier, contracte)
      RecommendationEngine.php   # Recomandări automate
      InventoryService.php       # Gestionare stoc
      ShippingService.php        # Calcul costuri transport
      InvoiceGenerator.php       # Generare facturi PDF
      OrderProcessor.php         # Procesare comenzi
      QuoteGenerator.php         # Generare oferte PDF
      
    views/
      browse.php                 # Catalog produse
      product-detail.php         # Detaliu produs
      cart.php                   # Coș
      checkout.php               # Checkout
      order-confirmation.php     # Confirmare comandă
      orders.php                 # Istoric comenzi
      order-detail.php           # Detaliu comandă
      quote-request.php          # Formular cerere ofertă
      quotes.php                 # Lista cereri ofertă
      
      admin/
        dashboard.php            # Dashboard marketplace
        products.php             # Lista produse
        product-form.php         # Adăugare/editare produs
        orders.php               # Lista comenzi
        order-detail.php         # Detaliu comandă admin
        quotes.php               # Cereri ofertă
        quote-detail.php         # Răspuns la cerere
        suppliers.php            # Lista furnizori
        analytics.php            # Rapoarte
        invoices.php             # Facturi
        
      components/
        product-card.php         # Card produs
        filter-sidebar.php       # Filtre
        recommendation-widget.php # Widget recomandări
        quick-order.php          # Comandă rapidă
```

## 📱 Interfață Utilizator - Wireframes

### 1. Catalog Produse (Company View)

```
┌─────────────────────────────────────────────────────────┐
│  Fleet Management > Marketplace                         │
├─────────────────────────────────────────────────────────┤
│  [Search products...]               [🛒 Cart (3)] [👤] │
├──────────┬──────────────────────────────────────────────┤
│ FILTRE   │  🏷️ Asigurări  🎫 Roviniete  🚗 Cauciucuri  │
│          │  🔧 Piese     ⚙️ Service                     │
│ Categorie├──────────────────────────────────────────────┤
│ □ Asig.  │                                              │
│ □ Rovin. │  ┌──────────┐  ┌──────────┐  ┌──────────┐  │
│ □ Cauci. │  │  RCA     │  │  CASCO   │  │ Rovinieta│  │
│          │  │  Flotă   │  │  Flotă   │  │ Ungaria  │  │
│ Preț     │  │          │  │          │  │          │  │
│ ▓▓▓░░░░  │  │ 450 RON  │  │ 1200 RON │  │  40 EUR  │  │
│ 0 - 5000 │  │ [+Cart]  │  │ [+Cart]  │  │ [+Cart]  │  │
│          │  └──────────┘  └──────────┘  └──────────┘  │
│ Supplier │                                              │
│ ☑ FleetIns│  ┌──────────┐  ┌──────────┐  ┌──────────┐│
│ □ TireXpr│  │Michelin  │  │ Cauciuc  │  │  Filtre  │  │
│          │  │ Iarna    │  │  Vară    │  │  Mann    │  │
│ Evaluare │  │195/65R15 │  │185/60R14 │  │  Set 4   │  │
│ ⭐⭐⭐⭐⭐  │  │ 320 RON  │  │  280 RON │  │  120 RON │  │
│          │  │ [+Cart]  │  │ [+Cart]  │  │ [+Cart]  │  │
│ [Reset]  │  └──────────┘  └──────────┘  └──────────┘  │
└──────────┴──────────────────────────────────────────────┘
│  💡 Recomandări pentru flota ta:                        │
│  • 5 vehicule cu RCA expirând în 30 zile [Vezi ofertă]│
│  • Sezon de iarnă: Cauciucuri recomandate [Browse]    │
└─────────────────────────────────────────────────────────┘
```

### 2. Detaliu Produs

```
┌─────────────────────────────────────────────────────────┐
│  [< Back to Catalog]                    [🛒 Cart (3)]  │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌───────────┐  RCA Flotă - Asigurare Obligatorie      │
│  │           │                                          │
│  │  [IMAGE]  │  ⭐⭐⭐⭐⭐ (24 reviews)                    │
│  │           │  Supplier: FleetInsure SRL               │
│  │           │  SKU: RCA-FLEET-2025                     │
│  └───────────┘                                          │
│  [📸][📸][📸] ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│                                                         │
│  Pret: 450 RON / vehicul                                │
│  □ Discount volume:                                     │
│    • 10-49 vehicule: 420 RON (-7%)                      │
│    • 50+ vehicule:   390 RON (-13%)                     │
│                                                         │
│  ┌─ Configurare ─────────────────────────────────┐     │
│  │ Perioada:    [○ 6 luni  ⦿ 12 luni]            │     │
│  │ Nr. Vehicule: [___5____] [Selecteaza din flota]│     │
│  │ Data inceput: [2025-01-15]                     │     │
│  └────────────────────────────────────────────────┘     │
│                                                         │
│  Total estimate: 2,100 RON (5 × 420 RON)                │
│                                                         │
│  [➕ Add to Cart]  [💬 Request Quote]  [⭐ Wishlist]   │
│                                                         │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│ [Description] [Specifications] [Reviews] [FAQ]         │
│                                                         │
│ ✓ Acoperire: RCA standard conform legislatie           │
│ ✓ Daune materiale: Nelimitat                           │
│ ✓ Daune corporale: Nelimitat                           │
│ ✓ Gestiune fleet: Portal online dedicat                │
│ ✓ Suport 24/7                                          │
│ ✓ Livrare: Instant (poliță electronică)                │
│                                                         │
│ 💡 Compatible cu: Toate vehiculele din flota ta        │
│ 💡 5 vehicule cu RCA expirand in 30 zile              │
└─────────────────────────────────────────────────────────┘
```

### 3. Coș de cumpărături

```
┌─────────────────────────────────────────────────────────┐
│  Shopping Cart                                          │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  3 items in your cart                                   │
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │ [IMG] RCA Flotă                                 │   │
│  │       5 vehicule × 420 RON        2,100 RON [×]│   │
│  │       Discount volume: -150 RON                 │   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │ [IMG] Cauciucuri Michelin Iarna                 │   │
│  │       20 buc × 320 RON            6,400 RON [×]│   │
│  │       Tier price applied: -10%                  │   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │ [IMG] Roviniete Ungaria                         │   │
│  │       5 buc × 40 EUR                200 EUR [×]│   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│                                                         │
│  Subtotal:              8,500 RON + 200 EUR             │
│  Discount volume:        -150 RON                       │
│  TAX (19%):            1,586 RON + 38 EUR               │
│  ━━━━━━━━━━━━━━━━━━━━                                   │
│  TOTAL:                9,936 RON + 238 EUR              │
│                                                         │
│  Cupon discount: [__________] [Apply]                  │
│                                                         │
│  [← Continue Shopping]    [Checkout →]                 │
│                                                         │
│  💡 Need custom pricing for large orders?              │
│     [Request a Quote] for personalized offer           │
└─────────────────────────────────────────────────────────┘
```

### 4. Dashboard Admin Marketplace

```
┌─────────────────────────────────────────────────────────┐
│  SuperAdmin > Marketplace Dashboard                     │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐  │
│  │ Orders   │ │ Revenue  │ │ Products │ │  Quotes  │  │
│  │   142    │ │ 245K RON │ │    328   │ │    12    │  │
│  │  +12%    │ │  +8%     │ │    +5    │ │   NEW!   │  │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘  │
│                                                         │
│  Recent Orders                          [View All →]   │
│  ┌────────────────────────────────────────────────┐    │
│  │ #ORD-2025-0142  FleetCo SRL    2,100 RON      │    │
│  │ 🟡 Pending      5 RCA policies   [Process]     │    │
│  ├────────────────────────────────────────────────┤    │
│  │ #ORD-2025-0141  TransLog Ltd   15,400 RON     │    │
│  │ 🟢 Confirmed    Tires + parts    [View]        │    │
│  ├────────────────────────────────────────────────┤    │
│  │ #ORD-2025-0140  AutoFleet      890 RON        │    │
│  │ 📦 Shipped      Filters set      [Track]       │    │
│  └────────────────────────────────────────────────┘    │
│                                                         │
│  Quote Requests (Pending)               [View All →]   │
│  ┌────────────────────────────────────────────────┐    │
│  │ #QTE-2025-0012  MegaTrans SRL                  │    │
│  │ 🔴 New Request  100 Tires for winter season    │    │
│  │ Deadline: 2025-12-30     [Respond]   [Details]│    │
│  ├────────────────────────────────────────────────┤    │
│  │ #QTE-2025-0011  FastDelivery Ltd               │    │
│  │ 🟡 Processing   Fleet insurance 50 vehicles    │    │
│  │ Quoted: 18,500 RON       [Edit]     [Send]    │    │
│  └────────────────────────────────────────────────┘    │
│                                                         │
│  [📊 Analytics] [📦 Products] [🏢 Suppliers] [⚙️ Settings]│
└─────────────────────────────────────────────────────────┘
```

## 🔄 Fluxuri de Lucru (Workflows)

### Flux 1: Comandă Simplă (Standard Order)

```
Company User                 System                SuperAdmin
     │                         │                        │
     ├─ Browse catalog ────────┤                        │
     ├─ View product ──────────┤                        │
     ├─ Add to cart ───────────┤                        │
     ├─ Checkout ──────────────┤                        │
     │                          ├─ Create order ────────┤
     │                          ├─ Send notification ───►
     │                          │                        │
     │                          │               ┌────────┴────────┐
     │                          │               │ Review order    │
     │                          │               │ Confirm/Process │
     │                          │               └────────┬────────┘
     │◄─────────────────────────┼─ Order confirmed ──────┤
     │                          ├─ Update inventory      │
     │                          ├─ Generate invoice ─────┤
     ├─ View invoice ───────────┤                        │
     │                          │                        │
     │                          ├─ Mark as shipped ──────┤
     │◄─────────────────────────┼─ Tracking info ────────┤
     │                          │                        │
     ├─ Confirm delivery ───────┤                        │
     │                          ├─ Complete order ───────┤
     ├─ Leave review ───────────┤                        │
```

### Flux 2: Request for Quote (RFQ)

```
Company User                 System                SuperAdmin
     │                         │                        │
     ├─ Browse products ────────┤                        │
     ├─ Request quote ──────────┤                        │
     │   (bulk order)           │                        │
     │                          ├─ Create quote request ─┤
     │                          ├─ Notify SuperAdmin ────►
     │                          │                        │
     │                          │               ┌────────┴────────┐
     │                          │               │ Review request  │
     │                          │               │ Calculate price │
     │                          │               │ Generate quote  │
     │                          │               └────────┬────────┘
     │                          │                        │
     │◄─────────────────────────┼─ Quote sent (PDF) ─────┤
     │                          │   (valid 7 days)       │
     │                          │                        │
     ├─ Review quote ───────────┤                        │
     ├─ Accept quote ───────────┤                        │
     │                          ├─ Convert to order ─────┤
     │                          │   (follow Flux 1)      │
```

### Flux 3: Recomandări Automate

```
System (Cron Daily)          Fleet Data         Marketplace
     │                         │                     │
     ├─ Scan fleet data ────────►                    │
     │   • Insurance expiring   │                    │
     │   • Maintenance due      │                    │
     │   • Seasonal needs       │                    │
     │                          │                    │
     ├─ Match with products ────┼────────────────────►
     │                          │                    │
     ├─ Calculate relevance     │                    │
     ├─ Generate recommendations│                    │
     │                          │                    │
     ├─ Create notifications ────►                   │
     │                          │                    │
     ├─ Send email (digest) ─────►                   │
     │                          │                    │
     └─ Display in dashboard ────►                   │
         (widget)
```

## 🚀 Faze de Implementare (Roadmap)

### Faza 1: MVP Foundation (4-6 săptămâni)

**Obiectiv**: Sistem funcțional basic pentru produse simple

**Tasks**:
1. **Week 1-2: Database & Models**
   - [ ] Creare schema bază de date (categorii, produse, comenzi)
   - [ ] Model classes (Product, Category, Order, OrderItem)
   - [ ] Migrații SQL
   - [ ] Seeding date test

2. **Week 3-4: Catalog & Browse**
   - [ ] Controller MarketplaceController (browse, search, filter)
   - [ ] View catalog cu paginare
   - [ ] View detaliu produs
   - [ ] Filter sidebar (categorii, preț)
   - [ ] Product card component

3. **Week 5-6: Cart & Checkout**
   - [ ] Cart model și controller
   - [ ] Add to cart functionality
   - [ ] Cart view
   - [ ] Basic checkout flow
   - [ ] Order creation
   - [ ] Order confirmation

**Deliverables**:
- ✅ Users pot browsing produse
- ✅ Users pot adăuga în coș
- ✅ Users pot plasa comenzi simple
- ✅ SuperAdmin poate vedea comenzi

### Faza 2: Admin Features (3-4 săptămâni)

**Obiectiv**: SuperAdmin poate gestiona catalog și comenzi

**Tasks**:
1. **Week 1-2: Product Management**
   - [ ] Admin dashboard marketplace
   - [ ] Product CRUD (Create, Read, Update, Delete)
   - [ ] Category management
   - [ ] Image upload și management
   - [ ] Bulk import produse (CSV)

2. **Week 3-4: Order Management**
   - [ ] Order list view (admin)
   - [ ] Order detail view (admin)
   - [ ] Order status workflow
   - [ ] Invoice generation (basic)
   - [ ] Email notifications (order placed, confirmed, shipped)

**Deliverables**:
- ✅ SuperAdmin poate adăuga/edita produse
- ✅ SuperAdmin poate procesa comenzi
- ✅ Notificări email automate
- ✅ Facturi generate automat

### Faza 3: Advanced Features (4-5 săptămâni)

**Obiectiv**: Funcționalități avansate și inteligente

**Tasks**:
1. **Week 1-2: Quotes & Pricing**
   - [ ] Request for Quote (RFQ) functionality
   - [ ] Quote management pentru SuperAdmin
   - [ ] Tier pricing (volume discounts)
   - [ ] Contract management (frame agreements)
   - [ ] Custom pricing per company

2. **Week 2-3: Recommendations**
   - [ ] Recommendation engine (rule-based)
   - [ ] Integration cu fleet data (insurance, maintenance)
   - [ ] Dashboard widget recomandări
   - [ ] Email digest săptămânal
   - [ ] "You may also like" pe product page

3. **Week 4-5: Enhanced Features**
   - [ ] Review system
   - [ ] Wishlist / Favorites
   - [ ] Product comparison
   - [ ] Advanced search (full-text, filters)
   - [ ] Supplier management

**Deliverables**:
- ✅ Volume discounts automate
- ✅ Request for Quote workflow complet
- ✅ Recomandări automate bazate pe fleet
- ✅ Reviews și rating-uri

### Faza 4: Integration & Analytics (3-4 săptămâni)

**Obiectiv**: Integrare cu flota și rapoarte

**Tasks**:
1. **Week 1-2: Fleet Integration**
   - [ ] Auto-suggest produse pentru vehicule specifice
   - [ ] Quick order din vehicle detail page
   - [ ] Link orders la vehicule/documente
   - [ ] Alerts automate (insurance expiring → suggest products)

2. **Week 3-4: Analytics & Reports**
   - [ ] Dashboard analytics pentru SuperAdmin
   - [ ] Sales reports
   - [ ] Product performance
   - [ ] Company purchasing patterns
   - [ ] TCO (Total Cost of Ownership) reports pentru companies

**Deliverables**:
- ✅ Marketplace integrat cu fleet management
- ✅ Rapoarte complete pentru SuperAdmin
- ✅ TCO tracking pentru companii

### Faza 5: Payment & Advanced (4-5 săptămâni)

**Obiectiv**: Payment gateway și features avansate

**Tasks**:
1. **Week 1-2: Payment Integration**
   - [ ] Payment gateway integration (euplatesc, netopia, stripe)
   - [ ] Payment methods (card, transfer bancar, ramburs)
   - [ ] Payment tracking
   - [ ] Refund handling

2. **Week 3-4: Advanced Features**
   - [ ] Multi-supplier marketplace
   - [ ] Commission tracking
   - [ ] Loyalty program / points
   - [ ] Coupon system
   - [ ] Subscription products (recurring)

3. **Week 5: Polish & Optimization**
   - [ ] Performance optimization
   - [ ] Mobile responsive
   - [ ] SEO optimization
   - [ ] Documentation complete

**Deliverables**:
- ✅ Plăți online integrate
- ✅ Multi-supplier support
- ✅ Sistem complet optimizat

## 🔐 Considerații Securitate & Compliance

### Securitate

1. **Autentificare & Autorizare**
   - Role-based access control (RBAC)
   - SuperAdmin: full access marketplace
   - Company Admin: poate plasa comenzi pentru compania sa
   - Company User: poate browsing și adăuga în coș (cu aprobare)

2. **Plăți**
   - PCI DSS compliance (dacă procesăm carduri)
   - Folosim payment processors certificați
   - Nu stocăm date de card

3. **Date**
   - Encrypt date sensibile (prețuri negociate, contracte)
   - Audit log pentru toate tranzacțiile
   - GDPR compliance pentru date personale

### Compliance

1. **Facturare**
   - Conform legislație română (facturi fiscale)
   - Numerotare continuă
   - Arhivare obligatorie

2. **Contracte**
   - Template-uri legale verificate
   - Signature digitală (opțional)
   - Arhivare securizată

## 📈 Metrici de Succes (KPIs)

### Pentru SuperAdmin (Seller)

- **Revenue**: Vânzări lunare din marketplace
- **Order Value**: Average Order Value (AOV)
- **Conversion Rate**: Visitors → Orders
- **Product Performance**: Top selling products
- **Customer Retention**: Repeat purchase rate
- **Quote Conversion**: Quotes → Orders

### Pentru Companies (Buyers)

- **Savings**: Economii prin volume discounts
- **Convenience**: Time saved în procurement
- **TCO Reduction**: Cost total per vehicul
- **Order Fulfillment**: Delivery time
- **Satisfaction**: Product quality ratings

## 🔌 Integrări Externe (Viitor)

1. **Furnizori Asigurări**
   - API integration cu Allianz, Groupama, etc.
   - Real-time pricing
   - Instant policy issuance

2. **Distribuitori Piese**
   - API integration cu Parts.ro, Auto Kelly, etc.
   - Stock sync
   - Automated ordering

3. **Payment Processors**
   - euplatesc.ro
   - netopia.ro
   - Stripe
   - Revolut Business

4. **Shipping**
   - Fan Courier API
   - Cargus API
   - Tracking automat

5. **Accounting**
   - Export comenzi către SmartBill, Oblio
   - Automated invoicing
   - VAT reporting

## 📱 Mobile App (Viitor)

Considerăm dezvoltarea unei aplicații mobile pentru:
- Quick ordering on the go
- Push notifications pentru recomandări
- Scan & order (QR codes pe produse)
- Mobile-first catalog browsing

## 🎓 Training & Documentation

### Pentru SuperAdmin
- Ghid administrare catalog
- Ghid procesare comenzi
- Best practices pricing
- Analytics interpretation

### Pentru Companies
- Ghid utilizare marketplace
- How to request quotes
- Understanding tier pricing
- TCO tracking

## 💡 Next Steps - Acțiuni Immediate

### 1. Validare Concept (1 săptămână)
- [ ] Prezintă conceptul către stakeholders
- [ ] Feedback de la companies pilot
- [ ] Identify must-have features pentru MVP
- [ ] Prioritize product categories (start cu asigurări?)

### 2. Setup Development (1 săptămână)
- [ ] Creare branch Git: `feature/marketplace`
- [ ] Setup development environment
- [ ] Creare schema bază de date
- [ ] Initialize module structure

### 3. MVP Development Start (săptămâna 3)
- [ ] Begin Faza 1 development
- [ ] Weekly sprints
- [ ] Regular demos pentru feedback

## 📞 Echipă Necesară

### Development
- **Backend Developer**: PHP, MySQL, Architecture
- **Frontend Developer**: HTML, CSS, JavaScript (Bootstrap/Vue.js?)
- **Full-stack Developer**: Integration

### Business
- **Product Manager**: Requirements, prioritization
- **Business Analyst**: Pricing strategy, analytics

### Optional
- **UI/UX Designer**: Mockups, user experience
- **QA Tester**: Testing, quality assurance

## 🏁 Concluzie

Marketplace-ul B2B va transforma Fleet Management într-o platformă completă care nu doar gestionează flote, ci și facilitează aprovizionarea eficientă cu produse și servicii necesare.

**Beneficii cheie**:
- **Pentru SuperAdmin**: Nou revenue stream, customer retention
- **Pentru Companies**: One-stop shop, time savings, volume discounts
- **Pentru sistem**: Increased engagement, valuable data despre fleet needs

**Estimated timeline total**: 18-24 săptămâni pentru versiune completă
**MVP timeline**: 6 săptămâni

---

**Ready to start? Next step: Approve plan și începe Faza 1! 🚀**
