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

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\Result\FormatType;
use MoveElevator\ComposerTranslationValidator\Result\Output;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResult;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * OutputTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 */
final class OutputTest extends TestCase
{
    private LoggerInterface $loggerMock;
    private InputInterface $inputMock;
    private BufferedOutput $output;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->inputMock = $this->createMock(InputInterface::class);
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
