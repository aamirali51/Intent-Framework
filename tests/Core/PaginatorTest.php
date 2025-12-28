<?php

declare(strict_types=1);

namespace Tests\Core;

use Core\Paginator;
use PHPUnit\Framework\TestCase;

class PaginatorTest extends TestCase
{
    public function testCreate(): void
    {
        $items = ['a', 'b', 'c'];
        $paginator = Paginator::create($items, 100, 10, 1);
        
        $this->assertEquals($items, $paginator->items());
        $this->assertEquals(100, $paginator->total());
        $this->assertEquals(10, $paginator->perPage());
        $this->assertEquals(1, $paginator->currentPage());
        $this->assertEquals(10, $paginator->lastPage());
    }

    public function testCount(): void
    {
        $items = ['a', 'b', 'c'];
        $paginator = Paginator::create($items, 100, 10, 1);
        
        $this->assertEquals(3, $paginator->count());
    }

    public function testIsEmpty(): void
    {
        $empty = Paginator::create([], 0, 10, 1);
        $this->assertTrue($empty->isEmpty());
        
        $notEmpty = Paginator::create(['a'], 1, 10, 1);
        $this->assertFalse($notEmpty->isEmpty());
    }

    public function testOnFirstPage(): void
    {
        $first = Paginator::create([], 100, 10, 1);
        $this->assertTrue($first->onFirstPage());
        
        $notFirst = Paginator::create([], 100, 10, 2);
        $this->assertFalse($notFirst->onFirstPage());
    }

    public function testOnLastPage(): void
    {
        $last = Paginator::create([], 100, 10, 10);
        $this->assertTrue($last->onLastPage());
        
        $notLast = Paginator::create([], 100, 10, 5);
        $this->assertFalse($notLast->onLastPage());
    }

    public function testHasMorePages(): void
    {
        $has = Paginator::create([], 100, 10, 5);
        $this->assertTrue($has->hasMorePages());
        
        $hasNot = Paginator::create([], 100, 10, 10);
        $this->assertFalse($hasNot->hasMorePages());
    }

    public function testUrl(): void
    {
        $paginator = Paginator::create([], 100, 10, 1, '/posts');
        
        $this->assertStringContainsString('page=1', $paginator->url(1));
        $this->assertStringContainsString('page=5', $paginator->url(5));
        $this->assertStringStartsWith('/posts', $paginator->url(1));
    }

    public function testPreviousPageUrl(): void
    {
        $first = Paginator::create([], 100, 10, 1, '/posts');
        $this->assertNull($first->previousPageUrl());
        
        $second = Paginator::create([], 100, 10, 2, '/posts');
        $this->assertStringContainsString('page=1', $second->previousPageUrl());
    }

    public function testNextPageUrl(): void
    {
        $last = Paginator::create([], 100, 10, 10, '/posts');
        $this->assertNull($last->nextPageUrl());
        
        $first = Paginator::create([], 100, 10, 1, '/posts');
        $this->assertStringContainsString('page=2', $first->nextPageUrl());
    }

    public function testFirstPageUrl(): void
    {
        $paginator = Paginator::create([], 100, 10, 5, '/posts');
        $this->assertStringContainsString('page=1', $paginator->firstPageUrl());
    }

    public function testLastPageUrl(): void
    {
        $paginator = Paginator::create([], 100, 10, 5, '/posts');
        $this->assertStringContainsString('page=10', $paginator->lastPageUrl());
    }

    public function testElements(): void
    {
        $paginator = Paginator::create([], 50, 10, 3, '/posts');
        $elements = $paginator->elements();
        
        $this->assertNotEmpty($elements);
        
        // Check active page exists
        $activeFound = false;
        foreach ($elements as $el) {
            if ($el['active']) {
                $activeFound = true;
                $this->assertEquals('3', $el['label']);
            }
        }
        $this->assertTrue($activeFound);
    }

    public function testLinks(): void
    {
        $paginator = Paginator::create([], 50, 10, 3, '/posts');
        $html = $paginator->links();
        
        $this->assertStringContainsString('<nav', $html);
        $this->assertStringContainsString('pagination', $html);
        $this->assertStringContainsString('page=', $html);
    }

    public function testLinksReturnsEmptyForSinglePage(): void
    {
        $paginator = Paginator::create(['a'], 1, 10, 1, '/posts');
        $this->assertEquals('', $paginator->links());
    }

    public function testToArray(): void
    {
        $items = ['a', 'b', 'c'];
        $paginator = Paginator::create($items, 100, 10, 1, '/posts');
        $array = $paginator->toArray();
        
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('current_page', $array);
        $this->assertArrayHasKey('last_page', $array);
        $this->assertArrayHasKey('per_page', $array);
        $this->assertArrayHasKey('total', $array);
        $this->assertArrayHasKey('prev_page_url', $array);
        $this->assertArrayHasKey('next_page_url', $array);
        
        $this->assertEquals($items, $array['data']);
        $this->assertEquals(1, $array['current_page']);
        $this->assertEquals(10, $array['last_page']);
    }

    public function testHandlesNegativeValues(): void
    {
        $paginator = Paginator::create([], -10, -5, -1);
        
        $this->assertEquals(0, $paginator->total());
        $this->assertEquals(1, $paginator->perPage());
        $this->assertEquals(1, $paginator->currentPage());
    }
}
