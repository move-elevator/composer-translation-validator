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
 * FileSet.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
class FileSet
{
    /**
     * @param array<string> $files
     */
    public function __construct(
        private readonly string $parser,
        private readonly string $path,
        private readonly string $setKey,
        private readonly array $files,
    ) {}

    public function getParser(): string
    {
        return $this->parser;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getSetKey(): string
    {
        return $this->setKey;
    }

    /**
     * @return array<string>
     */
    public function getFiles(): array
    {
        return $this->files;
    }
}
