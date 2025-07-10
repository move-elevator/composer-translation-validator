<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests;

use Composer\Composer;
use Composer\IO\IOInterface;
use MoveElevator\ComposerTranslationValidator\Plugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Plugin::class)]
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
