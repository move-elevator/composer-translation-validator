<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Parser;

use MoveElevator\ComposerTranslationValidator\Parser\ParserCache;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ParserCache::class)]
class ParserCacheTest extends TestCase
{
    private string $testFile;

    protected function setUp(): void
    {
        // Clear cache before each test
        ParserCache::clear();

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
        ParserCache::clear();
    }

    public function testGetCreatesNewParserInstance(): void
    {
        $parser = ParserCache::get($this->testFile, YamlParser::class);

        $this->assertInstanceOf(YamlParser::class, $parser);
        $this->assertSame($this->testFile, $parser->getFilePath());
    }

    public function testGetReturnsSameInstanceOnSecondCall(): void
    {
        $parser1 = ParserCache::get($this->testFile, YamlParser::class);
        $parser2 = ParserCache::get($this->testFile, YamlParser::class);

        $this->assertSame($parser1, $parser2);
    }

    public function testGetCreatesDifferentInstancesForDifferentFiles(): void
    {
        $testFile2 = tempnam(sys_get_temp_dir(), 'test2_').'.yaml';
        file_put_contents($testFile2, "key3: value3\n");

        try {
            $parser1 = ParserCache::get($this->testFile, YamlParser::class);
            $parser2 = ParserCache::get($testFile2, YamlParser::class);

            $this->assertNotSame($parser1, $parser2);
            $this->assertSame($this->testFile, $parser1->getFilePath());
            $this->assertSame($testFile2, $parser2->getFilePath());
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
            $yamlParser = ParserCache::get($this->testFile, YamlParser::class);
            $xliffParser = ParserCache::get($xliffFile, XliffParser::class);

            $this->assertNotSame($yamlParser, $xliffParser);
            $this->assertInstanceOf(YamlParser::class, $yamlParser);
            $this->assertInstanceOf(XliffParser::class, $xliffParser);
        } finally {
            if (file_exists($xliffFile)) {
                unlink($xliffFile);
            }
        }
    }

    public function testClearEmptiesCache(): void
    {
        // Add something to cache
        ParserCache::get($this->testFile, YamlParser::class);

        $statsBefore = ParserCache::getCacheStats();
        $this->assertSame(1, $statsBefore['cached_parsers']);

        ParserCache::clear();

        $statsAfter = ParserCache::getCacheStats();
        $this->assertSame(0, $statsAfter['cached_parsers']);
        $this->assertEmpty($statsAfter['cache_keys']);
    }

    public function testGetCacheStatsReturnsCorrectData(): void
    {
        $stats = ParserCache::getCacheStats();
        $this->assertArrayHasKey('cached_parsers', $stats);
        $this->assertArrayHasKey('cache_keys', $stats);
        $this->assertSame(0, $stats['cached_parsers']);
        $this->assertEmpty($stats['cache_keys']);

        // Add parser to cache
        ParserCache::get($this->testFile, YamlParser::class);

        $statsAfter = ParserCache::getCacheStats();
        $this->assertSame(1, $statsAfter['cached_parsers']);
        $this->assertCount(1, $statsAfter['cache_keys']);
        $this->assertStringContainsString($this->testFile, (string) $statsAfter['cache_keys'][0]);
        $this->assertStringContainsString(YamlParser::class, (string) $statsAfter['cache_keys'][0]);
    }

    public function testCacheKeyFormat(): void
    {
        ParserCache::get($this->testFile, YamlParser::class);

        $stats = ParserCache::getCacheStats();
        $expectedKey = $this->testFile.'::'.YamlParser::class;

        $this->assertContains($expectedKey, $stats['cache_keys']);
    }

    public function testGetWithNullParserClassReturnsFalse(): void
    {
        $result = ParserCache::get($this->testFile, null);
        $this->assertFalse($result);
    }
}
