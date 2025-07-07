<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface ValidatorInterface
{
    /**
     * @return array<string, mixed>
     */
    public function processFile(ParserInterface $file): array;

    /**
     * @param array<string, mixed> $issueSets
     */
    public function renderIssueSets(InputInterface $input, OutputInterface $output, array $issueSets): void;

    public function explain(): string;

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
}
