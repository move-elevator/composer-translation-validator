<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\FileDetector;

use MoveElevator\ComposerTranslationValidator\Parser\ParserRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class Collector
{
    public function __construct(protected ?LoggerInterface $logger = null)
    {
    }

    /**
     * @param string[]      $paths
     * @param string[]|null $excludePatterns
     *
     * @return array<class-string, array<string, array<mixed>>>
     *
     * @throws \ReflectionException
     */
    public function collectFiles(array $paths, ?DetectorInterface $detector = null, ?array $excludePatterns = null): array
    {
        $allFiles = [];
        foreach ($paths as $path) {
            if (!(new Filesystem())->exists($path)) {
                $this->logger->error('The provided path "'.$path.'" is not a valid directory.');
                continue;
            }

            foreach (ParserRegistry::getAvailableParsers() as $parserClass) {
                $files = array_filter(
                    glob($path.'/*'),
                    static fn ($file) => in_array(pathinfo($file, PATHINFO_EXTENSION), $parserClass::getSupportedFileExtensions(), true)
                );

                if ($excludePatterns) {
                    $files = array_filter($files, static fn ($file) => !array_filter($excludePatterns, static fn ($pattern) => fnmatch($pattern, basename($file))));
                }

                if (empty($files)) {
                    $this->logger->debug('No files found for parser class "'.$parserClass.'" in path "'.$path.'".');
                    continue;
                }

                if (null !== $detector) {
                    $allFiles[$parserClass][$path] = $detector->mapTranslationSet($files);
                } else {
                    foreach (FileDetectorRegistry::getAvailableFileDetectors() as $fileDetector) {
                        $translationSet = (new $fileDetector())->mapTranslationSet($files);
                        if (!empty($translationSet)) {
                            $allFiles[$parserClass][$path] = $translationSet;
                            continue 2;
                        }
                    }
                }
            }
        }

        return $allFiles;
    }
}
