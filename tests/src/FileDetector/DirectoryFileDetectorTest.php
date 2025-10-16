<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Tests\FileDetector;

use MoveElevator\ComposerTranslationValidator\FileDetector\DirectoryFileDetector;
use PHPUnit\Framework\TestCase;

/**
 * DirectoryFileDetectorTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class DirectoryFileDetectorTest extends TestCase
{
    public function testMapTranslationSetWithDirectoryBasedFiles(): void
    {
        $detector = new DirectoryFileDetector();

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
        $detector = new DirectoryFileDetector();

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

    public function testMapTranslationSetWithMixedFileFormats(): void
    {
        $detector = new DirectoryFileDetector();

        $files = [
            '/path/to/lang/en/messages.php',
            '/path/to/lang/de/messages.php',
            '/path/to/lang/en/auth.json',
            '/path/to/lang/de/auth.json',
            '/path/to/lang/en/validation.yaml',
            '/path/to/lang/de/validation.yml',
            '/path/to/lang/en/errors.xlf',
            '/path/to/lang/de/errors.xliff',
        ];

        $result = $detector->mapTranslationSet($files);

        $expected = [
            'messages' => [
                '/path/to/lang/en/messages.php',
                '/path/to/lang/de/messages.php',
            ],
            'auth' => [
                '/path/to/lang/en/auth.json',
                '/path/to/lang/de/auth.json',
            ],
            'validation' => [
                '/path/to/lang/en/validation.yaml',
                '/path/to/lang/de/validation.yml',
            ],
            'errors' => [
                '/path/to/lang/en/errors.xlf',
                '/path/to/lang/de/errors.xliff',
            ],
        ];

        $this->assertSame($expected, $result);
    }

    public function testMapTranslationSetIgnoresUnsupportedFiles(): void
    {
        $detector = new DirectoryFileDetector();

        $files = [
            '/path/to/lang/en/messages.php',    // Supported
            '/path/to/lang/en/config.ini',      // Unsupported
            '/path/to/lang/en/data.xml',        // Unsupported
            '/path/to/lang/en/readme.txt',      // Unsupported
            '/path/to/lang/de/messages.php',    // Supported
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
        $detector = new DirectoryFileDetector();

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
        $detector = new DirectoryFileDetector();

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
        $detector = new DirectoryFileDetector();

        $result = $detector->mapTranslationSet([]);

        $this->assertSame([], $result);
    }

    public function testMapTranslationSetWithNoMatchingFiles(): void
    {
        $detector = new DirectoryFileDetector();

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
        $detector = new DirectoryFileDetector();

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
        $detector = new DirectoryFileDetector();

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

    public function testMapTranslationSetWithInvalidPaths(): void
    {
        $detector = new DirectoryFileDetector();

        $files = [
            'messages.php',        // No directory structure
            '/messages.php',       // Only one path segment
            '/lang/en/messages.php', // Valid
            '',                    // Empty path
        ];

        $result = $detector->mapTranslationSet($files);

        $expected = [
            'messages' => [
                '/lang/en/messages.php',
            ],
        ];

        $this->assertSame($expected, $result);
    }
}
