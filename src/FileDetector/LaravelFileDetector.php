<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\FileDetector;

class LaravelFileDetector implements DetectorInterface
{
    /**
     * Maps Laravel-style translation files organized by language directories.
     * Examples:
     * - lang/en/messages.php, lang/de/messages.php
     * - resources/lang/en/auth.php, resources/lang/fr/auth.php.
     *
     * @param array<int, string> $files
     *
     * @return array<string, array<int, string>>
     */
    public function mapTranslationSet(array $files): array
    {
        $groups = [];

        foreach ($files as $file) {
            $pathParts = explode('/', str_replace('\\', '/', $file));
            $fileName = array_pop($pathParts);
            $languageDir = array_pop($pathParts);

            // Check if this follows Laravel pattern: lang_code/filename.php
            if (
                $languageDir
                && preg_match('/^[a-z]{2}(?:[-_][A-Z]{2})?$/', $languageDir)
                && preg_match('/^([^.]+)\.php$/i', $fileName, $matches)
            ) {
                $key = $matches[1];
                $groups[$key][] = $file;
            }
        }

        return $groups;
    }
}
