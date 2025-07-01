<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Parser;

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
}
