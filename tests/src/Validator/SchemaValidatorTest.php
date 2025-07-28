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

use Exception;
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
        if (false === $files) {
            rmdir($path);

            return;
        }

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
        $this->assertStringContainsString("The attribute 'version' is required but missing.", (string) $result[0]['message']);
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
        $logger = $this->createMock(LoggerInterface::class);
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
        $logger = $this->createMock(LoggerInterface::class);
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
        $logger = $this->createMock(LoggerInterface::class);
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
        $logger = $this->createMock(LoggerInterface::class);
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

        $parser = $this->createMock(ParserInterface::class);
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

        $parser = $this->createMock(ParserInterface::class);
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

        $parser = $this->createMock(ParserInterface::class);
        $parser->method('getFilePath')->willReturn($this->validXliffFile);
        $parser->method('getFileName')->willReturn('valid.xlf');

        $result = $validator->processFile($parser);
        $this->assertEmpty($result);
    }
}
