<?php

declare(strict_types=1);

namespace Core;

class Query
{
    public const REGEX_PATTERN_NAMED_PARAMS = '/(?:\'(?:\\\\.|[^\\\\\'])*\'|\"(?:\\\\.|[^\\\\\"])*\"|\`(?:\\\\.|[^\\\\\`])*\`|\[(?:\\\\.|[^\[\]])*?\]|(\:\w+)(?=(?:[^\'\"\`\[\]]|\'(?:\\\\.|[^\\\\\'])*\'|\"(?:\\\\.|[^\\\\\"])*\"|\`(?:\\\\.|[^\\\\\`])*\`|\[(?:\\\\.|[^\[\]])*?\])*$)|(?:\-\-[^\r\n]*|\/\*[\s\S]*?\*\/|\#.*))/m';
    public const INI_PCRE_JIT = 'pcre.jit';

    public function __construct(
        public string $sql,
        public array $params
    ) {
    }

    public function toSql(string $driver): string
    {
        return $this->pregReplaceCallback(
            static::REGEX_PATTERN_NAMED_PARAMS,
            function (array $match) use ($driver): string {
                if (!$this->isQuestionMarkMatch($match)) {
                    return $match[0];
                }

                $key = $match[1];
                $value = $this->params[$key];

                return $this->castParam($driver, $value);
            },
            $this->sql
        );
    }

    private function castParam(string $driver, mixed $value): string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return (string) ($value ? 1 : 0);
        }

        if (is_string($value)) {
            return match ($driver) {
                'mysql' => sprintf('"%s"', str_replace('"', '""', (string) $value)),
                default => sprintf("'%s'", str_replace("'", "''", (string) $value)),
            };
        }

        if ($value instanceof DateTimeInterface) {
            return $this->castParam(
                $driver,
                $value->format('Y-m-d H:i:s')
            );
        }

        return (string) $value;
    }

    private function pregReplaceCallback(string $pattern, callable $callback, string $subject): string
    {
        /**
         * To prevent possible out-of-memory issues, PCRE just in time compilation needs to be temporarily disabled
         * This can only happen with large recursive capture groups when nested quotes or very large IN lists are present
         */
        $ini = ini_get(static::INI_PCRE_JIT);

        ini_set(static::INI_PCRE_JIT, '0');

        $result = (string) preg_replace_callback($pattern, $callback, $subject);

        if (!is_bool($ini)) {
            ini_set(static::INI_PCRE_JIT, $ini);
        }

        return $result;
    }

    private function isQuestionMarkMatch(array $match): bool
    {
        return count($match) > 1;
    }
}
