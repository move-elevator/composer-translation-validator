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

/**
 * Core API interface for programmatic translation validation.
 * 
 * This interface provides the main entry point for external packages
 * (e.g., TYPO3 Commands) to validate translation files programmatically.
 */
interface ValidationEngineInterface
{
    /**
     * Validate translation files in given paths.
     *
     * @param array<string> $paths Translation file paths to validate
     * @param array<string, mixed> $options Validation options
     * @return ValidationResult|null Validation result or null if no files found
     * 
     * @throws \InvalidArgumentException If paths are invalid
     * @throws \RuntimeException If validation fails due to system errors
     */
    public function validatePaths(array $paths, array $options = []): ?ValidationResult;

    /**
     * Validate specific translation project with configuration.
     *
     * @param string $projectPath Root path of translation project
     * @param array<string, mixed> $configuration Validation configuration
     * @return ValidationResult|null Validation result or null if no files found
     * 
     * @throws \InvalidArgumentException If project path is invalid
     * @throws \RuntimeException If validation fails due to system errors
     */
    public function validateProject(string $projectPath, array $configuration = []): ?ValidationResult;

    /**
     * Get list of available validators.
     *
     * @return array<string> List of available validator class names
     */
    public function getAvailableValidators(): array;

    /**
     * Check if validation engine is properly configured.
     *
     * @return bool True if engine is ready to validate
     */
    public function isReady(): bool;
}