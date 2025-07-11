<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Result;

class ValidationResultJsonRenderer extends AbstractValidationResultRenderer
{
    /**
     * @throws \JsonException
     */
    public function render(ValidationResult $validationResult): int
    {
        $exitCode = $this->calculateExitCode($validationResult);

        $result = [
            'status' => $exitCode,
            'message' => $this->generateMessage($validationResult),
            'issues' => $this->formatIssuesForJson($validationResult),
            'statistics' => $this->formatStatisticsForOutput($validationResult),
        ];

        $this->output->writeln(
            json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return $exitCode;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function formatIssuesForJson(ValidationResult $validationResult): array
    {
        $groupedByFile = $this->groupIssuesByFile($validationResult);
        $jsonFormattedIssues = [];

        foreach ($groupedByFile as $filePath => $validatorGroups) {
            $jsonFormattedIssues[$filePath] = [];

            foreach ($validatorGroups as $validatorName => $validatorData) {
                $jsonFormattedIssues[$filePath][$validatorName] = [
                    'type' => $validatorData['type'],
                    'issues' => [],
                ];

                foreach ($validatorData['issues'] as $issue) {
                    $jsonFormattedIssues[$filePath][$validatorName]['issues'][] = [
                        'message' => strip_tags((string) $validatorData['validator']->formatIssueMessage($issue)),
                        'details' => $issue->getDetails(),
                    ];
                }
            }
        }

        return $jsonFormattedIssues;
    }
}
