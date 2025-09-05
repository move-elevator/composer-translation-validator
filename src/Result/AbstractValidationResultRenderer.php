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
use Symfony\Component\Console\Output\OutputInterface;

/**
 * AbstractValidationResultRenderer.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
abstract class AbstractValidationResultRenderer implements ValidationResultRendererInterface
{
    public function __construct(
        protected readonly OutputInterface $output,
        protected readonly bool $dryRun = false,
        protected readonly bool $strict = false,
    ) {}

    protected function generateMessage(ValidationResult $validationResult): string
    {
        $resultType = $validationResult->getOverallResult();

        if (!$resultType->notFullySuccessful()) {
            return 'Language validation succeeded.';
        }

        return match (true) {
            $this->dryRun && ResultType::ERROR === $resultType => 'Language validation failed with errors in dry-run mode.',
            $this->dryRun && ResultType::WARNING === $resultType => 'Language validation completed with warnings in dry-run mode.',
            $this->strict && ResultType::WARNING === $resultType => 'Language validation failed with warnings in strict mode.',
            ResultType::ERROR === $resultType => 'Language validation failed with errors.',
            ResultType::WARNING === $resultType => 'Language validation completed with warnings.',
            default => 'Language validation failed.',
        };
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function groupIssuesByFile(ValidationResult $validationResult): array
    {
        $validatorPairs = $validationResult->getValidatorFileSetPairs();

        if (empty($validatorPairs)) {
            return [];
        }

        $groupedByFile = [];

        foreach ($validatorPairs as $pair) {
            $validator = $pair['validator'];
            $fileSet = $pair['fileSet'];

            if (!$validator->hasIssues()) {
                continue;
            }

            $distributedIssues = $validator->distributeIssuesForDisplay($fileSet);

            foreach ($distributedIssues as $filePath => $issues) {
                $normalizedPath = $this->normalizePath($filePath);

                if (!isset($groupedByFile[$normalizedPath])) {
                    $groupedByFile[$normalizedPath] = [];
                }

                $validatorName = $validator->getShortName();
                if (!isset($groupedByFile[$normalizedPath][$validatorName])) {
                    $groupedByFile[$normalizedPath][$validatorName] = [
                        'validator' => $validator,
                        'type' => $validator->resultTypeOnValidationFailure()->toString(),
                        'issues' => [],
                    ];
                }

                foreach ($issues as $issue) {
                    $groupedByFile[$normalizedPath][$validatorName]['issues'][] = $issue;
                }
            }
        }

        return $groupedByFile;
    }

    protected function normalizePath(string $path): string
    {
        $realPath = realpath($path);
        if (false === $realPath) {
            $normalizedPath = rtrim($path, DIRECTORY_SEPARATOR);
            if (str_starts_with($normalizedPath, './')) {
                $normalizedPath = substr($normalizedPath, 2);
            }

            return $normalizedPath;
        }

        $normalizedPath = rtrim($realPath, DIRECTORY_SEPARATOR);

        $cwd = getcwd();
        if (false === $cwd) {
            return $normalizedPath;
        }
        $realCwd = realpath($cwd);
        if (false === $realCwd) {
            return $normalizedPath;
        }
        $cwd = $realCwd.DIRECTORY_SEPARATOR;

        if (str_starts_with($normalizedPath.DIRECTORY_SEPARATOR, $cwd)) {
            return substr($normalizedPath, strlen($cwd));
        }

        return $normalizedPath;
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatStatisticsForOutput(ValidationResult $validationResult): array
    {
        $statistics = $validationResult->getStatistics();
        if (null === $statistics) {
            return [];
        }

        return [
            'execution_time' => $statistics->getExecutionTime(),
            'execution_time_formatted' => $statistics->getExecutionTimeFormatted(),
            'files_checked' => $statistics->getFilesChecked(),
            'keys_checked' => $statistics->getKeysChecked(),
            'validators_run' => $statistics->getValidatorsRun(),
            'parsers_cached' => $statistics->getParsersCached(),
        ];
    }

    protected function calculateExitCode(ValidationResult $validationResult): int
    {
        return $validationResult->getOverallResult()
            ->resolveErrorToCommandExitCode($this->dryRun, $this->strict);
    }
}
