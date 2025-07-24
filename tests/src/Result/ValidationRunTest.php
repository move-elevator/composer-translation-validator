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

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Result\ValidationRun;
use MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

class ValidationRunTest extends TestCase
{
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testConstructor(): void
    {
        $validationRun = new ValidationRun($this->logger);

        // Just verify the object exists by calling a method
        $result = $validationRun->executeFor([], []);
        $this->assertSame([], $result->getAllValidators());
    }

    public function testExecuteForWithEmptyFileSets(): void
    {
        $validationRun = new ValidationRun($this->logger);
        $result = $validationRun->executeFor([], []);

        $this->assertSame([], $result->getAllValidators());
        $this->assertSame(ResultType::SUCCESS, $result->getOverallResult());
        $this->assertFalse($result->hasIssues());
    }

    public function testExecuteForWithEmptyValidatorClasses(): void
    {
        $fileSet = new FileSet('TestParser', '/test', 'set', ['test.xlf']);
        $validationRun = new ValidationRun($this->logger);

        $result = $validationRun->executeFor([$fileSet], []);

        $this->assertSame([], $result->getAllValidators());
        $this->assertSame(ResultType::SUCCESS, $result->getOverallResult());
        $this->assertFalse($result->hasIssues());
    }

    public function testExecuteForWithValidatorWithoutIssues(): void
    {
        $validatorClass = MockValidatorWithoutIssues::class;
        $fileSet = new FileSet('TestParser', '/test', 'set', ['test.xlf']);

        $validationRun = new ValidationRun($this->logger);
        $result = $validationRun->executeFor([$fileSet], [$validatorClass]);

        $this->assertSame([], $result->getAllValidators());
        $this->assertSame(ResultType::SUCCESS, $result->getOverallResult());
        $this->assertFalse($result->hasIssues());
    }

    public function testExecuteForWithValidatorWithIssues(): void
    {
        $validatorClass = MockValidatorWithIssues::class;
        $fileSet = new FileSet('TestParser', '/test', 'set', ['test.xlf']);

        $validationRun = new ValidationRun($this->logger);
        $result = $validationRun->executeFor([$fileSet], [$validatorClass]);

        $this->assertCount(1, $result->getAllValidators());
        $this->assertSame(ResultType::ERROR, $result->getOverallResult());
        $this->assertTrue($result->hasIssues());
    }

    public function testExecuteForWithMultipleFileSetsAndValidators(): void
    {
        $validatorClass1 = MockValidatorWithIssues::class;
        $validatorClass2 = MockValidatorWithoutIssues::class;

        $fileSet1 = new FileSet('Parser1', '/path1', 'set1', ['file1.xlf']);
        $fileSet2 = new FileSet('Parser2', '/path2', 'set2', ['file2.xlf']);

        $validationRun = new ValidationRun($this->logger);
        $result = $validationRun->executeFor(
            [$fileSet1, $fileSet2],
            [$validatorClass1, $validatorClass2],
        );

        // Only validators with issues should be included
        $this->assertCount(2, $result->getAllValidators()); // 2 file sets × 1 validator with issues
        $this->assertSame(ResultType::ERROR, $result->getOverallResult());
        $this->assertTrue($result->hasIssues());
    }

    public function testCreateFileSetsFromArrayWithEmptyArray(): void
    {
        $fileSets = ValidationRun::createFileSetsFromArray([]);

        $this->assertSame([], $fileSets);
    }

    public function testCreateFileSetsFromArrayWithSingleFileSet(): void
    {
        $allFiles = [
            'TestParser' => [
                '/test/path' => [
                    'testSet' => ['file1.xlf', 'file2.xlf'],
                ],
            ],
        ];

        $fileSets = ValidationRun::createFileSetsFromArray($allFiles);

        $this->assertCount(1, $fileSets);
        $this->assertSame('TestParser', $fileSets[0]->getParser());
        $this->assertSame('/test/path', $fileSets[0]->getPath());
        $this->assertSame('testSet', $fileSets[0]->getSetKey());
        $this->assertSame(['file1.xlf', 'file2.xlf'], $fileSets[0]->getFiles());
    }

    public function testCreateFileSetsFromArrayWithMultipleFileSets(): void
    {
        $allFiles = [
            'Parser1' => [
                '/path1' => [
                    'set1' => ['file1.xlf'],
                    'set2' => ['file2.xlf'],
                ],
                '/path2' => [
                    'set3' => ['file3.xlf'],
                ],
            ],
            'Parser2' => [
                '/path3' => [
                    'set4' => ['file4.yaml', 'file5.yaml'],
                ],
            ],
        ];

        $fileSets = ValidationRun::createFileSetsFromArray($allFiles);

        $this->assertCount(4, $fileSets);

        // Check first file set
        $this->assertSame('Parser1', $fileSets[0]->getParser());
        $this->assertSame('/path1', $fileSets[0]->getPath());
        $this->assertSame('set1', $fileSets[0]->getSetKey());
        $this->assertSame(['file1.xlf'], $fileSets[0]->getFiles());

        // Check last file set
        $this->assertSame('Parser2', $fileSets[3]->getParser());
        $this->assertSame('/path3', $fileSets[3]->getPath());
        $this->assertSame('set4', $fileSets[3]->getSetKey());
        $this->assertSame(['file4.yaml', 'file5.yaml'], $fileSets[3]->getFiles());
    }

