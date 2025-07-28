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

namespace MoveElevator\ComposerTranslationValidator\Tests\Validation;

use MoveElevator\ComposerTranslationValidator\Validation\ValidationOptions;
use MoveElevator\ComposerTranslationValidator\Validator\MismatchValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ValidationOptions::class)]
class ValidationOptionsTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $options = new ValidationOptions();

        $this->assertSame([], $options->onlyValidators);
        $this->assertSame([], $options->skipValidators);
        $this->assertSame([], $options->excludePatterns);
        $this->assertFalse($options->recursive);
        $this->assertFalse($options->strict);
        $this->assertFalse($options->dryRun);
        $this->assertNull($options->fileDetector);
    }

    public function testConstructorWithAllParameters(): void
    {
        $options = new ValidationOptions(
            onlyValidators: [MismatchValidator::class, MismatchValidator::class],
            skipValidators: [MismatchValidator::class],
            excludePatterns: ['*.backup'],
            recursive: true,
            strict: true,
            dryRun: true,
            fileDetector: 'CustomDetector',
        );

        $this->assertSame([MismatchValidator::class, MismatchValidator::class], $options->onlyValidators);
        $this->assertSame([MismatchValidator::class], $options->skipValidators);
        $this->assertSame(['*.backup'], $options->excludePatterns);
        $this->assertTrue($options->recursive);
        $this->assertTrue($options->strict);
        $this->assertTrue($options->dryRun);
        $this->assertSame('CustomDetector', $options->fileDetector);
    }

    public function testFromArrayWithEmptyArray(): void
    {
        $options = ValidationOptions::fromArray([]);

        $this->assertSame([], $options->onlyValidators);
        $this->assertSame([], $options->skipValidators);
        $this->assertSame([], $options->excludePatterns);
        $this->assertFalse($options->recursive);
        $this->assertFalse($options->strict);
        $this->assertFalse($options->dryRun);
        $this->assertNull($options->fileDetector);
    }

    public function testFromArrayWithStandardKeys(): void
    {
        $config = [
            'only' => [MismatchValidator::class],
            'skip' => [MismatchValidator::class],
            'exclude' => ['*.tmp'],
            'recursive' => true,
            'strict' => true,
            'dryRun' => true,
            'fileDetector' => 'TestDetector',
        ];

        $options = ValidationOptions::fromArray($config);

        $this->assertSame([MismatchValidator::class], $options->onlyValidators);
        $this->assertSame([MismatchValidator::class], $options->skipValidators);
        $this->assertSame(['*.tmp'], $options->excludePatterns);
        $this->assertTrue($options->recursive);
        $this->assertTrue($options->strict);
        $this->assertTrue($options->dryRun);
        $this->assertSame('TestDetector', $options->fileDetector);
    }

    public function testFromArrayWithAlternativeKeys(): void
    {
        $config = [
            'onlyValidators' => [MismatchValidator::class],
            'skipValidators' => [MismatchValidator::class],
            'excludePatterns' => ['*.tmp'],
            'dry-run' => true,
            'file-detector' => 'TestDetector',
        ];

        $options = ValidationOptions::fromArray($config);

        $this->assertSame([MismatchValidator::class], $options->onlyValidators);
        $this->assertSame([MismatchValidator::class], $options->skipValidators);
        $this->assertSame(['*.tmp'], $options->excludePatterns);
        $this->assertTrue($options->dryRun);
        $this->assertSame('TestDetector', $options->fileDetector);
    }

    public function testToArray(): void
    {
        $options = new ValidationOptions(
            onlyValidators: [MismatchValidator::class],
            skipValidators: [MismatchValidator::class],
            excludePatterns: ['*.backup'],
            recursive: true,
            strict: true,
            dryRun: true,
            fileDetector: 'CustomDetector',
        );

        $expected = [
            'only' => [MismatchValidator::class],
            'skip' => [MismatchValidator::class],
            'exclude' => ['*.backup'],
            'recursive' => true,
            'strict' => true,
            'dryRun' => true,
            'fileDetector' => 'CustomDetector',
        ];

        $this->assertSame($expected, $options->toArray());
    }

    public function testImmutability(): void
    {
        $options = new ValidationOptions(
            onlyValidators: [MismatchValidator::class],
            skipValidators: [MismatchValidator::class],
        );

        // Try to modify arrays (should not affect the object)
        $onlyValidators = $options->onlyValidators;
        $onlyValidators[] = MismatchValidator::class;

        $this->assertSame([MismatchValidator::class], $options->onlyValidators);
        $this->assertNotSame($onlyValidators, $options->onlyValidators);
    }
}
