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

namespace MoveElevator\ComposerTranslationValidator\Validator;

use Symfony\Component\Console\Command\Command;


/**
 * ResultType.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
enum ResultType: int
{
    case SUCCESS = 0;
    case WARNING = 1;
    case ERROR = 2;

    public function max(self $other): self
    {
        return $this->value >= $other->value ? $this : $other;
    }

    public function notFullySuccessful(): bool
    {
        return self::SUCCESS !== $this;
    }

    public function resolveErrorToCommandExitCode(bool $dryRun, bool $strict): int
    {
        if (self::ERROR === $this && !$dryRun) {
            return Command::FAILURE;
        }

        if (self::WARNING === $this && $strict) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    public function toString(): string
    {
        return match ($this) {
            self::SUCCESS => 'Success',
            self::WARNING => 'Warning',
            self::ERROR => 'Error',
        };
    }

    public function toColorString(): string
    {
        return match ($this) {
            self::SUCCESS => 'green',
            self::WARNING => 'yellow',
            self::ERROR => 'red',
        };
    }
}
