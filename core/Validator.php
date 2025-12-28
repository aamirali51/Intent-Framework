<?php

declare(strict_types=1);

namespace Core;

/**
 * Simple, explicit validation.
 * 
 * Returns array of errors (no exceptions thrown by default).
 * 
 * Usage:
 *   $validator = new Validator($data, [
 *       'name' => 'required|min:3|max:255',
 *       'email' => 'required|email',
 *       'age' => 'integer|min:18',
 *   ]);
 *   
 *   if ($validator->fails()) {
 *       return $response->json(['errors' => $validator->errors()], 422);
 *   }
 */
final class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $customMessages = [];

    /**
     * Built-in validation rules.
     */
    private const RULES = [
        'required', 'email', 'url', 'integer', 'numeric', 'boolean',
        'string', 'array', 'min', 'max', 'between', 'in', 'not_in',
        'regex', 'confirmed', 'nullable', 'alpha', 'alpha_num',
    ];

    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $messages;
        $this->validate();
    }

    /**
     * Static factory for fluent usage.
     */
    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new self($data, $rules, $messages);
    }

    /**
     * Check if validation failed.
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if validation passed.
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Get all validation errors.
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get validated data (only fields that were validated).
     */
    public function validated(): array
    {
        $validated = [];
        foreach (array_keys($this->rules) as $field) {
            if (array_key_exists($field, $this->data)) {
                $validated[$field] = $this->data[$field];
            }
        }
        return $validated;
    }

    /**
     * Get first error message.
     */
    public function firstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }
        return null;
    }

    /**
     * Run validation.
     */
    private function validate(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            // Check if nullable and value is null
            if (in_array('nullable', $rules, true) && $value === null) {
                continue;
            }

            foreach ($rules as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }

                $this->applyRule($field, $value, $rule);
            }
        }
    }

    /**
     * Apply a single rule.
     */
    private function applyRule(string $field, mixed $value, string $rule): void
    {
        // Parse rule:param format
        $params = [];
        if (str_contains($rule, ':')) {
            [$rule, $paramString] = explode(':', $rule, 2);
            $params = explode(',', $paramString);
        }

        $method = 'validate' . str_replace('_', '', ucwords($rule, '_'));

        if (method_exists($this, $method)) {
            $result = $this->$method($field, $value, $params);
            if ($result !== true) {
                $this->addError($field, $rule, $params, $result);
            }
        }
    }

    /**
     * Add an error message.
     */
    private function addError(string $field, string $rule, array $params, string $default): void
    {
        // Check for custom message
        $key = "{$field}.{$rule}";
        if (isset($this->customMessages[$key])) {
            $message = $this->customMessages[$key];
        } elseif (isset($this->customMessages[$field])) {
            $message = $this->customMessages[$field];
        } else {
            $message = $default;
        }

        // Replace placeholders
        $message = str_replace(':field', $this->formatField($field), $message);
        $message = str_replace(':value', (string) ($this->data[$field] ?? ''), $message);
        foreach ($params as $i => $param) {
            $message = str_replace(':param' . $i, $param, $message);
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Format field name for display.
     */
    private function formatField(string $field): string
    {
        return ucfirst(str_replace(['_', '-'], ' ', $field));
    }

    // ─────────────────────────────────────────────────────────────
    // Validation Rules
    // ─────────────────────────────────────────────────────────────

    private function validateRequired(string $field, mixed $value): true|string
    {
        if ($value === null || $value === '' || $value === []) {
            return ':field is required';
        }
        return true;
    }

    private function validateEmail(string $field, mixed $value): true|string
    {
        if ($value === null || $value === '') {
            return true; // Use required for presence check
        }
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return ':field must be a valid email address';
        }
        return true;
    }

    private function validateUrl(string $field, mixed $value): true|string
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return ':field must be a valid URL';
        }
        return true;
    }

    private function validateInteger(string $field, mixed $value): true|string
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!is_int($value) && !ctype_digit((string) $value)) {
            return ':field must be an integer';
        }
        return true;
    }

    private function validateNumeric(string $field, mixed $value): true|string
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!is_numeric($value)) {
            return ':field must be a number';
        }
        return true;
    }

    private function validateBoolean(string $field, mixed $value): true|string
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!in_array($value, [true, false, 0, 1, '0', '1'], true)) {
            return ':field must be true or false';
        }
        return true;
    }

    private function validateString(string $field, mixed $value): true|string
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!is_string($value)) {
            return ':field must be a string';
        }
        return true;
    }

    private function validateArray(string $field, mixed $value): true|string
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!is_array($value)) {
            return ':field must be an array';
        }
        return true;
    }

    private function validateMin(string $field, mixed $value, array $params): true|string
    {
        if ($value === null || $value === '') {
            return true;
        }
        $min = (int) ($params[0] ?? 0);

        if (is_string($value)) {
            if (mb_strlen($value) < $min) {
                return ":field must be at least :param0 characters";
            }
        } elseif (is_numeric($value)) {
            if ($value < $min) {
                return ":field must be at least :param0";
            }
        } elseif (is_array($value)) {
            if (count($value) < $min) {
                return ":field must have at least :param0 items";
            }
        }
        return true;
    }

    private function validateMax(string $field, mixed $value, array $params): true|string
    {
        if ($value === null || $value === '') {
            return true;
        }
        $max = (int) ($params[0] ?? 0);

        if (is_string($value)) {
            if (mb_strlen($value) > $max) {
                return ":field must not exceed :param0 characters";
            }
        } elseif (is_numeric($value)) {
            if ($value > $max) {
                return ":field must not exceed :param0";
            }
        } elseif (is_array($value)) {
            if (count($value) > $max) {
                return ":field must not have more than :param0 items";
            }
        }
        return true;
    }

    private function validateBetween(string $field, mixed $value, array $params): true|string
    {
        if ($value === null || $value === '') {
            return true;
        }
        $min = (int) ($params[0] ?? 0);
        $max = (int) ($params[1] ?? 0);

        if (is_string($value)) {
            $len = mb_strlen($value);
            if ($len < $min || $len > $max) {
                return ":field must be between :param0 and :param1 characters";
            }
        } elseif (is_numeric($value)) {
            if ($value < $min || $value > $max) {
                return ":field must be between :param0 and :param1";
            }
        }
        return true;
    }

    private function validateIn(string $field, mixed $value, array $params): true|string
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!in_array((string) $value, $params, true)) {
            return ":field must be one of: " . implode(', ', $params);
        }
        return true;
    }

    private function validateNotIn(string $field, mixed $value, array $params): true|string
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (in_array((string) $value, $params, true)) {
            return ":field must not be: " . implode(', ', $params);
        }
        return true;
    }

    private function validateRegex(string $field, mixed $value, array $params): true|string
    {
        if ($value === null || $value === '') {
            return true;
        }
        $pattern = $params[0] ?? '';
        if (!preg_match($pattern, (string) $value)) {
            return ":field format is invalid";
        }
        return true;
    }

    private function validateConfirmed(string $field, mixed $value): true|string
    {
        $confirmValue = $this->data[$field . '_confirmation'] ?? null;
        if ($value !== $confirmValue) {
            return ":field confirmation does not match";
        }
        return true;
    }

    private function validateAlpha(string $field, mixed $value): true|string
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!ctype_alpha((string) $value)) {
            return ":field must contain only letters";
        }
        return true;
    }

    private function validateAlphaNum(string $field, mixed $value): true|string
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!ctype_alnum((string) $value)) {
            return ":field must contain only letters and numbers";
        }
        return true;
    }
}
