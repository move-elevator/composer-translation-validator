<?php

declare(strict_types=1);

/*
 * This file is part of the Composer plugin "composer-translation-validator".
 *
 * Copyright (C) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace MoveElevator\ComposerTranslationValidator\Result;

use JsonException;

/**
 * ValidationResultJsonRenderer.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
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
            json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
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
