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

namespace MoveElevator\ComposerTranslationValidator\Result;

/**
 * ValidationStatistics.
 *
 * @author Konrad Michalik <km@move-elevator.de>
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
