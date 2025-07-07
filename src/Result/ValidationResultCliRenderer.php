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

            // Special handling for MismatchValidator - assign issues to affected files
            if (str_contains($validator::class, 'MismatchValidator')) {
                $this->handleMismatchValidatorIssues($validator, $fileSet, $groupedByFile);
            } else {
                foreach ($validator->getIssues() as $issue) {
                    $fileName = $issue->getFile();
                    // Build full path from fileSet and filename for consistency
                    $filePath = empty($fileName) ? '' : $fileSet->getPath().'/'.$fileName;

                    if (!empty($filePath) && !isset($groupedByFile[$filePath])) {
                        $groupedByFile[$filePath] = [];
                    }

                    if (!empty($filePath)) {
                        $groupedByFile[$filePath][] = [
                            'validator' => $validator,
                            'issue' => $issue,
                        ];
                    }
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

            // Special handling for MismatchValidator - assign issues to affected files
            if (str_contains($validator::class, 'MismatchValidator')) {
                $this->handleMismatchValidatorIssuesVerbose($validator, $fileSet, $groupedByFile);
            } else {
                foreach ($validator->getIssues() as $issue) {
                    $fileName = $issue->getFile();
                    $validatorClass = $validator::class;
                    // Build full path from fileSet and filename for consistency
                    $filePath = empty($fileName) ? '' : $fileSet->getPath().'/'.$fileName;

                    if (!empty($filePath)) {
                        if (!isset($groupedByFile[$filePath])) {
                            $groupedByFile[$filePath] = [];
                        }
                        if (!isset($groupedByFile[$filePath][$validatorClass])) {
                            $groupedByFile[$filePath][$validatorClass] = [
                                'validator' => $validator,
                                'issues' => [],
                            ];
                        }

                        $groupedByFile[$filePath][$validatorClass]['issues'][] = $issue;
                    }
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

                $this->io->writeln("  <fg=cyan;options=bold>$validatorName</>");

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
                if ($this->shouldShowDetailedOutput($validator)) {
                    $this->renderDetailedValidatorOutput($validator, $issues);
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
        // MismatchValidator and DuplicateValuesValidator produce warnings
        if (str_contains($validatorClass, 'MismatchValidator')
            || str_contains($validatorClass, 'DuplicateValuesValidator')) {
            return 2; // Warning
        }

        // All other validators produce errors
        return 1; // Error
    }

    /**
     * @param \MoveElevator\ComposerTranslationValidator\FileDetector\FileSet               $fileSet
     * @param array<string, array<int, array{validator: ValidatorInterface, issue: Issue}>> $groupedByFile
     */
    private function handleMismatchValidatorIssues(ValidatorInterface $validator, $fileSet, array &$groupedByFile): void
    {
        foreach ($validator->getIssues() as $issue) {
            $details = $issue->getDetails();
            $files = $details['files'] ?? [];

            // Add the issue to each affected file
            foreach ($files as $fileInfo) {
                $fileName = $fileInfo['file'] ?? '';
                if (!empty($fileName)) {
                    // Construct full file path from fileSet
                    $filePath = $fileSet->getPath().'/'.$fileName;

                    // Create a new issue specific to this file
                    $fileSpecificIssue = new Issue(
                        $filePath,
                        $details,
                        $issue->getParser(),
                        $issue->getValidatorType()
                    );

                    if (!isset($groupedByFile[$filePath])) {
                        $groupedByFile[$filePath] = [];
                    }

                    $groupedByFile[$filePath][] = [
                        'validator' => $validator,
                        'issue' => $fileSpecificIssue,
                    ];
                }
            }
        }
    }

    /**
     * @param \MoveElevator\ComposerTranslationValidator\FileDetector\FileSet                          $fileSet
     * @param array<string, array<string, array{validator: ValidatorInterface, issues: array<Issue>}>> $groupedByFile
     */
    private function handleMismatchValidatorIssuesVerbose(ValidatorInterface $validator, $fileSet, array &$groupedByFile): void
    {
        foreach ($validator->getIssues() as $issue) {
            $details = $issue->getDetails();
            $files = $details['files'] ?? [];
            $validatorClass = $validator::class;

            // Add the issue to each affected file
            foreach ($files as $fileInfo) {
                $fileName = $fileInfo['file'] ?? '';
                if (!empty($fileName)) {
                    // Construct full file path from fileSet
                    $filePath = $fileSet->getPath().'/'.$fileName;

                    // Create a new issue specific to this file
                    $fileSpecificIssue = new Issue(
                        $filePath,
                        $details,
                        $issue->getParser(),
                        $issue->getValidatorType()
                    );

                    if (!isset($groupedByFile[$filePath])) {
                        $groupedByFile[$filePath] = [];
                    }
                    if (!isset($groupedByFile[$filePath][$validatorClass])) {
                        $groupedByFile[$filePath][$validatorClass] = [
                            'validator' => $validator,
                            'issues' => [],
                        ];
                    }

                    $groupedByFile[$filePath][$validatorClass]['issues'][] = $fileSpecificIssue;
                }
            }
        }
    }

    private function formatIssueMessage(ValidatorInterface $validator, Issue $issue, string $validatorName = '', bool $isVerbose = false): string
    {
        $details = $issue->getDetails();
        $prefix = $isVerbose ? '' : "($validatorName) ";

        // Handle different validator types
        $validatorClass = $validator::class;

        if (str_contains($validatorClass, 'DuplicateKeysValidator')) {
            // Details contains duplicate keys with their counts
            $messages = [];
            foreach ($details as $key => $count) {
                if (is_string($key) && is_int($count)) {
                    $messages[] = "- <fg=red>ERROR</> {$prefix}the translation key `$key` occurs multiple times ({$count}x)";
                }
            }

            return implode("\n", $messages);
        }

        if (str_contains($validatorClass, 'DuplicateValuesValidator')) {
            // Details contains duplicate values with their keys
            $messages = [];
            foreach ($details as $value => $keys) {
                if (is_string($value) && is_array($keys)) {
                    $keyList = implode('`, `', $keys);
                    $messages[] = "- <fg=yellow>WARNING</> {$prefix}the translation value `$value` occurs in multiple keys (`$keyList`)";
                }
            }

            return implode("\n", $messages);
        }

        if (str_contains($validatorClass, 'SchemaValidator')) {
            // Details contains schema validation errors from XliffUtils::validateSchema()
            $messages = [];

            // The details array directly contains the validation errors
            foreach ($details as $error) {
                if (is_array($error)) {
                    $message = $error['message'] ?? 'Schema validation error';
                    $line = isset($error['line']) ? " (Line: {$error['line']})" : '';
                    $code = isset($error['code']) ? " (Code: {$error['code']})" : '';
                    $level = $error['level'] ?? 'ERROR';

                    $color = 'ERROR' === strtoupper($level) ? 'red' : 'yellow';
                    $levelText = strtoupper($level);

                    $messages[] = "- <fg=$color>$levelText</> {$prefix}$message$line$code";
                }
            }

            if (empty($messages)) {
                $messages[] = "- <fg=red>ERROR</> {$prefix}Schema validation error";
            }

            return implode("\n", $messages);
        }

        if (str_contains($validatorClass, 'MismatchValidator')) {
            // Details contains key mismatch information
            $key = $details['key'] ?? 'unknown';
            $files = $details['files'] ?? [];
            $currentFile = basename($issue->getFile());
            $otherFiles = [];
            $currentFileHasValue = false;

            foreach ($files as $fileInfo) {
                $fileName = $fileInfo['file'] ?? 'unknown';
                if ($fileName === $currentFile) {
                    $currentFileHasValue = null !== $fileInfo['value'];
                } else {
                    $otherFiles[] = $fileName;
                }
            }

            if ($currentFileHasValue) {
                $action = 'missing from';
            } else {
                $action = 'present in';
            }

            $otherFilesList = !empty($otherFiles) ? implode('`, `', $otherFiles) : 'other files';

            return "- <fg=yellow>WARNING</> {$prefix}translation key `$key` is $action other translation files (`$otherFilesList`)";
        }

        // Fallback for other validators
        $message = $details['message'] ?? 'Validation error';

        return "- <fg=red>ERROR</> {$prefix}$message";
    }

    private function shouldShowDetailedOutput(ValidatorInterface $validator): bool
    {
        $validatorClass = $validator::class;

        return str_contains($validatorClass, 'MismatchValidator');
    }

    /**
     * @param array<Issue> $issues
     */
    private function renderDetailedValidatorOutput(ValidatorInterface $validator, array $issues): void
    {
        // For MismatchValidator, show the detailed table
        if (str_contains($validator::class, 'MismatchValidator')) {
            $this->io->newLine();
            $this->renderMismatchTable($issues);
        }
    }

    /**
     * @param array<Issue> $issues
     */
    private function renderMismatchTable(array $issues): void
    {
        if (empty($issues)) {
            return;
        }

        $rows = [];
        $allKeys = [];
        $allFilesData = [];

        // Collect all data
        foreach ($issues as $issue) {
            $details = $issue->getDetails();
            $key = $details['key'] ?? 'unknown';
            $files = $details['files'] ?? [];
            $currentFile = basename($issue->getFile());

            if (!in_array($key, $allKeys)) {
                $allKeys[] = $key;
            }

            foreach ($files as $fileInfo) {
                $fileName = $fileInfo['file'] ?? '';
                $value = $fileInfo['value'];

                if (!isset($allFilesData[$key])) {
                    $allFilesData[$key] = [];
                }
                $allFilesData[$key][$fileName] = $value;
            }
        }

        // Get first issue to determine current file and file order
        $firstIssue = $issues[0];
        $currentFile = basename($firstIssue->getFile());
        $firstDetails = $firstIssue->getDetails();
        $firstFiles = $firstDetails['files'] ?? [];

        // Order files: current file first, then others
        $fileOrder = [$currentFile];
        foreach ($firstFiles as $fileInfo) {
            $fileName = $fileInfo['file'] ?? '';
            if ($fileName !== $currentFile && !in_array($fileName, $fileOrder)) {
                $fileOrder[] = $fileName;
            }
        }

        $header = ['Translation Key', $currentFile];
        foreach ($fileOrder as $fileName) {
            if ($fileName !== $currentFile) {
                $header[] = $fileName;
            }
        }

        // Build rows
        foreach ($allKeys as $key) {
            $row = [$key];
            foreach ($fileOrder as $fileName) {
                $value = $allFilesData[$key][$fileName] ?? null;
                $row[] = $value ?? '';  // Empty string instead of <missing>
            }
            $rows[] = $row;
        }

        $table = new \Symfony\Component\Console\Helper\Table($this->output);
        $table->setHeaders($header)
              ->setRows($rows)
              ->setStyle(
                  (new \Symfony\Component\Console\Helper\TableStyle())
                      ->setCellHeaderFormat('%s')
              )
              ->render();
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
