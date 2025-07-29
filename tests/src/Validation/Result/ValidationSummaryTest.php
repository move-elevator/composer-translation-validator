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
use MoveElevator\ComposerTranslationValidator\Validation\Result\IssueData;
use MoveElevator\ComposerTranslationValidator\Validation\Result\ValidationSummary;
use MoveElevator\ComposerTranslationValidator\Validator\MismatchValidator;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ValidationSummary::class)]
class ValidationSummaryTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $issues = [
            new IssueData(
                file: '/test/file1.xlf',
                messages: ['Error 1'],
                parser: 'XliffParser',
                validatorType: 'MismatchValidator',
                severity: ResultType::ERROR,
            ),
        ];

        $statistics = ['key' => 'value'];

        $summary = new ValidationSummary(
            success: false,
            overallResult: ResultType::ERROR,
            totalIssues: 1,
            totalValidators: 2,
            totalFiles: 3,
            executionTimeSeconds: 1.5,
            issues: $issues,
            validatorNames: ['MismatchValidator'],
            statistics: $statistics,
        );

        $this->assertFalse($summary->success);
        $this->assertSame(ResultType::ERROR, $summary->overallResult);
        $this->assertSame(1, $summary->totalIssues);
        $this->assertSame(2, $summary->totalValidators);
        $this->assertSame(3, $summary->totalFiles);
        $this->assertEqualsWithDelta(1.5, $summary->executionTimeSeconds, PHP_FLOAT_EPSILON);
        $this->assertSame($issues, $summary->issues);
        $this->assertSame(['MismatchValidator'], $summary->validatorNames);
        $this->assertSame($statistics, $summary->statistics);
    }

    public function testConstructorWithDefaults(): void
    {
        $summary = new ValidationSummary(
            success: true,
            overallResult: ResultType::SUCCESS,
            totalIssues: 0,
            totalValidators: 1,
            totalFiles: 1,
            executionTimeSeconds: 0.1,
        );

        $this->assertTrue($summary->success);
        $this->assertSame(ResultType::SUCCESS, $summary->overallResult);
        $this->assertSame(0, $summary->totalIssues);
        $this->assertSame(1, $summary->totalValidators);
        $this->assertSame(1, $summary->totalFiles);
        $this->assertEqualsWithDelta(0.1, $summary->executionTimeSeconds, PHP_FLOAT_EPSILON);
        $this->assertSame([], $summary->issues);
        $this->assertSame([], $summary->validatorNames);
        $this->assertSame([], $summary->statistics);
    }

    public function testFromValidationResultSuccess(): void
    {
        $validator = new MismatchValidator();
        $fileSet = new FileSet('XliffParser', '/test/path', 'test-set', ['/test/file.xlf']);
        $statistics = new ValidationStatistics(0.5, 1, 500, 1);

        $result = new ValidationResult(
            [$validator],
            ResultType::SUCCESS,
            [['validator' => $validator, 'fileSet' => $fileSet]],
            $statistics,
        );

        $summary = ValidationSummary::fromValidationResult($result);

        $this->assertTrue($summary->success);
        $this->assertSame(ResultType::SUCCESS, $summary->overallResult);
        $this->assertSame(0, $summary->totalIssues);
        $this->assertSame(1, $summary->totalValidators);
        $this->assertSame(1, $summary->totalFiles);
        $this->assertEqualsWithDelta(0.5, $summary->executionTimeSeconds, PHP_FLOAT_EPSILON);
        $this->assertSame([], $summary->issues);
        $this->assertSame([MismatchValidator::class], $summary->validatorNames);
    }

    public function testFromValidationResultWithIssues(): void
    {
        $validator = new MismatchValidator();
        $issue = new Issue('/test/file.xlf', ['Test error'], 'XliffParser', 'MismatchValidator');
        $validator->addIssue($issue);

        $fileSet = new FileSet('XliffParser', '/test/path', 'test-set', ['/test/file.xlf']);
        $statistics = new ValidationStatistics(0.8, 1, 800, 1);

        $result = new ValidationResult(
            [$validator],
            ResultType::ERROR,
            [['validator' => $validator, 'fileSet' => $fileSet]],
            $statistics,
        );

        $summary = ValidationSummary::fromValidationResult($result);

        $this->assertFalse($summary->success);
        $this->assertSame(ResultType::ERROR, $summary->overallResult);
        $this->assertSame(1, $summary->totalIssues);
        $this->assertSame(1, $summary->totalValidators);
        $this->assertSame(1, $summary->totalFiles);
        $this->assertEqualsWithDelta(0.8, $summary->executionTimeSeconds, PHP_FLOAT_EPSILON);
        $this->assertCount(1, $summary->issues);
        $this->assertSame([MismatchValidator::class], $summary->validatorNames);
    }

    public function testFromValidationResultWithoutStatistics(): void
    {
        $validator = new MismatchValidator();
        $fileSet = new FileSet('XliffParser', '/test/path', 'test-set', ['/test/file.xlf']);

        $result = new ValidationResult(
            [$validator],
            ResultType::SUCCESS,
            [['validator' => $validator, 'fileSet' => $fileSet]],
            null,
        );

        $summary = ValidationSummary::fromValidationResult($result);

        $this->assertSame(0, $summary->totalFiles);
        $this->assertEqualsWithDelta(0.0, $summary->executionTimeSeconds, PHP_FLOAT_EPSILON);
        $this->assertSame([], $summary->statistics);
    }

    public function testHasIssues(): void
    {
        $summaryWithIssues = new ValidationSummary(
            success: false,
            overallResult: ResultType::ERROR,
            totalIssues: 1,
            totalValidators: 1,
            totalFiles: 1,
            executionTimeSeconds: 1.0,
        );
        $this->assertTrue($summaryWithIssues->hasIssues());

        $summaryWithoutIssues = new ValidationSummary(
            success: true,
            overallResult: ResultType::SUCCESS,
            totalIssues: 0,
            totalValidators: 1,
            totalFiles: 1,
            executionTimeSeconds: 1.0,
        );
        $this->assertFalse($summaryWithoutIssues->hasIssues());
    }

    public function testHasFailed(): void
    {
        $failedSummary = new ValidationSummary(
            success: false,
            overallResult: ResultType::ERROR,
            totalIssues: 1,
            totalValidators: 1,
            totalFiles: 1,
            executionTimeSeconds: 1.0,
        );
        $this->assertTrue($failedSummary->hasFailed());

        $warningSummary = new ValidationSummary(
            success: false,
            overallResult: ResultType::WARNING,
            totalIssues: 1,
            totalValidators: 1,
            totalFiles: 1,
            executionTimeSeconds: 1.0,
        );
        $this->assertFalse($warningSummary->hasFailed());

        $successSummary = new ValidationSummary(
            success: true,
            overallResult: ResultType::SUCCESS,
            totalIssues: 0,
            totalValidators: 1,
            totalFiles: 1,
            executionTimeSeconds: 1.0,
        );
        $this->assertFalse($successSummary->hasFailed());
    }

    public function testHasWarnings(): void
    {
        $warningSummary = new ValidationSummary(
            success: false,
            overallResult: ResultType::WARNING,
            totalIssues: 1,
            totalValidators: 1,
            totalFiles: 1,
            executionTimeSeconds: 1.0,
        );
        $this->assertTrue($warningSummary->hasWarnings());

        $errorSummary = new ValidationSummary(
            success: false,
            overallResult: ResultType::ERROR,
            totalIssues: 1,
            totalValidators: 1,
            totalFiles: 1,
            executionTimeSeconds: 1.0,
        );
        $this->assertFalse($errorSummary->hasWarnings());

        $successSummary = new ValidationSummary(
            success: true,
            overallResult: ResultType::SUCCESS,
            totalIssues: 0,
            totalValidators: 1,
            totalFiles: 1,
            executionTimeSeconds: 1.0,
        );
        $this->assertFalse($successSummary->hasWarnings());
    }

    public function testGetIssuesByFile(): void
    {
        $issues = [
            new IssueData(
                file: '/test/file1.xlf',
                messages: ['Error 1'],
                parser: 'XliffParser',
                validatorType: 'MismatchValidator',
                severity: ResultType::ERROR,
            ),
            new IssueData(
                file: '/test/file1.xlf',
                messages: ['Error 2'],
                parser: 'XliffParser',
                validatorType: 'MismatchValidator',
                severity: ResultType::ERROR,
            ),
            new IssueData(
                file: '/test/file2.xlf',
                messages: ['Error 3'],
                parser: 'XliffParser',
                validatorType: 'MismatchValidator',
                severity: ResultType::ERROR,
            ),
        ];

        $summary = new ValidationSummary(
            success: false,
            overallResult: ResultType::ERROR,
            totalIssues: 3,
            totalValidators: 1,
            totalFiles: 2,
            executionTimeSeconds: 1.0,
            issues: $issues,
        );

        $issuesByFile = $summary->getIssuesByFile();

        $this->assertArrayHasKey('/test/file1.xlf', $issuesByFile);
        $this->assertArrayHasKey('/test/file2.xlf', $issuesByFile);
        $this->assertCount(2, $issuesByFile['/test/file1.xlf']);
        $this->assertCount(1, $issuesByFile['/test/file2.xlf']);
    }

    public function testGetIssuesByValidator(): void
    {
        $issues = [
            new IssueData(
                file: '/test/file1.xlf',
                messages: ['Error 1'],
                parser: 'XliffParser',
                validatorType: 'MismatchValidator',
                severity: ResultType::ERROR,
            ),
            new IssueData(
                file: '/test/file2.xlf',
                messages: ['Error 2'],
                parser: 'XliffParser',
                validatorType: 'MismatchValidator',
                severity: ResultType::ERROR,
            ),
            new IssueData(
                file: '/test/file3.xlf',
                messages: ['Error 3'],
                parser: 'XliffParser',
                validatorType: 'DuplicateKeysValidator',
                severity: ResultType::ERROR,
            ),
        ];

        $summary = new ValidationSummary(
            success: false,
            overallResult: ResultType::ERROR,
            totalIssues: 3,
            totalValidators: 2,
            totalFiles: 3,
            executionTimeSeconds: 1.0,
            issues: $issues,
        );

        $issuesByValidator = $summary->getIssuesByValidator();

        $this->assertArrayHasKey('MismatchValidator', $issuesByValidator);
        $this->assertArrayHasKey('DuplicateKeysValidator', $issuesByValidator);
        $this->assertCount(2, $issuesByValidator['MismatchValidator']);
        $this->assertCount(1, $issuesByValidator['DuplicateKeysValidator']);
    }

    public function testGetIssuesBySeverity(): void
    {
        $issues = [
            new IssueData(
                file: '/test/file1.xlf',
                messages: ['Error 1'],
                parser: 'XliffParser',
                validatorType: 'MismatchValidator',
                severity: ResultType::ERROR,
            ),
            new IssueData(
                file: '/test/file2.xlf',
                messages: ['Warning 1'],
                parser: 'XliffParser',
                validatorType: 'MismatchValidator',
                severity: ResultType::WARNING,
            ),
            new IssueData(
                file: '/test/file3.xlf',
                messages: ['Error 2'],
                parser: 'XliffParser',
                validatorType: 'MismatchValidator',
                severity: ResultType::ERROR,
            ),
        ];

        $summary = new ValidationSummary(
            success: false,
            overallResult: ResultType::ERROR,
            totalIssues: 3,
            totalValidators: 1,
            totalFiles: 3,
            executionTimeSeconds: 1.0,
            issues: $issues,
        );

        $issuesBySeverity = $summary->getIssuesBySeverity();

        $this->assertArrayHasKey('error', $issuesBySeverity);
        $this->assertArrayHasKey('warning', $issuesBySeverity);
        $this->assertCount(2, $issuesBySeverity['error']);
        $this->assertCount(1, $issuesBySeverity['warning']);
    }

    public function testGetSuccessRate(): void
    {
        // Test with no files
        $summaryNoFiles = new ValidationSummary(
            success: true,
            overallResult: ResultType::SUCCESS,
            totalIssues: 0,
            totalValidators: 1,
            totalFiles: 0,
            executionTimeSeconds: 1.0,
        );
        $this->assertEqualsWithDelta(100.0, $summaryNoFiles->getSuccessRate(), PHP_FLOAT_EPSILON);

        // Test with all files successful
        $summaryAllSuccess = new ValidationSummary(
            success: true,
            overallResult: ResultType::SUCCESS,
            totalIssues: 0,
            totalValidators: 1,
            totalFiles: 3,
            executionTimeSeconds: 1.0,
            issues: [],
        );
        $this->assertEqualsWithDelta(100.0, $summaryAllSuccess->getSuccessRate(), PHP_FLOAT_EPSILON);

        // Test with some files having issues
        $issues = [
            new IssueData(
                file: '/test/file1.xlf',
                messages: ['Error 1'],
                parser: 'XliffParser',
                validatorType: 'MismatchValidator',
                severity: ResultType::ERROR,
            ),
        ];

        $summaryPartialSuccess = new ValidationSummary(
            success: false,
            overallResult: ResultType::ERROR,
            totalIssues: 1,
            totalValidators: 1,
            totalFiles: 3,
            executionTimeSeconds: 1.0,
            issues: $issues,
        );
        // 2 files without issues out of 3 total = 66.67%
        $this->assertEqualsWithDelta(66.67, $summaryPartialSuccess->getSuccessRate(), 0.01);
    }

    public function testGetFormattedExecutionTime(): void
    {
        // Test milliseconds
        $summaryMs = new ValidationSummary(
            success: true,
            overallResult: ResultType::SUCCESS,
            totalIssues: 0,
            totalValidators: 1,
            totalFiles: 1,
            executionTimeSeconds: 0.5,
        );
        $this->assertSame('500ms', $summaryMs->getFormattedExecutionTime());

        // Test seconds
        $summarySeconds = new ValidationSummary(
            success: true,
            overallResult: ResultType::SUCCESS,
            totalIssues: 0,
            totalValidators: 1,
            totalFiles: 1,
            executionTimeSeconds: 2.345,
        );
        $this->assertSame('2.35s', $summarySeconds->getFormattedExecutionTime());

        // Test edge case: exactly 1 second
        $summaryOneSecond = new ValidationSummary(
            success: true,
            overallResult: ResultType::SUCCESS,
            totalIssues: 0,
            totalValidators: 1,
            totalFiles: 1,
            executionTimeSeconds: 1.0,
        );
        $this->assertSame('1.00s', $summaryOneSecond->getFormattedExecutionTime());
    }

    public function testToArray(): void
    {
        $issues = [
            new IssueData(
                file: '/test/file.xlf',
                messages: ['Test error'],
                parser: 'XliffParser',
                validatorType: 'MismatchValidator',
                severity: ResultType::WARNING,
                context: ['key' => 'value'],
                line: 42,
                column: 15,
            ),
        ];

        $statistics = ['filesChecked' => 1, 'validatorsRun' => 1];

        $summary = new ValidationSummary(
            success: false,
            overallResult: ResultType::WARNING,
            totalIssues: 1,
            totalValidators: 1,
            totalFiles: 1,
            executionTimeSeconds: 1.5,
            issues: $issues,
            validatorNames: ['MismatchValidator'],
            statistics: $statistics,
        );

        $array = $summary->toArray();

        $this->assertFalse($array['success']);
        $this->assertSame('warning', $array['overallResult']);

        $this->assertSame(1, $array['summary']['totalIssues']);
        $this->assertSame(1, $array['summary']['totalValidators']);
        $this->assertSame(1, $array['summary']['totalFiles']);
        $this->assertSame('1.50s', $array['summary']['executionTime']);
        $this->assertEqualsWithDelta(0.0, $array['summary']['successRate'], PHP_FLOAT_EPSILON); // 1 file with issues out of 1 total

        $this->assertCount(1, $array['issues']);
        $this->assertSame('/test/file.xlf', $array['issues'][0]['file']);

        $this->assertSame(['MismatchValidator'], $array['validators']);

        $this->assertSame($statistics, $array['statistics']);
    }

    public function testToJson(): void
    {
        $summary = new ValidationSummary(
            success: true,
            overallResult: ResultType::SUCCESS,
            totalIssues: 0,
            totalValidators: 1,
            totalFiles: 1,
            executionTimeSeconds: 0.5,
        );

        $json = $summary->toJson();
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertSame('success', $decoded['overallResult']);
    }

    public function testToJsonWithCustomFlags(): void
    {
        $summary = new ValidationSummary(
            success: true,
            overallResult: ResultType::SUCCESS,
            totalIssues: 0,
            totalValidators: 1,
            totalFiles: 1,
            executionTimeSeconds: 0.5,
        );

        $json = $summary->toJson(JSON_UNESCAPED_SLASHES);

        // Should not contain pretty print formatting
        $this->assertStringNotContainsString("\n", $json);
        $this->assertStringNotContainsString('    ', $json);
    }

    public function testGetIssuesByFileEmpty(): void
    {
        $summary = new ValidationSummary(
            success: true,
            overallResult: ResultType::SUCCESS,
            totalIssues: 0,
            totalValidators: 1,
            totalFiles: 1,
            executionTimeSeconds: 0.5,
            issues: [],
        );

        $this->assertSame([], $summary->getIssuesByFile());
    }

    public function testGetIssuesByValidatorEmpty(): void
    {
        $summary = new ValidationSummary(
            success: true,
            overallResult: ResultType::SUCCESS,
            totalIssues: 0,
            totalValidators: 1,
            totalFiles: 1,
            executionTimeSeconds: 0.5,
            issues: [],
        );

        $this->assertSame([], $summary->getIssuesByValidator());
    }

    public function testGetIssuesBySeverityEmpty(): void
    {
        $summary = new ValidationSummary(
            success: true,
            overallResult: ResultType::SUCCESS,
            totalIssues: 0,
            totalValidators: 1,
            totalFiles: 1,
            executionTimeSeconds: 0.5,
            issues: [],
        );

        $this->assertSame([], $summary->getIssuesBySeverity());
    }
}
