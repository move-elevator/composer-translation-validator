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

namespace MoveElevator\ComposerTranslationValidator\Tests\Parser;

use MoveElevator\ComposerTranslationValidator\Parser\{JsonParser, ParserInterface, ParserRegistry, PhpParser, XliffParser, YamlParser};
use PHPUnit\Framework\TestCase;

/**
 * ParserRegistryTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class ParserRegistryTest extends TestCase
{
    public function testGetAvailableParsers(): void
    {
        $parsers = ParserRegistry::getAvailableParsers();

        $this->assertContains(XliffParser::class, $parsers);
        $this->assertContains(YamlParser::class, $parsers);
        $this->assertContains(JsonParser::class, $parsers);
        $this->assertContains(PhpParser::class, $parsers);
        $this->assertCount(4, $parsers);
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

        // Test JSON extensions
        $this->assertSame(JsonParser::class, ParserRegistry::resolveParserClass('test.json'));

        // Test PHP extensions
        $this->assertSame(PhpParser::class, ParserRegistry::resolveParserClass('messages.php'));

        // Test unsupported extensions
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
                "Class {$parser} should implement ParserInterface",
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
