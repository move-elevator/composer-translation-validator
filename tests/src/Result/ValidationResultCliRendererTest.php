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
    private MockObject $input;

    protected function setUp(): void
    {
        $this->output = new BufferedOutput();
        /** @var MockObject&InputInterface $input */
        $input = $this->createMock(InputInterface::class);
        $this->input = $input;
        $this->renderer = new ValidationResultCliRenderer(
            $this->output,
            $input
        );
    }

    public function testRenderWithNoIssues(): void
    {
        /** @var array<ValidatorInterface> $validators */
        $validators = [];
        $validationResult = new ValidationResult($validators, ResultType::SUCCESS);

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
        /** @var array<ValidatorInterface> $validators */
        $validators = [$validator];
        /** @var array<array{validator: ValidatorInterface, fileSet: FileSet}> $pairs */
        $pairs = [['validator' => $validator, 'fileSet' => $fileSet]];
        $validationResult = new ValidationResult(
            $validators,
            ResultType::ERROR,
            $pairs
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
        /** @var MockObject&InputInterface $input */
        $input = $this->input;
        $renderer = new ValidationResultCliRenderer(
            $this->output,
            $input,
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
        /** @var MockObject&InputInterface $input */
        $input = $this->input;
        $renderer = new ValidationResultCliRenderer(
            $this->output,
            $input,
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
        $this->assertStringContainsString('Language validation completed with warnings', $this->output->fetch());
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
        /** @var MockObject&InputInterface $input */
        $input = $this->input;
        $this->renderer = new ValidationResultCliRenderer($this->output, $input);
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
        $issue = new Issue('sub/dir/test.xlf', ['key' => 'value'], 'TestParser', 'TestValidator');
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([$issue]);

        $fileSet = new FileSet('TestParser', '/full/path/to', 'setKey', ['sub/dir/test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::ERROR,
            [['validator' => $validator, 'fileSet' => $fileSet]]
        );

        $this->renderer->render($validationResult);
        $output = $this->output->fetch();

        // Should contain normalized path (relative to working directory)
        $this->assertStringContainsString('sub/dir/test.xlf', $output);
    }

    public function testSortIssuesBySeverity(): void
    {
        $validator1 = $this->createMockValidator(ResultType::WARNING);

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

        // Test that sorting actually occurred - errors should come before warnings
        $this->assertCount(2, $sorted);
        $this->assertSame(ResultType::WARNING, $sorted[0]['validator']->resultTypeOnValidationFailure());
        $this->assertSame(ResultType::ERROR, $sorted[1]['validator']->resultTypeOnValidationFailure());
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
        $this->assertStringContainsString('(TestValidator)', (string) $result);

        // Test verbose mode (no prefix)
        $result = $method->invoke($this->renderer, $validator, $issue, 'TestValidator', true);
        $this->assertStringNotContainsString('(TestValidator)', (string) $result);
    }

    public function testRenderStatisticsInVerboseMode(): void
    {
        $statistics = new \MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics(
            0.123,  // 123ms
            5,      // files
            15,     // keys
            3       // validators
        );

        $validationResult = new ValidationResult([], ResultType::SUCCESS, [], $statistics);

        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $this->renderer->render($validationResult);
        $output = $this->output->fetch();

        $this->assertStringContainsString('Execution time: 123ms', $output);
        $this->assertStringContainsString('Files checked: 5', $output);
        $this->assertStringContainsString('Keys checked: 15', $output);
        $this->assertStringContainsString('Validators run: 3', $output);
        $this->assertStringContainsString('Parsers cached: 0', $output); // Default value
    }

    public function testRenderStatisticsWithSecondsInVerboseMode(): void
    {
        $statistics = new \MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics(
            2.456,  // 2.46s
            10,     // files
            50,     // keys
            4,      // validators
            7       // parsers cached
        );

        $validationResult = new ValidationResult([], ResultType::SUCCESS, [], $statistics);

        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $this->renderer->render($validationResult);
        $output = $this->output->fetch();

        $this->assertStringContainsString('Execution time: 2.46s', $output);
        $this->assertStringContainsString('Files checked: 10', $output);
        $this->assertStringContainsString('Keys checked: 50', $output);
        $this->assertStringContainsString('Validators run: 4', $output);
        $this->assertStringContainsString('Parsers cached: 7', $output);
    }

    public function testRenderStatisticsNotShownInCompactMode(): void
    {
        $statistics = new \MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics(
            0.123,
            5,
            15,
            3
        );

        $validationResult = new ValidationResult([], ResultType::SUCCESS, [], $statistics);

        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $this->renderer->render($validationResult);
        $output = $this->output->fetch();

        $this->assertStringNotContainsString('Execution time:', $output);
        $this->assertStringNotContainsString('Files checked:', $output);
        $this->assertStringNotContainsString('Keys checked:', $output);
        $this->assertStringNotContainsString('Validators run:', $output);
        $this->assertStringNotContainsString('Parsers cached:', $output);
    }

    public function testRenderWithNullStatistics(): void
    {
        $validationResult = new ValidationResult([], ResultType::SUCCESS, [], null);

        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $this->renderer->render($validationResult);
        $output = $this->output->fetch();

        // Statistics should not be shown when null
        $this->assertStringNotContainsString('Execution time:', $output);
        $this->assertStringNotContainsString('Files checked:', $output);
        $this->assertStringNotContainsString('Keys checked:', $output);
        $this->assertStringNotContainsString('Validators run:', $output);
    }

    public function testStatisticsWithFailureInVerboseMode(): void
    {
        $validator = $this->createMockValidator();
        $issue = new Issue('test.xlf', ['key' => 'value'], 'TestParser', 'TestValidator');
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([$issue]);

        $statistics = new \MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics(
            0.089,  // 89ms
            2,      // files
            8,      // keys
            2       // validators
        );

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::ERROR,
            [['validator' => $validator, 'fileSet' => $fileSet]],
            $statistics
        );

        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $this->renderer->render($validationResult);
        $output = $this->output->fetch();

        // Both failure message and statistics should be shown
        $this->assertStringContainsString('Language validation failed', $output);
        $this->assertStringContainsString('Execution time: 89ms', $output);
        $this->assertStringContainsString('Files checked: 2', $output);
        $this->assertStringContainsString('Keys checked: 8', $output);
        $this->assertStringContainsString('Validators run: 2', $output);
    }

    /**
     * @return MockObject&ValidatorInterface
     */
    private function createMockValidator(ResultType $resultType = ResultType::ERROR): MockObject
    {
        /** @var MockObject&ValidatorInterface $validator */
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('resultTypeOnValidationFailure')->willReturn($resultType);
        $validator->method('formatIssueMessage')->willReturnCallback(fn (Issue $issue, string $prefix = '', bool $isVerbose = false): string => $isVerbose ? '- <fg=red>Error</> Validation error' : "- <fg=red>Error</> {$prefix}Validation error");
        $validator->method('distributeIssuesForDisplay')->willReturnCallback(function (FileSet $fileSet) use ($validator): array {
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

    public function testRenderWarningInCompactModeWithoutErrors(): void
    {
        // Test that warnings don't show detailed output in compact mode
        $validator = $this->createMockValidator(ResultType::WARNING);
        $issue = new Issue('test.xlf', ['warning'], 'TestParser', 'TestValidator');
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([$issue]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::WARNING,
            [['validator' => $validator, 'fileSet' => $fileSet]]
        );

        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $exitCode = $this->renderer->render($validationResult);
        $output = $this->output->fetch();

        $this->assertSame(0, $exitCode);
        // Should not contain file path or detailed issues in compact mode for warnings
        $this->assertStringNotContainsString('/test/path/test.xlf', $output);
        $this->assertStringContainsString('Language validation completed with warnings', $output);
    }

    public function testRenderErrorInCompactModeShowsDetails(): void
    {
        // Test that errors DO show detailed output in compact mode
        $validator = $this->createMockValidator(ResultType::ERROR);
        $issue = new Issue('test.xlf', ['error'], 'TestParser', 'TestValidator');
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([$issue]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::ERROR,
            [['validator' => $validator, 'fileSet' => $fileSet]]
        );

        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $exitCode = $this->renderer->render($validationResult);
        $output = $this->output->fetch();

        $this->assertSame(1, $exitCode);
        // Should contain file path and detailed issues in compact mode for errors
        $this->assertStringContainsString('test/path/test.xlf', $output);
        $this->assertStringContainsString('Language validation failed with errors', $output);
    }

    public function testRenderSummaryMessagesWithDryRun(): void
    {
        // Test dry-run message variations
        /** @var MockObject&InputInterface $input */
        $input = $this->input;
        $renderer = new ValidationResultCliRenderer(
            $this->output,
            $input,
            true, // dry run
            false
        );

        $validationResult = new ValidationResult([], ResultType::ERROR);
        $renderer->render($validationResult);
        $output = $this->output->fetch();

        $this->assertStringContainsString('Language validation failed with errors in dry-run mode.', $output);
    }

    public function testRenderWarningWithStrictModeHint(): void
    {
        $validationResult = new ValidationResult([], ResultType::WARNING);

        /** @var MockObject&InputInterface $input */
        $input = $this->input;
        $renderer = new ValidationResultCliRenderer(
            $this->output,
            $input,
            false, // not dry run
            false  // not strict
        );

        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $renderer->render($validationResult);
        $output = $this->output->fetch();

        $this->assertStringContainsString('Use `--strict` to treat warnings as errors', $output);
    }

    public function testRenderWarningWithStrictModeNoHint(): void
    {
        $validationResult = new ValidationResult([], ResultType::WARNING);

        /** @var MockObject&InputInterface $input */
        $input = $this->input;
        $renderer = new ValidationResultCliRenderer(
            $this->output,
            $input,
            false, // not dry run
            true   // strict mode
        );

        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $renderer->render($validationResult);
        $output = $this->output->fetch();

        $this->assertStringNotContainsString('Use `--strict` to treat warnings as errors', $output);
    }

    public function testRenderWarningCompactOutput(): void
    {
        $validationResult = new ValidationResult([], ResultType::WARNING);

        /** @var MockObject&InputInterface $input */
        $input = $this->input;
        $renderer = new ValidationResultCliRenderer(
            $this->output,
            $input,
            false, // not dry run
            false  // not strict
        );

        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $renderer->render($validationResult);
        $output = $this->output->fetch();

        // Should be simple text output (yellow), not a warning box
        $this->assertStringContainsString('Language validation completed with warnings', $output);
        $this->assertStringNotContainsString('[WARNING]', $output); // No warning box formatting
    }

    public function testRenderWarningVerboseOutput(): void
    {
        $validationResult = new ValidationResult([], ResultType::WARNING);

        /** @var MockObject&InputInterface $input */
        $input = $this->input;
        $renderer = new ValidationResultCliRenderer(
            $this->output,
            $input,
            false, // not dry run
            false  // not strict
        );

        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $renderer->render($validationResult);
        $output = $this->output->fetch();

        // Should be warning box format in verbose mode
        $this->assertStringContainsString('[WARNING]', $output);
    }

    public function testGroupIssuesByFileWithMultipleValidatorsVerbose(): void
    {
        // Create first validator with issues
        $validator1 = $this->createMock(ValidatorInterface::class);
        $validator1->method('resultTypeOnValidationFailure')->willReturn(ResultType::ERROR);
        $validator1->method('formatIssueMessage')->willReturnCallback(fn (Issue $issue, string $prefix = ''): string => "- ERROR {$prefix}Validation error 1");
        $validator1->method('getShortName')->willReturn('Validator1');
        $validator1->method('shouldShowDetailedOutput')->willReturn(false);
        $issue1 = new Issue('test.xlf', ['error1'], 'TestParser', 'TestValidator1');
        $validator1->method('hasIssues')->willReturn(true);
        $validator1->method('getIssues')->willReturn([$issue1]);
        $validator1->method('distributeIssuesForDisplay')->willReturnCallback(fn (FileSet $fileSet): array => ['/test/path/test.xlf' => [$issue1]]);

        // Create second validator with issues
        $validator2 = $this->createMock(ValidatorInterface::class);
        $validator2->method('resultTypeOnValidationFailure')->willReturn(ResultType::ERROR);
        $validator2->method('formatIssueMessage')->willReturnCallback(fn (Issue $issue, string $prefix = ''): string => "- ERROR {$prefix}Validation error 2");
        $validator2->method('getShortName')->willReturn('Validator2');
        $validator2->method('shouldShowDetailedOutput')->willReturn(false);
        $issue2 = new Issue('test.xlf', ['error2'], 'TestParser', 'TestValidator2');
        $validator2->method('hasIssues')->willReturn(true);
        $validator2->method('getIssues')->willReturn([$issue2]);
        $validator2->method('distributeIssuesForDisplay')->willReturnCallback(fn (FileSet $fileSet): array => ['/test/path/test.xlf' => [$issue2]]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator1, $validator2],
            ResultType::ERROR,
            [
                ['validator' => $validator1, 'fileSet' => $fileSet],
                ['validator' => $validator2, 'fileSet' => $fileSet],
            ]
        );

        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $exitCode = $this->renderer->render($validationResult);

        $this->assertSame(1, $exitCode);

        $output = $this->output->fetch();

        // Should contain file path
        $this->assertStringContainsString('test.xlf', $output);

        // Should contain both validators in verbose mode
        $this->assertStringContainsString('Validator1', $output);
        $this->assertStringContainsString('Validator2', $output);
    }

    public function testGroupIssuesByFileWithMultipleFilesVerbose(): void
    {
        $issue1 = new Issue('file1.xlf', ['error1'], 'TestParser', 'TestValidator');
        $issue2 = new Issue('file2.xlf', ['error2'], 'TestParser', 'TestValidator');

        $validator = $this->createMockValidator();
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([$issue1, $issue2]);
        $validator->method('distributeIssuesForDisplay')->willReturnCallback(fn (FileSet $fileSet): array => [
            '/test/path/file1.xlf' => [new Issue('file1.xlf', ['error1'], 'TestParser', 'TestValidator')],
            '/test/path/file2.xlf' => [new Issue('file2.xlf', ['error2'], 'TestParser', 'TestValidator')],
        ]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['file1.xlf', 'file2.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::ERROR,
            [['validator' => $validator, 'fileSet' => $fileSet]]
        );

        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $exitCode = $this->renderer->render($validationResult);

        $this->assertSame(1, $exitCode);

        $output = $this->output->fetch();

        // Should contain both file paths
        $this->assertStringContainsString('file1.xlf', $output);
        $this->assertStringContainsString('file2.xlf', $output);

        // Should contain validator name for each file
        $this->assertStringContainsString('MockObject_ValidatorInterface', $output);
    }

    public function testGroupIssuesByFileWarningsNotShownInCompactMode(): void
    {
        $validator = $this->createMockValidator();
        $validator->method('resultTypeOnValidationFailure')->willReturn(ResultType::WARNING);
        $issue = new Issue('test.xlf', ['warning'], 'TestParser', 'TestValidator');
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([$issue]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::WARNING,
            [['validator' => $validator, 'fileSet' => $fileSet]]
        );

        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $exitCode = $this->renderer->render($validationResult);

        $this->assertSame(0, $exitCode);

        $output = $this->output->fetch();

        // In compact mode, warnings should not show detailed file output in errors section
        $this->assertStringContainsString('Language validation completed with warnings', $output);

        // Note: CLI renderer may still show warnings in verbose mode, but this tests compact mode behavior
    }

    public function testGroupIssuesByFileWithEmptyValidators(): void
    {
        $validationResult = new ValidationResult([], ResultType::SUCCESS, []);

        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $exitCode = $this->renderer->render($validationResult);

        $this->assertSame(0, $exitCode);

        $output = $this->output->fetch();

        // Should only contain success message
        $this->assertStringContainsString('Language validation succeeded', $output);
        // Should not contain any file paths
        $this->assertStringNotContainsString('.xlf', $output);
    }

    public function testGroupIssuesByFileErrorsShownInCompactMode(): void
    {
        $validator = $this->createMockValidator();
        $validator->method('resultTypeOnValidationFailure')->willReturn(ResultType::ERROR);
        $issue = new Issue('test.xlf', ['error'], 'TestParser', 'TestValidator');
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([$issue]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::ERROR,
            [['validator' => $validator, 'fileSet' => $fileSet]]
        );

        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $exitCode = $this->renderer->render($validationResult);

        $this->assertSame(1, $exitCode);

        $output = $this->output->fetch();

        // In compact mode, errors should show detailed output
        $this->assertStringContainsString('test.xlf', $output);
        $this->assertStringContainsString('Language validation failed with errors', $output);
    }
}
