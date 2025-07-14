<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Command;

use Composer\Console\Application;
use MoveElevator\ComposerTranslationValidator\Command\ValidateTranslationCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(ValidateTranslationCommand::class)]
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
        } catch (\Throwable $e) {
            $this->assertStringContainsString(
                'Class "Invalid\Validator\Class" not found',
                $e->getMessage()
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
}
