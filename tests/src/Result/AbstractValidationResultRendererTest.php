<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\Result\AbstractValidationResultRenderer;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResult;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

final class AbstractValidationResultRendererTest extends TestCase
{
    private TestableAbstractValidationResultRenderer $renderer;
    private BufferedOutput $output;

    protected function setUp(): void
    {
        $this->output = new BufferedOutput();
        $this->renderer = new TestableAbstractValidationResultRenderer($this->output);
    }

    public function testGenerateMessageForSuccess(): void
    {
        $result = new ValidationResult([], ResultType::SUCCESS);
        $message = $this->renderer->testGenerateMessage($result);

        $this->assertSame('Language validation succeeded.', $message);
    }

    public function testGenerateMessageForError(): void
    {
        $result = new ValidationResult([], ResultType::ERROR);
        $message = $this->renderer->testGenerateMessage($result);

        $this->assertSame('Language validation failed with errors.', $message);
    }

    public function testGenerateMessageForWarning(): void
    {
        $result = new ValidationResult([], ResultType::WARNING);
        $message = $this->renderer->testGenerateMessage($result);

        $this->assertSame('Language validation completed with warnings.', $message);
    }

    public function testGenerateMessageForErrorInDryRun(): void
    {
        $renderer = new TestableAbstractValidationResultRenderer($this->output, true);
        $result = new ValidationResult([], ResultType::ERROR);
        $message = $renderer->testGenerateMessage($result);

        $this->assertSame('Language validation failed with errors in dry-run mode.', $message);
    }

    public function testGenerateMessageForWarningInDryRun(): void
    {
        $renderer = new TestableAbstractValidationResultRenderer($this->output, true);
        $result = new ValidationResult([], ResultType::WARNING);
        $message = $renderer->testGenerateMessage($result);

        $this->assertSame('Language validation completed with warnings in dry-run mode.', $message);
    }

    public function testGenerateMessageForWarningInStrictMode(): void
    {
        $renderer = new TestableAbstractValidationResultRenderer($this->output, false, true);
        $result = new ValidationResult([], ResultType::WARNING);
        $message = $renderer->testGenerateMessage($result);

        $this->assertSame('Language validation failed with warnings in strict mode.', $message);
    }

    public function testCalculateExitCodeForSuccess(): void
    {
        $result = new ValidationResult([], ResultType::SUCCESS);
        $exitCode = $this->renderer->testCalculateExitCode($result);

        $this->assertSame(0, $exitCode);
    }

    public function testCalculateExitCodeForError(): void
    {
        $result = new ValidationResult([], ResultType::ERROR);
        $exitCode = $this->renderer->testCalculateExitCode($result);

        $this->assertSame(1, $exitCode);
    }

    public function testCalculateExitCodeForWarning(): void
    {
        $result = new ValidationResult([], ResultType::WARNING);
        $exitCode = $this->renderer->testCalculateExitCode($result);

        $this->assertSame(0, $exitCode);
    }

    public function testCalculateExitCodeForWarningInStrictMode(): void
    {
        $renderer = new TestableAbstractValidationResultRenderer($this->output, false, true);
        $result = new ValidationResult([], ResultType::WARNING);
        $exitCode = $renderer->testCalculateExitCode($result);

        $this->assertSame(1, $exitCode);
    }

    public function testCalculateExitCodeForErrorInDryRun(): void
    {
        $renderer = new TestableAbstractValidationResultRenderer($this->output, true);
        $result = new ValidationResult([], ResultType::ERROR);
        $exitCode = $renderer->testCalculateExitCode($result);

        $this->assertSame(0, $exitCode);
    }

    public function testNormalizePathWithRealPath(): void
    {
        $currentDir = getcwd();
        $testFile = $currentDir.'/composer.json';

        if (file_exists($testFile)) {
            $normalized = $this->renderer->testNormalizePath($testFile);
            $this->assertSame('composer.json', $normalized);
        } else {
            $this->markTestSkipped('composer.json not found in current directory');
        }
    }

    public function testNormalizePathWithNonExistentPath(): void
    {
        $normalized = $this->renderer->testNormalizePath('./some/non/existent/path');
        $this->assertSame('some/non/existent/path', $normalized);
    }

    public function testFormatStatisticsForOutput(): void
    {
        $statistics = new \MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics(1.234, 5, 10, 4, 3);
        $result = new ValidationResult([], ResultType::SUCCESS, [], $statistics);

        $formatted = $this->renderer->testFormatStatisticsForOutput($result);

        $this->assertEqualsWithDelta(1.234, $formatted['execution_time'], PHP_FLOAT_EPSILON);
        $this->assertSame('1.23s', $formatted['execution_time_formatted']);
        $this->assertSame(5, $formatted['files_checked']);
        $this->assertSame(10, $formatted['keys_checked']);
        $this->assertSame(4, $formatted['validators_run']);
        $this->assertSame(3, $formatted['parsers_cached']);
    }

    public function testFormatStatisticsForOutputWithoutStatistics(): void
    {
        $result = new ValidationResult([], ResultType::SUCCESS);
        $formatted = $this->renderer->testFormatStatisticsForOutput($result);

        $this->assertSame([], $formatted);
    }
}

class TestableAbstractValidationResultRenderer extends AbstractValidationResultRenderer
{
    public function render(ValidationResult $validationResult): int
    {
        return 0;
    }

    public function testGenerateMessage(ValidationResult $result): string
    {
        return $this->generateMessage($result);
    }

    public function testCalculateExitCode(ValidationResult $result): int
    {
        return $this->calculateExitCode($result);
    }

    public function testNormalizePath(string $path): string
    {
        return $this->normalizePath($path);
    }

    /**
     * @return array<string, mixed>
     */
    public function testFormatStatisticsForOutput(ValidationResult $result): array
    {
        return $this->formatStatisticsForOutput($result);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function testGroupIssuesByFile(ValidationResult $result): array
    {
        return $this->groupIssuesByFile($result);
    }
}
