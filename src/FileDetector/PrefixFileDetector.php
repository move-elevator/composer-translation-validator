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

namespace MoveElevator\ComposerTranslationValidator\FileDetector;

/**
 * PrefixFileDetector.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class PrefixFileDetector implements DetectorInterface
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
            if (preg_match('/^([a-z]{2}(?:[-_][A-Z]{2})?)\.(.+)$/i', $basename, $matches)) {
                // Language prefix pattern (e.g., de.messages.xlf -> key: messages.xlf)
                $key = $matches[2];
                $groups[$key][] = $file;
            } elseif (preg_match('/^(locallang|messages|validation|errors|labels|translations?|test)\./', $basename)) {
                // Common translation file patterns (e.g., messages.xlf -> key: messages.xlf)
                $key = $basename;
                $groups[$key][] = $file;
            } elseif (preg_match('/^[^.]+\.(xlf|xliff|json|ya?ml|php)$/i', $basename) && !preg_match('/(config|validator|setting)/', $basename)) {
                // Generic translation files, but exclude config/validator files
                $key = $basename;
                $groups[$key][] = $file;
            }
        }

        return $groups;
    }
}
