<?php

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private string $prefix = '';
    private array $groupMiddleware = [];
    private array $namedRoutes = [];

    public function group(array $attributes, callable $callback): void
    {
        $previousPrefix = $this->prefix;
        $previousMiddleware = $this->groupMiddleware;

        if (isset($attributes['prefix'])) {
            $this->prefix .= '/' . trim($attributes['prefix'], '/');
        }
        if (isset($attributes['middleware'])) {
            $this->groupMiddleware = array_merge($this->groupMiddleware, (array) $attributes['middleware']);
        }

        $callback($this);

        $this->prefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    public function get(string $path, $handler, ?string $name = null): void
    {
        $this->addRoute('GET', $path, $handler, $name);
    }

    public function post(string $path, $handler, ?string $name = null): void
    {
        $this->addRoute('POST', $path, $handler, $name);
    }

    public function put(string $path, $handler, ?string $name = null): void
    {
        $this->addRoute('PUT', $path, $handler, $name);
    }

    public function delete(string $path, $handler, ?string $name = null): void
    {
        $this->addRoute('DELETE', $path, $handler, $name);
    }

    private function addRoute(string $method, string $path, $handler, ?string $name): void
    {
        $fullPath = $this->prefix . '/' . trim($path, '/');
        $fullPath = rtrim($fullPath, '/');
        $fullPath = $fullPath === '' ? '/' : $fullPath;
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $fullPath);
        $pattern = "#^{$pattern}$#";

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $this->groupMiddleware,
            'path' => $fullPath,
        ];

        if ($name) {
            $this->namedRoutes[$name] = $fullPath;
        }
    }

    public function middleware(string $name): self
    {
        $this->groupMiddleware[] = $name;
        return $this;
    }

    public function addMiddleware(string $name, callable $handler): void
    {
        $this->middleware[$name] = $handler;
    }

    public function dispatch(string $method, string $uri): mixed
    {
        // Normalize URI: strip /public prefix if LiteSpeed added it
        $uri = '/' . trim(parse_url($uri, PHP_URL_PATH), '/\\');
        $uri = preg_replace('#^/public#', '', $uri);
        $uri = '/' . trim($uri, '/');
        if ($uri === '') $uri = '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) continue;

            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run middleware
                foreach ($route['middleware'] as $mwName) {
                    if (isset($this->middleware[$mwName])) {
                        $result = ($this->middleware[$mwName])($params);
                        if ($result === false) return false;
                        if (is_array($result)) $params = array_merge($params, $result);
                    }
                }

                // Call handler — return true even if handler returns null (e.g. closures that include views)
                if (is_callable($route['handler'])) {
                    $result = call_user_func_array($route['handler'], $params);
                    return $result ?? true;
                }

                if (is_string($route['handler']) && str_contains($route['handler'], '@')) {
                    [$controller, $action] = explode('@', $route['handler'], 2);
                    $basePath = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
                    $controllerFile = $basePath . "/app/Controllers/{$controller}.php";

                    if (file_exists($controllerFile)) {
                        // Always load BaseController first so child controllers can extend it
                        $baseFile = $basePath . "/app/Controllers/BaseController.php";
                        if (file_exists($baseFile) && !class_exists('BaseController')) {
                            require_once $baseFile;
                        }
                        require_once $controllerFile;
                        if (!class_exists($controller)) {
                            http_response_code(500);
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'error' => "Controller class {$controller} not found in {$controllerFile}"]);
                            return true;
                        }
                        $instance = new $controller();
                        if (!method_exists($instance, $action)) {
                            http_response_code(500);
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'error' => "Method {$action} not found on {$controller}"]);
                            return true;
                        }
                        $result = call_user_func_array([$instance, $action], $params);
                        return $result ?? true;
                    } else {
                        http_response_code(500);
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => "Controller file not found: {$controllerFile}"]);
                        return true;
                    }
                }

                return true;
            }
        }

        return null;
    }

    public function route(string $name, array $params = []): string
    {
        $path = $this->namedRoutes[$name] ?? '/';
        foreach ($params as $key => $value) {
            $path = str_replace("{{$key}}", $value, $path);
        }
        return $path;
    }

    /**
     * Return debug info about registered routes.
     */
    public function debugRoutes(): array
    {
        return [
            'count' => count($this->routes),
            'patterns' => array_map(fn($r) => $r['method'] . ' ' . $r['path'] . ' [' . $r['pattern'] . ']', $this->routes),
        ];
    }
}