    public function testCreateFileSetsFromArrayWithEmptyValues(): void
    {
        $allFiles = [
            '' => [
                '' => [
                    '' => [],
                ],
            ],
        ];

        $fileSets = ValidationRun::createFileSetsFromArray($allFiles);

        $this->assertCount(1, $fileSets);
        $this->assertSame('', $fileSets[0]->getParser());
        $this->assertSame('', $fileSets[0]->getPath());
        $this->assertSame('', $fileSets[0]->getSetKey());
        $this->assertSame([], $fileSets[0]->getFiles());
    }

    public function testExecuteForCreatesStatistics(): void
    {
        $validatorClass = MockValidatorWithIssues::class;
        $fileSet = new FileSet(MockParserForTesting::class, '/test', 'set', ['test.xlf']);

        $validationRun = new ValidationRun($this->logger);
        $result = $validationRun->executeFor([$fileSet], [$validatorClass]);

        $statistics = $result->getStatistics();
        $this->assertInstanceOf(ValidationStatistics::class, $statistics);
        $this->assertGreaterThan(0, $statistics->getExecutionTime());
        $this->assertSame(1, $statistics->getFilesChecked());
        $this->assertSame(1, $statistics->getValidatorsRun());
        $this->assertGreaterThanOrEqual(0, $statistics->getKeysChecked());
    }

    public function testExecuteForWithMultipleFilesSetsStatistics(): void
    {
        $validatorClass1 = MockValidatorWithIssues::class;
        $validatorClass2 = MockValidatorWithoutIssues::class;

        $fileSet1 = new FileSet(MockParserForTesting::class, '/path1', 'set1', ['file1.xlf', 'file2.xlf']);
        $fileSet2 = new FileSet(MockParserForTesting::class, '/path2', 'set2', ['file3.xlf']);

        $validationRun = new ValidationRun($this->logger);
        $result = $validationRun->executeFor(
            [$fileSet1, $fileSet2],
            [$validatorClass1, $validatorClass2],
        );

        $statistics = $result->getStatistics();
        $this->assertInstanceOf(ValidationStatistics::class, $statistics);
        $this->assertGreaterThan(0, $statistics->getExecutionTime());
        $this->assertSame(3, $statistics->getFilesChecked()); // 2 + 1 files
        $this->assertSame(2, $statistics->getValidatorsRun()); // 2 validator classes
        $this->assertGreaterThanOrEqual(0, $statistics->getKeysChecked());
    }

    public function testExecuteForWithEmptyFileSetGeneratesZeroKeys(): void
    {
        $validationRun = new ValidationRun($this->logger);
        $result = $validationRun->executeFor([], []);

        $statistics = $result->getStatistics();
        $this->assertInstanceOf(ValidationStatistics::class, $statistics);
        $this->assertGreaterThanOrEqual(0, $statistics->getExecutionTime());
        $this->assertSame(0, $statistics->getFilesChecked());
        $this->assertSame(0, $statistics->getValidatorsRun());
        $this->assertSame(0, $statistics->getKeysChecked());
    }

    public function testExecuteForWithParserThrowsException(): void
    {
        $validatorClass = MockValidatorWithIssues::class;
        $fileSet = new FileSet(MockParserThatThrows::class, '/test', 'set', ['invalid.xlf']);

        $validationRun = new ValidationRun($this->logger);
        $result = $validationRun->executeFor([$fileSet], [$validatorClass]);

        $statistics = $result->getStatistics();
        // Should handle parser exceptions gracefully and continue execution
        if (null !== $statistics) {
            $this->assertSame(1, $statistics->getFilesChecked());
            $this->assertSame(0, $statistics->getKeysChecked()); // No keys counted due to exception
        }
    }

    public function testExecuteForWithParserReturningNullKeys(): void
    {
        $validatorClass = MockValidatorWithIssues::class;
        $fileSet = new FileSet(MockParserReturningNull::class, '/test', 'set', ['null.xlf']);

        $validationRun = new ValidationRun($this->logger);
        $result = $validationRun->executeFor([$fileSet], [$validatorClass]);

        $statistics = $result->getStatistics();
        if (null !== $statistics) {
            $this->assertSame(1, $statistics->getFilesChecked());
            $this->assertSame(0, $statistics->getKeysChecked()); // null keys should result in 0
        }
    }
}

