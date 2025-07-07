<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Result;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
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
        $validatorInstances = [];
        $validatorFileSetPairs = [];
        $overallResult = ResultType::SUCCESS;

        // Create fresh validator instances for each file set (original behavior)
        foreach ($fileSets as $fileSet) {
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

        return new ValidationResult($validatorInstances, $overallResult, $validatorFileSetPairs);
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
}
