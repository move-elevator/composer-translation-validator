<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Result;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Parser\ParserCache;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

class ValidationRun
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<FileSet>                          $fileSets
     * @param array<class-string<ValidatorInterface>> $validatorClasses
     */
    public function executeFor(array $fileSets, array $validatorClasses): ValidationResult
    {
        $startTime = microtime(true);
        $validatorInstances = [];
        $validatorFileSetPairs = [];
        $overallResult = ResultType::SUCCESS;
        $filesChecked = 0;

        foreach ($fileSets as $fileSet) {
            $filesChecked += count($fileSet->getFiles());
            foreach ($validatorClasses as $validatorClass) {
                $validatorInstance = new $validatorClass($this->logger);
                $result = $validatorInstance->validate($fileSet->getFiles(), $fileSet->getParser());
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
            $parsersCached
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
                    $keys = $parser->extractKeys();
                    if (is_array($keys)) {
                        $keysChecked += count($keys);
                    }
                } catch (\Throwable) {
                    // Skip files that can't be parsed
                }
            }
        }

        return $keysChecked;
    }
}
