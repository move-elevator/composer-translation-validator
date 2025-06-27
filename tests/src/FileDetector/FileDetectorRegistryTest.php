<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\FileDetector;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileDetectorRegistry;
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
        $this->assertCount(2, $detectors);
    }
}
