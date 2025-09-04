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

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Validator\DuplicateKeysValidator;
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

final class DuplicateKeysValidatorTest extends TestCase
{
    public function testProcessFileWithDuplicates(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2', 'key1', 'key3', 'key2']);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new DuplicateKeysValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertSame(['key1' => 2, 'key2' => 2], $result);
    }

    public function testProcessFileWithoutDuplicates(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2', 'key3']);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new DuplicateKeysValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
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

        $validator = new DuplicateKeysValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testSupportsParser(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new DuplicateKeysValidator($logger);

        $expectedParsers = [
            \MoveElevator\ComposerTranslationValidator\Parser\XliffParser::class,
            \MoveElevator\ComposerTranslationValidator\Parser\YamlParser::class,
            \MoveElevator\ComposerTranslationValidator\Parser\JsonParser::class,
            \MoveElevator\ComposerTranslationValidator\Parser\PhpParser::class,
        ];
        $this->assertSame($expectedParsers, $validator->supportsParser());
    }

    public function testGetShortName(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new DuplicateKeysValidator($logger);

        $this->assertSame('DuplicateKeysValidator', $validator->getShortName());
    }

    public function testResultTypeOnValidationFailure(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new DuplicateKeysValidator($logger);

        $this->assertSame(\MoveElevator\ComposerTranslationValidator\Validator\ResultType::ERROR, $validator->resultTypeOnValidationFailure());
    }

    public function testFormatIssueMessage(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new DuplicateKeysValidator($logger);

        // DuplicateKeysValidator expects details to be key => count pairs
        $issue = new \MoveElevator\ComposerTranslationValidator\Result\Issue(
            'test.xlf',
            ['duplicate_key' => 3, 'another_key' => 2],
            'XliffParser',
            'DuplicateKeysValidator',
        );

        $result = $validator->formatIssueMessage($issue);

        $this->assertStringContainsString('Error', $result);
        $this->assertStringContainsString('<fg=red>', $result);
        $this->assertStringContainsString('duplicate_key', $result);
        $this->assertStringContainsString('3x', $result);
        $this->assertStringContainsString('another_key', $result);
        $this->assertStringContainsString('2x', $result);
    }
}
