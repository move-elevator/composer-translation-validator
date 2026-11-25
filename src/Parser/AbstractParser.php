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

namespace MoveElevator\ComposerTranslationValidator\Parser;

use Generator;
use InvalidArgumentException;
use RuntimeException;

use function dirname;
use function in_array;
use function is_array;
use function sprintf;

/**
 * AbstractParser.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
abstract class AbstractParser
{
    protected readonly string $fileName;

    protected ?string $rawContent = null;

    /** @var array{keys: array<int, string>, values: array<string, mixed>}|null */
    private ?array $flattened = null;

    public function __construct(protected readonly string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException(sprintf('File "%s" does not exist.', $filePath));
        }

        if (!is_readable($filePath)) {
            throw new RuntimeException(sprintf('File "%s" is not readable.', $filePath));
        }

        if (!in_array(
            pathinfo($filePath, \PATHINFO_EXTENSION),
            static::getSupportedFileExtensions(),
            true,
        )) {
            throw new InvalidArgumentException(sprintf('File "%s" is not a valid file.', $filePath));
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
     * Returns the raw file content, reading it at most once.
     *
     * Parsers that already read the file while parsing populate this, so
     * consumers (e.g. the encoding validator) can reuse it instead of reading
     * the file from disk again. Returns null if the file cannot be read.
     */
    public function getRawContent(): ?string
    {
        if (null !== $this->rawContent) {
            return $this->rawContent;
        }

        $content = file_get_contents($this->filePath);

        return false === $content ? null : ($this->rawContent = $content);
    }

    /**
     * @return array<int, string>
     */
    abstract public static function getSupportedFileExtensions(): array;

    /**
     * Flattens nested translation data to dotted keys. A flat key that already
     * contains dots resolves to its own value, like a nested one. The result is
     * memoized, so `$data` is only read on the first call.
     *
     * @param array<int|string, mixed> $data
     *
     * @return array{keys: array<int, string>, values: array<string, mixed>}
     */
    protected function flattened(array $data): array
    {
        if (null === $this->flattened) {
            $keys = [];
            $values = [];
            foreach (self::flatten($data) as $key => $value) {
                $keys[] = (string) $key;
                $values[$key] = $value;
            }

            $this->flattened = ['keys' => $keys, 'values' => $values];
        }

        return $this->flattened;
    }

    /**
     * @param array<int|string, mixed> $data
     *
     * @return Generator<string, mixed>
     */
    private static function flatten(array $data, string $prefix = ''): Generator
    {
        foreach ($data as $key => $value) {
            $fullKey = '' === $prefix ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                yield from self::flatten($value, $fullKey);
            } else {
                yield $fullKey => $value;
            }
        }
    }
}
