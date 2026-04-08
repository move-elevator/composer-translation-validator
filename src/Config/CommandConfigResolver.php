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

namespace MoveElevator\ComposerTranslationValidator\Config;

use JsonException;
use Symfony\Component\Console\Input\InputInterface;

/**
 * CommandConfigResolver.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class CommandConfigResolver
{
    public function __construct(
        private readonly ConfigReader $configReader = new ConfigReader(),
    ) {}

    /**
     * Resolve the final configuration from config files and CLI input.
     *
     * Priority: CLI options > config file > composer.json > auto-detect > defaults
     *
     * @throws JsonException
     */
    public function resolve(InputInterface $input, ?string $workingDirectory = null): TranslationValidatorConfig
    {
        $config = $this->loadConfiguration($input, $workingDirectory);

        if ($input->getOption('dry-run')) {
            $config->setDryRun(true);
        }

        if ($input->getOption('strict')) {
            $config->setStrict(true);
        }

        $cliExcludeOption = $input->getOption('exclude');
        if ($cliExcludeOption) {
            $cliExcludePatterns = array_map(trim(...), explode(',', (string) $cliExcludeOption));
            $config->setExclude(array_merge($config->getExclude(), $cliExcludePatterns));
        }

        $cliFormat = $input->getOption('format');
        if ($cliFormat) {
            $config->setFormat((string) $cliFormat);
        }

        return $config;
    }

    /**
     * @throws JsonException
     */
    private function loadConfiguration(InputInterface $input, ?string $workingDirectory): TranslationValidatorConfig
    {
        $configPath = $input->getOption('config');

        if ($configPath) {
            return $this->configReader->read($configPath);
        }

        $workingDirectory ??= (string) getcwd();

        // Try to load from composer.json
        $composerJsonPath = $workingDirectory.'/composer.json';
        $config = $this->configReader->readFromComposerJson($composerJsonPath);
        if ($config) {
            return $config;
        }

        // Try auto-detection
        $config = $this->configReader->autoDetect($workingDirectory);
        if ($config) {
            return $config;
        }

        // Return default configuration
        return new TranslationValidatorConfig();
    }
}
