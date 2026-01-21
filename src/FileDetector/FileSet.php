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

namespace MoveElevator\ComposerTranslationValidator\FileDetector;

/**
 * FileSet.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class FileSet
{
    /**
     * @param array<string> $files
     */
    public function __construct(
        private readonly string $parser,
        private readonly string $path,
        private readonly string $setKey,
        private readonly array $files,
    ) {}

    public function getParser(): string
    {
        return $this->parser;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getSetKey(): string
    {
        return $this->setKey;
    }

    /**
     * @return array<string>
     */
    public function getFiles(): array
    {
        return $this->files;
    }
}
