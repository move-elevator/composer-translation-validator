<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Utility;

class PathUtility
{
    public static function normalizeFolderPath(string $path): string
    {
        $cwd = getcwd();
        $relativePath = str_starts_with($path, $cwd)
            ? substr($path, strlen($cwd) + 1)
            : $path;

        if (str_starts_with($relativePath, './')) {
            $relativePath = substr($relativePath, 2);
        }

        if ('' !== $relativePath && !str_ends_with($relativePath, '/')) {
            $relativePath .= '/';
        }

        return $relativePath;
    }
}
