<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025-2026 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Result\{AbstractValidationResultRenderer, Issue, ValidationResult, ValidationStatistics};
use MoveElevator\ComposerTranslationValidator\Validator\{ResultType, ValidatorInterface};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * AbstractValidationResultRendererTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class AbstractValidationResultRendererTest extends TestCase
{
    private TestableAbstractValidationResultRenderer $renderer;
    private BufferedOutput $output;

    protected function setUp(): void
    {
        $this->output = new BufferedOutput();
        $this->renderer = new TestableAbstractValidationResultRenderer($this->output);
    }

    /**
     * @return iterable<string, array{bool, bool, ResultType, string}>
     */
    public static function generateMessageProvider(): iterable
    {
        yield 'success' => [false, false, ResultType::SUCCESS, 'Language validation succeeded.'];
        yield 'error' => [false, false, ResultType::ERROR, 'Language validation failed with errors.'];
        yield 'warning' => [false, false, ResultType::WARNING, 'Language validation completed with warnings.'];
        yield 'error dry-run' => [true, false, ResultType::ERROR, 'Language validation failed with errors in dry-run mode.'];
        yield 'warning dry-run' => [true, false, ResultType::WARNING, 'Language validation completed with warnings in dry-run mode.'];
        yield 'warning strict' => [false, true, ResultType::WARNING, 'Language validation failed with warnings in strict mode.'];
    }

    #[DataProvider('generateMessageProvider')]
    public function testGenerateMessage(bool $dryRun, bool $strict, ResultType $resultType, string $expected): void
    {
        $renderer = new TestableAbstractValidationResultRenderer($this->output, $dryRun, $strict);
        $message = $renderer->testGenerateMessage(new ValidationResult([], $resultType));

        $this->assertSame($expected, $message);
    }

    /**
     * @return iterable<string, array{bool, bool, ResultType, int}>
     */
    public static function calculateExitCodeProvider(): iterable
    {
        yield 'success' => [false, false, ResultType::SUCCESS, 0];
        yield 'error' => [false, false, ResultType::ERROR, 1];
        yield 'warning' => [false, false, ResultType::WARNING, 0];
        yield 'warning strict' => [false, true, ResultType::WARNING, 1];
        yield 'error dry-run' => [true, false, ResultType::ERROR, 0];
    }

    #[DataProvider('calculateExitCodeProvider')]
    public function testCalculateExitCode(bool $dryRun, bool $strict, ResultType $resultType, int $expected): void
    {
        $renderer = new TestableAbstractValidationResultRenderer($this->output, $dryRun, $strict);
        $exitCode = $renderer->testCalculateExitCode(new ValidationResult([], $resultType));

        $this->assertSame($expected, $exitCode);
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

    public function testNormalizePathWithExistingPathOutsideCwd(): void
    {
        $outsidePath = realpath(sys_get_temp_dir());
        $this->assertNotFalse($outsidePath, 'System temp directory must resolve');

        $normalized = $this->renderer->testNormalizePath($outsidePath);

        // Path is real but not below cwd, so it is returned unshortened.
        $this->assertSame(rtrim($outsidePath, \DIRECTORY_SEPARATOR), $normalized);
    }

    public function testGroupIssuesByFileSkipsValidatorsWithoutIssues(): void
    {
        $withoutIssues = $this->createStub(ValidatorInterface::class);
        $withoutIssues->method('hasIssues')->willReturn(false);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $result = new ValidationResult(
            [$withoutIssues],
            ResultType::SUCCESS,
            [['validator' => $withoutIssues, 'fileSet' => $fileSet]],
        );

        $this->assertSame([], $this->renderer->testGroupIssuesByFile($result));
    }

    public function testGroupIssuesByFileGroupsDistributedIssues(): void
    {
        $issue = new Issue('test.xlf', ['key' => 'value'], 'TestParser', 'TestValidator');
        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('resultTypeOnValidationFailure')->willReturn(ResultType::ERROR);
        $validator->method('getShortName')->willReturn('TestValidator');
        $validator->method('distributeIssuesForDisplay')->willReturn([
            '/test/path/test.xlf' => [$issue],
        ]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $result = new ValidationResult(
            [$validator],
            ResultType::ERROR,
            [['validator' => $validator, 'fileSet' => $fileSet]],
        );

        $grouped = $this->renderer->testGroupIssuesByFile($result);

        $this->assertArrayHasKey('/test/path/test.xlf', $grouped);
        $this->assertArrayHasKey('TestValidator', $grouped['/test/path/test.xlf']);
    }

    public function testFormatStatisticsForOutput(): void
    {
        $statistics = new ValidationStatistics(1.234, 5, 10, 4, 3);
        $result = new ValidationResult([], ResultType::SUCCESS, [], $statistics);

        $formatted = $this->renderer->testFormatStatisticsForOutput($result);

        $this->assertEqualsWithDelta(1.234, $formatted['execution_time'], \PHP_FLOAT_EPSILON);
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

/**
 * TestableAbstractValidationResultRenderer.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
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
