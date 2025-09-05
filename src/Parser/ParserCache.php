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

namespace MoveElevator\ComposerTranslationValidator\Parser;

/**
 * ParserCache.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
class ParserCache
{
    /** @var array<string, ParserInterface> */
    private static array $cache = [];

    public static function get(string $filePath, ?string $parserClass): ParserInterface|false
    {
        if (null === $parserClass) {
            return false;
        }

        $cacheKey = $filePath.'::'.$parserClass;

        if (!isset(self::$cache[$cacheKey])) {
            /** @var ParserInterface $parser */
            $parser = new $parserClass($filePath);
            self::$cache[$cacheKey] = $parser;
        }

        return self::$cache[$cacheKey];
    }

    public static function clear(): void
    {
        self::$cache = [];
    }

    /** @return array<string, mixed> */
    public static function getCacheStats(): array
    {
        return [
            'cached_parsers' => count(self::$cache),
            'cache_keys' => array_keys(self::$cache),
        ];
    }
}
