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

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics;
use PHPUnit\Framework\TestCase;

/**
 * ValidationStatisticsTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 */
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
            $validatorsRun,
        );

        $this->assertSame($executionTime, $statistics->getExecutionTime());
        $this->assertSame($filesChecked, $statistics->getFilesChecked());
        $this->assertSame($keysChecked, $statistics->getKeysChecked());
        $this->assertSame($validatorsRun, $statistics->getValidatorsRun());
        $this->assertSame(0, $statistics->getParsersCached()); // Default value
    }

    public function testConstructorWithParsersCached(): void
    {
        $executionTime = 1.23456;
        $filesChecked = 5;
        $keysChecked = 10;
        $validatorsRun = 4;
        $parsersCached = 3;

        $statistics = new ValidationStatistics(
            $executionTime,
            $filesChecked,
            $keysChecked,
            $validatorsRun,
            $parsersCached,
        );

        $this->assertSame($executionTime, $statistics->getExecutionTime());
        $this->assertSame($filesChecked, $statistics->getFilesChecked());
        $this->assertSame($keysChecked, $statistics->getKeysChecked());
        $this->assertSame($validatorsRun, $statistics->getValidatorsRun());
        $this->assertSame($parsersCached, $statistics->getParsersCached());
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

    public function testGetParsersCachedDefault(): void
    {
        $statistics = new ValidationStatistics(1.0, 2, 3, 4);

        $this->assertSame(0, $statistics->getParsersCached());
    }

    public function testGetParsersCachedWithValue(): void
    {
        $statistics = new ValidationStatistics(1.0, 2, 3, 4, 5);

        $this->assertSame(5, $statistics->getParsersCached());
    }

    public function testGetExecutionTimeFormattedForLargeValue(): void
    {
        $statistics = new ValidationStatistics(1000.123456, 1, 1, 1);

        $this->assertSame('1,000.12s', $statistics->getExecutionTimeFormatted());
    }

    public function testGetExecutionTimeFormattedForBoundaryValue(): void
    {
        $statistics = new ValidationStatistics(0.999, 1, 1, 1);

        $this->assertSame('999ms', $statistics->getExecutionTimeFormatted());
    }

    public function testGetExecutionTimeFormattedForExactOneMicrosecond(): void
    {
        $statistics = new ValidationStatistics(0.000001, 1, 1, 1);

        $this->assertSame('0ms', $statistics->getExecutionTimeFormatted());
    }
}
