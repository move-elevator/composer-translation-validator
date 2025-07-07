<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResult;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResultJsonRenderer;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class ValidationResultJsonRendererTest extends TestCase
{
    private ValidationResultJsonRenderer $renderer;
    private BufferedOutput $output;

    protected function setUp(): void
    {
        $this->output = new BufferedOutput();
        $this->renderer = new ValidationResultJsonRenderer(
            $this->output
        );
    }

    public function testRenderWithNoIssues(): void
    {
        $validationResult = new ValidationResult([], ResultType::SUCCESS);

        $exitCode = $this->renderer->render($validationResult);

        $this->assertSame(0, $exitCode);

        $jsonOutput = $this->output->fetch();
        $output = json_decode($jsonOutput, true);

        $this->assertNotNull($output, 'Failed to decode JSON output: '.$jsonOutput);
        $this->assertEquals(0, $output['status']);
        $this->assertEquals('Language validation succeeded.', $output['message']);
        $this->assertEquals([], $output['issues']);
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

        $jsonOutput = $this->output->fetch();
        $output = json_decode($jsonOutput, true);

        $this->assertNotNull($output);
        $this->assertEquals(1, $output['status']);
        $this->assertEquals('Language validation failed.', $output['message']);
        $this->assertNotEmpty($output['issues']);

        // Verify the issues structure matches legacy format
        $validatorClass = $validator::class;
        $this->assertArrayHasKey($validatorClass, $output['issues']);
        $this->assertArrayHasKey('/test/path', $output['issues'][$validatorClass]);
        $this->assertArrayHasKey('setKey', $output['issues'][$validatorClass]['/test/path']);
    }

    public function testRenderWithDryRun(): void
    {
        $renderer = new ValidationResultJsonRenderer(
            $this->output,
            true  // dry run
        );

        $validator = $this->createMockValidator();
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([
            new Issue('test.xlf', ['error'], 'TestParser', 'TestValidator'),
        ]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::ERROR,
            [['validator' => $validator, 'fileSet' => $fileSet]]
        );

        $exitCode = $renderer->render($validationResult);

        $this->assertSame(0, $exitCode);

        $jsonOutput = $this->output->fetch();
        $output = json_decode($jsonOutput, true);

        $this->assertEquals(0, $output['status']);
        $this->assertEquals('Language validation failed and completed in dry-run mode.', $output['message']);
    }

    public function testRenderWithStrictMode(): void
    {
        $renderer = new ValidationResultJsonRenderer(
            $this->output,
            false, // dry run
            true   // strict
        );

        $validator = $this->createMockValidator();
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([
            new Issue('test.xlf', ['warning'], 'TestParser', 'TestValidator'),
        ]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::WARNING,
            [['validator' => $validator, 'fileSet' => $fileSet]]
        );

        $exitCode = $renderer->render($validationResult);

        $this->assertSame(1, $exitCode);

        $jsonOutput = $this->output->fetch();
        $output = json_decode($jsonOutput, true);

        $this->assertEquals(1, $output['status']);
        $this->assertEquals('Language validation failed.', $output['message']);
    }

    public function testRenderWithWarningNotStrict(): void
    {
        $renderer = new ValidationResultJsonRenderer(
            $this->output,
            false, // dry run
            false  // strict
        );

        $validator = $this->createMockValidator();
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([
            new Issue('test.xlf', ['warning'], 'TestParser', 'TestValidator'),
        ]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::WARNING,
            [['validator' => $validator, 'fileSet' => $fileSet]]
        );

        $exitCode = $renderer->render($validationResult);

        $this->assertSame(0, $exitCode);

        $jsonOutput = $this->output->fetch();
        $output = json_decode($jsonOutput, true);

        $this->assertEquals(0, $output['status']);
        $this->assertEquals('Language validation failed.', $output['message']);
    }

    public function testJsonOutputFormat(): void
    {
        $validator = $this->createMockValidator();
        $issue = new Issue('test.xlf', ['error' => 'test error'], 'TestParser', 'TestValidator');
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([$issue]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::ERROR,
            [['validator' => $validator, 'fileSet' => $fileSet]]
        );

        $this->renderer->render($validationResult);

        $jsonOutput = $this->output->fetch();

        // Verify it's valid JSON
        $this->assertJson($jsonOutput);

        // Verify JSON formatting flags are applied
        $this->assertStringContainsString("\n", $jsonOutput); // Pretty print
        $this->assertStringContainsString('    ', $jsonOutput); // Indentation
    }

    private function createMockValidator(): ValidatorInterface|MockObject
    {
        $validator = $this->createMock(ValidatorInterface::class);

        return $validator;
    }
}
