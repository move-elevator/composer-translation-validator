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

namespace MoveElevator\ComposerTranslationValidator\FileDetector;

/**
 * SuffixFileDetector.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class SuffixFileDetector implements DetectorInterface
{
    /**
     * @param array<int, string> $files
     *
     * @return array<string, array<int, string>>
     */
    public function mapTranslationSet(array $files): array
    {
        $groups = [];

        foreach ($files as $file) {
            $basename = basename($file);
            if (preg_match('/^([^.]+)\.[a-z]{2}([-_][A-Z]{2})?(\.ya?ml|\.xlf|\.json|\.php)?$/i', $basename, $matches)) {
                $key = $matches[1];
                $groups[$key][] = $file;
            }
        }

        return $groups;
    }
}
