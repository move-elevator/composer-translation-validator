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

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Parser\JsonParser;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\PhpParser;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * HtmlTagValidator.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
class HtmlTagValidator extends AbstractValidator implements ValidatorInterface
{
    /** @var array<string, array<string, array{value: string, html_structure: array<string, mixed>}>> */
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

            $htmlStructure = $this->analyzeHtmlStructure($value);
            $fileKey = !empty($this->currentFilePath) ? $this->currentFilePath : $file->getFileName();
            $this->keyData[$key][$fileKey] = [
                'value' => $value,
                'html_structure' => $htmlStructure,
            ];
        }

        return [];
    }

    public function postProcess(): void
    {
        foreach ($this->keyData as $key => $fileData) {
            $htmlInconsistencies = $this->findHtmlInconsistencies($fileData);

            if (!empty($htmlInconsistencies)) {
                $result = [
                    'key' => $key,
                    'files' => $fileData,
                    'inconsistencies' => $htmlInconsistencies,
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

    /**
     * Analyze HTML structure of a translation value.
     *
     * @return array<string, mixed>
     */
    private function analyzeHtmlStructure(string $value): array
    {
        $structure = [
            'tags' => [],
            'self_closing_tags' => [],
            'attributes' => [],
            'structure_errors' => [],
        ];

        // Extract all HTML tags with their attributes
        if (preg_match_all('/<(\/?)([\w\-]+)([^>]*)>/i', $value, $matches, PREG_SET_ORDER)) {
            $tagStack = [];

            foreach ($matches as $match) {
                $isClosing = !empty($match[1]);
                $tagName = strtolower($match[2]);
                $attributes = trim($match[3]);

                if ($isClosing) {
                    // Closing tag
                    if (empty($tagStack) || end($tagStack) !== $tagName) {
                        $structure['structure_errors'][] = "Unmatched closing tag: </{$tagName}>";
                    } else {
                        array_pop($tagStack);
                    }
                } else {
                    // Opening tag or self-closing
                    if (str_ends_with($attributes, '/')) {
                        // Self-closing tag
                        $structure['self_closing_tags'][] = $tagName;
                        $attributes = rtrim($attributes, ' /');
                    } else {
                        // Regular opening tag
                        $tagStack[] = $tagName;
                    }

                    $structure['tags'][] = $tagName;

                    // Extract attributes
                    if (!empty($attributes)) {
                        $structure['attributes'][$tagName] = $this->extractAttributes($attributes);
                    }
                }
            }

            // Check for unclosed tags
            foreach ($tagStack as $unclosedTag) {
                $structure['structure_errors'][] = "Unclosed tag: <{$unclosedTag}>";
            }
        }

        return $structure;
    }

    /**
     * Extract attributes from tag attribute string.
     *
     * @return array<string, string>
     */
    private function extractAttributes(string $attributeString): array
    {
        $attributes = [];

        if (preg_match_all('/(\w+)=(["\'])([^"\']*)\2/i', $attributeString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attributes[$match[1]] = $match[3];
            }
        }

        return $attributes;
    }

    /**
     * @param array<string, array{value: string, html_structure: array<string, mixed>}> $fileData
     *
     * @return array<string>
     */
    private function findHtmlInconsistencies(array $fileData): array
    {
        if (count($fileData) < 2) {
            return [];
        }

        $inconsistencies = [];

        // Collect all HTML structures from all files for this key
        $allStructures = array_map(static fn ($data) => $data['html_structure'], $fileData);

        // Compare HTML structures between files
        $fileNames = array_keys($allStructures);
        $referenceFile = $fileNames[0];
        $referenceStructure = $allStructures[$referenceFile];

        for ($i = 1, $iMax = count($fileNames); $i < $iMax; ++$i) {
            $currentFile = basename($fileNames[$i]);
            $currentStructure = $allStructures[$fileNames[$i]];

            // Check tag consistency
            $referenceTags = $referenceStructure['tags'] ?? [];
            $currentTags = $currentStructure['tags'] ?? [];

            if ($referenceTags !== $currentTags) {
                $missingTags = array_diff($referenceTags, $currentTags);
                $extraTags = array_diff($currentTags, $referenceTags);

                if (!empty($missingTags)) {
                    $inconsistencies[] = "File '{$currentFile}' is missing HTML tags: ".implode(', ', array_map(fn ($tag) => "<{$tag}>", $missingTags));
                }

                if (!empty($extraTags)) {
                    $inconsistencies[] = "File '{$currentFile}' has extra HTML tags: ".implode(', ', array_map(fn ($tag) => "<{$tag}>", $extraTags));
                }
            }

            // Check structure errors
            $currentErrors = $currentStructure['structure_errors'] ?? [];
            if (!empty($currentErrors)) {
                $inconsistencies[] = "File '{$currentFile}' has HTML structure errors: ".implode('; ', $currentErrors);
            }

            // Check attribute consistency for common tags
            $referenceAttributes = $referenceStructure['attributes'] ?? [];
            $currentAttributes = $currentStructure['attributes'] ?? [];

            foreach ($referenceAttributes as $tagName => $refAttrs) {
                if (isset($currentAttributes[$tagName])) {
                    $currAttrs = $currentAttributes[$tagName];

                    // Check for class attribute differences (common source of inconsistency)
                    if (isset($refAttrs['class']) && isset($currAttrs['class']) && $refAttrs['class'] !== $currAttrs['class']) {
                        $inconsistencies[] = "File '{$currentFile}' has different class attribute for <{$tagName}>: '{$currAttrs['class']}' vs '{$refAttrs['class']}'";
                    }
                }
            }
        }

        return $inconsistencies;
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

        return "- <fg={$color}>{$level}</> {$prefix}HTML tag inconsistency in translation key `{$key}` - {$inconsistencyText}";
    }

    public function distributeIssuesForDisplay(FileSet $fileSet): array
    {
        $distribution = [];

        foreach ($this->issues as $issue) {
            $details = $issue->getDetails();
            $files = $details['files'] ?? [];

            foreach ($files as $filePath => $_) {
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
                $row[] = $this->highlightHtmlTags($value);
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

    private function highlightHtmlTags(string $value): string
    {
        $value = preg_replace('/<(\w+)([^>]*)>/', '<fg=cyan><$1$2></>', $value) ?? $value;

        return preg_replace('/<\/(\w+)>/', '<fg=magenta></$1></>', $value) ?? $value;
    }

    /**
     * @return class-string<ParserInterface>[]
     */
    public function supportsParser(): array
    {
        return [XliffParser::class, YamlParser::class, JsonParser::class, PhpParser::class];
    }

    protected function resetState(): void
    {
        parent::resetState();
        $this->keyData = [];
    }

    public function resultTypeOnValidationFailure(): ResultType
    {
        return ResultType::WARNING;
    }

    public function shouldShowDetailedOutput(): bool
    {
        return true;
    }
}
