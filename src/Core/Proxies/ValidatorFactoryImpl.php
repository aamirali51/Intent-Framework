<?php

declare(strict_types=1);

namespace Core\Proxies;

use Core\Contracts\ValidatorFactory;
use Core\Validator;

/**
 * Validator factory implementation.
 * 
 * Wraps static Validator::make() for instance-style access via Registry.
 */
final class ValidatorFactoryImpl implements ValidatorFactory
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string|array<int, string|int>> $rules
     * @param array<string, string> $messages
     */
    public function make(array $data, array $rules, array $messages = []): Validator
    {
        return Validator::make($data, $rules, $messages);
    }
}
