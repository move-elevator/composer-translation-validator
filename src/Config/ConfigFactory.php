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
    }
}
