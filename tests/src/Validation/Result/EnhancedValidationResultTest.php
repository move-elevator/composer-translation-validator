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

namespace MoveElevator\ComposerTranslationValidator\Tests\Validation\Result;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResult;
use MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics;
use MoveElevator\ComposerTranslationValidator\Validation\Result\EnhancedValidationResult;
use MoveElevator\ComposerTranslationValidator\Validation\Result\ValidatorFileSetPair;
use MoveElevator\ComposerTranslationValidator\Validator\MismatchValidator;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EnhancedValidationResult::class)]
class EnhancedValidationResultTest extends TestCase
{
    private MismatchValidator $validator;
    private FileSet $fileSet;
    private ValidatorFileSetPair $pair;
    private ValidationStatistics $statistics;

    protected function setUp(): void
    {
        $this->validator = new MismatchValidator();
        $this->fileSet = new FileSet('XliffParser', '/test/path', 'test-set', ['/test/file.xlf']);
        $this->pair = new ValidatorFileSetPair($this->validator, $this->fileSet);
        $this->statistics = new ValidationStatistics(0.5, 1, 500, 1);
    }

    public function testConstructorWithAllParameters(): void
    {
        $result = new EnhancedValidationResult(
            validatorInstances: [$this->validator],
            overallResult: ResultType::SUCCESS,
            validatorFileSetPairs: [$this->pair],
            statistics: $this->statistics,
        );

        $this->assertSame([$this->validator], $result->validatorInstances);
        $this->assertSame(ResultType::SUCCESS, $result->overallResult);
        $this->assertSame([$this->pair], $result->validatorFileSetPairs);
        $this->assertSame($this->statistics, $result->statistics);
    }

    public function testConstructorWithDefaults(): void
    {
        $result = new EnhancedValidationResult(
            validatorInstances: [$this->validator],
            overallResult: ResultType::SUCCESS,
        );

        $this->assertSame([$this->validator], $result->validatorInstances);
        $this->assertSame(ResultType::SUCCESS, $result->overallResult);
        $this->assertSame([], $result->validatorFileSetPairs);
        $this->assertNotInstanceOf(\MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics::class, $result->statistics);
    }

    public function testFromValidationResultSuccess(): void
    {
        $legacyResult = new ValidationResult(
            [$this->validator],
            ResultType::SUCCESS,
            [['validator' => $this->validator, 'fileSet' => $this->fileSet]],
            $this->statistics,
        );

        $enhanced = EnhancedValidationResult::fromValidationResult($legacyResult);

        $this->assertSame([$this->validator], $enhanced->validatorInstances);
        $this->assertSame(ResultType::SUCCESS, $enhanced->overallResult);
        $this->assertCount(1, $enhanced->validatorFileSetPairs);
        $this->assertSame($this->statistics, $enhanced->statistics);
    }

    public function testFromValidationResultWithIssues(): void
    {
        $issue = new Issue('/test/file.xlf', ['Test error'], 'XliffParser', 'MismatchValidator');
        $this->validator->addIssue($issue);

        $legacyResult = new ValidationResult(
            [$this->validator],
            ResultType::ERROR,
            [['validator' => $this->validator, 'fileSet' => $this->fileSet]],
            $this->statistics,
        );

        $enhanced = EnhancedValidationResult::fromValidationResult($legacyResult);

        $this->assertSame(ResultType::ERROR, $enhanced->overallResult);
        $this->assertTrue($enhanced->hasIssues());
        $this->assertCount(1, $enhanced->getAllIssues());
    }

    public function testToLegacyValidationResult(): void
    {
        $enhanced = new EnhancedValidationResult(
            validatorInstances: [$this->validator],
            overallResult: ResultType::SUCCESS,
            validatorFileSetPairs: [$this->pair],
            statistics: $this->statistics,
        );

        $legacy = $enhanced->toLegacyValidationResult();

        $this->assertSame([$this->validator], $legacy->getAllValidators());
        $this->assertSame(ResultType::SUCCESS, $legacy->getOverallResult());
        $this->assertSame($this->statistics, $legacy->getStatistics());

        $pairs = $legacy->getValidatorFileSetPairs();
        $this->assertCount(1, $pairs);
        $this->assertSame($this->validator, $pairs[0]['validator']);
        $this->assertSame($this->fileSet, $pairs[0]['fileSet']);
    }

