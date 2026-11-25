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

use Symfony\Component\Console\Formatter\OutputFormatter;
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

    /**
     * Removes control characters except tab and newline, for text that is
     * rendered or escaped elsewhere and only must not carry raw escape sequences.
     */
    public static function stripControlCharacters(string $value): string
    {
        return preg_replace('/[\x00-\x08\x0B-\x1F\x7F]|\xC2[\x80-\x9F]/', '', $value) ?? $value;
    }

    /**
     * Makes an untrusted value safe to embed in a one-line console message.
     *
     * Line breaks become a visible \n or \r so the value cannot start a new
     * output line (e.g. a GitHub Actions workflow command). Remaining control
     * characters are removed first, then formatter markup is escaped. The order
     * matters: escaping adds backslashes that stripping must not touch.
     */
    public static function sanitizeForConsole(string $value): string
    {
        $value = str_replace(["\r", "\n"], ['\\r', '\\n'], $value);
        $value = preg_replace('/[\x00-\x08\x0B-\x1F\x7F]|\xC2[\x80-\x9F]/', '', $value) ?? $value;

        return OutputFormatter::escape($value);
    }
}
