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
use PHPUnit\Framework\TestCase;

/**
 * SuffixFileDetectorTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class SuffixFileDetectorTest extends TestCase
{
    public function testMapTranslationSetWithSuffixFiles(): void
    {
        $detector = new SuffixFileDetector();
        $files = [
            '/path/to/locallang.de.xlf',
            '/path/to/locallang.fr.xlf',
            '/path/to/locallang.xlf',
        ];

        $expected = [
            'locallang' => [
                '/path/to/locallang.de.xlf',
                '/path/to/locallang.fr.xlf',
            ],
        ];

        $this->assertSame($expected, $detector->mapTranslationSet($files));
    }

    public function testMapTranslationSetWithMixedFiles(): void
    {
        $detector = new SuffixFileDetector();
        $files = [
            '/path/to/messages.de.xlf',
            '/path/to/messages.xlf',
            '/path/to/validation.en.yml',
            '/path/to/validation.yml',
        ];

        $expected = [
            'messages' => [
                '/path/to/messages.de.xlf',
            ],
            'validation' => [
                '/path/to/validation.en.yml',
            ],
        ];

        $this->assertSame($expected, $detector->mapTranslationSet($files));
    }

    public function testMapTranslationSetWithNoSuffixFiles(): void
    {
        $detector = new SuffixFileDetector();
        $files = [
            '/path/to/locallang.xlf',
            '/path/to/messages.yml',
        ];

        $expected = [];

        $this->assertSame($expected, $detector->mapTranslationSet($files));
    }

    public function testMapTranslationSetWithEmptyArray(): void
    {
        $detector = new SuffixFileDetector();
        $files = [];

        $expected = [];

        $this->assertSame($expected, $detector->mapTranslationSet($files));
    }
}