    public function testHasIssuesWithoutIssues(): void
    {
        $result = new EnhancedValidationResult(
            validatorInstances: [$this->validator],
            overallResult: ResultType::SUCCESS,
            validatorFileSetPairs: [$this->pair],
        );

        $this->assertFalse($result->hasIssues());
    }

    public function testHasIssuesWithIssues(): void
    {
        $issue = new Issue('/test/file.xlf', ['Test error'], 'XliffParser', 'MismatchValidator');
        $this->validator->addIssue($issue);

        $result = new EnhancedValidationResult(
            validatorInstances: [$this->validator],
            overallResult: ResultType::ERROR,
            validatorFileSetPairs: [$this->pair],
        );

        $this->assertTrue($result->hasIssues());
    }

    public function testGetValidatorsWithIssues(): void
    {
        $validatorWithoutIssues = new MismatchValidator();
        $validatorWithIssues = new MismatchValidator();
        $issue = new Issue('/test/file.xlf', ['Test error'], 'XliffParser', 'MismatchValidator');
        $validatorWithIssues->addIssue($issue);

        $result = new EnhancedValidationResult(
            validatorInstances: [$validatorWithoutIssues, $validatorWithIssues],
            overallResult: ResultType::ERROR,
        );

        $validatorsWithIssues = $result->getValidatorsWithIssues();

        $this->assertCount(1, $validatorsWithIssues);
        $this->assertSame($validatorWithIssues, array_values($validatorsWithIssues)[0]);
    }

    public function testGetAllValidators(): void
    {
        $validator1 = new MismatchValidator();
        $validator2 = new MismatchValidator();

        $result = new EnhancedValidationResult(
            validatorInstances: [$validator1, $validator2],
            overallResult: ResultType::SUCCESS,
        );

        $validators = $result->getAllValidators();

        $this->assertCount(2, $validators);
        $this->assertSame($validator1, $validators[0]);
        $this->assertSame($validator2, $validators[1]);
    }

    public function testGetValidatorFileSetPairs(): void
    {
        $pair1 = new ValidatorFileSetPair($this->validator, $this->fileSet);
        $pair2 = new ValidatorFileSetPair($this->validator, $this->fileSet);

        $result = new EnhancedValidationResult(
            validatorInstances: [$this->validator],
            overallResult: ResultType::SUCCESS,
            validatorFileSetPairs: [$pair1, $pair2],
        );

        $pairs = $result->getValidatorFileSetPairs();

        $this->assertCount(2, $pairs);
        $this->assertSame($pair1, $pairs[0]);
        $this->assertSame($pair2, $pairs[1]);
    }

    public function testGetValidatorFileSetPairsWithIssues(): void
    {
        $validatorWithoutIssues = new MismatchValidator();
        $validatorWithIssues = new MismatchValidator();
        $issue = new Issue('/test/file.xlf', ['Test error'], 'XliffParser', 'MismatchValidator');
        $validatorWithIssues->addIssue($issue);

        $pairWithoutIssues = new ValidatorFileSetPair($validatorWithoutIssues, $this->fileSet);
        $pairWithIssues = new ValidatorFileSetPair($validatorWithIssues, $this->fileSet);

        $result = new EnhancedValidationResult(
            validatorInstances: [$validatorWithoutIssues, $validatorWithIssues],
            overallResult: ResultType::ERROR,
            validatorFileSetPairs: [$pairWithoutIssues, $pairWithIssues],
        );

        $pairsWithIssues = $result->getValidatorFileSetPairsWithIssues();

        $this->assertCount(1, $pairsWithIssues);
        $this->assertSame($pairWithIssues, array_values($pairsWithIssues)[0]);
    }

    public function testGetAllIssuesEmpty(): void
    {
        $result = new EnhancedValidationResult(
            validatorInstances: [$this->validator],
            overallResult: ResultType::SUCCESS,
            validatorFileSetPairs: [$this->pair],
        );

        $issues = $result->getAllIssues();

        $this->assertSame([], $issues);
    }

