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
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use MoveElevator\ComposerTranslationValidator\Validator\DuplicateValuesValidator;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;

final class DuplicateValuesValidatorTest extends TestCase
{
    private LoggerInterface $loggerMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loggerMock = $this->createMock(LoggerInterface::class);
    }

    public function testProcessFileWithDuplicateValues(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2', 'key3']);
        $parser->method('getContentByKey')
            ->willReturnMap([
                ['key1', 'valueA'],
                ['key2', 'valueB'],
                ['key3', 'valueA'], // Duplicate value
            ]);
        $parser->method('getFileName')->willReturn('test.xlf');

        $validator = new DuplicateValuesValidator($this->loggerMock);
        $validator->processFile($parser);

        // Access protected property to check internal state
        $reflection = new ReflectionClass($validator);
        $valuesArrayProperty = $reflection->getProperty('valuesArray');
        $valuesArrayProperty->setAccessible(true);
        $valuesArray = $valuesArrayProperty->getValue($validator);

        $this->assertArrayHasKey('test.xlf', $valuesArray);
        $this->assertArrayHasKey('valueA', $valuesArray['test.xlf']);
        $this->assertSame(['key1', 'key3'], $valuesArray['test.xlf']['valueA']);
    }

    public function testProcessFileWithoutDuplicateValues(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2', 'key3']);
        $parser->method('getContentByKey')
            ->willReturnMap([
                ['key1', 'valueA'],
                ['key2', 'valueB'],
                ['key3', 'valueC'],
            ]);
        $parser->method('getFileName')->willReturn('test.xlf');

        $validator = new DuplicateValuesValidator($this->loggerMock);
        $validator->processFile($parser);

        $reflection = new ReflectionClass($validator);
        $valuesArrayProperty = $reflection->getProperty('valuesArray');
        $valuesArrayProperty->setAccessible(true);
        $valuesArray = $valuesArrayProperty->getValue($validator);

        $this->assertArrayHasKey('test.xlf', $valuesArray);
        $this->assertArrayHasKey('valueA', $valuesArray['test.xlf']);
        $this->assertArrayHasKey('valueB', $valuesArray['test.xlf']);
        $this->assertArrayHasKey('valueC', $valuesArray['test.xlf']);
        $this->assertSame(['key1'], $valuesArray['test.xlf']['valueA']);
        $this->assertSame(['key2'], $valuesArray['test.xlf']['valueB']);
        $this->assertSame(['key3'], $valuesArray['test.xlf']['valueC']);
    }

    public function testProcessFileWithInvalidFile(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(null);
        $parser->method('getFileName')->willReturn('invalid.xlf');

        /** @var MockObject&LoggerInterface $loggerMock */
        $loggerMock = $this->loggerMock;
        $loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('The source file invalid.xlf is not valid.'));

        $validator = new DuplicateValuesValidator($this->loggerMock);
        $validator->processFile($parser);

        $reflection = new ReflectionClass($validator);
        $valuesArrayProperty = $reflection->getProperty('valuesArray');
        $valuesArrayProperty->setAccessible(true);
        $valuesArray = $valuesArrayProperty->getValue($validator);

        $this->assertEmpty($valuesArray);
    }

    public function testPostProcessWithDuplicateValues(): void
    {
        $validator = new DuplicateValuesValidator($this->loggerMock);

        // Manually set valuesArray to simulate previous processFile calls
        $reflection = new ReflectionClass($validator);
        $valuesArrayProperty = $reflection->getProperty('valuesArray');
        $valuesArrayProperty->setAccessible(true);
        $valuesArrayProperty->setValue($validator, [
            'file1.xlf' => [
                'valueA' => ['key1', 'key3'],
                'valueB' => ['key2'],
            ],
            'file2.xlf' => [
                'valueX' => ['keyA', 'keyB'],
            ],
        ]);

        $validator->postProcess();

        $issuesProperty = $reflection->getProperty('issues');
        $issuesProperty->setAccessible(true);
        $issues = $issuesProperty->getValue($validator);

        $expectedIssues = [
            [
                'file' => 'file1.xlf',
                'issues' => [
                    'valueA' => ['key1', 'key3'],
                ],
                'parser' => '',
                'type' => 'DuplicateValuesValidator',
            ],
            [
                'file' => 'file2.xlf',
                'issues' => [
                    'valueX' => ['keyA', 'keyB'],
                ],
                'parser' => '',
                'type' => 'DuplicateValuesValidator',
            ],
        ];

        $this->assertSame($expectedIssues, array_map(fn ($issue) => $issue->toArray(), $issues));
    }

    public function testPostProcessWithoutDuplicateValues(): void
    {
        $validator = new DuplicateValuesValidator($this->loggerMock);

        // Manually set valuesArray with no duplicates
        $reflection = new ReflectionClass($validator);
        $valuesArrayProperty = $reflection->getProperty('valuesArray');
        $valuesArrayProperty->setAccessible(true);
        $valuesArrayProperty->setValue($validator, [
            'file1.xlf' => [
                'valueA' => ['key1'],
                'valueB' => ['key2'],
            ],
        ]);

        $validator->postProcess();

        $issuesProperty = $reflection->getProperty('issues');
        $issuesProperty->setAccessible(true);
        $issues = $issuesProperty->getValue($validator);

        $this->assertEmpty($issues);
    }

    public function testResetStateResetsValuesArray(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new DuplicateValuesValidator($logger);

        // Manually set valuesArray to simulate previous validation
        $reflection = new ReflectionClass($validator);
        $valuesArrayProperty = $reflection->getProperty('valuesArray');
        $valuesArrayProperty->setAccessible(true);
        $valuesArrayProperty->setValue($validator, [
            'file1.xlf' => [
                'value1' => ['key1', 'key2'],
                'value2' => ['key3'],
            ],
            'file2.xlf' => [
                'value1' => ['keyA'],
            ],
        ]);

        // Verify valuesArray is set
        $this->assertNotEmpty($valuesArrayProperty->getValue($validator));

        // Call resetState
        $resetStateMethod = $reflection->getMethod('resetState');
        $resetStateMethod->setAccessible(true);
        $resetStateMethod->invoke($validator);

        // Verify valuesArray is reset
        $this->assertSame([], $valuesArrayProperty->getValue($validator));

        // Verify issues are also reset (from parent)
        $this->assertFalse($validator->hasIssues());
    }

    public function testSupportsParser(): void
    {
        $validator = new DuplicateValuesValidator($this->loggerMock);
        $supportedParsers = $validator->supportsParser();

        $this->assertContains(XliffParser::class, $supportedParsers);
        $this->assertContains(YamlParser::class, $supportedParsers);
    }

    public function testResultTypeOnValidationFailure(): void
    {
        $validator = new DuplicateValuesValidator($this->loggerMock);
        $this->assertSame(ResultType::WARNING, $validator->resultTypeOnValidationFailure());
    }

    public function testGetShortName(): void
    {
        $validator = new DuplicateValuesValidator($this->loggerMock);
        $this->assertSame('DuplicateValuesValidator', $validator->getShortName());
    }

    public function testFormatIssueMessage(): void
    {
        $validator = new DuplicateValuesValidator($this->loggerMock);

        // DuplicateValuesValidator expects details to be value => keys array pairs
        $issue = new \MoveElevator\ComposerTranslationValidator\Result\Issue(
            'test.xlf',
            ['duplicate_value' => ['key1', 'key2'], 'another_value' => ['key3', 'key4']],
            'XliffParser',
            'DuplicateValuesValidator',
        );

        $result = $validator->formatIssueMessage($issue);

        $this->assertStringContainsString('Warning', $result);
        $this->assertStringContainsString('<fg=yellow>', $result);
        $this->assertStringContainsString('duplicate_value', $result);
        $this->assertStringContainsString('key1`, `key2', $result);
        $this->assertStringContainsString('another_value', $result);
        $this->assertStringContainsString('key3`, `key4', $result);
    }

    public function testProcessFileWithNullValues(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2', 'key3']);
        $parser->method('getContentByKey')
            ->willReturnMap([
                ['key1', 'valueA'],
                ['key2', null], // This should be skipped
                ['key3', 'valueB'],
            ]);
        $parser->method('getFileName')->willReturn('test.xlf');

        $validator = new DuplicateValuesValidator($this->loggerMock);
        $validator->processFile($parser);

        // Access protected property to check internal state
        $reflection = new ReflectionClass($validator);
        $valuesArrayProperty = $reflection->getProperty('valuesArray');
        $valuesArrayProperty->setAccessible(true);
        $valuesArray = $valuesArrayProperty->getValue($validator);

        $this->assertArrayHasKey('test.xlf', $valuesArray);
        $this->assertArrayHasKey('valueA', $valuesArray['test.xlf']);
        $this->assertArrayHasKey('valueB', $valuesArray['test.xlf']);
        $this->assertArrayNotHasKey('null', $valuesArray['test.xlf']); // null values should be skipped
        $this->assertSame(['key1'], $valuesArray['test.xlf']['valueA']);
        $this->assertSame(['key3'], $valuesArray['test.xlf']['valueB']);
    }
}
