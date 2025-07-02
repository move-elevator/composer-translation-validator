<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\Result\FormatType;
use MoveElevator\ComposerTranslationValidator\Result\Output;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

final class OutputTest extends TestCase
{
    private MockObject|LoggerInterface $loggerMock;
    private MockObject|InputInterface $inputMock;
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
        $output = new Output(
            $this->loggerMock,
            $this->output,
            $this->inputMock,
            FormatType::CLI,
            ResultType::SUCCESS,
            []
        );

        $exitCode = $output->summarize();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Language validation succeeded.', $this->output->fetch());
    }

    public function testSummarizeJsonFormat(): void
    {
        $output = new Output(
            $this->loggerMock,
            $this->output,
            $this->inputMock,
            FormatType::JSON,
            ResultType::SUCCESS,
            []
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
        $output = new Output(
            $this->loggerMock,
            $this->output,
            $this->inputMock,
            FormatType::CLI,
            ResultType::ERROR,
            []
        );

        $exitCode = $output->summarize();

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Language validation failed.', $this->output->fetch());
    }
}
