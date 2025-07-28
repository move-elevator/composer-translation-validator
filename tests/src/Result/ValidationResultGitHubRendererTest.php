<?php

declare(strict_types=1);

/*
 * This file is part of the Composer plugin "composer-translation-validator".
 *
 * Copyright (C) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResult;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResultGitHubRenderer;
use MoveElevator\ComposerTranslationValidator\Result\ValidationStatistics;
use MoveElevator\ComposerTranslationValidator\Validator\AbstractValidator;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

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

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('::notice::Language validation succeeded.', $output);
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

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('::error file=translations/messages.en.yaml,line=15,col=2,title=Key%20Mismatch::Translation key mismatch found', $output);
        $this->assertStringContainsString('::error::Language validation failed with errors.', $output);
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

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('::warning file=translations/messages.de.yaml,line=8::Duplicate translation value detected', $output);
        $this->assertStringContainsString('::notice::Language validation completed with warnings.', $output);
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

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('::warning file=translations/messages.de.yaml,line=8::Duplicate translation value detected', $output);
        $this->assertStringContainsString('::error::Language validation failed with warnings in strict mode.', $output);
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

        $this->assertStringContainsString('file=translations/special%3Afile%2Cname.yaml', $output);
        $this->assertStringContainsString('title=Title%3A%20with%2C%20special%20characters', $output);
        $this->assertStringContainsString('Message with %25special%25 characters%3A newline', $output);
        $this->assertStringContainsString('and carriage return', $output);
    }

    public function testRenderWithStatistics(): void
    {
        $statistics = new ValidationStatistics(1.234, 5, 150, 8, 3);

        $validationResult = $this->createValidationResult([], ResultType::SUCCESS, $statistics);

        $this->renderer->render($validationResult);
        $output = $this->output->fetch();

        $this->assertStringContainsString('::notice::Validation completed in 1.23s - Files: 5, Keys: 150, Validators: 8', $output);
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

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('::notice::Language validation failed with errors in dry-run mode.', $output);
    }

    /**
     * @param Issue[] $issues
     */
    private function createValidationResult(
        array $issues,
        ResultType $resultType = ResultType::SUCCESS,
        ?ValidationStatistics $statistics = null,
    ): ValidationResult {
        $validator = $this->createMock(AbstractValidator::class);
        $validator->method('hasIssues')->willReturn(!empty($issues));
        $validator->method('getIssues')->willReturn($issues);
        $validator->method('resultTypeOnValidationFailure')->willReturn($resultType);
        $validator->method('getShortName')->willReturn('TestValidator');
        $validator->method('distributeIssuesForDisplay')->willReturn(
            array_reduce($issues, function (array $acc, Issue $issue) {
                $acc[$issue->getFile()][] = $issue;

                return $acc;
            }, []),
        );

        $fileSet = $this->createMock(FileSet::class);

        /** @var array<ValidatorInterface> $validators */
        $validators = [$validator];
        /** @var array<array{validator: ValidatorInterface, fileSet: FileSet}> $pairs */
        $pairs = [['validator' => $validator, 'fileSet' => $fileSet]];

        return new ValidationResult($validators, $resultType, $pairs, $statistics);
    }
}
