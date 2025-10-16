<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\FileDetector;

/**
 * DirectoryFileDetector.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
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

            if (!$fileName || !$languageDir) {
                continue;
            }

            // Check if this follows directory-based pattern: lang_code/filename.ext
            if (
                preg_match('/^[a-z]{2}(?:[-_][A-Z]{2})?$/', $languageDir)
                && preg_match('/^([^.]+)\.(php|json|ya?ml|xlf|xliff)$/i', $fileName, $matches)
            ) {
                $key = $matches[1];
                $groups[$key][] = $file;
            }
        }

        return $groups;
    }
}
