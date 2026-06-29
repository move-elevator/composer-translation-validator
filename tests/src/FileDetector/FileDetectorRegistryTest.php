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

use MoveElevator\ComposerTranslationValidator\FileDetector\{DetectorInterface, DirectoryFileDetector, FileDetectorRegistry, PrefixFileDetector, SuffixFileDetector};
use PHPUnit\Framework\TestCase;

/**
 * FileDetectorRegistryTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class FileDetectorRegistryTest extends TestCase
{
    public function testGetAvailableFileDetectors(): void
    {
        $detectors = FileDetectorRegistry::getAvailableFileDetectors();

        $this->assertSame($detectors, FileDetectorRegistry::getAvailableFileDetectors());
        $this->assertContains(PrefixFileDetector::class, $detectors);
        $this->assertContains(SuffixFileDetector::class, $detectors);
        $this->assertContains(DirectoryFileDetector::class, $detectors);
        $this->assertCount(3, $detectors);

        foreach ($detectors as $detector) {
            $this->assertTrue(class_exists($detector), "Class {$detector} should exist");
            $this->assertContains(
                DetectorInterface::class,
                class_implements($detector) ?: [],
                "Class {$detector} should implement DetectorInterface",
            );
        }
    }
}
