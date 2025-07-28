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

namespace MoveElevator\ComposerTranslationValidator\Validation;

use MoveElevator\ComposerTranslationValidator\Result\ValidationResult;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;

/**
 * Immutable value object providing API-friendly validation summary.
 * 
 * This wraps ValidationResult for cleaner external API usage.
 */
final readonly class ValidationSummary
{
    /**
     * @param bool $success Whether validation passed
     * @param ResultType $resultType Overall result type
     * @param int $issuesCount Total number of issues found
     * @param int $filesChecked Number of files validated
     * @param int $validatorsRun Number of validators executed
     * @param float $executionTime Validation execution time in seconds
     * @param array<string> $issueMessages List of issue messages
     */
    public function __construct(
        public bool $success,
        public ResultType $resultType,
        public int $issuesCount,
        public int $filesChecked,
        public int $validatorsRun,
        public float $executionTime,
        public array $issueMessages = [],
    ) {}

    /**
     * Create ValidationSummary from ValidationResult.
     *
     * @param ValidationResult $result Original validation result
     * @return self
     */
    public static function fromValidationResult(ValidationResult $result): self
    {
        $issues = $result->getValidatorFileSetPairs();
        $issuesCount = count($issues);
        $issueMessages = [];

        foreach ($issues as $pair) {
            $validator = $pair['validator'];
            $issues = $validator->getIssues();
            foreach ($issues as $issue) {
                $issueMessages[] = sprintf(
                    '[%s] %s',
                    $validator::class,
                    $issue->getMessage()
                );
            }
        }

        $statistics = $result->getStatistics();

        return new self(
            success: $result->getOverallResult() === ResultType::SUCCESS,
            resultType: $result->getOverallResult(),
            issuesCount: $issuesCount,
            filesChecked: $statistics->getFilesChecked(),
            validatorsRun: $statistics->getValidatorsRun(),
            executionTime: $statistics->getExecutionTime(),
            issueMessages: $issueMessages,
        );
    }

    /**
     * Check if validation had any issues.
     *
     * @return bool
     */
    public function hasIssues(): bool
    {
        return $this->issuesCount > 0;
    }

    /**
     * Check if validation failed (errors found).
     *
     * @return bool
     */
    public function hasFailed(): bool
    {
        return $this->resultType === ResultType::ERROR;
    }

    /**
     * Check if validation has warnings.
     *
     * @return bool
     */
    public function hasWarnings(): bool
    {
        return $this->resultType === ResultType::WARNING;
    }
}