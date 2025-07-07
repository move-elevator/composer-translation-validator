<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\FileDetector;

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
    ) {
    }

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
