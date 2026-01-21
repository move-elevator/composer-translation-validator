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
