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

namespace MoveElevator\ComposerTranslationValidator\Validation\Config;

use MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig;

/**
 * Immutable value object for type-safe validation configuration.
 *
 * Replaces array-based configuration passing with structured, typed
 * configuration for external API consumption. Compatible with PHP 8.1+ readonly properties.
 */
final readonly class ValidationConfiguration
{
    /**
     * @param array<string>                       $paths             Paths to validate
     * @param array<string>                       $onlyValidators    Run only these validators (class names)
     * @param array<string>                       $skipValidators    Skip these validators (class names)
     * @param array<string>                       $excludePatterns   File patterns to exclude
     * @param array<string>                       $fileDetectors     File detector classes to use
     * @param array<string>                       $parsers           Parser classes to use
     * @param array<string, array<string, mixed>> $validatorSettings Settings per validator
     */
    public function __construct(
        public array $paths = [],
        public array $onlyValidators = [],
        public array $skipValidators = [],
        public array $excludePatterns = [],
        public array $fileDetectors = [],
        public array $parsers = [],
        public bool $strict = false,
        public bool $dryRun = false,
        public string $format = 'cli',
        public bool $verbose = false,
        public bool $recursive = false,
        public array $validatorSettings = [],
    ) {}

    /**
     * Create from legacy TranslationValidatorConfig.
     */
    public static function fromLegacyConfig(TranslationValidatorConfig $config): self
    {
        return new self(
            paths: $config->getPaths(),
            onlyValidators: $config->getOnly(),
            skipValidators: $config->getSkip(),
            excludePatterns: $config->getExclude(),
            fileDetectors: $config->getFileDetectors(),
            parsers: $config->getParsers(),
            strict: $config->getStrict(),
            dryRun: $config->getDryRun(),
            format: $config->getFormat(),
            verbose: $config->getVerbose(),
            recursive: false, // Not available in legacy config
            validatorSettings: $config->getAllValidatorSettings(),
        );
    }

    /**
     * Create from array configuration.
     *
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            paths: self::getArrayValue($config, 'paths', []),
            onlyValidators: self::getArrayValue($config, 'only', self::getArrayValue($config, 'onlyValidators', [])),
            skipValidators: self::getArrayValue($config, 'skip', self::getArrayValue($config, 'skipValidators', [])),
            excludePatterns: self::getArrayValue($config, 'exclude', self::getArrayValue($config, 'excludePatterns', [])),
            fileDetectors: self::getArrayValue($config, 'file-detectors', self::getArrayValue($config, 'fileDetectors', [])),
            parsers: self::getArrayValue($config, 'parsers', []),
            strict: (bool) ($config['strict'] ?? false),
            dryRun: (bool) ($config['dry-run'] ?? $config['dryRun'] ?? false),
            format: (string) ($config['format'] ?? 'cli'),
            verbose: (bool) ($config['verbose'] ?? false),
            recursive: (bool) ($config['recursive'] ?? false),
            validatorSettings: self::getArrayValue($config, 'validator-settings', self::getArrayValue($config, 'validatorSettings', [])),
        );
    }

    /**
     * Convert to legacy TranslationValidatorConfig.
     */
    public function toLegacyConfig(): TranslationValidatorConfig
    {
        $config = new TranslationValidatorConfig();

        return $config
            ->setPaths($this->paths)
            ->setOnly($this->onlyValidators)
            ->setSkip($this->skipValidators)
            ->setExclude($this->excludePatterns)
            ->setFileDetectors($this->fileDetectors)
            ->setParsers($this->parsers)
            ->setStrict($this->strict)
            ->setDryRun($this->dryRun)
            ->setFormat($this->format)
            ->setVerbose($this->verbose)
            ->setValidatorSettings($this->validatorSettings);
    }

    /**
     * Create a modified copy with new paths.
     *
     * @param array<string> $paths
     */
    public function withPaths(array $paths): self
    {
        return new self(
            paths: $paths,
            onlyValidators: $this->onlyValidators,
            skipValidators: $this->skipValidators,
            excludePatterns: $this->excludePatterns,
            fileDetectors: $this->fileDetectors,
            parsers: $this->parsers,
            strict: $this->strict,
            dryRun: $this->dryRun,
            format: $this->format,
            verbose: $this->verbose,
            recursive: $this->recursive,
            validatorSettings: $this->validatorSettings,
        );
    }

    /**
     * Create a modified copy with only specific validators.
     *
     * @param array<string> $validators
     */
    public function withOnlyValidators(array $validators): self
    {
        return new self(
            paths: $this->paths,
            onlyValidators: $validators,
            skipValidators: $this->skipValidators,
            excludePatterns: $this->excludePatterns,
            fileDetectors: $this->fileDetectors,
            parsers: $this->parsers,
            strict: $this->strict,
            dryRun: $this->dryRun,
            format: $this->format,
            verbose: $this->verbose,
            recursive: $this->recursive,
            validatorSettings: $this->validatorSettings,
        );
    }

    /**
     * Create a modified copy excluding specific validators.
     *
     * @param array<string> $validators
     */
    public function withSkipValidators(array $validators): self
    {
        return new self(
            paths: $this->paths,
            onlyValidators: $this->onlyValidators,
            skipValidators: $validators,
            excludePatterns: $this->excludePatterns,
            fileDetectors: $this->fileDetectors,
            parsers: $this->parsers,
            strict: $this->strict,
            dryRun: $this->dryRun,
            format: $this->format,
            verbose: $this->verbose,
            recursive: $this->recursive,
            validatorSettings: $this->validatorSettings,
        );
    }

    /**
     * Create a modified copy with strict mode enabled/disabled.
     */
    public function withStrict(bool $strict): self
    {
        return new self(
            paths: $this->paths,
            onlyValidators: $this->onlyValidators,
            skipValidators: $this->skipValidators,
            excludePatterns: $this->excludePatterns,
            fileDetectors: $this->fileDetectors,
            parsers: $this->parsers,
            strict: $strict,
            dryRun: $this->dryRun,
            format: $this->format,
            verbose: $this->verbose,
            recursive: $this->recursive,
            validatorSettings: $this->validatorSettings,
        );
    }

    /**
     * Create a modified copy with recursive mode enabled/disabled.
     */
    public function withRecursive(bool $recursive): self
    {
        return new self(
            paths: $this->paths,
            onlyValidators: $this->onlyValidators,
            skipValidators: $this->skipValidators,
            excludePatterns: $this->excludePatterns,
            fileDetectors: $this->fileDetectors,
            parsers: $this->parsers,
            strict: $this->strict,
            dryRun: $this->dryRun,
            format: $this->format,
            verbose: $this->verbose,
            recursive: $recursive,
            validatorSettings: $this->validatorSettings,
        );
    }

    /**
     * Check if configuration has any validators specified.
     */
    public function hasValidatorsSpecified(): bool
    {
        return !empty($this->onlyValidators);
    }

    /**
     * Check if configuration has any validators to skip.
     */
    public function hasValidatorsToSkip(): bool
    {
        return !empty($this->skipValidators);
    }

    /**
     * Check if configuration has file exclusion patterns.
     */
    public function hasExcludePatterns(): bool
    {
        return !empty($this->excludePatterns);
    }

    /**
     * Check if configuration has custom file detectors.
     */
    public function hasCustomFileDetectors(): bool
    {
        return !empty($this->fileDetectors);
    }

    /**
     * Check if configuration has custom parsers.
     */
    public function hasCustomParsers(): bool
    {
        return !empty($this->parsers);
    }

    /**
     * Check if configuration has validator-specific settings.
     */
    public function hasValidatorSettings(): bool
    {
        return !empty($this->validatorSettings);
    }

    /**
     * Get settings for a specific validator.
     *
     * @return array<string, mixed>
     */
    public function getValidatorSettings(string $validatorClass): array
    {
        return $this->validatorSettings[$validatorClass] ?? [];
    }

    /**
     * Convert to array format for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'paths' => $this->paths,
            'onlyValidators' => $this->onlyValidators,
            'skipValidators' => $this->skipValidators,
            'excludePatterns' => $this->excludePatterns,
            'fileDetectors' => $this->fileDetectors,
            'parsers' => $this->parsers,
            'strict' => $this->strict,
            'dryRun' => $this->dryRun,
            'format' => $this->format,
            'verbose' => $this->verbose,
            'recursive' => $this->recursive,
            'validatorSettings' => $this->validatorSettings,
        ];
    }

    /**
     * Get array value with fallback to empty array.
     *
     * @param array<string, mixed> $array
     * @param array<mixed>         $default
     *
     * @return array<mixed>
     */
    private static function getArrayValue(array $array, string $key, array $default = []): array
    {
        $value = $array[$key] ?? $default;

        return is_array($value) ? $value : $default;
    }
}
