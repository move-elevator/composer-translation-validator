<?php

declare(strict_types=1);

/*
 * This file is part of the Composer plugin "composer-translation-validator".
 *
 * Copyright (C) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace MoveElevator\ComposerTranslationValidator\Tests\Command;

use Composer\Console\Application;
use MoveElevator\ComposerTranslationValidator\Command\ValidateTranslationCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;

#[CoversClass(ValidateTranslationCommand::class)]
/**
 * ValidateTranslationCommandTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 */
class ValidateTranslationCommandTest extends TestCase
{
    public function testExecuteWithValidArguments(): void
    {
        $application = new Application();
        $application->add(new ValidateTranslationCommand());

        $command = $application->find('validate-translations');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/translations/xliff/success'],
            '--dry-run' => true,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Language validation succeeded.', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testExecuteWithNoPaths(): void
    {
        $application = new Application();
        $application->add(new ValidateTranslationCommand());

        $command = $application->find('validate-translations');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'path' => [],
        ]);

        $this->assertStringContainsString('No paths provided.', $commandTester->getDisplay());
        $this->assertSame(1, $commandTester->getStatusCode());
    }

    public function testExecuteWithNoFilesFound(): void
    {
        $application = new Application();
        $application->add(new ValidateTranslationCommand());

        $command = $application->find('validate-translations');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/empty'],
        ]);

        $this->assertStringContainsString('No files found in the specified directories.', $commandTester->getDisplay());
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testExecuteWithInvalidValidator(): void
    {
        $application = new Application();
        $application->add(new ValidateTranslationCommand());

        $command = $application->find('validate-translations');
        $commandTester = new CommandTester($command);

        try {
            $commandTester->execute([
                'path' => [__DIR__.'/../Fixtures/translations/xliff/success'],
                '--only' => 'Invalid\\Validator\\Class',
            ]);
            $this->fail('Expected exception was not thrown.');
        } catch (Throwable $e) {
            $this->assertStringContainsString(
                'Class "Invalid\Validator\Class" not found',
                $e->getMessage(),
            );
        }
    }

    public function testExecuteWithErrors(): void
    {
        $application = new Application();
        $application->add(new ValidateTranslationCommand());

        $command = $application->find('validate-translations');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/translations/xliff/fail'],
        ]);

        $this->assertStringContainsString('Language validation failed', $commandTester->getDisplay());
        $this->assertSame(1, $commandTester->getStatusCode());
    }

    public function testExecuteWithErrorsAndDryRun(): void
    {
        $application = new Application();
        $application->add(new ValidateTranslationCommand());

        $command = $application->find('validate-translations');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/translations/xliff/fail'],
            '--dry-run' => true,
        ]);

        $this->assertStringContainsString('Language validation failed', $commandTester->getDisplay());
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testExecuteWithJsonFormat(): void
    {
        $application = new Application();
        $application->add(new ValidateTranslationCommand());

        $command = $application->find('validate-translations');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/translations/xliff/success'],
            '--format' => 'json',
        ]);

        $output = json_decode($commandTester->getDisplay(), true);
        $this->assertIsArray($output);
        $this->assertArrayHasKey('status', $output);
        $this->assertSame(0, $output['status']);
        $this->assertArrayHasKey('message', $output);
        $this->assertStringContainsString('Language validation succeeded.', (string) $output['message']);
        $this->assertArrayHasKey('issues', $output);
        $this->assertEmpty($output['issues']);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testExecuteWithErrorsAndVerboseOutput(): void
    {
        $application = new Application();
        $application->add(new ValidateTranslationCommand());

        $command = $application->find('validate-translations');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/translations/xliff/fail'],
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $this->assertStringContainsString('Language validation failed', $commandTester->getDisplay());
        // In verbose mode, validator names should be shown grouped by file
        $this->assertStringContainsString('MismatchValidator', $commandTester->getDisplay());
        $this->assertSame(1, $commandTester->getStatusCode());
    }

    public function testExecuteWithJsonFormatAndErrors(): void
    {
        $application = new Application();
        $application->add(new ValidateTranslationCommand());

        $command = $application->find('validate-translations');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/translations/xliff/fail'],
            '--format' => 'json',
        ]);

        $output = json_decode($commandTester->getDisplay(), true);
        $this->assertIsArray($output);
        $this->assertArrayHasKey('status', $output);
        $this->assertSame(1, $output['status']);
        $this->assertArrayHasKey('message', $output);
        $this->assertStringContainsString('Language validation failed with errors.', (string) $output['message']);
        $this->assertArrayHasKey('issues', $output);
        $this->assertNotEmpty($output['issues']);
        $this->assertSame(1, $commandTester->getStatusCode());
    }

    public function testExecuteWithInvalidFormat(): void
    {
        $application = new Application();
        $application->add(new ValidateTranslationCommand());

        $command = $application->find('validate-translations');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/translations/xliff/success'],
            '--format' => 'invalid',
        ]);

        $this->assertStringContainsString('Invalid output format specified.', $commandTester->getDisplay());
        $this->assertSame(1, $commandTester->getStatusCode());
    }

    public function testExecuteWithNullValidator(): void
    {
        $application = new Application();
        $application->add(new ValidateTranslationCommand());

        $command = $application->find('validate-translations');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/translations/xliff/success'],
            '--skip' => null,
        ]);

        $this->assertStringContainsString('Language validation succeeded.', $commandTester->getDisplay());
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testExecuteWithRecursiveOption(): void
    {
        $application = new Application();
        $application->add(new ValidateTranslationCommand());

        $command = $application->find('validate-translations');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/translations/xliff/success'],
            '--recursive' => true,
            '--dry-run' => true,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Language validation', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testExecuteWithRecursiveShortOption(): void
    {
        $application = new Application();
        $application->add(new ValidateTranslationCommand());

        $command = $application->find('validate-translations');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/translations/xliff/success'],
            '-r' => true,
            '--dry-run' => true,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Language validation', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testExecuteWithRecursiveAndJsonFormat(): void
    {
        $application = new Application();
        $application->add(new ValidateTranslationCommand());

        $command = $application->find('validate-translations');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/translations/xliff/success'],
            '--recursive' => true,
            '--format' => 'json',
        ]);

        $output = json_decode($commandTester->getDisplay(), true);
        $this->assertIsArray($output);
        $this->assertArrayHasKey('status', $output);
    }

    public function testRecursiveOptionCanBeUsedWithOtherOptions(): void
    {
        $application = new Application();
        $application->add(new ValidateTranslationCommand());

        $command = $application->find('validate-translations');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/translations/xliff/success'],
            '--recursive' => true,
            '--dry-run' => true,
            '--strict' => true,
        ]);

        $this->assertSame(0, $commandTester->getStatusCode());
    }
}
