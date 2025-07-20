<?php

declare(strict_types=1);

/*
 * This file is part of the Composer plugin "composer-translation-validator".
 *
 * Copyright (C) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

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
