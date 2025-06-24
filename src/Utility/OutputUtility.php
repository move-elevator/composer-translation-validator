<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Utility;

use Symfony\Component\Console\Output\OutputInterface;

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
