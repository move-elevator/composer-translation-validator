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
use RuntimeException;

use function sprintf;

/**
 * SchemaValidator.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
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

        $dataObject = json_decode(json_encode($data, \JSON_THROW_ON_ERROR), false, 512, \JSON_THROW_ON_ERROR);
        $validator->validate($dataObject, $schema);

        if (!$validator->isValid()) {
            $errors = [];
            foreach ($validator->getErrors() as $error) {
                $errors[] = sprintf('[%s] %s', $error['property'], $error['message']);
            }

            throw new RuntimeException('Configuration validation failed:'.\PHP_EOL.implode(\PHP_EOL, $errors));
        }
    }

    public function isAvailable(): bool
    {
        return class_exists(\JsonSchema\Validator::class);
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

        $schema = json_decode($schemaContent, false, 512, \JSON_THROW_ON_ERROR);
        if (null === $schema) {
            throw new RuntimeException('Invalid JSON Schema file: '.self::SCHEMA_PATH);
        }

        return $schema;
    }
}
