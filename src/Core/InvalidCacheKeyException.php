<?php

declare(strict_types=1);

namespace Core;

use Psr\SimpleCache\InvalidArgumentException;
use InvalidArgumentException as BaseInvalidArgumentException;

/**
 * PSR-16 compliant cache key exception.
 */
class InvalidCacheKeyException extends BaseInvalidArgumentException implements InvalidArgumentException
{
}
