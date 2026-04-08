<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025-2026 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\Enum\KeyNamingConvention;

use function count;

/**
 * KeyConverter.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class KeyConverter
{
    public function toSnakeCase(string $key): string
    {
        // Convert camelCase/PascalCase to snake_case
        $result = preg_replace('/([a-z])([A-Z])/', '$1_$2', $key);
        // Convert kebab-case and dot.notation to snake_case
        $result = str_replace(['-', '.'], '_', $result ?? $key);

        // Convert to lowercase
        return strtolower($result);
    }

    public function toCamelCase(string $key): string
    {
        // Handle camelCase/PascalCase first
        if (preg_match('/[A-Z]/', $key)) {
            // Convert PascalCase to camelCase
            return lcfirst($key);
        }

        // Convert snake_case, kebab-case, and dot.notation to camelCase
        $parts = preg_split('/[_\-.]+/', $key);
        if (false === $parts) {
            return $key;
        }

        $result = strtolower($parts[0] ?? '');
        for ($i = 1, $iMax = count($parts); $i < $iMax; ++$i) {
            $result .= ucfirst(strtolower($parts[$i]));
        }

        return $result;
    }

    public function toKebabCase(string $key): string
    {
        // Convert camelCase/PascalCase to kebab-case
        $result = preg_replace('/([a-z])([A-Z])/', '$1-$2', $key);
        // Convert snake_case and dot.notation to kebab-case
        $result = str_replace(['_', '.'], '-', $result ?? $key);

        return strtolower($result);
    }

    public function toPascalCase(string $key): string
    {
        // Handle camelCase/PascalCase first
        if (preg_match('/[A-Z]/', $key)) {
            // Already in PascalCase or camelCase, just ensure first letter is uppercase
            return ucfirst($key);
        }

        // Convert snake_case, kebab-case, and dot.notation to PascalCase
        $parts = preg_split('/[_\-.]+/', $key);
        if (false === $parts) {
            return ucfirst($key);
        }

        return implode('', array_map(ucfirst(...), array_map(strtolower(...), $parts)));
    }

    public function toDotNotation(string $key): string
    {
        // Convert camelCase/PascalCase to dot.notation
        $result = preg_replace('/([a-z])([A-Z])/', '$1.$2', $key);
        // Convert snake_case and kebab-case to dot.notation
        $result = str_replace(['_', '-'], '.', $result ?? $key);

        return strtolower($result);
    }

    public function convertDotSeparatedKey(string $key, ?KeyNamingConvention $convention): string
    {
        if (null === $convention) {
            return $key;
        }

        $segments = explode('.', $key);
        $convertedSegments = [];

        foreach ($segments as $segment) {
            $convertedSegments[] = match ($convention) {
                KeyNamingConvention::SNAKE_CASE => $this->toSnakeCase($segment),
                KeyNamingConvention::CAMEL_CASE => $this->toCamelCase($segment),
                KeyNamingConvention::KEBAB_CASE => $this->toKebabCase($segment),
                KeyNamingConvention::PASCAL_CASE => $this->toPascalCase($segment),
                KeyNamingConvention::DOT_NOTATION => $this->toDotNotation($segment),
            };
        }

        return implode('.', $convertedSegments);
    }

    /**
     * Convert a key to match a target convention.
     */
    public function convertKey(string $key, KeyNamingConvention $convention): string
    {
        if (str_contains($key, '.')) {
            return $this->convertDotSeparatedKey($key, $convention);
        }

        return match ($convention) {
            KeyNamingConvention::SNAKE_CASE => $this->toSnakeCase($key),
            KeyNamingConvention::CAMEL_CASE => $this->toCamelCase($key),
            KeyNamingConvention::KEBAB_CASE => $this->toKebabCase($key),
            KeyNamingConvention::PASCAL_CASE => $this->toPascalCase($key),
            KeyNamingConvention::DOT_NOTATION => $this->toDotNotation($key),
        };
    }
}
