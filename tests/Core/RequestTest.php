<?php

declare(strict_types=1);

namespace Tests\Core;

use Core\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset superglobals
        $_GET = [];
        $_POST = [];
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ];
    }

    public function testDefaultMethod(): void
    {
        $request = new Request();
        $this->assertEquals('GET', $request->method);
    }

    public function testPostMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = new Request();
        $this->assertEquals('POST', $request->method);
    }

    public function testDefaultPath(): void
    {
        $request = new Request();
        $this->assertEquals('/', $request->path);
    }

    public function testPathWithQuery(): void
    {
        $_SERVER['REQUEST_URI'] = '/users?page=1';
        $request = new Request();
        $this->assertEquals('/users', $request->path);
    }

    public function testNestedPath(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/v1/users/123';
        $request = new Request();
        $this->assertEquals('/api/v1/users/123', $request->path);
    }

    public function testGetQueryParam(): void
    {
        $_GET['page'] = '5';
        $request = new Request();
        $this->assertEquals('5', $request->get('page'));
    }

    public function testGetQueryParamDefault(): void
    {
        $request = new Request();
        $this->assertEquals(1, $request->get('page', 1));
    }

    public function testPostParam(): void
    {
        $_POST['name'] = 'John';
        $request = new Request();
        $this->assertEquals('John', $request->post('name'));
    }

    public function testPostParamDefault(): void
    {
        $request = new Request();
        $this->assertNull($request->post('name'));
        $this->assertEquals('default', $request->post('name', 'default'));
    }

    public function testHeaderDefault(): void
    {
        $request = new Request();
        $this->assertNull($request->header('x-custom'));
        $this->assertEquals('default', $request->header('x-custom', 'default'));
    }

    public function testHeaderParsing(): void
    {
        $_SERVER['HTTP_X_CUSTOM_HEADER'] = 'custom-value';
        $request = new Request();
        $this->assertEquals('custom-value', $request->header('x-custom-header'));
    }

    public function testIsAjax(): void
    {
        $request = new Request();
        $this->assertFalse($request->isAjax());
        
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $request = new Request();
        $this->assertTrue($request->isAjax());
    }

    public function testWantsJson(): void
    {
        $request = new Request();
        $this->assertFalse($request->wantsJson());
        
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $request = new Request();
        $this->assertTrue($request->wantsJson());
    }

    public function testWantsJsonPartial(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html, application/json';
        $request = new Request();
        $this->assertTrue($request->wantsJson());
    }

    public function testJsonBodyNull(): void
    {
        $request = new Request();
        $this->assertNull($request->json());
    }

    public function testQueryArray(): void
    {
        $_GET = ['foo' => 'bar', 'baz' => 'qux'];
        $request = new Request();
        $this->assertEquals(['foo' => 'bar', 'baz' => 'qux'], $request->query);
    }

    public function testPostArray(): void
    {
        $_POST = ['name' => 'John', 'email' => 'john@example.com'];
        $request = new Request();
        $this->assertEquals(['name' => 'John', 'email' => 'john@example.com'], $request->post);
    }

    public function testUri(): void
    {
        $_SERVER['REQUEST_URI'] = '/users?page=1&sort=name';
        $request = new Request();
        $this->assertEquals('/users?page=1&sort=name', $request->uri);
    }

    public function testMethodCaseInsensitive(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'post';
        $request = new Request();
        $this->assertEquals('POST', $request->method);
    }
}
