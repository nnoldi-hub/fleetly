<?php
/**
 * PartnerController
 * 
 * Controller pentru vizualizarea partenerilor/reclamelor de către utilizatori
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../models/Partner.php';
require_once __DIR__ . '/../models/PartnerCategory.php';

class PartnerController {
    private $partnerModel;
    private $categoryModel;
    private $user;
    
    public function __construct() {
        $this->partnerModel = new Partner();
        $this->categoryModel = new PartnerCategory();
        $this->user = Auth::getInstance()->user();
    }
    
    /**
     * Pagina principală - afișează partenerii pe categorii
     */
    public function index() {
        $categories = $this->categoryModel->getAllWithCounts();
        $featuredPartners = $this->partnerModel->getFeatured(6);
        
        // Aplică filtre dacă există
        $filters = [
            'valid_only' => true,
            'category_slug' => $_GET['category'] ?? null,
            'search' => $_GET['search'] ?? null
        ];
        
        $partners = $this->partnerModel->getAll($filters);
        
        // Grupează partenerii pe categorii pentru afișare
        $partnersByCategory = [];
        foreach ($partners as $partner) {
            $catSlug = $partner['category_slug'] ?? 'other';
            if (!isset($partnersByCategory[$catSlug])) {
                $partnersByCategory[$catSlug] = [
                    'category' => [
                        'name' => $partner['category_name'],
                        'slug' => $partner['category_slug'],
                        'icon' => $partner['category_icon'],
                        'color' => $partner['category_color']
                    ],
                    'partners' => []
                ];
            }
            $partnersByCategory[$catSlug]['partners'][] = $partner;
        }
        
        $selectedCategory = null;
        if (!empty($filters['category_slug'])) {
            $selectedCategory = $this->categoryModel->getBySlug($filters['category_slug']);
        }
        
        include __DIR__ . '/../views/partners/index.php';
    }
    
    /**
     * Detalii partener
     */
    public function show() {
        $slug = $_GET['slug'] ?? null;
        $id = $_GET['id'] ?? null;
        
        if ($slug) {
            $partner = $this->partnerModel->getBySlug($slug);
        } elseif ($id) {
            $partner = $this->partnerModel->getById($id);
        } else {
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=partners');
            exit;
        }
        
        if (!$partner) {
            $_SESSION['error'] = 'Partenerul nu a fost găsit.';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=partners');
            exit;
        }
        
        // Incrementează vizualizările
        $this->partnerModel->incrementViews(
            $partner['id'],
            $this->user->id ?? null,
            $this->user->company_id ?? null
        );
        
        // Obține parteneri similari din aceeași categorie
        $similarPartners = $this->partnerModel->getAll([
            'category_id' => $partner['category_id'],
            'valid_only' => true
        ]);
        
        // Exclude partenerul curent
        $similarPartners = array_filter($similarPartners, function($p) use ($partner) {
            return $p['id'] != $partner['id'];
        });
        
        $similarPartners = array_slice($similarPartners, 0, 4);
        
        include __DIR__ . '/../views/partners/show.php';
    }
    
    /**
     * Redirect către site-ul partenerului (cu tracking)
     */
    public function redirect() {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=partners');
            exit;
        }
        
        $partner = $this->partnerModel->getById($id);
        
        if (!$partner || empty($partner['website_url'])) {
            $_SESSION['error'] = 'Link-ul nu este disponibil.';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=partners');
            exit;
        }
        
        // Incrementează click-urile
        $this->partnerModel->incrementClicks(
            $partner['id'],
            $this->user->id ?? null,
            $this->user->company_id ?? null
        );
        
        // Redirect către site-ul partenerului
        header('Location: ' . $partner['website_url']);
        exit;
    }
    
    /**
     * Afișează partenerii dintr-o categorie
     */
    public function category() {
        $slug = $_GET['slug'] ?? null;
        
        if (!$slug) {
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=partners');
            exit;
        }
        
        $category = $this->categoryModel->getBySlug($slug);
        
        if (!$category) {
            $_SESSION['error'] = 'Categoria nu a fost găsită.';
            header('Location: ' . BASE_URL . 'modules/marketplace/?action=partners');
            exit;
        }
        
        $partners = $this->partnerModel->getAll([
            'category_id' => $category['id'],
            'valid_only' => true
        ]);
        
        $categories = $this->categoryModel->getAllWithCounts();
        
        include __DIR__ . '/../views/partners/category.php';
    }
}
