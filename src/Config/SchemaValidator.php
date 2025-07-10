<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Config;

class SchemaValidator
{
    private const SCHEMA_PATH = __DIR__.'/../../schema/translation-validator.schema.json';

    /**
     * @param array<string, mixed> $data
     *
     * @throws \JsonException
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

            throw new \RuntimeException('Configuration validation failed:'.PHP_EOL.implode(PHP_EOL, $errors));
        }
    }

    /**
     * @throws \JsonException
     */
    private function loadSchema(): object
    {
        if (!file_exists(self::SCHEMA_PATH)) {
            throw new \RuntimeException('JSON Schema file not found: '.self::SCHEMA_PATH);
        }

        $schemaContent = file_get_contents(self::SCHEMA_PATH);
        if (false === $schemaContent) {
            throw new \RuntimeException('Failed to read JSON Schema file: '.self::SCHEMA_PATH);
        }

        $schema = json_decode($schemaContent, false, 512, JSON_THROW_ON_ERROR);
        if (null === $schema) {
            throw new \RuntimeException('Invalid JSON Schema file: '.self::SCHEMA_PATH);
        }

        return $schema;
    }

    public function isAvailable(): bool
    {
        return class_exists(\JsonSchema\Validator::class);
    }
}
