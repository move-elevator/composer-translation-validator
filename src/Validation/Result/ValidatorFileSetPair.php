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

use InvalidArgumentException;
use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;

/**
 * Immutable value object representing a validator-fileset pairing.
 *
 * Replaces array{validator: ValidatorInterface, fileSet: FileSet} for type safety
 * and better API consumption. Compatible with PHP 8.1+ readonly properties.
 */
final readonly class ValidatorFileSetPair
{
    public function __construct(
        public ValidatorInterface $validator,
        public FileSet $fileSet,
    ) {}

    /**
     * Get the validator class name for identification.
     */
    public function getValidatorName(): string
    {
        return $this->validator::class;
    }

    /**
     * Check if this validator has any issues.
     */
    public function hasIssues(): bool
    {
        return $this->validator->hasIssues();
    }

    /**
     * Get the file set identifier.
     */
    public function getFileSetId(): string
    {
        return $this->fileSet->getSetKey();
    }

    /**
     * Get all files in the file set.
     *
     * @return array<string>
     */
    public function getFiles(): array
    {
        return $this->fileSet->getFiles();
    }

    /**
     * Convert to array format for backward compatibility.
     *
     * @return array{validator: ValidatorInterface, fileSet: FileSet}
     */
    public function toArray(): array
    {
        return [
            'validator' => $this->validator,
            'fileSet' => $this->fileSet,
        ];
    }

    /**
     * Create from array format for backward compatibility.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['validator']) || !isset($data['fileSet'])) {
            throw new InvalidArgumentException('Array must contain validator and fileSet keys');
        }

        if (!$data['validator'] instanceof ValidatorInterface) {
            throw new InvalidArgumentException('validator must implement ValidatorInterface');
        }

        if (!$data['fileSet'] instanceof FileSet) {
            throw new InvalidArgumentException('fileSet must be instance of FileSet');
        }

        return new self($data['validator'], $data['fileSet']);
    }
}
