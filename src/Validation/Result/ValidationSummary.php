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

namespace MoveElevator\ComposerTranslationValidator\Validation\Result;

use MoveElevator\ComposerTranslationValidator\Result\ValidationResult;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;

/**
 * Immutable value object providing external API-friendly validation summary.
 *
 * Wraps ValidationResult with cleaner interface for programmatic consumption
 * by external packages like TYPO3 Commands. Compatible with PHP 8.1+ readonly properties.
 */
final readonly class ValidationSummary
{
    /**
     * @param bool                 $success              Whether validation passed without issues
     * @param ResultType           $overallResult        Overall validation result type
     * @param int                  $totalIssues          Total number of validation issues found
     * @param int                  $totalValidators      Number of validators executed
     * @param int                  $totalFiles           Number of files processed
     * @param float                $executionTimeSeconds Time taken for validation
     * @param array<IssueData>     $issues               Structured issue data
     * @param array<string>        $validatorNames       Names of all executed validators
     * @param array<string, mixed> $statistics           Additional statistical data
     */
    public function __construct(
        public bool $success,
        public ResultType $overallResult,
        public int $totalIssues,
        public int $totalValidators,
        public int $totalFiles,
        public float $executionTimeSeconds,
        public array $issues = [],
        public array $validatorNames = [],
        public array $statistics = [],
    ) {}

    /**
     * Create ValidationSummary from ValidationResult.
     */
    public static function fromValidationResult(ValidationResult $result): self
    {
        $issues = [];
        $validatorNames = [];
        $totalIssues = 0;

        // Process validator-fileset pairs for structured data
        foreach ($result->getValidatorFileSetPairs() as $pair) {
            $validator = $pair['validator'];
            $validatorNames[] = $validator::class;

            if ($validator->hasIssues()) {
                foreach ($validator->getIssues() as $issue) {
                    $issues[] = IssueData::fromIssue($issue, $result->getOverallResult());
                    ++$totalIssues;
                }
            }
        }

        $statistics = $result->getStatistics();
        $statisticsData = [];

        if (null !== $statistics) {
            $statisticsData = [
                'filesChecked' => $statistics->getFilesChecked(),
                'validatorsRun' => $statistics->getValidatorsRun(),
                'executionTime' => $statistics->getExecutionTime(),
            ];
        }

        return new self(
            success: !$result->hasIssues(),
            overallResult: $result->getOverallResult(),
            totalIssues: $totalIssues,
            totalValidators: count($result->getAllValidators()),
            totalFiles: $statistics?->getFilesChecked() ?? 0,
            executionTimeSeconds: $statistics?->getExecutionTime() ?? 0.0,
            issues: $issues,
            validatorNames: array_unique($validatorNames),
            statistics: $statisticsData,
        );
    }

    /**
     * Check if validation has any issues.
     */
    public function hasIssues(): bool
    {
        return $this->totalIssues > 0;
    }

    /**
     * Check if validation failed completely.
     */
    public function hasFailed(): bool
    {
        return ResultType::ERROR === $this->overallResult;
    }

    /**
     * Check if validation has warnings only.
     */
    public function hasWarnings(): bool
    {
        return ResultType::WARNING === $this->overallResult;
    }

    /**
     * Get issues by file.
     *
     * @return array<string, array<IssueData>>
     */
    public function getIssuesByFile(): array
    {
        $issuesByFile = [];

        foreach ($this->issues as $issue) {
            $issuesByFile[$issue->file][] = $issue;
        }

        return $issuesByFile;
    }

    /**
     * Get issues by validator type.
     *
     * @return array<string, array<IssueData>>
     */
    public function getIssuesByValidator(): array
    {
        $issuesByValidator = [];

        foreach ($this->issues as $issue) {
            $issuesByValidator[$issue->validatorType][] = $issue;
        }

        return $issuesByValidator;
    }

    /**
     * Get issues by severity.
     *
     * @return array<string, array<IssueData>>
     */
    public function getIssuesBySeverity(): array
    {
        $issuesBySeverity = [];

        foreach ($this->issues as $issue) {
            $severityKey = strtolower($issue->severity->toString());
            $issuesBySeverity[$severityKey][] = $issue;
        }

        return $issuesBySeverity;
    }

    /**
     * Get simple success rate as percentage.
     */
    public function getSuccessRate(): float
    {
        if (0 === $this->totalFiles) {
            return 100.0;
        }

        $filesWithoutIssues = $this->totalFiles - count($this->getIssuesByFile());

        return ($filesWithoutIssues / $this->totalFiles) * 100.0;
    }

    /**
     * Get formatted execution time.
     */
    public function getFormattedExecutionTime(): string
    {
        if ($this->executionTimeSeconds < 1.0) {
            return sprintf('%.0fms', $this->executionTimeSeconds * 1000);
        }

        return sprintf('%.2fs', $this->executionTimeSeconds);
    }

    /**
     * Convert to array format for JSON serialization.
     *
     * @return array{
     *   success: bool,
     *   overallResult: string,
     *   summary: array{
     *     totalIssues: int,
     *     totalValidators: int,
     *     totalFiles: int,
     *     executionTime: string,
     *     successRate: float
     *   },
     *   issues: array<array{
     *     file: string,
     *     messages: array<string>,
     *     parser: string,
     *     validatorType: string,
     *     severity: string,
     *     context: array<string, mixed>,
     *     line: int|null,
     *     column: int|null
     *   }>,
     *   validators: array<string>,
     *   statistics: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'overallResult' => strtolower($this->overallResult->toString()),
            'summary' => [
                'totalIssues' => $this->totalIssues,
                'totalValidators' => $this->totalValidators,
                'totalFiles' => $this->totalFiles,
                'executionTime' => $this->getFormattedExecutionTime(),
                'successRate' => $this->getSuccessRate(),
            ],
            'issues' => array_map(fn (IssueData $issue) => $issue->toArray(), $this->issues),
            'validators' => $this->validatorNames,
            'statistics' => $this->statistics,
        ];
    }

    /**
     * Convert to JSON string for external API responses.
     */
    public function toJson(int $flags = JSON_PRETTY_PRINT): string
    {
        return json_encode($this->toArray(), $flags) ?: '{}';
    }
}
