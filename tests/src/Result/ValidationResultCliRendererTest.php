<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Result\{Issue, ValidationResult, ValidationResultCliRenderer};
use MoveElevator\ComposerTranslationValidator\Validator\{ResultType, ValidatorInterface};
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\{BufferedOutput, OutputInterface};

/**
 * ValidationResultCliRendererTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
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
            $input,
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
            $pairs,
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
            true,  // dry run
        );

        $validator = $this->createMockValidator();
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::ERROR,
            [['validator' => $validator, 'fileSet' => $fileSet]],
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
            true,   // strict
        );

        $validator = $this->createMockValidator();
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::WARNING,
            [['validator' => $validator, 'fileSet' => $fileSet]],
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
            [['validator' => $validator, 'fileSet' => $fileSet]],
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
            [['validator' => $validator, 'fileSet' => $fileSet]],
        );

        $exitCode = $this->renderer->render($validationResult);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Language validation completed with warnings', $this->output->fetch());
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
            ],
        );

        $this->renderer->render($validationResult);

        $output = $this->output->fetch();
        // In new format, should not contain "Validator:" but should contain file names
        $this->assertStringNotContainsString('Validator:', $output);
        // Should contain both paths in the new compact format
        $this->assertStringContainsString('test1.xlf', $output);
        $this->assertStringContainsString('test2.xlf', $output);
    }

    public function testRenderStatisticsInVerboseMode(): void
    {
        $statistics = new \MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics(
            0.123,  // 123ms
            5,      // files
            15,     // keys
            3,       // validators
        );

        $validationResult = new ValidationResult([], ResultType::SUCCESS, [], $statistics);

        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $this->renderer->render($validationResult);
        $output = $this->output->fetch();

        $this->assertStringContainsString('Execution time: 123ms', $output);
        $this->assertStringContainsString('Files checked: 5', $output);
        $this->assertStringContainsString('Keys checked: 15', $output);
        $this->assertStringContainsString('Validators run: 3', $output);
        $this->assertStringContainsString('Parsers cached: 0', $output);
    }

    public function testRenderStatisticsNotInCompactMode(): void
    {
        $statistics = new \MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics(
            0.123, 5, 15, 3,
        );

        $validationResult = new ValidationResult([], ResultType::SUCCESS, [], $statistics);

        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $this->renderer->render($validationResult);
        $output = $this->output->fetch();

        $this->assertStringNotContainsString('Execution time:', $output);
        $this->assertStringNotContainsString('Files checked:', $output);
    }

    public function testRenderWarningInCompactMode(): void
    {
        $validator = $this->createMockValidator(ResultType::WARNING);
        $issue = new Issue('test.xlf', ['warning'], 'TestParser', 'TestValidator');
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([$issue]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::WARNING,
            [['validator' => $validator, 'fileSet' => $fileSet]],
        );

        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $exitCode = $this->renderer->render($validationResult);
        $output = $this->output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Language validation completed with warnings', $output);
    }

    public function testRenderErrorInCompactMode(): void
    {
        $validator = $this->createMockValidator(ResultType::ERROR);
        $issue = new Issue('test.xlf', ['error'], 'TestParser', 'TestValidator');
        $validator->method('hasIssues')->willReturn(true);
        $validator->method('getIssues')->willReturn([$issue]);

        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['test.xlf']);
        $validationResult = new ValidationResult(
            [$validator],
            ResultType::ERROR,
            [['validator' => $validator, 'fileSet' => $fileSet]],
        );

        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $exitCode = $this->renderer->render($validationResult);
        $output = $this->output->fetch();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('test/path/test.xlf', $output);
        $this->assertStringContainsString('Language validation failed with errors', $output);
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
            false,  // not strict
        );

        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $renderer->render($validationResult);
        $output = $this->output->fetch();

        $this->assertStringContainsString('Use `--strict` to treat warnings as errors', $output);
    }

    public function testRenderWarningInVerboseMode(): void
    {
        $validationResult = new ValidationResult([], ResultType::WARNING);

        /** @var MockObject&InputInterface $input */
        $input = $this->input;
        $renderer = new ValidationResultCliRenderer(
            $this->output,
            $input,
            false, // not dry run
            false,  // not strict
        );

        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $renderer->render($validationResult);
        $output = $this->output->fetch();

        $this->assertStringContainsString('Language validation completed with warnings', $output);
        $this->assertStringContainsString('[WARNING]', $output);
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
}
