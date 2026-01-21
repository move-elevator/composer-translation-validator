<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025-2026 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Utility;

use function strlen;

/**
 * PathUtility.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class PathUtility
{
    public static function normalizeFolderPath(string $path): string
    {
        $realPath = realpath($path);
        if (false === $realPath) {
            $normalizedPath = rtrim($path, \DIRECTORY_SEPARATOR);
            if (str_starts_with($normalizedPath, './')) {
                $normalizedPath = substr($normalizedPath, 2);
            }

            return $normalizedPath;
        }

        $normalizedPath = rtrim($realPath, \DIRECTORY_SEPARATOR);

        $cwd = getcwd();
        if (false === $cwd) {
            return $normalizedPath;
        }
        $realCwd = realpath($cwd);
        if (false === $realCwd) {
            return $normalizedPath;
        }
        $cwd = $realCwd.\DIRECTORY_SEPARATOR;

        if (str_starts_with($normalizedPath.\DIRECTORY_SEPARATOR, $cwd)) {
            return substr($normalizedPath, strlen($cwd));
        }

        return $normalizedPath;
    }
}
