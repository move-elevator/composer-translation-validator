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

namespace MoveElevator\ComposerTranslationValidator\Tests\Parser;

use MoveElevator\ComposerTranslationValidator\Parser\{ParserCache, XliffParser, YamlParser};
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ParserCache::class)]
/**
 * ParserCacheTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class ParserCacheTest extends TestCase
{
    private string $testFile;

    private ParserCache $parserCache;

    protected function setUp(): void
    {
        $this->parserCache = new ParserCache();

        // Create a test YAML file
        $this->testFile = tempnam(sys_get_temp_dir(), 'test_').'.yaml';
        file_put_contents($this->testFile, "key1: value1\nkey2: value2\n");
    }

    protected function tearDown(): void
    {
        // Clean up
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
    }

    public function testGetCreatesNewParserInstance(): void
    {
        $parser = $this->parserCache->get($this->testFile, YamlParser::class);
        self::assertNotFalse($parser);

        self::assertInstanceOf(YamlParser::class, $parser);
        self::assertSame($this->testFile, $parser->getFilePath());
    }

    public function testGetReturnsSameInstanceOnSecondCall(): void
    {
        $parser1 = $this->parserCache->get($this->testFile, YamlParser::class);
        $parser2 = $this->parserCache->get($this->testFile, YamlParser::class);

        self::assertSame($parser1, $parser2);
    }

    public function testGetCreatesDifferentInstancesForDifferentFiles(): void
    {
        $testFile2 = tempnam(sys_get_temp_dir(), 'test2_').'.yaml';
        file_put_contents($testFile2, "key3: value3\n");

        try {
            $parser1 = $this->parserCache->get($this->testFile, YamlParser::class);
            $parser2 = $this->parserCache->get($testFile2, YamlParser::class);
            self::assertNotFalse($parser1);
            self::assertNotFalse($parser2);

            self::assertNotSame($parser1, $parser2);
            self::assertSame($this->testFile, $parser1->getFilePath());
            self::assertSame($testFile2, $parser2->getFilePath());
        } finally {
            if (file_exists($testFile2)) {
                unlink($testFile2);
            }
        }
    }

    public function testGetCreatesDifferentInstancesForDifferentParserClasses(): void
    {
        // Create an XLIFF file for testing
        $xliffFile = tempnam(sys_get_temp_dir(), 'test_').'.xlf';
        file_put_contents($xliffFile, '<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2">
    <file source-language="en" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="test.key">
                <source>Test</source>
                <target>Test</target>
            </trans-unit>
        </body>
    </file>
</xliff>');

        try {
            $yamlParser = $this->parserCache->get($this->testFile, YamlParser::class);
            $xliffParser = $this->parserCache->get($xliffFile, XliffParser::class);

            self::assertNotSame($yamlParser, $xliffParser);
            self::assertInstanceOf(YamlParser::class, $yamlParser);
            self::assertInstanceOf(XliffParser::class, $xliffParser);
        } finally {
            if (file_exists($xliffFile)) {
                unlink($xliffFile);
            }
        }
    }

    public function testClearEmptiesCache(): void
    {
        // Add something to cache
        $this->parserCache->get($this->testFile, YamlParser::class);

        $statsBefore = $this->parserCache->getCacheStats();
        self::assertSame(1, $statsBefore['cached_parsers']);

        $this->parserCache->clear();

        $statsAfter = $this->parserCache->getCacheStats();
        self::assertSame(0, $statsAfter['cached_parsers']);
        self::assertEmpty($statsAfter['cache_keys']);
    }

    public function testGetCacheStatsReturnsCorrectData(): void
    {
        $stats = $this->parserCache->getCacheStats();
        self::assertArrayHasKey('cached_parsers', $stats);
        self::assertArrayHasKey('cache_keys', $stats);
        self::assertSame(0, $stats['cached_parsers']);
        self::assertEmpty($stats['cache_keys']);

        // Add parser to cache
        $this->parserCache->get($this->testFile, YamlParser::class);

        $statsAfter = $this->parserCache->getCacheStats();
        self::assertSame(1, $statsAfter['cached_parsers']);
        self::assertCount(1, $statsAfter['cache_keys']);
        self::assertStringContainsString($this->testFile, (string) $statsAfter['cache_keys'][0]);
        self::assertStringContainsString(YamlParser::class, (string) $statsAfter['cache_keys'][0]);
    }

    public function testCacheKeyFormat(): void
    {
        $this->parserCache->get($this->testFile, YamlParser::class);

        $stats = $this->parserCache->getCacheStats();
        $expectedKey = $this->testFile.'::'.YamlParser::class;

        self::assertContains($expectedKey, $stats['cache_keys']);
    }

    public function testGetWithNullParserClassReturnsFalse(): void
    {
        $result = $this->parserCache->get($this->testFile, null);
        self::assertFalse($result);
    }

    public function testSeparateInstancesHaveIndependentCaches(): void
    {
        $cache1 = new ParserCache();
        $cache2 = new ParserCache();

        $cache1->get($this->testFile, YamlParser::class);

        self::assertSame(1, $cache1->getCacheStats()['cached_parsers']);
        self::assertSame(0, $cache2->getCacheStats()['cached_parsers']);
    }
}
