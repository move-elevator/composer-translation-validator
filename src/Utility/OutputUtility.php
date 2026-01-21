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

namespace MoveElevator\ComposerTranslationValidator\Utility;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * OutputUtility.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
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
