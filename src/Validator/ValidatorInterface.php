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
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use Symfony\Component\Console\Output\OutputInterface;

interface ValidatorInterface
{
    /**
     * @return array<string, mixed>
     */
    public function processFile(ParserInterface $file): array;

    /**
     * @param array<string>                 $files
     * @param class-string<ParserInterface> $parserClass
     *
     * @return array<string, mixed>
     */
    public function validate(array $files, string $parserClass): array;

    /**
     * @return class-string<ParserInterface>[]
     */
    public function supportsParser(): array;

    public function resultTypeOnValidationFailure(): ResultType;

    public function hasIssues(): bool;

    /**
     * @return array<Issue>
     */
    public function getIssues(): array;

    public function addIssue(Issue $issue): void;

    /**
     * Format an issue for CLI display.
     */
    public function formatIssueMessage(Issue $issue, string $prefix = ''): string;

    /**
     * Distribute issues across files for grouped display.
     *
     * @return array<string, array<Issue>> Array with file paths as keys and issues as values
     */
    public function distributeIssuesForDisplay(FileSet $fileSet): array;

    /**
     * Check if this validator should show detailed output in verbose mode.
     */
    public function shouldShowDetailedOutput(): bool;

    /**
     * Render detailed output for this validator in verbose mode.
     *
     * @param array<Issue> $issues
     */
    public function renderDetailedOutput(OutputInterface $output, array $issues): void;

    /**
     * Get short name of the validator (class name without namespace).
     */
    public function getShortName(): string;
}
