<?php

declare(strict_types=1);

namespace KonradMichalik\ComposerTranslationValidator\Capability;

use Composer\Plugin\Capability\CommandProvider;
use KonradMichalik\ComposerTranslationValidator\Command\ValidateTranslationCommand;

class ValidateTranslationCommandProvider implements CommandProvider
{
    public function getCommands(): array
    {
        return [
            new ValidateTranslationCommand(),
        ];
    }
}
