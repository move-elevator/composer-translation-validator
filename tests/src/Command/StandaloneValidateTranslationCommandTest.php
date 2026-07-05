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

namespace MoveElevator\ComposerTranslationValidator\Tests\Command;

use MoveElevator\ComposerTranslationValidator\Command\StandaloneValidateTranslationCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * StandaloneValidateTranslationCommandTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
#[CoversClass(StandaloneValidateTranslationCommand::class)]
final class StandaloneValidateTranslationCommandTest extends TestCase
{
    public function testExecuteWithValidArguments(): void
    {
        $commandTester = $this->createCommandTester();

        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/translations/xliff/success'],
        ]);

        $this->assertStringContainsString('Language validation succeeded.', $commandTester->getDisplay());
        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testExecuteWithErrors(): void
    {
        $commandTester = $this->createCommandTester();

        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/translations/xliff/fail'],
        ]);

        $this->assertStringContainsString('Language validation failed', $commandTester->getDisplay());
        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }

    public function testExecuteWithJsonFormat(): void
    {
        $commandTester = $this->createCommandTester();

        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/translations/xliff/success'],
            '--format' => 'json',
        ]);

        $output = json_decode($commandTester->getDisplay(), true);
        $this->assertIsArray($output);
        $this->assertSame(0, $output['status']);
        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    private function createCommandTester(): CommandTester
    {
        return new CommandTester(new StandaloneValidateTranslationCommand());
    }
}
