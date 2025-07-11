<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Config;

use MoveElevator\ComposerTranslationValidator\Config\SchemaValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SchemaValidator::class)]
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

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configuration validation failed:');

        $this->schemaValidator->validate($emptyData);
    }

    public function testValidateWithInvalidDataThrowsException(): void
    {
        $invalidData = [
            'paths' => 'not-an-array', // Should be array
            'format' => 'invalid-format', // Invalid enum value
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configuration validation failed:');

        $this->schemaValidator->validate($invalidData);
    }

    public function testLoadSchemaFileNotFoundThrowsException(): void
    {
        // Create a temporary SchemaValidator with a non-existent schema path
        $reflection = new \ReflectionClass(SchemaValidator::class);
        $schemaPathProperty = $reflection->getConstant('SCHEMA_PATH');

        // Backup the original schema file and rename it temporarily
        $backupPath = $schemaPathProperty.'.backup';
        if (file_exists($schemaPathProperty)) {
            rename($schemaPathProperty, $backupPath);
        }

        try {
            $this->expectException(\RuntimeException::class);
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
        $reflection = new \ReflectionClass(SchemaValidator::class);
        $schemaPathProperty = $reflection->getConstant('SCHEMA_PATH');

        // Backup the original schema file
        $backupPath = $schemaPathProperty.'.backup';
        if (file_exists($schemaPathProperty)) {
            rename($schemaPathProperty, $backupPath);
        }

        // Create an invalid JSON schema file
        file_put_contents($schemaPathProperty, 'invalid json content');

        try {
            $this->expectException(\JsonException::class);
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

        $this->expectException(\RuntimeException::class);
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
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('Configuration validation failed:', $message);
            // The error message should contain multiple error lines
            $this->assertGreaterThan(1, substr_count($message, '['));
        }
    }
}
