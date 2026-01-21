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

namespace MoveElevator\ComposerTranslationValidator\Tests\Capability;

use MoveElevator\ComposerTranslationValidator\Capability\ValidateTranslationCommandProvider;
use MoveElevator\ComposerTranslationValidator\Command\ValidateTranslationCommand;
use PHPUnit\Framework\TestCase;

/**
 * ValidateTranslationCommandProviderTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class ValidateTranslationCommandProviderTest extends TestCase
{
    public function testGetCommands(): void
    {
        $provider = new ValidateTranslationCommandProvider();
        $commands = $provider->getCommands();

        $this->assertCount(1, $commands);
        $this->assertInstanceOf(ValidateTranslationCommand::class, $commands[0]);
    }
}
