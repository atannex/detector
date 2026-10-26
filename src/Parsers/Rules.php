<?php

declare(strict_types=1);

namespace Atannex\Detector\Parsers;

/**
 * The rule files in resources/rules, loaded once per process and compiled
 * into case-insensitive patterns.
 *
 * @internal
 */
final class Rules
{
    /** @var array<string, list<array{0: string, 1: mixed, 2?: mixed, 3?: mixed, 4?: mixed}>> */
    private static array $loaded = [];

    /** @var array<string, string> */
    private static array $combined = [];

    /**
     * @return list<array<int, mixed>> each rule with [0] compiled
     */
    public static function get(string $name): array
    {
        if (! isset(self::$loaded[$name])) {
            $rules = require dirname(__DIR__, 2)."/resources/rules/{$name}.php";

            foreach ($rules as $i => $rule) {
                $rules[$i][0] = self::compile($rule[0]);
            }

            self::$loaded[$name] = $rules;
        }

        return self::$loaded[$name];
    }

    /**
     * One pattern matching anything any rule of the file matches: a cheap
     * "is any of these in here at all" before trying them one by one.
     */
    public static function any(string $name): string
    {
        if (! isset(self::$combined[$name])) {
            $sources = array_map(fn (array $rule) => '(?:'.self::source($rule[0]).')', self::get($name));
            self::$combined[$name] = '~'.implode('|', $sources).'~i';
        }

        return self::$combined[$name];
    }

    public static function compile(string $pattern): string
    {
        return '~'.str_replace('~', '\~', $pattern).'~i';
    }

    /**
     * The first non-empty capture of v, v2, v3… (PCRE wants unique names).
     *
     * @param  array<int|string, string>  $matches
     */
    public static function version(array $matches): ?string
    {
        foreach (['v', 'v2', 'v3', 'v4'] as $group) {
            if (isset($matches[$group]) && $matches[$group] !== '') {
                return $matches[$group];
            }
        }

        return null;
    }

    private static function source(string $compiled): string
    {
        // Drop the delimiters and named groups (duplicate names across rules).
        $source = substr($compiled, 1, -2);

        return (string) preg_replace('/\(\?<[a-zA-Z]\w*>/', '(?:', $source);
    }
}
