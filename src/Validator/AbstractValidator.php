<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\ParserRegistry;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractValidator
{
    /** @var array<Issue> */
    protected array $issues = [];

    public function __construct(protected ?LoggerInterface $logger = null)
    {
    }

    /**
     * @param string[]                           $files
     * @param class-string<ParserInterface>|null $parserClass
     *
     * @return array<string, array<mixed>>
     *
     * @throws \ReflectionException
     */
    public function validate(array $files, ?string $parserClass): array
    {
        // Reset state for fresh validation run
        $this->resetState();

        $name = $this->getShortName();
        $this->logger->debug(
            sprintf(
                '> Checking for <options=bold,underscore>%s</> ...',
                $name
            )
        );

        foreach ($files as $filePath) {
            $file = new ($parserClass ?: ParserRegistry::resolveParserClass($filePath))($filePath);
            /* @var ParserInterface $file */

            if (!in_array($file::class, $this->supportsParser(), true)) {
                $this->logger?->debug(
                    sprintf(
                        'The file <fg=cyan>%s</> is not supported by the validator <fg=red>%s</>.',
                        $file->getFileName(),
                        static::class
                    )
                );
                continue;
            }

            $this->logger->debug('> Checking language file: <fg=gray>'.$file->getFileDirectory().'</><fg=cyan>'.$file->getFileName().'</> ...');

            $validationResult = $this->processFile($file);
            if (!empty($validationResult)) {
                $this->addIssue(new Issue(
                    $file->getFileName(),
                    $validationResult,
                    $file::class,
                    $name
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
        // This method can be overridden by subclasses to perform additional processing after validation.
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

    /**
     * Reset validator state for fresh validation run.
     * Override in subclasses if they have additional state to reset.
     */
    protected function resetState(): void
    {
        $this->issues = [];
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
            $fileName = $issue->getFile();
            if (empty($fileName)) {
                continue;
            }

            // Build full path from fileSet and filename for consistency
            $basePath = rtrim($fileSet->getPath(), '/');
            $filePath = $basePath.'/'.$fileName;

            if (!isset($distribution[$filePath])) {
                $distribution[$filePath] = [];
            }
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
}
