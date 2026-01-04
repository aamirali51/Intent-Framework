<?php

declare(strict_types=1);

namespace Core;

/**
 * HTTP Response builder.
 * 
 * Fluent interface for building and sending responses.
 */
final class Response
{
    private int $status = 200;
    /** @var array<string, string> */
    private array $headers = [];
    private string $body = '';

    /**
     * Set the HTTP status code.
     */
    public function status(int $code): self
    {
        $this->status = $code;
        return $this;
    }

    /**
     * Set a header.
     */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set the response body.
     */
    public function body(string $content): self
    {
        $this->body = $content;
        return $this;
    }

    /**
     * Send an HTML response.
     */
    public function html(string $content, int $status = 200): self
    {
        return $this
            ->status($status)
            ->header('Content-Type', 'text/html; charset=utf-8')
            ->body($content);
    }

    /**
     * Send a JSON response.
     */
    public function json(mixed $data, int $status = 200): self
    {
        return $this
            ->status($status)
            ->header('Content-Type', 'application/json')
            ->body(json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * Send a plain text response.
     */
    public function text(string $content, int $status = 200): self
    {
        return $this
            ->status($status)
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->body($content);
    }

    /**
     * Redirect to a URL.
     */
    public function redirect(string $url, int $status = 302): self
    {
        return $this
            ->status($status)
            ->header('Location', $url)
            ->body('');
    }

    /**
     * Send the response to the client.
     */
    public function send(): never
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->body;
        exit;
    }

    /**
     * Get the status code.
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Get the body content.
     */
    public function getBody(): string
    {
        return $this->body;
    }
}
