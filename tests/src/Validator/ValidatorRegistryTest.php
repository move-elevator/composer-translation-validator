<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Validator\DuplicateKeysValidator;
use MoveElevator\ComposerTranslationValidator\Validator\MismatchValidator;
use MoveElevator\ComposerTranslationValidator\Validator\SchemaValidator;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorRegistry;
use PHPUnit\Framework\TestCase;

final class ValidatorRegistryTest extends TestCase
{
    public function testGetAvailableValidators(): void
    {
        $validators = ValidatorRegistry::getAvailableValidators();

        $this->assertContains(MismatchValidator::class, $validators);
        $this->assertContains(DuplicateKeysValidator::class, $validators);
        $this->assertContains(SchemaValidator::class, $validators);
        $this->assertCount(4, $validators);
    }
}
