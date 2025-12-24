# Quick Start: Implementare Marketplace - Faza 1 MVP

**Target**: Marketplace funcțional în 6 săptămâni
**Focus**: Asigurări și Roviniete (produse digitale, fără complicații logistice)

## 🎯 MVP Scope

### Features incluse:
✅ Catalog produse (categorii, listare, detalii)  
✅ Coș cumpărături  
✅ Checkout simplu (fără plată online)  
✅ Procesare comenzi de către SuperAdmin  
✅ Notificări email  
✅ Administrare produse (CRUD)  

### Features excluse din MVP:
❌ Request for Quote (RFQ) - Faza 2  
❌ Tier pricing complex - Faza 2  
❌ Recomandări automate - Faza 3  
❌ Payment gateway - Faza 5  
❌ Multi-supplier - Faza 5  

## 📅 Timeline 6 Săptămâni

### Săptămâna 1-2: Database & Foundation

**Day 1-3: Schema bază de date**

```bash
# Creare fișier migrare
cd C:\wamp64\www\fleet-management
touch sql/migrations/2025_12_24_marketplace_phase1.sql
```

SQL minimal pentru MVP (fără toate feature-urile avansate):

```sql
-- Categorii
CREATE TABLE mp_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Produse (versiune simplificată)
CREATE TABLE mp_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    sku VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(12,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'RON',
    image_main VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES mp_categories(id)
);

-- Coș
CREATE TABLE mp_cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES mp_products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart (company_id, user_id, product_id)
);

-- Comenzi
CREATE TABLE mp_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    company_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
    subtotal DECIMAL(12,2) NOT NULL,
    tax DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_status (status)
);

-- Items comandă
CREATE TABLE mp_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_sku VARCHAR(100),
    quantity INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES mp_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES mp_products(id)
);

-- Seed categorii
INSERT INTO mp_categories (name, slug, icon, sort_order) VALUES
('Asigurari', 'asigurari', 'shield', 1),
('Roviniete', 'roviniete', 'ticket', 2),
('Cauciucuri', 'cauciucuri', 'circle', 3),
('Piese Auto', 'piese-auto', 'tool', 4);

-- Seed produse test (Asigurări)
INSERT INTO mp_products (category_id, sku, name, slug, description, price) VALUES
(1, 'RCA-FLEET-2025', 'RCA Flotă - Asigurare Obligatorie', 'rca-flota', 
 'Asigurare RCA pentru flote. Acoperire conform legislației. Gestiune online.', 450.00),
(1, 'CASCO-FLEET-2025', 'CASCO Flotă - Asigurare Completă', 'casco-flota',
 'Asigurare CASCO completă. Daune totale, furt, incendiu. Franșiză negociabilă.', 1200.00),
(2, 'ROV-HU-2025', 'Rovinieta Ungaria - 12 luni', 'rovinieta-ungaria',
 'Rovinieta electronică Ungaria valabilă 12 luni. Livrare instant.', 180.00),
(2, 'ROV-BG-2025', 'Rovinieta Bulgaria - 12 luni', 'rovinieta-bulgaria',
 'Rovinieta electronică Bulgaria valabilă 12 luni. Livrare instant.', 150.00);
```

**Day 4-7: Models**

Creare models în `modules/marketplace/models/`:

```php
// Product.php
// Category.php  
// Cart.php
// Order.php
// OrderItem.php
```

**Day 8-10: Module structure**

```bash
mkdir -p modules/marketplace/controllers
mkdir -p modules/marketplace/models
mkdir -p modules/marketplace/views
mkdir -p modules/marketplace/views/admin
mkdir -p modules/marketplace/services
```

### Săptămâna 3-4: Catalog & Product Pages

**Controllers:**
- `MarketplaceController.php` - Browse, search
- `ProductController.php` - Detalii produs
- `CartController.php` - Cart operations

**Views:**
- `browse.php` - Catalog cu categorii
- `product-detail.php` - Detaliu produs
- `cart.php` - Coș

**Key features:**
- Listare produse cu paginare
- Filtrare pe categorii
- Căutare simplă
- Add to cart
- Update/remove din cart

### Săptămâna 5: Checkout & Orders

**Controllers:**
- `CheckoutController.php` - Finalizare comandă
- `OrderController.php` - Istoric comenzi

**Views:**
- `checkout.php` - Review și plasare comandă
- `order-confirmation.php` - Confirmare
- `orders.php` - Lista comenzi
- `order-detail.php` - Detaliu comandă

**Key features:**
- Review cart înainte de comandă
- Salvare comandă în DB
- Generate order number
- Email notification către SuperAdmin
- Email confirmation către company

### Săptămâna 6: Admin & Polish

