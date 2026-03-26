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
    private readonly bool $isVersion2;

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

        $this->isVersion2 = version_compare((string) ($this->xml['version'] ?? ''), '2.0', '>=');
    }

    /**
     * @return array<int, string>|null
     */
    public function extractKeys(): ?array
    {
        $units = $this->getTranslationUnits();
        if (null === $units) {
            return [];
        }

        $keys = [];
        foreach ($units as $unit) {
            $keys[] = (string) $unit['id'];
        }

        return $keys;
    }

    public function getContentByKey(string $key): ?string
    {
        $units = $this->getTranslationUnits();
        if (null === $units) {
            return null;
        }

        $attribute = $this->hasTargetLanguage() ? 'target' : 'source';

        foreach ($units as $unit) {
            if ((string) $unit['id'] !== $key) {
                continue;
            }

            $source = $this->getUnitContent($unit, 'source');
            $target = $this->getUnitContent($unit, 'target');

            $primary = 'target' === $attribute ? $target : $source;
            if ('' !== $primary) {
                return $primary;
            }

            $fallback = 'target' === $attribute ? $source : $target;
            if ('' !== $fallback) {
                return $fallback;
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
            return $matches[1];
        }

        if ($this->isVersion2) {
            return (string) ($this->xml['srcLang'] ?? '');
        }

        return (string) ($this->xml->file['source-language'] ?? '');
    }

    private function getTranslationUnits(): ?SimpleXMLElement
    {
        if ($this->isVersion2) {
            $units = $this->xml->file->unit;

            return $units->count() > 0 ? $units : null;
        }

        $units = $this->xml->file->body->{'trans-unit'};

        return $units->count() > 0 ? $units : null;
    }

    private function getUnitContent(SimpleXMLElement $unit, string $element): string
    {
        if ($this->isVersion2) {
            return (string) ($unit->segment->{$element} ?? '');
        }

        return (string) ($unit->{$element} ?? '');
    }

    private function hasTargetLanguage(): bool
    {
        if ($this->isVersion2) {
            return !empty((string) ($this->xml['trgLang'] ?? ''));
        }

        return !empty((string) $this->xml->file['target-language']);
    }
}
