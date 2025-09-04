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

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @package ComposerTranslationValidator
 */

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
            if (preg_match('/^([a-z]{2}(?:[-_][A-Z]{2})?)\.(.+)$/i', $basename, $matches)) {
                // Language prefix pattern (e.g., de.messages.xlf -> key: messages.xlf)
                $key = $matches[2];
                $groups[$key][] = $file;
            } elseif (preg_match('/^(locallang|messages|validation|errors|labels|translations?|test)\./', $basename)) {
                // Common translation file patterns (e.g., messages.xlf -> key: messages.xlf)
                $key = $basename;
                $groups[$key][] = $file;
            } elseif (preg_match('/^[^.]+\.(xlf|xliff|json|ya?ml|php)$/i', $basename) && !preg_match('/(config|validator|setting)/', $basename)) {
                // Generic translation files, but exclude config/validator files
                $key = $basename;
                $groups[$key][] = $file;
            }
        }

        return $groups;
    }
}
