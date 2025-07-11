<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Result;

use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractValidationResultRenderer implements ValidationResultRendererInterface
{
    public function __construct(
        protected readonly OutputInterface $output,
        protected readonly bool $dryRun = false,
        protected readonly bool $strict = false,
    ) {
    }

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

        $cwd = realpath(getcwd()).DIRECTORY_SEPARATOR;
        $normalizedPath = rtrim($realPath, DIRECTORY_SEPARATOR);

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
