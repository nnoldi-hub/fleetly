<?php
// includes/breadcrumb.php
/**
 * Sistem de navigare breadcrumb pentru aplicația Fleet Management
 * Generează automat breadcrumb-urile pe baza URL-ului curent
 */

class Breadcrumb {
    private $items = [];
    private $separator = '/';
    private $homeText = 'Acasă';
    private $homeUrl = '';
    
    public function __construct($homeUrl = '') {
        $this->homeUrl = $homeUrl ?: BASE_URL;
    }
    
    /**
     * Adaugă un element la breadcrumb
     */
    public function add($title, $url = null, $icon = null) {
        $this->items[] = [
            'title' => $title,
            'url' => $url,
            'icon' => $icon,
            'active' => $url === null
        ];
        return $this;
    }
    
    /**
     * Setează separatorul pentru breadcrumb
     */
    public function setSeparator($separator) {
        $this->separator = $separator;
        return $this;
    }
    
    /**
     * Setează textul și URL-ul pentru home
     */
    public function setHome($text, $url = null) {
        $this->homeText = $text;
        if ($url) {
            $this->homeUrl = $url;
        }
        return $this;
    }
    
    /**
     * Generează breadcrumb automat pe baza URL-ului curent
     */
    public function autoGenerate() {
        $currentUrl = $_SERVER['REQUEST_URI'];
        $basePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', realpath('.'));
        $path = str_replace($basePath, '', $currentUrl);
        
        // Elimină query string
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }
        
        // Elimină slash-urile de la început și sfârșit
        $path = trim($path, '/');
        
        if (empty($path)) {
            return $this;
        }
        
        $segments = explode('/', $path);
        $currentPath = '';
        
        foreach ($segments as $index => $segment) {
            if (empty($segment)) continue;
            
            $currentPath .= ($currentPath ? '/' : '') . $segment;
            $isLast = ($index === count($segments) - 1);
            
            // Generează titlul bazat pe segment
            $title = $this->generateTitle($segment, $segments, $index);
            $url = $isLast ? null : BASE_URL . $currentPath;
            $icon = $this->getIconForSegment($segment, $index);
            
            $this->add($title, $url, $icon);
        }
        
        return $this;
    }
    
    /**
     * Generează titlul pentru un segment URL
     */
    private function generateTitle($segment, $segments, $index) {
        // Înlocuiește underscore și dash cu spații
        $title = str_replace(['_', '-'], ' ', $segment);
        
        // Conversii specifice pentru module
        $translations = [
            'vehicles' => 'Vehicule',
            'documents' => 'Documente', 
            'drivers' => 'Șoferi',
            'maintenance' => 'Întreținere',
            'fuel' => 'Combustibil',
            'reports' => 'Rapoarte',
            'notifications' => 'Notificări',
            'dashboard' => 'Dashboard',
            'settings' => 'Setări',
            'profile' => 'Profil',
            'add' => 'Adaugă',
            'edit' => 'Editează',
            'view' => 'Vizualizare',
            'list' => 'Listă',
            'expiring' => 'Ce Expiră',
            'expired' => 'Expirate',
            'history' => 'Istoric',
            'schedule' => 'Programare',
            'consumption' => 'Consum',
            'fleet' => 'Flotă',
            'costs' => 'Costuri',
            'export' => 'Export'
        ];
        
        $lowerSegment = strtolower($segment);
        if (isset($translations[$lowerSegment])) {
            return $translations[$lowerSegment];
        }
        
        // Dacă este un ID numeric, încearcă să obții informații contextuale
        if (is_numeric($segment) && $index > 0) {
            $parentSegment = $segments[$index - 1];
            return $this->getContextualTitle($parentSegment, $segment);
        }
        
        // Capitalizează prima literă din fiecare cuvânt
        return ucwords($title);
    }
    
    /**
     * Obține titlu contextual pentru ID-uri numerice
     */
    private function getContextualTitle($parentSegment, $id) {
        try {
            switch ($parentSegment) {
                case 'vehicles':
                    $vehicle = (new Vehicle())->find($id);
                    return $vehicle ? $vehicle['registration_number'] : "Vehicul #$id";
                    
                case 'drivers':
                    $driver = (new Driver())->find($id);
                    return $driver ? $driver['name'] : "Șofer #$id";
                    
                case 'documents':
                    $document = (new Document())->find($id);
                    if ($document) {
                        $types = [
                            'insurance_rca' => 'RCA',
                            'insurance_casco' => 'CASCO',
                            'itp' => 'ITP',
                            'vignette' => 'Rovinietă'
                        ];
                        return $types[$document['document_type']] ?? 'Document';
                    }
                    return "Document #$id";
                    
                default:
                    return "#$id";
            }
        } catch (Exception $e) {
            return "#$id";
        }
    }
    
    /**
     * Obține iconița pentru un segment
     */
    private function getIconForSegment($segment, $index) {
        if ($index === 0) {
            $icons = [
                'vehicles' => 'fas fa-car',
                'documents' => 'fas fa-file-contract',
                'drivers' => 'fas fa-users',
                'maintenance' => 'fas fa-tools',
                'fuel' => 'fas fa-gas-pump',
                'reports' => 'fas fa-chart-bar',
                'notifications' => 'fas fa-bell',
                'dashboard' => 'fas fa-tachometer-alt',
                'settings' => 'fas fa-cog',
                'profile' => 'fas fa-user'
            ];
            
            return $icons[strtolower($segment)] ?? null;
        }
        
        return null;
    }
    
    /**
     * Renderează breadcrumb-ul
     */
    public function render($cssClass = 'breadcrumb', $containerClass = 'breadcrumb-container') {
        if (empty($this->items)) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="<?= htmlspecialchars($containerClass) ?>">
            <nav aria-label="breadcrumb">
                <ol class="<?= htmlspecialchars($cssClass) ?>">
                    <!-- Home -->
                    <li class="breadcrumb-item">
                        <a href="<?= htmlspecialchars($this->homeUrl) ?>" class="breadcrumb-home">
                            <i class="fas fa-home"></i>
                            <span><?= htmlspecialchars($this->homeText) ?></span>
                        </a>
                    </li>
                    
                    <!-- Items -->
                    <?php foreach ($this->items as $index => $item): ?>
                        <li class="breadcrumb-item<?= $item['active'] ? ' active' : '' ?>"
                            <?= $item['active'] ? 'aria-current="page"' : '' ?>>
                            
                            <?php if ($item['active']): ?>
                                <!-- Item activ (ultimul) -->
                                <?php if ($item['icon']): ?>
                                    <i class="<?= htmlspecialchars($item['icon']) ?>"></i>
                                <?php endif; ?>
                                <span><?= htmlspecialchars($item['title']) ?></span>
                                
                            <?php else: ?>
                                <!-- Item cu link -->
                                <a href="<?= htmlspecialchars($item['url']) ?>" class="breadcrumb-link">
                                    <?php if ($item['icon']): ?>
                                        <i class="<?= htmlspecialchars($item['icon']) ?>"></i>
                                    <?php endif; ?>
                                    <span><?= htmlspecialchars($item['title']) ?></span>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Afișează breadcrumb-ul direct
     */
    public function display($cssClass = 'breadcrumb', $containerClass = 'breadcrumb-container') {
        echo $this->render($cssClass, $containerClass);
    }
    
    /**
     * Returnează breadcrumb-ul ca array pentru JSON
     */
    public function toArray() {
        $result = [
            [
                'title' => $this->homeText,
                'url' => $this->homeUrl,
                'icon' => 'fas fa-home',
                'active' => false
            ]
        ];
        
        return array_merge($result, $this->items);
    }
    
    /**
     * Șterge toate elementele
     */
    public function clear() {
        $this->items = [];
        return $this;
    }
    
    /**
     * Verifică dacă breadcrumb-ul este gol
     */
    public function isEmpty() {
        return empty($this->items);
    }
    
    /**
     * Obține numărul de elemente
     */
    public function count() {
        return count($this->items);
    }
}

