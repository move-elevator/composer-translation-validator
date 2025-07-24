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

class MismatchValidator extends AbstractValidator implements ValidatorInterface
{
    /** @var array<string, array<string>> */
    /**
     * @var array<string, array<string, string|null>>
     */
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
            $action = 'missing but present in';
        }

        $otherFilesList = !empty($otherFiles) ? implode('`, `', $otherFiles) : 'other files';

        return "- <fg=$color>$level</> {$prefix} the translation key `$key` is $action other translation files (`$otherFilesList`)";
    }

    public function distributeIssuesForDisplay(FileSet $fileSet): array
    {
        $distribution = [];

        foreach ($this->issues as $issue) {
            $details = $issue->getDetails();
            $files = $details['files'] ?? [];

            foreach ($files as $fileInfo) {
                $fileName = $fileInfo['file'] ?? '';
                if (!empty($fileName)) {
                    $basePath = rtrim($fileSet->getPath(), '/');
                    $filePath = $basePath.'/'.$fileName;

                    $fileSpecificIssue = new Issue(
                        $filePath,
                        $details,
                        $issue->getParser(),
                        $issue->getValidatorType(),
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

        $firstIssue = $issues[0];
        $currentFile = basename($firstIssue->getFile());
        $firstDetails = $firstIssue->getDetails();
        $firstFiles = $firstDetails['files'] ?? [];

        $fileOrder = [$currentFile];
        foreach ($firstFiles as $fileInfo) {
            $fileName = $fileInfo['file'] ?? '';
            if ($fileName !== $currentFile && !in_array($fileName, $fileOrder, true)) {
                $fileOrder[] = $fileName;
            }
        }

        $header = ['Translation Key', $currentFile];
        foreach ($fileOrder as $fileName) {
            if ($fileName !== $currentFile) {
                $header[] = $fileName;
            }
        }

        foreach ($allKeys as $key) {
            $row = [$key];
            foreach ($fileOrder as $fileName) {
                $value = $allFilesData[$key][$fileName] ?? null;
                $row[] = $value ?? '';
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

    protected function resetState(): void
    {
        parent::resetState();
        $this->keyArray = [];
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
