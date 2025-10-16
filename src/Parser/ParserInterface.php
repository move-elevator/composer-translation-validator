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

/**
 * ParserInterface.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
interface ParserInterface
{
    public function __construct(string $filePath);

    /**
     * @return array<int, string>
     */
    public static function getSupportedFileExtensions(): array;

    /**
     * @return array<int, string>|null
     */
    public function extractKeys(): ?array;

    public function getContentByKey(string $key): ?string;

    public function getFileName(): string;

    public function getFileDirectory(): string;

    public function getFilePath(): string;
}
