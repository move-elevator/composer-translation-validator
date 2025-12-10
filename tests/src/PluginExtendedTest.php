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

use Composer\Composer;
use Composer\IO\IOInterface;
use MoveElevator\ComposerTranslationValidator\Plugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * PluginExtendedTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
#[CoversClass(Plugin::class)]
/**
 * PluginExtendedTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class PluginExtendedTest extends TestCase
{
    private Plugin $plugin;

    protected function setUp(): void
    {
        $this->plugin = new Plugin();
    }

    public function testActivate(): void
    {
        $composer = $this->createStub(Composer::class);
        $io = $this->createStub(IOInterface::class);

        // Should not throw any exception - test passes if no exception is thrown
        $this->plugin->activate($composer, $io);
        $this->addToAssertionCount(1);
    }

    public function testDeactivate(): void
    {
        $composer = $this->createStub(Composer::class);
        $io = $this->createStub(IOInterface::class);

        // Should not throw any exception - test passes if no exception is thrown
        $this->plugin->deactivate($composer, $io);
        $this->addToAssertionCount(1);
    }

    public function testUninstall(): void
    {
        $composer = $this->createStub(Composer::class);
        $io = $this->createStub(IOInterface::class);

        // Should not throw any exception - test passes if no exception is thrown
        $this->plugin->uninstall($composer, $io);
        $this->addToAssertionCount(1);
    }
}
