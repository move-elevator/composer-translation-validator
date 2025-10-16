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

namespace MoveElevator\ComposerTranslationValidator\Config;

use JsonException;

use function dirname;
use function is_array;

/**
 * ConfigReader.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
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

    /**
     * @throws JsonException
     */
    public function autoDetect(?string $workingDirectory = null): ?TranslationValidatorConfig
    {
        $workingDirectory ??= getcwd();

        if (!$workingDirectory) {
            return null;
        }

        foreach (self::AUTO_DETECTION_FILES as $filename) {
            $configPath = $workingDirectory.\DIRECTORY_SEPARATOR.$filename;
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

        $composerData = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
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

        return $basePath.\DIRECTORY_SEPARATOR.$configPath;
    }
}
