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

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\Result\Issue;
use PHPUnit\Framework\TestCase;

/**
 * IssueTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class IssueTest extends TestCase
{
    public function testConstruct(): void
    {
        $file = 'test.xlf';
        $details = ['key1' => 'value1', 'key2' => 'value2'];
        $parser = 'TestParser';
        $validatorType = 'TestValidator';

        $issue = new Issue($file, $details, $parser, $validatorType);

        self::assertSame($file, $issue->getFile());
        self::assertSame($details, $issue->getDetails());
        self::assertSame($parser, $issue->getParser());
        self::assertSame($validatorType, $issue->getValidatorType());
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

        self::assertSame($expected, $issue->toArray());
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

        self::assertSame($expected, $issue->toArray());
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

        self::assertSame($expected, $issue->toArray());
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

        self::assertSame($details, $issue->getDetails());
        self::assertSame($details, $issue->toArray()['issues']);
    }
}
