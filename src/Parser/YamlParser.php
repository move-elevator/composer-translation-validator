<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Parser;

use Symfony\Component\Yaml\Yaml;

class YamlParser extends AbstractParser implements ParserInterface
{
    /** @var array<string, mixed> */
    private array $yaml = [];

    public function __construct(protected string $filePath)
    {
        parent::__construct($filePath);

        try {
            $this->yaml = Yaml::parseFile($filePath);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Failed to parse YAML file "%s": %s', $filePath, $e->getMessage()), 0, $e);
        }
    }

    /**
     * @return array<int, string>|null
     */
    public function extractKeys(): ?array
    {
        $extract = static function (
            array $data,
            string $prefix = '',
        ) use (&$extract): array {
            $keys = [];
            foreach ($data as $key => $value) {
                $fullKey = '' === $prefix
                    ? $key
                    : $prefix.'.'.$key;
                if (is_array($value)) {
                    $extracted = $extract($value, $fullKey);
                    foreach ($extracted as $k) {
                        $keys[] = $k;
                    }
                } else {
                    $keys[] = $fullKey;
                }
            }

            return $keys;
        };

        return $extract($this->yaml);
    }

    public function getContentByKey(string $key, string $attribute = 'source'): ?string
    {
        // Note: the $attribute parameter is required by ParserInterface
        // but is not used for YAML, since YAML has no source/target concept.
        $parts = explode('.', $key);
        $value = $this->yaml;

        foreach ($parts as $part) {
            if (is_array($value) && array_key_exists($part, $value)) {
                $value = $value[$part];
            } else {
                return null;
            }
        }

        return is_string($value) ? $value : null;
    }

    /**
     * @return array<int, string>
     */
    public static function getSupportedFileExtensions(): array
    {
        return ['yaml', 'yml'];
    }

    public function getLanguage(): string
    {
        if (preg_match(
            '/\.(\w{2})\./',
            $this->getFileName(),
            $matches
        )) {
            return $matches[1];
        }

        return '';
    }
}
