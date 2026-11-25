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

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics;
use PHPUnit\Framework\TestCase;

/**
 * ValidationStatisticsTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class ValidationStatisticsTest extends TestCase
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

        self::assertSame($executionTime, $statistics->getExecutionTime());
        self::assertSame($filesChecked, $statistics->getFilesChecked());
        self::assertSame($keysChecked, $statistics->getKeysChecked());
        self::assertSame($validatorsRun, $statistics->getValidatorsRun());
        self::assertSame(0, $statistics->getParsersCached()); // Default value
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

        self::assertSame($executionTime, $statistics->getExecutionTime());
        self::assertSame($filesChecked, $statistics->getFilesChecked());
        self::assertSame($keysChecked, $statistics->getKeysChecked());
        self::assertSame($validatorsRun, $statistics->getValidatorsRun());
        self::assertSame($parsersCached, $statistics->getParsersCached());
    }

    public function testGetExecutionTimeFormattedForMilliseconds(): void
    {
        $statistics = new ValidationStatistics(0.123, 1, 1, 1);

        self::assertSame('123ms', $statistics->getExecutionTimeFormatted());
    }

    public function testGetExecutionTimeFormattedForSeconds(): void
    {
        $statistics = new ValidationStatistics(1.23456, 1, 1, 1);

        self::assertSame('1.23s', $statistics->getExecutionTimeFormatted());
    }

    public function testGetExecutionTimeFormattedForExactOneSecond(): void
    {
        $statistics = new ValidationStatistics(1.0, 1, 1, 1);

        self::assertSame('1.00s', $statistics->getExecutionTimeFormatted());
    }

    public function testGetExecutionTimeFormattedForVerySmallValue(): void
    {
        $statistics = new ValidationStatistics(0.001, 1, 1, 1);

        self::assertSame('1ms', $statistics->getExecutionTimeFormatted());
    }

    public function testGetExecutionTimeFormattedForZero(): void
    {
        $statistics = new ValidationStatistics(0.0, 1, 1, 1);

        self::assertSame('0ms', $statistics->getExecutionTimeFormatted());
    }

    public function testGetParsersCachedDefault(): void
    {
        $statistics = new ValidationStatistics(1.0, 2, 3, 4);

        self::assertSame(0, $statistics->getParsersCached());
    }

    public function testGetParsersCachedWithValue(): void
    {
        $statistics = new ValidationStatistics(1.0, 2, 3, 4, 5);

        self::assertSame(5, $statistics->getParsersCached());
    }

    public function testGetExecutionTimeFormattedForLargeValue(): void
    {
        $statistics = new ValidationStatistics(1000.123456, 1, 1, 1);

        self::assertSame('1,000.12s', $statistics->getExecutionTimeFormatted());
    }

    public function testGetExecutionTimeFormattedForBoundaryValue(): void
    {
        $statistics = new ValidationStatistics(0.999, 1, 1, 1);

        self::assertSame('999ms', $statistics->getExecutionTimeFormatted());
    }

    public function testGetExecutionTimeFormattedForExactOneMicrosecond(): void
    {
        $statistics = new ValidationStatistics(0.000001, 1, 1, 1);

        self::assertSame('0ms', $statistics->getExecutionTimeFormatted());
    }
}
