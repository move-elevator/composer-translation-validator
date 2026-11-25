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
use MoveElevator\ComposerTranslationValidator\Result\{Issue, ValidationResult, ValidationResultJsonRenderer, ValidationStatistics};
use MoveElevator\ComposerTranslationValidator\Validator\{DuplicateKeysValidator, ResultType, ValidatorInterface};
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * ValidationResultJsonRendererTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class ValidationResultJsonRendererTest extends TestCase
{
    private ValidationResultJsonRenderer $renderer;
    private BufferedOutput $output;

    protected function setUp(): void
    {
        $this->output = new BufferedOutput();
        $this->renderer = new ValidationResultJsonRenderer(
            $this->output,
        );
    }

    public function testRenderWithNoIssues(): void
    {
        /** @var array<ValidatorInterface> $validators */
        $validators = [];
        $validationResult = new ValidationResult($validators, ResultType::SUCCESS);

        $exitCode = $this->renderer->render($validationResult);

        self::assertSame(0, $exitCode);

        $jsonOutput = $this->output->fetch();
        $output = json_decode($jsonOutput, true);

        self::assertNotNull($output, 'Failed to decode JSON output: '.$jsonOutput);
        self::assertEquals(0, $output['status']);
        self::assertEquals('Language validation succeeded.', $output['message']);
        self::assertEquals([], $output['issues']);
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
            $pairs,
        );

        $exitCode = $this->renderer->render($validationResult);

        self::assertSame(1, $exitCode);

        $jsonOutput = $this->output->fetch();
        $output = json_decode($jsonOutput, true);

        self::assertNotNull($output);
        self::assertEquals(1, $output['status']);
        self::assertEquals('Language validation failed with errors.', $output['message']);
        self::assertNotEmpty($output['issues']);

        // Verify the new file-based structure
        self::assertArrayHasKey('/test/path/test.xlf', $output['issues']);
        self::assertArrayHasKey('MockValidator', $output['issues']['/test/path/test.xlf']);
        self::assertArrayHasKey('type', $output['issues']['/test/path/test.xlf']['MockValidator']);
        self::assertArrayHasKey('issues', $output['issues']['/test/path/test.xlf']['MockValidator']);
    }

    public function testRenderWithDryRun(): void
    {
        $renderer = new ValidationResultJsonRenderer(
            $this->output,
            true,  // dry run
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
            [['validator' => $validator, 'fileSet' => $fileSet]],
        );

        $exitCode = $renderer->render($validationResult);

        self::assertSame(0, $exitCode);

        $jsonOutput = $this->output->fetch();
        $output = json_decode($jsonOutput, true);

        self::assertEquals(0, $output['status']);
        self::assertEquals('Language validation failed with errors in dry-run mode.', $output['message']);
    }

    public function testRenderWithStrictMode(): void
    {
        $renderer = new ValidationResultJsonRenderer(
            $this->output,
            false, // dry run
            true,   // strict
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
            [['validator' => $validator, 'fileSet' => $fileSet]],
        );

        $exitCode = $renderer->render($validationResult);

        self::assertSame(1, $exitCode);

        $jsonOutput = $this->output->fetch();
        $output = json_decode($jsonOutput, true);

        self::assertEquals(1, $output['status']);
        self::assertEquals('Language validation failed with warnings in strict mode.', $output['message']);
    }

    public function testRenderWithWarningNotStrict(): void
    {
        $renderer = new ValidationResultJsonRenderer(
            $this->output,
            false, // dry run
            false,  // strict
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
            [['validator' => $validator, 'fileSet' => $fileSet]],
        );

        $exitCode = $renderer->render($validationResult);

        self::assertSame(0, $exitCode);

        $jsonOutput = $this->output->fetch();
        $output = json_decode($jsonOutput, true);

        self::assertEquals(0, $output['status']);
        self::assertEquals('Language validation completed with warnings.', $output['message']);
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
            [['validator' => $validator, 'fileSet' => $fileSet]],
        );

        $this->renderer->render($validationResult);

        $jsonOutput = $this->output->fetch();

        // Verify it's valid JSON
        self::assertJson($jsonOutput);

        // Verify JSON formatting flags are applied
        self::assertStringContainsString("\n", $jsonOutput); // Pretty print
        self::assertStringContainsString('    ', $jsonOutput); // Indentation
    }

    public function testRenderWithStatistics(): void
    {
        $statistics = new ValidationStatistics(1.234, 5, 10, 4, 3);
        $validationResult = new ValidationResult([], ResultType::SUCCESS, [], $statistics);

        $exitCode = $this->renderer->render($validationResult);

        self::assertSame(0, $exitCode);

        $jsonOutput = $this->output->fetch();
        $output = json_decode($jsonOutput, true);

        self::assertNotNull($output);
        self::assertArrayHasKey('statistics', $output);

        $stats = $output['statistics'];
        self::assertEqualsWithDelta(1.234, $stats['execution_time'], \PHP_FLOAT_EPSILON);
        self::assertSame('1.23s', $stats['execution_time_formatted']);
        self::assertSame(5, $stats['files_checked']);
        self::assertSame(10, $stats['keys_checked']);
        self::assertSame(4, $stats['validators_run']);
        self::assertSame(3, $stats['parsers_cached']);
    }

    public function testRenderWithoutStatistics(): void
    {
        $validationResult = new ValidationResult([], ResultType::SUCCESS);

        $exitCode = $this->renderer->render($validationResult);

        self::assertSame(0, $exitCode);

        $jsonOutput = $this->output->fetch();
        $output = json_decode($jsonOutput, true);

        self::assertNotNull($output);
        self::assertArrayHasKey('statistics', $output);
        self::assertEmpty($output['statistics']);
    }

    public function testRenderStatisticsDefaultParsersCached(): void
    {
        $statistics = new ValidationStatistics(0.5, 2, 5, 3); // No parsers_cached parameter
        $validationResult = new ValidationResult([], ResultType::SUCCESS, [], $statistics);

        $exitCode = $this->renderer->render($validationResult);

        self::assertSame(0, $exitCode);

        $jsonOutput = $this->output->fetch();
        $output = json_decode($jsonOutput, true);

        self::assertNotNull($output);
        self::assertArrayHasKey('statistics', $output);

        $stats = $output['statistics'];
        self::assertSame(0, $stats['parsers_cached']); // Default value
    }

    public function testGroupIssuesByFileWithMultipleValidators(): void
    {
        // Create first validator with issues
        $validator1 = self::createStub(ValidatorInterface::class);
        $validator1->method('resultTypeOnValidationFailure')->willReturn(ResultType::ERROR);
        $validator1->method('formatIssueMessage')->willReturnCallback(static fn (Issue $issue, string $prefix = ''): string => "- ERROR {$prefix}Validation error 1");
        $validator1->method('getShortName')->willReturn('Validator1');
        $issue1 = new Issue('test.xlf', ['error1'], 'TestParser', 'TestValidator1');
        $validator1->method('hasIssues')->willReturn(true);
        $validator1->method('getIssues')->willReturn([$issue1]);
        $validator1->method('distributeIssuesForDisplay')->willReturnCallback(static fn (FileSet $fileSet): array => ['/test/path/test.xlf' => [$issue1]]);

        // Create second validator with issues
        $validator2 = self::createStub(ValidatorInterface::class);
        $validator2->method('resultTypeOnValidationFailure')->willReturn(ResultType::ERROR);
        $validator2->method('formatIssueMessage')->willReturnCallback(static fn (Issue $issue, string $prefix = ''): string => "- ERROR {$prefix}Validation error 2");
        $validator2->method('getShortName')->willReturn('Validator2');
        $issue2 = new Issue('test.xlf', ['error2'], 'TestParser', 'TestValidator2');
        $validator2->method('hasIssues')->willReturn(true);
        $validator2->method('getIssues')->willReturn([$issue2]);
        $validator2->method('distributeIssuesForDisplay')->willReturnCallback(static fn (FileSet $fileSet): array => ['/test/path/test.xlf' => [$issue2]]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator1, $validator2],
            ResultType::ERROR,
            [
                ['validator' => $validator1, 'fileSet' => $fileSet],
                ['validator' => $validator2, 'fileSet' => $fileSet],
            ],
        );

        $exitCode = $this->renderer->render($validationResult);

        self::assertSame(1, $exitCode);

        $jsonOutput = $this->output->fetch();
        $output = json_decode($jsonOutput, true);

        self::assertNotNull($output);
        self::assertArrayHasKey('issues', $output);

        // Should have issues grouped by file path
        $issues = $output['issues'];
        self::assertArrayHasKey('/test/path/test.xlf', $issues);

        $fileIssues = $issues['/test/path/test.xlf'];
        self::assertArrayHasKey('Validator1', $fileIssues);
        self::assertArrayHasKey('Validator2', $fileIssues);

        // Check validator 1 issues
        $validator1Issues = $fileIssues['Validator1'];
        self::assertArrayHasKey('type', $validator1Issues);
        self::assertArrayHasKey('issues', $validator1Issues);
        self::assertCount(1, $validator1Issues['issues']);

        // Check validator 2 issues
        $validator2Issues = $fileIssues['Validator2'];
        self::assertArrayHasKey('type', $validator2Issues);
        self::assertArrayHasKey('issues', $validator2Issues);
        self::assertCount(1, $validator2Issues['issues']);
    }

    public function testGroupIssuesByFileWithMultipleFiles(): void
    {
        $validator = $this->createMockValidator();
        $issue1 = new Issue('file1.xlf', ['error1'], 'TestParser', 'TestValidator');
        $issue2 = new Issue('file2.xlf', ['error2'], 'TestParser', 'TestValidator');

        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([$issue1, $issue2]);
        $validator->method('distributeIssuesForDisplay')->willReturnCallback(static fn (FileSet $fileSet): array => [
            '/test/path/file1.xlf' => [new Issue('file1.xlf', ['error1'], 'TestParser', 'TestValidator')],
            '/test/path/file2.xlf' => [new Issue('file2.xlf', ['error2'], 'TestParser', 'TestValidator')],
        ]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['file1.xlf', 'file2.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::ERROR,
            [['validator' => $validator, 'fileSet' => $fileSet]],
        );

        $exitCode = $this->renderer->render($validationResult);

        self::assertSame(1, $exitCode);

        $jsonOutput = $this->output->fetch();
        $output = json_decode($jsonOutput, true);

        self::assertNotNull($output);
        self::assertArrayHasKey('issues', $output);

        $issues = $output['issues'];
        self::assertArrayHasKey('/test/path/file1.xlf', $issues);
        self::assertArrayHasKey('/test/path/file2.xlf', $issues);

        // Each file should have the validator's issues
        self::assertArrayHasKey('MockValidator', $issues['/test/path/file1.xlf']);
        self::assertArrayHasKey('MockValidator', $issues['/test/path/file2.xlf']);
    }

    public function testGroupIssuesByFileWithEmptyValidators(): void
    {
        $validationResult = new ValidationResult([], ResultType::SUCCESS, []);

        $exitCode = $this->renderer->render($validationResult);

        self::assertSame(0, $exitCode);

        $jsonOutput = $this->output->fetch();
        $output = json_decode($jsonOutput, true);

        self::assertNotNull($output);
        self::assertArrayHasKey('issues', $output);
        self::assertEmpty($output['issues']);
    }

    public function testGroupIssuesByFileWithValidatorsWithoutIssues(): void
    {
        $validator = $this->createMockValidator();
        $validator->method('hasIssues')->willReturn(false);
        $validator->method('getIssues')->willReturn([]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::SUCCESS,
            [['validator' => $validator, 'fileSet' => $fileSet]],
        );

        $exitCode = $this->renderer->render($validationResult);

        self::assertSame(0, $exitCode);

        $jsonOutput = $this->output->fetch();
        $output = json_decode($jsonOutput, true);

        self::assertNotNull($output);
        self::assertArrayHasKey('issues', $output);
        self::assertEmpty($output['issues']);
    }

    public function testRenderKeepsAngleBracketsAndMarkupLikeTextInMessages(): void
    {
        $validator = new DuplicateKeysValidator();
        $validator->addIssue(new Issue('test.xlf', ['<bg=red>key</>' => 2], 'TestParser', 'DuplicateKeysValidator'));
        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);

        $this->renderer->render(new ValidationResult(
            [$validator],
            ResultType::ERROR,
            [['validator' => $validator, 'fileSet' => $fileSet]],
        ));

        $decoded = json_decode($this->output->fetch(), true, 512, \JSON_THROW_ON_ERROR);
        $fileIssues = reset($decoded['issues']);
        $message = reset($fileIssues)['issues'][0]['message'];

        self::assertStringContainsString('`<bg=red>key</>`', $message);
        self::assertStringNotContainsString('\\', $message);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\Stub&ValidatorInterface
     */
    private function createMockValidator(): \PHPUnit\Framework\MockObject\Stub
    {
        /** @var \PHPUnit\Framework\MockObject\Stub&ValidatorInterface $validator */
        $validator = self::createStub(ValidatorInterface::class);
        $validator->method('resultTypeOnValidationFailure')->willReturn(ResultType::ERROR);
        $validator->method('formatIssueMessage')->willReturnCallback(static fn (Issue $issue, string $prefix = ''): string => "- ERROR {$prefix}Validation error");
        $validator->method('distributeIssuesForDisplay')->willReturnCallback(static function (FileSet $fileSet) use ($validator): array {
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
        $validator->method('validate')->willReturn(true);
        $validator->method('addIssue');

        return $validator;
    }
}
