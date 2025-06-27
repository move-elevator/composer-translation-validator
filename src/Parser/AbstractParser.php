<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Parser;

abstract class AbstractParser
{
    protected string $fileName = '';

    public function __construct(protected string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException(sprintf('File "%s" does not exist.', $filePath));
        }

        if (!is_readable($filePath)) {
            throw new \RuntimeException(sprintf('File "%s" is not readable.', $filePath));
        }

        if (!in_array(pathinfo($filePath, PATHINFO_EXTENSION), static::getSupportedFileExtensions(), true)) {
            throw new \InvalidArgumentException(sprintf('File "%s" is not a valid file.', $filePath));
        }

        $this->fileName = basename($filePath);
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getFileDirectory(): string
    {
        return dirname($this->filePath).\DIRECTORY_SEPARATOR;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * @return array<int, string>
     */
    abstract public static function getSupportedFileExtensions(): array;
}
