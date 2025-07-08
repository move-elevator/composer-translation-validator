<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics;
use PHPUnit\Framework\TestCase;

class ValidationStatisticsTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $executionTime = 1.23456;
        $filesChecked = 5;
        $keysChecked = 10;
        $validatorsRun = 4;

        $statistics = new ValidationStatistics(
            $executionTime,
            $filesChecked,
            $keysChecked,
            $validatorsRun
        );

        $this->assertSame($executionTime, $statistics->getExecutionTime());
        $this->assertSame($filesChecked, $statistics->getFilesChecked());
        $this->assertSame($keysChecked, $statistics->getKeysChecked());
        $this->assertSame($validatorsRun, $statistics->getValidatorsRun());
    }

    public function testGetExecutionTimeFormattedForMilliseconds(): void
    {
        $statistics = new ValidationStatistics(0.123, 1, 1, 1);

        $this->assertSame('123ms', $statistics->getExecutionTimeFormatted());
    }

    public function testGetExecutionTimeFormattedForSeconds(): void
    {
        $statistics = new ValidationStatistics(1.23456, 1, 1, 1);

        $this->assertSame('1.23s', $statistics->getExecutionTimeFormatted());
    }

    public function testGetExecutionTimeFormattedForExactOneSecond(): void
    {
        $statistics = new ValidationStatistics(1.0, 1, 1, 1);

        $this->assertSame('1.00s', $statistics->getExecutionTimeFormatted());
    }

    public function testGetExecutionTimeFormattedForVerySmallValue(): void
    {
        $statistics = new ValidationStatistics(0.001, 1, 1, 1);

        $this->assertSame('1ms', $statistics->getExecutionTimeFormatted());
    }

    public function testGetExecutionTimeFormattedForZero(): void
    {
        $statistics = new ValidationStatistics(0.0, 1, 1, 1);

        $this->assertSame('0ms', $statistics->getExecutionTimeFormatted());
    }
}
