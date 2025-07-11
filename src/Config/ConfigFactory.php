<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Config;

class ConfigFactory
{
    public function __construct(
        private readonly ConfigValidator $validator = new ConfigValidator(),
    ) {
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws \JsonException
     */
    public function createFromArray(array $data): TranslationValidatorConfig
    {
        $schemaValidator = new SchemaValidator();
        if ($schemaValidator->isAvailable()) {
            $schemaValidator->validate($data);
        }

        $this->validator->validate($data);

        $config = new TranslationValidatorConfig();

        if (isset($data['paths'])) {
            $config->setPaths($data['paths']);
        }

        if (isset($data['validators'])) {
            $config->setValidators($data['validators']);
        }

        if (isset($data['file-detectors'])) {
            $config->setFileDetectors($data['file-detectors']);
        }

        if (isset($data['parsers'])) {
            $config->setParsers($data['parsers']);
        }

        if (isset($data['only'])) {
            $config->setOnly($data['only']);
        }

        if (isset($data['skip'])) {
            $config->setSkip($data['skip']);
        }

        if (isset($data['exclude'])) {
            $config->setExclude($data['exclude']);
        }

        if (isset($data['strict'])) {
            $config->setStrict($data['strict']);
        }

        if (isset($data['dry-run'])) {
            $config->setDryRun($data['dry-run']);
        }

        if (isset($data['format'])) {
            $config->setFormat($data['format']);
        }

        if (isset($data['verbose'])) {
            $config->setVerbose($data['verbose']);
        }

        return $config;
    }
}
