<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\JsonParser;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\PhpParser;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use MoveElevator\ComposerTranslationValidator\Result\Issue;

class EncodingValidator extends AbstractValidator implements ValidatorInterface
{
    /**
     * @return array<string, string>
     */
    public function processFile(ParserInterface $file): array
    {
        $filePath = $file->getFilePath();
        $issues = [];

        // Read raw file content
        $content = file_get_contents($filePath);
        if (false === $content) {
            $this->logger?->error(
                'Could not read file content: '.$file->getFileName()
            );

            return [];
        }

        // Check UTF-8 encoding
        if (!$this->isValidUtf8($content)) {
            $issues['encoding'] = 'File is not valid UTF-8 encoded';
        }

        // Check for BOM
        if ($this->hasByteOrderMark($content)) {
            $issues['bom'] = 'File contains UTF-8 Byte Order Mark (BOM)';
        }

        // Check for invisible/problematic characters
        $invisibleChars = $this->findInvisibleCharacters($content);
        if (!empty($invisibleChars)) {
            $issues['invisible_chars'] = sprintf(
                'File contains invisible characters: %s',
                implode(', ', array_unique($invisibleChars))
            );
        }

        // Check Unicode normalization
        if ($this->hasUnicodeNormalizationIssues($content)) {
            $issues['unicode_normalization'] = 'File contains non-NFC normalized Unicode characters';
        }

        // JSON-specific validation for JSON files
        if ($file instanceof JsonParser && !$this->isValidJsonStructure($content)) {
            $issues['json_syntax'] = 'File contains invalid JSON syntax';
        }

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

        // Check for various problematic characters
        $checks = [
            'Zero-width space' => "\u{200B}",
            'Zero-width non-joiner' => "\u{200C}",
            'Zero-width joiner' => "\u{200D}",
            'Word joiner' => "\u{2060}",
            'Zero-width no-break space' => "\u{FEFF}",
            'Left-to-right mark' => "\u{200E}",
            'Right-to-left mark' => "\u{200F}",
            'Soft hyphen' => "\u{00AD}",
        ];

        foreach ($checks as $name => $char) {
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
            // If intl extension is not available, skip this check
            return false;
        }

        // Check if content is not in NFC (Canonical Decomposition followed by Canonical Composition)
        $normalized = \Normalizer::normalize($content, \Normalizer::FORM_C);

        return false !== $normalized && $content !== $normalized;
    }

    private function isValidJsonStructure(string $content): bool
    {
        // Remove BOM if present for JSON validation
        $cleanContent = $this->hasByteOrderMark($content)
            ? substr($content, 3)
            : $content;

        json_decode($cleanContent);

        return JSON_ERROR_NONE === json_last_error();
    }
}
