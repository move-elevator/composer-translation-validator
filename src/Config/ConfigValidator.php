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

use RuntimeException;

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @package ComposerTranslationValidator
 */

class ConfigValidator
{
    /**
     * @param array<string, mixed> $data
     */
    public function validate(array $data): void
    {
        if (isset($data['paths']) && !is_array($data['paths'])) {
            throw new RuntimeException("Configuration 'paths' must be an array");
        }

        if (isset($data['validators']) && !is_array($data['validators'])) {
            throw new RuntimeException("Configuration 'validators' must be an array");
        }

        if (isset($data['file-detectors']) && !is_array($data['file-detectors'])) {
            throw new RuntimeException("Configuration 'file-detectors' must be an array");
        }

        if (isset($data['parsers']) && !is_array($data['parsers'])) {
            throw new RuntimeException("Configuration 'parsers' must be an array");
        }

        if (isset($data['only']) && !is_array($data['only'])) {
            throw new RuntimeException("Configuration 'only' must be an array");
        }

        if (isset($data['skip']) && !is_array($data['skip'])) {
            throw new RuntimeException("Configuration 'skip' must be an array");
        }

        if (isset($data['exclude']) && !is_array($data['exclude'])) {
            throw new RuntimeException("Configuration 'exclude' must be an array");
        }

        if (isset($data['strict']) && !is_bool($data['strict'])) {
            throw new RuntimeException("Configuration 'strict' must be a boolean");
        }

        if (isset($data['dry-run']) && !is_bool($data['dry-run'])) {
            throw new RuntimeException("Configuration 'dry-run' must be a boolean");
        }

        if (isset($data['format'])) {
            if (!is_string($data['format'])) {
                throw new RuntimeException("Configuration 'format' must be a string");
            }
            $this->validateFormat($data['format']);
        }

        if (isset($data['verbose']) && !is_bool($data['verbose'])) {
            throw new RuntimeException("Configuration 'verbose' must be a boolean");
        }
    }

    private function validateFormat(string $format): void
    {
        $allowedFormats = ['cli', 'json', 'yaml', 'php'];
        if (!in_array($format, $allowedFormats, true)) {
            throw new RuntimeException("Invalid format '{$format}'. Allowed formats: ".implode(', ', $allowedFormats));
        }
    }
}
