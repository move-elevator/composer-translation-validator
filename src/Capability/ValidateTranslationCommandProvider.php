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

namespace MoveElevator\ComposerTranslationValidator\Capability;

use Composer\Plugin\Capability\CommandProvider;
use MoveElevator\ComposerTranslationValidator\Command\ValidateTranslationCommand;

/**
 * ValidateTranslationCommandProvider.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class ValidateTranslationCommandProvider implements CommandProvider
{
    public function getCommands(): array
    {
        return [
            new ValidateTranslationCommand(),
        ];
    }
}
