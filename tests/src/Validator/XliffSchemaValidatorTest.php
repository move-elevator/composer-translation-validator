<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Validator\XliffSchemaValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * XliffSchemaValidatorTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class XliffSchemaValidatorTest extends TestCase
{
    private XliffSchemaValidator $validator;

    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->validator = new XliffSchemaValidator($this->logger);
    }

    public function testClassCanBeInstantiatedWithoutLogger(): void
    {
        $validator = new XliffSchemaValidator();
        $this->assertSame([XliffParser::class], $validator->supportsParser());
    }

    public function testSupportsParser(): void
    {
        $supportedParsers = $this->validator->supportsParser();

        $this->assertSame([XliffParser::class], $supportedParsers);
    }

    public function testProcessFileWithValidXliff(): void
    {
        $validXliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file source-language="en" target-language="de" original="test.xlf" datatype="plaintext">
        <body>
            <trans-unit id="test">
                <source>Hello</source>
                <target>Hallo</target>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'xliff_test_');
        file_put_contents($tempFile, $validXliff);

        $parser = $this->createMock(XliffParser::class);
        $parser->method('getFilePath')->willReturn($tempFile);
        $parser->method('getFileName')->willReturn('test.xlf');

        $result = $this->validator->processFile($parser);

        unlink($tempFile);

        $this->assertSame([], $result);
    }

    public function testProcessFileWithNonExistentFile(): void
    {
        $parser = $this->createMock(XliffParser::class);
        $parser->method('getFilePath')->willReturn('/non/existent/file.xlf');
        $parser->method('getFileName')->willReturn('non_existent.xlf');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('File does not exist: non_existent.xlf');

        $result = $this->validator->processFile($parser);

        $this->assertSame([], $result);
    }

    public function testProcessFileWithInvalidXml(): void
    {
        $invalidXml = '<invalid xml content';

        $tempFile = tempnam(sys_get_temp_dir(), 'xliff_test_');
        file_put_contents($tempFile, $invalidXml);

        $parser = $this->createMock(XliffParser::class);
        $parser->method('getFilePath')->willReturn($tempFile);
        $parser->method('getFileName')->willReturn('invalid.xlf');

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to validate XML schema:'));

        $result = $this->validator->processFile($parser);

        unlink($tempFile);

        $this->assertSame([], $result);
    }

    public function testProcessFileWithUnsupportedXliffVersion(): void
    {
        $unsupportedXliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="2.1" xmlns="urn:oasis:names:tc:xliff:document:2.1">
    <file id="test">
        <unit id="test">
            <segment>
                <source>Hello</source>
                <target>Hallo</target>
            </segment>
        </unit>
    </file>
</xliff>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'xliff_test_');
        file_put_contents($tempFile, $unsupportedXliff);

        $parser = $this->createMock(XliffParser::class);
        $parser->method('getFilePath')->willReturn($tempFile);
        $parser->method('getFileName')->willReturn('unsupported.xlf');

        $this->logger->expects($this->once())
            ->method('notice')
            ->with($this->stringContains('Skipping XliffSchemaValidator: No support implemented for loading XLIFF version'));

        $result = $this->validator->processFile($parser);

        unlink($tempFile);

        $this->assertSame([], $result);
    }

    public function testFormatIssueMessageWithArrayError(): void
    {
        // AbstractValidator creates one Issue per error, so errorDetails is a single error array
        $errorDetails = [
            'message' => 'Element validation failed',
            'line' => 42,
            'code' => 'XLIFF001',
            'level' => 'ERROR',
        ];

        $issue = new Issue(
            'test.xlf',
            $errorDetails,
            'XliffParser',
            'XliffSchemaValidator',
        );

        $result = $this->validator->formatIssueMessage($issue, 'Prefix: ');

        $expected = '- <fg=red>Error</> Prefix: Element validation failed (Line: 42) (Code: XLIFF001)';
        $this->assertSame($expected, $result);
    }

    public function testFormatIssueMessageWithWarning(): void
    {
        // AbstractValidator creates one Issue per error, so errorDetails is a single error array
        $errorDetails = [
            'message' => 'Optional element missing',
            'level' => 'WARNING',
        ];

        $issue = new Issue(
            'test.xlf',
            $errorDetails,
            'XliffParser',
            'XliffSchemaValidator',
        );

        $result = $this->validator->formatIssueMessage($issue);

        $expected = '- <fg=yellow>Warning</> Optional element missing';
        $this->assertSame($expected, $result);
    }

    public function testFormatIssueMessageWithEmptyDetails(): void
    {
        $issue = new Issue(
            'test.xlf',
            [],
            'XliffParser',
            'XliffSchemaValidator',
        );

        $result = $this->validator->formatIssueMessage($issue);

        $expected = '- <fg=red>Error</> Schema validation error';
        $this->assertSame($expected, $result);
    }

    public function testFormatIssueMessageWithIncompleteErrorArray(): void
    {
        // Test a single error array with missing message
        $errorDetails = [
            'line' => 10,
            'code' => 1234,
            'level' => 'ERROR',
            // 'message' is missing
        ];

        $issue = new Issue(
            'test.xlf',
            $errorDetails,
            'XliffParser',
            'XliffSchemaValidator',
        );

        $result = $this->validator->formatIssueMessage($issue);

        $expected = '- <fg=red>Error</> Schema validation error';
        $this->assertSame($expected, $result);
    }
}
