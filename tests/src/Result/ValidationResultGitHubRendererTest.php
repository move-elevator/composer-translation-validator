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
use MoveElevator\ComposerTranslationValidator\Result\{Issue, ValidationResult, ValidationResultGitHubRenderer, ValidationStatistics};
use MoveElevator\ComposerTranslationValidator\Validator\{AbstractValidator, MismatchValidator, ResultType, ValidatorInterface};
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * ValidationResultGitHubRendererTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class ValidationResultGitHubRendererTest extends TestCase
{
    private BufferedOutput $output;
    private ValidationResultGitHubRenderer $renderer;

    protected function setUp(): void
    {
        $this->output = new BufferedOutput();
        $this->renderer = new ValidationResultGitHubRenderer($this->output);
    }

    public function testRenderSuccessfulValidation(): void
    {
        $validationResult = $this->createValidationResult([]);

        $exitCode = $this->renderer->render($validationResult);
        $output = $this->output->fetch();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('::notice::Language validation succeeded.', $output);
    }

    public function testRenderErrorIssues(): void
    {
        $issue = new Issue(
            'translations/messages.en.yaml',
            [
                'message' => 'Translation key mismatch found',
                'line' => 15,
                'column' => 2,
                'title' => 'Key Mismatch',
            ],
            'TestParser',
            'TestValidator',
        );

        $validationResult = $this->createValidationResult([$issue], ResultType::ERROR);

        $exitCode = $this->renderer->render($validationResult);
        $output = $this->output->fetch();

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('::error file=translations/messages.en.yaml,line=15,col=2,title=Key Mismatch::Translation key mismatch found', $output);
        self::assertStringContainsString('::error::Language validation failed with errors.', $output);
    }

    public function testRenderWarningIssues(): void
    {
        $issue = new Issue(
            'translations/messages.de.yaml',
            [
                'message' => 'Duplicate translation value detected',
                'line' => 8,
            ],
            'TestParser',
            'TestValidator',
        );

        $validationResult = $this->createValidationResult([$issue], ResultType::WARNING);

        $exitCode = $this->renderer->render($validationResult);
        $output = $this->output->fetch();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('::warning file=translations/messages.de.yaml,line=8::Duplicate translation value detected', $output);
        self::assertStringContainsString('::notice::Language validation completed with warnings.', $output);
    }

    public function testRenderWarningIssuesWithStrictMode(): void
    {
        $renderer = new ValidationResultGitHubRenderer($this->output, false, true);

        $issue = new Issue(
            'translations/messages.de.yaml',
            [
                'message' => 'Duplicate translation value detected',
                'line' => 8,
            ],
            'TestParser',
            'TestValidator',
        );

        $validationResult = $this->createValidationResult([$issue], ResultType::WARNING);

        $exitCode = $renderer->render($validationResult);
        $output = $this->output->fetch();

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('::warning file=translations/messages.de.yaml,line=8::Duplicate translation value detected', $output);
        self::assertStringContainsString('::error::Language validation failed with warnings in strict mode.', $output);
    }

    public function testRenderEscapesSpecialCharacters(): void
    {
        $issue = new Issue(
            'translations/special:file,name.yaml',
            [
                'message' => "Message with %special% characters: newline\nand carriage return\r",
                'title' => 'Title: with, special characters',
            ],
            'TestParser',
            'TestValidator',
        );

        $validationResult = $this->createValidationResult([$issue], ResultType::ERROR);

        $this->renderer->render($validationResult);
        $output = $this->output->fetch();

        self::assertStringContainsString('file=translations/special%3Afile%2Cname.yaml', $output);
        self::assertStringContainsString('title=Title%3A with%2C special characters', $output);
        self::assertStringContainsString('Message with %25special%25 characters: newline', $output);
        self::assertStringContainsString('and carriage return', $output);
    }

    public function testRenderUsesValidatorMessageForIssuesWithoutMessageDetail(): void
    {
        $validator = new MismatchValidator();
        $validator->addIssue(new Issue(
            'app.de.yaml',
            [
                'key' => 'greeting',
                'files' => [
                    ['file' => 'app.en.yaml', 'value' => 'Hello'],
                    ['file' => 'app.de.yaml', 'value' => null],
                ],
            ],
            'TestParser',
            'MismatchValidator',
        ));
        $fileSet = new FileSet('TestParser', '/test/path', 'setKey', ['app.de.yaml']);

        $this->renderer->render(new ValidationResult(
            [$validator],
            ResultType::ERROR,
            [['validator' => $validator, 'fileSet' => $fileSet]],
        ));
        $output = $this->output->fetch();

        self::assertStringContainsString('the translation key `greeting` is missing but present in other translation files', $output);
        self::assertStringNotContainsString('Translation validation issue', $output);
    }

    public function testRenderKeepsSpacesInFilePathsAndRemovesControlCharacters(): void
    {
        $issue = new Issue(
            "translations/my folder/messages\x07.yaml",
            [
                'message' => "red\x07 text\r\nnext line",
                'title' => "Ti\x07tle",
            ],
            'TestParser',
            'TestValidator',
        );

        $this->renderer->render($this->createValidationResult([$issue], ResultType::ERROR));
        $output = $this->output->fetch();

        self::assertStringContainsString('::error file=translations/my folder/messages.yaml,title=Title::red text%0D%0Anext line', $output);
    }

    public function testRenderDoesNotInterpretConsoleMarkupInAnnotations(): void
    {
        $output = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true);
        $issue = new Issue(
            'translations/messages.yaml',
            ['message' => OutputFormatter::escape('<fg=red>key</>')],
            'TestParser',
            'TestValidator',
        );

        (new ValidationResultGitHubRenderer($output))->render($this->createValidationResult([$issue], ResultType::ERROR));
        $written = $output->fetch();

        self::assertStringContainsString('::<fg=red>key</>', $written);
        self::assertStringNotContainsString("\e", $written);
    }

    public function testRenderWithStatistics(): void
    {
        $statistics = new ValidationStatistics(1.234, 5, 150, 8, 3);

        $validationResult = $this->createValidationResult([], ResultType::SUCCESS, $statistics);

        $this->renderer->render($validationResult);
        $output = $this->output->fetch();

        self::assertStringContainsString('::notice::Validation completed in 1.23s - Files: 5, Keys: 150, Validators: 8', $output);
    }

    public function testRenderWithDryRun(): void
    {
        $renderer = new ValidationResultGitHubRenderer($this->output, true, false);

        $issue = new Issue(
            'translations/messages.en.yaml',
            ['message' => 'Test error'],
            'TestParser',
            'TestValidator',
        );

        $validationResult = $this->createValidationResult([$issue], ResultType::ERROR);

        $exitCode = $renderer->render($validationResult);
        $output = $this->output->fetch();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('::notice::Language validation failed with errors in dry-run mode.', $output);
    }

    /**
     * @param Issue[] $issues
     */
    private function createValidationResult(
        array $issues,
        ResultType $resultType = ResultType::SUCCESS,
        ?ValidationStatistics $statistics = null,
    ): ValidationResult {
        $validator = self::createStub(AbstractValidator::class);
        $validator->method('hasIssues')->willReturn(!empty($issues));
        $validator->method('getIssues')->willReturn($issues);
        $validator->method('resultTypeOnValidationFailure')->willReturn($resultType);
        $validator->method('getShortName')->willReturn('TestValidator');
        $validator->method('formatIssueMessage')->willReturnCallback(
            static fn (Issue $issue): string => (string) ($issue->getDetails()['message'] ?? ''),
        );
        $validator->method('distributeIssuesForDisplay')->willReturn(
            array_reduce($issues, static function (array $acc, Issue $issue) {
                $acc[$issue->getFile()][] = $issue;

                return $acc;
            }, []),
        );

        $fileSet = self::createStub(FileSet::class);

        /** @var array<ValidatorInterface> $validators */
        $validators = [$validator];
        /** @var array<array{validator: ValidatorInterface, fileSet: FileSet}> $pairs */
        $pairs = [['validator' => $validator, 'fileSet' => $fileSet]];

        return new ValidationResult($validators, $resultType, $pairs, $statistics);
    }
}
