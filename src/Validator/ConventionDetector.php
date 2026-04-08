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

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\Enum\KeyNamingConvention;

use function count;
use function in_array;

/**
 * ConventionDetector.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class ConventionDetector
{
    /**
     * Detect which conventions a key matches.
     *
     * @return array<string>
     */
    public function detectKeyConventions(string $key): array
    {
        // For keys with dots, we need to handle dot.notation specially
        if (str_contains($key, '.')) {
            $matchingConventions = [];

            // First, check if the entire key matches dot.notation
            if (KeyNamingConvention::DOT_NOTATION->matches($key)) {
                $matchingConventions[] = KeyNamingConvention::DOT_NOTATION->value;
            }

            // Then check if all segments follow a consistent non-dot convention
            $segments = explode('.', $key);
            $consistentConventions = null;

            // Check which conventions ALL segments support (excluding dot.notation)
            foreach ($segments as $segment) {
                $segmentMatches = $this->detectSegmentConventions($segment);
                // Remove dot.notation from segment matches as it doesn't apply to individual segments
                $segmentMatches = array_filter($segmentMatches, fn ($conv) => $conv !== KeyNamingConvention::DOT_NOTATION->value);

                if (null === $consistentConventions) {
                    // First segment - initialize with its conventions
                    $consistentConventions = $segmentMatches;
                } else {
                    // Subsequent segments - keep only conventions that ALL segments support
                    $consistentConventions = array_intersect($consistentConventions, $segmentMatches);
                }
            }

            // Add segment-based conventions to the result
            if (!empty($consistentConventions) && !in_array('unknown', $consistentConventions, true)) {
                $matchingConventions = array_merge($matchingConventions, array_values($consistentConventions));
            }

            // If no convention matches, it's mixed
            if (empty($matchingConventions)) {
                return ['mixed_conventions'];
            }

            return array_unique($matchingConventions);
        } else {
            // No dots, check regular conventions
            return $this->detectSegmentConventions($key);
        }
    }

    /**
     * Detect conventions for a single segment (without dots).
     *
     * @return array<string>
     */
    public function detectSegmentConventions(string $segment): array
    {
        $matchingConventions = [];

        foreach (KeyNamingConvention::cases() as $convention) {
            if ($convention->matches($segment)) {
                $matchingConventions[] = $convention->value;
            }
        }

        // If no convention matches, classify as 'unknown'
        if (empty($matchingConventions)) {
            $matchingConventions[] = 'unknown';
        }

        return $matchingConventions;
    }

    /**
     * Analyze keys for consistency when no convention is configured.
     *
     * Returns raw data arrays (not Issue objects) so the caller can create Issues.
     *
     * @param array<string> $keys
     *
     * @return array<array<string, mixed>>
     */
    public function analyzeKeyConsistency(array $keys, string $fileName): array
    {
        if (empty($keys)) {
            return [];
        }

        $conventionCounts = [];
        $keyConventions = [];

        // Analyze each key to determine which conventions it matches
        foreach ($keys as $key) {
            $matchingConventions = $this->detectKeyConventions($key);
            $keyConventions[$key] = $matchingConventions;

            foreach ($matchingConventions as $convention) {
                $conventionCounts[$convention] = ($conventionCounts[$convention] ?? 0) + 1;
            }
        }

        // If all keys follow the same convention(s), no issues
        if (count($conventionCounts) <= 1) {
            return [];
        }

        // Find the most common convention
        $dominantConvention = array_key_first($conventionCounts);
        $maxCount = $conventionCounts[$dominantConvention];

        foreach ($conventionCounts as $convention => $count) {
            if ($count > $maxCount) {
                $dominantConvention = $convention;
                $maxCount = $count;
            }
        }

        $issues = [];
        $conventionNames = array_keys($conventionCounts);

        // Report inconsistencies
        foreach ($keys as $key) {
            $keyMatches = $keyConventions[$key];

            // If key doesn't match the dominant convention, it's an issue
            if (!in_array($dominantConvention, $keyMatches, true)) {
                $issues[] = [
                    'key' => $key,
                    'file' => $fileName,
                    'detected_conventions' => $keyMatches,
                    'dominant_convention' => $dominantConvention,
                    'all_conventions_found' => $conventionNames,
                    'inconsistency_type' => 'mixed_conventions',
                ];
            }
        }

        return $issues;
    }
}
