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

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;

final class ResultTypeTest extends TestCase
{
    public function testMax(): void
    {
        $this->assertSame(ResultType::ERROR, ResultType::ERROR->max(ResultType::WARNING));
        $this->assertSame(ResultType::ERROR, ResultType::WARNING->max(ResultType::ERROR));
        $this->assertSame(ResultType::WARNING, ResultType::WARNING->max(ResultType::SUCCESS));
        $this->assertSame(ResultType::WARNING, ResultType::SUCCESS->max(ResultType::WARNING));
        $this->assertSame(ResultType::SUCCESS, ResultType::SUCCESS->max(ResultType::SUCCESS));
    }

    public function testNotFullySuccessful(): void
    {
        $this->assertFalse(ResultType::SUCCESS->notFullySuccessful());
        $this->assertTrue(ResultType::WARNING->notFullySuccessful());
        $this->assertTrue(ResultType::ERROR->notFullySuccessful());
    }

    public function testResolveErrorToCommandExitCode(): void
    {
        // ERROR, not dryRun, not strict
        $this->assertSame(Command::FAILURE, ResultType::ERROR->resolveErrorToCommandExitCode(false, false));

        // ERROR, dryRun, not strict
        $this->assertSame(Command::SUCCESS, ResultType::ERROR->resolveErrorToCommandExitCode(true, false));

        // ERROR, not dryRun, strict
        $this->assertSame(Command::FAILURE, ResultType::ERROR->resolveErrorToCommandExitCode(false, true));

        // ERROR, dryRun, strict
        $this->assertSame(Command::SUCCESS, ResultType::ERROR->resolveErrorToCommandExitCode(true, true));

        // WARNING, not dryRun, not strict
        $this->assertSame(Command::SUCCESS, ResultType::WARNING->resolveErrorToCommandExitCode(false, false));

        // WARNING, dryRun, not strict
        $this->assertSame(Command::SUCCESS, ResultType::WARNING->resolveErrorToCommandExitCode(true, false));

        // WARNING, not dryRun, strict
        $this->assertSame(Command::FAILURE, ResultType::WARNING->resolveErrorToCommandExitCode(false, true));

        // WARNING, dryRun, strict
        $this->assertSame(Command::FAILURE, ResultType::WARNING->resolveErrorToCommandExitCode(true, true));

        // SUCCESS, not dryRun, not strict
        $this->assertSame(Command::SUCCESS, ResultType::SUCCESS->resolveErrorToCommandExitCode(false, false));

        // SUCCESS, dryRun, not strict
        $this->assertSame(Command::SUCCESS, ResultType::SUCCESS->resolveErrorToCommandExitCode(true, false));

        // SUCCESS, not dryRun, strict
        $this->assertSame(Command::SUCCESS, ResultType::SUCCESS->resolveErrorToCommandExitCode(false, true));

        // SUCCESS, dryRun, strict
        $this->assertSame(Command::SUCCESS, ResultType::SUCCESS->resolveErrorToCommandExitCode(true, true));
    }

    public function testToString(): void
    {
        $this->assertSame('Success', ResultType::SUCCESS->toString());
        $this->assertSame('Warning', ResultType::WARNING->toString());
        $this->assertSame('Error', ResultType::ERROR->toString());
    }

    public function testToColorString(): void
    {
        $this->assertSame('green', ResultType::SUCCESS->toColorString());
        $this->assertSame('yellow', ResultType::WARNING->toColorString());
        $this->assertSame('red', ResultType::ERROR->toColorString());
    }

    public function testMaxWithSameValues(): void
    {
        $this->assertSame(ResultType::ERROR, ResultType::ERROR->max(ResultType::ERROR));
        $this->assertSame(ResultType::WARNING, ResultType::WARNING->max(ResultType::WARNING));
    }
}
