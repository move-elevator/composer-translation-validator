<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025-2026 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Validator\Trait;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Result\Issue;

/**
 * DistributesIssuesForDisplayTrait.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
trait DistributesIssuesForDisplayTrait
{
    /**
     * @return array<string, array<Issue>>
     */
    public function distributeIssuesForDisplay(FileSet $fileSet): array
    {
        $distribution = [];

        foreach ($this->issues as $issue) {
            $filePaths = $this->extractFilePathsFromIssue($issue);

            foreach ($filePaths as $filePath) {
                if (!empty($filePath)) {
                    $fileSpecificIssue = new Issue(
                        $filePath,
                        $issue->getDetails(),
                        $issue->getParser(),
                        $issue->getValidatorType(),
                    );

                    $distribution[$filePath] ??= [];
                    $distribution[$filePath][] = $fileSpecificIssue;
                }
            }
        }

        return $distribution;
    }

    /**
     * Extract file paths from an issue's details.
     *
     * @return array<string>
     */
    abstract protected function extractFilePathsFromIssue(Issue $issue): array;
}
