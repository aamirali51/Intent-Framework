<?php

declare(strict_types=1);

namespace Core;

/**
 * Application orchestrator.
 * 
 * Boots the framework and dispatches requests.
 * No container, no providers, no magic.
 */
final class App
{
    private Router $router;
    private Request $request;

    public function __construct()
    {
        // Load config
        Config::load(BASE_PATH . '/config.php');

        // Create instances
        $this->request = new Request();
        $this->router = new Router();

        // Initialize static Route facade
        Route::setRouter($this->router);
    }

    /**
     * Get the router instance.
     */
    public function router(): Router
    {
        return $this->router;
    }

    /**
     * Get the request instance.
     */
    public function request(): Request
    {
        return $this->request;
    }

    /**
     * Run the application.
     */
    public function run(): void
    {
        try {
            // Load user routes
            $this->loadRoutes();

            // Register default routes (only if not already defined)
            $this->registerDefaultRoutes();

            // Dispatch request
            $match = $this->router->dispatch($this->request);

            if ($match === null) {
                $this->notFound();
            }

            $handler = $match['handler'];
            $params = $match['params'];
            $middleware = $match['middleware'];

            // Execute through middleware pipeline
            $response = $this->runThroughMiddleware(
                $this->request,
                $middleware,
                function (Request $request) use ($handler, $params): Response {
                    $response = $handler($request, new Response(), $params);
                    return $response instanceof Response ? $response : new Response();
                }
            );

            // Send response
            $response->send();
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Run request through middleware pipeline.
     * 
     * @param Request $request
     * @param array<string|callable> $middleware
     * @param callable $destination Final handler
     * @return Response
     */
    private function runThroughMiddleware(Request $request, array $middleware, callable $destination): Response
    {
        if (empty($middleware)) {
            return $destination($request);
        }

        return (new Pipeline())
            ->send($request)
            ->through($middleware)
            ->then($destination);
    }

    /**
     * Handle uncaught exceptions.
     */
    private function handleException(\Throwable $e): never
    {
        $debug = Config::get('app.debug', false);
        $response = new Response();
        $status = $this->getHttpStatus($e);

        if ($this->request->wantsJson()) {
            $this->sendJsonError($response, $e, $status, $debug);
        }

        $this->sendHtmlError($response, $e, $status, $debug);
    }

    /**
     * Get HTTP status code from exception.
     */
    private function getHttpStatus(\Throwable $e): int
    {
        // Allow exceptions to define their own status code
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }

        return 500;
    }

    /**
     * Send JSON error response.
     */
    private function sendJsonError(Response $response, \Throwable $e, int $status, bool $debug): never
    {
        $error = ['error' => $debug ? $e->getMessage() : 'Internal Server Error'];

        if ($debug) {
            $error['exception'] = get_class($e);
            $error['file'] = $e->getFile();
            $error['line'] = $e->getLine();
            $error['trace'] = array_map(function ($frame) {
                return [
                    'file' => $frame['file'] ?? null,
                    'line' => $frame['line'] ?? null,
                    'function' => ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? ''),
                ];
            }, $e->getTrace());
        }

        $response->json($error, $status)->send();
    }

    /**
     * Send HTML error response.
     */
    private function sendHtmlError(Response $response, \Throwable $e, int $status, bool $debug): never
    {
        if ($debug) {
            $html = $this->renderDebugError($e, $status);
        } else {
            $html = $this->renderProductionError($status);
        }

        $response->html($html, $status)->send();
    }

    /**
     * Render debug error page with stack trace.
     */
    private function renderDebugError(\Throwable $e, int $status): string
    {
        $class = htmlspecialchars(get_class($e));
        $message = htmlspecialchars($e->getMessage());
        $file = htmlspecialchars($e->getFile());
        $line = $e->getLine();

        $trace = '';
        foreach ($e->getTrace() as $i => $frame) {
            $frameFile = htmlspecialchars($frame['file'] ?? 'unknown');
            $frameLine = $frame['line'] ?? 0;
            $func = htmlspecialchars(($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? ''));
            $trace .= "<div class=\"frame\"><span class=\"num\">#{$i}</span> <span class=\"file\">{$frameFile}:{$frameLine}</span> <span class=\"func\">{$func}()</span></div>";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{$status} - {$class}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: ui-monospace, monospace; background: #0a0a0a; color: #fafafa; padding: 2rem; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: #dc2626; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem; }
        .header h1 { font-size: 1.5rem; margin-bottom: 0.5rem; }
        .header p { opacity: 0.9; word-break: break-all; }
        .location { background: #1a1a1a; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; color: #fbbf24; }
        .trace { background: #1a1a1a; padding: 1rem; border-radius: 8px; }
        .trace h2 { margin-bottom: 1rem; font-size: 1rem; color: #888; }
        .frame { padding: 0.5rem 0; border-bottom: 1px solid #333; font-size: 0.875rem; }
        .frame:last-child { border-bottom: none; }
        .num { color: #888; margin-right: 1rem; }
        .file { color: #60a5fa; }
        .func { color: #4ade80; margin-left: 0.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$class}</h1>
            <p>{$message}</p>
        </div>
        <div class="location">{$file}:{$line}</div>
        <div class="trace">
            <h2>Stack Trace</h2>
            {$trace}
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render production error page.
     */
    private function renderProductionError(int $status): string
    {
        $messages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error',
        ];

        $message = $messages[$status] ?? 'Error';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{$status} - {$message}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: #0a0a0a; color: #fafafa; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { text-align: center; }
        h1 { font-size: 6rem; color: #dc2626; }
        p { font-size: 1.5rem; color: #888; margin-top: 0.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>{$status}</h1>
        <p>{$message}</p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Load route definitions.
     */
    private function loadRoutes(): void
    {
        $routesFile = BASE_PATH . '/routes.php';

        if (!file_exists($routesFile)) {
            throw new \RuntimeException('Routes file not found: routes.php');
        }

        $router = $this->router;
        require $routesFile;
    }

    /**
     * Handle 404 not found.
     */
    private function notFound(): never
    {
        $response = new Response();

        if ($this->request->wantsJson()) {
            $response->json(['error' => 'Not Found'], 404)->send();
        }

        $response->html('<h1>404 Not Found</h1>', 404)->send();
    }

    /**
     * Register default framework routes.
     * 
     * Only registers if the user has NOT defined these routes.
     * This allows the welcome page to auto-disappear once the user
     * defines their own '/' route.
     */
    private function registerDefaultRoutes(): void
    {
        // Welcome page - only if '/' is not already registered
        if (!$this->router->hasRoute('GET', '/')) {
            $this->router->get('/', function (Request $request, Response $response): Response {
                $welcomeFile = BASE_PATH . '/views/welcome.php';
                
                if (!file_exists($welcomeFile)) {
                    return $response->html('<h1>Intent Framework</h1><p>It works!</p>');
                }

                return $response->html(file_get_contents($welcomeFile));
            });
        }
    }
}

