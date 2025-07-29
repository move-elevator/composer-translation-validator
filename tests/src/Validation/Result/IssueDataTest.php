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

namespace MoveElevator\ComposerTranslationValidator\Tests\Validation\Result;

use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Validation\Result\IssueData;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IssueData::class)]
class IssueDataTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $issueData = new IssueData(
            file: '/test/file.xlf',
            messages: ['Error message 1', 'Error message 2'],
            parser: 'XliffParser',
            validatorType: 'MismatchValidator',
            severity: ResultType::ERROR,
            context: ['key' => 'value'],
            line: 42,
            column: 15,
        );

        $this->assertSame('/test/file.xlf', $issueData->file);
        $this->assertSame(['Error message 1', 'Error message 2'], $issueData->messages);
        $this->assertSame('XliffParser', $issueData->parser);
        $this->assertSame('MismatchValidator', $issueData->validatorType);
        $this->assertSame(ResultType::ERROR, $issueData->severity);
        $this->assertSame(['key' => 'value'], $issueData->context);
        $this->assertSame(42, $issueData->line);
        $this->assertSame(15, $issueData->column);
    }

    public function testConstructorWithDefaults(): void
    {
        $issueData = new IssueData(
            file: '/test/file.xlf',
            messages: ['Error message'],
            parser: 'XliffParser',
            validatorType: 'MismatchValidator',
            severity: ResultType::ERROR,
        );

        $this->assertSame('/test/file.xlf', $issueData->file);
        $this->assertSame(['Error message'], $issueData->messages);
        $this->assertSame('XliffParser', $issueData->parser);
        $this->assertSame('MismatchValidator', $issueData->validatorType);
        $this->assertSame(ResultType::ERROR, $issueData->severity);
        $this->assertSame([], $issueData->context);
        $this->assertNull($issueData->line);
        $this->assertNull($issueData->column);
    }

    public function testFromIssueWithSimpleDetails(): void
    {
        $issue = new Issue(
            '/test/file.xlf',
            ['Error message 1', 'Error message 2'],
            'XliffParser',
            'MismatchValidator',
        );

        $issueData = IssueData::fromIssue($issue, ResultType::WARNING);

        $this->assertSame('/test/file.xlf', $issueData->file);
        $this->assertSame(['Error message 1', 'Error message 2'], $issueData->messages);
        $this->assertSame('XliffParser', $issueData->parser);
        $this->assertSame('MismatchValidator', $issueData->validatorType);
        $this->assertSame(ResultType::WARNING, $issueData->severity);
        $this->assertSame([], $issueData->context);
        $this->assertNull($issueData->line);
        $this->assertNull($issueData->column);
    }

    public function testFromIssueWithLocationAndContext(): void
    {
        $issue = new Issue(
            '/test/file.xlf',
            [
                'Error message',
                'line' => 42,
                'column' => 15,
                'key' => 'value',
                'contextData' => 'test',
            ],
            'XliffParser',
            'MismatchValidator',
        );

        $issueData = IssueData::fromIssue($issue);

        $this->assertSame('/test/file.xlf', $issueData->file);
        $this->assertSame(['Error message'], $issueData->messages);
        $this->assertSame('XliffParser', $issueData->parser);
        $this->assertSame('MismatchValidator', $issueData->validatorType);
        $this->assertSame(ResultType::ERROR, $issueData->severity); // Default
        $this->assertSame(['key' => 'value', 'contextData' => 'test'], $issueData->context);
        $this->assertSame(42, $issueData->line);
        $this->assertSame(15, $issueData->column);
    }

    public function testFromIssueWithNoMessages(): void
    {
        $issue = new Issue(
            '/test/file.xlf',
            ['line' => 42, 'key' => 'value'],
            'XliffParser',
            'MismatchValidator',
        );

        $issueData = IssueData::fromIssue($issue);

        // Should convert values to strings as messages
        $this->assertSame(['42', 'value'], $issueData->messages);
        $this->assertSame(['key' => 'value'], $issueData->context);
        $this->assertSame(42, $issueData->line);
    }

    public function testGetPrimaryMessage(): void
    {
        $issueData = new IssueData(
            file: '/test/file.xlf',
            messages: ['Primary message', 'Secondary message'],
            parser: 'XliffParser',
            validatorType: 'MismatchValidator',
            severity: ResultType::ERROR,
        );

        $this->assertSame('Primary message', $issueData->getPrimaryMessage());

        $emptyIssueData = new IssueData(
            file: '/test/file.xlf',
            messages: [],
            parser: 'XliffParser',
            validatorType: 'MismatchValidator',
            severity: ResultType::ERROR,
        );

        $this->assertSame('Unknown issue', $emptyIssueData->getPrimaryMessage());
    }

    public function testGetAllMessagesAsString(): void
    {
        $issueData = new IssueData(
            file: '/test/file.xlf',
            messages: ['Message 1', 'Message 2', 'Message 3'],
            parser: 'XliffParser',
            validatorType: 'MismatchValidator',
            severity: ResultType::ERROR,
        );

        $this->assertSame('Message 1 | Message 2 | Message 3', $issueData->getAllMessagesAsString());

        $singleMessageData = new IssueData(
            file: '/test/file.xlf',
            messages: ['Single message'],
            parser: 'XliffParser',
            validatorType: 'MismatchValidator',
            severity: ResultType::ERROR,
        );

        $this->assertSame('Single message', $singleMessageData->getAllMessagesAsString());
    }

    public function testHasLocation(): void
    {
        $withLocation = new IssueData(
            file: '/test/file.xlf',
            messages: ['Error'],
            parser: 'XliffParser',
            validatorType: 'MismatchValidator',
            severity: ResultType::ERROR,
            line: 42,
        );
        $this->assertTrue($withLocation->hasLocation());

        $withoutLocation = new IssueData(
            file: '/test/file.xlf',
            messages: ['Error'],
            parser: 'XliffParser',
            validatorType: 'MismatchValidator',
            severity: ResultType::ERROR,
        );
        $this->assertFalse($withoutLocation->hasLocation());
    }

    public function testGetLocationString(): void
    {
        $withLineAndColumn = new IssueData(
            file: '/test/file.xlf',
            messages: ['Error'],
            parser: 'XliffParser',
            validatorType: 'MismatchValidator',
            severity: ResultType::ERROR,
            line: 42,
            column: 15,
        );
        $this->assertSame('42:15', $withLineAndColumn->getLocationString());

        $withLineOnly = new IssueData(
            file: '/test/file.xlf',
            messages: ['Error'],
            parser: 'XliffParser',
            validatorType: 'MismatchValidator',
            severity: ResultType::ERROR,
            line: 42,
        );
        $this->assertSame('42', $withLineOnly->getLocationString());

        $withoutLocation = new IssueData(
            file: '/test/file.xlf',
            messages: ['Error'],
            parser: 'XliffParser',
            validatorType: 'MismatchValidator',
            severity: ResultType::ERROR,
        );
        $this->assertNull($withoutLocation->getLocationString());
    }

    public function testGetFormattedString(): void
    {
        $withLocation = new IssueData(
            file: '/test/file.xlf',
            messages: ['Error message'],
            parser: 'XliffParser',
            validatorType: 'MismatchValidator',
            severity: ResultType::ERROR,
            line: 42,
            column: 15,
        );
        $this->assertSame('/test/file.xlf:42:15:Error message', $withLocation->getFormattedString());

        $withoutLocation = new IssueData(
            file: '/test/file.xlf',
            messages: ['Error message'],
            parser: 'XliffParser',
            validatorType: 'MismatchValidator',
            severity: ResultType::ERROR,
        );
        $this->assertSame('/test/file.xlf:Error message', $withoutLocation->getFormattedString());
    }

    public function testToArray(): void
    {
        $issueData = new IssueData(
            file: '/test/file.xlf',
            messages: ['Error message 1', 'Error message 2'],
            parser: 'XliffParser',
            validatorType: 'MismatchValidator',
            severity: ResultType::WARNING,
            context: ['key' => 'value'],
            line: 42,
            column: 15,
        );

        $expected = [
            'file' => '/test/file.xlf',
            'messages' => ['Error message 1', 'Error message 2'],
            'parser' => 'XliffParser',
            'validatorType' => 'MismatchValidator',
            'severity' => 'warning',
            'context' => ['key' => 'value'],
            'line' => 42,
            'column' => 15,
        ];

        $this->assertSame($expected, $issueData->toArray());
    }

    public function testToLegacyIssue(): void
    {
        $issueData = new IssueData(
            file: '/test/file.xlf',
            messages: ['Error message 1', 'Error message 2'],
            parser: 'XliffParser',
            validatorType: 'MismatchValidator',
            severity: ResultType::ERROR,
            context: ['key' => 'value'],
            line: 42,
            column: 15,
        );

        $legacyIssue = $issueData->toLegacyIssue();

        $this->assertSame('/test/file.xlf', $legacyIssue->getFile());
        $this->assertSame('XliffParser', $legacyIssue->getParser());
        $this->assertSame('MismatchValidator', $legacyIssue->getValidatorType());

        $details = $legacyIssue->getDetails();
        $this->assertSame('Error message 1', $details[0]);
        $this->assertSame('Error message 2', $details[1]);
        $this->assertSame(42, $details['line']);
        $this->assertSame(15, $details['column']);
        $this->assertSame('value', $details['key']);
    }

    public function testRoundTripConversion(): void
    {
        $originalIssue = new Issue(
            '/test/file.xlf',
            ['Error message', 'line' => 42, 'key' => 'value'],
            'XliffParser',
            'MismatchValidator',
        );

        $issueData = IssueData::fromIssue($originalIssue, ResultType::WARNING);
        $convertedIssue = $issueData->toLegacyIssue();

        $this->assertSame($originalIssue->getFile(), $convertedIssue->getFile());
        $this->assertSame($originalIssue->getParser(), $convertedIssue->getParser());
        $this->assertSame($originalIssue->getValidatorType(), $convertedIssue->getValidatorType());
    }

    public function testFromIssueWithComplexDataTypes(): void
    {
        $issue = new Issue(
            '/test/file.xlf',
            [
                'Simple message',
                'line' => 42,
                'column' => 15,
                'boolean' => true,
                'array' => ['nested', 'data'],
                'null' => null,
                'number' => 123.45,
            ],
            'XliffParser',
            'MismatchValidator',
        );

        $issueData = IssueData::fromIssue($issue);

        $this->assertSame(['Simple message'], $issueData->messages);
        $this->assertSame(42, $issueData->line);
        $this->assertSame(15, $issueData->column);
        $this->assertArrayHasKey('boolean', $issueData->context);
        $this->assertArrayHasKey('array', $issueData->context);
        $this->assertArrayHasKey('null', $issueData->context);
        $this->assertArrayHasKey('number', $issueData->context);
        $this->assertTrue($issueData->context['boolean']);
        $this->assertSame(['nested', 'data'], $issueData->context['array']);
        $this->assertNull($issueData->context['null']);
        $this->assertEqualsWithDelta(123.45, $issueData->context['number'], PHP_FLOAT_EPSILON);
    }

    public function testFromIssueWithInvalidLineAndColumn(): void
    {
        $issue = new Issue(
            '/test/file.xlf',
            [
                'Message',
                'line' => 'not-a-number',
                'column' => 'also-not-a-number',
            ],
            'XliffParser',
            'MismatchValidator',
        );

        $issueData = IssueData::fromIssue($issue);

        $this->assertNull($issueData->line);
        $this->assertNull($issueData->column);
        $this->assertArrayHasKey('line', $issueData->context);
        $this->assertArrayHasKey('column', $issueData->context);
        $this->assertSame('not-a-number', $issueData->context['line']);
        $this->assertSame('also-not-a-number', $issueData->context['column']);
    }

    public function testToLegacyIssueWithoutLocationAndContext(): void
    {
        $issueData = new IssueData(
            file: '/test/file.xlf',
            messages: ['Simple message'],
            parser: 'XliffParser',
            validatorType: 'MismatchValidator',
            severity: ResultType::ERROR,
        );

        $legacyIssue = $issueData->toLegacyIssue();
        $details = $legacyIssue->getDetails();

        $this->assertSame('Simple message', $details[0]);
        $this->assertArrayNotHasKey('line', $details);
        $this->assertArrayNotHasKey('column', $details);
    }

    public function testGetFormattedStringEdgeCases(): void
    {
        // Empty message
        $emptyMessage = new IssueData(
            file: '/test/file.xlf',
            messages: [],
            parser: 'XliffParser',
            validatorType: 'MismatchValidator',
            severity: ResultType::ERROR,
        );
        $this->assertSame('/test/file.xlf:Unknown issue', $emptyMessage->getFormattedString());

        // Multiple colons in file path
        $colonFile = new IssueData(
            file: 'C:\\Users\\test:file\\test.xlf',
            messages: ['Error'],
            parser: 'XliffParser',
            validatorType: 'MismatchValidator',
            severity: ResultType::ERROR,
            line: 5,
        );
        $this->assertSame('C:\\Users\\test:file\\test.xlf:5:Error', $colonFile->getFormattedString());
    }

    public function testGetAllMessagesAsStringEmpty(): void
    {
        $issueData = new IssueData(
            file: '/test/file.xlf',
            messages: [],
            parser: 'XliffParser',
            validatorType: 'MismatchValidator',
            severity: ResultType::ERROR,
        );

        $this->assertSame('', $issueData->getAllMessagesAsString());
    }

    public function testFromIssueWithMixedIntKeys(): void
    {
        $issue = new Issue(
            '/test/file.xlf',
            [
                0 => 'First message',
                'custom_key' => 'Custom value',
                1 => 'Second message',
                'line' => 42,
            ],
            'XliffParser',
            'MismatchValidator',
        );

        $issueData = IssueData::fromIssue($issue);

        $this->assertSame(['First message', 'Second message'], $issueData->messages);
        $this->assertSame(42, $issueData->line);
        $this->assertSame(['custom_key' => 'Custom value'], $issueData->context);
    }
}
