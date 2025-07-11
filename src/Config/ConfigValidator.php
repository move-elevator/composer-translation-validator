<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Config;

class ConfigValidator
{
    /**
     * @param array<string, mixed> $data
     */
    public function validate(array $data): void
    {
        if (isset($data['paths']) && !is_array($data['paths'])) {
            throw new \RuntimeException("Configuration 'paths' must be an array");
        }

        if (isset($data['validators']) && !is_array($data['validators'])) {
            throw new \RuntimeException("Configuration 'validators' must be an array");
        }

        if (isset($data['file-detectors']) && !is_array($data['file-detectors'])) {
            throw new \RuntimeException("Configuration 'file-detectors' must be an array");
        }

        if (isset($data['parsers']) && !is_array($data['parsers'])) {
            throw new \RuntimeException("Configuration 'parsers' must be an array");
        }

        if (isset($data['only']) && !is_array($data['only'])) {
            throw new \RuntimeException("Configuration 'only' must be an array");
        }

        if (isset($data['skip']) && !is_array($data['skip'])) {
            throw new \RuntimeException("Configuration 'skip' must be an array");
        }

        if (isset($data['exclude']) && !is_array($data['exclude'])) {
            throw new \RuntimeException("Configuration 'exclude' must be an array");
        }

        if (isset($data['strict']) && !is_bool($data['strict'])) {
            throw new \RuntimeException("Configuration 'strict' must be a boolean");
        }

        if (isset($data['dry-run']) && !is_bool($data['dry-run'])) {
            throw new \RuntimeException("Configuration 'dry-run' must be a boolean");
        }

        if (isset($data['format'])) {
            if (!is_string($data['format'])) {
                throw new \RuntimeException("Configuration 'format' must be a string");
            }
            $this->validateFormat($data['format']);
        }

        if (isset($data['verbose']) && !is_bool($data['verbose'])) {
            throw new \RuntimeException("Configuration 'verbose' must be a boolean");
        }
    }

    private function validateFormat(string $format): void
    {
        $allowedFormats = ['cli', 'json', 'yaml', 'php'];
        if (!in_array($format, $allowedFormats, true)) {
            throw new \RuntimeException("Invalid format '{$format}'. Allowed formats: ".implode(', ', $allowedFormats));
        }
    }
}
