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
 * ValidationStatistics.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
class ValidationStatistics
{
    public function __construct(
        private readonly float $executionTime,
        private readonly int $filesChecked,
        private readonly int $keysChecked,
        private readonly int $validatorsRun,
        private readonly int $parsersCached = 0,
    ) {}

    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

    public function getExecutionTimeFormatted(): string
    {
        if ($this->executionTime < 1.0) {
            return number_format($this->executionTime * 1000, 0).'ms';
        }

        return number_format($this->executionTime, 2).'s';
    }

    public function getFilesChecked(): int
    {
        return $this->filesChecked;
    }

    public function getKeysChecked(): int
    {
        return $this->keysChecked;
    }

    public function getValidatorsRun(): int
    {
        return $this->validatorsRun;
    }

    public function getParsersCached(): int
    {
        return $this->parsersCached;
    }
}
