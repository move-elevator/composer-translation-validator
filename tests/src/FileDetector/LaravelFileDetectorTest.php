<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\FileDetector;

use MoveElevator\ComposerTranslationValidator\FileDetector\LaravelFileDetector;
use PHPUnit\Framework\TestCase;

final class LaravelFileDetectorTest extends TestCase
{
    public function testMapTranslationSetWithLaravelStyleFiles(): void
    {
        $detector = new LaravelFileDetector();

        $files = [
            '/path/to/resources/lang/en/messages.php',
            '/path/to/resources/lang/de/messages.php',
            '/path/to/resources/lang/en/auth.php',
            '/path/to/resources/lang/de/auth.php',
            '/path/to/resources/lang/fr/messages.php',
        ];

        $result = $detector->mapTranslationSet($files);

        $expected = [
            'messages' => [
                '/path/to/resources/lang/en/messages.php',
                '/path/to/resources/lang/de/messages.php',
                '/path/to/resources/lang/fr/messages.php',
            ],
            'auth' => [
                '/path/to/resources/lang/en/auth.php',
                '/path/to/resources/lang/de/auth.php',
            ],
        ];

        $this->assertSame($expected, $result);
    }

    public function testMapTranslationSetWithComplexLocales(): void
    {
        $detector = new LaravelFileDetector();

        $files = [
            '/app/lang/en_US/messages.php',
            '/app/lang/de_DE/messages.php',
            '/app/lang/fr-FR/messages.php',
            '/app/lang/en-GB/validation.php',
        ];

        $result = $detector->mapTranslationSet($files);

        $expected = [
            'messages' => [
                '/app/lang/en_US/messages.php',
                '/app/lang/de_DE/messages.php',
                '/app/lang/fr-FR/messages.php',
            ],
            'validation' => [
                '/app/lang/en-GB/validation.php',
            ],
        ];

        $this->assertSame($expected, $result);
    }

    public function testMapTranslationSetIgnoresNonPhpFiles(): void
    {
        $detector = new LaravelFileDetector();

        $files = [
            '/path/to/lang/en/messages.php',
            '/path/to/lang/en/messages.json',
            '/path/to/lang/en/messages.yaml',
            '/path/to/lang/en/messages.xlf',
            '/path/to/lang/de/messages.php',
        ];

        $result = $detector->mapTranslationSet($files);

        $expected = [
            'messages' => [
                '/path/to/lang/en/messages.php',
                '/path/to/lang/de/messages.php',
            ],
        ];

        $this->assertSame($expected, $result);
    }

    public function testMapTranslationSetIgnoresInvalidLanguageCodes(): void
    {
        $detector = new LaravelFileDetector();

        $files = [
            '/path/to/lang/english/messages.php', // Invalid: full language name
            '/path/to/lang/en/messages.php',      // Valid
            '/path/to/lang/123/messages.php',     // Invalid: numeric
            '/path/to/lang/e/messages.php',       // Invalid: too short
            '/path/to/lang/deutsch/messages.php', // Invalid: full language name
            '/path/to/lang/de/messages.php',      // Valid
        ];

        $result = $detector->mapTranslationSet($files);

        $expected = [
            'messages' => [
                '/path/to/lang/en/messages.php',
                '/path/to/lang/de/messages.php',
            ],
        ];

        $this->assertSame($expected, $result);
    }

    public function testMapTranslationSetWithWindowsPaths(): void
    {
        $detector = new LaravelFileDetector();

        $files = [
            'C:\\app\\resources\\lang\\en\\messages.php',
            'C:\\app\\resources\\lang\\de\\messages.php',
        ];

        $result = $detector->mapTranslationSet($files);

        $expected = [
            'messages' => [
                'C:\\app\\resources\\lang\\en\\messages.php',
                'C:\\app\\resources\\lang\\de\\messages.php',
            ],
        ];

        $this->assertSame($expected, $result);
    }

    public function testMapTranslationSetWithEmptyArray(): void
    {
        $detector = new LaravelFileDetector();

        $result = $detector->mapTranslationSet([]);

        $this->assertSame([], $result);
    }

    public function testMapTranslationSetWithNoMatchingFiles(): void
    {
        $detector = new LaravelFileDetector();

        $files = [
            '/path/to/config/app.php',
            '/path/to/models/User.php',
            '/path/to/controllers/HomeController.php',
        ];

        $result = $detector->mapTranslationSet($files);

        $this->assertSame([], $result);
    }

    public function testMapTranslationSetWithMixedStructures(): void
    {
        $detector = new LaravelFileDetector();

        $files = [
            // Laravel structure
            '/app/lang/en/messages.php',
            '/app/lang/de/messages.php',
            // Non-Laravel structure (should be ignored)
            '/app/translations/messages.en.php',
            '/app/translations/messages.de.php',
            // Laravel structure with different base path
            '/project/resources/lang/fr/auth.php',
            '/project/resources/lang/es/auth.php',
        ];

        $result = $detector->mapTranslationSet($files);

        $expected = [
            'messages' => [
                '/app/lang/en/messages.php',
                '/app/lang/de/messages.php',
            ],
            'auth' => [
                '/project/resources/lang/fr/auth.php',
                '/project/resources/lang/es/auth.php',
            ],
        ];

        $this->assertSame($expected, $result);
    }

    public function testMapTranslationSetWithSameFileNameDifferentPaths(): void
    {
        $detector = new LaravelFileDetector();

        $files = [
            '/app1/lang/en/messages.php',
            '/app1/lang/de/messages.php',
            '/app2/lang/en/messages.php',
            '/app2/lang/fr/messages.php',
        ];

        $result = $detector->mapTranslationSet($files);

        // All should be grouped under 'messages' since they have the same base filename
        $expected = [
            'messages' => [
                '/app1/lang/en/messages.php',
                '/app1/lang/de/messages.php',
                '/app2/lang/en/messages.php',
                '/app2/lang/fr/messages.php',
            ],
        ];

        $this->assertSame($expected, $result);
    }
}
