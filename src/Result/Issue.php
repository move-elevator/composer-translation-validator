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

namespace MoveElevator\ComposerTranslationValidator\Result;

/**
 * Issue.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class Issue
{
    /**
     * @param array<mixed> $details
     */
    public function __construct(
        private readonly string $file,
        private readonly array $details,
        private readonly string $parser,
        private readonly string $validatorType,
    ) {}

    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @return array<mixed>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    public function getParser(): string
    {
        return $this->parser;
    }

    public function getValidatorType(): string
    {
        return $this->validatorType;
    }

    /**
     * @return array{file: string, issues: array<mixed>, parser: string, type: string}
     */
    public function toArray(): array
    {
        return [
            'file' => $this->file,
            'issues' => $this->details,
            'parser' => $this->parser,
            'type' => $this->validatorType,
        ];
    }
}