// Mock classes for testing
class MockParserForTesting implements ParserInterface
{
    public function __construct(string $file)
    {
        // Mock parser doesn't actually read files
        unset($file);
    }

    public function extractKeys(): ?array
    {
        return ['key1', 'key2', 'key3']; // Return 3 mock keys for testing
    }

    public function getContentByKey(string $key): ?string
    {
        return "Mock content for {$key}";
    }

    public function getFileName(): string
    {
        return 'mock.xlf';
    }

    public function getFileDirectory(): string
    {
        return '/mock/path/';
    }

    public static function getSupportedFileExtensions(): array
    {
        return ['xlf'];
    }

    public function getFilePath(): string
    {
        return '/mock/path/mock.xlf';
    }
}

class MockValidatorWithoutIssues implements ValidatorInterface
{
    public function __construct(?LoggerInterface $logger = null)
    {
        // Logger parameter required by interface but not used in mock
        unset($logger);
    }

    /**
     * @return array<string, mixed>
     */
    public function validate(array $files, string $parserClass): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function processFile(ParserInterface $file): array
    {
        return [];
    }

    public function supportsParser(): array
    {
        return [];
    }

    public function resultTypeOnValidationFailure(): ResultType
    {
        return ResultType::ERROR;
    }

    public function hasIssues(): bool
    {
        return false;
    }

    public function getIssues(): array
    {
        return [];
    }

    public function addIssue(Issue $issue): void {}

    public function formatIssueMessage(Issue $issue, string $prefix = '', bool $isVerbose = false): string
    {
        return "- <fg=red>ERROR</> {$prefix}Mock validation error";
    }

    public function distributeIssuesForDisplay(FileSet $fileSet): array
    {
        return [];
    }

    public function shouldShowDetailedOutput(): bool
    {
        return false;
    }

    public function renderDetailedOutput(OutputInterface $output, array $issues): void {}

    public function getShortName(): string
    {
        return static::class;
    }
}

class MockValidatorWithIssues implements ValidatorInterface
{
    public function __construct(?LoggerInterface $logger = null)
    {
        // Logger parameter required by interface but not used in mock
        unset($logger);
    }

    /**
     * @return array<string, mixed>
     */
    public function validate(array $files, string $parserClass): array
    {
        return ['mock_issue' => 'test'];
    }

    /**
     * @return array<string, mixed>
     */
    public function processFile(ParserInterface $file): array
    {
        return ['mock_issue' => 'test'];
    }

    public function supportsParser(): array
    {
        return [];
    }

    public function resultTypeOnValidationFailure(): ResultType
    {
        return ResultType::ERROR;
    }

    public function hasIssues(): bool
    {
        return true;
    }

    public function getIssues(): array
    {
        return [new Issue('test.xlf', ['mock'], 'MockParser', 'MockValidator')];
    }

    public function addIssue(Issue $issue): void {}

    public function formatIssueMessage(Issue $issue, string $prefix = '', bool $isVerbose = false): string
    {
        return "- <fg=red>ERROR</> {$prefix}Mock validation error with issues";
    }

    public function distributeIssuesForDisplay(FileSet $fileSet): array
    {
        $distribution = [];
        foreach ($this->getIssues() as $issue) {
            $fileName = $issue->getFile();
            if (!empty($fileName)) {
                $basePath = rtrim($fileSet->getPath(), '/');
                $filePath = $basePath.'/'.$fileName;
                $distribution[$filePath][] = $issue;
            }
        }

        return $distribution;
    }

    public function shouldShowDetailedOutput(): bool
    {
        return false;
    }

    public function renderDetailedOutput(OutputInterface $output, array $issues): void {}

    public function getShortName(): string
    {
        return static::class;
    }
}

class MockParserThatThrows implements ParserInterface
{
    public function __construct(string $file)
    {
        unset($file);
    }

    public function extractKeys(): ?array
    {
        throw new RuntimeException('Parser failed');
    }

    public function getContentByKey(string $key): ?string
    {
        return null;
    }

    public function getFileName(): string
    {
        return 'error.xlf';
    }

    public function getFileDirectory(): string
    {
        return '/error/path/';
    }

    public static function getSupportedFileExtensions(): array
    {
        return ['xlf'];
    }

    public function getFilePath(): string
    {
        return '/error/path/error.xlf';
    }
}

class MockParserReturningNull implements ParserInterface
{
    public function __construct(string $file)
    {
        unset($file);
    }

    public function extractKeys(): ?array
    {
        return null;
    }

    public function getContentByKey(string $key): ?string
    {
        return null;
    }

    public function getFileName(): string
    {
        return 'null.xlf';
    }

    public function getFileDirectory(): string
    {
        return '/null/path/';
    }

    public static function getSupportedFileExtensions(): array
    {
        return ['xlf'];
    }

    public function getFilePath(): string
    {
        return '/null/path/null.xlf';
    }
}
