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

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Validator\DuplicateKeysValidator;
use MoveElevator\ComposerTranslationValidator\Validator\EmptyValuesValidator;
use MoveElevator\ComposerTranslationValidator\Validator\EncodingValidator;
use MoveElevator\ComposerTranslationValidator\Validator\HtmlTagValidator;
use MoveElevator\ComposerTranslationValidator\Validator\KeyCountValidator;
use MoveElevator\ComposerTranslationValidator\Validator\KeyDepthValidator;
use MoveElevator\ComposerTranslationValidator\Validator\KeyNamingConventionValidator;
use MoveElevator\ComposerTranslationValidator\Validator\MismatchValidator;
use MoveElevator\ComposerTranslationValidator\Validator\PlaceholderConsistencyValidator;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorRegistry;
use MoveElevator\ComposerTranslationValidator\Validator\XliffSchemaValidator;
use PHPUnit\Framework\TestCase;

final class ValidatorRegistryTest extends TestCase
{
    public function testGetAvailableValidators(): void
    {
        $validators = ValidatorRegistry::getAvailableValidators();

        $this->assertContains(MismatchValidator::class, $validators);
        $this->assertContains(DuplicateKeysValidator::class, $validators);
        $this->assertContains(EmptyValuesValidator::class, $validators);
        $this->assertContains(PlaceholderConsistencyValidator::class, $validators);
        $this->assertContains(HtmlTagValidator::class, $validators);
        $this->assertContains(KeyCountValidator::class, $validators);
        $this->assertContains(KeyDepthValidator::class, $validators);
        $this->assertContains(KeyNamingConventionValidator::class, $validators);
        $this->assertContains(XliffSchemaValidator::class, $validators);
        $this->assertContains(EncodingValidator::class, $validators);
        $this->assertCount(11, $validators);
    }

    public function testGetAvailableValidatorsReturnsArray(): void
    {
        $validators = ValidatorRegistry::getAvailableValidators();

        $this->assertNotEmpty($validators);
    }

    public function testGetAvailableValidatorsContainsValidClasses(): void
    {
        $validators = ValidatorRegistry::getAvailableValidators();

        foreach ($validators as $validator) {
            $this->assertTrue(class_exists($validator), "Class {$validator} should exist");
            $this->assertContains(
                \MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface::class,
                class_implements($validator) ?: [],
                "Class {$validator} should implement ValidatorInterface",
            );
        }
    }

    public function testGetAvailableValidatorsConsistentOrder(): void
    {
        $validators1 = ValidatorRegistry::getAvailableValidators();
        $validators2 = ValidatorRegistry::getAvailableValidators();

        $this->assertSame($validators1, $validators2);
    }

    public function testGetAvailableValidatorsContainsDuplicateValuesValidator(): void
    {
        $validators = ValidatorRegistry::getAvailableValidators();

        $this->assertContains(\MoveElevator\ComposerTranslationValidator\Validator\DuplicateValuesValidator::class, $validators);
    }
}
