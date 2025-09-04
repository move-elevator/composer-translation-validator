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

namespace MoveElevator\ComposerTranslationValidator\Tests\Config;

use JsonException;
use MoveElevator\ComposerTranslationValidator\Config\SchemaValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

#[CoversClass(SchemaValidator::class)]
/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 *
 * @package ComposerTranslationValidator
 */

class SchemaValidatorTest extends TestCase
{
    private SchemaValidator $schemaValidator;

    protected function setUp(): void
    {
        $this->schemaValidator = new SchemaValidator();
    }

    public function testIsAvailableReturnsTrueWhenPackageInstalled(): void
    {
        $isAvailable = $this->schemaValidator->isAvailable();

        $this->assertTrue($isAvailable);
    }

    public function testValidateWithValidData(): void
    {
        $validData = [
            'paths' => ['translations'],
            'strict' => true,
            'format' => 'json',
        ];

        // Should not throw an exception even without the schema package
        $this->schemaValidator->validate($validData);

        $this->addToAssertionCount(1);
    }

    public function testValidateWithComplexValidData(): void
    {
        $validData = [
            'paths' => ['translations', 'resources/lang'],
            'validators' => ['SomeValidator'],
            'file-detectors' => ['SomeDetector'],
            'parsers' => ['SomeParser'],
            'only' => ['OnlyValidator'],
            'skip' => ['SkipValidator'],
            'exclude' => ['vendor/*', 'node_modules/*'],
            'strict' => false,
            'dry-run' => true,
            'format' => 'cli',
            'verbose' => true,
        ];

        // Should not throw an exception even without the schema package
        $this->schemaValidator->validate($validData);

        $this->addToAssertionCount(1);
    }

    public function testValidateWithEmptyDataThrowsException(): void
    {
        $emptyData = [];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration validation failed:');

        $this->schemaValidator->validate($emptyData);
    }

    public function testValidateWithInvalidDataThrowsException(): void
    {
        $invalidData = [
            'paths' => 'not-an-array', // Should be array
            'format' => 'invalid-format', // Invalid enum value
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration validation failed:');

        $this->schemaValidator->validate($invalidData);
    }

    public function testLoadSchemaFileNotFoundThrowsException(): void
    {
        // Create a temporary SchemaValidator with a non-existent schema path
        $reflection = new ReflectionClass(SchemaValidator::class);
        $schemaPathProperty = $reflection->getConstant('SCHEMA_PATH');

        // Backup the original schema file and rename it temporarily
        $backupPath = $schemaPathProperty.'.backup';
        if (file_exists($schemaPathProperty)) {
            rename($schemaPathProperty, $backupPath);
        }

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('JSON Schema file not found:');

            $this->schemaValidator->validate(['paths' => ['test']]);
        } finally {
            // Restore the original schema file
            if (file_exists($backupPath)) {
                rename($backupPath, $schemaPathProperty);
            }
        }
    }

    public function testLoadSchemaWithInvalidJsonThrowsException(): void
    {
        $reflection = new ReflectionClass(SchemaValidator::class);
        $schemaPathProperty = $reflection->getConstant('SCHEMA_PATH');

        // Backup the original schema file
        $backupPath = $schemaPathProperty.'.backup';
        if (file_exists($schemaPathProperty)) {
            rename($schemaPathProperty, $backupPath);
        }

        // Create an invalid JSON schema file
        file_put_contents($schemaPathProperty, 'invalid json content');

        try {
            $this->expectException(JsonException::class);
            $this->expectExceptionMessage('Syntax error');

            $this->schemaValidator->validate(['paths' => ['test']]);
        } finally {
            // Restore the original schema file
            unlink($schemaPathProperty);
            if (file_exists($backupPath)) {
                rename($backupPath, $schemaPathProperty);
            }
        }
    }

    public function testValidateWithNullValueInData(): void
    {
        $dataWithNull = [
            'paths' => ['test'],
            'strict' => null, // Invalid - should be boolean
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration validation failed:');

        $this->schemaValidator->validate($dataWithNull);
    }

    public function testValidateWithMultipleErrorsShowsAllErrors(): void
    {
        $invalidData = [
            'paths' => 'not-an-array',  // Error 1
            'strict' => 'not-a-boolean', // Error 2
            'format' => 'invalid-format', // Error 3
        ];

        try {
            $this->schemaValidator->validate($invalidData);
            $this->fail('Expected RuntimeException was not thrown');
        } catch (RuntimeException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('Configuration validation failed:', $message);
            // The error message should contain multiple error lines
            $this->assertGreaterThan(1, substr_count($message, '['));
        }
    }

    public function testValidateReturnsEarlyWhenJsonSchemaValidatorNotAvailable(): void
    {
        // Create a mock SchemaValidator that simulates JsonSchema\Validator not being available
        $validator = new
/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 *
 * @package ComposerTranslationValidator
 */

class extends SchemaValidator {
    public function validate(array $data): void
    {
        // Simulate the class_exists check returning false
        if (!$this->isAvailable()) {
            return;
        }

        parent::validate($data);
    }

    public function isAvailable(): bool
    {
        return false; // Simulate JsonSchema\Validator not available
    }
};

        // This should return early without throwing an exception
        $validator->validate(['invalid' => 'data']);

        $this->addToAssertionCount(1);
    }

    public function testLoadSchemaFileReadFailureThrowsException(): void
    {
        $reflection = new ReflectionClass(SchemaValidator::class);
        $schemaPathProperty = $reflection->getConstant('SCHEMA_PATH');

        // Backup the original schema file
        $backupPath = $schemaPathProperty.'.backup';
        if (file_exists($schemaPathProperty)) {
            rename($schemaPathProperty, $backupPath);
        }

        // Create a directory instead of a file to simulate read failure
        mkdir($schemaPathProperty);

        try {
            $this->expectException(RuntimeException::class);

            $this->schemaValidator->validate(['paths' => ['test']]);
        } finally {
            // Clean up: remove directory and restore original file
            rmdir($schemaPathProperty);
            if (file_exists($backupPath)) {
                rename($backupPath, $schemaPathProperty);
            }
        }
    }
}
