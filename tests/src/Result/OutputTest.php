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

    public function testSummarizeCliFormat(): void
    {
        /** @var array<\MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface> $validators */
        $validators = [];
        $validationResult = new ValidationResult($validators, ResultType::SUCCESS);

        $output = new Output(
            $this->loggerMock,
            $this->output,
            $this->inputMock,
            FormatType::CLI,
            $validationResult,
        );

        $exitCode = $output->summarize();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Language validation succeeded.', $this->output->fetch());
    }

    public function testSummarizeJsonFormat(): void
    {
        /** @var array<\MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface> $validators */
        $validators = [];
        $validationResult = new ValidationResult($validators, ResultType::SUCCESS);

        $output = new Output(
            $this->loggerMock,
            $this->output,
            $this->inputMock,
            FormatType::JSON,
            $validationResult,
        );

        $exitCode = $output->summarize();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $rawOutput = $this->output->fetch();
        $jsonOutput = json_decode($rawOutput, true);
        $this->assertNotNull($jsonOutput, 'JSON output should be valid');
        $this->assertJson($rawOutput, 'Output should be valid JSON');
        $this->assertSame(Command::SUCCESS, $jsonOutput['status']);
        $this->assertSame('Language validation succeeded.', $jsonOutput['message']);
    }

    public function testSummarizeCliFormatFailure(): void
    {
        /** @var array<\MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface> $validators */
        $validators = [];
        $validationResult = new ValidationResult($validators, ResultType::ERROR);

        $output = new Output(
            $this->loggerMock,
            $this->output,
            $this->inputMock,
            FormatType::CLI,
            $validationResult,
        );

        $exitCode = $output->summarize();

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Language validation failed with errors.', $this->output->fetch());
    }

    public function testSummarizeWithWarnings(): void
    {
        /** @var array<\MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface> $validators */
        $validators = [];
        $validationResult = new ValidationResult($validators, ResultType::WARNING);

        $output = new Output(
            $this->loggerMock,
            $this->output,
            $this->inputMock,
            FormatType::CLI,
            $validationResult,
        );

        $exitCode = $output->summarize();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Language validation completed with warnings.', $this->output->fetch());
    }

    public function testSummarizeJsonFormatWithFailure(): void
    {
        /** @var array<\MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface> $validators */
        $validators = [];
        $validationResult = new ValidationResult($validators, ResultType::ERROR);

        $output = new Output(
            $this->loggerMock,
            $this->output,
            $this->inputMock,
            FormatType::JSON,
            $validationResult,
        );

        $exitCode = $output->summarize();

        $this->assertSame(Command::FAILURE, $exitCode);
        $rawOutput = $this->output->fetch();
        $jsonOutput = json_decode($rawOutput, true);
        $this->assertNotNull($jsonOutput);
        $this->assertSame(Command::FAILURE, $jsonOutput['status']);
        $this->assertSame('Language validation failed with errors.', $jsonOutput['message']);
    }

    public function testSummarizeJsonFormatWithWarnings(): void
    {
        /** @var array<\MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface> $validators */
        $validators = [];
        $validationResult = new ValidationResult($validators, ResultType::WARNING);

        $output = new Output(
            $this->loggerMock,
            $this->output,
            $this->inputMock,
            FormatType::JSON,
            $validationResult,
        );

        $exitCode = $output->summarize();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $rawOutput = $this->output->fetch();
        $jsonOutput = json_decode($rawOutput, true);
        $this->assertNotNull($jsonOutput);
        $this->assertSame(Command::SUCCESS, $jsonOutput['status']);
        $this->assertSame('Language validation completed with warnings.', $jsonOutput['message']);
    }

    public function testOutputWithDryRunMode(): void
    {
        /** @var array<\MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface> $validators */
        $validators = [];
        $validationResult = new ValidationResult($validators, ResultType::ERROR);

        $output = new Output(
            $this->loggerMock,
            $this->output,
            $this->inputMock,
            FormatType::CLI,
            $validationResult,
            true, // dry run
        );

        $exitCode = $output->summarize();

        // In dry run mode, should return SUCCESS even with errors
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testOutputWithStrictMode(): void
    {
        /** @var array<\MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface> $validators */
        $validators = [];
        $validationResult = new ValidationResult($validators, ResultType::WARNING);

        $output = new Output(
            $this->loggerMock,
            $this->output,
            $this->inputMock,
            FormatType::CLI,
            $validationResult,
            false, // not dry run
            true,   // strict mode
        );

        $exitCode = $output->summarize();

        // In strict mode, warnings should return FAILURE
        $this->assertSame(Command::FAILURE, $exitCode);
    }
}
