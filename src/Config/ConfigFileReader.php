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

namespace MoveElevator\ComposerTranslationValidator\Config;

use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class ConfigFileReader
{
    public function __construct(private readonly ConfigFactory $factory = new ConfigFactory())
    {
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function readFile(string $configPath): array
    {
        if (!file_exists($configPath)) {
            throw new InvalidArgumentException("Configuration file not found: {$configPath}");
        }

        if (!is_readable($configPath)) {
            throw new RuntimeException("Configuration file is not readable: {$configPath}");
        }

        $extension = pathinfo($configPath, PATHINFO_EXTENSION);

        return match ($extension) {
            'php' => $this->readPhpConfigAsArray($configPath),
            'json' => $this->readJsonConfig($configPath),
            'yaml', 'yml' => $this->readYamlConfig($configPath),
            default => throw new InvalidArgumentException("Unsupported configuration file format: {$extension}"),
        };
    }

    /**
     * @throws JsonException
     */
    public function readAsConfig(string $configPath): TranslationValidatorConfig
    {
        $extension = pathinfo($configPath, PATHINFO_EXTENSION);

        if ('php' === $extension) {
            return $this->readPhpConfig($configPath);
        }

        $data = $this->readFile($configPath);

        return $this->factory->createFromArray($data);
    }

    /**
     * @return array<string, mixed>
     */
    private function readPhpConfigAsArray(string $configPath): array
    {
        return $this->readPhpConfig($configPath)->toArray();
    }

    private function readPhpConfig(string $configPath): TranslationValidatorConfig
    {
        $realPath = realpath($configPath);
        if (false === $realPath) {
            throw new RuntimeException("Invalid configuration file path: {$configPath}");
        }
        $config = require $realPath;

        if (!$config instanceof TranslationValidatorConfig) {
            throw new RuntimeException('PHP configuration file must return an instance of TranslationValidatorConfig');
        }

        return $config;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    private function readJsonConfig(string $configPath): array
    {
        $content = file_get_contents($configPath);
        if (false === $content) {
            throw new RuntimeException("Failed to read configuration file: {$configPath}");
        }

        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException("Invalid JSON configuration file: {$configPath}");
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function readYamlConfig(string $configPath): array
    {
        $data = Yaml::parseFile($configPath);
        if (!is_array($data)) {
            throw new RuntimeException("Invalid YAML configuration file: {$configPath}");
        }

        return $data;
    }
}
