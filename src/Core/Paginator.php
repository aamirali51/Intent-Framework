<?php

declare(strict_types=1);

namespace Core;

/**
 * Simple paginator.
 * 
 * Works with array data or DB results.
 * 
 * Usage:
 *   $paginator = Paginator::create($items, $total, $perPage, $currentPage);
 *   foreach ($paginator->items() as $item) { ... }
 *   echo $paginator->links();
 */
final class Paginator
{
    /** @var array<int, array<string, mixed>> */
    private array $items;
    private int $total;
    private int $perPage;
    private int $currentPage;
    private int $lastPage;
    private string $path;

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function __construct(
        array $items,
        int $total,
        int $perPage = 15,
        int $currentPage = 1,
        string $path = ''
    ) {
        $this->items = $items;
        $this->total = max(0, $total);
        $this->perPage = max(1, $perPage);
        $this->currentPage = max(1, $currentPage);
        $this->lastPage = max(1, (int) ceil($this->total / $this->perPage));
        $this->path = $path ?: $this->getCurrentPath();
    }

    /**
     * Create paginator from array data.
     * 
     * @param array<int, array<string, mixed>> $items
     */
    public static function create(
        array $items,
        int $total,
        int $perPage = 15,
        ?int $currentPage = null,
        string $path = ''
    ): self {
        $currentPage = $currentPage ?? self::resolveCurrentPage();
        return new self($items, $total, $perPage, $currentPage, $path);
    }

    /**
     * Create paginator for DB query (auto-paginate).
     */
    public static function fromQuery(
        QueryBuilder $query,
        int $perPage = 15,
        ?int $currentPage = null
    ): self {
        $currentPage = $currentPage ?? self::resolveCurrentPage();
        
        // Clone query for count
        $total = $query->count();
        
        // Get paginated items
        $offset = ($currentPage - 1) * $perPage;
        $items = $query->limit($perPage)->offset($offset)->get();
        
        return new self($items, $total, $perPage, $currentPage);
    }

    /**
     * Get current page from request.
     */
    public static function resolveCurrentPage(): int
    {
        /** @var int|string $page */
        $page = $_GET['page'] ?? 1;
        return max(1, (int) $page);
    }

    /**
     * Get the paginated items.
     * 
     * @return array<int, array<string, mixed>>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * Get total item count.
     */
    public function total(): int
    {
        return $this->total;
    }

    /**
     * Get items per page.
     */
    public function perPage(): int
    {
        return $this->perPage;
    }

    /**
     * Get current page number.
     */
    public function currentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Get last page number.
     */
    public function lastPage(): int
    {
        return $this->lastPage;
    }

    /**
     * Check if there are more pages.
     */
    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage;
    }

    /**
     * Check if on first page.
     */
    public function onFirstPage(): bool
    {
        return $this->currentPage <= 1;
    }

    /**
     * Check if on last page.
     */
    public function onLastPage(): bool
    {
        return $this->currentPage >= $this->lastPage;
    }

    /**
     * Get count of items on current page.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Check if there are any items.
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Get URL for a specific page.
     */
    public function url(int $page): string
    {
        $page = max(1, min($page, $this->lastPage));
        $separator = str_contains($this->path, '?') ? '&' : '?';
        return $this->path . $separator . 'page=' . $page;
    }

    /**
     * Get previous page URL.
     */
    public function previousPageUrl(): ?string
    {
        if ($this->onFirstPage()) {
            return null;
        }
        return $this->url($this->currentPage - 1);
    }

    /**
     * Get next page URL.
     */
    public function nextPageUrl(): ?string
    {
        if ($this->onLastPage()) {
            return null;
        }
        return $this->url($this->currentPage + 1);
    }

    /**
     * Get first page URL.
     */
    public function firstPageUrl(): string
    {
        return $this->url(1);
    }

    /**
     * Get last page URL.
     */
    public function lastPageUrl(): string
    {
        return $this->url($this->lastPage);
    }

    /**
     * Generate page links as array.
     * 
     * @param int $onEachSide Number of links on each side of current
     * @return array<int, array{url: string, label: string, active: bool}>
     */
    public function elements(int $onEachSide = 2): array
    {
        $window = $onEachSide * 2 + 1;
        $pages = [];

        if ($this->lastPage <= $window + 4) {
            // Show all pages
            for ($i = 1; $i <= $this->lastPage; $i++) {
                $pages[] = $this->pageElement($i);
            }
        } else {
            // Show with ellipsis
            $start = max(1, $this->currentPage - $onEachSide);
            $end = min($this->lastPage, $this->currentPage + $onEachSide);

            // Adjust window at edges
            if ($start <= 3) {
                $end = $window;
                $start = 1;
            } elseif ($end >= $this->lastPage - 2) {
                $start = $this->lastPage - $window + 1;
                $end = $this->lastPage;
            }

            // First page
            if ($start > 1) {
                $pages[] = $this->pageElement(1);
                if ($start > 2) {
                    $pages[] = ['url' => '', 'label' => '...', 'active' => false];
                }
            }

            // Middle pages
            for ($i = $start; $i <= $end; $i++) {
                $pages[] = $this->pageElement($i);
            }

            // Last page
            if ($end < $this->lastPage) {
                if ($end < $this->lastPage - 1) {
                    $pages[] = ['url' => '', 'label' => '...', 'active' => false];
                }
                $pages[] = $this->pageElement($this->lastPage);
            }
        }

        return $pages;
    }

    /**
     * Create page element.
     * 
     * @return array{url: string, label: string, active: bool}
     */
    private function pageElement(int $page): array
    {
        return [
            'url' => $this->url($page),
            'label' => (string) $page,
            'active' => $page === $this->currentPage,
        ];
    }

    /**
     * Generate simple HTML pagination links.
     */
    public function links(): string
    {
        if ($this->lastPage <= 1) {
            return '';
        }

        $html = '<nav class="pagination"><ul>';

        // Previous
        if ($this->onFirstPage()) {
            $html .= '<li class="disabled"><span>&laquo;</span></li>';
        } else {
            $html .= '<li><a href="' . htmlspecialchars($this->previousPageUrl() ?? '') . '">&laquo;</a></li>';
        }

        // Page numbers
        foreach ($this->elements() as $element) {
            if ($element['url'] === '') {
                $html .= '<li class="disabled"><span>' . $element['label'] . '</span></li>';
            } elseif ($element['active']) {
                $html .= '<li class="active"><span>' . $element['label'] . '</span></li>';
            } else {
                $html .= '<li><a href="' . htmlspecialchars($element['url']) . '">' . $element['label'] . '</a></li>';
            }
        }

        // Next
        if ($this->onLastPage()) {
            $html .= '<li class="disabled"><span>&raquo;</span></li>';
        } else {
            $html .= '<li><a href="' . htmlspecialchars($this->nextPageUrl() ?? '') . '">&raquo;</a></li>';
        }

        $html .= '</ul></nav>';

        return $html;
    }

    /**
     * Get current request path.
     */
    private function getCurrentPath(): string
    {
        /** @var string $uri */
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        if ($path === false || $path === null) {
            return '/';
        }
        return $path;
    }

    /**
     * Convert to array for JSON.
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'data' => $this->items,
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'per_page' => $this->perPage,
            'total' => $this->total,
            'from' => $this->isEmpty() ? null : (($this->currentPage - 1) * $this->perPage + 1),
            'to' => $this->isEmpty() ? null : (($this->currentPage - 1) * $this->perPage + $this->count()),
            'prev_page_url' => $this->previousPageUrl(),
            'next_page_url' => $this->nextPageUrl(),
        ];
    }
}
