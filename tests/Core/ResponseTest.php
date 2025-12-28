<?php

declare(strict_types=1);

namespace Tests\Core;

use Core\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testDefaultStatus(): void
    {
        $response = new Response();
        $this->assertEquals(200, $response->getStatus());
    }

    public function testSetStatus(): void
    {
        $response = new Response();
        $response->status(404);
        $this->assertEquals(404, $response->getStatus());
    }

    public function testFluentInterface(): void
    {
        $response = new Response();
        $result = $response->status(201)->header('X-Test', 'value');
        
        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(201, $response->getStatus());
    }

    public function testJsonResponse(): void
    {
        $response = new Response();
        $response->json(['name' => 'John']);
        
        $this->assertEquals('{"name":"John"}', $response->getBody());
    }

    public function testHtmlResponse(): void
    {
        $response = new Response();
        $response->html('<h1>Hello</h1>');
        
        $this->assertEquals('<h1>Hello</h1>', $response->getBody());
    }

    public function testTextResponse(): void
    {
        $response = new Response();
        $response->text('Plain text');
        
        $this->assertEquals('Plain text', $response->getBody());
    }

    public function testJsonWithStatus(): void
    {
        $response = new Response();
        $response->json(['error' => 'Not found'], 404);
        
        $this->assertEquals(404, $response->getStatus());
    }

    public function testRedirectResponse(): void
    {
        $response = new Response();
        $response->redirect('/login');
        
        $this->assertEquals(302, $response->getStatus());
    }

    public function testRedirectWithCustomStatus(): void
    {
        $response = new Response();
        $response->redirect('/new-location', 301);
        
        $this->assertEquals(301, $response->getStatus());
    }
}
