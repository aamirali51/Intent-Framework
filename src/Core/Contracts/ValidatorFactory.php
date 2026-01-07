<?php

declare(strict_types=1);

namespace Core\Contracts;

use Core\Validator;

/**
 * Validator factory interface for type-safe helper access.
 * 
 * Provides PHPStan Level 9 compatibility for validator() helper.
 */
interface ValidatorFactory
{
    /**
     * Create a new validator instance.
     * 
     * @param array<string, mixed> $data
     * @param array<string, string|array<int, string|int>> $rules
     * @param array<string, string> $messages
     */
    public function make(array $data, array $rules, array $messages = []): Validator;
}
