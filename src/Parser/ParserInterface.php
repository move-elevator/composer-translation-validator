<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Parser;

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
}
