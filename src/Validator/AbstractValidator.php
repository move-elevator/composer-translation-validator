<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Parser\{ParserCache, ParserInterface, ParserRegistry};
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function in_array;
use function is_array;
use function sprintf;

/**
 * AbstractValidator.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
abstract class AbstractValidator
{
    /** @var array<Issue> */
    protected array $issues = [];

    protected string $currentFilePath = '';

    public function __construct(protected ?LoggerInterface $logger = null) {}

    /**
     * @param string[]                           $files
     * @param class-string<ParserInterface>|null $parserClass
     *
     * @return array<string, array<mixed>>
     */
    public function validate(array $files, ?string $parserClass): array
    {
        // Reset state for fresh validation run
        $this->resetState();

        $name = $this->getShortName();
        $this->logger?->debug(
            sprintf(
                '> Checking for <options=bold,underscore>%s</> ...',
                $name,
            ),
        );

        foreach ($files as $filePath) {
            $this->currentFilePath = $filePath;

            $file = ParserCache::get(
                $filePath,
                $parserClass ?: ParserRegistry::resolveParserClass(
                    $filePath,
                    $this->logger,
                ),
            );
            /* @var ParserInterface $file */

            if (!$file instanceof ParserInterface) {
                $this->logger?->debug(
                    sprintf(
                        'The file <fg=cyan>%s</> could not be parsed by the '
                        .'validator <fg=red>%s</>.',
                        $filePath,
                        static::class,
                    ),
                );
                continue;
            }

            if (!in_array($file::class, $this->supportsParser(), true)) {
                $this->logger?->debug(
                    sprintf(
                        'The file <fg=cyan>%s</> is not supported by the validator <fg=red>%s</>.',
                        $file->getFileName(),
                        static::class,
                    ),
                );
                continue;
            }

            $this->logger?->debug(
                '> Checking language file: <fg=gray>'
                .$file->getFileDirectory()
                .'</><fg=cyan>'
                .$file->getFileName()
                .'</> ...',
            );

            $validationResult = $this->processFile($file);
            if (empty($validationResult)) {
                continue;
            }

            // Handle case where processFile returns multiple issues
            if (isset($validationResult[0]) && is_array($validationResult[0])) {
                // Multiple issues - create one Issue object per item
                foreach ($validationResult as $issueData) {
                    $this->addIssue(new Issue(
                        $filePath,
                        $issueData,
                        $file::class,
                        $name,
                    ));
                }
            } else {
                // Single issue data - create one Issue object
                $this->addIssue(new Issue(
                    $filePath,
                    $validationResult,
                    $file::class,
                    $name,
                ));
            }
        }

        $this->postProcess();

        return array_map(fn ($issue) => $issue->toArray(), $this->issues);
    }

    /**
     * @return array<mixed>
     */
    abstract public function processFile(ParserInterface $file): array;

    /**
     * @return class-string<ParserInterface>[]
     */
    abstract public function supportsParser(): array;

    public function postProcess(): void
    {
        // This method can be overridden by subclasses to perform
        // additional processing after validation.
    }

    public function resultTypeOnValidationFailure(): ResultType
    {
        return ResultType::ERROR;
    }

    public function hasIssues(): bool
    {
        return !empty($this->issues);
    }

    /**
     * @return array<Issue>
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

    public function addIssue(Issue $issue): void
    {
        $this->issues[] = $issue;
    }

    public function formatIssueMessage(Issue $issue, string $prefix = ''): string
    {
        $details = $issue->getDetails();
        $resultType = $this->resultTypeOnValidationFailure();

        $level = $resultType->toString();
        $color = $resultType->toColorString();

        $message = $details['message'] ?? 'Validation error';

        return "- <fg=$color>$level</> {$prefix}$message";
    }

    /**
     * @return array<string, array<Issue>>
     */
    public function distributeIssuesForDisplay(FileSet $fileSet): array
    {
        $distribution = [];

        foreach ($this->issues as $issue) {
            $filePath = $issue->getFile();
            if (empty($filePath)) {
                continue;
            }

            // Use the full file path directly since it's now stored in Issue objects
            $distribution[$filePath] ??= [];
            $distribution[$filePath][] = $issue;
        }

        return $distribution;
    }

    public function shouldShowDetailedOutput(): bool
    {
        return false;
    }

    /**
     * @param array<Issue> $issues
     */
    public function renderDetailedOutput(OutputInterface $output, array $issues): void
    {
        // Default implementation: no detailed output
    }

    public function getShortName(): string
    {
        $classPart = strrchr(static::class, '\\');

        return false !== $classPart ? substr($classPart, 1) : static::class;
    }

    /**
     * Reset validator state for fresh validation run.
     * Override in subclasses if they have additional state to reset.
     */
    protected function resetState(): void
    {
        $this->issues = [];
    }
}
