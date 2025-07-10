<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Config;

use Symfony\Component\Yaml\Yaml;

class ConfigReader
{
    private const AUTO_DETECTION_FILES = [
        'translation-validator.php',
        'translation-validator.json',
        'translation-validator.yaml',
        'translation-validator.yml',
    ];

    /**
     * @throws \JsonException
     */
    public function read(string $configPath): TranslationValidatorConfig
    {
        if (!file_exists($configPath)) {
            throw new \InvalidArgumentException("Configuration file not found: {$configPath}");
        }

        if (!is_readable($configPath)) {
            throw new \RuntimeException("Configuration file is not readable: {$configPath}");
        }

        $extension = pathinfo($configPath, PATHINFO_EXTENSION);

        return match ($extension) {
            'php' => $this->readPhpConfig($configPath),
            'json' => $this->readJsonConfig($configPath),
            'yaml', 'yml' => $this->readYamlConfig($configPath),
            default => throw new \InvalidArgumentException("Unsupported configuration file format: {$extension}"),
        };
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

    private function readPhpConfig(string $configPath): TranslationValidatorConfig
    {
        $config = require $configPath;

        if (!$config instanceof TranslationValidatorConfig) {
            throw new \RuntimeException('PHP configuration file must return an instance of TranslationValidatorConfig');
        }

        return $config;
    }

    /**
     * @throws \JsonException
     */
    private function readJsonConfig(string $configPath): TranslationValidatorConfig
    {
        $content = file_get_contents($configPath);
        if (false === $content) {
            throw new \RuntimeException("Failed to read configuration file: {$configPath}");
        }

        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new \RuntimeException("Invalid JSON configuration file: {$configPath}");
        }

        return $this->createConfigFromArray($data);
    }

    /**
     * @throws \JsonException
     */
    private function readYamlConfig(string $configPath): TranslationValidatorConfig
    {
        $data = Yaml::parseFile($configPath);
        if (!is_array($data)) {
            throw new \RuntimeException("Invalid YAML configuration file: {$configPath}");
        }

        return $this->createConfigFromArray($data);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws \JsonException
     */
    private function createConfigFromArray(array $data): TranslationValidatorConfig
    {
        // Validate against JSON schema if available
        $schemaValidator = new SchemaValidator();
        if ($schemaValidator->isAvailable()) {
            $schemaValidator->validate($data);
        }

        $config = new TranslationValidatorConfig();

        if (isset($data['paths'])) {
            if (!is_array($data['paths'])) {
                throw new \RuntimeException("Configuration 'paths' must be an array");
            }
            $config->setPaths($data['paths']);
        }

        if (isset($data['validators'])) {
            if (!is_array($data['validators'])) {
                throw new \RuntimeException("Configuration 'validators' must be an array");
            }
            $config->setValidators($data['validators']);
        }

        if (isset($data['file-detectors'])) {
            if (!is_array($data['file-detectors'])) {
                throw new \RuntimeException("Configuration 'file-detectors' must be an array");
            }
            $config->setFileDetectors($data['file-detectors']);
        }

        if (isset($data['parsers'])) {
            if (!is_array($data['parsers'])) {
                throw new \RuntimeException("Configuration 'parsers' must be an array");
            }
            $config->setParsers($data['parsers']);
        }

        if (isset($data['only'])) {
            if (!is_array($data['only'])) {
                throw new \RuntimeException("Configuration 'only' must be an array");
            }
            $config->setOnly($data['only']);
        }

        if (isset($data['skip'])) {
            if (!is_array($data['skip'])) {
                throw new \RuntimeException("Configuration 'skip' must be an array");
            }
            $config->setSkip($data['skip']);
        }

        if (isset($data['exclude'])) {
            if (!is_array($data['exclude'])) {
                throw new \RuntimeException("Configuration 'exclude' must be an array");
            }
            $config->setExclude($data['exclude']);
        }

        if (isset($data['strict'])) {
            if (!is_bool($data['strict'])) {
                throw new \RuntimeException("Configuration 'strict' must be a boolean");
            }
            $config->setStrict($data['strict']);
        }

        if (isset($data['dry-run'])) {
            if (!is_bool($data['dry-run'])) {
                throw new \RuntimeException("Configuration 'dry-run' must be a boolean");
            }
            $config->setDryRun($data['dry-run']);
        }

        if (isset($data['format'])) {
            if (!is_string($data['format'])) {
                throw new \RuntimeException("Configuration 'format' must be a string");
            }
            $this->validateFormat($data['format']);
            $config->setFormat($data['format']);
        }

        if (isset($data['verbose'])) {
            if (!is_bool($data['verbose'])) {
                throw new \RuntimeException("Configuration 'verbose' must be a boolean");
            }
            $config->setVerbose($data['verbose']);
        }

        return $config;
    }

    private function validateFormat(string $format): void
    {
        $allowedFormats = ['cli', 'json', 'yaml', 'php'];
        if (!in_array($format, $allowedFormats, true)) {
            throw new \RuntimeException("Invalid format '{$format}'. Allowed formats: ".implode(', ', $allowedFormats));
        }
    }

    private function resolveConfigPath(string $configPath, string $basePath): string
    {
        if (str_starts_with($configPath, '/') || preg_match('/^[a-zA-Z]:[\\/]/', $configPath)) {
            return $configPath;
        }

        return $basePath.DIRECTORY_SEPARATOR.$configPath;
    }
}
