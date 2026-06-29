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

use MoveElevator\ComposerTranslationValidator\FileDetector\PrefixFileDetector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * PrefixFileDetectorTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class PrefixFileDetectorTest extends TestCase
{
    /**
     * @param array<int, string>                $files
     * @param array<string, array<int, string>> $expected
     */
    #[DataProvider('mapTranslationSetProvider')]
    public function testMapTranslationSet(array $files, array $expected): void
    {
        $this->assertSame($expected, (new PrefixFileDetector())->mapTranslationSet($files));
    }

    /**
     * @return iterable<string, array{array<int, string>, array<string, array<int, string>>}>
     */
    public static function mapTranslationSetProvider(): iterable
    {
        yield 'prefixed files' => [
            [
                '/path/to/de.locallang.xlf',
                '/path/to/fr.locallang.xlf',
                '/path/to/locallang.xlf',
            ],
            [
                'locallang.xlf' => [
                    '/path/to/de.locallang.xlf',
                    '/path/to/fr.locallang.xlf',
                    '/path/to/locallang.xlf',
                ],
            ],
        ];

        yield 'mixed files' => [
            [
                '/path/to/de.messages.xlf',
                '/path/to/messages.xlf',
                '/path/to/en.validation.xlf',
                '/path/to/validation.xlf',
            ],
            [
                'messages.xlf' => [
                    '/path/to/de.messages.xlf',
                    '/path/to/messages.xlf',
                ],
                'validation.xlf' => [
                    '/path/to/en.validation.xlf',
                    '/path/to/validation.xlf',
                ],
            ],
        ];

        yield 'no prefixed files' => [
            [
                '/path/to/locallang.xlf',
                '/path/to/messages.xlf',
            ],
            [
                'locallang.xlf' => ['/path/to/locallang.xlf'],
                'messages.xlf' => ['/path/to/messages.xlf'],
            ],
        ];

        yield 'generic translation files' => [
            [
                '/path/to/foo.xlf',
                '/path/to/homepage.json',
            ],
            [
                'foo.xlf' => ['/path/to/foo.xlf'],
                'homepage.json' => ['/path/to/homepage.json'],
            ],
        ];

        yield 'empty array' => [[], []];
    }
}
