<?php

declare(strict_types=1);

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
    public function formatIssueMessage(Issue $issue, string $prefix = '', bool $isVerbose = false): string;

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
