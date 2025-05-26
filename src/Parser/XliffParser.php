<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Parser;

class XliffParser implements ParserInterface
{
    private \SimpleXMLElement|bool $xml = false;
    protected string $fileName = '';

    public function __construct(protected string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException(sprintf('File "%s" does not exist.', $filePath));
        }

        if (!is_readable($filePath)) {
            throw new \RuntimeException(sprintf('File "%s" is not readable.', $filePath));
        }

        if (!in_array(pathinfo($filePath, PATHINFO_EXTENSION), self::getSupportedFileExtensions(), true)) {
            throw new \InvalidArgumentException(sprintf('File "%s" is not a valid XLIFF file.', $filePath));
        }
        $this->xml = @simplexml_load_file($filePath);
        $this->fileName = basename($filePath);
    }

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
        foreach ($this->xml->file->body->{'trans-unit'} as $unit) {
            if ((string) $unit['id'] === $key) {
                return (string) $unit->source ?? null;
            }
        }

        return null;
    }

    public static function getSupportedFileExtensions(): array
    {
        return ['xliff', 'xlf'];
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

    public function getLanguage(): string
    {
        if (preg_match('/^([a-z]{2})\./i', $this->getFileName(), $matches)) {
            $language = $matches[1];
        } else {
            $language = (string) ($this->xml->file['source-language'] ?? '');
        }

        return $language;
    }
}
