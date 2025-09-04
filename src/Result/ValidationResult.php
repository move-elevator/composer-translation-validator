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

namespace MoveElevator\ComposerTranslationValidator\Result;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;

/**
 * ValidationResult.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
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
