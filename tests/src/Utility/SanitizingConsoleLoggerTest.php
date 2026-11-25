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

namespace MoveElevator\ComposerTranslationValidator\Tests\Utility;

use MoveElevator\ComposerTranslationValidator\Utility\SanitizingConsoleLogger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * SanitizingConsoleLoggerTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class SanitizingConsoleLoggerTest extends TestCase
{
    public function testMessageCannotInjectLinesStylesOrEscapeSequences(): void
    {
        $output = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true);
        $logger = new SanitizingConsoleLogger($output);

        $logger->error("bad\n::error file=x::PWNED <bg=red>RED</> \e[2J<href=https://evil.example>x</>");

        $written = $output->fetch();

        self::assertSame(1, substr_count($written, "\n"));
        self::assertStringContainsString('<bg=red>RED</>', $written);
        self::assertStringContainsString('<href=https://evil.example>x</>', $written);
        self::assertStringNotContainsString("\e[2J", $written);
        self::assertStringNotContainsString("\e[41m", $written);
        self::assertStringNotContainsString("\e]8;", $written);
    }

    public function testContextValuesAreSanitizedToo(): void
    {
        $output = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true);
        $logger = new SanitizingConsoleLogger($output);

        $logger->error('Cannot read {file}', ['file' => "x\n::error::PWNED <bg=red>RED</> \e[2J"]);

        $written = $output->fetch();

        self::assertSame(1, substr_count($written, "\n"));
        self::assertStringContainsString('<bg=red>RED</>', $written);
        self::assertStringNotContainsString("\e[2J", $written);
        self::assertStringNotContainsString("\e[41m", $written);
    }
}
