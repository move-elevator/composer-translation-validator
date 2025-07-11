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
