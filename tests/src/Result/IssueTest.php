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

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\Result\Issue;
use PHPUnit\Framework\TestCase;

class IssueTest extends TestCase
{
    public function testConstruct(): void
    {
        $file = 'test.xlf';
        $details = ['key1' => 'value1', 'key2' => 'value2'];
        $parser = 'TestParser';
        $validatorType = 'TestValidator';

        $issue = new Issue($file, $details, $parser, $validatorType);

        $this->assertSame($file, $issue->getFile());
        $this->assertSame($details, $issue->getDetails());
        $this->assertSame($parser, $issue->getParser());
        $this->assertSame($validatorType, $issue->getValidatorType());
    }

    public function testGetFile(): void
    {
        $issue = new Issue('test.xlf', [], 'TestParser', 'TestValidator');

        $this->assertSame('test.xlf', $issue->getFile());
    }

    public function testGetDetails(): void
    {
        $details = ['error' => 'Something went wrong'];
        $issue = new Issue('test.xlf', $details, 'TestParser', 'TestValidator');

        $this->assertSame($details, $issue->getDetails());
    }

    public function testGetParser(): void
    {
        $issue = new Issue('test.xlf', [], 'XliffParser', 'TestValidator');

        $this->assertSame('XliffParser', $issue->getParser());
    }

    public function testGetValidatorType(): void
    {
        $issue = new Issue('test.xlf', [], 'TestParser', 'MismatchValidator');

        $this->assertSame('MismatchValidator', $issue->getValidatorType());
    }

    public function testToArray(): void
    {
        $file = 'messages.yaml';
        $details = ['duplicate' => ['key1', 'key2']];
        $parser = 'YamlParser';
        $validatorType = 'DuplicateValidator';

        $issue = new Issue($file, $details, $parser, $validatorType);

        $expected = [
            'file' => $file,
            'issues' => $details,
            'parser' => $parser,
            'type' => $validatorType,
        ];

        $this->assertSame($expected, $issue->toArray());
    }

    public function testToArrayWithEmptyDetails(): void
    {
        $issue = new Issue('empty.xlf', [], 'TestParser', 'TestValidator');

        $expected = [
            'file' => 'empty.xlf',
            'issues' => [],
            'parser' => 'TestParser',
            'type' => 'TestValidator',
        ];

        $this->assertSame($expected, $issue->toArray());
    }

    public function testToArrayWithComplexDetails(): void
    {
        $details = [
            'errors' => [
                ['line' => 5, 'message' => 'Invalid key'],
                ['line' => 10, 'message' => 'Missing value'],
            ],
            'warnings' => [
                ['line' => 3, 'message' => 'Deprecated usage'],
            ],
        ];

        $issue = new Issue('complex.xlf', $details, 'XliffParser', 'SchemaValidator');

        $expected = [
            'file' => 'complex.xlf',
            'issues' => $details,
            'parser' => 'XliffParser',
            'type' => 'SchemaValidator',
        ];

        $this->assertSame($expected, $issue->toArray());
    }

    public function testWithNullAndSpecialCharactersInDetails(): void
    {
        $details = [
            'null_value' => null,
            'special_chars' => 'äöü ß €',
            'unicode' => '🚀 🎉',
            'empty_string' => '',
            'zero' => 0,
            'false' => false,
        ];

        $issue = new Issue('special.xlf', $details, 'TestParser', 'TestValidator');

        $this->assertSame($details, $issue->getDetails());
        $this->assertSame($details, $issue->toArray()['issues']);
    }

    public function testWithLongFilePath(): void
    {
        $longPath = str_repeat('very/long/path/', 10).'test.xlf';
        $issue = new Issue($longPath, [], 'TestParser', 'TestValidator');

        $this->assertSame($longPath, $issue->getFile());
    }
}
