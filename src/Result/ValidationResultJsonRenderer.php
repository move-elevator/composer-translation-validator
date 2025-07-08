<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Result;

use Symfony\Component\Console\Output\OutputInterface;

readonly class ValidationResultJsonRenderer implements ValidationResultRendererInterface
{
    public function __construct(
        private OutputInterface $output,
        private bool $dryRun = false,
        private bool $strict = false,
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
     * @return array<class-string, array<string, array<string, array<mixed>>>>
     */
    private function formatIssuesForJson(ValidationResult $validationResult): array
    {
        return $validationResult->toLegacyArray();
    }
}
