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
        $this->assertStringContainsString('test.xlf', $output);
        $this->assertStringContainsString('ERROR', $output);
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
        $issue = new Issue('test.xlf', ['key' => 'value'], 'TestParser', 'TestValidator');
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([$issue]);
        $validator->method('explain')->willReturn('Test validator explanation');

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::ERROR,
            [['validator' => $validator, 'fileSet' => $fileSet]]
        );

        $this->renderer->render($validationResult);

        $output = $this->output->fetch();
        // In verbose mode, validator names should be grouped
        $this->assertStringContainsString('MockObject_ValidatorInterface', $output);
        $this->assertStringContainsString('test.xlf', $output);
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
        $issue1 = new Issue('test1.xlf', ['key' => 'value1'], 'TestParser', 'TestValidator');
        $issue2 = new Issue('test2.xlf', ['key' => 'value2'], 'TestParser', 'TestValidator');
        $validator1->method('hasIssues')->willReturn(true);
        $validator1->method('getIssues')->willReturn([$issue1, $issue2]);
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
        // In new format, should not contain "Validator:" but should contain file names
        $this->assertStringNotContainsString('Validator:', $output);
        // Should contain both paths in the new compact format
        $this->assertStringContainsString('test1.xlf', $output);
        $this->assertStringContainsString('test2.xlf', $output);
    }

    public function testRenderCompactVsVerboseMode(): void
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

        // Test compact mode (non-verbose)
        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $this->renderer->render($validationResult);
        $compactOutput = $this->output->fetch();

        // Test verbose mode
        $this->output = new BufferedOutput();
        $this->renderer = new ValidationResultCliRenderer($this->output, $this->input);
        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $this->renderer->render($validationResult);
        $verboseOutput = $this->output->fetch();

        // Compact mode should include validator name in parentheses
        $this->assertStringContainsString('(MockObject_ValidatorInterface', $compactOutput);

        // Verbose mode should group by validator name without parentheses in message
        $this->assertStringContainsString('MockObject_ValidatorInterface', $verboseOutput);
        $this->assertStringNotContainsString('(MockObject_ValidatorInterface', $verboseOutput);
    }

    public function testRenderWithVerboseHintInCompactMode(): void
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

        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $this->renderer->render($validationResult);
        $output = $this->output->fetch();

        // Should contain hint about verbose mode in compact mode
        $this->assertStringContainsString('See more details with the `-v` verbose option', $output);
    }

    public function testPathNormalization(): void
    {
        // Test path normalization behavior through actual rendering
        $validator = $this->createMockValidator();
        $issue = new Issue('test.xlf', ['key' => 'value'], 'TestParser', 'TestValidator');
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([$issue]);

        $fileSet = new FileSet('TestParser', '/full/path/to', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::ERROR,
            [['validator' => $validator, 'fileSet' => $fileSet]]
        );

        $this->renderer->render($validationResult);
        $output = $this->output->fetch();

        // Should contain normalized path
        $this->assertStringContainsString('test.xlf', $output);
    }

    public function testSortIssuesBySeverity(): void
    {
        $validator1 = $this->createMockValidator();
        $validator1->method('resultTypeOnValidationFailure')->willReturn(ResultType::WARNING);

        $validator2 = $this->createMockValidator();
        $validator2->method('resultTypeOnValidationFailure')->willReturn(ResultType::ERROR);

        $issue1 = new Issue('test1.xlf', ['warning'], 'TestParser', 'TestValidator');
        $issue2 = new Issue('test2.xlf', ['error'], 'TestParser', 'TestValidator');

        $fileIssues = [
            ['validator' => $validator1, 'issue' => $issue1],
            ['validator' => $validator2, 'issue' => $issue2],
        ];

        $reflection = new \ReflectionClass($this->renderer);
        $method = $reflection->getMethod('sortIssuesBySeverity');
        $method->setAccessible(true);

        $sorted = $method->invoke($this->renderer, $fileIssues);

        // Test that sorting actually occurred - array should have 2 elements
        $this->assertCount(2, $sorted);
        $this->assertArrayHasKey('validator', $sorted[0]);
        $this->assertArrayHasKey('issue', $sorted[0]);
    }

    public function testSortValidatorGroupsBySeverity(): void
    {
        $validatorGroups = [
            'TestValidator1' => ['validator' => $this->createMockValidator(), 'issues' => []],
            'TestValidator2' => ['validator' => $this->createMockValidator(), 'issues' => []],
            'TestValidator3' => ['validator' => $this->createMockValidator(), 'issues' => []],
        ];

        $reflection = new \ReflectionClass($this->renderer);
        $method = $reflection->getMethod('sortValidatorGroupsBySeverity');
        $method->setAccessible(true);

        $sorted = $method->invoke($this->renderer, $validatorGroups);

        // Test that we got the same number of groups back
        $this->assertCount(3, $sorted);
        $this->assertArrayHasKey('TestValidator1', $sorted);
        $this->assertArrayHasKey('TestValidator2', $sorted);
        $this->assertArrayHasKey('TestValidator3', $sorted);
    }

    public function testGetValidatorSeverity(): void
    {
        $reflection = new \ReflectionClass($this->renderer);
        $method = $reflection->getMethod('getValidatorSeverity');
        $method->setAccessible(true);

        // SchemaValidator should have priority 1
        $result = $method->invoke($this->renderer, 'SchemaValidator');
        $this->assertSame(1, $result);

        // Other error validators should have priority 1
        $result = $method->invoke($this->renderer, 'SomeErrorValidator');
        $this->assertSame(1, $result);
    }

    public function testFormatIssueMessage(): void
    {
        $validator = $this->createMockValidator();
        $issue = new Issue('test.xlf', ['message' => 'Test error'], 'TestParser', 'TestValidator');

        $reflection = new \ReflectionClass($this->renderer);
        $method = $reflection->getMethod('formatIssueMessage');
        $method->setAccessible(true);

        // Test with validator name prefix (non-verbose)
        $result = $method->invoke($this->renderer, $validator, $issue, 'TestValidator', false);
        $this->assertStringContainsString('(TestValidator)', $result);

        // Test verbose mode (no prefix)
        $result = $method->invoke($this->renderer, $validator, $issue, 'TestValidator', true);
        $this->assertStringNotContainsString('(TestValidator)', $result);
    }

    private function createMockValidator(): ValidatorInterface|MockObject
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('explain')->willReturn('Mock validator explanation');
        $validator->method('resultTypeOnValidationFailure')->willReturn(ResultType::ERROR);
        $validator->method('formatIssueMessage')->willReturnCallback(function ($issue, $prefix = '', $isVerbose = false) {
            return "- <fg=red>ERROR</> {$prefix}Validation error";
        });
        $validator->method('distributeIssuesForDisplay')->willReturnCallback(function ($fileSet) use ($validator) {
            $distribution = [];
            foreach ($validator->getIssues() as $issue) {
                $fileName = $issue->getFile();
                if (!empty($fileName)) {
                    $basePath = rtrim($fileSet->getPath(), '/');
                    $filePath = $basePath.'/'.$fileName;
                    $distribution[$filePath][] = $issue;
                }
            }

            return $distribution;
        });
        $validator->method('shouldShowDetailedOutput')->willReturn(false);
        $validator->method('renderDetailedOutput');
        $validator->method('getShortName')->willReturn('MockObject_ValidatorInterface');

        return $validator;
    }
}
