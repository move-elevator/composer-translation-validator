<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Result;

class ValidationStatistics
{
    public function __construct(
        private readonly float $executionTime,
        private readonly int $filesChecked,
        private readonly int $keysChecked,
        private readonly int $validatorsRun,
        private readonly int $parsersCached = 0,
    ) {
    }

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
