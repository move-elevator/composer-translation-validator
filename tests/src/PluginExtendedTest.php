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

use Composer\Composer;
use Composer\IO\IOInterface;
use MoveElevator\ComposerTranslationValidator\Plugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Plugin::class)]
/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 *
 * @package ComposerTranslationValidator
 */

class PluginExtendedTest extends TestCase
{
    private Plugin $plugin;

    protected function setUp(): void
    {
        $this->plugin = new Plugin();
    }

    public function testActivate(): void
    {
        $composer = $this->createMock(Composer::class);
        $io = $this->createMock(IOInterface::class);

        // Should not throw any exception - test passes if no exception is thrown
        $this->plugin->activate($composer, $io);
        $this->addToAssertionCount(1);
    }

    public function testDeactivate(): void
    {
        $composer = $this->createMock(Composer::class);
        $io = $this->createMock(IOInterface::class);

        // Should not throw any exception - test passes if no exception is thrown
        $this->plugin->deactivate($composer, $io);
        $this->addToAssertionCount(1);
    }

    public function testUninstall(): void
    {
        $composer = $this->createMock(Composer::class);
        $io = $this->createMock(IOInterface::class);

        // Should not throw any exception - test passes if no exception is thrown
        $this->plugin->uninstall($composer, $io);
        $this->addToAssertionCount(1);
    }
}
