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

use MoveElevator\ComposerTranslationValidator\FileDetector\PrefixFileDetector;
use PHPUnit\Framework\TestCase;

/**
 * PrefixFileDetectorTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 */
final class PrefixFileDetectorTest extends TestCase
{
    public function testMapTranslationSetWithPrefixedFiles(): void
    {
        $detector = new PrefixFileDetector();
        $files = [
            '/path/to/de.locallang.xlf',
            '/path/to/fr.locallang.xlf',
            '/path/to/locallang.xlf',
        ];

        $expected = [
            'locallang.xlf' => [
                '/path/to/de.locallang.xlf',
                '/path/to/fr.locallang.xlf',
                '/path/to/locallang.xlf',
            ],
        ];

        $this->assertSame($expected, $detector->mapTranslationSet($files));
    }

    public function testMapTranslationSetWithMixedFiles(): void
    {
        $detector = new PrefixFileDetector();
        $files = [
            '/path/to/de.messages.xlf',
            '/path/to/messages.xlf',
            '/path/to/en.validation.xlf',
            '/path/to/validation.xlf',
        ];

        $expected = [
            'messages.xlf' => [
                '/path/to/de.messages.xlf',
                '/path/to/messages.xlf',
            ],
            'validation.xlf' => [
                '/path/to/en.validation.xlf',
                '/path/to/validation.xlf',
            ],
        ];

        $this->assertSame($expected, $detector->mapTranslationSet($files));
    }

    public function testMapTranslationSetWithNoPrefixedFiles(): void
    {
        $detector = new PrefixFileDetector();
        $files = [
            '/path/to/locallang.xlf',
            '/path/to/messages.xlf',
        ];

        $expected = [
            'locallang.xlf' => [
                '/path/to/locallang.xlf',
            ],
            'messages.xlf' => [
                '/path/to/messages.xlf',
            ],
        ];

        $this->assertSame($expected, $detector->mapTranslationSet($files));
    }

    public function testMapTranslationSetWithEmptyArray(): void
    {
        $detector = new PrefixFileDetector();
        $files = [];

        $expected = [];

        $this->assertSame($expected, $detector->mapTranslationSet($files));
    }
}
