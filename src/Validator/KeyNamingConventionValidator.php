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

namespace MoveElevator\ComposerTranslationValidator\Validator;

use InvalidArgumentException;
use MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig;
use MoveElevator\ComposerTranslationValidator\Enum\KeyNamingConvention;
use MoveElevator\ComposerTranslationValidator\Parser\JsonParser;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\PhpParser;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use MoveElevator\ComposerTranslationValidator\Result\Issue;

class KeyNamingConventionValidator extends AbstractValidator implements ValidatorInterface
{
    private ?KeyNamingConvention $convention = null;
    private ?string $customPattern = null;
    private ?TranslationValidatorConfig $config = null;

    public function setConfig(?TranslationValidatorConfig $config): void
    {
        $this->config = $config;
        $this->loadConventionFromConfig();
    }

    public function processFile(ParserInterface $file): array
    {
        $keys = $file->extractKeys();

        if (null === $keys) {
            $this->logger?->error(
                'The source file '.$file->getFileName().' is not valid.',
            );

            return [];
        }

        $issues = [];

        // If no convention is configured, analyze keys for inconsistencies
        if (null === $this->convention && null === $this->customPattern) {
            $issueData = $this->analyzeKeyConsistency($keys, $file->getFileName());
        } else {
            // Use configured convention
            $issueData = [];
            foreach ($keys as $key) {
                if (!$this->validateKeyFormat($key)) {
                    $issueData[] = [
                        'key' => $key,
                        'file' => $file->getFileName(),
                        'expected_convention' => $this->convention,
                        'pattern' => $this->getActivePattern(),
                        'suggestion' => $this->suggestCorrection($key),
                    ];
                }
            }
        }

        return $issueData;
    }

    private function loadConventionFromConfig(): void
    {
        if (null === $this->config) {
            return;
        }

        $validatorSettings = $this->config->getValidatorSettings('KeyNamingConventionValidator');

        if (empty($validatorSettings)) {
            return;
        }

        // Load convention from config
        if (isset($validatorSettings['convention']) && is_string($validatorSettings['convention'])) {
            try {
                $this->setConvention($validatorSettings['convention']);
            } catch (InvalidArgumentException $e) {
                $this->logger?->warning(
                    'Invalid convention in config: '.$validatorSettings['convention'].'. '.$e->getMessage(),
                );
            }
        }

        // Load custom pattern from config (overrides convention)
        if (isset($validatorSettings['custom_pattern']) && is_string($validatorSettings['custom_pattern'])) {
            try {
                $this->setCustomPattern($validatorSettings['custom_pattern']);
            } catch (InvalidArgumentException $e) {
                $this->logger?->warning(
                    'Invalid custom pattern in config: '.$validatorSettings['custom_pattern'].'. '.$e->getMessage(),
                );
            }
        }
    }

    public function setConvention(string $convention): void
    {
        $this->convention = KeyNamingConvention::fromString($convention);
    }

    public function setCustomPattern(string $pattern): void
    {
        $result = @preg_match($pattern, '');
        if (false === $result) {
            throw new InvalidArgumentException('Invalid regex pattern provided');
        }

        $this->customPattern = $pattern;
        $this->convention = null; // Custom pattern overrides convention
    }

    private function validateKeyFormat(string $key): bool
    {
        if (null === $this->convention && null === $this->customPattern) {
            return true; // No validation if no pattern is set
        }

        // If custom pattern is set, use it directly
        if (null !== $this->customPattern) {
            return (bool) preg_match($this->customPattern, $key);
        }

        // For base conventions, validate each segment separately if key contains dots
        if (str_contains($key, '.')) {
            $segments = explode('.', $key);
            foreach ($segments as $segment) {
                if (!$this->validateSegment($segment)) {
                    return false;
                }
            }

            return true;
        }

        // Single segment, validate directly
        return $this->validateSegment($key);
    }

    private function validateSegment(string $segment): bool
    {
        if (null === $this->convention) {
            return true;
        }

        return $this->convention->matches($segment);
    }

    private function getActivePattern(): ?string
    {
        if (null !== $this->customPattern) {
            return $this->customPattern;
        }

        return $this->convention?->getPattern();
    }

    private function suggestCorrection(string $key): string
    {
        if (null === $this->convention) {
            return $key;
        }

        if (str_contains($key, '.')) {
            return $this->convertDotSeparatedKey($key);
        }

        return match ($this->convention) {
            KeyNamingConvention::SNAKE_CASE => $this->toSnakeCase($key),
            KeyNamingConvention::CAMEL_CASE => $this->toCamelCase($key),
            KeyNamingConvention::KEBAB_CASE => $this->toKebabCase($key),
            KeyNamingConvention::PASCAL_CASE => $this->toPascalCase($key),
            KeyNamingConvention::DOT_NOTATION => $this->toDotNotation($key),
        };
    }

    private function toSnakeCase(string $key): string
    {
        // Convert camelCase/PascalCase to snake_case
        $result = preg_replace('/([a-z])([A-Z])/', '$1_$2', $key);
        // Convert kebab-case and dot.notation to snake_case
        $result = str_replace(['-', '.'], '_', $result ?? $key);

        // Convert to lowercase
        return strtolower($result);
    }

    private function toCamelCase(string $key): string
    {
        // Handle camelCase/PascalCase first
        if (preg_match('/[A-Z]/', $key)) {
            // Convert PascalCase to camelCase
            return lcfirst($key);
        }

        // Convert snake_case, kebab-case, and dot.notation to camelCase
        $parts = preg_split('/[_\-.]+/', $key);
        if (false === $parts) {
            return $key;
        }

        $result = strtolower($parts[0] ?? '');
        for ($i = 1, $iMax = count($parts); $i < $iMax; ++$i) {
            $result .= ucfirst(strtolower($parts[$i]));
        }

        return $result;
    }

