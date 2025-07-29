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

use InvalidArgumentException;
use SimpleXMLElement;

class XliffParser extends AbstractParser implements ParserInterface
{
    private readonly SimpleXMLElement $xml;

    /**
     * @param string $filePath Path to the XLIFF file
     *
     * @throws InvalidArgumentException If file cannot be parsed as
     *                                  valid XML
     */
    public function __construct(string $filePath)
    {
        parent::__construct($filePath);

        $xmlContent = file_get_contents($filePath);
        if (false === $xmlContent) {
            throw new InvalidArgumentException("Failed to read file: {$filePath}");
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);

        if (false === $xml) {
            throw new InvalidArgumentException("Failed to parse XML content from file: {$filePath}");
        }

        $this->xml = $xml;
        libxml_clear_errors();
    }

    /**
     * @return array<int, string>|null
     */
    public function extractKeys(): ?array
    {
        $keys = [];
        foreach ($this->xml->file->body->{'trans-unit'} as $unit) {
            $keys[] = (string) $unit['id'];
        }

        return $keys;
    }

    public function getContentByKey(string $key): ?string
    {
        $attribute = $this->hasTargetLanguage() ? 'target' : 'source';

        foreach ($this->xml->file->body->{'trans-unit'} as $unit) {
            if ((string) $unit['id'] === $key) {
                if ('' !== (string) $unit->{$attribute}) {
                    return (string) $unit->{$attribute};
                }

                if ('target' === $attribute && $this->hasTargetLanguage()) {
                    $fallbackContent = (string) $unit->source;
                    if ('' !== $fallbackContent) {
                        return $fallbackContent;
                    }
                }

                if ('source' === $attribute && !$this->hasTargetLanguage()) {
                    $fallbackContent = (string) $unit->target;
                    if ('' !== $fallbackContent) {
                        return $fallbackContent;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    public static function getSupportedFileExtensions(): array
    {
        return ['xliff', 'xlf'];
    }

    public function getLanguage(): string
    {
        if (preg_match(
            '/^([a-z]{2})\./i',
            $this->getFileName(),
            $matches,
        )) {
            $language = $matches[1];
        } else {
            $language = (string) ($this->xml->file['source-language'] ?? '');
        }

        return $language;
    }

    private function hasTargetLanguage(): bool
    {
        return !empty((string) $this->xml->file['target-language']);
    }
}
