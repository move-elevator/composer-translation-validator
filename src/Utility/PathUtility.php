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

namespace MoveElevator\ComposerTranslationValidator\Utility;

/**
 * PathUtility.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
class PathUtility
{
    public static function normalizeFolderPath(string $path): string
    {
        $realPath = realpath($path);
        if (false === $realPath) {
            $normalizedPath = rtrim($path, DIRECTORY_SEPARATOR);
            if (str_starts_with($normalizedPath, './')) {
                $normalizedPath = substr($normalizedPath, 2);
            }

            return $normalizedPath;
        }

        $normalizedPath = rtrim($realPath, DIRECTORY_SEPARATOR);

        $cwd = getcwd();
        if (false === $cwd) {
            return $normalizedPath;
        }
        $realCwd = realpath($cwd);
        if (false === $realCwd) {
            return $normalizedPath;
        }
        $cwd = $realCwd.DIRECTORY_SEPARATOR;

        if (str_starts_with($normalizedPath.DIRECTORY_SEPARATOR, $cwd)) {
            return substr($normalizedPath, strlen($cwd));
        }

        return $normalizedPath;
    }
}
