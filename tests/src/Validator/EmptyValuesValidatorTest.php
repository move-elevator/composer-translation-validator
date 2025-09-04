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

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\JsonParser;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\PhpParser;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Validator\EmptyValuesValidator;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 *
 * @package ComposerTranslationValidator
 */

final class EmptyValuesValidatorTest extends TestCase
{
    public function testProcessFileWithEmptyValues(): void
    {
        $parser = $this->createMock(ParserInterface::class);
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
        $parser = $this->createMock(ParserInterface::class);
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
        $parser = $this->createMock(ParserInterface::class);
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
        $parser = $this->createMock(ParserInterface::class);
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
        $parser = $this->createMock(ParserInterface::class);
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

    public function testSupportsParser(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new EmptyValuesValidator($logger);

        $expectedParsers = [XliffParser::class, YamlParser::class, JsonParser::class, PhpParser::class];
        $this->assertSame($expectedParsers, $validator->supportsParser());
    }

    public function testGetShortName(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new EmptyValuesValidator($logger);

        $this->assertSame('EmptyValuesValidator', $validator->getShortName());
    }

    public function testResultTypeOnValidationFailure(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new EmptyValuesValidator($logger);

        $this->assertSame(ResultType::WARNING, $validator->resultTypeOnValidationFailure());
    }

    public function testFormatIssueMessageWithEmptyValue(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new EmptyValuesValidator($logger);

        $issue = new Issue(
            'test.xlf',
            ['empty_key' => ''],
            'XliffParser',
            'EmptyValuesValidator',
        );

        $result = $validator->formatIssueMessage($issue);

        $this->assertStringContainsString('Warning', $result);
        $this->assertStringContainsString('<fg=yellow>', $result);
        $this->assertStringContainsString('empty_key', $result);
        $this->assertStringContainsString('empty value', $result);
    }

    public function testFormatIssueMessageWithWhitespaceValue(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new EmptyValuesValidator($logger);

        $issue = new Issue(
            'test.xlf',
            ['whitespace_key' => '   '],
            'XliffParser',
            'EmptyValuesValidator',
        );

        $result = $validator->formatIssueMessage($issue);

        $this->assertStringContainsString('Warning', $result);
        $this->assertStringContainsString('<fg=yellow>', $result);
        $this->assertStringContainsString('whitespace_key', $result);
        $this->assertStringContainsString('whitespace only value', $result);
    }

    public function testFormatIssueMessageWithMultipleEmptyValues(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new EmptyValuesValidator($logger);

        $issue = new Issue(
            'test.xlf',
            ['empty_key' => '', 'whitespace_key' => '  '],
            'XliffParser',
            'EmptyValuesValidator',
        );

        $result = $validator->formatIssueMessage($issue);

        $this->assertStringContainsString('empty_key', $result);
        $this->assertStringContainsString('whitespace_key', $result);
        $this->assertStringContainsString('empty value', $result);
        $this->assertStringContainsString('whitespace only value', $result);
    }

    public function testFormatIssueMessageWithPrefix(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new EmptyValuesValidator($logger);

        $issue = new Issue(
            'test.xlf',
            ['empty_key' => ''],
            'XliffParser',
            'EmptyValuesValidator',
        );

        $result = $validator->formatIssueMessage($issue, '(TestPrefix) ');

        $this->assertStringContainsString('(TestPrefix)', $result);
        $this->assertStringContainsString('empty_key', $result);
    }

    public function testProcessFileWithEmptyKeysArray(): void
    {
        $parser = $this->createMock(ParserInterface::class);
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
        $parser = $this->createMock(ParserInterface::class);
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