    public function testGetAllIssuesWithMultipleIssues(): void
    {
        $validator1 = new MismatchValidator();
        $validator2 = new MismatchValidator();

        $issue1 = new Issue('/test/file1.xlf', ['Error 1'], 'XliffParser', 'MismatchValidator');
        $issue2 = new Issue('/test/file2.xlf', ['Error 2'], 'XliffParser', 'MismatchValidator');
        $issue3 = new Issue('/test/file3.xlf', ['Error 3'], 'XliffParser', 'MismatchValidator');

        $validator1->addIssue($issue1);
        $validator1->addIssue($issue2);
        $validator2->addIssue($issue3);

        $pair1 = new ValidatorFileSetPair($validator1, $this->fileSet);
        $pair2 = new ValidatorFileSetPair($validator2, $this->fileSet);

        $result = new EnhancedValidationResult(
            validatorInstances: [$validator1, $validator2],
            overallResult: ResultType::ERROR,
            validatorFileSetPairs: [$pair1, $pair2],
        );

        $issues = $result->getAllIssues();

        $this->assertCount(3, $issues);
        $this->assertSame('/test/file1.xlf', $issues[0]->file);
        $this->assertSame('/test/file2.xlf', $issues[1]->file);
        $this->assertSame('/test/file3.xlf', $issues[2]->file);
    }

    public function testGetIssuesByFile(): void
    {
        $validator = new MismatchValidator();

        $issue1 = new Issue('/test/file1.xlf', ['Error 1'], 'XliffParser', 'MismatchValidator');
        $issue2 = new Issue('/test/file1.xlf', ['Error 2'], 'XliffParser', 'MismatchValidator');
        $issue3 = new Issue('/test/file2.xlf', ['Error 3'], 'XliffParser', 'MismatchValidator');

        $validator->addIssue($issue1);
        $validator->addIssue($issue2);
        $validator->addIssue($issue3);

        $pair = new ValidatorFileSetPair($validator, $this->fileSet);

        $result = new EnhancedValidationResult(
            validatorInstances: [$validator],
            overallResult: ResultType::ERROR,
            validatorFileSetPairs: [$pair],
        );

        $issuesByFile = $result->getIssuesByFile();

        $this->assertArrayHasKey('/test/file1.xlf', $issuesByFile);
        $this->assertArrayHasKey('/test/file2.xlf', $issuesByFile);
        $this->assertCount(2, $issuesByFile['/test/file1.xlf']);
        $this->assertCount(1, $issuesByFile['/test/file2.xlf']);
    }

    public function testGetIssuesByValidator(): void
    {
        $validator = new MismatchValidator();

        // Create issues that would be grouped by validator type
        $issue1 = new Issue('/test/file1.xlf', ['Error 1'], 'XliffParser', 'MismatchValidator');
        $issue2 = new Issue('/test/file2.xlf', ['Error 2'], 'XliffParser', 'MismatchValidator');

        $validator->addIssue($issue1);
        $validator->addIssue($issue2);

        $pair = new ValidatorFileSetPair($validator, $this->fileSet);

        $result = new EnhancedValidationResult(
            validatorInstances: [$validator],
            overallResult: ResultType::ERROR,
            validatorFileSetPairs: [$pair],
        );

        $issuesByValidator = $result->getIssuesByValidator();

        $this->assertArrayHasKey('MismatchValidator', $issuesByValidator);
        $this->assertCount(2, $issuesByValidator['MismatchValidator']);
    }

    public function testGetSummary(): void
    {
        $result = new EnhancedValidationResult(
            validatorInstances: [$this->validator],
            overallResult: ResultType::SUCCESS,
            validatorFileSetPairs: [$this->pair],
            statistics: $this->statistics,
        );

        $summary = $result->getSummary();

        $this->assertTrue($summary->success);
        $this->assertSame(ResultType::SUCCESS, $summary->overallResult);
        $this->assertSame(1, $summary->totalValidators);
    }

    public function testGetOverallResult(): void
    {
        $result = new EnhancedValidationResult(
            validatorInstances: [$this->validator],
            overallResult: ResultType::WARNING,
        );

        $this->assertSame(ResultType::WARNING, $result->getOverallResult());
    }

    public function testGetStatistics(): void
    {
        $result = new EnhancedValidationResult(
            validatorInstances: [$this->validator],
            overallResult: ResultType::SUCCESS,
            statistics: $this->statistics,
        );

        $this->assertSame($this->statistics, $result->getStatistics());
    }

    public function testGetStatisticsNull(): void
    {
        $result = new EnhancedValidationResult(
            validatorInstances: [$this->validator],
            overallResult: ResultType::SUCCESS,
        );

        $this->assertNotInstanceOf(\MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics::class, $result->getStatistics());
    }

