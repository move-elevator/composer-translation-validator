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

use MoveElevator\ComposerTranslationValidator\FileDetector\SuffixFileDetector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * SuffixFileDetectorTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class SuffixFileDetectorTest extends TestCase
{
    /**
     * @param array<int, string>                $files
     * @param array<string, array<int, string>> $expected
     */
    #[DataProvider('mapTranslationSetProvider')]
    public function testMapTranslationSet(array $files, array $expected): void
    {
        $this->assertSame($expected, (new SuffixFileDetector())->mapTranslationSet($files));
    }

    /**
     * @return iterable<string, array{array<int, string>, array<string, array<int, string>>}>
     */
    public static function mapTranslationSetProvider(): iterable
    {
        yield 'suffix files' => [
            [
                '/path/to/locallang.de.xlf',
                '/path/to/locallang.fr.xlf',
                '/path/to/locallang.xlf',
            ],
            [
                'locallang' => [
                    '/path/to/locallang.de.xlf',
                    '/path/to/locallang.fr.xlf',
                ],
            ],
        ];

        yield 'mixed files' => [
            [
                '/path/to/messages.de.xlf',
                '/path/to/messages.xlf',
                '/path/to/validation.en.yml',
                '/path/to/validation.yml',
            ],
            [
                'messages' => ['/path/to/messages.de.xlf'],
                'validation' => ['/path/to/validation.en.yml'],
            ],
        ];

        yield 'no suffix files' => [
            [
                '/path/to/locallang.xlf',
                '/path/to/messages.yml',
            ],
            [],
        ];

        yield 'empty array' => [[], []];
    }
}
