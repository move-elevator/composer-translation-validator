<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Parser;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\ParserRegistry;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use PHPUnit\Framework\TestCase;

final class ParserRegistryTest extends TestCase
{
    public function testGetAvailableParsers(): void
    {
        $parsers = ParserRegistry::getAvailableParsers();

        $this->assertContains(XliffParser::class, $parsers);
        $this->assertContains(YamlParser::class, $parsers);
        $this->assertCount(2, $parsers);
    }

    public function testResolveParserClass(): void
    {
        $this->assertSame(XliffParser::class, ParserRegistry::resolveParserClass('test.xliff'));
        $this->assertSame(YamlParser::class, ParserRegistry::resolveParserClass('test.yaml'));
        $this->assertNull(ParserRegistry::resolveParserClass('unknown.txt'));
    }

    public function testResolveParserClassWithVariousExtensions(): void
    {
        // Test XLIFF extensions
        $this->assertSame(XliffParser::class, ParserRegistry::resolveParserClass('messages.xlf'));
        $this->assertSame(XliffParser::class, ParserRegistry::resolveParserClass('locallang.xliff'));

        // Test YAML extensions
        $this->assertSame(YamlParser::class, ParserRegistry::resolveParserClass('config.yml'));
        $this->assertSame(YamlParser::class, ParserRegistry::resolveParserClass('translations.yaml'));

        // Test unsupported extensions
        $this->assertNull(ParserRegistry::resolveParserClass('test.json'));
        $this->assertNull(ParserRegistry::resolveParserClass('data.xml'));
        $this->assertNull(ParserRegistry::resolveParserClass('config.ini'));
    }

    public function testResolveParserClassWithEmptyAndSpecialFilenames(): void
    {
        $this->assertNull(ParserRegistry::resolveParserClass(''));
        $this->assertSame(XliffParser::class, ParserRegistry::resolveParserClass('.xlf')); // This actually has the xlf extension
        $this->assertNull(ParserRegistry::resolveParserClass('file'));
        $this->assertNull(ParserRegistry::resolveParserClass('file.'));
    }

    public function testGetAvailableParsersReturnsValidClasses(): void
    {
        $parsers = ParserRegistry::getAvailableParsers();

        foreach ($parsers as $parser) {
            $this->assertTrue(class_exists($parser), "Class {$parser} should exist");
            $this->assertContains(
                ParserInterface::class,
                class_implements($parser) ?: [],
                "Class {$parser} should implement ParserInterface"
            );
        }
    }

    public function testGetAvailableParsersConsistency(): void
    {
        $parsers1 = ParserRegistry::getAvailableParsers();
        $parsers2 = ParserRegistry::getAvailableParsers();

        $this->assertSame($parsers1, $parsers2);
    }
}
