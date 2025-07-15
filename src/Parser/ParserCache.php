<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Parser;

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
