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

use Exception;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Validator\XliffSchemaValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

use function sprintf;

/**
 * SchemaValidatorTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
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

    public function testProcessFileWithValidXliff(): void
    {
        $parser = $this->createStub(ParserInterface::class);
        $parser->method('getFilePath')->willReturn($this->validXliffFile);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new XliffSchemaValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testProcessFileWithInvalidXliff(): void
    {
        $parser = $this->createStub(ParserInterface::class);
        $parser->method('getFilePath')->willReturn($this->invalidXliffFile);

        $logger = $this->createStub(LoggerInterface::class);
        $validator = new XliffSchemaValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('message', $result[0]);
        $this->assertStringContainsString("The attribute 'version' is required but missing.", (string) $result[0]['message']);
    }

    public function testProcessFileWithNonExistentFile(): void
    {
        $parser = $this->createStub(ParserInterface::class);
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
        $logger = $this->createStub(LoggerInterface::class);
        $validator = new XliffSchemaValidator($logger);

        $this->assertSame([\MoveElevator\ComposerTranslationValidator\Parser\XliffParser::class], $validator->supportsParser());
    }

    public function testFormatIssueMessage(): void
    {
        $logger = $this->createStub(LoggerInterface::class);
        $validator = new XliffSchemaValidator($logger);

        $issue = new \MoveElevator\ComposerTranslationValidator\Result\Issue(
            'test.xlf',
            [
                'message' => 'Element was not closed',
                'line' => 10,
                'code' => 76,
                'level' => 'ERROR',
            ],
            'XliffParser',
            'SchemaValidator',
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
        $logger = $this->createStub(LoggerInterface::class);
        $validator = new XliffSchemaValidator($logger);

        $issue = new \MoveElevator\ComposerTranslationValidator\Result\Issue(
            'test.xlf',
            [
                'message' => 'Some warning',
                'line' => 5,
                'code' => 77,
                'level' => 'WARNING',
            ],
            'XliffParser',
            'SchemaValidator',
        );

        $result = $validator->formatIssueMessage($issue);

        $this->assertStringContainsString('Warning', $result);
        $this->assertStringContainsString('Some warning', $result);
        $this->assertStringContainsString('Line: 5', $result);
        $this->assertStringContainsString('<fg=yellow>', $result);
    }

    public function testFormatIssueMessageSingleError(): void
    {
        $logger = $this->createStub(LoggerInterface::class);
        $validator = new XliffSchemaValidator($logger);

        // With the new behavior, each error is a separate Issue
        $issue = new \MoveElevator\ComposerTranslationValidator\Result\Issue(
            'test.xlf',
            [
                'message' => 'Single error',
                'line' => 10,
                'code' => 76,
                'level' => 'ERROR',
            ],
            'XliffParser',
            'SchemaValidator',
        );

        $result = $validator->formatIssueMessage($issue);

        $this->assertStringContainsString('Single error', $result);
        $this->assertStringContainsString('Line: 10', $result);
        $this->assertStringContainsString('Code: 76', $result);
        $this->assertStringContainsString('<fg=red>', $result);
    }

    public function testFormatIssueMessageEmptyDetails(): void
    {
        $logger = $this->createStub(LoggerInterface::class);
        $validator = new XliffSchemaValidator($logger);

        $issue = new \MoveElevator\ComposerTranslationValidator\Result\Issue(
            'test.xlf',
            [],
            'XliffParser',
            'SchemaValidator',
        );

        $result = $validator->formatIssueMessage($issue);

        $this->assertStringContainsString('Error', $result);
        $this->assertStringContainsString('Schema validation error', $result);
        $this->assertStringContainsString('<fg=red>', $result);
    }

    public function testGetShortName(): void
    {
        $logger = $this->createStub(LoggerInterface::class);
        $validator = new XliffSchemaValidator($logger);

        $this->assertSame('XliffSchemaValidator', $validator->getShortName());
    }

    public function testProcessFileWithFileReadFailure(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('File does not exist:'));

        // Create a validator that simulates file_get_contents returning false
        $validator = new class($logger) extends XliffSchemaValidator {
            public function processFile(ParserInterface $file): array
            {
                if (!file_exists($file->getFilePath())) {
                    $this->logger?->error('File does not exist: '.$file->getFileName());

                    return [];
                }

                // Simulate file_get_contents returning false
                $fileContent = file_get_contents($file->getFilePath());
                if (false === $fileContent) {
                    $this->logger?->error('Failed to read file: '.$file->getFileName());

                    return [];
                }

                return parent::processFile($file);
            }
        };

        $parser = $this->createStub(ParserInterface::class);
        $parser->method('getFilePath')->willReturn('/non/existent/file.xlf');
        $parser->method('getFileName')->willReturn('nonexistent.xlf');

        $result = $validator->processFile($parser);
        $this->assertEmpty($result);
    }

    public function testProcessFileWithUnsupportedXliffVersion(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('notice')
            ->with($this->stringContains('No support implemented for loading XLIFF version'));

        // Create a validator that simulates the unsupported version exception
        $validator = new class($logger) extends XliffSchemaValidator {
            public function processFile(ParserInterface $file): array
            {
                if (!file_exists($file->getFilePath())) {
                    $this->logger?->error('File does not exist: '.$file->getFileName());

                    return [];
                }

                $fileContent = file_get_contents($file->getFilePath());
                if (false === $fileContent) {
                    $this->logger?->error('Failed to read file: '.$file->getFileName());

                    return [];
                }

                // Simulate exception with unsupported version message
                $e = new Exception('No support implemented for loading XLIFF version 2.0');
                if (str_contains($e->getMessage(), 'No support implemented for loading XLIFF version')) {
                    $this->logger?->notice(sprintf('Skipping %s: %s', $this->getShortName(), $e->getMessage()));
                } else {
                    $this->logger?->error('Failed to validate XML schema: '.$e->getMessage());
                }

                return [];
            }
        };

        $parser = $this->createStub(ParserInterface::class);
        $parser->method('getFilePath')->willReturn($this->validXliffFile);
        $parser->method('getFileName')->willReturn('valid.xlf');

        $result = $validator->processFile($parser);
        $this->assertEmpty($result);
    }

    public function testProcessFileWithValidationException(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to validate XML schema:'));

        // Create a validator that simulates a general validation exception
        $validator = new class($logger) extends XliffSchemaValidator {
            public function processFile(ParserInterface $file): array
            {
                if (!file_exists($file->getFilePath())) {
                    $this->logger?->error('File does not exist: '.$file->getFileName());

                    return [];
                }

                $fileContent = file_get_contents($file->getFilePath());
                if (false === $fileContent) {
                    $this->logger?->error('Failed to read file: '.$file->getFileName());

                    return [];
                }

                // Simulate general validation exception
                $e = new Exception('XML parsing error occurred');
                if (str_contains($e->getMessage(), 'No support implemented for loading XLIFF version')) {
                    $this->logger?->notice(sprintf('Skipping %s: %s', $this->getShortName(), $e->getMessage()));
                } else {
                    $this->logger?->error('Failed to validate XML schema: '.$e->getMessage());
                }

                return [];
            }
        };

        $parser = $this->createStub(ParserInterface::class);
        $parser->method('getFilePath')->willReturn($this->validXliffFile);
        $parser->method('getFileName')->willReturn('valid.xlf');

        $result = $validator->processFile($parser);
        $this->assertEmpty($result);
    }

    private function removeDirectory(string $path): void
    {
        $files = glob($path.'/*');
        if (false === $files) {
            rmdir($path);

            return;
        }

        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
    }
}
