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

namespace MoveElevator\ComposerTranslationValidator\Tests\Validation;

use MoveElevator\ComposerTranslationValidator\Result\ValidationResult;
use MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics;
use MoveElevator\ComposerTranslationValidator\Validation\ValidationSummary;
use MoveElevator\ComposerTranslationValidator\Validator\MismatchValidator;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(ValidationSummary::class)]
class ValidationSummaryTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $summary = new ValidationSummary(
            success: true,
            resultType: ResultType::SUCCESS,
            issuesCount: 0,
            filesChecked: 5,
            validatorsRun: 3,
            executionTime: 1.5,
            issueMessages: [],
        );

        $this->assertTrue($summary->success);
        $this->assertSame(ResultType::SUCCESS, $summary->resultType);
        $this->assertSame(0, $summary->issuesCount);
        $this->assertSame(5, $summary->filesChecked);
        $this->assertSame(3, $summary->validatorsRun);
        $this->assertSame(1.5, $summary->executionTime);
        $this->assertSame([], $summary->issueMessages);
    }

    public function testFromValidationResultWithSuccess(): void
    {
        $statistics = new ValidationStatistics(1.0, 5, 100, 3, 2);
        $validationResult = new ValidationResult([], ResultType::SUCCESS, [], $statistics);

        $summary = ValidationSummary::fromValidationResult($validationResult);

        $this->assertTrue($summary->success);
        $this->assertSame(ResultType::SUCCESS, $summary->resultType);
        $this->assertSame(0, $summary->issuesCount);
        $this->assertSame(5, $summary->filesChecked);
        $this->assertSame(3, $summary->validatorsRun);
        $this->assertSame(1.0, $summary->executionTime);
        $this->assertSame([], $summary->issueMessages);
    }

    public function testFromValidationResultWithIssues(): void
    {
        $statistics = new ValidationStatistics(2.0, 10, 200, 5, 3);
        
        // Create validator with issues
        $validator = new MismatchValidator(new NullLogger());
        $validatorFileSetPairs = [
            ['validator' => $validator, 'fileSet' => null],
        ];

        $validationResult = new ValidationResult(
            [$validator],
            ResultType::ERROR,
            $validatorFileSetPairs,
            $statistics
        );

        $summary = ValidationSummary::fromValidationResult($validationResult);

        $this->assertFalse($summary->success);
        $this->assertSame(ResultType::ERROR, $summary->resultType);
        $this->assertSame(1, $summary->issuesCount);
        $this->assertSame(10, $summary->filesChecked);
        $this->assertSame(5, $summary->validatorsRun);
        $this->assertSame(2.0, $summary->executionTime);
    }

    public function testHasIssuesWithNoIssues(): void
    {
        $summary = new ValidationSummary(
            success: true,
            resultType: ResultType::SUCCESS,
            issuesCount: 0,
            filesChecked: 5,
            validatorsRun: 3,
            executionTime: 1.0,
        );

        $this->assertFalse($summary->hasIssues());
    }

    public function testHasIssuesWithIssues(): void
    {
        $summary = new ValidationSummary(
            success: false,
            resultType: ResultType::ERROR,
            issuesCount: 2,
            filesChecked: 5,
            validatorsRun: 3,
            executionTime: 1.0,
        );

        $this->assertTrue($summary->hasIssues());
    }

    public function testHasFailedWithSuccess(): void
    {
        $summary = new ValidationSummary(
            success: true,
            resultType: ResultType::SUCCESS,
            issuesCount: 0,
            filesChecked: 5,
            validatorsRun: 3,
            executionTime: 1.0,
        );

        $this->assertFalse($summary->hasFailed());
    }

    public function testHasFailedWithError(): void
    {
        $summary = new ValidationSummary(
            success: false,
            resultType: ResultType::ERROR,
            issuesCount: 1,
            filesChecked: 5,
            validatorsRun: 3,
            executionTime: 1.0,
        );

        $this->assertTrue($summary->hasFailed());
    }

    public function testHasWarningsWithSuccess(): void
    {
        $summary = new ValidationSummary(
            success: true,
            resultType: ResultType::SUCCESS,
            issuesCount: 0,
            filesChecked: 5,
            validatorsRun: 3,
            executionTime: 1.0,
        );

        $this->assertFalse($summary->hasWarnings());
    }

    public function testHasWarningsWithWarning(): void
    {
        $summary = new ValidationSummary(
            success: false,
            resultType: ResultType::WARNING,
            issuesCount: 1,
            filesChecked: 5,
            validatorsRun: 3,
            executionTime: 1.0,
        );

        $this->assertTrue($summary->hasWarnings());
    }

    public function testHasWarningsWithError(): void
    {
        $summary = new ValidationSummary(
            success: false,
            resultType: ResultType::ERROR,
            issuesCount: 1,
            filesChecked: 5,
            validatorsRun: 3,
            executionTime: 1.0,
        );

        $this->assertFalse($summary->hasWarnings());
    }
}