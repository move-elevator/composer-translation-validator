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
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ValidatorInterface.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
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
