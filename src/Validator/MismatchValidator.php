<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MismatchValidator extends AbstractValidator implements ValidatorInterface
{
    /** @var array<string, array<string>> */
    protected array $keyArray = [];

    public function processFile(ParserInterface $file): array
    {
        $keys = $file->extractKeys();

        if (!$keys) {
            $this->logger?->error('The source file '.$file->getFileName().' is not valid.');

            return [];
        }
        foreach ($keys as $key) {
            $value = $file->getContentByKey($key);
            $this->keyArray[$file->getFileName()][$key] = $value ?? null;
        }

        return [];
    }

    public function postProcess(): void
    {
        $allKeys = [];
        foreach ($this->keyArray as $values) {
            $allKeys[] = array_keys($values);
        }
        $allKeys = array_unique(array_merge(...$allKeys));

        foreach ($allKeys as $key) {
            $missingInSome = false;
            foreach ($this->keyArray as $keys) {
                if (!array_key_exists($key, $keys)) {
                    $missingInSome = true;
                    break;
                }
            }
            if ($missingInSome) {
                $result = [
                    'key' => $key,
                    'files' => [],
                ];
                foreach ($this->keyArray as $file => $keys) {
                    $result['files'][] = [
                        'file' => $file,
                        'value' => $keys[$key] ?? null,
                    ];
                }
                $this->addIssue(new Issue(
                    '',
                    $result,
                    '',
                    'MismatchValidator'
                ));
            }
        }
    }

    /**
     * @param array<string, array<int, array<mixed>>> $issueSets
     */
    public function renderIssueSets(InputInterface $input, OutputInterface $output, array $issueSets): void
    {
        $rows = [];
        $header = ['Key'];
        $allFiles = [];

        foreach ($issueSets as $issuesPerFile) {
            foreach ($issuesPerFile as $issues) {
                // Handle both new format (with 'issues' key) and old format (direct data)
                if (isset($issues['issues']) && is_array($issues['issues'])) {
                    $issueData = $issues['issues'];
                } else {
                    $issueData = $issues;
                }
                $key = $issueData['key'];
                $files = $issueData['files'];
                if (empty($allFiles)) {
                    $allFiles = array_column($files, 'file');
                    $header = array_merge(['Key'], array_map(static fn ($f) => "<fg=red>$f</>", $allFiles));
                }
                $row = [$key];
                foreach ($files as $fileInfo) {
                    $row[] = $fileInfo['value'] ?? '<fg=yellow><missing></>';
                }
                $rows[] = $row;
            }
        }

        (new Table($output))
            ->setHeaders($header)
            ->setRows($rows)
            ->setStyle(
                (new TableStyle())
                    ->setCellHeaderFormat('%s')
            )
            ->render();
    }

    public function explain(): string
    {
        return 'This validator checks for keys that are present in some files but not in others. '
            .'It helps to identify mismatches in translation keys across different translation files.';
    }

    /**
     * @return class-string<ParserInterface>[]
     */
    public function supportsParser(): array
    {
        return [XliffParser::class, YamlParser::class];
    }

    protected function resetState(): void
    {
        parent::resetState();
        $this->keyArray = [];
    }

    public function resultTypeOnValidationFailure(): ResultType
    {
        return ResultType::WARNING;
    }

    public function formatIssueMessage(Issue $issue, string $prefix = '', bool $isVerbose = false): string
    {
        $details = $issue->getDetails();
        $resultType = $this->resultTypeOnValidationFailure();

        $level = $resultType->toString();
        $color = $resultType->toColorString();

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

        return "- <fg=$color>$level</> {$prefix}translation key `$key` is $action other translation files (`$otherFilesList`)";
    }

    public function distributeIssuesForDisplay(FileSet $fileSet): array
    {
        $distribution = [];

        foreach ($this->issues as $issue) {
            $details = $issue->getDetails();
            $files = $details['files'] ?? [];

            // Add the issue to each affected file
            foreach ($files as $fileInfo) {
                $fileName = $fileInfo['file'] ?? '';
                if (!empty($fileName)) {
                    // Construct full file path from fileSet
                    $basePath = rtrim($fileSet->getPath(), '/');
                    $filePath = $basePath.'/'.$fileName;

                    // Create a new issue specific to this file
                    $fileSpecificIssue = new Issue(
                        $filePath,
                        $details,
                        $issue->getParser(),
                        $issue->getValidatorType()
                    );

                    if (!isset($distribution[$filePath])) {
                        $distribution[$filePath] = [];
                    }

                    $distribution[$filePath][] = $fileSpecificIssue;
                }
            }
        }

        return $distribution;
    }

    public function shouldShowDetailedOutput(): bool
    {
        return true;
    }

    public function renderDetailedOutput(OutputInterface $output, array $issues): void
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

        $table = new Table($output);
        $table->setHeaders($header)
              ->setRows($rows)
              ->setStyle(
                  (new TableStyle())
                      ->setCellHeaderFormat('%s')
              )
              ->render();
    }
}
