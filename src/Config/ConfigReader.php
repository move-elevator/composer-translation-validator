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

use JsonException;

class ConfigReader
{
    private const AUTO_DETECTION_FILES = [
        'translation-validator.php',
        'translation-validator.json',
        'translation-validator.yaml',
        'translation-validator.yml',
    ];

    public function __construct(
        private readonly ConfigFileReader $fileReader = new ConfigFileReader(),
    ) {}

    /**
     * @throws JsonException
     */
    public function read(string $configPath): TranslationValidatorConfig
    {
        return $this->fileReader->readAsConfig($configPath);
    }

    public function autoDetect(?string $workingDirectory = null): ?TranslationValidatorConfig
    {
        $workingDirectory ??= getcwd();

        if (!$workingDirectory) {
            return null;
        }

        foreach (self::AUTO_DETECTION_FILES as $filename) {
            $configPath = $workingDirectory.DIRECTORY_SEPARATOR.$filename;
            if (file_exists($configPath)) {
                return $this->read($configPath);
            }
        }

        return null;
    }

    /**
     * @throws JsonException
     */
    public function readFromComposerJson(string $composerJsonPath): ?TranslationValidatorConfig
    {
        if (!file_exists($composerJsonPath)) {
            return null;
        }

        $content = file_get_contents($composerJsonPath);
        if (false === $content) {
            return null;
        }

        $composerData = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($composerData)) {
            return null;
        }

        $configFilePath = $composerData['extra']['translation-validator']['config-file'] ?? null;
        if (!$configFilePath) {
            return null;
        }

        $composerDir = dirname($composerJsonPath);
        $absoluteConfigPath = $this->resolveConfigPath($configFilePath, $composerDir);

        return $this->read($absoluteConfigPath);
    }

    private function resolveConfigPath(string $configPath, string $basePath): string
    {
        if (str_starts_with($configPath, '/') || preg_match('/^[a-zA-Z]:[\\/]/', $configPath)) {
            return $configPath;
        }

        return $basePath.DIRECTORY_SEPARATOR.$configPath;
    }
}
