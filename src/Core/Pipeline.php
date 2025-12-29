<?php

declare(strict_types=1);

namespace Core;

/**
 * Simple, explicit middleware pipeline.
 * 
 * No global stacks, no magic registration.
 * Middleware is applied explicitly per-route.
 * 
 * Usage:
 *   Route::get('/admin', $handler)->middleware(AuthMiddleware::class);
 *   Route::get('/api/users', $handler)->middleware([RateLimitMiddleware::class, AuthMiddleware::class]);
 */
final class Pipeline
{
    /** @var array<callable> */
    private array $pipes = [];
    private mixed $passable;

    /**
     * Set the object being passed through the pipeline.
     */
    public function send(mixed $passable): self
    {
        $this->passable = $passable;
        return $this;
    }

    /**
     * Set the pipes (middleware) to run.
     * 
     * @param array<callable|string> $pipes
     */
    public function through(array $pipes): self
    {
        $this->pipes = $pipes;
        return $this;
    }

    /**
     * Run the pipeline and return the result.
     */
    public function then(callable $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $destination
        );

        return $pipeline($this->passable);
    }

    /**
     * Get the carry function that wraps each pipe.
     */
    private function carry(): callable
    {
        return function (callable $next, mixed $pipe): callable {
            return function (mixed $passable) use ($next, $pipe): mixed {
                // If pipe is a string, instantiate it
                if (is_string($pipe)) {
                    $pipe = new $pipe();
                }

                // If it's an object with handle method
                if (is_object($pipe) && method_exists($pipe, 'handle')) {
                    return $pipe->handle($passable, $next);
                }

                // If it's a callable
                if (is_callable($pipe)) {
                    return $pipe($passable, $next);
                }

                throw new \RuntimeException('Invalid middleware: must be callable or have handle() method');
            };
        };
    }
}
