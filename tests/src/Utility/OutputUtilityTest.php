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

use MoveElevator\ComposerTranslationValidator\Utility\OutputUtility;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * OutputUtilityTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
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

    public function testStripControlCharactersRemovesAnsiEscapes(): void
    {
        $input = "before\x1b[31mred\x1b[0mafter";

        $this->assertSame('before[31mred[0mafter', OutputUtility::stripControlCharacters($input));
    }

    public function testStripControlCharactersRemovesNullAndDelete(): void
    {
        $this->assertSame('clean', OutputUtility::stripControlCharacters("cl\x00ea\x7fn"));
    }

    public function testStripControlCharactersKeepsTabAndNewline(): void
    {
        $input = "line1\nline2\tend";

        $this->assertSame($input, OutputUtility::stripControlCharacters($input));
    }
}
