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

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\Result\{FormatType, Output, ValidationResult};
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * OutputTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class OutputTest extends TestCase
{
    private LoggerInterface $loggerMock;
    private InputInterface $inputMock;
    private BufferedOutput $output;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loggerMock = $this->createStub(LoggerInterface::class);
        $this->inputMock = $this->createStub(InputInterface::class);
        $this->output = new BufferedOutput();
    }

    /**
     * @return iterable<string, array{ResultType, int, string}>
     */
    public static function cliSummaryProvider(): iterable
    {
        yield 'success' => [ResultType::SUCCESS, Command::SUCCESS, 'Language validation succeeded.'];
        yield 'error' => [ResultType::ERROR, Command::FAILURE, 'Language validation failed with errors.'];
        yield 'warning' => [ResultType::WARNING, Command::SUCCESS, 'Language validation completed with warnings.'];
    }

    #[DataProvider('cliSummaryProvider')]
    public function testSummarizeCliFormat(ResultType $resultType, int $expectedExitCode, string $expectedMessage): void
    {
        $output = $this->createOutput(FormatType::CLI, new ValidationResult([], $resultType));

        $exitCode = $output->summarize();

        $this->assertSame($expectedExitCode, $exitCode);
        $this->assertStringContainsString($expectedMessage, $this->output->fetch());
    }

    /**
     * @return iterable<string, array{ResultType, int, string}>
     */
    public static function jsonSummaryProvider(): iterable
    {
        yield 'success' => [ResultType::SUCCESS, Command::SUCCESS, 'Language validation succeeded.'];
        yield 'error' => [ResultType::ERROR, Command::FAILURE, 'Language validation failed with errors.'];
        yield 'warning' => [ResultType::WARNING, Command::SUCCESS, 'Language validation completed with warnings.'];
    }

    #[DataProvider('jsonSummaryProvider')]
    public function testSummarizeJsonFormat(ResultType $resultType, int $expectedStatus, string $expectedMessage): void
    {
        $output = $this->createOutput(FormatType::JSON, new ValidationResult([], $resultType));

        $exitCode = $output->summarize();

        $this->assertSame($expectedStatus, $exitCode);
        $rawOutput = $this->output->fetch();
        $this->assertJson($rawOutput, 'Output should be valid JSON');
        $jsonOutput = json_decode($rawOutput, true);
        $this->assertNotNull($jsonOutput, 'JSON output should be valid');
        $this->assertSame($expectedStatus, $jsonOutput['status']);
        $this->assertSame($expectedMessage, $jsonOutput['message']);
    }

    public function testOutputWithDryRunMode(): void
    {
        $output = $this->createOutput(
            FormatType::CLI,
            new ValidationResult([], ResultType::ERROR),
            dryRun: true,
        );

        // In dry run mode, should return SUCCESS even with errors
        $this->assertSame(Command::SUCCESS, $output->summarize());
    }

    public function testOutputWithStrictMode(): void
    {
        $output = $this->createOutput(
            FormatType::CLI,
            new ValidationResult([], ResultType::WARNING),
            strict: true,
        );

        // In strict mode, warnings should return FAILURE
        $this->assertSame(Command::FAILURE, $output->summarize());
    }

    private function createOutput(
        FormatType $format,
        ValidationResult $validationResult,
        bool $dryRun = false,
        bool $strict = false,
    ): Output {
        return new Output(
            $this->loggerMock,
            $this->output,
            $this->inputMock,
            $format,
            $validationResult,
            $dryRun,
            $strict,
        );
    }
}
