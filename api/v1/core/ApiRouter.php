<?php
/**
 * API Router
 * 
 * Router simplu pentru API REST cu suport pentru:
 * - Routing cu parametri dinamici ({id})
 * - Grupuri de rute cu middleware
 * - Callback-uri pentru controllere
 */
class ApiRouter {
    
    private $routes = [];
    private $currentMiddleware = [];
    private $patterns = [
        '{id}' => '([0-9]+)',
        '{slug}' => '([a-zA-Z0-9_-]+)',
        '{any}' => '([^/]+)'
    ];
    
    /**
     * Add GET route
     */
    public function get($path, $handler) {
        $this->addRoute('GET', $path, $handler);
    }
    
    /**
     * Add POST route
     */
    public function post($path, $handler) {
        $this->addRoute('POST', $path, $handler);
    }
    
    /**
     * Add PUT route
     */
    public function put($path, $handler) {
        $this->addRoute('PUT', $path, $handler);
    }
    
    /**
     * Add DELETE route
     */
    public function delete($path, $handler) {
        $this->addRoute('DELETE', $path, $handler);
    }
    
    /**
     * Add PATCH route
     */
    public function patch($path, $handler) {
        $this->addRoute('PATCH', $path, $handler);
    }
    
    /**
     * Add route with group middleware
     */
    public function group($options, $callback) {
        $previousMiddleware = $this->currentMiddleware;
        
        if (isset($options['middleware'])) {
            $middleware = is_array($options['middleware']) 
                ? $options['middleware'] 
                : [$options['middleware']];
            $this->currentMiddleware = array_merge($this->currentMiddleware, $middleware);
        }
        
        $callback($this);
        
        $this->currentMiddleware = $previousMiddleware;
    }
    
    /**
     * Add route to collection
     */
    private function addRoute($method, $path, $handler) {
        // Convert path to regex
        $pattern = $this->pathToRegex($path);
        
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $this->currentMiddleware
        ];
    }
    
    /**
     * Convert path to regex pattern
     */
    private function pathToRegex($path) {
        $pattern = preg_quote($path, '#');
        
        foreach ($this->patterns as $placeholder => $regex) {
            $pattern = str_replace(preg_quote($placeholder, '#'), $regex, $pattern);
        }
        
        return '#^' . $pattern . '$#';
    }
    
    /**
     * Dispatch request
     */
    public function dispatch($method, $path) {
        // Normalize path
        $path = rtrim($path, '/');
        if (empty($path)) {
            $path = '/';
        }
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            if (preg_match($route['pattern'], $path, $matches)) {
                array_shift($matches); // Remove full match
                
                // Execute middleware
                foreach ($route['middleware'] as $middleware) {
                    $this->executeMiddleware($middleware);
                }
                
                // Execute handler
                return $this->executeHandler($route['handler'], $matches);
            }
        }
        
        // No route found
        ApiResponse::notFound('Endpoint not found: ' . $method . ' ' . $path);
    }
    
    /**
     * Execute middleware
     */
    private function executeMiddleware($middleware) {
        switch ($middleware) {
            case 'auth':
                AuthMiddleware::handle();
                break;
            
            case 'admin':
                AuthMiddleware::requireAdmin();
                break;
            
            case 'superadmin':
                AuthMiddleware::requireSuperAdmin();
                break;
            
            default:
                // Custom middleware class
                if (class_exists($middleware)) {
                    $instance = new $middleware();
                    if (method_exists($instance, 'handle')) {
                        $instance->handle();
                    }
                }
        }
    }
    
    /**
     * Execute route handler
     */
    private function executeHandler($handler, $params = []) {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }
        
        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controller, $method) = explode('@', $handler);
            
            if (!class_exists($controller)) {
                ApiResponse::error('Controller not found: ' . $controller, 500);
            }
            
            $instance = new $controller();
            
            if (!method_exists($instance, $method)) {
                ApiResponse::error('Method not found: ' . $method, 500);
            }
            
            return call_user_func_array([$instance, $method], $params);
        }
        
        ApiResponse::error('Invalid handler', 500);
    }
    
    /**
     * Get all routes (for documentation)
     */
    public function getRoutes() {
        return array_map(function($route) {
            return [
                'method' => $route['method'],
                'path' => $route['path'],
                'middleware' => $route['middleware']
            ];
        }, $this->routes);
    }
}
