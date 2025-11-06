<?php
class Router {
    private $routes = [];
    
    public function addRoute($method, $path, $controller, $action) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'controller' => $controller,
            'action' => $action
        ];
    }
    
    public function route($method, $uri) {
        $normMethod = strtoupper($method);
        $normUri = $this->normalizePath($uri);
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $normMethod && $this->matchPath($route['path'], $normUri)) {
                $controllerClass = $route['controller'];
                $action = $route['action'];
                
                if (class_exists($controllerClass)) {
                    $controller = new $controllerClass();
                    if (method_exists($controller, $action)) {
                        return $controller->$action();
                    }
                }
            }
        }
        
        // 404
        http_response_code(404);
        // Try to include header/footer using filesystem path to avoid relative include issues
        $hdr = __DIR__ . '/../includes/header.php';
        if (is_file($hdr)) { include $hdr; }
        echo "<div class='container mt-5'>";
        echo "<div class='alert alert-danger'><h4>404 - Pagina nu a fost găsită</h4><div class='small text-muted'>" . htmlspecialchars($normUri) . "</div></div>";
        echo "</div>";
        $ftr = __DIR__ . '/../includes/footer.php';
        if (is_file($ftr)) { include $ftr; }
    }
    
    private function matchPath($routePath, $uri) {
        // Normalizează pentru a trata trailing slash-uri
        $r = $this->normalizePath($routePath);
        $u = $this->normalizePath($uri);
        if ($r === $u) return true;
        // Extra safety: allow when the URI ends with the route path (helps when base path trimming fails)
        if (strlen($u) > strlen($r)) {
            $pos = strrpos($u, $r);
            if ($pos !== false && $pos + strlen($r) === strlen($u)) {
                // ensure boundary before match is a slash
                if ($pos === 0 || $u[$pos - 1] === '/') return true;
            }
        }
        return false;
    }

    private function normalizePath($p) {
        if ($p === null) return '/';
        // Păstrează doar partea de path (fără query)
        $p = parse_url($p, PHP_URL_PATH) ?? '/';
        // Asigură leading slash
        if ($p === '') $p = '/';
        if ($p[0] !== '/') $p = '/' . $p;
        // Elimină trailing slash dacă nu e root
        if (strlen($p) > 1) {
            $p = rtrim($p, '/');
        }
        return $p;
    }
}
?>
