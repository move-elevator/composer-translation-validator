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

namespace MoveElevator\ComposerTranslationValidator\Parser;

use InvalidArgumentException;
use RuntimeException;

/**
 * AbstractParser.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
abstract class AbstractParser
{
    protected readonly string $fileName;

    public function __construct(protected readonly string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException(sprintf('File "%s" does not exist.', $filePath));
        }

        if (!is_readable($filePath)) {
            throw new RuntimeException(sprintf('File "%s" is not readable.', $filePath));
        }

        if (!in_array(
            pathinfo($filePath, PATHINFO_EXTENSION),
            static::getSupportedFileExtensions(),
            true,
        )) {
            throw new InvalidArgumentException(sprintf('File "%s" is not a valid file.', $filePath));
        }

        $this->fileName = basename($filePath);
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getFileDirectory(): string
    {
        return dirname($this->filePath).\DIRECTORY_SEPARATOR;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * @return array<int, string>
     */
    abstract public static function getSupportedFileExtensions(): array;
}
