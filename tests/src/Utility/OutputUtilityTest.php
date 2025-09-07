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

namespace MoveElevator\ComposerTranslationValidator\Tests\Utility;

use MoveElevator\ComposerTranslationValidator\Utility\OutputUtility;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * OutputUtilityTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 */
final class OutputUtilityTest extends TestCase
{
    public function testDebugWithVerboseOutput(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $output->method('isVerbose')->willReturn(true);
        $output->method('isVeryVerbose')->willReturn(false);
        $output->expects($this->once())
            ->method('writeln')
            ->with('Test message');

        OutputUtility::debug($output, 'Test message');
    }

    public function testDebugWithVeryVerboseOutput(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $output->method('isVerbose')->willReturn(true);
        $output->method('isVeryVerbose')->willReturn(true);
        $output->expects($this->once())
            ->method('writeln')
            ->with('<fg=gray>Very verbose message</>');

        OutputUtility::debug($output, 'Very verbose message', true);
    }

    public function testDebugWithNonVerboseOutput(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $output->method('isVerbose')->willReturn(false);
        $output->expects($this->never())
            ->method('writeln');

        OutputUtility::debug($output, 'Test message');
    }

    public function testDebugWithNonVeryVerboseOutputAndVeryVerboseFlag(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $output->method('isVerbose')->willReturn(true);
        $output->method('isVeryVerbose')->willReturn(false);
        $output->expects($this->never())
            ->method('writeln');

        OutputUtility::debug($output, 'Very verbose message', true);
    }

    public function testDebugWithNoNewLine(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $output->method('isVerbose')->willReturn(true);
        $output->method('isVeryVerbose')->willReturn(false);
        $output->expects($this->once())
            ->method('write')
            ->with('Test message');

        OutputUtility::debug($output, 'Test message', false, false);
    }
}
