<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Result;

interface ValidationResultRendererInterface
{
    /**
     * Render validation results and return command exit code.
     */
    public function render(ValidationResult $validationResult): int;
}
