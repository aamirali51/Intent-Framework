<?php

declare(strict_types=1);

namespace Core\Exceptions;

/**
 * Base exception for Intent Framework.
 * 
 * All custom exceptions extend this.
 */
class IntentException extends \Exception
{
    protected int $statusCode = 500;

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Render the exception as an HTTP response.
     * 
     * @return array<string, mixed>
     */
    public function render(): array
    {
        return [
            'error' => $this->getMessage(),
            'code' => $this->getCode(),
        ];
    }
}

/**
 * 404 Not Found - Resource doesn't exist.
 */
class NotFoundException extends IntentException
{
    protected int $statusCode = 404;

    public function __construct(string $message = 'Not Found', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * 401 Unauthorized - Authentication required.
 */
class UnauthorizedException extends IntentException
{
    protected int $statusCode = 401;

    public function __construct(string $message = 'Unauthorized', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * 403 Forbidden - Authenticated but not allowed.
 */
class ForbiddenException extends IntentException
{
    protected int $statusCode = 403;

    public function __construct(string $message = 'Forbidden', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * 422 Validation Error - Input validation failed.
 */
class ValidationException extends IntentException
{
    protected int $statusCode = 422;
    /** @var array<string, array<int, string>> */
    protected array $errors = [];

    /**
     * @param array<string, array<int, string>> $errors
     */
    public function __construct(array $errors, string $message = 'Validation failed')
    {
        $this->errors = $errors;
        parent::__construct($message);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function render(): array
    {
        return [
            'error' => $this->getMessage(),
            'errors' => $this->errors,
        ];
    }
}

/**
 * 429 Too Many Requests - Rate limit exceeded.
 */
class TooManyRequestsException extends IntentException
{
    protected int $statusCode = 429;
    protected int $retryAfter;

    public function __construct(int $retryAfter = 60, string $message = 'Too many requests')
    {
        $this->retryAfter = $retryAfter;
        parent::__construct($message);
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * @return array<string, mixed>
     */
    public function render(): array
    {
        return [
            'error' => $this->getMessage(),
            'retry_after' => $this->retryAfter,
        ];
    }
}

/**
 * 500 Internal Server Error - Something went wrong.
 */
class ServerException extends IntentException
{
    protected int $statusCode = 500;

    public function __construct(string $message = 'Internal Server Error', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * 503 Service Unavailable - Maintenance mode.
 */
class MaintenanceException extends IntentException
{
    protected int $statusCode = 503;

    public function __construct(string $message = 'Service temporarily unavailable')
    {
        parent::__construct($message);
    }
}
