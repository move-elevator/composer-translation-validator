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

namespace MoveElevator\ComposerTranslationValidator\Tests;

use Composer\Plugin\Capability\CommandProvider;
use MoveElevator\ComposerTranslationValidator\Capability\ValidateTranslationCommandProvider;
use MoveElevator\ComposerTranslationValidator\Plugin;
use PHPUnit\Framework\TestCase;

final class PluginTest extends TestCase
{
    public function testGetCapabilities(): void
    {
        $plugin = new Plugin();
        $capabilities = $plugin->getCapabilities();

        $this->assertArrayHasKey(CommandProvider::class, $capabilities);
        $this->assertSame(ValidateTranslationCommandProvider::class, $capabilities[CommandProvider::class]);
    }

    public function testActivate(): void
    {
        $plugin = new Plugin();
        $composer = $this->createMock(\Composer\Composer::class);
        $io = $this->createMock(\Composer\IO\IOInterface::class);

        // Should not throw any exception
        $plugin->activate($composer, $io);
        $this->addToAssertionCount(1);
    }

    public function testDeactivate(): void
    {
        $plugin = new Plugin();
        $composer = $this->createMock(\Composer\Composer::class);
        $io = $this->createMock(\Composer\IO\IOInterface::class);

        // Should not throw any exception
        $plugin->deactivate($composer, $io);
        $this->addToAssertionCount(1);
    }

    public function testUninstall(): void
    {
        $plugin = new Plugin();
        $composer = $this->createMock(\Composer\Composer::class);
        $io = $this->createMock(\Composer\IO\IOInterface::class);

        // Should not throw any exception
        $plugin->uninstall($composer, $io);
        $this->addToAssertionCount(1);
    }
}
