<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Parser;

use MoveElevator\ComposerTranslationValidator\Parser\ParserUtility;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use PHPUnit\Framework\TestCase;

final class ParserUtilityTest extends TestCase
{
    public function testResolveAllowedFileExtensions(): void
    {
        $extensions = ParserUtility::resolveAllowedFileExtensions();

        $this->assertContains('xliff', $extensions);
        $this->assertContains('xlf', $extensions);
        $this->assertCount(2, array_unique($extensions));
    }

    public function testResolveParserClasses(): void
    {
        $parserClasses = ParserUtility::resolveParserClasses();

        $this->assertContains(XliffParser::class, $parserClasses);
        $this->assertCount(1, $parserClasses);
    }

    public function testResolveParserClassWithSupportedExtension(): void
    {
        $filePath = '/path/to/file.xlf';
        $parserClass = ParserUtility::resolveParserClass($filePath);

        $this->assertSame(XliffParser::class, $parserClass);

        $filePath = '/path/to/another.xliff';
        $parserClass = ParserUtility::resolveParserClass($filePath);

        $this->assertSame(XliffParser::class, $parserClass);
    }

    public function testResolveParserClassWithUnsupportedExtension(): void
    {
        $filePath = '/path/to/file.txt';
        $parserClass = ParserUtility::resolveParserClass($filePath);

        $this->assertNull($parserClass);
    }
}
