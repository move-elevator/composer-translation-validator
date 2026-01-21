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

namespace MoveElevator\ComposerTranslationValidator\Parser;

use function count;

/**
 * ParserCache.
 *
 * @author Konrad Michalik <km@move-elevator.de>
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
