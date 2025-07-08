<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResult;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use PHPUnit\Framework\TestCase;

class ValidationResultTest extends TestCase
{
    public function testConstructWithoutValidatorFileSetPairs(): void
    {
        $validators = [];
        $resultType = ResultType::SUCCESS;

        $result = new ValidationResult($validators, $resultType);

        $this->assertSame($validators, $result->getAllValidators());
        $this->assertSame($resultType, $result->getOverallResult());
        $this->assertFalse($result->hasIssues());
        $this->assertSame([], $result->getValidatorsWithIssues());
    }

    public function testConstructWithValidatorFileSetPairs(): void
    {
        $validators = [];
        $resultType = ResultType::ERROR;
        $pairs = [];

        $result = new ValidationResult($validators, $resultType, $pairs);

        $this->assertSame($validators, $result->getAllValidators());
        $this->assertSame($resultType, $result->getOverallResult());
    }

    public function testHasIssuesWithNoValidators(): void
    {
        $result = new ValidationResult([], ResultType::SUCCESS);

        $this->assertFalse($result->hasIssues());
    }

    public function testHasIssuesWithValidatorsWithoutIssues(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('hasIssues')->willReturn(false);

        $result = new ValidationResult([$validator], ResultType::SUCCESS);

        $this->assertFalse($result->hasIssues());
    }

    public function testHasIssuesWithValidatorsWithIssues(): void
    {
        $validator1 = $this->createMock(ValidatorInterface::class);
        $validator1->method('hasIssues')->willReturn(false);

        $validator2 = $this->createMock(ValidatorInterface::class);
        $validator2->method('hasIssues')->willReturn(true);

        $result = new ValidationResult([$validator1, $validator2], ResultType::ERROR);

        $this->assertTrue($result->hasIssues());
    }

    public function testGetValidatorsWithIssues(): void
    {
        $validator1 = $this->createMock(ValidatorInterface::class);
        $validator1->method('hasIssues')->willReturn(false);

        $validator2 = $this->createMock(ValidatorInterface::class);
        $validator2->method('hasIssues')->willReturn(true);

        $validator3 = $this->createMock(ValidatorInterface::class);
        $validator3->method('hasIssues')->willReturn(true);

        $result = new ValidationResult([$validator1, $validator2, $validator3], ResultType::WARNING);

        $validatorsWithIssues = $result->getValidatorsWithIssues();

        $this->assertCount(2, $validatorsWithIssues);
        $this->assertContains($validator2, $validatorsWithIssues);
        $this->assertContains($validator3, $validatorsWithIssues);
        $this->assertNotContains($validator1, $validatorsWithIssues);
    }

    public function testGetAllValidators(): void
    {
        $validator1 = $this->createMock(ValidatorInterface::class);
        $validator2 = $this->createMock(ValidatorInterface::class);

        $validators = [$validator1, $validator2];
        $result = new ValidationResult($validators, ResultType::SUCCESS);

        $this->assertSame($validators, $result->getAllValidators());
    }

    public function testGetOverallResult(): void
    {
        $resultType = ResultType::WARNING;
        $result = new ValidationResult([], $resultType);

        $this->assertSame($resultType, $result->getOverallResult());
    }

    public function testGetValidatorFileSetPairs(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $fileSet = new FileSet('TestParser', '/test/path', 'testSet', ['test.xlf']);

        $pairs = [
            ['validator' => $validator, 'fileSet' => $fileSet],
        ];

        $result = new ValidationResult([$validator], ResultType::SUCCESS, $pairs);

        $this->assertSame($pairs, $result->getValidatorFileSetPairs());
    }

    public function testToLegacyArrayWithoutValidatorFileSetPairs(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([
            new Issue('test.xlf', ['error' => 'test'], 'TestParser', 'TestValidator'),
        ]);

        $result = new ValidationResult([$validator], ResultType::ERROR);
        $legacyArray = $result->toLegacyArray();

        $expectedClass = $validator::class;
        $this->assertArrayHasKey($expectedClass, $legacyArray);
        $this->assertArrayHasKey('', $legacyArray[$expectedClass]);
        $this->assertArrayHasKey('', $legacyArray[$expectedClass]['']);
        $this->assertCount(1, $legacyArray[$expectedClass]['']['']);
    }

    public function testToLegacyArrayWithValidatorFileSetPairs(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([
            new Issue('test.xlf', ['error' => 'test'], 'TestParser', 'TestValidator'),
        ]);

        $fileSet = new FileSet('TestParser', '/test/path', 'testSet', ['test.xlf']);

        $pairs = [
            ['validator' => $validator, 'fileSet' => $fileSet],
        ];

        $result = new ValidationResult([$validator], ResultType::ERROR, $pairs);
        $legacyArray = $result->toLegacyArray();

        $expectedClass = $validator::class;
        $this->assertArrayHasKey($expectedClass, $legacyArray);
        $this->assertArrayHasKey('/test/path', $legacyArray[$expectedClass]);
        $this->assertArrayHasKey('testSet', $legacyArray[$expectedClass]['/test/path']);
        $this->assertCount(1, $legacyArray[$expectedClass]['/test/path']['testSet']);
    }

