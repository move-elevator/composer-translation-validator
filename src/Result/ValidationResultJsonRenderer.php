<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Result;

use Symfony\Component\Console\Output\OutputInterface;

class ValidationResultJsonRenderer implements ValidationResultRendererInterface
{
    public function __construct(
        private readonly OutputInterface $output,
        private readonly bool $dryRun = false,
        private readonly bool $strict = false,
    ) {
    }

    /**
     * @throws \JsonException
     */
    public function render(ValidationResult $validationResult): int
    {
        $exitCode = $validationResult->getOverallResult()->resolveErrorToCommandExitCode($this->dryRun, $this->strict);

        $result = [
            'status' => $exitCode,
            'message' => $this->generateMessage($validationResult),
            'issues' => $this->formatIssuesForJson($validationResult),
        ];

        $this->output->writeln(
            json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return $exitCode;
    }

    private function generateMessage(ValidationResult $validationResult): string
    {
        if (!$validationResult->hasIssues()) {
            return 'Language validation succeeded.';
        }

        if ($this->dryRun) {
            return 'Language validation failed and completed in dry-run mode.';
        }

        return 'Language validation failed.';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function formatIssuesForJson(ValidationResult $validationResult): array
    {
        $validatorPairs = $validationResult->getValidatorFileSetPairs();

        if (empty($validatorPairs)) {
            return [];
        }

        $groupedByFile = [];

        foreach ($validatorPairs as $pair) {
            $validator = $pair['validator'];
            $fileSet = $pair['fileSet'];

            if (!$validator->hasIssues()) {
                continue;
            }

            $distributedIssues = $validator->distributeIssuesForDisplay($fileSet);

            foreach ($distributedIssues as $filePath => $issues) {
                $normalizedPath = $this->normalizePath($filePath);

                if (!isset($groupedByFile[$normalizedPath])) {
                    $groupedByFile[$normalizedPath] = [];
                }

                $validatorName = $validator->getShortName();
                if (!isset($groupedByFile[$normalizedPath][$validatorName])) {
                    $groupedByFile[$normalizedPath][$validatorName] = [
                        'type' => $validator->resultTypeOnValidationFailure()->toString(),
                        'issues' => [],
                    ];
                }

                foreach ($issues as $issue) {
                    $groupedByFile[$normalizedPath][$validatorName]['issues'][] = [
                        'message' => strip_tags($validator->formatIssueMessage($issue)),
                        'details' => $issue->getDetails(),
                    ];
                }
            }
        }

        return $groupedByFile;
    }

    private function normalizePath(string $path): string
    {
        $realPath = realpath($path);
        if (false === $realPath) {
            $normalizedPath = rtrim($path, DIRECTORY_SEPARATOR);
            if (str_starts_with($normalizedPath, './')) {
                $normalizedPath = substr($normalizedPath, 2);
            }

            return $normalizedPath;
        }

        $cwd = realpath(getcwd()).DIRECTORY_SEPARATOR;
        $normalizedPath = rtrim($realPath, DIRECTORY_SEPARATOR);

        if (str_starts_with($normalizedPath.DIRECTORY_SEPARATOR, $cwd)) {
            return substr($normalizedPath, strlen($cwd));
        }

        return $normalizedPath;
    }
}
