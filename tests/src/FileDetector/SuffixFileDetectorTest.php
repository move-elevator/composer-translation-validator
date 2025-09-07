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

namespace MoveElevator\ComposerTranslationValidator\Tests\FileDetector;

use MoveElevator\ComposerTranslationValidator\FileDetector\SuffixFileDetector;
use PHPUnit\Framework\TestCase;

/**
 * SuffixFileDetectorTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
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
