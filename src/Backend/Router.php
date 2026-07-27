<?php
/**
 * Pit o Cuixa — Router
 *
 * Maps HTTP method + URI path to controller callables.
 * Supports:
 * - Static routes: ['GET', '/api/products', handler]
 * - Parameterised routes: ['GET', '/api/products/{slug}', handler]
 * - Fallback 404 handler
 *
 * Returns an associative array with 'handler' and 'params'.
 *
 * @package Pit\Cuixa\Backend
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend;

use Pit\Cuixa\Backend\Http\Response;

class Router
{
    /** @var array<int, array{string, string, callable}> */
    private array $routes = [];

    /** @var callable|null */
    private $notFoundHandler = null;

    /**
     * Register a route.
     *
     * @param string   $method   HTTP method (GET, POST, PUT, DELETE)
     * @param string   $path     URI pattern with optional {param} placeholders
     * @param callable $handler  Callback receiving (array $params) as argument
     */
    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [$method, $path, $handler];
    }

    /**
     * Set the fallback 404 handler.
     *
     * @param callable $handler
     */
    public function setNotFound(callable $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    /**
     * Resolve a request to a handler.
     *
     * @param  string $method  HTTP method (e.g. GET)
     * @param  string $uri     Full request URI (e.g. /api/products/pollo-enter)
     * @return array{handler: callable, params: array}
     */
    public function resolve(string $method, string $uri): array
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = rtrim($path ?? '/', '/') ?: '/';

        foreach ($this->routes as [$routeMethod, $routePath, $handler]) {
            if ($routeMethod !== $method) {
                continue;
            }

            $params = $this->match($routePath, $path);

            if ($params !== null) {
                return [
                    'handler' => $handler,
                    'params'  => $params,
                ];
            }
        }

        // No match — use fallback or built-in 404
        if ($this->notFoundHandler !== null) {
            return [
                'handler' => $this->notFoundHandler,
                'params'  => [],
            ];
        }

        // Built-in 404
        return [
            'handler' => static function (): void {
                Response::error('Not Found', 404);
            },
            'params' => [],
        ];
    }

    /**
     * Match a route pattern against a real path.
     * Returns associative array of params, or null if no match.
     *
     * @param  string      $pattern  Route pattern (e.g. /api/products/{slug})
     * @param  string      $path     Request path (e.g. /api/products/pollo-enter)
     * @return array|null
     */
    private function match(string $pattern, string $path): ?array
    {
        // Convert route pattern to regex
        // {param} → named capture group
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $path, $matches) !== 1) {
            return null;
        }

        // Filter named matches
        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    /**
     * Dispatch the resolved handler and return its output.
     * The handler receives the params array.
     */
    public function dispatch(string $method, string $uri): void
    {
        $result = $this->resolve($method, $uri);
        $handler = $result['handler'];
        $params  = $result['params'];

        $handler($params);
    }
}
