<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Tests;

use Composer\Plugin\Capability\CommandProvider;
use MoveElevator\ComposerTranslationValidator\Capability\ValidateTranslationCommandProvider;
use MoveElevator\ComposerTranslationValidator\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * PluginTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
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
        $composer = $this->createStub(\Composer\Composer::class);
        $io = $this->createStub(\Composer\IO\IOInterface::class);

        // Should not throw any exception
        $plugin->activate($composer, $io);
        $this->addToAssertionCount(1);
    }

    public function testDeactivate(): void
    {
        $plugin = new Plugin();
        $composer = $this->createStub(\Composer\Composer::class);
        $io = $this->createStub(\Composer\IO\IOInterface::class);

        // Should not throw any exception
        $plugin->deactivate($composer, $io);
        $this->addToAssertionCount(1);
    }

    public function testUninstall(): void
    {
        $plugin = new Plugin();
        $composer = $this->createStub(\Composer\Composer::class);
        $io = $this->createStub(\Composer\IO\IOInterface::class);

        // Should not throw any exception
        $plugin->uninstall($composer, $io);
        $this->addToAssertionCount(1);
    }
}
