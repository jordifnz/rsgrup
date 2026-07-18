<?php
class Router {
    private array $routes = [];

    public function get(string $path, callable|array $handler): void {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, $handler): void {
        $pattern = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $path);
        $this->routes[] = compact('method', 'path', 'pattern', 'handler');
    }

    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri    = '/' . trim($uri, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            if (preg_match('#^' . $route['pattern'] . '$#', $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->call($route['handler'], $params);
                return;
            }
        }

        http_response_code(404);
        include BASE_PATH . '/public/404.php';
    }

    private function call($handler, array $params): void {
        if (is_callable($handler)) {
            call_user_func($handler, $params);
        } elseif (is_array($handler)) {
            [$class, $method] = $handler;
            $ctrl = new $class();
            $ctrl->$method($params);
        }
    }
}
