<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Utility;

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

        $cwd = realpath(getcwd()) . DIRECTORY_SEPARATOR;
        $normalizedPath = rtrim($realPath, DIRECTORY_SEPARATOR);

        if (str_starts_with($normalizedPath . DIRECTORY_SEPARATOR, $cwd)) {
            return substr($normalizedPath, strlen($cwd));
        }

        return $normalizedPath;
    }
}
