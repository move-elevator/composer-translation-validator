<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\FileDetector;

class DirectoryFileDetector implements DetectorInterface
{
    /**
     * Maps translation files organized by language directories.
     * Examples:
     * - lang/en/messages.php, lang/de/messages.php (Laravel style)
     * - resources/lang/en/auth.php, resources/lang/fr/auth.php (Laravel style)
     * - translations/en/messages.php, translations/de/messages.php (directory-based).
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

            // Check if this follows directory-based pattern: lang_code/filename.ext
            if (
                $languageDir
                && preg_match('/^[a-z]{2}(?:[-_][A-Z]{2})?$/', $languageDir)
                && preg_match('/^([^.]+)\.(php|json|ya?ml|xlf|xliff)$/i', $fileName, $matches)
            ) {
                $key = $matches[1];
                $groups[$key][] = $file;
            }
        }

        return $groups;
    }
}
