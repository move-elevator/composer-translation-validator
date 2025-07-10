<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResult;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResultJsonRenderer;
use MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics;
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

        // Verify the new file-based structure
        $this->assertArrayHasKey('/test/path/test.xlf', $output['issues']);
        $this->assertArrayHasKey('MockValidator', $output['issues']['/test/path/test.xlf']);
        $this->assertArrayHasKey('type', $output['issues']['/test/path/test.xlf']['MockValidator']);
        $this->assertArrayHasKey('issues', $output['issues']['/test/path/test.xlf']['MockValidator']);
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
        $validator->method('resultTypeOnValidationFailure')->willReturn(ResultType::ERROR);
        $validator->method('formatIssueMessage')->willReturnCallback(fn (Issue $issue, string $prefix = ''): string => "- ERROR {$prefix}Validation error");
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
        $validator->method('getShortName')->willReturn('MockValidator');
        $validator->method('supportsParser')->willReturn(['TestParser']);
        $validator->method('processFile')->willReturn([]);
        $validator->method('validate')->willReturn([]);
        $validator->method('addIssue');

        return $validator;
    }

    public function testRenderWithStatistics(): void
    {
        $statistics = new ValidationStatistics(1.234, 5, 10, 4, 3);
        $validationResult = new ValidationResult([], ResultType::SUCCESS, [], $statistics);

        $exitCode = $this->renderer->render($validationResult);

        $this->assertSame(0, $exitCode);

        $jsonOutput = $this->output->fetch();
        $output = json_decode($jsonOutput, true);

        $this->assertNotNull($output);
        $this->assertArrayHasKey('statistics', $output);

        $stats = $output['statistics'];
        $this->assertEqualsWithDelta(1.234, $stats['execution_time'], PHP_FLOAT_EPSILON);
        $this->assertSame('1.23s', $stats['execution_time_formatted']);
        $this->assertSame(5, $stats['files_checked']);
        $this->assertSame(10, $stats['keys_checked']);
        $this->assertSame(4, $stats['validators_run']);
        $this->assertSame(3, $stats['parsers_cached']);
    }

    public function testRenderWithoutStatistics(): void
    {
        $validationResult = new ValidationResult([], ResultType::SUCCESS);

        $exitCode = $this->renderer->render($validationResult);

        $this->assertSame(0, $exitCode);

        $jsonOutput = $this->output->fetch();
        $output = json_decode($jsonOutput, true);

        $this->assertNotNull($output);
        $this->assertArrayHasKey('statistics', $output);
        $this->assertEmpty($output['statistics']);
    }

    public function testRenderStatisticsDefaultParsersCached(): void
    {
        $statistics = new ValidationStatistics(0.5, 2, 5, 3); // No parsers_cached parameter
        $validationResult = new ValidationResult([], ResultType::SUCCESS, [], $statistics);

        $exitCode = $this->renderer->render($validationResult);

        $this->assertSame(0, $exitCode);

        $jsonOutput = $this->output->fetch();
        $output = json_decode($jsonOutput, true);

        $this->assertNotNull($output);
        $this->assertArrayHasKey('statistics', $output);

        $stats = $output['statistics'];
        $this->assertSame(0, $stats['parsers_cached']); // Default value
    }
}
