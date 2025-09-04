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

namespace MoveElevator\ComposerTranslationValidator\Utility;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @package ComposerTranslationValidator
 */

class OutputUtility
{
    public static function debug(OutputInterface $output, string $message, bool $veryVerbose = false, bool $newLine = true): void
    {
        if (!$output->isVerbose()) {
            return;
        }

        if (!$output->isVeryVerbose() && $veryVerbose) {
            return;
        }

        if ($veryVerbose) {
            $message = '<fg=gray>'.$message.'</>';
        }

        $newLine ? $output->writeln($message) : $output->write($message);
    }
}
