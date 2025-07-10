<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Command;

use MoveElevator\ComposerTranslationValidator\Command\ValidateTranslationCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ValidateTranslationCommand::class)]
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
