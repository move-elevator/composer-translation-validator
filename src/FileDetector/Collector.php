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

namespace MoveElevator\ComposerTranslationValidator\FileDetector;

use Exception;
use MoveElevator\ComposerTranslationValidator\Parser\ParserRegistry;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionException;
use Symfony\Component\Filesystem\Filesystem;

use function dirname;
use function in_array;

/**
 * Collector.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class Collector
{
    public function __construct(protected ?LoggerInterface $logger = null) {}

    /**
     * @param string[]      $paths
     * @param string[]|null $excludePatterns
     *
     * @return array<class-string, array<string, array<mixed>>>
     *
     * @throws ReflectionException
     */
    public function collectFiles(
        array $paths,
        ?DetectorInterface $detector = null,
        ?array $excludePatterns = null,
        bool $recursive = false,
    ): array {
        $allFiles = [];
        foreach ($paths as $path) {
            if (!(new Filesystem())->exists($path)) {
                $this->logger?->error('The provided path "'.$path.'" is not a valid directory.');
                continue;
            }

            foreach (ParserRegistry::getAvailableParsers() as $parserClass) {
                $files = $this->findFiles($path, $parserClass::getSupportedFileExtensions(), $recursive);
                if (empty($files)) {
                    $this->logger?->debug('No files found for parser class "'.$parserClass.'" in path "'.$path.'".');
                    continue;
                }

                if ($excludePatterns) {
                    $files = array_filter(
                        $files,
                        static fn ($file) => !array_filter(
                            $excludePatterns,
                            static fn ($pattern) => fnmatch($pattern, basename((string) $file)),
                        ),
                    );
                }

                if (empty($files)) {
                    $this->logger?->debug('No files found for parser class "'.$parserClass.'" in path "'.$path.'".');
                    continue;
                }

                if (null !== $detector) {
                    $allFiles[$parserClass][$path] = $detector->mapTranslationSet($files);
                } else {
                    // Group files by directory to prevent cross-directory FileSets
                    $filesByDirectory = $this->groupFilesByDirectory($files);

                    foreach ($filesByDirectory as $directory => $directoryFiles) {
                        foreach (FileDetectorRegistry::getAvailableFileDetectors() as $fileDetector) {
                            $translationSet = (new $fileDetector())->mapTranslationSet($directoryFiles);
                            if (!empty($translationSet)) {
                                // Use directory-specific path key to separate FileSets
                                $pathKey = $path.'/'.$directory;
                                $allFiles[$parserClass][$pathKey] = $translationSet;
                                break; // Found a detector for this directory, move to next directory
                            }
                        }
                    }
                }
            }
        }

        return $allFiles;
    }

    /**
     * Find files in a directory, optionally recursively.
     *
     * @param string[] $supportedExtensions
     *
     * @return string[]
     */
    private function findFiles(string $path, array $supportedExtensions, bool $recursive): array
    {
        if (!$recursive) {
            $globFiles = glob($path.'/*');
            if (false === $globFiles) {
                $this->logger?->warning('Failed to glob files in path: '.$path);

                return [];
            }

            return array_filter(
                $globFiles,
                static fn ($file) => in_array(
                    pathinfo((string) $file, \PATHINFO_EXTENSION),
                    $supportedExtensions,
                    true,
                ),
            );
        }

        $normalizedPath = $this->normalizePath($path);
        if (!$this->isPathSafe($normalizedPath)) {
            $this->logger?->warning('Skipping potentially unsafe path: '.$path);

            return [];
        }

        $files = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($normalizedPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY,
            );

            foreach ($iterator as $file) {
                $filePath = $file->getPathname();
                $extension = pathinfo((string) $filePath, \PATHINFO_EXTENSION);

                if (in_array($extension, $supportedExtensions, true) && is_file($filePath)) {
                    $files[] = $filePath;
                }
            }
        } catch (Exception $e) {
            $this->logger?->error('Error during recursive file search: '.$e->getMessage());

            return [];
        }

        return $files;
    }

    /**
     * Normalize a file path to prevent path traversal attacks.
     */
    private function normalizePath(string $path): string
    {
        $resolved = realpath($path);
        if (false !== $resolved) {
            return $resolved;
        }

        return rtrim($path, '/\\');
    }

    /**
     * Basic path safety check to prevent obvious security issues.
     */
    private function isPathSafe(string $path): bool
    {
        $dangerousPaths = ['/etc', '/usr', '/bin', '/sbin', '/proc', '/sys', '/private/etc'];

        foreach ($dangerousPaths as $dangerousPath) {
            if (str_starts_with($path, $dangerousPath)) {
                return false;
            }
        }

        return substr_count($path, '/') + substr_count($path, '\\') <= 20;
    }

    /**
     * Groups files by their immediate parent directory to prevent cross-directory FileSets.
     *
     * @param array<string> $files
     *
     * @return array<string, array<string>>
     */
    private function groupFilesByDirectory(array $files): array
    {
        $groups = [];

        foreach ($files as $file) {
            $directory = dirname($file);
            $groups[$directory][] = $file;
        }

        return $groups;
    }
}
