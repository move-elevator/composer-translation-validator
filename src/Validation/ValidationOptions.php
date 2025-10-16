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

use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;

/**
 * Immutable value object representing validation options for the API.
 *
 * This replaces array-based configuration for type-safe external API usage.
 */
final class ValidationOptions
{
    /**
     * @param array<int, class-string<ValidatorInterface>> $onlyValidators  Specific validators to run (FQCN)
     * @param array<int, class-string<ValidatorInterface>> $skipValidators  Validators to skip (FQCN)
     * @param array<string>                                $excludePatterns File patterns to exclude
     * @param bool                                         $recursive       Search recursively in subdirectories
     * @param bool                                         $strict          Treat warnings as errors
     * @param bool                                         $dryRun          Run without throwing errors
     * @param string|null                                  $fileDetector    File detector class to use
     */
    public function __construct(
        public readonly array $onlyValidators = [],
        public readonly array $skipValidators = [],
        public readonly array $excludePatterns = [],
        public readonly bool $recursive = false,
        public readonly bool $strict = false,
        public readonly bool $dryRun = false,
        public readonly ?string $fileDetector = null,
    ) {}

    /**
     * Create ValidationOptions from array configuration.
     *
     * @param array<string, mixed> $config Configuration array
     */
    public static function fromArray(array $config): self
    {
        return new self(
            onlyValidators: $config['only'] ?? $config['onlyValidators'] ?? [],
            skipValidators: $config['skip'] ?? $config['skipValidators'] ?? [],
            excludePatterns: $config['exclude'] ?? $config['excludePatterns'] ?? [],
            recursive: $config['recursive'] ?? false,
            strict: $config['strict'] ?? false,
            dryRun: $config['dryRun'] ?? $config['dry-run'] ?? false,
            fileDetector: $config['fileDetector'] ?? $config['file-detector'] ?? null,
        );
    }

    /**
     * Convert to array for internal usage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'only' => $this->onlyValidators,
            'skip' => $this->skipValidators,
            'exclude' => $this->excludePatterns,
            'recursive' => $this->recursive,
            'strict' => $this->strict,
            'dryRun' => $this->dryRun,
            'fileDetector' => $this->fileDetector,
        ];
    }
}
