<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\FileDetector\DetectorInterface;

interface ValidatorInterface
{
    /**
     * @param array<int, string> $allFiles
     */
    public function validate(DetectorInterface $fileDetector, ?string $parserClass, array $allFiles): bool;
}
