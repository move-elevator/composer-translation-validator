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

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use Iterator;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Validator\EmptyValuesValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * EmptyValuesValidatorTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class EmptyValuesValidatorTest extends TestCase
{
    public function testProcessFileWithEmptyValues(): void
    {
        $parser = $this->createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2', 'key3']);
        $parser->method('getContentByKey')->willReturnMap([
            ['key1', 'Valid content'],
            ['key2', ''],
            ['key3', '   '],
        ]);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new EmptyValuesValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertSame(['key2' => '', 'key3' => '   '], $result);
    }

    public function testProcessFileWithoutEmptyValues(): void
    {
        $parser = $this->createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2', 'key3']);
        $parser->method('getContentByKey')->willReturnMap([
            ['key1', 'Valid content'],
            ['key2', 'Another valid content'],
            ['key3', 'Third valid content'],
        ]);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new EmptyValuesValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testProcessFileWithNullValues(): void
    {
        $parser = $this->createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2']);
        $parser->method('getContentByKey')->willReturnMap([
            ['key1', 'Valid content'],
            ['key2', null],
        ]);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new EmptyValuesValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertSame(['key2' => ''], $result);
    }

    public function testProcessFileWithMixedWhitespaceValues(): void
    {
        $parser = $this->createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2', 'key3', 'key4']);
        $parser->method('getContentByKey')->willReturnMap([
            ['key1', 'Valid content'],
            ['key2', ''],
            ['key3', ' '],
            ['key4', "\t\n "],
        ]);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new EmptyValuesValidator($logger);
        $result = $validator->processFile($parser);

        $expected = [
            'key2' => '',
            'key3' => ' ',
            'key4' => "\t\n ",
        ];
        $this->assertSame($expected, $result);
    }

    public function testProcessFileWithInvalidFile(): void
    {
        $parser = $this->createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(null);
        $parser->method('getFileName')->willReturn('invalid.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('The source file invalid.xlf is not valid.'));

        $validator = new EmptyValuesValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    /**
     * @param array<string, string> $details
     * @param array<string>         $expectedFragments
     */
    #[DataProvider('formatIssueMessageProvider')]
    public function testFormatIssueMessage(array $details, array $expectedFragments): void
    {
        $logger = $this->createStub(LoggerInterface::class);
        $validator = new EmptyValuesValidator($logger);

        $issue = new Issue(
            'test.xlf',
            $details,
            'XliffParser',
            'EmptyValuesValidator',
        );

        $result = $validator->formatIssueMessage($issue);

        $this->assertStringContainsString('Warning', $result);
        $this->assertStringContainsString('<fg=yellow>', $result);
        foreach ($expectedFragments as $fragment) {
            $this->assertStringContainsString($fragment, $result);
        }
    }

    /**
     * @return Iterator<string, array{array<string, string>, array<string>}>
     */
    public static function formatIssueMessageProvider(): Iterator
    {
        yield 'empty value' => [
            ['empty_key' => ''],
            ['empty_key', 'empty value'],
        ];

        yield 'whitespace only value' => [
            ['whitespace_key' => '   '],
            ['whitespace_key', 'whitespace only value'],
        ];

        yield 'multiple empty values' => [
            ['empty_key' => '', 'whitespace_key' => '  '],
            ['empty_key', 'whitespace_key', 'empty value', 'whitespace only value'],
        ];
    }

    public function testProcessFileWithEmptyKeysArray(): void
    {
        $parser = $this->createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn([]);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new EmptyValuesValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testProcessFileWithPartiallyEmptyValues(): void
    {
        $parser = $this->createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2', 'key3', 'key4', 'key5']);
        $parser->method('getContentByKey')->willReturnMap([
            ['key1', 'Valid content'],
            ['key2', ''],
            ['key3', 'Another valid content'],
            ['key4', '   '],
            ['key5', 'Final valid content'],
        ]);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new EmptyValuesValidator($logger);
        $result = $validator->processFile($parser);

        $expected = [
            'key2' => '',
            'key4' => '   ',
        ];
        $this->assertSame($expected, $result);
    }
}
