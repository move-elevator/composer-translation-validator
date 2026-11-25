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

namespace MoveElevator\ComposerTranslationValidator\Result;

use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

/**
 * ValidationResultGitHubRenderer.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class ValidationResultGitHubRenderer extends AbstractValidationResultRenderer
{
    public function render(ValidationResult $validationResult): int
    {
        $exitCode = $this->calculateExitCode($validationResult);

        // Output GitHub Actions workflow commands for each issue
        $this->renderIssues($validationResult);

        // Output summary
        $this->renderSummary($validationResult, $exitCode);

        return $exitCode;
    }

    private function renderIssues(ValidationResult $validationResult): void
    {
        $groupedByFile = $this->groupIssuesByFile($validationResult);

        $formatter = new OutputFormatter(false);

        foreach ($groupedByFile as $filePath => $validators) {
            foreach ($validators as $validatorData) {
                $validator = $validatorData['validator'];
                $resultType = $validator->resultTypeOnValidationFailure();

                foreach ($validatorData['issues'] as $issue) {
                    $message = $formatter->format($validator->formatIssueMessage($issue)) ?? '';
                    $this->renderGitHubAnnotation($issue, $filePath, $resultType, $message);
                }
            }
        }
    }

    private function renderGitHubAnnotation(Issue $issue, string $filePath, ResultType $resultType, string $message): void
    {
        $details = $issue->getDetails();
        $line = $details['line'] ?? null;
        $column = $details['column'] ?? null;

        $annotationType = match ($resultType) {
            ResultType::ERROR => 'error',
            ResultType::WARNING => 'warning',
            // @codeCoverageIgnoreStart
            default => 'notice',
            // @codeCoverageIgnoreEnd
        };

        $params = ['file='.$this->escapeProperty($filePath)];

        if (null !== $line) {
            $params[] = 'line='.$line;
        }

        if (null !== $column) {
            $params[] = 'col='.$column;
        }

        if (isset($details['title'])) {
            $params[] = 'title='.$this->escapeProperty((string) $details['title']);
        }

        $paramString = implode(',', $params);
        $escapedMessage = $this->escapeData($message);

        $this->output->writeln("::{$annotationType} {$paramString}::{$escapedMessage}", OutputInterface::OUTPUT_RAW);
    }

    private function renderSummary(ValidationResult $validationResult, int $exitCode): void
    {
        $message = $this->generateMessage($validationResult);
        $resultType = $validationResult->getOverallResult();

        $summaryType = match (true) {
            0 === $exitCode => 'notice',
            ResultType::WARNING === $resultType && !$this->strict => 'notice',
            default => 'error',
        };

        $this->output->writeln("::{$summaryType}::{$message}", OutputInterface::OUTPUT_RAW);

        $statistics = $this->formatStatisticsForOutput($validationResult);
        if (!empty($statistics)) {
            $statsMessage = sprintf(
                'Validation completed in %s - Files: %d, Keys: %d, Validators: %d',
                $statistics['execution_time_formatted'] ?? 'unknown',
                $statistics['files_checked'] ?? 0,
                $statistics['keys_checked'] ?? 0,
                $statistics['validators_run'] ?? 0,
            );

            $this->output->writeln("::notice::{$statsMessage}", OutputInterface::OUTPUT_RAW);
        }
    }

    /**
     * Escape property values for GitHub Actions annotations. The characters follow the actions toolkit:
     * https://github.com/actions/toolkit/blob/main/packages/core/src/command.ts.
     */
    private function escapeProperty(string $value): string
    {
        return $this->stripControlCharacters(str_replace(
            ['%', "\r", "\n", ':', ','],
            ['%25', '%0D', '%0A', '%3A', '%2C'],
            $value,
        ));
    }

    /**
     * Escape data values for GitHub Actions annotations, see escapeProperty().
     */
    private function escapeData(string $value): string
    {
        return $this->stripControlCharacters(str_replace(
            ['%', "\r", "\n"],
            ['%25', '%0D', '%0A'],
            $value,
        ));
    }

    private function stripControlCharacters(string $value): string
    {
        return preg_replace('/[\x00-\x08\x0B-\x1F\x7F]/', '', $value) ?? $value;
    }
}
