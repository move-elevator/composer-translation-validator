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

namespace MoveElevator\ComposerTranslationValidator\Service;

use MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig;
use MoveElevator\ComposerTranslationValidator\FileDetector\Collector;
use MoveElevator\ComposerTranslationValidator\FileDetector\DetectorInterface;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResult;
use MoveElevator\ComposerTranslationValidator\Result\ValidationRun;
use MoveElevator\ComposerTranslationValidator\Utility\ClassUtility;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorRegistry;
use Psr\Log\LoggerInterface;
use ReflectionException;

class ValidationOrchestrationService
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param array<string>                                $paths
     * @param array<string>                                $excludePatterns
     * @param array<int, class-string<ValidatorInterface>> $validators
     *
     * @throws ReflectionException
     */
    public function executeValidation(
        array $paths,
        array $excludePatterns,
        bool $recursive,
        ?DetectorInterface $fileDetector,
        array $validators,
        TranslationValidatorConfig $config,
    ): ?ValidationResult {
        if (empty($paths)) {
            return null;
        }

        $allFiles = (new Collector($this->logger))->collectFiles(
            $paths,
            $fileDetector,
            $excludePatterns,
            $recursive,
        );

        if (empty($allFiles)) {
            return null;
        }

        $fileSets = ValidationRun::createFileSetsFromArray($allFiles);

        return (new ValidationRun($this->logger))->executeFor($fileSets, $validators, $config);
    }

    public function resolveFileDetector(TranslationValidatorConfig $config): ?DetectorInterface
    {
        $configFileDetectors = $config->getFileDetectors();
        $fileDetectorClass = !empty($configFileDetectors) ? $configFileDetectors[0] : null;

        $detector = ClassUtility::instantiate(
            DetectorInterface::class,
            $this->logger,
            'file detector',
            $fileDetectorClass,
        );

        return $detector instanceof DetectorInterface ? $detector : null;
    }

    /**
     * @param array<int, class-string<ValidatorInterface>>|null $only
     * @param array<int, class-string<ValidatorInterface>>|null $skip
     *
     * @return array<int, class-string<ValidatorInterface>>
     */
    public function resolveValidators(
        ?array $only = null,
        ?array $skip = null,
        ?TranslationValidatorConfig $config = null,
    ): array {
        $configOnly = $config?->getOnly() ?? [];
        $configSkip = $config?->getSkip() ?? [];

        $resolvedOnly = !empty($only) ? $only : $configOnly;
        $resolvedSkip = !empty($skip) ? $skip : $configSkip;

        /** @var array<int, class-string<ValidatorInterface>> $result */
        $result = match (true) {
            !empty($resolvedOnly) => $resolvedOnly,
            !empty($resolvedSkip) => array_values(array_diff(ValidatorRegistry::getAvailableValidators(), $resolvedSkip)),
            default => ValidatorRegistry::getAvailableValidators(),
        };

        return $result;
    }

    /**
     * @param array<string> $inputPaths
     *
     * @return array<string>
     */
    public function resolvePaths(array $inputPaths, TranslationValidatorConfig $config): array
    {
        $configPaths = $config->getPaths();
        $paths = !empty($inputPaths) ? $inputPaths : $configPaths;

        return array_map(
            static fn ($path) => str_starts_with((string) $path, '/')
                ? $path
                : getcwd().'/'.$path,
            $paths,
        );
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $interface
     *
     * @return array<int, class-string<T>>
     */
    public function validateClassInput(
        string $interface,
        string $type,
        ?string $className = null,
    ): array {
        if (null === $className) {
            return [];
        }

        $classNames = str_contains($className, ',') ? explode(',', $className) : [$className];
        /** @var array<int, class-string<T>> $classes */
        $classes = [];

        foreach ($classNames as $name) {
            ClassUtility::instantiate(
                $interface,
                $this->logger,
                $type,
                $name,
            );
            /** @var class-string<T> $validatedName */
            $validatedName = $name;
            $classes[] = $validatedName;
        }

        return $classes;
    }
}
