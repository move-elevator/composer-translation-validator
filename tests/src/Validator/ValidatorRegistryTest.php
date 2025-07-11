<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Validator\DuplicateKeysValidator;
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
        $this->assertContains(PlaceholderConsistencyValidator::class, $validators);
        $this->assertContains(XliffSchemaValidator::class, $validators);
        $this->assertCount(5, $validators);
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
                "Class {$validator} should implement ValidatorInterface"
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
