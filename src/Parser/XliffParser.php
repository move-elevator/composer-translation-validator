<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Parser;

class XliffParser extends AbstractParser implements ParserInterface
{
    private readonly \SimpleXMLElement|bool $xml;

    public function __construct(protected string $filePath)
    {
        parent::__construct($filePath);

        $this->xml = @simplexml_load_string(file_get_contents($filePath));
    }

    /**
     * @return array<int, string>|null
     */
    public function extractKeys(): ?array
    {
        $keys = [];
        foreach ($this->xml->file->body->{'trans-unit'} as $unit) {
            $keys[] = (string) $unit['id'];
        }

        return $keys;
    }

    public function getContentByKey(string $key, string $attribute = 'source'): ?string
    {
        foreach ($this->xml->file->body->{'trans-unit'} as $unit) {
            if ((string) $unit['id'] === $key) {
                return ('' !== (string) $unit->{$attribute}) ? (string) $unit->{$attribute} : null;
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
        if (preg_match('/^([a-z]{2})\./i', $this->getFileName(), $matches)) {
            $language = $matches[1];
        } else {
            $language = (string) ($this->xml->file['source-language'] ?? '');
        }

        return $language;
    }
}
