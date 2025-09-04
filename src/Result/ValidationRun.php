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

use MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig;
use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Parser\ParserCache;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @package ComposerTranslationValidator
 */

class ValidationRun
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param array<FileSet>                          $fileSets
     * @param array<class-string<ValidatorInterface>> $validatorClasses
     */
    public function executeFor(array $fileSets, array $validatorClasses, ?TranslationValidatorConfig $config = null): ValidationResult
    {
        $startTime = microtime(true);
        $validatorInstances = [];
        $validatorFileSetPairs = [];
        $overallResult = ResultType::SUCCESS;
        $filesChecked = 0;

        foreach ($fileSets as $fileSet) {
            $filesChecked += count($fileSet->getFiles());
            foreach ($validatorClasses as $validatorClass) {
                // Create a new validator instance for each FileSet to ensure isolation
                $validatorInstance = new $validatorClass($this->logger);

                // Pass config to validator if it supports it
                if (null !== $config && method_exists($validatorInstance, 'setConfig')) {
                    $validatorInstance->setConfig($config);
                }

                /** @var class-string<\MoveElevator\ComposerTranslationValidator\Parser\ParserInterface> $parserClass */
                $parserClass = $fileSet->getParser();
                $result = $validatorInstance->validate($fileSet->getFiles(), $parserClass);
                if (!empty($result)) {
                    $overallResult = $overallResult->max($validatorInstance->resultTypeOnValidationFailure());
                    $validatorInstances[] = $validatorInstance;
                    $validatorFileSetPairs[] = [
                        'validator' => $validatorInstance,
                        'fileSet' => $fileSet,
                    ];
                }
            }
        }

        $keysChecked = $this->countKeysChecked($fileSets);

        $validatorsRun = count($validatorClasses);

        // Get cache statistics before clearing cache
        $cacheStats = ParserCache::getCacheStats();
        $parsersCached = $cacheStats['cached_parsers'];

        $executionTime = microtime(true) - $startTime;
        $statistics = new ValidationStatistics(
            $executionTime,
            $filesChecked,
            $keysChecked,
            $validatorsRun,
            $parsersCached,
        );

        $validationResult = new ValidationResult($validatorInstances, $overallResult, $validatorFileSetPairs, $statistics);
        ParserCache::clear();

        return $validationResult;
    }

    /**
     * @param array<string, array<string, array<string, array<string>>>> $allFiles
     *
     * @return array<FileSet>
     */
    public static function createFileSetsFromArray(array $allFiles): array
    {
        $fileSets = [];

        foreach ($allFiles as $parser => $paths) {
            foreach ($paths as $path => $translationSets) {
                foreach ($translationSets as $setKey => $files) {
                    $fileSets[] = new FileSet($parser, $path, $setKey, $files);
                }
            }
        }

        return $fileSets;
    }

    /**
     * @param array<FileSet> $fileSets
     */
    private function countKeysChecked(array $fileSets): int
    {
        $keysChecked = 0;

        foreach ($fileSets as $fileSet) {
            $parserClass = $fileSet->getParser();

            foreach ($fileSet->getFiles() as $file) {
                try {
                    $parser = ParserCache::get($file, $parserClass);
                    if (false === $parser) {
                        continue;
                    }
                    $keys = $parser->extractKeys();
                    if (is_array($keys)) {
                        $keysChecked += count($keys);
                    }
                } catch (Throwable) {
                    // Skip files that can't be parsed
                }
            }
        }

        return $keysChecked;
    }
}
