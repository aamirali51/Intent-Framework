<?php

declare(strict_types=1);

namespace Tests\Core;

use Core\Router;
use Core\Request;
use Core\Response;
use Core\Config;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        Config::set('routing.file_routes_first', false);
        Config::set('feature.file_routes', false); // Disable for unit tests
    }

    public function testStaticRouteRegistration(): void
    {
        $handler = fn() => 'test';
        $this->router->get('/test', $handler);
        
        $this->assertTrue($this->router->hasRoute('GET', '/test'));
        $this->assertFalse($this->router->hasRoute('POST', '/test'));
    }

    public function testDynamicRouteRegistration(): void
    {
        $handler = fn() => 'test';
        $this->router->get('/users/{id}', $handler);
        
        $this->assertTrue($this->router->hasRoute('GET', '/users/{id}'));
    }

    public function testMultipleHttpMethods(): void
    {
        $handler = fn() => 'test';
        
        $this->router->get('/resource', $handler);
        $this->router->post('/resource', $handler);
        $this->router->put('/resource', $handler);
        $this->router->delete('/resource', $handler);
        
        $this->assertTrue($this->router->hasRoute('GET', '/resource'));
        $this->assertTrue($this->router->hasRoute('POST', '/resource'));
        $this->assertTrue($this->router->hasRoute('PUT', '/resource'));
        $this->assertTrue($this->router->hasRoute('DELETE', '/resource'));
    }

    public function testAnyMethodRegistration(): void
    {
        $handler = fn() => 'test';
        $this->router->any('/webhook', $handler);
        
        $this->assertTrue($this->router->hasRoute('GET', '/webhook'));
        $this->assertTrue($this->router->hasRoute('POST', '/webhook'));
        $this->assertTrue($this->router->hasRoute('PUT', '/webhook'));
        $this->assertTrue($this->router->hasRoute('DELETE', '/webhook'));
    }

    public function testPathNormalization(): void
    {
        $handler = fn() => 'test';
        
        $this->router->get('test', $handler);
        $this->assertTrue($this->router->hasRoute('GET', '/test'));
        
        $this->router->get('/trailing/', $handler);
        $this->assertTrue($this->router->hasRoute('GET', '/trailing'));
    }
}
