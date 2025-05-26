<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use MoveElevator\ComposerTranslationValidator\Capability\ValidateTranslationCommandProvider;

class Plugin implements PluginInterface, Capable
{
    public function activate(Composer $composer, IOInterface $io): void
    {
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    public function getCapabilities(): array
    {
        return [
            CommandProvider::class => ValidateTranslationCommandProvider::class,
        ];
    }
}
