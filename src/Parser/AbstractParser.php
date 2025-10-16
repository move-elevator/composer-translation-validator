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

use InvalidArgumentException;
use RuntimeException;

use function dirname;
use function in_array;
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
     * @return array<int, string>
     */
    abstract public static function getSupportedFileExtensions(): array;
}
