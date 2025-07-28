<?php

declare(strict_types=1);

/*
 * This file is part of the Composer plugin "composer-translation-validator".
 *
 * Copyright (C) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace MoveElevator\ComposerTranslationValidator\Enum;

use InvalidArgumentException;

enum KeyNamingConvention: string
{
    case SNAKE_CASE = 'snake_case';
    case CAMEL_CASE = 'camelCase';
    case KEBAB_CASE = 'kebab-case';
    case PASCAL_CASE = 'PascalCase';
    case DOT_NOTATION = 'dot.notation';

    public function getPattern(): string
    {
        return match ($this) {
            self::SNAKE_CASE => '/^[a-z]([a-z0-9]|_[a-z0-9])*$/',
            self::CAMEL_CASE => '/^[a-z][a-zA-Z0-9]*$/',
            self::KEBAB_CASE => '/^[a-z][a-z0-9-]*[a-z0-9]$|^[a-z]$/',
            self::PASCAL_CASE => '/^[A-Z][a-zA-Z0-9]*$/',
            self::DOT_NOTATION => '/^[a-z][a-z0-9]*(\.[a-z][a-z0-9]*)*$/',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::SNAKE_CASE => 'snake_case (lowercase with underscores)',
            self::CAMEL_CASE => 'camelCase (first letter lowercase)',
            self::KEBAB_CASE => 'kebab-case (lowercase with hyphens)',
            self::PASCAL_CASE => 'PascalCase (first letter uppercase)',
            self::DOT_NOTATION => 'dot.notation (lowercase with dots)',
        };
    }

    /**
     * Create enum instance from string value.
     *
     * @throws InvalidArgumentException if convention is not supported
     */
    public static function fromString(string $convention): self
    {
        return self::tryFrom($convention) ?? throw new InvalidArgumentException(sprintf('Unknown convention "%s". Available conventions: %s', $convention, implode(', ', self::getAvailableConventions())));
    }

    /**
     * Get all available convention names.
     *
     * @return array<string>
     */
    public static function getAvailableConventions(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }

    /**
     * Check if a key matches this convention.
     */
    public function matches(string $key): bool
    {
        return 1 === preg_match($this->getPattern(), $key);
    }
}
