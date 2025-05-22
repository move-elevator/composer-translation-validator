<?php

declare(strict_types=1);

namespace KonradMichalik\ComposerTranslationValidator\FileDetector;

interface DetectorInterface
{
    public function mapTranslationSet(array $files): array;
}
