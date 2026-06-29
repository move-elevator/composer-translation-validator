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
use MoveElevator\ComposerTranslationValidator\Utility\LocaleUtility;
use SimpleXMLElement;

use function preg_match;

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
        // @codeCoverageIgnoreStart
        if (false === $xmlContent) {
            throw new InvalidArgumentException("Failed to read file: {$filePath}");
        }
        // @codeCoverageIgnoreEnd

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
        return $this->getLanguageFromFileName() ?? $this->getSourceLanguage();
    }

    public function isVersion2(): bool
    {
        return $this->isVersion2;
    }

    /**
     * Returns the base language extracted from the filename, or null if none.
     * Region is stripped (e.g. "de_DE.locallang.xlf" → "de").
     */
    public function getLanguageFromFileName(): ?string
    {
        $locale = $this->getLocaleFromFileName();

        return null === $locale ? null : LocaleUtility::parse($locale)['base'];
    }

    /**
     * Extracts the full locale from the filename, preserving the region, supporting both
     * prefix convention (de.locallang.xlf, TYPO3 style) and
     * suffix convention (messages.de.xlf, Symfony/Laravel style).
     * Returns null if the filename carries no locale (e.g. "de_DE" or "de").
     */
    public function getLocaleFromFileName(): ?string
    {
        $fileName = $this->getFileName();

        // Prefix convention: de.locallang.xlf, de_AT.locallang.xlf, es_419.locallang.xlf
        if (preg_match('/^([a-z]{2}(?:[-_](?:[a-z]{2}|[0-9]{3}))?)\./i', $fileName, $matches)) {
            return $matches[1];
        }

        // Suffix convention: messages.de.xlf, messages.de_AT.xlf, messages.es_419.xlf
        if (preg_match('/\.([a-z]{2}(?:[-_](?:[a-z]{2}|[0-9]{3}))?)\.(?:xlf|xliff)$/i', $fileName, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Returns the base target language declared in the XLIFF file, or null if not set.
     * Region is stripped (e.g. "de-AT" → "de").
     */
    public function getTargetLanguage(): ?string
    {
        $locale = $this->getTargetLocale();

        return null === $locale ? null : LocaleUtility::parse($locale)['base'];
    }

    /**
     * Returns the full target locale declared in the XLIFF file, preserving the region,
     * or null if not set.
     * XLIFF 1.x: target-language attribute on <file>.
     * XLIFF 2.x: trgLang attribute on <xliff>.
     */
    public function getTargetLocale(): ?string
    {
        $lang = $this->isVersion2
            ? (string) ($this->xml['trgLang'] ?? '')
            : (string) ($this->xml->file['target-language'] ?? '');

        return '' === $lang ? null : $lang;
    }

    private function getSourceLanguage(): string
    {
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
