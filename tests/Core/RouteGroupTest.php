<?php

declare(strict_types=1);

namespace Tests\Core;

use Core\Router;
use Core\Config;
use PHPUnit\Framework\TestCase;

class RouteGroupTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        Config::set('routing.file_routes_first', false);
        Config::set('feature.file_routes', false);
    }

    public function testGroupWithPrefix(): void
    {
        $this->router->group(['prefix' => '/admin'], function ($router) {
            $router->get('/dashboard', fn() => 'dashboard');
            $router->get('/users', fn() => 'users');
        });

        $this->assertTrue($this->router->hasRoute('GET', '/admin/dashboard'));
        $this->assertTrue($this->router->hasRoute('GET', '/admin/users'));
    }

    public function testGroupWithMiddleware(): void
    {
        $middleware = 'AuthMiddleware';
        
        $this->router->group(['middleware' => $middleware], function ($router) {
            $router->get('/protected', fn() => 'protected');
        });

        $this->assertTrue($this->router->hasRoute('GET', '/protected'));
    }

    public function testGroupWithPrefixAndMiddleware(): void
    {
        $this->router->group([
            'prefix' => '/api',
            'middleware' => 'ApiMiddleware',
        ], function ($router) {
            $router->get('/users', fn() => 'users');
            $router->post('/users', fn() => 'create');
        });

        $this->assertTrue($this->router->hasRoute('GET', '/api/users'));
        $this->assertTrue($this->router->hasRoute('POST', '/api/users'));
    }

    public function testNestedGroups(): void
    {
        $this->router->group(['prefix' => '/api'], function ($router) {
            $router->group(['prefix' => '/v1'], function ($router) {
                $router->get('/users', fn() => 'users');
            });
        });

        $this->assertTrue($this->router->hasRoute('GET', '/api/v1/users'));
    }

    public function testNestedGroupsWithMiddleware(): void
    {
        $this->router->group(['middleware' => 'Auth'], function ($router) {
            $router->group(['middleware' => 'Admin'], function ($router) {
                $router->get('/settings', fn() => 'settings');
            });
        });

        // Route should exist (middleware is internal, we just check route exists)
        $this->assertTrue($this->router->hasRoute('GET', '/settings'));
    }

    public function testGroupDoesNotAffectOutsideRoutes(): void
    {
        $this->router->get('/outside', fn() => 'outside');
        
        $this->router->group(['prefix' => '/admin'], function ($router) {
            $router->get('/inside', fn() => 'inside');
        });
        
        $this->router->get('/also-outside', fn() => 'also-outside');

        $this->assertTrue($this->router->hasRoute('GET', '/outside'));
        $this->assertTrue($this->router->hasRoute('GET', '/admin/inside'));
        $this->assertTrue($this->router->hasRoute('GET', '/also-outside'));
        
        // /inside without prefix should NOT exist
        $this->assertFalse($this->router->hasRoute('GET', '/inside'));
    }

    public function testEmptyGroup(): void
    {
        $this->router->group(['prefix' => '/empty'], function ($router) {
            // No routes registered
        });

        // Should not throw, just do nothing
        $this->assertFalse($this->router->hasRoute('GET', '/empty'));
    }
}
