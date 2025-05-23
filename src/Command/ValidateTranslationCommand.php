<?php

declare(strict_types=1);

namespace KonradMichalik\ComposerTranslationValidator\Command;

use Composer\Command\BaseCommand;
use KonradMichalik\ComposerTranslationValidator\FileDetector\DetectorInterface;
use KonradMichalik\ComposerTranslationValidator\FileDetector\PrefixFileDetector;
use KonradMichalik\ComposerTranslationValidator\Parser\ParserInterface;
use KonradMichalik\ComposerTranslationValidator\Parser\XliffParser;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class ValidateTranslationCommand extends BaseCommand
{
    protected ?SymfonyStyle $io = null;
    protected ?OutputInterface $output = null;
    protected bool $dryRun = false;
    protected bool $hasErrors = false;

    protected function configure(): void
    {
        $this->setName('validate-translations')
            ->setDescription('Validates XLIFF files and checks if all target files have the same keys as the source file.')
            ->addArgument('path', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Paths to the folders containing XLIFF files')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run the command in dry-run mode without throwing errors')
            ->addOption('exclude-pattern', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Patterns to exclude specific files')
            ->addOption('file-detector', null, InputOption::VALUE_REQUIRED, 'The file detector to use (FQCN)', PrefixFileDetector::class)
            ->addOption('parser', null, InputOption::VALUE_REQUIRED, 'The parser to use (FQCN)', XliffParser::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);
        $paths = array_map(static function ($path) {
            return str_starts_with($path, '/') ? $path : getcwd().'/'.$path;
        }, $input->getArgument('path'));

        $this->dryRun = $input->getOption('dry-run');
        $excludePatterns = $input->getOption('exclude-pattern');
        $filesystem = new Filesystem();

        if (!$this->validateClass($input->getOption('file-detector'), DetectorInterface::class)) {
            return 1;
        }
        $fileDetector = new ($input->getOption('file-detector'))();

        if (!$this->validateClass($input->getOption('parser'), ParserInterface::class)) {
            return 1;
        }
        $parserClass = $input->getOption('parser');

        if (empty($paths)) {
            $this->io->error('No paths provided.');

            return 1;
        }

        $allFiles = $this->collectFiles($paths, $parserClass::getSupportedFileExtensions(), $excludePatterns, $filesystem);
        if (empty($allFiles)) {
            $this->io->warning('No files found in the specified directories.');

            return 0;
        }

        $this->hasErrors = $this->validateFiles($fileDetector, $parserClass, $allFiles);

        return $this->finalizeValidation();
    }

    private function validateClass(string $class, string $interface): bool
    {
        if (!class_exists($class)) {
            $this->io->error(sprintf('The class "%s" does not exist.', $class));

            return false;
        }

        if (!is_subclass_of($class, $interface)) {
            $this->io->error(sprintf('The class "%s" must implement %s.', $class, $interface));

            return false;
        }

        return true;
    }

    private function collectFiles(array $paths, array $extensions, ?array $excludePatterns, Filesystem $filesystem): array
    {
        $allFiles = [];
        foreach ($paths as $path) {
            if (!$filesystem->exists($path)) {
                $this->io->error('The provided path "'.$path.'" is not a valid directory.');

                return [];
            }

            $files = array_filter(glob($path.'/*'), fn ($file) => in_array(pathinfo($file, PATHINFO_EXTENSION), $extensions, true));
            $allFiles = [...$allFiles, ...$files];
        }

        if ($excludePatterns) {
            $allFiles = array_filter($allFiles, fn ($file) => !array_filter($excludePatterns, fn ($pattern) => fnmatch($pattern, basename($file))));
        }

        return $allFiles;
    }

    private function validateFiles($fileDetector, string $parserClass, array $allFiles): bool
    {
        $hasErrors = false;

        foreach ($fileDetector->mapTranslationSet($allFiles) as $sourceFile => $targetFiles) {
            $source = new $parserClass($sourceFile);
            $this->output->write('> Checking language source file: <fg=cyan>'.$source->getFileName().'</> ...');
            $sourceKeys = $source->extractKeys();

            if (!$sourceKeys) {
                $this->io->error('The source file '.$sourceFile.' is not valid.');
                $hasErrors = true;
                continue;
            }

            foreach ($targetFiles as $targetFile) {
                $target = new $parserClass($targetFile);
                $missingKeys = array_diff($sourceKeys, $target->extractKeys());

                if ($missingKeys) {
                    $this->io->warning('Found missing keys in '.$target->getFileName());
                    $this->io->table(
                        ['Language Key', $source->getFileName(), $target->getFileName()],
                        array_map(fn ($key) => [$key, $source->getContentByKey($key), $target->getContentByKey($key)], $missingKeys)
                    );
                    $hasErrors = true;
                } else {
                    $this->output->writeln(' <fg=green>✔</>');
                }
            }
        }

        return $hasErrors;
    }

    private function finalizeValidation(): int
    {
        if ($this->hasErrors) {
            if ($this->dryRun) {
                $this->io->warning('Language validation completed in dry-run mode. Missing keys were found.');

                return 0;
            }

            $this->io->error('Language validation failed. Missing keys were found.');
            return 1;
        }

        $message = 'Language validation succeeded. All keys are present in the target files.';
        $this->dryRun ? $this->io->success($message) : $this->output->writeln('<fg=green>'.$message.'</>');

        return 0;
    }
}
