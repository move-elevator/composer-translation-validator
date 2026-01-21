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

use MoveElevator\ComposerTranslationValidator\Utility\PathUtility;
use MoveElevator\ComposerTranslationValidator\Validator\{ResultType, ValidatorInterface};
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * ValidationResultCliRenderer.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class ValidationResultCliRenderer extends AbstractValidationResultRenderer
{
    private readonly SymfonyStyle $io;

    public function __construct(
        OutputInterface $output,
        private readonly InputInterface $input,
        bool $dryRun = false,
        bool $strict = false,
    ) {
        parent::__construct($output, $dryRun, $strict);
        $this->io = new SymfonyStyle($this->input, $this->output);
    }

    public function render(ValidationResult $validationResult): int
    {
        if ($this->output->isVerbose()) {
            $this->renderVerboseOutput($validationResult);
        } else {
            $this->renderCompactOutput($validationResult);
        }

        $this->renderSummary($validationResult->getOverallResult());

        if ($this->output->isVerbose()) {
            $this->renderStatistics($validationResult);
        }

        return $this->calculateExitCode($validationResult);
    }

    private function renderHeader(): void
    {
        $this->io->title('Composer Translation Validator');
        $this->io->text([
            'A comprehensive tool for validating translation files (XLIFF, YAML, JSON and PHP).',
            'Checks for mismatches, duplicates, placeholder consistency and schema compliance.',
            '',
            'For more information and usage examples, run: <fg=cyan>composer validate-translations --help</>',
        ]);
        $this->io->newLine();
    }

    private function renderCompactOutput(ValidationResult $validationResult): void
    {
        $groupedByFile = $this->groupIssuesByFile($validationResult);

        if (empty($groupedByFile)) {
            return;
        }

        // Check if we have any errors (not just warnings)
        $hasErrors = false;
        foreach ($groupedByFile as $validatorGroups) {
            foreach ($validatorGroups as $validatorData) {
                if ($validatorData['validator']->hasIssues()
                    && ResultType::ERROR === $validatorData['validator']
                        ->resultTypeOnValidationFailure()) {
                    $hasErrors = true;
                    break 2;
                }
            }
        }

        // Only show detailed output for errors, not warnings
        if (!$hasErrors) {
            return;
        }

        foreach ($groupedByFile as $filePath => $validatorGroups) {
            $relativePath = PathUtility::normalizeFolderPath($filePath);
            $this->io->writeln("<fg=cyan>$relativePath</>");
            $this->io->newLine();

            $fileIssues = [];
            foreach ($validatorGroups as $validatorData) {
                foreach ($validatorData['issues'] as $issue) {
                    $fileIssues[] = [
                        'validator' => $validatorData['validator'],
                        'issue' => $issue,
                    ];
                }
            }

            $sortedIssues = $this->sortIssuesBySeverity($fileIssues);

            foreach ($sortedIssues as $fileIssue) {
                $validator = $fileIssue['validator'];
                $issue = $fileIssue['issue'];
                $validatorName = $validator->getShortName();

                $message = $this->formatIssueMessage($validator, $issue, $validatorName);
                $lines = explode("\n", $message);
                foreach ($lines as $line) {
                    if (!empty(trim($line))) {
                        $this->io->writeln($line);
                    }
                }
            }

            $this->io->newLine();
        }
    }

    private function renderVerboseOutput(ValidationResult $validationResult): void
    {
        $this->renderHeader();

        $groupedByFile = $this->groupIssuesByFile($validationResult);

        if (empty($groupedByFile)) {
            return;
        }

        foreach ($groupedByFile as $filePath => $validatorGroups) {
            $relativePath = PathUtility::normalizeFolderPath($filePath);
            $this->io->writeln("<fg=cyan>$relativePath</>");
            $this->io->newLine();

            $sortedValidatorGroups = $this->sortValidatorGroupsBySeverity($validatorGroups);

            foreach ($sortedValidatorGroups as $validatorName => $validatorData) {
                $validator = $validatorData['validator'];
                $issues = $validatorData['issues'];

                $this->io->writeln("  <options=bold>$validatorName</>");

                foreach ($issues as $issue) {
                    $message = $this->formatIssueMessage($validator, $issue, '', true);
                    $lines = explode("\n", $message);
                    foreach ($lines as $line) {
                        if (!empty(trim($line))) {
                            $this->io->writeln("    $line");
                        }
                    }
                }

                if ($validator->shouldShowDetailedOutput()) {
                    $this->io->newLine();
                    $validator->renderDetailedOutput($this->output, $issues);
                }

                $this->io->newLine();
            }
        }
    }

    /**
     * @param array<array{validator: ValidatorInterface, issue: Issue}> $fileIssues
     *
     * @return array<array{validator: ValidatorInterface, issue: Issue}>
     */
    private function sortIssuesBySeverity(array $fileIssues): array
    {
        usort($fileIssues, function ($a, $b) {
            $severityA = $this->getIssueSeverity($a['validator']);
            $severityB = $this->getIssueSeverity($b['validator']);

            return $severityA <=> $severityB;
        });

        return $fileIssues;
    }

    /**
     * @param array<string, array{validator: ValidatorInterface, type: string, issues: array<Issue>}> $validatorGroups
     *
     * @return array<string, array{validator: ValidatorInterface, type: string, issues: array<Issue>}>
     */
    private function sortValidatorGroupsBySeverity(array $validatorGroups): array
    {
        uksort($validatorGroups, function ($validatorNameA, $validatorNameB) use ($validatorGroups) {
            $validatorA = $validatorGroups[$validatorNameA]['validator'];
            $validatorB = $validatorGroups[$validatorNameB]['validator'];

            $severityA = $this->getValidatorSeverity($validatorA::class);
            $severityB = $this->getValidatorSeverity($validatorB::class);

            return $severityA <=> $severityB;
        });

        return $validatorGroups;
    }

    private function getIssueSeverity(ValidatorInterface $validator): int
    {
        return $this->getValidatorSeverity($validator::class);
    }

    private function getValidatorSeverity(string $validatorClass): int
    {
        if (str_contains($validatorClass, 'SchemaValidator')) {
            return 1;
        }

        try {
            if (!class_exists($validatorClass)) {
                return 1;
            }
            /** @var class-string $validatorClass */
            $reflection = new ReflectionClass($validatorClass);
            if ($reflection->isInstantiable()) {
                $validator = $reflection->newInstance();
                if ($validator instanceof ValidatorInterface) {
                    $resultType = $validator->resultTypeOnValidationFailure();

                    return ResultType::ERROR === $resultType ? 1 : 2;
                }
            }
        } catch (Throwable) {
        }

        return 1;
    }

    private function formatIssueMessage(
        ValidatorInterface $validator,
        Issue $issue,
        string $validatorName = '',
        bool $isVerbose = false,
    ): string {
        $prefix = $isVerbose ? '' : "($validatorName) ";

        return $validator->formatIssueMessage($issue, $prefix);
    }

    private function renderSummary(ResultType $resultType): void
    {
        if ($resultType->notFullySuccessful()) {
            $message = $this->generateMessage(new ValidationResult([], $resultType));

            if (!$this->output->isVerbose()) {
                $message .= ' See more details with the `-v` verbose option.';

                if (ResultType::WARNING === $resultType && !$this->strict) {
                    $message .= ' Use `--strict` to treat warnings as errors.';
                }
            }

            if (ResultType::WARNING === $resultType
                && !$this->dryRun
                && !$this->strict
                && !$this->output->isVerbose()) {
                $this->output->writeln('<fg=yellow>'.$message.'</>');
            } else {
                $this->io->newLine();
                $this->io->{$this->dryRun || (ResultType::WARNING === $resultType && !$this->strict) ? 'warning' : 'error'}($message);
            }
        } else {
            $message = $this->generateMessage(new ValidationResult([], $resultType));
            $this->output->isVerbose()
                ? $this->io->success($message)
                : $this->output->writeln('<fg=green>'.$message.'</>');
        }
    }

    private function renderStatistics(ValidationResult $validationResult): void
    {
        $statisticsData = $this->formatStatisticsForOutput($validationResult);

        if (empty($statisticsData)) {
            return;
        }

        $this->io->newLine();
        $this->output->writeln('<fg=gray>Execution time: '.$statisticsData['execution_time_formatted'].'</>');
        $this->output->writeln('<fg=gray>Files checked: '.$statisticsData['files_checked'].'</>');
        $this->output->writeln('<fg=gray>Keys checked: '.$statisticsData['keys_checked'].'</>');
        $this->output->writeln('<fg=gray>Validators run: '.$statisticsData['validators_run'].'</>');
        $this->output->writeln('<fg=gray>Parsers cached: '.$statisticsData['parsers_cached'].'</>');
    }
}
