<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Result;

use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ValidationResultCliRenderer
{
    private readonly SymfonyStyle $io;

    public function __construct(
        private readonly OutputInterface $output,
        private readonly InputInterface $input,
        private readonly bool $dryRun = false,
        private readonly bool $strict = false,
    ) {
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

        return $validationResult->getOverallResult()->resolveErrorToCommandExitCode($this->dryRun, $this->strict);
    }

    private function renderCompactOutput(ValidationResult $validationResult): void
    {
        $validatorPairs = $validationResult->getValidatorFileSetPairs();

        if (empty($validatorPairs)) {
            return;
        }

        // Group by file path
        $groupedByFile = [];
        foreach ($validatorPairs as $pair) {
            $validator = $pair['validator'];
            $fileSet = $pair['fileSet'];

            if (!$validator->hasIssues()) {
                continue;
            }

            // Use validator's distribution method to handle file-specific issues
            $distributedIssues = $validator->distributeIssuesForDisplay($fileSet);

            foreach ($distributedIssues as $filePath => $issues) {
                if (!isset($groupedByFile[$filePath])) {
                    $groupedByFile[$filePath] = [];
                }

                foreach ($issues as $issue) {
                    $groupedByFile[$filePath][] = [
                        'validator' => $validator,
                        'issue' => $issue,
                    ];
                }
            }
        }

        foreach ($groupedByFile as $filePath => $fileIssues) {
            $relativePath = $this->getRelativePath($filePath);
            $this->io->writeln("<fg=cyan>$relativePath</>");
            $this->io->newLine();

            // Sort issues by severity (errors first, warnings second)
            $sortedIssues = $this->sortIssuesBySeverity($fileIssues);

            foreach ($sortedIssues as $fileIssue) {
                $validator = $fileIssue['validator'];
                $issue = $fileIssue['issue'];
                $validatorName = $this->getValidatorShortName($validator::class);

                $message = $this->formatIssueMessage($validator, $issue, $validatorName);
                // Handle multiple lines from formatIssueMessage
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
        $validatorPairs = $validationResult->getValidatorFileSetPairs();

        if (empty($validatorPairs)) {
            return;
        }

        // Group by file path, then by validator
        $groupedByFile = [];
        foreach ($validatorPairs as $pair) {
            $validator = $pair['validator'];
            $fileSet = $pair['fileSet'];

            if (!$validator->hasIssues()) {
                continue;
            }

            // Use validator's distribution method to handle file-specific issues
            $distributedIssues = $validator->distributeIssuesForDisplay($fileSet);
            $validatorClass = $validator::class;

            foreach ($distributedIssues as $filePath => $issues) {
                if (!isset($groupedByFile[$filePath])) {
                    $groupedByFile[$filePath] = [];
                }
                if (!isset($groupedByFile[$filePath][$validatorClass])) {
                    $groupedByFile[$filePath][$validatorClass] = [
                        'validator' => $validator,
                        'issues' => [],
                    ];
                }

                foreach ($issues as $issue) {
                    $groupedByFile[$filePath][$validatorClass]['issues'][] = $issue;
                }
            }
        }

        foreach ($groupedByFile as $filePath => $validatorGroups) {
            $relativePath = $this->getRelativePath($filePath);
            $this->io->writeln("<fg=cyan>$relativePath</>");
            $this->io->newLine();

            // Sort validator groups by severity (errors first, warnings second)
            $sortedValidatorGroups = $this->sortValidatorGroupsBySeverity($validatorGroups);

            foreach ($sortedValidatorGroups as $validatorClass => $data) {
                $validator = $data['validator'];
                $issues = $data['issues'];
                $validatorName = $this->getValidatorShortName($validatorClass);

                $this->io->writeln("  <options=bold>$validatorName</>");

                foreach ($issues as $issue) {
                    $message = $this->formatIssueMessage($validator, $issue, '', true);
                    // Handle multiple lines from formatIssueMessage
                    $lines = explode("\n", $message);
                    foreach ($lines as $line) {
                        if (!empty(trim($line))) {
                            $this->io->writeln("    $line");
                        }
                    }
                }

                // Show detailed tables for certain validators in verbose mode
                if ($validator->shouldShowDetailedOutput()) {
                    $this->io->newLine();
                    $validator->renderDetailedOutput($this->output, $issues);
                }

                $this->io->newLine();
            }
        }
    }

    private function getValidatorShortName(string $validatorClass): string
    {
        $parts = explode('\\', $validatorClass);

        return end($parts);
    }

    private function getRelativePath(string $filePath): string
    {
        // If already relative (starts with ./), return as-is
        if (str_starts_with($filePath, './')) {
            return $filePath;
        }

        $cwd = getcwd();
        if ($cwd && str_starts_with($filePath, $cwd)) {
            return '.'.substr($filePath, strlen($cwd));
        }

        return $filePath;
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

            // Errors (1) come before warnings (2)
            return $severityA <=> $severityB;
        });

        return $fileIssues;
    }

    /**
     * @param array<string, array{validator: ValidatorInterface, issues: array<Issue>}> $validatorGroups
     *
     * @return array<string, array{validator: ValidatorInterface, issues: array<Issue>}>
     */
    private function sortValidatorGroupsBySeverity(array $validatorGroups): array
    {
        uksort($validatorGroups, function ($validatorClassA, $validatorClassB) {
            $severityA = $this->getValidatorSeverity($validatorClassA);
            $severityB = $this->getValidatorSeverity($validatorClassB);

            // Errors (1) come before warnings (2)
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
        // For SchemaValidator, maintain current behavior (always ERROR)
        if (str_contains($validatorClass, 'SchemaValidator')) {
            return 1; // Error
        }

        // For other validators, use their ResultType to determine severity
        try {
            $reflection = new \ReflectionClass($validatorClass);
            if ($reflection->isInstantiable()) {
                $validator = $reflection->newInstance();
                if ($validator instanceof ValidatorInterface) {
                    $resultType = $validator->resultTypeOnValidationFailure();
                    return $resultType === ResultType::ERROR ? 1 : 2;
                }
            }
        } catch (\ReflectionException | \Throwable $e) {
            // Fallback to error if we can't instantiate the validator
        }

        // Fallback to error
        return 1; // Error
    }


    private function formatIssueMessage(ValidatorInterface $validator, Issue $issue, string $validatorName = '', bool $isVerbose = false): string
    {
        $prefix = $isVerbose ? '' : "($validatorName) ";

        return $validator->formatIssueMessage($issue, $prefix, $isVerbose);
    }


    private function renderSummary(ResultType $resultType): void
    {
        if ($resultType->notFullySuccessful()) {
            $this->io->newLine();
            $message = $this->dryRun
                ? 'Language validation failed and completed in dry-run mode.'
                : 'Language validation failed.';

            if (!$this->output->isVerbose()) {
                $message .= ' See more details with the `-v` verbose option.';
            }

            $this->io->{$this->dryRun || ResultType::WARNING === $resultType ? 'warning' : 'error'}($message);
        } else {
            $message = 'Language validation succeeded.';
            $this->output->isVerbose()
                ? $this->io->success($message)
                : $this->output->writeln('<fg=green>'.$message.'</>');
        }
    }
}
