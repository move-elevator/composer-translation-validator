<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\FileDetector;

class PrefixFileDetector implements DetectorInterface
{
    /**
     * @param array<int, string> $files
     *
     * @return array<string, array<int, string>>
     */
    public function mapTranslationSet(array $files): array
    {
        $groups = [];

        foreach ($files as $file) {
            $basename = basename($file);
            if (
                preg_match('/^([a-z]{2}(?:[-_][A-Z]{2})?)\.(.+)$/i', $basename, $matches) // Prefix
                || preg_match('/^[^.]+\.[^.]+$/', $basename, $matches) // No prefix, only one dot (e.g. locallang_be.xlf)
            ) {
                $key = $matches[2] ?? $matches[0];
                $groups[$key][] = $file;
            }
        }

        return $groups;
    }
}
