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

namespace MoveElevator\ComposerTranslationValidator\Utility;

use MoveElevator\ComposerTranslationValidator\Enum\LocaleMatch;

use function strtolower;
use function strtoupper;

/**
 * LocaleUtility.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class LocaleUtility
{
    /**
     * Splits a locale into its base language (lowercase) and region (uppercase, or null).
     *
     * @return array{base: string, region: ?string}
     */
    public static function parse(string $locale): array
    {
        $parts = preg_split('/[-_]/', $locale, 2);
        $base = strtolower((string) ($parts[0] ?? ''));
        $region = isset($parts[1]) && '' !== $parts[1]
            ? strtoupper($parts[1])
            : null;

        return ['base' => $base, 'region' => $region];
    }

    /**
     * Compares two locales. A missing region on either side is not treated as a
     * mismatch — only two present-but-differing regions count as RegionMismatch.
     */
    public static function compare(string $a, string $b): LocaleMatch
    {
        $localeA = self::parse($a);
        $localeB = self::parse($b);

        if ($localeA['base'] !== $localeB['base']) {
            return LocaleMatch::BaseMismatch;
        }

        if (null !== $localeA['region']
            && null !== $localeB['region']
            && $localeA['region'] !== $localeB['region']
        ) {
            return LocaleMatch::RegionMismatch;
        }

        return LocaleMatch::Identical;
    }
}
