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
use RuntimeException;

/**
 * Immutable value object providing API-friendly validation summary.
 *
 * This wraps ValidationResult for cleaner external API usage.
 */
final class ValidationSummary
{
    /**
     * @param bool          $success       Whether validation passed
     * @param ResultType    $resultType    Overall result type
     * @param int           $issuesCount   Total number of issues found
     * @param int           $filesChecked  Number of files validated
     * @param int           $validatorsRun Number of validators executed
     * @param float         $executionTime Validation execution time in seconds
     * @param array<string> $issueMessages List of issue messages
     */
    public function __construct(
        public readonly bool $success,
        public readonly ResultType $resultType,
        public readonly int $issuesCount,
        public readonly int $filesChecked,
        public readonly int $validatorsRun,
        public readonly float $executionTime,
        public readonly array $issueMessages = [],
    ) {}

    /**
     * Create ValidationSummary from ValidationResult.
     *
     * @param ValidationResult $result Original validation result
     */
    public static function fromValidationResult(ValidationResult $result): self
    {
        $issues = $result->getValidatorFileSetPairs();
        $issuesCount = count($issues);
        $issueMessages = [];

        foreach ($issues as $pair) {
            $validator = $pair['validator'];
            $validatorIssues = $validator->getIssues();
            foreach ($validatorIssues as $issue) {
                $details = $issue->getDetails();
                $message = implode(', ', $details);
                $issueMessages[] = sprintf(
                    '[%s] %s: %s',
                    $validator::class,
                    $issue->getFile(),
                    $message,
                );
            }
        }

        $statistics = $result->getStatistics();

        if (null === $statistics) {
            throw new RuntimeException('ValidationResult statistics cannot be null');
        }

        return new self(
            success: ResultType::SUCCESS === $result->getOverallResult(),
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
     */
    public function hasIssues(): bool
    {
        return $this->issuesCount > 0;
    }

    /**
     * Check if validation failed (errors found).
     */
    public function hasFailed(): bool
    {
        return ResultType::ERROR === $this->resultType;
    }

    /**
     * Check if validation has warnings.
     */
    public function hasWarnings(): bool
    {
        return ResultType::WARNING === $this->resultType;
    }
}
