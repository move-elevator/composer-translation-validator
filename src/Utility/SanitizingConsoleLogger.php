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

use Stringable;
use Symfony\Component\Console\Logger\ConsoleLogger;

use function is_string;

/**
 * SanitizingConsoleLogger.
 *
 * File names and parser messages come from the validated project and may carry
 * console markup, escape sequences or line breaks, so every message is sanitized
 * before the console logger prints it.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class SanitizingConsoleLogger extends ConsoleLogger
{
    /**
     * @param string|Stringable    $message untyped, the parent signature differs between Symfony versions
     * @param array<string, mixed> $context
     */
    public function log($level, $message, array $context = []): void
    {
        $context = array_map(
            static fn ($value) => is_string($value) || $value instanceof Stringable
                ? OutputUtility::sanitizeForConsole((string) $value)
                : $value,
            $context,
        );

        parent::log($level, OutputUtility::sanitizeForConsole((string) $message), $context);
    }
}
