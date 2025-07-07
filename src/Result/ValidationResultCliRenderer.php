<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Result;

use MoveElevator\ComposerTranslationValidator\Utility\PathUtility;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ValidationResultCliRenderer
{
    private readonly SymfonyStyle $io;

    public function __construct(
        private readonly OutputInterface $output,
        private readonly InputInterface $input,
        private readonly bool $dryRun = false,
        private readonly bool $strict = false,
    ) {
        $this->io = new SymfonyStyle($this->input, $this->output);
    }

    public function render(ValidationResult $validationResult): int
    {
        $this->renderValidatorResults($validationResult);
        $this->renderSummary($validationResult->getOverallResult());

        return $validationResult->getOverallResult()->resolveErrorToCommandExitCode($this->dryRun, $this->strict);
    }

    private function renderValidatorResults(ValidationResult $validationResult): void
    {
        $validatorPairs = $validationResult->getValidatorFileSetPairs();

        if (empty($validatorPairs)) {
            return;
        }

        // Group by validator class for rendering
        $groupedByValidator = [];
        foreach ($validatorPairs as $pair) {
            $validator = $pair['validator'];
            $fileSet = $pair['fileSet'];

            if (!$validator->hasIssues()) {
                continue;
            }

            $validatorClass = $validator::class;
            if (!isset($groupedByValidator[$validatorClass])) {
                $groupedByValidator[$validatorClass] = [
                    'validator' => $validator,
                    'paths' => [],
                ];
            }

            $path = $fileSet->getPath();
            if (!isset($groupedByValidator[$validatorClass]['paths'][$path])) {
                $groupedByValidator[$validatorClass]['paths'][$path] = [];
            }

            $groupedByValidator[$validatorClass]['paths'][$path][] = $validator->getIssues();
        }

        foreach ($groupedByValidator as $data) {
            $this->renderValidatorSectionWithPaths($data['validator'], $data['paths']);
        }
    }

    /**
     * @param array<string, array<array<Issue>>> $pathsWithIssues
     */
    private function renderValidatorSectionWithPaths(ValidatorInterface $validator, array $pathsWithIssues): void
    {
        $validatorClass = $validator::class;
        $this->io->section(sprintf('Validator: <fg=cyan>%s</>', $validatorClass));

        if ($this->output->isVerbose()) {
            $this->io->writeln(sprintf('Explanation: %s', $validator->explain()));
        }

        foreach ($pathsWithIssues as $path => $issueArrays) {
            $this->io->writeln(sprintf('<fg=gray>Folder Path: %s</>', PathUtility::normalizeFolderPath($path)));
            $this->io->newLine();

            // Flatten all issues for this path
            $allIssues = [];
            foreach ($issueArrays as $issueArray) {
                $allIssues = [...$allIssues, ...$issueArray];
            }

            // Convert issues back to legacy format for existing renderIssueSets method
            $legacyFormat = $this->convertToLegacyFormat($allIssues);
            $validator->renderIssueSets($this->input, $this->output, $legacyFormat);

            $this->io->newLine();
            $this->io->newLine();
        }
    }

    /**
     * @param array<Issue> $issues
     *
     * @return array<string, array<int, array<mixed>>>
     */
    private function convertToLegacyFormat(array $issues): array
    {
        $legacy = [];

        foreach ($issues as $issue) {
            $legacy[''][] = $issue->toArray();
        }

        return $legacy;
    }

    private function renderSummary(ResultType $resultType): void
    {
        if ($resultType->notFullySuccessful()) {
            $this->io->newLine();
            $this->io->{$this->dryRun || ResultType::WARNING === $resultType ? 'warning' : 'error'}(
                $this->dryRun
                    ? 'Language validation failed and completed in dry-run mode.'
                    : 'Language validation failed.'
            );
        } else {
            $message = 'Language validation succeeded.';
            $this->output->isVerbose()
                ? $this->io->success($message)
                : $this->output->writeln('<fg=green>'.$message.'</>');
        }
    }
}
