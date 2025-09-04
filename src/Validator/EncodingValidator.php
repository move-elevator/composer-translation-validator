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

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\JsonParser;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\PhpParser;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use Normalizer;

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @package ComposerTranslationValidator
 */

class EncodingValidator extends AbstractValidator implements ValidatorInterface
{
    /**
     * @return array<string, string>
     */
    public function processFile(ParserInterface $file): array
    {
        $filePath = $file->getFilePath();
        $issues = [];

        if (!file_exists($filePath)) {
            $this->logger?->error(
                'File does not exist: '.$file->getFileName(),
            );

            return [];
        }

        // Read raw file content
        $content = file_get_contents($filePath);
        if (false === $content) {
            $this->logger?->error(
                'Could not read file content: '.$file->getFileName(),
            );

            return [];
        }

        // Early exit for empty files
        if ('' === $content) {
            return [];
        }

        // Check UTF-8 encoding first - if invalid, other checks may fail
        if (!$this->isValidUtf8($content)) {
            $issues['encoding'] = 'File is not valid UTF-8 encoded';

            // Skip other checks for invalid UTF-8 content
            return $issues;
        }

        // Check for BOM (fast byte check)
        $hasBom = $this->hasByteOrderMark($content);
        if ($hasBom) {
            $issues['bom'] = 'File contains UTF-8 Byte Order Mark (BOM)';
        }

        // Check for invisible/problematic characters
        $invisibleChars = $this->findInvisibleCharacters($content);
        if (!empty($invisibleChars)) {
            $issues['invisible_chars'] = sprintf(
                'File contains invisible characters: %s',
                implode(', ', array_unique($invisibleChars)),
            );
        }

        // Check Unicode normalization (expensive, only if intl available)
        if ($this->hasUnicodeNormalizationIssues($content)) {
            $issues['unicode_normalization'] = 'File contains non-NFC normalized Unicode characters';
        }

        // Note: JSON syntax validation is handled by JsonParser constructor
        // Invalid JSON files will throw exceptions before reaching this validator

        return $issues;
    }

    public function formatIssueMessage(Issue $issue, string $prefix = ''): string
    {
        $details = $issue->getDetails();
        $resultType = $this->resultTypeOnValidationFailure();

        $level = $resultType->toString();
        $color = $resultType->toColorString();

        $messages = [];
        foreach ($details as $type => $message) {
            if (is_string($type) && is_string($message)) {
                $messages[] = "- <fg=$color>$level</> {$prefix}encoding issue: $message";
            }
        }

        return implode("\n", $messages);
    }

    /**
     * @return class-string<ParserInterface>[]
     */
    public function supportsParser(): array
    {
        return [XliffParser::class, YamlParser::class, JsonParser::class, PhpParser::class];
    }

    public function resultTypeOnValidationFailure(): ResultType
    {
        return ResultType::WARNING;
    }

    private function isValidUtf8(string $content): bool
    {
        return mb_check_encoding($content, 'UTF-8');
    }

    private function hasByteOrderMark(string $content): bool
    {
        // UTF-8 BOM is 0xEF 0xBB 0xBF
        return str_starts_with($content, "\xEF\xBB\xBF");
    }

    /**
     * @return array<string>
     */
    private function findInvisibleCharacters(string $content): array
    {
        $problematicChars = [];

        // Early exit for ASCII-only content (performance optimization)
        if (mb_check_encoding($content, 'ASCII')) {
            // Only check for control characters in ASCII content
            if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $content)) {
                $problematicChars[] = 'Control characters';
            }

            return $problematicChars;
        }

        // Check for problematic Unicode characters individually for better performance
        $charMap = [
            "\u{200B}" => 'Zero-width space',
            "\u{200C}" => 'Zero-width non-joiner',
            "\u{200D}" => 'Zero-width joiner',
            "\u{2060}" => 'Word joiner',
            "\u{FEFF}" => 'Zero-width no-break space',
            "\u{200E}" => 'Left-to-right mark',
            "\u{200F}" => 'Right-to-left mark',
            "\u{00AD}" => 'Soft hyphen',
        ];

        foreach ($charMap as $char => $name) {
            if (str_contains($content, $char)) {
                $problematicChars[] = $name;
            }
        }

        // Check for control characters (except allowed whitespace)
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $content)) {
            $problematicChars[] = 'Control characters';
        }

        return $problematicChars;
    }

    private function hasUnicodeNormalizationIssues(string $content): bool
    {
        if (!class_exists('Normalizer')) {
            return false;
        }

        $normalized = Normalizer::normalize($content, Normalizer::FORM_C);

        return false !== $normalized && $content !== $normalized;
    }
}
