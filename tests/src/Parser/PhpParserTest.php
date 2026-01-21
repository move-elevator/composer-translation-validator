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

namespace MoveElevator\ComposerTranslationValidator\Tests\Parser;

use InvalidArgumentException;
use MoveElevator\ComposerTranslationValidator\Parser\PhpParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function count;
use function dirname;

/**
 * PhpParserTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class PhpParserTest extends TestCase
{
    public function testGetSupportedFileExtensions(): void
    {
        $expectedExtensions = ['php'];
        $this->assertSame($expectedExtensions, PhpParser::getSupportedFileExtensions());
    }

    public function testConstructorWithValidFile(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/success/messages.en.php';
        $parser = new PhpParser($filePath);

        $this->assertSame('messages.en.php', $parser->getFileName());
        $this->assertSame($filePath, $parser->getFilePath());
    }

    public function testConstructorWithNonExistentFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File "/non/existent/file.php" does not exist.');

        new PhpParser('/non/existent/file.php');
    }

    public function testConstructorWithInvalidExtension(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/is not a valid file/');

        // Create a temporary file with wrong extension
        $tempFile = tempnam(sys_get_temp_dir(), 'test') ?: '';
        file_put_contents($tempFile, '<?php return [];');

        try {
            new PhpParser($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function testConstructorWithInvalidPhpFile(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/fail/invalid.php';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PHP translation file must return an array');

        new PhpParser($filePath);
    }

    public function testExtractKeysFromValidFile(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/success/messages.en.php';
        $parser = new PhpParser($filePath);

        $keys = $parser->extractKeys();
        $this->assertIsArray($keys);

        $expectedKeys = [
            'welcome',
            'user.profile',
            'user.settings',
            'user.name',
            'navigation.home',
            'navigation.about',
            'navigation.contact',
            'greeting',
            'status.active',
            'status.inactive',
        ];

        $this->assertCount(count($expectedKeys), $keys);
        foreach ($expectedKeys as $expectedKey) {
            $this->assertContains($expectedKey, $keys);
        }
    }

    public function testGetContentByKeyWithValidKeys(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/success/messages.en.php';
        $parser = new PhpParser($filePath);

        $this->assertSame('Welcome to our application!', $parser->getContentByKey('welcome'));
        $this->assertSame('User Profile', $parser->getContentByKey('user.profile'));
        $this->assertSame('Username: %username%', $parser->getContentByKey('user.name'));
        $this->assertSame('Home', $parser->getContentByKey('navigation.home'));
        $this->assertSame('Contact {email}', $parser->getContentByKey('navigation.contact'));
        $this->assertSame('Hello %name%! Welcome to {site}', $parser->getContentByKey('greeting'));
        $this->assertSame('Active', $parser->getContentByKey('status.active'));
    }

    public function testGetContentByKeyWithInvalidKeys(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/success/messages.en.php';
        $parser = new PhpParser($filePath);

        $this->assertNull($parser->getContentByKey('nonexistent'));
        $this->assertNull($parser->getContentByKey('user.nonexistent'));
        $this->assertNull($parser->getContentByKey('navigation.nonexistent.deep'));
    }

    public function testGetContentByKeyIgnoresAttributeParameter(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/success/messages.en.php';
        $parser = new PhpParser($filePath);

        // The attribute parameter should be ignored for PHP files
        $this->assertSame('Welcome to our application!', $parser->getContentByKey('welcome'));
        $this->assertSame('Welcome to our application!', $parser->getContentByKey('welcome'));
        $this->assertSame('Welcome to our application!', $parser->getContentByKey('welcome'));
    }

    public function testGetLanguageFromSymfonyStyleFilename(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/success/messages.en.php';
        $parser = new PhpParser($filePath);

        // This file follows Symfony pattern: messages.en.php
        $this->assertSame('en', $parser->getLanguage());
    }

    public function testGetLanguageFromLaravelStyleDirectory(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/laravel/en/messages.php';
        $parser = new PhpParser($filePath);

        $this->assertSame('en', $parser->getLanguage());
    }

    public function testGetLanguageFromLaravelStyleDirectoryGerman(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/laravel/de/messages.php';
        $parser = new PhpParser($filePath);

        $this->assertSame('de', $parser->getLanguage());
    }

    public function testGetLanguageReturnsEmptyWhenNoPatternMatches(): void
    {
        // Create a file that doesn't match any pattern
        $tempFile = tempnam(sys_get_temp_dir(), 'nomatch_').'.php';
        file_put_contents($tempFile, '<?php return ["test" => "value"];');

        try {
            $parser = new PhpParser($tempFile);
            $this->assertSame('', $parser->getLanguage());
        } finally {
            unlink($tempFile);
        }
    }

    public function testGetFileDirectory(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/success/messages.en.php';
        $parser = new PhpParser($filePath);

        $expectedDirectory = dirname($filePath).\DIRECTORY_SEPARATOR;
        $this->assertSame($expectedDirectory, $parser->getFileDirectory());
    }

    public function testGetFilePath(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/success/messages.en.php';
        $parser = new PhpParser($filePath);

        $this->assertSame($filePath, $parser->getFilePath());
    }

    public function testGetFileName(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/success/messages.en.php';
        $parser = new PhpParser($filePath);

        $this->assertSame('messages.en.php', $parser->getFileName());
    }

    public function testHandlesComplexNestedArrays(): void
    {
        // Create a complex nested array file
        $complexArray = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'deep' => 'Deep nested value',
                    ],
                ],
                'simple' => 'Simple value',
            ],
            'root' => 'Root value',
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'complex_').'.php';
        file_put_contents($tempFile, '<?php return '.var_export($complexArray, true).';');

        try {
            $parser = new PhpParser($tempFile);

            $keys = $parser->extractKeys();
            if (null !== $keys) {
                $this->assertContains('level1.level2.level3.deep', $keys);
                $this->assertContains('level1.simple', $keys);
                $this->assertContains('root', $keys);
            }

            $this->assertSame('Deep nested value', $parser->getContentByKey('level1.level2.level3.deep'));
            $this->assertSame('Simple value', $parser->getContentByKey('level1.simple'));
            $this->assertSame('Root value', $parser->getContentByKey('root'));
        } finally {
            unlink($tempFile);
        }
    }

    public function testHandlesEmptyArray(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'empty_').'.php';
        file_put_contents($tempFile, '<?php return [];');

        try {
            $parser = new PhpParser($tempFile);

            $keys = $parser->extractKeys();
            $this->assertSame([], $keys);
        } finally {
            unlink($tempFile);
        }
    }

    public function testHandlesArrayWithNonStringValues(): void
    {
        $arrayWithMixed = [
            'string_value' => 'This is a string',
            'numeric_value' => 123,
            'boolean_value' => true,
            'null_value' => null,
            'array_value' => ['nested' => 'value'],
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'mixed_').'.php';
        file_put_contents($tempFile, '<?php return '.var_export($arrayWithMixed, true).';');

        try {
            $parser = new PhpParser($tempFile);

            $keys = $parser->extractKeys();
            if (null !== $keys) {
                $this->assertContains('string_value', $keys);
                $this->assertContains('numeric_value', $keys);
                $this->assertContains('boolean_value', $keys);
                $this->assertContains('null_value', $keys);
                $this->assertContains('array_value.nested', $keys);
            }

            $this->assertSame('This is a string', $parser->getContentByKey('string_value'));
            $this->assertNull($parser->getContentByKey('numeric_value')); // Non-string values return null
            $this->assertNull($parser->getContentByKey('boolean_value'));
            $this->assertNull($parser->getContentByKey('null_value'));
            $this->assertSame('value', $parser->getContentByKey('array_value.nested'));
        } finally {
            unlink($tempFile);
        }
    }
}
