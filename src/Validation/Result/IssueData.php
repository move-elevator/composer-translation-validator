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

namespace MoveElevator\ComposerTranslationValidator\Validation\Result;

use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;

/**
 * Type-safe immutable value object for structured issue representation.
 *
 * Provides better typing than array<mixed> details in Issue class.
 * Compatible with PHP 8.1+ readonly properties for external API consumption.
 */
final readonly class IssueData
{
    /**
     * @param array<string>        $messages Specific issue messages
     * @param array<string, mixed> $context  Additional context data
     */
    public function __construct(
        public string $file,
        public array $messages,
        public string $parser,
        public string $validatorType,
        public ResultType $severity,
        public array $context = [],
        public ?int $line = null,
        public ?int $column = null,
    ) {}

    /**
     * Create from existing Issue object.
     */
    public static function fromIssue(Issue $issue, ResultType $severity = ResultType::ERROR): self
    {
        $details = $issue->getDetails();
        $messages = [];
        $context = [];
        $line = null;
        $column = null;

        // Extract structured data from mixed array details
        foreach ($details as $key => $value) {
            if (is_int($key) && is_string($value)) {
                $messages[] = $value;
            } elseif (is_string($key)) {
                if ('line' === $key && is_int($value)) {
                    $line = $value;
                } elseif ('column' === $key && is_int($value)) {
                    $column = $value;
                } else {
                    $context[$key] = $value;
                }
            }
        }

        // If no structured messages, use details as messages
        if (empty($messages)) {
            $messages = array_map('strval', array_values($details));
        }

        return new self(
            file: $issue->getFile(),
            messages: $messages,
            parser: $issue->getParser(),
            validatorType: $issue->getValidatorType(),
            severity: $severity,
            context: $context,
            line: $line,
            column: $column,
        );
    }

    /**
     * Get primary message (first message).
     */
    public function getPrimaryMessage(): string
    {
        return $this->messages[0] ?? 'Unknown issue';
    }

    /**
     * Get all messages as single string.
     */
    public function getAllMessagesAsString(): string
    {
        return implode(' | ', $this->messages);
    }

    /**
     * Check if issue has location information.
     */
    public function hasLocation(): bool
    {
        return null !== $this->line;
    }

    /**
     * Get formatted location string.
     */
    public function getLocationString(): ?string
    {
        if (null === $this->line) {
            return null;
        }

        if (null !== $this->column) {
            return sprintf('%d:%d', $this->line, $this->column);
        }

        return (string) $this->line;
    }

    /**
     * Get formatted issue string for display.
     */
    public function getFormattedString(): string
    {
        $parts = [$this->file];

        if ($this->hasLocation()) {
            $parts[] = $this->getLocationString();
        }

        $parts[] = $this->getPrimaryMessage();

        return implode(':', $parts);
    }

    /**
     * Convert to array format for serialization.
     *
     * @return array{
     *   file: string,
     *   messages: array<string>,
     *   parser: string,
     *   validatorType: string,
     *   severity: string,
     *   context: array<string, mixed>,
     *   line: int|null,
     *   column: int|null
     * }
     */
    public function toArray(): array
    {
        return [
            'file' => $this->file,
            'messages' => $this->messages,
            'parser' => $this->parser,
            'validatorType' => $this->validatorType,
            'severity' => strtolower($this->severity->toString()),
            'context' => $this->context,
            'line' => $this->line,
            'column' => $this->column,
        ];
    }

    /**
     * Convert to legacy Issue format for backward compatibility.
     */
    public function toLegacyIssue(): Issue
    {
        $details = $this->messages;

        if (null !== $this->line) {
            $details['line'] = $this->line;
        }

        if (null !== $this->column) {
            $details['column'] = $this->column;
        }

        foreach ($this->context as $key => $value) {
            $details[$key] = $value;
        }

        return new Issue(
            $this->file,
            $details,
            $this->parser,
            $this->validatorType,
        );
    }
}
