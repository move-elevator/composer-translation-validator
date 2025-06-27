<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Parser;

class ParserUtility
{
    /**
     * @return array<int, string>
     *
     * @throws \ReflectionException
     */
    public static function resolveAllowedFileExtensions(): array
    {
        $fileExtensions = [];
        foreach (self::resolveParserClasses() as $parserClass) {
            if (method_exists($parserClass, 'getSupportedFileExtensions')) {
                $fileExtensions = [...$fileExtensions, ...$parserClass::getSupportedFileExtensions()];
            }
        }

        return $fileExtensions;
    }

    /**
     * @return array<int, class-string<ParserInterface>>
     *
     * @throws \ReflectionException
     */
    public static function resolveParserClasses(): array
    {
        $allClasses = get_declared_classes();
        $parserClasses = [];

        foreach ($allClasses as $class) {
            $reflectionClass = new \ReflectionClass($class);
            if ($reflectionClass->implementsInterface(ParserInterface::class)) {
                $parserClasses[] = $class;
            }
        }

        return $parserClasses;
    }

    /**
     * @return class-string<ParserInterface>|null
     *
     * @throws \ReflectionException
     */
    public static function resolveParserClass(string $filePath): ?string
    {
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
        $parserClasses = self::resolveAllowedFileExtensions();

        foreach ($parserClasses as $parserClass) {
            /* @var class-string<ParserInterface> $parserClass */
            if (in_array($fileExtension, $parserClass::getSupportedFileExtensions(), true)) {
                return $parserClass;
            }
        }

        return null;
    }
}
