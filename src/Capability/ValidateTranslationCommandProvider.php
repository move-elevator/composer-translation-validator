<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Capability;

use Composer\Plugin\Capability\CommandProvider;
use MoveElevator\ComposerTranslationValidator\Command\ValidateTranslationCommand;

class ValidateTranslationCommandProvider implements CommandProvider
{
    public function getCommands(): array
    {
        return [
            new ValidateTranslationCommand(),
        ];
    }
}
