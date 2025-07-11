<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Validator\XliffSchemaValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class SchemaValidatorTest extends TestCase
{
    private string $tempDir;
    private string $validXliffFile;
    private string $invalidXliffFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().'/schema_validator_test_'.uniqid('', true);
        mkdir($this->tempDir);

        $this->validXliffFile = $this->tempDir.'/valid.xlf';
        file_put_contents($this->validXliffFile, <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en" datatype="plaintext" original="messages.en.xlf">
    <body>
      <trans-unit id="key1">
        <source>Source 1</source>
        <target>Target 1</target>
      </trans-unit>
    </body>
  </file>
</xliff>
EOT
        );

        $this->invalidXliffFile = $this->tempDir.'/invalid.xlf';
        file_put_contents($this->invalidXliffFile, <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2">
  <file source-language="en" datatype="plaintext" original="messages.en.xlf">
    <body>
      <trans-unit id="key1">
        <source>Source 1</source>
        <target>Target 1</target>
      </trans-unit>
    </body>
  </file>
</xliff>
EOT
        ); // Missing version attribute
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    private function removeDirectory(string $path): void
    {
        $files = glob($path.'/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
    }

    public function testProcessFileWithValidXliff(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('getFilePath')->willReturn($this->validXliffFile);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new XliffSchemaValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testProcessFileWithInvalidXliff(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('getFilePath')->willReturn($this->invalidXliffFile);

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new XliffSchemaValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('message', $result[0]);
        $this->assertStringContainsString("The attribute 'version' is required but missing.", $result[0]['message']);
    }

    public function testProcessFileWithNonExistentFile(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('getFilePath')->willReturn('/non/existent/file.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('File does not exist'));

        $validator = new XliffSchemaValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testSupportsParser(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new XliffSchemaValidator($logger);

        $this->assertSame([\MoveElevator\ComposerTranslationValidator\Parser\XliffParser::class], $validator->supportsParser());
    }

    public function testFormatIssueMessage(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new XliffSchemaValidator($logger);

        $issue = new \MoveElevator\ComposerTranslationValidator\Result\Issue(
            'test.xlf',
            [
                [
                    'message' => 'Element was not closed',
                    'line' => 10,
                    'code' => 76,
                    'level' => 'ERROR',
                ],
            ],
            'XliffParser',
            'SchemaValidator'
        );

        $result = $validator->formatIssueMessage($issue);

        $this->assertStringContainsString('Error', $result);
        $this->assertStringContainsString('Element was not closed', $result);
        $this->assertStringContainsString('Line: 10', $result);
        $this->assertStringContainsString('Code: 76', $result);
        $this->assertStringContainsString('<fg=red>', $result);
    }

    public function testFormatIssueMessageWithWarning(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new XliffSchemaValidator($logger);

        $issue = new \MoveElevator\ComposerTranslationValidator\Result\Issue(
            'test.xlf',
            [
                [
                    'message' => 'Some warning',
                    'line' => 5,
                    'code' => 77,
                    'level' => 'WARNING',
                ],
            ],
            'XliffParser',
            'SchemaValidator'
        );

        $result = $validator->formatIssueMessage($issue);

        $this->assertStringContainsString('Warning', $result);
        $this->assertStringContainsString('Some warning', $result);
        $this->assertStringContainsString('Line: 5', $result);
        $this->assertStringContainsString('<fg=yellow>', $result);
    }

    public function testFormatIssueMessageMultipleErrors(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new XliffSchemaValidator($logger);

        $issue = new \MoveElevator\ComposerTranslationValidator\Result\Issue(
            'test.xlf',
            [
                [
                    'message' => 'First error',
                    'line' => 10,
                    'code' => 76,
                    'level' => 'ERROR',
                ],
                [
                    'message' => 'Second error',
                    'line' => 15,
                    'code' => 77,
                    'level' => 'ERROR',
                ],
            ],
            'XliffParser',
            'SchemaValidator'
        );

        $result = $validator->formatIssueMessage($issue);

        $this->assertStringContainsString('First error', $result);
        $this->assertStringContainsString('Second error', $result);
        $this->assertStringContainsString('Line: 10', $result);
        $this->assertStringContainsString('Line: 15', $result);
        // Should contain newline for multiple errors
        $this->assertStringContainsString("\n", $result);
    }

    public function testFormatIssueMessageEmptyDetails(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new XliffSchemaValidator($logger);

        $issue = new \MoveElevator\ComposerTranslationValidator\Result\Issue(
            'test.xlf',
            [],
            'XliffParser',
            'SchemaValidator'
        );

        $result = $validator->formatIssueMessage($issue);

        $this->assertStringContainsString('Error', $result);
        $this->assertStringContainsString('Schema validation error', $result);
        $this->assertStringContainsString('<fg=red>', $result);
    }

    public function testGetShortName(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new XliffSchemaValidator($logger);

        $this->assertSame('XliffSchemaValidator', $validator->getShortName());
    }
}
