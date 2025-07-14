<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\FileDetector;

use MoveElevator\ComposerTranslationValidator\FileDetector\DetectorInterface;
use MoveElevator\ComposerTranslationValidator\FileDetector\FileDetectorRegistry;
use MoveElevator\ComposerTranslationValidator\FileDetector\LaravelFileDetector;
use MoveElevator\ComposerTranslationValidator\FileDetector\PrefixFileDetector;
use MoveElevator\ComposerTranslationValidator\FileDetector\SuffixFileDetector;
use PHPUnit\Framework\TestCase;

final class FileDetectorRegistryTest extends TestCase
{
    public function testGetAvailableFileDetectors(): void
    {
        $detectors = FileDetectorRegistry::getAvailableFileDetectors();

        $this->assertContains(PrefixFileDetector::class, $detectors);
        $this->assertContains(SuffixFileDetector::class, $detectors);
        $this->assertContains(LaravelFileDetector::class, $detectors);
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
                "Class {$detector} should implement DetectorInterface"
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
