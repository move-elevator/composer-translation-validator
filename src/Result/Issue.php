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

namespace MoveElevator\ComposerTranslationValidator\Result;

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @package ComposerTranslationValidator
 */

class Issue
{
    /**
     * @param array<mixed> $details
     */
    public function __construct(
        private readonly string $file,
        private readonly array $details,
        private readonly string $parser,
        private readonly string $validatorType,
    ) {}

    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @return array<mixed>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    public function getParser(): string
    {
        return $this->parser;
    }

    public function getValidatorType(): string
    {
        return $this->validatorType;
    }

    /**
     * @return array{file: string, issues: array<mixed>, parser: string, type: string}
     */
    public function toArray(): array
    {
        return [
            'file' => $this->file,
            'issues' => $this->details,
            'parser' => $this->parser,
            'type' => $this->validatorType,
        ];
    }
}
