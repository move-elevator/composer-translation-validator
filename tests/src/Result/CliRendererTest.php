<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\Result\CliRenderer;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class CliRendererTest extends TestCase
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

    public function testRenderResultSuccess(): void
    {
        $renderer = new CliRenderer(
            $this->loggerMock,
            $this->output,
            $this->inputMock,
            ResultType::SUCCESS,
            []
        );

        $exitCode = $renderer->renderResult();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Language validation succeeded.', $this->output->fetch());
    }

    public function testRenderResultError(): void
    {
        $issues = [
            'MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateKeysValidator' => [
                'path' => [
                    'file1.xlf' => [
                        [
                            'file' => 'file1.xlf',
                            'issues' => [
                                'key2' => 2,
                            ],
                            'parser' => 'MoveElevator\\ComposerTranslationValidator\\Parser\\XliffParser',
                            'type' => 'DuplicateKeysValidator',
                        ],
                    ],
                ],
            ],
        ];

        $renderer = new CliRenderer(
            $this->loggerMock,
            $this->output,
            $this->inputMock,
            ResultType::ERROR,
            $issues
        );

        $exitCode = $renderer->renderResult();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Language validation failed.', $this->output->fetch());
    }

    public function testRenderResultErrorDryRun(): void
    {
        $issues = [
            'MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateKeysValidator' => [
                'path' => [
                    'file1.xlf' => [
                        [
                            'file' => 'file1.xlf',
                            'issues' => [
                                'key2' => 2,
                            ],
                            'parser' => 'MoveElevator\\ComposerTranslationValidator\\Parser\\XliffParser',
                            'type' => 'DuplicateKeysValidator',
                        ],
                    ],
                ],
            ],
        ];

        $validatorMock = $this->createMock(ValidatorInterface::class);
        $validatorMock->method('explain')->willReturn('Explanation for DuplicateKeysValidator');
        $validatorMock->method('renderIssueSets');

        $renderer = new CliRenderer(
            $this->loggerMock,
            $this->output,
            $this->inputMock,
            ResultType::ERROR,
            $issues,
            true // dryRun
        );

        $exitCode = $renderer->renderResult();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Language validation failed and completed in dry-run mode.', $this->output->fetch());
    }

    public function testRenderResultWarningStrict(): void
    {
        $issues = [
            'MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateKeysValidator' => [
                'path' => [
                    'file1.xlf' => [
                        [
                            'file' => 'file1.xlf',
                            'issues' => [
                                'key2' => 2,
                            ],
                            'parser' => 'MoveElevator\\ComposerTranslationValidator\\Parser\\XliffParser',
                            'type' => 'DuplicateKeysValidator',
                        ],
                    ],
                ],
            ],
        ];

        $validatorMock = $this->createMock(ValidatorInterface::class);
        $validatorMock->method('explain')->willReturn('Explanation for MismatchValidator');
        $validatorMock->method('renderIssueSets');

        $renderer = new CliRenderer(
            $this->loggerMock,
            $this->output,
            $this->inputMock,
            ResultType::WARNING,
            $issues,
            false, // dryRun
            true // strict
        );

        $exitCode = $renderer->renderResult();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Language validation failed.', $this->output->fetch());
    }

    public function testRenderIssuesVerboseOutput(): void
    {
        $issues = [
            'MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateKeysValidator' => [
                'path' => [
                    'file1.xlf' => [
                        [
                            'file' => 'file1.xlf',
                            'issues' => [
                                'key2' => 2,
                            ],
                            'parser' => 'MoveElevator\\ComposerTranslationValidator\\Parser\\XliffParser',
                            'type' => 'DuplicateKeysValidator',
                        ],
                    ],
                ],
            ],
        ];

        $validatorMock = $this->createMock(ValidatorInterface::class);
        $validatorMock->method('explain')->willReturn('Explanation for DuplicateKeysValidator');
        $validatorMock->method('renderIssueSets');

        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        $renderer = new CliRenderer(
            $this->loggerMock,
            $this->output,
            $this->inputMock,
            ResultType::ERROR,
            $issues
        );

        // Access protected method using reflection
        $reflection = new \ReflectionClass($renderer);
        $method = $reflection->getMethod('renderIssues');
        $method->setAccessible(true);
        $method->invoke($renderer);

        $this->assertStringContainsString('Explanation', $this->output->fetch());
    }
}
