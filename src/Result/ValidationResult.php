<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Result;

use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;

class ValidationResult
{
    /**
     * @param array<ValidatorInterface>                                                                                             $validatorInstances
     * @param array<array{validator: ValidatorInterface, fileSet: \MoveElevator\ComposerTranslationValidator\FileDetector\FileSet}> $validatorFileSetPairs
     */
    public function __construct(
        private readonly array $validatorInstances,
        private readonly ResultType $overallResult,
        private readonly array $validatorFileSetPairs = [],
    ) {
    }

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
     * @return array<array{validator: ValidatorInterface, fileSet: \MoveElevator\ComposerTranslationValidator\FileDetector\FileSet}>
     */
    public function getValidatorFileSetPairs(): array
    {
        return $this->validatorFileSetPairs;
    }

    /**
     * @return array<class-string<ValidatorInterface>, array<string, array<string, array<mixed>>>>
     */
    public function toLegacyArray(): array
    {
        $issues = [];

        if (!empty($this->validatorFileSetPairs)) {
            // Use the validator-fileSet pairs for accurate path information
            foreach ($this->validatorFileSetPairs as $pair) {
                $validator = $pair['validator'];
                $fileSet = $pair['fileSet'];

                if ($validator->hasIssues()) {
                    $validatorClass = $validator::class;
                    $path = $fileSet->getPath();
                    $setKey = $fileSet->getSetKey();

                    if (!isset($issues[$validatorClass])) {
                        $issues[$validatorClass] = [];
                    }
                    if (!isset($issues[$validatorClass][$path])) {
                        $issues[$validatorClass][$path] = [];
                    }
                    if (!isset($issues[$validatorClass][$path][$setKey])) {
                        $issues[$validatorClass][$path][$setKey] = [];
                    }

                    foreach ($validator->getIssues() as $issue) {
                        $issues[$validatorClass][$path][$setKey][] = $issue->toArray();
                    }
                }
            }
        } else {
            // Fallback to old behavior for backward compatibility
            foreach ($this->validatorInstances as $validator) {
                if ($validator->hasIssues()) {
                    $validatorClass = $validator::class;
                    if (!isset($issues[$validatorClass])) {
                        $issues[$validatorClass] = [];
                    }
                    foreach ($validator->getIssues() as $issue) {
                        $issues[$validatorClass][''][''][] = $issue->toArray();
                    }
                }
            }
        }

        return $issues;
    }
}
