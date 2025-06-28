<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Capability;

use MoveElevator\ComposerTranslationValidator\Capability\ValidateTranslationCommandProvider;
use MoveElevator\ComposerTranslationValidator\Command\ValidateTranslationCommand;
use PHPUnit\Framework\TestCase;

final class ValidateTranslationCommandProviderTest extends TestCase
{
    public function testGetCommands(): void
    {
        $provider = new ValidateTranslationCommandProvider();
        $commands = $provider->getCommands();

        $this->assertCount(1, $commands);
        $this->assertInstanceOf(ValidateTranslationCommand::class, $commands[0]);
    }
}
