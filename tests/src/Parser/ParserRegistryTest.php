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

        self::assertSame($parsers, ParserRegistry::getAvailableParsers());
        self::assertContains(XliffParser::class, $parsers);
        self::assertContains(YamlParser::class, $parsers);
        self::assertContains(JsonParser::class, $parsers);
        self::assertContains(PhpParser::class, $parsers);
        self::assertCount(4, $parsers);

        foreach ($parsers as $parser) {
            self::assertTrue(class_exists($parser), "Class {$parser} should exist");
            self::assertContains(
                ParserInterface::class,
                class_implements($parser) ?: [],
                "Class {$parser} should implement ParserInterface",
            );
        }
    }

    public function testResolveParserClassWithVariousExtensions(): void
    {
        self::assertSame(XliffParser::class, ParserRegistry::resolveParserClass('test.xliff'));
        self::assertSame(XliffParser::class, ParserRegistry::resolveParserClass('messages.xlf'));
        self::assertSame(XliffParser::class, ParserRegistry::resolveParserClass('locallang.xliff'));

        self::assertSame(YamlParser::class, ParserRegistry::resolveParserClass('test.yaml'));
        self::assertSame(YamlParser::class, ParserRegistry::resolveParserClass('config.yml'));
        self::assertSame(YamlParser::class, ParserRegistry::resolveParserClass('translations.yaml'));

        self::assertSame(JsonParser::class, ParserRegistry::resolveParserClass('test.json'));
        self::assertSame(PhpParser::class, ParserRegistry::resolveParserClass('messages.php'));

        self::assertNull(ParserRegistry::resolveParserClass('unknown.txt'));
        self::assertNull(ParserRegistry::resolveParserClass('data.xml'));
        self::assertNull(ParserRegistry::resolveParserClass('config.ini'));
    }

    public function testResolveParserClassWithEmptyAndSpecialFilenames(): void
    {
        self::assertNull(ParserRegistry::resolveParserClass(''));
        self::assertSame(XliffParser::class, ParserRegistry::resolveParserClass('.xlf')); // This actually has the xlf extension
        self::assertNull(ParserRegistry::resolveParserClass('file'));
        self::assertNull(ParserRegistry::resolveParserClass('file.'));
    }
}
