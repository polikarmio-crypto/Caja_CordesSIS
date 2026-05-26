<?php
class Router {
    private $routes = [];

    public function add($method, $path, $callback) {
        $path = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_]+)', $path);
        $this->routes[] = [
            'method' => $method,
            'path' => '#^' . $path . '/?$#',
            'callback' => $callback
        ];
    }

    public function run() {
        // Obtener URI de REQUEST_URI (funciona con php -S y con Apache)
        $uri = $_SERVER['REQUEST_URI'];
        
        // Eliminar la query string (?foo=bar) para que no interfiera con el matching
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Eliminar el prefijo BASE_URL si existe (cuando el proyecto está en una subcarpeta)
        if (defined('BASE_URL') && BASE_URL !== '' && strpos($uri, BASE_URL) === 0) {
            $uri = substr($uri, strlen(BASE_URL));
        }
        
        $uri = rtrim($uri, '/');
        if ($uri === '') $uri = '/';
        $method = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            if ($route['method'] == $method && preg_match($route['path'], $uri, $matches)) {
                http_response_code(200); // Fuerza 200 OK en php -S
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                // Fix: callback might be an array like ['AuthController', 'login']. We need a new instance if it's not a static method.
                if (is_array($route['callback'])) {
                    $controllerName = $route['callback'][0];
                    $methodName = $route['callback'][1];
                    $controller = new $controllerName();
                    call_user_func_array([$controller, $methodName], array_values($params));
                } else {
                    call_user_func_array($route['callback'], array_values($params));
                }
                return;
            }
        }
        
        http_response_code(404);
        echo "404 Not Found";
    }
}
?>
