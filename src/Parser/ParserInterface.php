<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Parser;

interface ParserInterface
{
    public function __construct(string $filePath);

    public static function getSupportedFileExtensions(): array;

    public function extractKeys(): ?array;

    public function getContentByKey(string $key): ?string;
}
