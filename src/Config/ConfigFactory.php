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

use function is_array;

/**
 * ConfigFactory.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class ConfigFactory
{
    public function __construct(
        private readonly ConfigValidator $validator = new ConfigValidator(),
    ) {}

    /**
     * @param array<string, mixed> $data
     *
     * @throws JsonException
     */
    public function createFromArray(array $data): TranslationValidatorConfig
    {
        $schemaValidator = new SchemaValidator();
        if ($schemaValidator->isAvailable()) {
            $schemaValidator->validate($data);
        }

        $this->validator->validate($data);

        $config = new TranslationValidatorConfig();

        $this->applyConfigurationSettings($config, $data);

        return $config;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applyConfigurationSettings(TranslationValidatorConfig $config, array $data): void
    {
        $setters = [
            'paths' => 'setPaths',
            'validators' => 'setValidators',
            'file-detectors' => 'setFileDetectors',
            'parsers' => 'setParsers',
            'only' => 'setOnly',
            'skip' => 'setSkip',
            'exclude' => 'setExclude',
            'strict' => 'setStrict',
            'dry-run' => 'setDryRun',
            'format' => 'setFormat',
            'verbose' => 'setVerbose',
        ];

        foreach ($setters as $key => $method) {
            if (isset($data[$key])) {
                $config->$method($data[$key]);
            }
        }

        // Handle validator-specific settings
        if (isset($data['validator-settings']) && is_array($data['validator-settings'])) {
            $config->setValidatorSettings($data['validator-settings']);
        }
    }
}
