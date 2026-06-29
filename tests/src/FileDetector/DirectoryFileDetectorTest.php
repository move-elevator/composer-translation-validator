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

namespace MoveElevator\ComposerTranslationValidator\Tests\FileDetector;

use MoveElevator\ComposerTranslationValidator\FileDetector\DirectoryFileDetector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * DirectoryFileDetectorTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class DirectoryFileDetectorTest extends TestCase
{
    /**
     * @param array<int, string>                $files
     * @param array<string, array<int, string>> $expected
     */
    #[DataProvider('mapTranslationSetProvider')]
    public function testMapTranslationSet(array $files, array $expected): void
    {
        $this->assertSame($expected, (new DirectoryFileDetector())->mapTranslationSet($files));
    }

    /**
     * @return iterable<string, array{array<int, string>, array<string, array<int, string>>}>
     */
    public static function mapTranslationSetProvider(): iterable
    {
        yield 'directory based files' => [
            [
                '/path/to/resources/lang/en/messages.php',
                '/path/to/resources/lang/de/messages.php',
                '/path/to/resources/lang/en/auth.php',
                '/path/to/resources/lang/de/auth.php',
                '/path/to/resources/lang/fr/messages.php',
            ],
            [
                'messages' => [
                    '/path/to/resources/lang/en/messages.php',
                    '/path/to/resources/lang/de/messages.php',
                    '/path/to/resources/lang/fr/messages.php',
                ],
                'auth' => [
                    '/path/to/resources/lang/en/auth.php',
                    '/path/to/resources/lang/de/auth.php',
                ],
            ],
        ];

        yield 'complex locales' => [
            [
                '/app/lang/en_US/messages.php',
                '/app/lang/de_DE/messages.php',
                '/app/lang/fr-FR/messages.php',
                '/app/lang/en-GB/validation.php',
            ],
            [
                'messages' => [
                    '/app/lang/en_US/messages.php',
                    '/app/lang/de_DE/messages.php',
                    '/app/lang/fr-FR/messages.php',
                ],
                'validation' => [
                    '/app/lang/en-GB/validation.php',
                ],
            ],
        ];

        yield 'mixed file formats' => [
            [
                '/path/to/lang/en/messages.php',
                '/path/to/lang/de/messages.php',
                '/path/to/lang/en/auth.json',
                '/path/to/lang/de/auth.json',
                '/path/to/lang/en/validation.yaml',
                '/path/to/lang/de/validation.yml',
                '/path/to/lang/en/errors.xlf',
                '/path/to/lang/de/errors.xliff',
            ],
            [
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
            ],
        ];

        yield 'ignores unsupported files' => [
            [
                '/path/to/lang/en/messages.php',
                '/path/to/lang/en/config.ini',
                '/path/to/lang/en/data.xml',
                '/path/to/lang/en/readme.txt',
                '/path/to/lang/de/messages.php',
            ],
            [
                'messages' => [
                    '/path/to/lang/en/messages.php',
                    '/path/to/lang/de/messages.php',
                ],
            ],
        ];

        yield 'ignores invalid language codes' => [
            [
                '/path/to/lang/english/messages.php',
                '/path/to/lang/en/messages.php',
                '/path/to/lang/123/messages.php',
                '/path/to/lang/e/messages.php',
                '/path/to/lang/deutsch/messages.php',
                '/path/to/lang/de/messages.php',
            ],
            [
                'messages' => [
                    '/path/to/lang/en/messages.php',
                    '/path/to/lang/de/messages.php',
                ],
            ],
        ];

        yield 'windows paths' => [
            [
                'C:\\app\\resources\\lang\\en\\messages.php',
                'C:\\app\\resources\\lang\\de\\messages.php',
            ],
            [
                'messages' => [
                    'C:\\app\\resources\\lang\\en\\messages.php',
                    'C:\\app\\resources\\lang\\de\\messages.php',
                ],
            ],
        ];

        yield 'empty array' => [[], []];

        yield 'no matching files' => [
            [
                '/path/to/config/app.php',
                '/path/to/models/User.php',
                '/path/to/controllers/HomeController.php',
            ],
            [],
        ];

        yield 'mixed structures' => [
            [
                '/app/lang/en/messages.php',
                '/app/lang/de/messages.php',
                '/app/translations/messages.en.php',
                '/app/translations/messages.de.php',
                '/project/resources/lang/fr/auth.php',
                '/project/resources/lang/es/auth.php',
            ],
            [
                'messages' => [
                    '/app/lang/en/messages.php',
                    '/app/lang/de/messages.php',
                ],
                'auth' => [
                    '/project/resources/lang/fr/auth.php',
                    '/project/resources/lang/es/auth.php',
                ],
            ],
        ];

        yield 'same file name different paths' => [
            [
                '/app1/lang/en/messages.php',
                '/app1/lang/de/messages.php',
                '/app2/lang/en/messages.php',
                '/app2/lang/fr/messages.php',
            ],
            [
                'messages' => [
                    '/app1/lang/en/messages.php',
                    '/app1/lang/de/messages.php',
                    '/app2/lang/en/messages.php',
                    '/app2/lang/fr/messages.php',
                ],
            ],
        ];

        yield 'invalid paths' => [
            [
                'messages.php',
                '/messages.php',
                '/lang/en/messages.php',
                '',
            ],
            [
                'messages' => [
                    '/lang/en/messages.php',
                ],
            ],
        ];
    }
}