**Admin Controllers:**
- `admin/CatalogAdminController.php` - CRUD produse
- `admin/OrderAdminController.php` - Procesare comenzi

**Admin Views:**
- `admin/dashboard.php` - Overview comenzi
- `admin/products.php` - Lista produse
- `admin/product-form.php` - Add/Edit
- `admin/orders.php` - Lista comenzi
- `admin/order-detail.php` - Procesare comandă

**Key features:**
- Dashboard cu statistici simple
- CRUD complet produse
- Image upload
- Procesare comenzi (confirm/complete/cancel)
- Notificări email

## 🔨 Implementare Pas cu Pas

### Pas 1: Rulare migrare

```bash
# Conectare la MySQL
mysql -u root -p fleet_management < sql/migrations/2025_12_24_marketplace_phase1.sql
```

### Pas 2: Creare models

**modules/marketplace/models/Product.php:**

```php
<?php
require_once __DIR__ . '/../../../core/Model.php';

class Product extends Model {
    protected $table = 'mp_products';
    
    public function getAll($filters = [], $limit = 20, $offset = 0) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p 
                JOIN mp_categories c ON p.category_id = c.id 
                WHERE p.is_active = 1";
        
        $params = [];
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getBySlug($slug) {
        return $this->db->fetch(
            "SELECT p.*, c.name as category_name 
             FROM {$this->table} p 
             JOIN mp_categories c ON p.category_id = c.id 
             WHERE p.slug = ? AND p.is_active = 1",
            [$slug]
        );
    }
}
```

### Pas 3: Creare router

**modules/marketplace/index.php:**

```php
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Router.php';
require_once __DIR__ . '/../../core/Auth.php';

$auth = Auth::getInstance();
if (!$auth->check()) {
    header('Location: ' . BASE_URL . 'modules/auth/index.php?action=login');
    exit;
}

$router = new Router();

// Public routes
$router->addRoute('GET', '/marketplace', 'MarketplaceController', 'index');
$router->addRoute('GET', '/marketplace/product', 'ProductController', 'show');
$router->addRoute('GET', '/marketplace/cart', 'CartController', 'index');
$router->addRoute('POST', '/marketplace/cart/add', 'CartController', 'add');
$router->addRoute('POST', '/marketplace/cart/update', 'CartController', 'update');
$router->addRoute('POST', '/marketplace/cart/remove', 'CartController', 'remove');
$router->addRoute('GET', '/marketplace/checkout', 'CheckoutController', 'index');
$router->addRoute('POST', '/marketplace/checkout', 'CheckoutController', 'process');
$router->addRoute('GET', '/marketplace/orders', 'OrderController', 'index');
$router->addRoute('GET', '/marketplace/order', 'OrderController', 'show');

// Admin routes (SuperAdmin only)
if ($auth->user()->role === 'superadmin') {
    $router->addRoute('GET', '/marketplace/admin', 'admin/DashboardController', 'index');
    $router->addRoute('GET', '/marketplace/admin/products', 'admin/CatalogAdminController', 'index');
    $router->addRoute('GET', '/marketplace/admin/product/create', 'admin/CatalogAdminController', 'create');
    $router->addRoute('POST', '/marketplace/admin/product/store', 'admin/CatalogAdminController', 'store');
    $router->addRoute('GET', '/marketplace/admin/product/edit', 'admin/CatalogAdminController', 'edit');
    $router->addRoute('POST', '/marketplace/admin/product/update', 'admin/CatalogAdminController', 'update');
    $router->addRoute('POST', '/marketplace/admin/product/delete', 'admin/CatalogAdminController', 'delete');
    $router->addRoute('GET', '/marketplace/admin/orders', 'admin/OrderAdminController', 'index');
    $router->addRoute('GET', '/marketplace/admin/order', 'admin/OrderAdminController', 'show');
    $router->addRoute('POST', '/marketplace/admin/order/update-status', 'admin/OrderAdminController', 'updateStatus');
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
```

### Pas 4: Creare controller exemplu

**modules/marketplace/controllers/MarketplaceController.php:**

```php
<?php
require_once __DIR__ . '/../../../core/Controller.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Category.php';

class MarketplaceController extends Controller {
    private $productModel;
    private $categoryModel;
    
    public function __construct() {
        parent::__construct();
        $this->productModel = new Product();
        $this->categoryModel = new Category();
    }
    
    public function index() {
        $categoryId = $_GET['category'] ?? null;
        $search = $_GET['search'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 12;
        $offset = ($page - 1) * $perPage;
        
        $filters = [
            'category_id' => $categoryId,
            'search' => $search
        ];
        
        $products = $this->productModel->getAll($filters, $perPage, $offset);
        $categories = $this->categoryModel->getAll();
        $total = $this->productModel->count($filters);
        $totalPages = ceil($total / $perPage);
        
        $this->render('browse', [
            'products' => $products,
            'categories' => $categories,
            'currentCategory' => $categoryId,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'pageTitle' => 'Marketplace'
        ]);
    }
}
```

