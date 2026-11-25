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

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Validator\{DuplicateKeysValidator, EmptyValuesValidator, EncodingValidator, HtmlTagValidator, KeyCountValidator, KeyDepthValidator, KeyNamingConventionValidator, MismatchValidator, PlaceholderConsistencyValidator, ValidatorRegistry, XliffSchemaValidator};
use PHPUnit\Framework\TestCase;

/**
 * ValidatorRegistryTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class ValidatorRegistryTest extends TestCase
{
    public function testGetAvailableValidators(): void
    {
        $validators = ValidatorRegistry::getAvailableValidators();

        self::assertContains(MismatchValidator::class, $validators);
        self::assertContains(DuplicateKeysValidator::class, $validators);
        self::assertContains(EmptyValuesValidator::class, $validators);
        self::assertContains(PlaceholderConsistencyValidator::class, $validators);
        self::assertContains(HtmlTagValidator::class, $validators);
        self::assertContains(KeyCountValidator::class, $validators);
        self::assertContains(KeyDepthValidator::class, $validators);
        self::assertContains(KeyNamingConventionValidator::class, $validators);
        self::assertContains(XliffSchemaValidator::class, $validators);
        self::assertContains(EncodingValidator::class, $validators);
        self::assertCount(11, $validators);
    }

    public function testGetAvailableValidatorsReturnsArray(): void
    {
        $validators = ValidatorRegistry::getAvailableValidators();

        self::assertNotEmpty($validators);
    }

    public function testGetAvailableValidatorsContainsValidClasses(): void
    {
        $validators = ValidatorRegistry::getAvailableValidators();

        foreach ($validators as $validator) {
            self::assertTrue(class_exists($validator), "Class {$validator} should exist");
            self::assertContains(
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

        self::assertSame($validators1, $validators2);
    }

    public function testGetAvailableValidatorsContainsDuplicateValuesValidator(): void
    {
        $validators = ValidatorRegistry::getAvailableValidators();

        self::assertContains(\MoveElevator\ComposerTranslationValidator\Validator\DuplicateValuesValidator::class, $validators);
    }
}
