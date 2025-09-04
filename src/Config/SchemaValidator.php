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
use RuntimeException;

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @package ComposerTranslationValidator
 */

class SchemaValidator
{
    private const SCHEMA_PATH = __DIR__.'/../../schema/translation-validator.schema.json';

    /**
     * @param array<string, mixed> $data
     *
     * @throws JsonException
     */
    public function validate(array $data): void
    {
        if (!class_exists(\JsonSchema\Validator::class)) {
            return;
        }

        $schema = $this->loadSchema();
        $validator = new \JsonSchema\Validator();

        $dataObject = json_decode(json_encode($data, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
        $validator->validate($dataObject, $schema);

        if (!$validator->isValid()) {
            $errors = [];
            foreach ($validator->getErrors() as $error) {
                $errors[] = sprintf('[%s] %s', $error['property'], $error['message']);
            }

            throw new RuntimeException('Configuration validation failed:'.PHP_EOL.implode(PHP_EOL, $errors));
        }
    }

    /**
     * @throws JsonException
     */
    private function loadSchema(): object
    {
        if (!file_exists(self::SCHEMA_PATH) || !is_readable(self::SCHEMA_PATH) || !is_file(self::SCHEMA_PATH)) {
            throw new RuntimeException('JSON Schema file not found: '.self::SCHEMA_PATH);
        }

        $schemaContent = file_get_contents(self::SCHEMA_PATH);
        if (false === $schemaContent) {
            throw new RuntimeException('Failed to read JSON Schema file: '.self::SCHEMA_PATH);
        }

        $schema = json_decode($schemaContent, false, 512, JSON_THROW_ON_ERROR);
        if (null === $schema) {
            throw new RuntimeException('Invalid JSON Schema file: '.self::SCHEMA_PATH);
        }

        return $schema;
    }

    public function isAvailable(): bool
    {
        return class_exists(\JsonSchema\Validator::class);
    }
}
