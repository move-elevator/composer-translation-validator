<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025-2026 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Result;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Validator\{ResultType, ValidatorInterface};

/**
 * ValidationResult.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class ValidationResult
{
    /**
     * @param array<ValidatorInterface>                                     $validatorInstances
     * @param array<array{validator: ValidatorInterface, fileSet: FileSet}> $validatorFileSetPairs
     */
    public function __construct(
        private readonly array $validatorInstances,
        private readonly ResultType $overallResult,
        private readonly array $validatorFileSetPairs = [],
        private readonly ?ValidationStatistics $statistics = null,
    ) {}

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
     * @return array<ValidatorInterface>
     */
    public function getValidatorsWithIssues(): array
    {
        return array_filter($this->validatorInstances, fn ($validator) => $validator->hasIssues());
    }

    /**
     * @return array<ValidatorInterface>
     */
    public function getAllValidators(): array
    {
        return $this->validatorInstances;
    }

    public function getOverallResult(): ResultType
    {
        return $this->overallResult;
    }

    /**
     * @return array<array{validator: ValidatorInterface, fileSet: FileSet}>
     */
    public function getValidatorFileSetPairs(): array
    {
        return $this->validatorFileSetPairs;
    }

    public function getStatistics(): ?ValidationStatistics
    {
        return $this->statistics;
    }
}
