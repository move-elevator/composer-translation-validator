<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Result\ValidationRun;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

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
            [$validatorClass1, $validatorClass2]
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
}

// Mock classes for testing
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
    public function processFile(\MoveElevator\ComposerTranslationValidator\Parser\ParserInterface $file): array
    {
        return [];
    }

    public function explain(): string
    {
        return 'Mock validator without issues';
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

    public function addIssue(\MoveElevator\ComposerTranslationValidator\Result\Issue $issue): void
    {
    }

    public function formatIssueMessage(\MoveElevator\ComposerTranslationValidator\Result\Issue $issue, string $prefix = '', bool $isVerbose = false): string
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

    public function renderDetailedOutput(\Symfony\Component\Console\Output\OutputInterface $output, array $issues): void
    {
    }

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
    public function processFile(\MoveElevator\ComposerTranslationValidator\Parser\ParserInterface $file): array
    {
        return ['mock_issue' => 'test'];
    }

    public function explain(): string
    {
        return 'Mock validator with issues';
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
        return [new \MoveElevator\ComposerTranslationValidator\Result\Issue('test.xlf', ['mock'], 'MockParser', 'MockValidator')];
    }

    public function addIssue(\MoveElevator\ComposerTranslationValidator\Result\Issue $issue): void
    {
    }

    public function formatIssueMessage(\MoveElevator\ComposerTranslationValidator\Result\Issue $issue, string $prefix = '', bool $isVerbose = false): string
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

    public function renderDetailedOutput(\Symfony\Component\Console\Output\OutputInterface $output, array $issues): void
    {
    }

    public function getShortName(): string
    {
        return static::class;
    }
}
