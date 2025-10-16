<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Enum;

use InvalidArgumentException;

use function sprintf;

/**
 * KeyNamingConvention.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
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
        // Check if it's a valid enum value
        $enumValue = self::tryFrom($convention);

        if (null === $enumValue) {
            throw new InvalidArgumentException(sprintf('Unknown convention "%s". Available conventions: %s', $convention, implode(', ', self::getConfigurableConventions())));
        }

        // Reject dot.notation as it's not configurable
        if (self::DOT_NOTATION === $enumValue) {
            throw new InvalidArgumentException(sprintf('Convention "%s" is not configurable. Available conventions: %s', $convention, implode(', ', self::getConfigurableConventions())));
        }

        return $enumValue;
    }

    /**
     * Get all available convention names.
     *
     * @return array<string>
     */
    public static function getAvailableConventions(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    /**
     * Get configurable convention names (excludes dot.notation).
     * dot.notation is used internally for detection but should not be configured explicitly.
     *
     * @return array<string>
     */
    public static function getConfigurableConventions(): array
    {
        return array_filter(
            self::getAvailableConventions(),
            static fn (string $convention): bool => $convention !== self::DOT_NOTATION->value,
        );
    }

    /**
     * Check if a key matches this convention.
     */
    public function matches(string $key): bool
    {
        return 1 === preg_match($this->getPattern(), $key);
    }
}