### Pas 5: Creare view exemplu

**modules/marketplace/views/browse.php:**

```php
<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="container py-4">
    <h1>Marketplace</h1>
    
    <div class="row">
        <!-- Sidebar categorii -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">Categorii</div>
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_URL ?>marketplace" 
                       class="list-group-item list-group-item-action <?= !$currentCategory ? 'active' : '' ?>">
                        Toate produsele
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="<?= BASE_URL ?>marketplace?category=<?= $cat['id'] ?>" 
                           class="list-group-item list-group-item-action <?= $currentCategory == $cat['id'] ? 'active' : '' ?>">
                            <i class="bi bi-<?= $cat['icon'] ?>"></i> <?= htmlspecialchars($cat['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Produse -->
        <div class="col-md-9">
            <!-- Search -->
            <form method="GET" class="mb-3">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Caută produse..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i> Caută
                    </button>
                </div>
            </form>
            
            <!-- Grid produse -->
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php foreach ($products as $product): ?>
                    <div class="col">
                        <div class="card h-100">
                            <?php if ($product['image_main']): ?>
                                <img src="<?= BASE_URL . $product['image_main'] ?>" 
                                     class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars($product['category_name']) ?></p>
                                <p class="card-text"><?= htmlspecialchars(substr($product['description'], 0, 100)) ?>...</p>
                                <h4 class="text-primary"><?= number_format($product['price'], 2) ?> <?= $product['currency'] ?></h4>
                            </div>
                            <div class="card-footer">
                                <a href="<?= BASE_URL ?>marketplace/product?slug=<?= $product['slug'] ?>" 
                                   class="btn btn-primary w-100">
                                    Vezi detalii
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Paginare -->
            <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&category=<?= $currentCategory ?>&search=<?= urlencode($search) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
```

### Pas 6: Adăugare link în meniu

**includes/sidebar.php sau header.php:**

```php
<li class="nav-item">
    <a class="nav-link" href="<?= BASE_URL ?>marketplace">
        <i class="bi bi-shop"></i> Marketplace
    </a>
</li>
```

## ✅ Testing Checklist MVP

### User Flow Testing

- [ ] Browse catalog (toate produsele)
- [ ] Filtrare pe categorie
- [ ] Căutare produse
- [ ] Vezi detalii produs
- [ ] Add to cart
- [ ] Update quantity în cart
- [ ] Remove din cart
- [ ] Checkout și plasare comandă
- [ ] Primire email confirmare
- [ ] Vezi istoric comenzi
- [ ] Vezi detalii comandă

### Admin Flow Testing

- [ ] Dashboard marketplace (statistici)
- [ ] Lista produse
- [ ] Adăugare produs nou
- [ ] Editare produs existent
- [ ] Ștergere produs
- [ ] Upload imagine produs
- [ ] Lista comenzi noi
- [ ] Vezi detaliu comandă
- [ ] Confirmare comandă (notificare email)
- [ ] Completare comandă

## 📊 Success Metrics MVP

După lansarea MVP, urmărește:
- **Adoption**: % companii care browsează marketplace
- **Conversion**: % vizitatori care plasează comenzi
- **AOV**: Average Order Value
- **Top products**: Ce se vinde cel mai bine
- **Feedback**: Colectare feedback de la utilizatori

## 🚀 Launch Plan

### Pre-Launch (1 săptămână înainte)
- [ ] Testing complet
- [ ] Seed 10-20 produse reale
- [ ] Pregătire email announcement
- [ ] Training pentru SuperAdmin
- [ ] Documentație utilizatori

### Launch Day
- [ ] Deploy pe production
- [ ] Send announcement email către companies
- [ ] Monitoring activ pentru errors
- [ ] Support disponibil

### Post-Launch (prima săptămână)
- [ ] Daily monitoring comenzi
- [ ] Colectare feedback
- [ ] Quick fixes pentru issues
- [ ] Planning Faza 2

## 📝 După MVP - Faza 2 Planning

Features prioritare după MVP:
1. **Request for Quote** (cereri ofertă bulk)
2. **Tier pricing** (discount-uri volum)
3. **Recomandări automate** (bazat pe fleet)
4. **Reviews** (rating produse)
5. **Advanced search** (filtre multiple)

---

**Gata să începi? Pasul 1: Rulează migrarea SQL! 🚀**

```bash
cd C:\wamp64\www\fleet-management
mysql -u root -p fleet_management < sql/migrations/2025_12_24_marketplace_phase1.sql
```
