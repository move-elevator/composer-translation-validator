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
use MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;

/**
 * Enhanced ValidationResult using typed value objects instead of arrays.
 *
 * Provides type-safe API for external consumption while maintaining
 * backward compatibility. Compatible with PHP 8.1+ readonly properties.
 */
final readonly class EnhancedValidationResult
{
    /**
     * @param array<ValidatorInterface>   $validatorInstances
     * @param array<ValidatorFileSetPair> $validatorFileSetPairs
     */
    public function __construct(
        public array $validatorInstances,
        public ResultType $overallResult,
        public array $validatorFileSetPairs = [],
        public ?ValidationStatistics $statistics = null,
    ) {}

    /**
     * Create from legacy ValidationResult.
     */
    public static function fromValidationResult(ValidationResult $result): self
    {
        $pairs = [];
        foreach ($result->getValidatorFileSetPairs() as $pairArray) {
            $pairs[] = ValidatorFileSetPair::fromArray($pairArray);
        }

        return new self(
            validatorInstances: $result->getAllValidators(),
            overallResult: $result->getOverallResult(),
            validatorFileSetPairs: $pairs,
            statistics: $result->getStatistics(),
        );
    }

    /**
     * Convert to legacy ValidationResult for backward compatibility.
     */
    public function toLegacyValidationResult(): ValidationResult
    {
        $pairArrays = [];
        foreach ($this->validatorFileSetPairs as $pair) {
            $pairArrays[] = $pair->toArray();
        }

        return new ValidationResult(
            $this->validatorInstances,
            $this->overallResult,
            $pairArrays,
            $this->statistics,
        );
    }

    /**
     * Check if validation has any issues.
     */
    public function hasIssues(): bool
    {
        foreach ($this->validatorInstances as $validator) {
            if ($validator->hasIssues()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get validators that have issues (type-safe).
     *
     * @return array<ValidatorInterface>
     */
    public function getValidatorsWithIssues(): array
    {
        return array_filter($this->validatorInstances, fn ($validator) => $validator->hasIssues());
    }

    /**
     * Get all validators (type-safe).
     *
     * @return array<ValidatorInterface>
     */
    public function getAllValidators(): array
    {
        return $this->validatorInstances;
    }

    /**
     * Get typed validator-fileset pairs.
     *
     * @return array<ValidatorFileSetPair>
     */
    public function getValidatorFileSetPairs(): array
    {
        return $this->validatorFileSetPairs;
    }

    /**
     * Get validator-fileset pairs that have issues.
     *
     * @return array<ValidatorFileSetPair>
     */
    public function getValidatorFileSetPairsWithIssues(): array
    {
        return array_filter($this->validatorFileSetPairs, fn ($pair) => $pair->hasIssues());
    }

    /**
     * Get all structured issue data.
     *
     * @return array<IssueData>
     */
    public function getAllIssues(): array
    {
        $issues = [];

        foreach ($this->validatorFileSetPairs as $pair) {
            if ($pair->hasIssues()) {
                foreach ($pair->validator->getIssues() as $issue) {
                    $issues[] = IssueData::fromIssue($issue, $this->overallResult);
                }
            }
        }

        return $issues;
    }

    /**
     * Get issues grouped by file.
     *
     * @return array<string, array<IssueData>>
     */
    public function getIssuesByFile(): array
    {
        $issuesByFile = [];

        foreach ($this->getAllIssues() as $issue) {
            $issuesByFile[$issue->file][] = $issue;
        }

        return $issuesByFile;
    }

    /**
     * Get issues grouped by validator.
     *
     * @return array<string, array<IssueData>>
     */
    public function getIssuesByValidator(): array
    {
        $issuesByValidator = [];

        foreach ($this->getAllIssues() as $issue) {
            $issuesByValidator[$issue->validatorType][] = $issue;
        }

        return $issuesByValidator;
    }

    /**
     * Get validation summary for API consumption.
     */
    public function getSummary(): ValidationSummary
    {
        return ValidationSummary::fromValidationResult($this->toLegacyValidationResult());
    }

    /**
     * Get overall result type.
     */
    public function getOverallResult(): ResultType
    {
        return $this->overallResult;
    }

    /**
     * Get validation statistics.
     */
    public function getStatistics(): ?ValidationStatistics
    {
        return $this->statistics;
    }

    /**
     * Get total issue count.
     */
    public function getTotalIssueCount(): int
    {
        return count($this->getAllIssues());
    }

    /**
     * Get total validator count.
     */
    public function getTotalValidatorCount(): int
    {
        return count($this->validatorInstances);
    }

    /**
     * Get total file count from statistics.
     */
    public function getTotalFileCount(): int
    {
        return $this->statistics?->getFilesChecked() ?? 0;
    }

    /**
     * Check if result indicates success (no issues).
     */
    public function isSuccess(): bool
    {
        return !$this->hasIssues();
    }

    /**
     * Check if result indicates failure (has errors).
     */
    public function isFailure(): bool
    {
        return ResultType::ERROR === $this->overallResult;
    }

    /**
     * Check if result has only warnings.
     */
    public function hasOnlyWarnings(): bool
    {
        return ResultType::WARNING === $this->overallResult;
    }
}
