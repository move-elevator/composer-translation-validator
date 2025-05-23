<?php

declare(strict_types=1);

namespace KonradMichalik\ComposerTranslationValidator\Parser;

class ParserUtility
{
    public static function resolveAllowedFileExtensions(): array
    {
        $allClasses = get_declared_classes();
        $parserClasses = [];

        foreach ($allClasses as $class) {
            $reflectionClass = new \ReflectionClass($class);
            if ($reflectionClass->implementsInterface(ParserInterface::class)) {
                $parserClasses[] = $class;
            }
        }

        $fileExtensions = [];
        foreach ($parserClasses as $parserClass) {
            if (method_exists($parserClass, 'getSupportedFileExtensions')) {
                $fileExtensions = [...$fileExtensions, ...$parserClass::getSupportedFileExtensions()];
            }
        }

        return $fileExtensions;
    }

    public static function resolveParserClass(string $filePath): ?string
    {
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
        $parserClasses = self::resolveAllowedFileExtensions();

        foreach ($parserClasses as $parserClass) {
            if (in_array($fileExtension, $parserClass::getSupportedFileExtensions(), true)) {
                return $parserClass;
            }
        }

        return null;
    }
}
