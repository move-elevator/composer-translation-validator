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

use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

use function is_array;

/**
 * ConfigFileReader.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class ConfigFileReader
{
    public function __construct(
        private readonly ConfigFactory $factory = new ConfigFactory(),
    ) {}

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

        $extension = pathinfo($configPath, \PATHINFO_EXTENSION);

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
        $extension = pathinfo($configPath, \PATHINFO_EXTENSION);

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

        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
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
