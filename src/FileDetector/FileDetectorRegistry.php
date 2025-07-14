<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\FileDetector;

class FileDetectorRegistry
{
    /**
     * @return array<int, class-string<DetectorInterface>>
     */
    public static function getAvailableFileDetectors(): array
    {
        return [
            PrefixFileDetector::class,
            SuffixFileDetector::class,
            DirectoryFileDetector::class,
        ];
    }
}
