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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidationResultRendererFactory
{
    public static function create(
        FormatType $format,
        OutputInterface $output,
        InputInterface $input,
        bool $dryRun = false,
        bool $strict = false,
    ): ValidationResultRendererInterface {
        return match ($format) {
            FormatType::CLI => new ValidationResultCliRenderer($output, $input, $dryRun, $strict),
            FormatType::JSON => new ValidationResultJsonRenderer($output, $dryRun, $strict),
            FormatType::GITHUB => new ValidationResultGitHubRenderer($output, $dryRun, $strict),
        };
    }
}
