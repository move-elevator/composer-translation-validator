<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025-2026 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Parser;

use InvalidArgumentException;
use SimpleXMLElement;

/**
 * XliffParser.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class XliffParser extends AbstractParser implements ParserInterface
{
    private readonly SimpleXMLElement|bool $xml;

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
        $this->xml = simplexml_load_string($xmlContent);

        if (false === $this->xml) {
            throw new InvalidArgumentException("Failed to parse XML content from file: {$filePath}");
        }
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
