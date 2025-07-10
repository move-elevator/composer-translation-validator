<?php

declare(strict_types=1);

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
