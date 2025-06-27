<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\FileDetector;

class SuffixFileDetector implements DetectorInterface
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
                preg_match('/^([^.]+)\.[a-z]{2}([-_][A-Z]{2})?(\.ya?ml|\.xlf)?$/i', $basename, $matches) // Suffix
                || preg_match('/^[^.]+\.(ya?ml|xlf)$/i', $basename, $matches) // No prefix, only one dot (e.g. locallang_be.xlf)
            ) {
                $key = $matches[1];
                $groups[$key][] = $file;
            }
        }

        return $groups;
    }
}
