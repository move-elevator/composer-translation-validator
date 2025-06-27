<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Utility;

class PathUtility
{
    public static function normalizeFolderPath(string $path): string
    {
        $realPath = realpath($path);
        if (false === $realPath) {
            // If realpath fails, it might be a non-existent path, return as is but normalized
            $normalizedPath = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            if (str_starts_with($normalizedPath, './')) {
                $normalizedPath = substr($normalizedPath, 2);
            }

            return $normalizedPath;
        }

        $cwd = realpath(getcwd()).DIRECTORY_SEPARATOR;
        $normalizedPath = rtrim($realPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (str_starts_with($normalizedPath, $cwd)) {
            $relativePath = substr($normalizedPath, strlen($cwd));

            return $relativePath;
        }

        return $normalizedPath;
    }
}
