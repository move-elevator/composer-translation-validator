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

namespace MoveElevator\ComposerTranslationValidator\Tests\Command;

use MoveElevator\ComposerTranslationValidator\Command\ValidateTranslationCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ValidateTranslationCommand::class)]
/**
 * ValidateTranslationCommandConfigTestSimple.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class ValidateTranslationCommandConfigTestSimple extends TestCase
{
    public function testCommandHasConfigOption(): void
    {
        $command = new ValidateTranslationCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('config'));
        $this->assertSame('c', $definition->getOption('config')->getShortcut());
        $this->assertSame('Path to the configuration file', $definition->getOption('config')->getDescription());
    }
}
