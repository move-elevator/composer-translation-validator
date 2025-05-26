<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\FileDetector;

interface DetectorInterface
{
    public function mapTranslationSet(array $files): array;
}
