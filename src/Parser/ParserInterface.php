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

interface ParserInterface
{
    public function __construct(string $filePath);

    /**
     * @return array<int, string>
     */
    public static function getSupportedFileExtensions(): array;

    /**
     * @return array<int, string>|null
     */
    public function extractKeys(): ?array;

    public function getContentByKey(string $key): ?string;

    public function getFileName(): string;

    public function getFileDirectory(): string;

    public function getFilePath(): string;
}
