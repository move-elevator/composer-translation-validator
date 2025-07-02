<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\Result\JsonRenderer;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

final class JsonRendererTest extends TestCase
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
        $renderer = new JsonRenderer(
            $this->loggerMock,
            $this->output,
            $this->inputMock,
            ResultType::SUCCESS,
            []
        );

        $exitCode = $renderer->renderResult();

        $this->assertSame(0, $exitCode);
        $output = json_decode($this->output->fetch(), true);
        $this->assertSame(0, $output['status']);
        $this->assertSame('Language validation succeeded.', $output['message']);
        $this->assertEmpty($output['issues']);
    }

    public function testRenderResultError(): void
    {
        $issues = [
            'MoveElevator\ComposerTranslationValidator\Validator\DuplicateKeysValidator' => [
                'file1.xlf' => [
                    'set1' => [
                        'file' => 'file1.xlf',
                        'issues' => ['keyA' => 2],
                        'parser' => 'MoveElevator\ComposerTranslationValidator\Parser\XliffParser',
                        'type' => 'DuplicateKeysValidator',
                    ],
                ],
            ],
        ];

        $renderer = new JsonRenderer(
            $this->loggerMock,
            $this->output,
            $this->inputMock,
            ResultType::ERROR,
            $issues
        );

        $exitCode = $renderer->renderResult();

        $this->assertSame(1, $exitCode);
        $output = json_decode($this->output->fetch(), true);
        $this->assertSame(1, $output['status']);
        $this->assertSame('Language validation failed.', $output['message']);
        $this->assertNotEmpty($output['issues']);
    }

    public function testRenderResultErrorDryRun(): void
    {
        $issues = [
            'MoveElevator\ComposerTranslationValidator\Validator\DuplicateKeysValidator' => [
                'file1.xlf' => [
                    'set1' => [
                        'file' => 'file1.xlf',
                        'issues' => ['keyA' => 2],
                        'parser' => 'MoveElevator\ComposerTranslationValidator\Parser\XliffParser',
                        'type' => 'DuplicateKeysValidator',
                    ],
                ],
            ],
        ];

        $renderer = new JsonRenderer(
            $this->loggerMock,
            $this->output,
            $this->inputMock,
            ResultType::ERROR,
            $issues,
            true // dryRun
        );

        $exitCode = $renderer->renderResult();

        $this->assertSame(0, $exitCode);
        $output = json_decode($this->output->fetch(), true);
        $this->assertSame(0, $output['status']);
        $this->assertSame('Language validation failed and completed in dry-run mode.', $output['message']);
        $this->assertNotEmpty($output['issues']);
    }

    public function testRenderResultWarningStrict(): void
    {
        $issues = [
            'MoveElevator\ComposerTranslationValidator\Validator\MismatchValidator' => [
                'file1.xlf' => [
                    'set1' => [
                        'file' => 'file1.xlf',
                        'issues' => ['keyA' => 2],
                        'parser' => 'MoveElevator\ComposerTranslationValidator\Parser\XliffParser',
                        'type' => 'MismatchValidator',
                    ],
                ],
            ],
        ];

        $renderer = new JsonRenderer(
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
        $output = json_decode($this->output->fetch(), true);
        $this->assertSame(1, $output['status']);
        $this->assertSame('Language validation failed.', $output['message']);
        $this->assertNotEmpty($output['issues']);
    }

    public function testRenderResultWarningNotStrict(): void
    {
        $issues = [
            'MoveElevator\ComposerTranslationValidator\Validator\MismatchValidator' => [
                'file1.xlf' => [
                    'set1' => [
                        'file' => 'file1.xlf',
                        'issues' => ['keyA' => 2],
                        'parser' => 'MoveElevator\ComposerTranslationValidator\Parser\XliffParser',
                        'type' => 'MismatchValidator',
                    ],
                ],
            ],
        ];

        $renderer = new JsonRenderer(
            $this->loggerMock,
            $this->output,
            $this->inputMock,
            ResultType::WARNING,
            $issues,
            false, // dryRun
            false // strict
        );

        $exitCode = $renderer->renderResult();

        $this->assertSame(0, $exitCode);
        $output = json_decode($this->output->fetch(), true);
        $this->assertSame(0, $output['status']);
        $this->assertSame('Language validation failed.', $output['message']);
        $this->assertNotEmpty($output['issues']);
    }
}
