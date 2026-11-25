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
        self::assertSame($expectedExtensions, PhpParser::getSupportedFileExtensions());
    }

    public function testConstructorWithValidFile(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/success/messages.en.php';
        $parser = new PhpParser($filePath);

        self::assertSame('messages.en.php', $parser->getFileName());
        self::assertSame($filePath, $parser->getFilePath());
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
        self::assertIsArray($keys);

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

        self::assertCount(count($expectedKeys), $keys);
        foreach ($expectedKeys as $expectedKey) {
            self::assertContains($expectedKey, $keys);
        }
    }

    public function testExtractKeysIsMemoized(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/success/messages.en.php';
        $parser = new PhpParser($filePath);

        $first = $parser->extractKeys();
        $second = $parser->extractKeys();

        self::assertSame($first, $second);
    }

    public function testGetContentByKeyWithValidKeys(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/success/messages.en.php';
        $parser = new PhpParser($filePath);

        self::assertSame('Welcome to our application!', $parser->getContentByKey('welcome'));
        self::assertSame('User Profile', $parser->getContentByKey('user.profile'));
        self::assertSame('Username: %username%', $parser->getContentByKey('user.name'));
        self::assertSame('Home', $parser->getContentByKey('navigation.home'));
        self::assertSame('Contact {email}', $parser->getContentByKey('navigation.contact'));
        self::assertSame('Hello %name%! Welcome to {site}', $parser->getContentByKey('greeting'));
        self::assertSame('Active', $parser->getContentByKey('status.active'));
    }

    public function testGetContentByKeyWithInvalidKeys(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/success/messages.en.php';
        $parser = new PhpParser($filePath);

        self::assertNull($parser->getContentByKey('nonexistent'));
        self::assertNull($parser->getContentByKey('user.nonexistent'));
        self::assertNull($parser->getContentByKey('navigation.nonexistent.deep'));
    }

    public function testGetContentByKeyIgnoresAttributeParameter(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/success/messages.en.php';
        $parser = new PhpParser($filePath);

        // The attribute parameter should be ignored for PHP files
        self::assertSame('Welcome to our application!', $parser->getContentByKey('welcome'));
        self::assertSame('Welcome to our application!', $parser->getContentByKey('welcome'));
        self::assertSame('Welcome to our application!', $parser->getContentByKey('welcome'));
    }

    public function testGetLanguageFromSymfonyStyleFilename(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/success/messages.en.php';
        $parser = new PhpParser($filePath);

        // This file follows Symfony pattern: messages.en.php
        self::assertSame('en', $parser->getLanguage());
    }

    public function testGetLanguageFromLaravelStyleDirectory(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/laravel/en/messages.php';
        $parser = new PhpParser($filePath);

        self::assertSame('en', $parser->getLanguage());
    }

    public function testGetLanguageFromLaravelStyleDirectoryGerman(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/laravel/de/messages.php';
        $parser = new PhpParser($filePath);

        self::assertSame('de', $parser->getLanguage());
    }

    public function testGetLanguageReturnsEmptyWhenNoPatternMatches(): void
    {
        // Create a file that doesn't match any pattern
        $tempFile = tempnam(sys_get_temp_dir(), 'nomatch_').'.php';
        file_put_contents($tempFile, '<?php return ["test" => "value"];');

        try {
            $parser = new PhpParser($tempFile);
            self::assertSame('', $parser->getLanguage());
        } finally {
            unlink($tempFile);
        }
    }

    public function testGetFileDirectory(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/success/messages.en.php';
        $parser = new PhpParser($filePath);

        $expectedDirectory = dirname($filePath).\DIRECTORY_SEPARATOR;
        self::assertSame($expectedDirectory, $parser->getFileDirectory());
    }

    public function testGetFilePath(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/success/messages.en.php';
        $parser = new PhpParser($filePath);

        self::assertSame($filePath, $parser->getFilePath());
    }

    public function testGetFileName(): void
    {
        $filePath = __DIR__.'/../Fixtures/translations/php/success/messages.en.php';
        $parser = new PhpParser($filePath);

        self::assertSame('messages.en.php', $parser->getFileName());
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
                self::assertContains('level1.level2.level3.deep', $keys);
                self::assertContains('level1.simple', $keys);
                self::assertContains('root', $keys);
            }

            self::assertSame('Deep nested value', $parser->getContentByKey('level1.level2.level3.deep'));
            self::assertSame('Simple value', $parser->getContentByKey('level1.simple'));
            self::assertSame('Root value', $parser->getContentByKey('root'));
        } finally {
            unlink($tempFile);
        }
    }

    public function testDoesNotExecuteCodeInFile(): void
    {
        $marker = (tempnam(sys_get_temp_dir(), 'marker_') ?: '').'.marker';

        $tempFile = tempnam(sys_get_temp_dir(), 'malicious_').'.php';
        file_put_contents(
            $tempFile,
            "<?php file_put_contents('".$marker."', 'pwned'); return ['key' => 'value'];",
        );

        try {
            $parser = new PhpParser($tempFile);

            self::assertFileDoesNotExist($marker, 'PHP translation file must not be executed');
            self::assertSame('value', $parser->getContentByKey('key'));
        } finally {
            unlink($tempFile);
            if (file_exists($marker)) {
                unlink($marker);
            }
        }
    }

    public function testRejectsFunctionCallValues(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'call_').'.php';
        file_put_contents($tempFile, "<?php return ['key' => strtoupper('value')];");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unsupported expression');

        try {
            new PhpParser($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function testRejectsListArrayWithImplicitKeys(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'list_').'.php';
        file_put_contents($tempFile, '<?php return ["a", "b"];');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('string or integer keys');

        try {
            new PhpParser($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function testRejectsUnsupportedConstant(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'const_').'.php';
        file_put_contents($tempFile, '<?php return ["key" => PHP_EOL];');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unsupported constant');

        try {
            new PhpParser($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function testRejectsFloatKey(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'floatkey_').'.php';
        file_put_contents($tempFile, '<?php return [1.5 => "value"];');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('string or integer keys');

        try {
            new PhpParser($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function testHandlesBooleanFalseValue(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'false_').'.php';
        file_put_contents($tempFile, '<?php return ["flag" => false];');

        try {
            $parser = new PhpParser($tempFile);
            $keys = $parser->extractKeys();
            self::assertSame(['flag'], $keys);
        } finally {
            unlink($tempFile);
        }
    }

    public function testRejectsFileWithoutReturn(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'noreturn_').'.php';
        file_put_contents($tempFile, '<?php $x = 1;');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must return an array');

        try {
            new PhpParser($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function testRejectsNegatedNonNumber(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'negstr_').'.php';
        file_put_contents($tempFile, '<?php return ["key" => -"x"];');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unsupported expression');

        try {
            new PhpParser($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function testHandlesNegativeNumbers(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'negative_').'.php';
        file_put_contents($tempFile, '<?php return ["count" => -5, "ratio" => -1.5];');

        try {
            $parser = new PhpParser($tempFile);

            $keys = $parser->extractKeys();
            self::assertIsArray($keys);
            self::assertContains('count', $keys);
            self::assertContains('ratio', $keys);
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
            self::assertSame([], $keys);
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
                self::assertContains('string_value', $keys);
                self::assertContains('numeric_value', $keys);
                self::assertContains('boolean_value', $keys);
                self::assertContains('null_value', $keys);
                self::assertContains('array_value.nested', $keys);
            }

            self::assertSame('This is a string', $parser->getContentByKey('string_value'));
            self::assertNull($parser->getContentByKey('numeric_value')); // Non-string values return null
            self::assertNull($parser->getContentByKey('boolean_value'));
            self::assertNull($parser->getContentByKey('null_value'));
            self::assertSame('value', $parser->getContentByKey('array_value.nested'));
        } finally {
            unlink($tempFile);
        }
    }
}
