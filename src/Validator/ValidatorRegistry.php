<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025-2026 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Validator;

/**
 * ValidatorRegistry.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class ValidatorRegistry
{
    /**
     * @return array<int, class-string<ValidatorInterface>>
     */
    public static function getAvailableValidators(): array
    {
        return [
            MismatchValidator::class,
            DuplicateKeysValidator::class,
            DuplicateValuesValidator::class,
            EmptyValuesValidator::class,
            PlaceholderConsistencyValidator::class,
            HtmlTagValidator::class,
            KeyNamingConventionValidator::class,
            KeyCountValidator::class,
            KeyDepthValidator::class,
            XliffSchemaValidator::class,
            EncodingValidator::class,
        ];
    }
}
