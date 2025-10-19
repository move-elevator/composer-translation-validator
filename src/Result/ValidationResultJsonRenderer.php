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

namespace MoveElevator\ComposerTranslationValidator\Result;

use JsonException;

/**
 * ValidationResultJsonRenderer.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class ValidationResultJsonRenderer extends AbstractValidationResultRenderer
{
    /**
     * @throws JsonException
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
            json_encode($result, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE),
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
