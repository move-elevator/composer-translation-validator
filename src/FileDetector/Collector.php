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
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionException;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;

use function dirname;
use function filesize;
use function in_array;
use function is_array;
use function sprintf;
use function strlen;

/**
 * Collector.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class Collector
{
    /**
     * Maximum size (in bytes) of a translation file that will be read into
     * memory. Larger files are skipped to prevent memory exhaustion when
     * scanning untrusted directories.
     */
    private const MAX_FILE_SIZE = 30 * 1024 * 1024;

    public function __construct(protected ?LoggerInterface $logger = null) {}

    /**
     * @param string[]                                   $paths
     * @param DetectorInterface|DetectorInterface[]|null $detector        one detector or a list applied in order, later ones only see files the earlier ones did not pair
     * @param string[]|null                              $excludePatterns
     *
     * @return array<class-string, array<string, array<mixed>>>
     *
     * @throws ReflectionException
     */
    public function collectFiles(
        array $paths,
        DetectorInterface|array|null $detector = null,
        ?array $excludePatterns = null,
        bool $recursive = false,
    ): array {
        $detectors = match (true) {
            null === $detector => [],
            is_array($detector) => $detector,
            default => [$detector],
        };

        $allFiles = [];
        foreach ($paths as $path) {
            if (!(new Filesystem())->exists($path)) {
                $this->logger?->error('The provided path "'.$path.'" is not a valid directory.');
                continue;
            }

            // Scan the directory once and bucket files by their parser, instead
            // of scanning it separately for every parser class.
            $excludeRoot = $recursive ? $this->normalizePath($path) : rtrim($path, '/');
            $filesByParser = $this->scanFilesByParser($path, $recursive, $excludePatterns ?? [], $excludeRoot);

            foreach (ParserRegistry::getAvailableParsers() as $parserClass) {
                $files = $filesByParser[$parserClass] ?? [];
                if (empty($files)) {
                    $this->logger?->debug('No files found for parser class "'.$parserClass.'" in path "'.$path.'".');
                    continue;
                }

                if ($excludePatterns) {
                    $files = array_filter(
                        $files,
                        fn ($file) => !$this->isExcluded((string) $file, $excludeRoot, $excludePatterns),
                    );
                }

                if (empty($files)) {
                    $this->logger?->debug('No files found for parser class "'.$parserClass.'" in path "'.$path.'".');
                    continue;
                }

                if ([] !== $detectors) {
                    // Each detector only sees the files the previous ones did not claim
                    $unclaimed = $files;
                    $pathSets = [];
                    foreach ($detectors as $configuredDetector) {
                        foreach ($this->mapWithDetector($configuredDetector, $path, $unclaimed) as $directory => $translationSet) {
                            $pathSets[$directory] = array_merge_recursive($pathSets[$directory] ?? [], $translationSet);
                            foreach ($translationSet as $setFiles) {
                                $unclaimed = array_diff($unclaimed, (array) $setFiles);
                            }
                        }
                    }

                    foreach ($pathSets as $directory => $translationSet) {
                        $allFiles[$parserClass][$directory] = $translationSet;
                    }
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
     * Scans a path once and groups the found files by their parser class.
     *
     * @param string[] $excludePatterns
     *
     * @return array<class-string, string[]>
     */
    private function scanFilesByParser(string $path, bool $recursive, array $excludePatterns, string $excludeRoot): array
    {
        $extensionMap = self::extensionToParserMap();
        $files = $this->findFiles($path, array_keys($extensionMap), $recursive, $excludePatterns, $excludeRoot);

        $filesByParser = [];
        foreach ($files as $file) {
            $extension = pathinfo((string) $file, \PATHINFO_EXTENSION);
            if (isset($extensionMap[$extension])) {
                $filesByParser[$extensionMap[$extension]][] = $file;
            }
        }

        return $filesByParser;
    }

    /**
     * Maps each supported file extension to the parser class handling it.
     * The first parser declaring an extension wins, matching parser resolution.
     *
     * @return array<string, class-string>
     */
    private static function extensionToParserMap(): array
    {
        $map = [];
        foreach (ParserRegistry::getAvailableParsers() as $parserClass) {
            foreach ($parserClass::getSupportedFileExtensions() as $extension) {
                $map[$extension] ??= $parserClass;
            }
        }

        return $map;
    }

    /**
     * Find files in a directory, optionally recursively.
     *
     * @param string[] $supportedExtensions
     * @param string[] $excludePatterns     excluded directories are not traversed
     *
     * @return string[]
     */
    private function findFiles(string $path, array $supportedExtensions, bool $recursive, array $excludePatterns, string $excludeRoot): array
    {
        if (!$recursive) {
            $globFiles = glob($path.'/*');
            // @codeCoverageIgnoreStart
            if (false === $globFiles) {
                $this->logger?->warning('Failed to glob files in path: '.$path);

                return [];
            }
            // @codeCoverageIgnoreEnd

            return array_filter(
                $globFiles,
                fn ($file) => in_array(
                    pathinfo((string) $file, \PATHINFO_EXTENSION),
                    $supportedExtensions,
                    true,
                ) && $this->isWithinSizeLimit((string) $file),
            );
        }

        $normalizedPath = $this->normalizePath($path);
        if (!$this->isPathSafe($normalizedPath)) {
            $this->logger?->warning('Skipping potentially unsafe path: '.$path);

            return [];
        }

        $files = [];

        try {
            // Reject symlinks (files and directories) so the recursive scan
            // cannot be redirected outside the target tree via a crafted link.
            $directoryIterator = new RecursiveDirectoryIterator($normalizedPath, RecursiveDirectoryIterator::SKIP_DOTS);
            $filteredIterator = new RecursiveCallbackFilterIterator(
                $directoryIterator,
                fn (SplFileInfo $current): bool => !$current->isLink()
                    && !($current->isDir() && $this->isExcluded($current->getPathname(), $excludeRoot, $excludePatterns, true)),
            );
            $iterator = new RecursiveIteratorIterator(
                $filteredIterator,
                RecursiveIteratorIterator::LEAVES_ONLY,
            );

            foreach ($iterator as $file) {
                $filePath = $file->getPathname();
                $extension = pathinfo((string) $filePath, \PATHINFO_EXTENSION);

                if (in_array($extension, $supportedExtensions, true)
                    && is_file($filePath)
                    && $this->isWithinSizeLimit($filePath)) {
                    $files[] = $filePath;
                }
            }
            // @codeCoverageIgnoreStart
        } catch (Exception $e) {
            $this->logger?->error('Error during recursive file search: '.$e->getMessage());

            return [];
        }
        // @codeCoverageIgnoreEnd

        return $files;
    }

    /**
     * Returns true as soon as the name or the path relative to the scanned
     * directory matches any exclude pattern, for files and directories alike.
     * Candidates also exist with a leading slash, so `**\/backup/**` matches a
     * top-level `backup/`, and directories are also tried with a trailing slash.
     * Directories above the scanned path never take part in the matching.
     *
     * @param string[] $patterns
     */
    private function isExcluded(string $path, string $root, array $patterns, bool $isDirectory = false): bool
    {
        $relative = str_starts_with($path, $root.'/') ? substr($path, strlen($root) + 1) : $path;
        $names = [basename($path), $relative];
        if ($isDirectory) {
            $names[] = $relative.'/';
        }
        $candidates = array_merge($names, array_map(static fn (string $name): string => '/'.$name, $names));

        foreach ($patterns as $pattern) {
            foreach ($candidates as $candidate) {
                if (fnmatch($pattern, $candidate)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Rejects files larger than the configured limit to avoid loading huge
     * (potentially malicious) files entirely into memory.
     */
    private function isWithinSizeLimit(string $file): bool
    {
        $size = filesize($file);
        // @codeCoverageIgnoreStart
        if (false === $size) {
            $this->logger?->warning(
                sprintf('Unable to determine file size, skipping: %s', $file),
            );

            return false;
        }
        // @codeCoverageIgnoreEnd

        if ($size > self::MAX_FILE_SIZE) {
            $this->logger?->warning(
                sprintf('Skipping file exceeding the maximum size of %d bytes: %s', self::MAX_FILE_SIZE, $file),
            );

            return false;
        }

        return true;
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

        // @codeCoverageIgnoreStart
        return rtrim($path, '/\\');
        // @codeCoverageIgnoreEnd
    }

    /**
     * Basic path safety check to prevent obvious security issues.
     *
     * Rejects sensitive system locations. Matching is done on path boundaries
     * (so `/etc` does not reject `/etcetera`) and case-insensitively to also
     * cover Windows system directories.
     */
    private function isPathSafe(string $path): bool
    {
        $normalized = rtrim(str_replace('\\', '/', $path), '/');
        $haystack = strtolower($normalized).'/';

        $dangerousPaths = [
            '/etc', '/usr', '/bin', '/sbin', '/proc', '/sys', '/dev', '/root', '/boot',
            '/private/etc',
            'c:/windows', 'c:/program files', 'c:/program files (x86)',
        ];

        foreach ($dangerousPaths as $dangerousPath) {
            if (str_starts_with($haystack, $dangerousPath.'/')) {
                return false;
            }
        }

        return substr_count($normalized, '/') <= 20;
    }

    /**
     * @param array<string> $files
     *
     * @return array<string, array<mixed>> non-empty translation sets keyed by directory
     */
    private function mapWithDetector(DetectorInterface $detector, string $path, array $files): array
    {
        // DirectoryFileDetector pairs files across sibling language directories
        $filesByDirectory = $detector instanceof DirectoryFileDetector
            ? [$path => $files]
            : $this->groupFilesByDirectory($files);

        $translationSets = [];
        foreach ($filesByDirectory as $directory => $directoryFiles) {
            $translationSet = $detector->mapTranslationSet($directoryFiles);
            if (!empty($translationSet)) {
                $translationSets[$directory] = $translationSet;
            }
        }

        return $translationSets;
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