    public function testToLegacyArrayWithMultipleValidatorsWithIssues(): void
    {
        // Create real mock classes instead of using createMock to get different class names
        $validator1 = new class implements ValidatorInterface {
            public function hasIssues(): bool
            {
                return true;
            }

            public function getIssues(): array
            {
                return [new Issue('file1.xlf', ['error1'], 'Parser1', 'Validator1')];
            }

            public function addIssue(Issue $issue): void
            {
            }

            public function validate(array $files, string $parserClass): array
            {
                return [];
            }

            public function processFile(\MoveElevator\ComposerTranslationValidator\Parser\ParserInterface $file): array
            {
                return [];
            }

            public function renderIssueSets(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output, array $issueSets): void
            {
            }

            public function explain(): string
            {
                return '';
            }

            public function supportsParser(): array
            {
                return [];
            }

            public function resultTypeOnValidationFailure(): ResultType
            {
                return ResultType::ERROR;
            }

            public function formatIssueMessage(Issue $issue, string $prefix = '', bool $isVerbose = false): string
            {
                return "- <fg=red>ERROR</> {$prefix}Mock validation error";
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
        };

        $validator2 = new class implements ValidatorInterface {
            public function hasIssues(): bool
            {
                return true;
            }

            public function getIssues(): array
            {
                return [new Issue('file2.xlf', ['error2'], 'Parser2', 'Validator2')];
            }

            public function addIssue(Issue $issue): void
            {
            }

            public function validate(array $files, string $parserClass): array
            {
                return [];
            }

            public function processFile(\MoveElevator\ComposerTranslationValidator\Parser\ParserInterface $file): array
            {
                return [];
            }

            public function renderIssueSets(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output, array $issueSets): void
            {
            }

            public function explain(): string
            {
                return '';
            }

            public function supportsParser(): array
            {
                return [];
            }

            public function resultTypeOnValidationFailure(): ResultType
            {
                return ResultType::ERROR;
            }

            public function formatIssueMessage(Issue $issue, string $prefix = '', bool $isVerbose = false): string
            {
                return "- <fg=red>ERROR</> {$prefix}Mock validation error 2";
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
        };

        $fileSet1 = new FileSet('Parser1', '/path1', 'set1', ['file1.xlf']);
        $fileSet2 = new FileSet('Parser2', '/path2', 'set2', ['file2.xlf']);

        $pairs = [
            ['validator' => $validator1, 'fileSet' => $fileSet1],
            ['validator' => $validator2, 'fileSet' => $fileSet2],
        ];

        $result = new ValidationResult([$validator1, $validator2], ResultType::ERROR, $pairs);
        $legacyArray = $result->toLegacyArray();

        // Each validator gets its own entry, so we should have 2 entries
        $this->assertCount(2, $legacyArray);

        // Check that both validator classes are present
        $validatorClasses = array_keys($legacyArray);
        $this->assertContains($validator1::class, $validatorClasses);
        $this->assertContains($validator2::class, $validatorClasses);
    }

    public function testToLegacyArrayWithValidatorsWithoutIssues(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('hasIssues')->willReturn(false);

        $result = new ValidationResult([$validator], ResultType::SUCCESS);
        $legacyArray = $result->toLegacyArray();

        $this->assertSame([], $legacyArray);
    }

    public function testToLegacyArrayWithMixedValidators(): void
    {
        $validatorWithIssues = new class implements ValidatorInterface {
            public function hasIssues(): bool
            {
                return true;
            }

            public function getIssues(): array
            {
                return [new Issue('test.xlf', ['error'], 'TestParser', 'TestValidator')];
            }

            public function addIssue(Issue $issue): void
            {
            }

            public function validate(array $files, string $parserClass): array
            {
                return [];
            }

            public function processFile(\MoveElevator\ComposerTranslationValidator\Parser\ParserInterface $file): array
            {
                return [];
            }

            public function renderIssueSets(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output, array $issueSets): void
            {
            }

            public function explain(): string
            {
                return '';
            }

            public function supportsParser(): array
            {
                return [];
            }

            public function resultTypeOnValidationFailure(): ResultType
            {
                return ResultType::ERROR;
            }

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

            public function renderDetailedOutput(\Symfony\Component\Console\Output\OutputInterface $output, array $issues): void
            {
            }
        };

        $validatorWithoutIssues = new class implements ValidatorInterface {
            public function hasIssues(): bool
            {
                return false;
            }

            public function getIssues(): array
            {
                return [];
            }

            public function addIssue(Issue $issue): void
            {
            }

            public function validate(array $files, string $parserClass): array
            {
                return [];
            }

            public function processFile(\MoveElevator\ComposerTranslationValidator\Parser\ParserInterface $file): array
            {
                return [];
            }

            public function renderIssueSets(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output, array $issueSets): void
            {
            }

            public function explain(): string
            {
                return '';
            }

            public function supportsParser(): array
            {
                return [];
            }

            public function resultTypeOnValidationFailure(): ResultType
            {
                return ResultType::ERROR;
            }

            public function formatIssueMessage(Issue $issue, string $prefix = '', bool $isVerbose = false): string
            {
                return "- <fg=red>ERROR</> {$prefix}Mock validation error without issues";
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
        };

        $fileSet = new FileSet('TestParser', '/test', 'set', ['test.xlf']);

        $pairs = [
            ['validator' => $validatorWithIssues, 'fileSet' => $fileSet],
            ['validator' => $validatorWithoutIssues, 'fileSet' => $fileSet],
        ];

        $result = new ValidationResult([$validatorWithIssues, $validatorWithoutIssues], ResultType::ERROR, $pairs);
        $legacyArray = $result->toLegacyArray();

        $this->assertCount(1, $legacyArray);
        $this->assertArrayHasKey($validatorWithIssues::class, $legacyArray);

        // The validator without issues should NOT be in the legacy array
        $validatorClasses = array_keys($legacyArray);
        $this->assertNotContains($validatorWithoutIssues::class, $validatorClasses);
    }
}
