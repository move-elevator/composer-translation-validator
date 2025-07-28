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

use InvalidArgumentException;
use MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResult;
use MoveElevator\ComposerTranslationValidator\Service\ValidationOrchestrationService;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorRegistry;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Main API implementation for programmatic translation validation.
 * 
 * This class provides the primary entry point for external packages
 * to validate translation files programmatically.
 */
final class ValidationEngine implements ValidationEngineInterface
{
    public function __construct(
        private readonly ValidationOrchestrationService $orchestrationService,
        private readonly LoggerInterface $logger,
    ) {}

    public function validatePaths(array $paths, array $options = []): ?ValidationResult
    {
        if (empty($paths)) {
            throw new InvalidArgumentException('Paths array cannot be empty');
        }

        $validationOptions = ValidationOptions::fromArray($options);
        $config = $this->createConfigFromOptions($validationOptions);

        // Resolve absolute paths
        $absolutePaths = $this->orchestrationService->resolvePaths($paths, $config);
        
        // Resolve file detector
        $fileDetector = $this->orchestrationService->resolveFileDetector($config);
        
        // Resolve validators
        $validators = $this->orchestrationService->resolveValidators(
            $validationOptions->onlyValidators,
            $validationOptions->skipValidators,
            $config
        );

        try {
            return $this->orchestrationService->executeValidation(
                $absolutePaths,
                $validationOptions->excludePatterns,
                $validationOptions->recursive,
                $fileDetector,
                $validators,
                $config
            );
        } catch (\Throwable $e) {
            throw new RuntimeException(
                sprintf('Validation failed: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    public function validateProject(string $projectPath, array $configuration = []): ?ValidationResult
    {
        if (empty($projectPath)) {
            throw new InvalidArgumentException('Project path cannot be empty');
        }

        if (!is_dir($projectPath)) {
            throw new InvalidArgumentException(
                sprintf('Project path "%s" is not a valid directory', $projectPath)
            );
        }

        // Use project path as single path to validate
        $paths = [rtrim($projectPath, '/')];
        
        // Enable recursive by default for project validation
        $options = array_merge(['recursive' => true], $configuration);

        return $this->validatePaths($paths, $options);
    }

    public function getAvailableValidators(): array
    {
        return ValidatorRegistry::getAvailableValidators();
    }

    public function isReady(): bool
    {
        try {
            // Check if we have validators available
            $validators = $this->getAvailableValidators();
            if (empty($validators)) {
                return false;
            }

            // Check if orchestration service is properly initialized
            if (!isset($this->orchestrationService)) {
                return false;
            }

            // Check if logger is available
            if (!isset($this->logger)) {
                return false;
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Create configuration from validation options.
     *
     * @param ValidationOptions $options
     * @return TranslationValidatorConfig
     */
    private function createConfigFromOptions(ValidationOptions $options): TranslationValidatorConfig
    {
        $config = new TranslationValidatorConfig();
        
        if (!empty($options->onlyValidators)) {
            $config->setOnly($options->onlyValidators);
        }
        
        if (!empty($options->skipValidators)) {
            $config->setSkip($options->skipValidators);
        }
        
        if (!empty($options->excludePatterns)) {
            $config->setExclude($options->excludePatterns);
        }
        
        if ($options->fileDetector !== null) {
            $config->setFileDetectors([$options->fileDetector]);
        }
        
        $config->setDryRun($options->dryRun);
        $config->setStrict($options->strict);

        return $config;
    }
}