// Funcții helper pentru utilizare ușoară
function createBreadcrumb($autoGenerate = true) {
    $breadcrumb = new Breadcrumb(BASE_URL);
    
    if ($autoGenerate) {
        $breadcrumb->autoGenerate();
    }
    
    return $breadcrumb;
}

function displayBreadcrumb($items = null, $autoGenerate = true) {
    $breadcrumb = createBreadcrumb($autoGenerate);
    
    if ($items && is_array($items)) {
        $breadcrumb->clear();
        foreach ($items as $item) {
            if (is_string($item)) {
                $breadcrumb->add($item);
            } elseif (is_array($item)) {
                $breadcrumb->add(
                    $item['title'] ?? $item[0] ?? '',
                    $item['url'] ?? $item[1] ?? null,
                    $item['icon'] ?? $item[2] ?? null
                );
            }
        }
    }
    
    $breadcrumb->display();
}

// CSS pentru breadcrumb (să fie inclus în main.css sau separat)
?>
<style>
.breadcrumb-container {
    background: #f8f9fa;
    padding: 1rem 0;
    border-bottom: 1px solid #e9ecef;
    margin-bottom: 1.5rem;
}

.breadcrumb {
    background: transparent;
    padding: 0;
    margin: 0;
    font-size: 0.9rem;
}

.breadcrumb-item {
    display: flex;
    align-items: center;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    padding: 0 0.75rem;
    color: #6c757d;
    font-weight: 600;
    font-size: 1.1rem;
}

.breadcrumb-home,
.breadcrumb-link {
    color: #0d6efd;
    text-decoration: none;
    display: flex;
    align-items: center;
    transition: all 0.2s ease;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
}

.breadcrumb-home:hover,
.breadcrumb-link:hover {
    color: #0a58ca;
    background: rgba(13, 110, 253, 0.1);
    text-decoration: none;
}

.breadcrumb-home i,
.breadcrumb-link i {
    margin-right: 0.5rem;
    font-size: 0.875rem;
}

.breadcrumb-item.active {
    color: #6c757d;
    font-weight: 500;
}

.breadcrumb-item.active i {
    margin-right: 0.5rem;
    color: #495057;
}

/* Responsive design */
@media (max-width: 768px) {
    .breadcrumb-container {
        padding: 0.75rem 0;
    }
    
    .breadcrumb {
        font-size: 0.8rem;
        flex-wrap: wrap;
    }
    
    .breadcrumb-item + .breadcrumb-item::before {
        padding: 0 0.5rem;
    }
    
    .breadcrumb-home span,
    .breadcrumb-link span {
        display: none;
    }
    
    .breadcrumb-item.active span {
        display: inline;
    }
    
    /* Afișează doar iconițele pe mobile, exceptând ultimul element */
    .breadcrumb-home i,
    .breadcrumb-link i {
        margin-right: 0;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .breadcrumb-container {
        background: #212529;
        border-bottom-color: #495057;
    }
    
    .breadcrumb-item + .breadcrumb-item::before {
        color: #adb5bd;
    }
    
    .breadcrumb-item.active {
        color: #adb5bd;
    }
    
    .breadcrumb-home:hover,
    .breadcrumb-link:hover {
        background: rgba(13, 110, 253, 0.2);
    }
}
</style>