    public function testGetTotalIssueCount(): void
    {
        $validator = new MismatchValidator();

        $issue1 = new Issue('/test/file1.xlf', ['Error 1'], 'XliffParser', 'MismatchValidator');
        $issue2 = new Issue('/test/file2.xlf', ['Error 2'], 'XliffParser', 'MismatchValidator');

        $validator->addIssue($issue1);
        $validator->addIssue($issue2);

        $pair = new ValidatorFileSetPair($validator, $this->fileSet);

        $result = new EnhancedValidationResult(
            validatorInstances: [$validator],
            overallResult: ResultType::ERROR,
            validatorFileSetPairs: [$pair],
        );

        $this->assertSame(2, $result->getTotalIssueCount());
    }

    public function testGetTotalValidatorCount(): void
    {
        $validator1 = new MismatchValidator();
        $validator2 = new MismatchValidator();

        $result = new EnhancedValidationResult(
            validatorInstances: [$validator1, $validator2],
            overallResult: ResultType::SUCCESS,
        );

        $this->assertSame(2, $result->getTotalValidatorCount());
    }

    public function testGetTotalFileCount(): void
    {
        $statistics = new ValidationStatistics(1.0, 5, 2, 1);

        $result = new EnhancedValidationResult(
            validatorInstances: [$this->validator],
            overallResult: ResultType::SUCCESS,
            statistics: $statistics,
        );

        $this->assertSame(5, $result->getTotalFileCount());
    }

    public function testGetTotalFileCountWithoutStatistics(): void
    {
        $result = new EnhancedValidationResult(
            validatorInstances: [$this->validator],
            overallResult: ResultType::SUCCESS,
        );

        $this->assertSame(0, $result->getTotalFileCount());
    }

    public function testIsSuccess(): void
    {
        $successResult = new EnhancedValidationResult(
            validatorInstances: [$this->validator],
            overallResult: ResultType::SUCCESS,
        );
        $this->assertTrue($successResult->isSuccess());

        $validator = new MismatchValidator();
        $issue = new Issue('/test/file.xlf', ['Error'], 'XliffParser', 'MismatchValidator');
        $validator->addIssue($issue);

        $errorResult = new EnhancedValidationResult(
            validatorInstances: [$validator],
            overallResult: ResultType::ERROR,
        );
        $this->assertFalse($errorResult->isSuccess());
    }

    public function testIsFailure(): void
    {
        $errorResult = new EnhancedValidationResult(
            validatorInstances: [$this->validator],
            overallResult: ResultType::ERROR,
        );
        $this->assertTrue($errorResult->isFailure());

        $warningResult = new EnhancedValidationResult(
            validatorInstances: [$this->validator],
            overallResult: ResultType::WARNING,
        );
        $this->assertFalse($warningResult->isFailure());

        $successResult = new EnhancedValidationResult(
            validatorInstances: [$this->validator],
            overallResult: ResultType::SUCCESS,
        );
        $this->assertFalse($successResult->isFailure());
    }

    public function testHasOnlyWarnings(): void
    {
        $warningResult = new EnhancedValidationResult(
            validatorInstances: [$this->validator],
            overallResult: ResultType::WARNING,
        );
        $this->assertTrue($warningResult->hasOnlyWarnings());

        $errorResult = new EnhancedValidationResult(
            validatorInstances: [$this->validator],
            overallResult: ResultType::ERROR,
        );
        $this->assertFalse($errorResult->hasOnlyWarnings());

        $successResult = new EnhancedValidationResult(
            validatorInstances: [$this->validator],
            overallResult: ResultType::SUCCESS,
        );
        $this->assertFalse($successResult->hasOnlyWarnings());
    }

    public function testRoundTripConversion(): void
    {
        $issue = new Issue('/test/file.xlf', ['Test error'], 'XliffParser', 'MismatchValidator');
        $this->validator->addIssue($issue);

        $original = new ValidationResult(
            [$this->validator],
            ResultType::ERROR,
            [['validator' => $this->validator, 'fileSet' => $this->fileSet]],
            $this->statistics,
        );

        $enhanced = EnhancedValidationResult::fromValidationResult($original);
        $converted = $enhanced->toLegacyValidationResult();

        $this->assertSame($original->getOverallResult(), $converted->getOverallResult());
        $this->assertSame($original->getAllValidators(), $converted->getAllValidators());
        $this->assertSame($original->getStatistics(), $converted->getStatistics());
        $this->assertSame($original->hasIssues(), $converted->hasIssues());
    }
}
