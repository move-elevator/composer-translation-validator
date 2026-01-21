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

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Parser\{JsonParser, ParserInterface, PhpParser, XliffParser, YamlParser};
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use Symfony\Component\Console\Helper\{Table, TableStyle};
use Symfony\Component\Console\Output\OutputInterface;

use function count;
use function in_array;

/**
 * PlaceholderConsistencyValidator.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class PlaceholderConsistencyValidator extends AbstractValidator implements ValidatorInterface
{
    /** @var array<string, array<string, array{value: string, placeholders: array<string>}>> */
    protected array $keyData = [];

    public function processFile(ParserInterface $file): array
    {
        $keys = $file->extractKeys();

        if (null === $keys) {
            $this->logger?->error(
                'The source file '.$file->getFileName().' is not valid.',
            );

            return [];
        }

        foreach ($keys as $key) {
            $value = $file->getContentByKey($key);
            if (null === $value) {
                continue;
            }

            $placeholders = $this->extractPlaceholders($value);
            $fileKey = !empty($this->currentFilePath) ? $this->currentFilePath : $file->getFileName();
            $this->keyData[$key][$fileKey] = [
                'value' => $value,
                'placeholders' => $placeholders,
            ];
        }

        return [];
    }

    public function postProcess(): void
    {
        foreach ($this->keyData as $key => $fileData) {
            $placeholderInconsistencies = $this->findPlaceholderInconsistencies($fileData);

            if (!empty($placeholderInconsistencies)) {
                $result = [
                    'key' => $key,
                    'files' => $fileData,
                    'inconsistencies' => $placeholderInconsistencies,
                ];

                $this->addIssue(new Issue(
                    '',
                    $result,
                    '',
                    $this->getShortName(),
                ));
            }
        }
    }

    public function formatIssueMessage(Issue $issue, string $prefix = ''): string
    {
        $details = $issue->getDetails();
        $resultType = $this->resultTypeOnValidationFailure();

        $level = $resultType->toString();
        $color = $resultType->toColorString();

        $key = $details['key'] ?? 'unknown';
        $inconsistencies = $details['inconsistencies'] ?? [];

        $inconsistencyText = implode('; ', $inconsistencies);

        return "- <fg={$color}>{$level}</> {$prefix}placeholder inconsistency in translation key `{$key}` - {$inconsistencyText}";
    }

    public function distributeIssuesForDisplay(FileSet $fileSet): array
    {
        $distribution = [];

        foreach ($this->issues as $issue) {
            $details = $issue->getDetails();
            $files = $details['files'] ?? [];

            foreach ($files as $filePath => $fileInfo) {
                if (!empty($filePath)) {
                    $fileSpecificIssue = new Issue(
                        $filePath,
                        $details,
                        $issue->getParser(),
                        $issue->getValidatorType(),
                    );

                    $distribution[$filePath] ??= [];
                    $distribution[$filePath][] = $fileSpecificIssue;
                }
            }
        }

        return $distribution;
    }

    public function renderDetailedOutput(OutputInterface $output, array $issues): void
    {
        if (empty($issues)) {
            return;
        }

        $rows = [];
        $allKeys = [];
        $allFilesData = [];

        foreach ($issues as $issue) {
            $details = $issue->getDetails();
            $key = $details['key'] ?? 'unknown';
            $files = $details['files'] ?? [];

            if (!in_array($key, $allKeys)) {
                $allKeys[] = $key;
            }

            foreach ($files as $filePath => $fileInfo) {
                $fileName = basename((string) $filePath);
                $value = $fileInfo['value'] ?? '';
                if (!isset($allFilesData[$key])) {
                    $allFilesData[$key] = [];
                }
                $allFilesData[$key][$fileName] = $value;
            }
        }

        $firstIssue = $issues[0];
        $firstDetails = $firstIssue->getDetails();
        $firstFiles = $firstDetails['files'] ?? [];

        $fileOrder = array_map(static fn ($path) => basename((string) $path), array_keys($firstFiles));

        $header = ['Translation Key'];
        foreach ($fileOrder as $fileName) {
            $header[] = $fileName;
        }

        foreach ($allKeys as $key) {
            $row = [$key];
            foreach ($fileOrder as $fileName) {
                $value = $allFilesData[$key][$fileName] ?? '';
                $row[] = $this->highlightPlaceholders($value);
            }
            $rows[] = $row;
        }

        $table = new Table($output);
        $table->setHeaders($header)
            ->setRows($rows)
            ->setStyle(
                (new TableStyle())
                    ->setCellHeaderFormat('%s'),
            )
            ->render();
    }

    /**
     * @return class-string<ParserInterface>[]
     */
    public function supportsParser(): array
    {
        return [XliffParser::class, YamlParser::class, JsonParser::class, PhpParser::class];
    }

    public function resultTypeOnValidationFailure(): ResultType
    {
        return ResultType::WARNING;
    }

    public function shouldShowDetailedOutput(): bool
    {
        return true;
    }

    protected function resetState(): void
    {
        parent::resetState();
        $this->keyData = [];
    }

    /**
     * Extract placeholders from a translation value
     * Supports various placeholder syntaxes:
     * - %parameter% (Symfony style)
     * - {parameter} (ICU MessageFormat style)
     * - {{ parameter }} (Twig style)
     * - %s, %d, %1$s (printf style)
     * - :parameter (Laravel style).
     *
     * @return array<string>
     */
    private function extractPlaceholders(string $value): array
    {
        $placeholders = [];

        // Symfony style: %parameter%
        if (preg_match_all('/%([a-zA-Z_][a-zA-Z0-9_]*)%/', $value, $matches)) {
            foreach ($matches[1] as $match) {
                $placeholders[] = "%{$match}%";
            }
        }

        // ICU MessageFormat style: {parameter}
        if (preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $value, $matches)) {
            foreach ($matches[1] as $match) {
                $placeholders[] = "{{$match}}";
            }
        }

        // Twig style: {{ parameter }}
        if (preg_match_all('/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/', $value, $matches)) {
            foreach ($matches[1] as $match) {
                $placeholders[] = "{{ {$match} }}";
            }
        }

        // Printf style: %s, %d, %1$s, etc.
        if (preg_match_all('/%(?:(\d+)\$)?[sdcoxXeEfFgGaA]/', $value, $matches)) {
            foreach ($matches[0] as $match) {
                $placeholders[] = $match;
            }
        }

        // Laravel style: :parameter
        if (preg_match_all('/:([a-zA-Z_][a-zA-Z0-9_]*)/', $value, $matches)) {
            foreach ($matches[1] as $match) {
                $placeholders[] = ":{$match}";
            }
        }

        return array_unique($placeholders);
    }

    /**
     * @param array<string, array{value: string, placeholders: array<string>}> $fileData
     *
     * @return array<string>
     */
    private function findPlaceholderInconsistencies(array $fileData): array
    {
        if (count($fileData) < 2) {
            return [];
        }

        $inconsistencies = [];
        $allPlaceholders = [];

        // Collect all placeholders from all files for this key
        foreach ($fileData as $fileName => $data) {
            $allPlaceholders[$fileName] = $data['placeholders'];
        }

        // Compare placeholders between files
        $fileNames = array_keys($allPlaceholders);
        $referenceFile = $fileNames[0];
        $referencePlaceholders = $allPlaceholders[$referenceFile];

        for ($i = 1, $iMax = count($fileNames); $i < $iMax; ++$i) {
            $currentFile = basename($fileNames[$i]);
            $currentPlaceholders = $allPlaceholders[$fileNames[$i]];

            $missing = array_diff($referencePlaceholders, $currentPlaceholders);
            $extra = array_diff($currentPlaceholders, $referencePlaceholders);

            if (!empty($missing)) {
                $inconsistencies[] = "File '{$currentFile}' is missing placeholders: ".implode(', ', $missing);
            }

            if (!empty($extra)) {
                $inconsistencies[] = "File '{$currentFile}' has extra placeholders: ".implode(', ', $extra);
            }
        }

        return $inconsistencies;
    }

    private function highlightPlaceholders(string $value): string
    {
        $placeholders = $this->extractPlaceholders($value);

        foreach ($placeholders as $placeholder) {
            $value = str_replace($placeholder, "<fg=yellow>{$placeholder}</>", $value);
        }

        return $value;
    }
}
