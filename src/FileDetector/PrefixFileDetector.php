<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\FileDetector;

class PrefixFileDetector implements DetectorInterface
{
    /**
    * @param array<int, string> $files
    *
    * @return array<string, array<int, string>>
    */
    public function mapTranslationSet(array $files): array
    {
        $mapping = [];
        $sourceFiles = $this->findSourceFiles($files);

        foreach ($sourceFiles as $sourceFile) {
            $sourceBaseName = basename($sourceFile);
            $mapping[$sourceFile] = [];

            foreach ($files as $file) {
                if ($file === $sourceFile) {
                    continue;
                }

                if (str_contains($file, $sourceBaseName) && dirname($file) === dirname($sourceFile)) {
                    $mapping[$sourceFile][] = $file;
                }
            }
        }

        return $mapping;
    }

    /**
    * @param array<int, string> $files
    *
    * @return array<int, string>
    */
    private function findSourceFiles(array $files): array
    {
        $sourceFiles = [];
        foreach ($files as $file) {
            $basename = basename($file);
            if (!preg_match('/^[a-z]{2}\./', $basename)) {
                $sourceFiles[] = $file;
            }
        }

        return $sourceFiles;
    }
}
