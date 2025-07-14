<?php

declare(strict_types=1);

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

final class EmptyValuesValidatorTest extends TestCase
{
    public function testProcessFileWithEmptyValues(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2', 'key3']);
        $parser->method('getContentByKey')->willReturnMap([
            ['key1', 'source', 'Valid content'],
            ['key2', 'source', ''],
            ['key3', 'source', '   '],
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
            ['key1', 'source', 'Valid content'],
            ['key2', 'source', 'Another valid content'],
            ['key3', 'source', 'Third valid content'],
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
            ['key1', 'source', 'Valid content'],
            ['key2', 'source', null],
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
            ['key1', 'source', 'Valid content'],
            ['key2', 'source', ''],
            ['key3', 'source', ' '],
            ['key4', 'source', "\t\n "],
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
            'EmptyValuesValidator'
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
            'EmptyValuesValidator'
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
            'EmptyValuesValidator'
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
            'EmptyValuesValidator'
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
            ['key1', 'source', 'Valid content'],
            ['key2', 'source', ''],
            ['key3', 'source', 'Another valid content'],
            ['key4', 'source', '   '],
            ['key5', 'source', 'Final valid content'],
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
