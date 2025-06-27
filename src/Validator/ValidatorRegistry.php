<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Validator;

class ValidatorRegistry
{
    /**
     * @return array<int, class-string<ValidatorInterface>>
     */
    public static function getAvailableValidators(): array
    {
        return [
            MismatchValidator::class,
            DuplicatesValidator::class,
            SchemaValidator::class,
        ];
    }
}
