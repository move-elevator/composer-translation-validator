<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Parser;

use JsonException;
use RuntimeException;

use function array_key_exists;
use function is_array;
use function is_string;
use function sprintf;

/**
 * JsonParser.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class JsonParser extends AbstractParser implements ParserInterface
{
    /** @var array<string, mixed> */
    private array $json = [];

    public function __construct(string $filePath)
    {
        parent::__construct($filePath);

        try {
            $content = file_get_contents($filePath);
            if (false === $content) {
                throw new RuntimeException("Failed to read file: {$filePath}");
            }

            $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
            if (!is_array($decoded) || array_is_list($decoded)) {
                throw new RuntimeException("JSON file does not contain an object: {$filePath}");
            }

            $this->json = $decoded;
        } catch (JsonException $e) {
            throw new RuntimeException(sprintf('Failed to parse JSON file "%s": %s', $filePath, $e->getMessage()), 0, $e);
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

        return $extract($this->json);
    }

    public function getContentByKey(string $key): ?string
    {
        // Note: the $attribute parameter is required by ParserInterface
        // but is not used for JSON, since JSON has no source/target concept.
        $parts = explode('.', $key);
        $value = $this->json;

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
        return ['json'];
    }

    public function getLanguage(): string
    {
        if (preg_match(
            '/\.([a-z]{2})(?:[-_][A-Z]{2})?\./',
            $this->getFileName(),
            $matches,
        )) {
            return $matches[1];
        }

        return '';
    }
}
