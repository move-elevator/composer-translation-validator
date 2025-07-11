<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Config;

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
    ) {
    }

    /**
     * @throws \JsonException
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
     * @throws \JsonException
     */
    public function readFromComposerJson(string $composerJsonPath): ?TranslationValidatorConfig
    {
        if (!file_exists($composerJsonPath)) {
            return null;
        }

        $composerData = json_decode(file_get_contents($composerJsonPath), true, 512, JSON_THROW_ON_ERROR);
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