    private function toKebabCase(string $key): string
    {
        // Convert camelCase/PascalCase to kebab-case
        $result = preg_replace('/([a-z])([A-Z])/', '$1-$2', $key);
        // Convert snake_case and dot.notation to kebab-case
        $result = str_replace(['_', '.'], '-', $result ?? $key);

        return strtolower($result);
    }

    private function toPascalCase(string $key): string
    {
        // Handle camelCase/PascalCase first
        if (preg_match('/[A-Z]/', $key)) {
            // Already in PascalCase or camelCase, just ensure first letter is uppercase
            return ucfirst($key);
        }

        // Convert snake_case, kebab-case, and dot.notation to PascalCase
        $parts = preg_split('/[_\-.]+/', $key);
        if (false === $parts) {
            return ucfirst($key);
        }

        return implode('', array_map('ucfirst', array_map('strtolower', $parts)));
    }

    private function toDotNotation(string $key): string
    {
        // Convert camelCase/PascalCase to dot.notation
        $result = preg_replace('/([a-z])([A-Z])/', '$1.$2', $key);
        // Convert snake_case and kebab-case to dot.notation
        $result = str_replace(['_', '-'], '.', $result ?? $key);

        return strtolower($result);
    }

    private function convertDotSeparatedKey(string $key): string
    {
        $segments = explode('.', $key);
        $convertedSegments = [];

        foreach ($segments as $segment) {
            $convertedSegments[] = match ($this->convention) {
                KeyNamingConvention::SNAKE_CASE => $this->toSnakeCase($segment),
                KeyNamingConvention::CAMEL_CASE => $this->toCamelCase($segment),
                KeyNamingConvention::KEBAB_CASE => $this->toKebabCase($segment),
                KeyNamingConvention::PASCAL_CASE => $this->toPascalCase($segment),
                KeyNamingConvention::DOT_NOTATION => $this->toDotNotation($segment),
                null => $segment,
            };
        }

        return implode('.', $convertedSegments);
    }

    public function formatIssueMessage(Issue $issue, string $prefix = ''): string
    {
        $details = $issue->getDetails();
        $resultType = $this->resultTypeOnValidationFailure();

        $level = $resultType->toString();
        $color = $resultType->toColorString();

        $key = $details['key'] ?? 'unknown';

        // Handle different issue types
        if (isset($details['inconsistency_type']) && 'mixed_conventions' === $details['inconsistency_type']) {
            $detectedConventions = $details['detected_conventions'] ?? [];
            $dominantConvention = $details['dominant_convention'] ?? 'unknown';
            $allConventions = $details['all_conventions_found'] ?? [];

            $detectedStr = implode(', ', $detectedConventions);
            $allStr = implode(', ', $allConventions);

            $message = "inconsistent key naming: `{$key}` follows {$detectedStr} but file uses mixed conventions ({$allStr}). Dominant convention: {$dominantConvention}";
        } else {
            // Legacy behavior for configured conventions
            $convention = $details['expected_convention'] ?? 'custom pattern';
            $suggestion = $details['suggestion'] ?? '';

            $message = "key naming convention violation: `{$key}` does not follow {$convention} convention";
            if (!empty($suggestion) && $suggestion !== $key) {
                $message .= " (suggestion: `{$suggestion}`)";
            }
        }

        return "- <fg={$color}>{$level}</> {$prefix}{$message}";
    }

    /**
     * @return class-string<ParserInterface>[]
     */
    public function supportsParser(): array
    {
        return [XliffParser::class, YamlParser::class, JsonParser::class, PhpParser::class];
    }

    public function resultTypeOnValidationFailure(): ResultType
    {
        return ResultType::WARNING;
    }

    public function shouldShowDetailedOutput(): bool
    {
        return false;
    }

    /**
     * Get available naming conventions.
     *
     * @return array<string, array{pattern: string, description: string}>
     */
    public static function getAvailableConventions(): array
    {
        $conventions = [];
        foreach (KeyNamingConvention::cases() as $convention) {
            $conventions[$convention->value] = [
                'pattern' => $convention->getPattern(),
                'description' => $convention->getDescription(),
            ];
        }

        return $conventions;
    }

    /**
     * Check if validator should run based on configuration.
     */
    public function shouldRun(): bool
    {
        return true; // Always run, even without configuration
    }

    /**
     * Analyze keys for consistency when no convention is configured.
     *
     * @param array<string> $keys
     *
     * @return array<array<string, mixed>>
     */
    private function analyzeKeyConsistency(array $keys, string $fileName): array
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

    /**
     * Detect which conventions a key matches.
     *
     * @return array<string>
     */
    private function detectKeyConventions(string $key): array
    {
        // For keys with dots, analyze segments for consistent convention usage
        if (str_contains($key, '.')) {
            $segments = explode('.', $key);
            $consistentConventions = null;

            // Check which conventions ALL segments support
            foreach ($segments as $segment) {
                $segmentMatches = $this->detectSegmentConventions($segment);

                if (null === $consistentConventions) {
                    // First segment - initialize with its conventions
                    $consistentConventions = $segmentMatches;
                } else {
                    // Subsequent segments - keep only conventions that ALL segments support
                    $consistentConventions = array_intersect($consistentConventions, $segmentMatches);
                }
            }

            // If no convention is consistent across all segments, it's mixed
            if (empty($consistentConventions) || in_array('unknown', $consistentConventions, true)) {
                return ['mixed_conventions'];
            }

            return array_values($consistentConventions);
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
    private function detectSegmentConventions(string $segment): array
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
}
