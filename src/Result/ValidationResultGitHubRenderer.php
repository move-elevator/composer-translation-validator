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

namespace MoveElevator\ComposerTranslationValidator\Result;

use MoveElevator\ComposerTranslationValidator\Validator\ResultType;

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @package ComposerTranslationValidator
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

        foreach ($groupedByFile as $filePath => $validators) {
            foreach ($validators as $validatorData) {
                $resultType = $validatorData['validator']->resultTypeOnValidationFailure();

                foreach ($validatorData['issues'] as $issue) {
                    $this->renderGitHubAnnotation($issue, $filePath, $resultType);
                }
            }
        }
    }

    private function renderGitHubAnnotation(Issue $issue, string $filePath, ResultType $resultType): void
    {
        $details = $issue->getDetails();
        $message = $details['message'] ?? 'Translation validation issue';
        $line = $details['line'] ?? null;
        $column = $details['column'] ?? null;

        $annotationType = match ($resultType) {
            ResultType::ERROR => 'error',
            ResultType::WARNING => 'warning',
            default => 'notice',
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

        $this->output->writeln("::{$annotationType} {$paramString}::{$escapedMessage}");
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

        $this->output->writeln("::{$summaryType}::{$message}");

        $statistics = $this->formatStatisticsForOutput($validationResult);
        if (!empty($statistics)) {
            $statsMessage = sprintf(
                'Validation completed in %s - Files: %d, Keys: %d, Validators: %d',
                $statistics['execution_time_formatted'] ?? 'unknown',
                $statistics['files_checked'] ?? 0,
                $statistics['keys_checked'] ?? 0,
                $statistics['validators_run'] ?? 0,
            );

            $this->output->writeln("::notice::{$statsMessage}");
        }
    }

    /**
     * Escape property values for GitHub Actions annotations
     * https://docs.github.com/en/actions/using-workflows/workflow-commands-for-github-actions#setting-an-error-message.
     */
    private function escapeProperty(string $value): string
    {
        return str_replace(
            ['%', "\r", "\n", ':', ',', ' '],
            ['%25', '%0D', '%0A', '%3A', '%2C', '%20'],
            $value,
        );
    }

    /**
     * Escape data values for GitHub Actions annotations
     * https://docs.github.com/en/actions/using-workflows/workflow-commands-for-github-actions#setting-an-error-message.
     */
    private function escapeData(string $value): string
    {
        return str_replace(
            ['%', "\r", "\n", ':'],
            ['%25', '%0D', '%0A', '%3A'],
            $value,
        );
    }
}
