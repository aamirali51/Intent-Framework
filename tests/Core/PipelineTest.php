<?php

declare(strict_types=1);

namespace Tests\Core;

use Core\Pipeline;
use Core\Request;
use Core\Response;
use PHPUnit\Framework\TestCase;

class PipelineTest extends TestCase
{
    public function testEmptyPipeline(): void
    {
        $result = (new Pipeline())
            ->send('input')
            ->through([])
            ->then(fn($input) => $input . '_processed');
        
        $this->assertEquals('input_processed', $result);
    }

    public function testSingleMiddleware(): void
    {
        $middleware = function ($input, $next) {
            return $next($input . '_mid1');
        };

        $result = (new Pipeline())
            ->send('input')
            ->through([$middleware])
            ->then(fn($input) => $input . '_processed');
        
        $this->assertEquals('input_mid1_processed', $result);
    }

    public function testMultipleMiddleware(): void
    {
        $mid1 = fn($input, $next) => $next($input . '_mid1');
        $mid2 = fn($input, $next) => $next($input . '_mid2');
        $mid3 = fn($input, $next) => $next($input . '_mid3');

        $result = (new Pipeline())
            ->send('input')
            ->through([$mid1, $mid2, $mid3])
            ->then(fn($input) => $input . '_done');
        
        // Middleware executes in order: mid1 → mid2 → mid3 → destination
        $this->assertEquals('input_mid1_mid2_mid3_done', $result);
    }

    public function testMiddlewareCanShortCircuit(): void
    {
        $blockingMiddleware = fn($input, $next) => 'blocked';
        $neverReached = fn($input, $next) => $next($input . '_should_not_run');

        $result = (new Pipeline())
            ->send('input')
            ->through([$blockingMiddleware, $neverReached])
            ->then(fn($input) => $input . '_done');
        
        $this->assertEquals('blocked', $result);
    }

    public function testMiddlewareWithClassHandle(): void
    {
        $middlewareClass = new class {
            public function handle($input, $next)
            {
                return $next($input . '_class');
            }
        };

        $result = (new Pipeline())
            ->send('input')
            ->through([$middlewareClass])
            ->then(fn($input) => $input . '_done');
        
        $this->assertEquals('input_class_done', $result);
    }
}
