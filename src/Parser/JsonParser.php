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

use JsonException;
use RuntimeException;

class JsonParser extends AbstractParser implements ParserInterface
{
    /** @var array<string, mixed> */
    private array $json = [];

    public function __construct(protected string $filePath)
    {
        parent::__construct($filePath);

        try {
            $content = file_get_contents($filePath);
            if (false === $content) {
                throw new RuntimeException("Failed to read file: {$filePath}");
            }

            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($decoded) || array_is_list($decoded)) {
                throw new RuntimeException("JSON file does not contain an object: {$filePath}");
            }

            $this->json = $decoded;
        } catch (JsonException $e) {
            throw new RuntimeException(sprintf('Failed to parse JSON file "%s": %s', $filePath, $e->getMessage()), 0, $e);
        }
    }

    /**
     * @return array<int, string>|null
     */
    public function extractKeys(): ?array
    {
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

        return $extract($this->json);
    }

    public function getContentByKey(string $key): ?string
    {
        // Note: the $attribute parameter is required by ParserInterface
        // but is not used for JSON, since JSON has no source/target concept.
        $parts = explode('.', $key);
        $value = $this->json;

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
        return ['json'];
    }

    public function getLanguage(): string
    {
        if (preg_match(
            '/\.([a-z]{2})(?:[-_][A-Z]{2})?\./',
            $this->getFileName(),
            $matches,
        )) {
            return $matches[1];
        }

        return '';
    }
}
