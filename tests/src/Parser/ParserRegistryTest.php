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

namespace MoveElevator\ComposerTranslationValidator\Tests\Parser;

use MoveElevator\ComposerTranslationValidator\Parser\JsonParser;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\ParserRegistry;
use MoveElevator\ComposerTranslationValidator\Parser\PhpParser;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use PHPUnit\Framework\TestCase;

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 *
 * @package ComposerTranslationValidator
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
