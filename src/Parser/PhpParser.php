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

namespace MoveElevator\ComposerTranslationValidator\Parser;

use RuntimeException;
use Throwable;

/**
 * PhpParser.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
class PhpParser extends AbstractParser implements ParserInterface
{
    /** @var array<string, mixed> */
    private array $translations = [];

    public function __construct(string $filePath)
    {
        parent::__construct($filePath);

        try {
            $this->loadTranslations();
        } catch (Throwable $e) {
            throw new RuntimeException(sprintf('Failed to parse PHP file "%s": %s', $filePath, $e->getMessage()), 0, $e);
        }
    }

    /**
     * @return array<int, string>|null
     */
    public function extractKeys(): ?array
    {
        if (empty($this->translations)) {
            return [];
        }

        $extract = static function (
            array $data,
            string $prefix = '',
        ) use (&$extract): array {
            $keys = [];
            foreach ($data as $key => $value) {
                $fullKey = '' === $prefix
                    ? $key
                    : $prefix.'.'.$key;
                if (is_array($value)) {
                    $extracted = $extract($value, $fullKey);
                    foreach ($extracted as $k) {
                        $keys[] = $k;
                    }
                } else {
                    $keys[] = $fullKey;
                }
            }

            return $keys;
        };

        return $extract($this->translations);
    }

    public function getContentByKey(string $key): ?string
    {
        // Note: the $attribute parameter is required by ParserInterface
        // but is not used for PHP files, since PHP has no source/target concept.
        $parts = explode('.', $key);
        $value = $this->translations;

        foreach ($parts as $part) {
            if (is_array($value) && array_key_exists($part, $value)) {
                $value = $value[$part];
            } else {
                return null;
            }
        }

        return is_string($value) ? $value : null;
    }

    /**
     * @return array<int, string>
     */
    public static function getSupportedFileExtensions(): array
    {
        return ['php'];
    }

    public function getLanguage(): string
    {
        $fileName = $this->getFileName();

        // Laravel pattern: en/messages.php, de/auth.php
        $directory = basename(dirname($this->filePath));
        if (preg_match('/^[a-z]{2}(?:[-_][A-Z]{2})?$/', $directory)) {
            return $directory;
        }

        // Symfony pattern: messages.en.php, validators.de.php
        if (preg_match('/\.([a-z]{2}(?:[-_][A-Z]{2})?)\.php$/i', $fileName, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function loadTranslations(): void
    {
        // Temporarily disable error reporting to prevent issues with include
        $errorReporting = error_reporting(0);

        try {
            // Use output buffering to catch any output from the included file
            ob_start();
            $result = include $this->filePath;
            ob_end_clean();

            if (!is_array($result)) {
                throw new RuntimeException('PHP translation file must return an array');
            }

            $this->translations = $result;
        } finally {
            // Restore error reporting
            error_reporting($errorReporting);
        }
    }
}
