<?php

declare(strict_types=1);

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
