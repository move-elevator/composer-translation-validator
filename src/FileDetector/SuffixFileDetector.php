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
            if (preg_match('/^([^.]+)\.[a-z]{2}([-_][A-Z]{2})?(\.ya?ml|\.xlf|\.json|\.php)?$/i', $basename, $matches)) {
                $key = $matches[1];
                $groups[$key][] = $file;
            }
        }

        return $groups;
    }
}
