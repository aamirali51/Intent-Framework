<?php

declare(strict_types=1);

namespace Core;

/**
 * Simple, explicit validation.
 * 
 * Returns array of errors (no exceptions thrown by default).
 * 
 * Usage (string syntax - Laravel style):
 *   $validator = new Validator($data, [
 *       'name' => 'required|min:3|max:255',
 *       'email' => 'required|email',
 *   ]);
 * 
 * Usage (array syntax - no magic parsing):
 *   $validator = new Validator($data, [
 *       'name' => ['required', ['min', 3], ['max', 255]],
 *       'email' => ['required', 'email'],
 *   ]);
 *   
 *   if ($validator->fails()) {
 *       return $response->json(['errors' => $validator->errors()], 422);
 *   }
 */
final class Validator
{
    /** @var array<string, mixed> */
    private array $data;
    /** @var array<string, string|array<int, string|int>> */
    private array $rules;
    /** @var array<string, array<int, string>> */
    private array $errors = [];
    /** @var array<string, string> */
    private array $customMessages = [];

    // unused constant removed

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|array<int, string|int>> $rules
     * @param array<string, string> $messages
     */
    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $messages;
        $this->validate();
    }

    /**
     * Static factory for fluent usage.
     * 
     * @param array<string, mixed> $data
     * @param array<string, string|array<int, string|int>> $rules
     * @param array<string, string> $messages
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
     * 
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get validated data (only fields that were validated).
     * 
     * @return array<string, mixed>
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

                if (is_int($rule)) {
                    $rule = (string) $rule;
                }
                $this->applyRule($field, $value, $rule);
            }
        }
    }

    /**
     * Apply a single rule.
     * 
     * Supports multiple formats:
     *   'min:3'           - String with colon (Laravel style)
     *   ['min', 3]        - Array with params (explicit style)
     *   'required'        - Simple string rule
     * 
     * @param string|array<int, string|int> $rule
     */
    private function applyRule(string $field, mixed $value, string|array $rule): void
    {
        /** @var array<int, string|int> $params */
        $params = [];
        $ruleName = '';
        
        // Handle array format: ['min', 3] or ['between', 1, 10]
        if (is_array($rule)) {
            $ruleName = (string) array_shift($rule);
            $params = $rule;
        }
        // Handle string format: 'min:3' or 'in:a,b,c'
        elseif (str_contains($rule, ':')) {
            [$ruleName, $paramString] = explode(':', $rule, 2);
            $params = explode(',', $paramString);
        } else {
            $ruleName = $rule;
        }

        $method = 'validate' . str_replace('_', '', ucwords($ruleName, '_'));

        if (method_exists($this, $method)) {
            $result = $this->$method($field, $value, $params);
            if ($result !== true) {
                $this->addError($field, $ruleName, $params, $result);
            }
        }
    }

    /**
     * Add an error message.
     * 
     * @param array<int, string|int> $params
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
        /** @var string $fieldValue */
        $fieldValue = isset($this->data[$field]) ? (is_string($this->data[$field]) ? $this->data[$field] : '') : '';
        $message = str_replace(':value', $fieldValue, $message);
        foreach ($params as $i => $param) {
            $message = str_replace(':param' . $i, (string) $param, $message);
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
        if (!is_int($value) && !ctype_digit(is_string($value) ? $value : '')) {
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

    /**
     * @param array<int, string|int> $params
     */
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

    /**
     * @param array<int, string|int> $params
     */
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

    /**
     * @param array<int, string|int> $params
     */
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

    /**
     * @param array<int, string|int> $params
     */
    private function validateIn(string $field, mixed $value, array $params): true|string
    {
        if ($value === null || $value === '') {
            return true;
        }
        /** @var string $stringValue */
        $stringValue = is_scalar($value) ? (string) $value : '';
        /** @var array<int, string> $stringParams */
        $stringParams = array_map('strval', $params);
        if (!in_array($stringValue, $stringParams, true)) {
            return ":field must be one of: " . implode(', ', $stringParams);
        }
        return true;
    }

    /**
     * @param array<int, string|int> $params
     */
    private function validateNotIn(string $field, mixed $value, array $params): true|string
    {
        if ($value === null || $value === '') {
            return true;
        }
        /** @var string $stringValue */
        $stringValue = is_scalar($value) ? (string) $value : '';
        /** @var array<int, string> $stringParams */
        $stringParams = array_map('strval', $params);
        if (in_array($stringValue, $stringParams, true)) {
            return ":field must not be: " . implode(', ', $stringParams);
        }
        return true;
    }

    /**
     * @param array<int, string|int> $params
     */
    private function validateRegex(string $field, mixed $value, array $params): true|string
    {
        if ($value === null || $value === '') {
            return true;
        }
        /** @var string $pattern */
        $pattern = isset($params[0]) ? (string) $params[0] : '';
        /** @var string $stringValue */
        $stringValue = is_scalar($value) ? (string) $value : '';
        if (!preg_match($pattern, $stringValue)) {
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
        /** @var string $stringValue */
        $stringValue = is_scalar($value) ? (string) $value : '';
        if (!ctype_alpha($stringValue)) {
            return ":field must contain only letters";
        }
        return true;
    }

    private function validateAlphaNum(string $field, mixed $value): true|string
    {
        if ($value === null || $value === '') {
            return true;
        }
        /** @var string $stringValue */
        $stringValue = is_scalar($value) ? (string) $value : '';
        if (!ctype_alnum($stringValue)) {
            return ":field must contain only letters and numbers";
        }
        return true;
    }
}
