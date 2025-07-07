<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResult;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResultCliRenderer;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ValidationResultCliRendererTest extends TestCase
{
    private ValidationResultCliRenderer $renderer;
    private BufferedOutput $output;
    private InputInterface|MockObject $input;

    protected function setUp(): void
    {
        $this->output = new BufferedOutput();
        $this->input = $this->createMock(InputInterface::class);
        $this->renderer = new ValidationResultCliRenderer(
            $this->output,
            $this->input
        );
    }

    public function testRenderWithNoIssues(): void
    {
        $validationResult = new ValidationResult([], ResultType::SUCCESS);

        $exitCode = $this->renderer->render($validationResult);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Language validation succeeded', $this->output->fetch());
    }

    public function testRenderWithIssues(): void
    {
        $validator = $this->createMockValidator();
        $issue = new Issue('test.xlf', ['key' => 'value'], 'TestParser', 'TestValidator');
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([$issue]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::ERROR,
            [['validator' => $validator, 'fileSet' => $fileSet]]
        );

        $exitCode = $this->renderer->render($validationResult);

        $this->assertSame(1, $exitCode);
        $output = $this->output->fetch();
        $this->assertStringContainsString('Validator:', $output);
        $this->assertStringContainsString('Folder Path:', $output);
        $this->assertStringContainsString('Language validation failed', $output);
    }

    public function testRenderWithDryRun(): void
    {
        $renderer = new ValidationResultCliRenderer(
            $this->output,
            $this->input,
            true  // dry run
        );

        $validator = $this->createMockValidator();
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::ERROR,
            [['validator' => $validator, 'fileSet' => $fileSet]]
        );

        $exitCode = $renderer->render($validationResult);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('dry-run mode', $this->output->fetch());
    }

    public function testRenderWithStrictMode(): void
    {
        $renderer = new ValidationResultCliRenderer(
            $this->output,
            $this->input,
            false, // dry run
            true   // strict
        );

        $validator = $this->createMockValidator();
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::WARNING,
            [['validator' => $validator, 'fileSet' => $fileSet]]
        );

        $exitCode = $renderer->render($validationResult);

        $this->assertSame(1, $exitCode);
    }

    public function testRenderWithVerboseOutput(): void
    {
        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        $validator = $this->createMockValidator();
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([]);
        $validator->method('explain')->willReturn('Test validator explanation');

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::ERROR,
            [['validator' => $validator, 'fileSet' => $fileSet]]
        );

        $this->renderer->render($validationResult);

        $output = $this->output->fetch();
        $this->assertStringContainsString('Explanation:', $output);
        $this->assertStringContainsString('Mock validator explanation', $output);
    }

    public function testRenderWithWarningResult(): void
    {
        $validator = $this->createMockValidator();
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::WARNING,
            [['validator' => $validator, 'fileSet' => $fileSet]]
        );

        $exitCode = $this->renderer->render($validationResult);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Language validation failed', $this->output->fetch());
    }

    public function testRenderWithSuccessVerbose(): void
    {
        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        $validationResult = new ValidationResult([], ResultType::SUCCESS);

        $exitCode = $this->renderer->render($validationResult);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Language validation succeeded', $this->output->fetch());
    }

    public function testRenderWithMultipleValidators(): void
    {
        $validator1 = $this->createMockValidator();
        $validator1->method('hasIssues')->willReturn(true);
        $validator1->method('getIssues')->willReturn([]);
        $validator1->method('explain')->willReturn('First validator');

        $fileSet1 = new FileSet('TestParser', '/test/path1', 'setKey1', ['test1.xlf']);
        $fileSet2 = new FileSet('TestParser', '/test/path2', 'setKey2', ['test2.xlf']);
        $validationResult = new ValidationResult(
            [$validator1],
            ResultType::ERROR,
            [
                ['validator' => $validator1, 'fileSet' => $fileSet1],
                ['validator' => $validator1, 'fileSet' => $fileSet2],
            ]
        );

        $this->renderer->render($validationResult);

        $output = $this->output->fetch();
        $this->assertStringContainsString('Validator:', $output);
        // Should contain both paths for the same validator
        $this->assertStringContainsString('/test/path1/', $output);
        $this->assertStringContainsString('/test/path2/', $output);
    }

    private function createMockValidator(): ValidatorInterface|MockObject
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('renderIssueSets');
        $validator->method('explain')->willReturn('Mock validator explanation');

        return $validator;
    }
}
