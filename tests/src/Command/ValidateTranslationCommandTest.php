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

use Composer\Console\Application;
use MoveElevator\ComposerTranslationValidator\Command\ValidateTranslationCommand;
use MoveElevator\ComposerTranslationValidator\Validator\MismatchValidator;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider};
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(ValidateTranslationCommand::class)]
/**
 * ValidateTranslationCommandTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class ValidateTranslationCommandTest extends TestCase
{
    public function testExecuteWithValidArguments(): void
    {
        $application = new Application();
        $this->addCommandToApplication($application, new ValidateTranslationCommand());

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
        $this->addCommandToApplication($application, new ValidateTranslationCommand());

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
        $this->addCommandToApplication($application, new ValidateTranslationCommand());

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
        $this->addCommandToApplication($application, new ValidateTranslationCommand());

        $command = $application->find('validate-translations');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/translations/xliff/success'],
            '--only' => 'Invalid\\Validator\\Class',
        ]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString(
            'The "--only" option contains no valid validators.',
            $commandTester->getDisplay(),
        );
    }

    public function testExecuteWithInvalidSkipValidator(): void
    {
        $application = new Application();
        $this->addCommandToApplication($application, new ValidateTranslationCommand());

        $command = $application->find('validate-translations');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/translations/xliff/success'],
            '--skip' => 'Invalid\\Validator\\Class',
        ]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString(
            'The "--skip" option contains no valid validators.',
            $commandTester->getDisplay(),
        );
    }

    public function testWarningsDoNotEndUpInTheJsonOnStdout(): void
    {
        $directory = sys_get_temp_dir().'/tv-single-'.uniqid('', true);
        mkdir($directory);

        try {
            file_put_contents($directory.'/messages.de.yaml', 'a: x');

            $application = new Application();
            $this->addCommandToApplication($application, new ValidateTranslationCommand());
            $commandTester = new CommandTester($application->find('validate-translations'));
            $commandTester->execute(['path' => [$directory], '--format' => 'json'], ['capture_stderr_separately' => true]);
        } finally {
            @unlink($directory.'/messages.de.yaml');
            rmdir($directory);
        }

        $this->assertJson($commandTester->getDisplay());
        $this->assertStringContainsString('has no counterpart in other languages', $commandTester->getErrorOutput());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidConfigValidatorProvider(): iterable
    {
        yield 'only' => ['only', 'The "only" setting in the configuration file contains no valid validators.'];
        yield 'skip' => ['skip', 'The "skip" setting in the configuration file contains no valid validators.'];
    }

    #[DataProvider('invalidConfigValidatorProvider')]
    public function testExecuteWithInvalidValidatorInConfigFile(string $setting, string $expectedMessage): void
    {
        $configFile = $this->createConfigFile([$setting => ['Invalid\\Validator\\Class']]);

        try {
            $commandTester = $this->executeWithConfig($configFile);
        } finally {
            unlink($configFile);
        }

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString($expectedMessage, $commandTester->getDisplay());
    }

    public function testExecuteIgnoresInvalidConfigSkipWhenOnlyOptionIsGiven(): void
    {
        $configFile = $this->createConfigFile(['skip' => ['Invalid\\Validator\\Class']]);

        try {
            $application = new Application();
            $this->addCommandToApplication($application, new ValidateTranslationCommand());
            $commandTester = new CommandTester($application->find('validate-translations'));
            $commandTester->execute([
                'path' => [__DIR__.'/../Fixtures/translations/xliff/success'],
                '--config' => $configFile,
                '--only' => MismatchValidator::class,
            ]);
        } finally {
            unlink($configFile);
        }

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testExecuteIgnoresInvalidValidatorNextToValidOneInConfigFile(): void
    {
        $configFile = $this->createConfigFile(['only' => ['Invalid\\Validator\\Class', MismatchValidator::class]]);

        try {
            $commandTester = $this->executeWithConfig($configFile);
        } finally {
            unlink($configFile);
        }

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('The class "Invalid\\Validator\\Class" does not exist.', $commandTester->getDisplay());
    }

    public function testExecuteWithErrors(): void
    {
        $application = new Application();
        $this->addCommandToApplication($application, new ValidateTranslationCommand());

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
        $this->addCommandToApplication($application, new ValidateTranslationCommand());

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
        $this->addCommandToApplication($application, new ValidateTranslationCommand());

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
        $this->addCommandToApplication($application, new ValidateTranslationCommand());

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
        $this->addCommandToApplication($application, new ValidateTranslationCommand());

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
        $this->addCommandToApplication($application, new ValidateTranslationCommand());

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
        $this->addCommandToApplication($application, new ValidateTranslationCommand());

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
        $this->addCommandToApplication($application, new ValidateTranslationCommand());

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
        $this->addCommandToApplication($application, new ValidateTranslationCommand());

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
        $this->addCommandToApplication($application, new ValidateTranslationCommand());

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
        $this->addCommandToApplication($application, new ValidateTranslationCommand());

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

    public function testExecuteWithExcludeOption(): void
    {
        $application = new Application();
        $this->addCommandToApplication($application, new ValidateTranslationCommand());

        $command = $application->find('validate-translations');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/translations/xliff/success'],
            '--exclude' => '**/nonexistent/**',
            '--dry-run' => true,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Language validation', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testExecuteWithMultipleExcludePatterns(): void
    {
        $application = new Application();
        $this->addCommandToApplication($application, new ValidateTranslationCommand());

        $command = $application->find('validate-translations');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/translations/xliff/success'],
            '--exclude' => '**/backup/**,**/*.bak',
            '--dry-run' => true,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Language validation', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testExecuteWithExcludeShortOption(): void
    {
        $application = new Application();
        $this->addCommandToApplication($application, new ValidateTranslationCommand());

        $command = $application->find('validate-translations');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/translations/xliff/success'],
            '-e' => '**/temp/**',
            '--dry-run' => true,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Language validation', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createConfigFile(array $config): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'tv-config-');
        $configFile = $tempFile.'.json';
        rename($tempFile, $configFile);
        file_put_contents($configFile, json_encode(['paths' => ['translations']] + $config, \JSON_THROW_ON_ERROR));

        return $configFile;
    }

    private function executeWithConfig(string $configFile): CommandTester
    {
        $application = new Application();
        $this->addCommandToApplication($application, new ValidateTranslationCommand());

        $commandTester = new CommandTester($application->find('validate-translations'));
        $commandTester->execute([
            'path' => [__DIR__.'/../Fixtures/translations/xliff/success'],
            '--config' => $configFile,
        ]);

        return $commandTester;
    }

    /**
     * Adds a command to the application in a way compatible with both Symfony Console 5-7 and 8+.
     */
    private function addCommandToApplication(Application $application, Command $command): void
    {
        // @phpstan-ignore function.alreadyNarrowedType (method only exists in Symfony Console 8+)
        if (method_exists($application, 'addCommand')) {
            $application->addCommand($command);
        } else {
            // @phpstan-ignore method.notFound (Symfony Console < 8)
            $application->add($command);
        }
    }
}
