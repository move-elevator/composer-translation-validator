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

use MoveElevator\ComposerTranslationValidator\FileDetector\DetectorInterface;
use MoveElevator\ComposerTranslationValidator\FileDetector\DirectoryFileDetector;
use MoveElevator\ComposerTranslationValidator\FileDetector\FileDetectorRegistry;
use MoveElevator\ComposerTranslationValidator\FileDetector\PrefixFileDetector;
use MoveElevator\ComposerTranslationValidator\FileDetector\SuffixFileDetector;
use PHPUnit\Framework\TestCase;

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 *
 * @package ComposerTranslationValidator
 */

final class FileDetectorRegistryTest extends TestCase
{
    public function testGetAvailableFileDetectors(): void
    {
        $detectors = FileDetectorRegistry::getAvailableFileDetectors();

        $this->assertContains(PrefixFileDetector::class, $detectors);
        $this->assertContains(SuffixFileDetector::class, $detectors);
        $this->assertContains(DirectoryFileDetector::class, $detectors);
        $this->assertCount(3, $detectors);
    }

    public function testGetAvailableFileDetectorsReturnsArray(): void
    {
        $detectors = FileDetectorRegistry::getAvailableFileDetectors();

        $this->assertNotEmpty($detectors);
    }

    public function testGetAvailableFileDetectorsContainsValidClasses(): void
    {
        $detectors = FileDetectorRegistry::getAvailableFileDetectors();

        foreach ($detectors as $detector) {
            $this->assertTrue(class_exists($detector), "Class {$detector} should exist");
            $this->assertContains(
                DetectorInterface::class,
                class_implements($detector) ?: [],
                "Class {$detector} should implement DetectorInterface",
            );
        }
    }

    public function testGetAvailableFileDetectorsAlwaysReturnsSameOrder(): void
    {
        $detectors1 = FileDetectorRegistry::getAvailableFileDetectors();
        $detectors2 = FileDetectorRegistry::getAvailableFileDetectors();

        $this->assertSame($detectors1, $detectors2);
    }
}
