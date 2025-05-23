<?php

declare(strict_types=1);

namespace KonradMichalik\ComposerTranslationValidator\Command;

use Composer\Command\BaseCommand;
use KonradMichalik\ComposerTranslationValidator\FileDetector\DetectorInterface;
use KonradMichalik\ComposerTranslationValidator\FileDetector\PrefixFileDetector;
use KonradMichalik\ComposerTranslationValidator\Parser\ParserInterface;
use KonradMichalik\ComposerTranslationValidator\Parser\ParserUtility;
use KonradMichalik\ComposerTranslationValidator\Parser\XliffParser;
use Symfony\Component\Console\Helper\Table;
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
            ->addOption('exclude', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Patterns to exclude specific files')
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
        $excludePatterns = $input->getOption('exclude');
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

        $allFiles = $this->collectFiles($paths, ParserUtility::resolveAllowedFileExtensions(), $excludePatterns, $filesystem);
        if (empty($allFiles)) {
            $this->io->warning('No files found in the specified directories.');

            return 0;
        }

        $this->hasErrors = $this->validateFiles($fileDetector, $parserClass, $allFiles);

        return $this->finalizeValidation();
    }

    private function validateClass(?string $class, string $interface): bool
    {
        if (is_null($class)) {
            return true;
        }

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

    private function validateFiles($fileDetector, ?string $parserClass, array $allFiles): bool
    {
        $hasErrors = false;

        foreach ($fileDetector->mapTranslationSet($allFiles) as $sourceFile => $targetFiles) {
            $source = new ($parserClass ?: ParserUtility::resolveParserClass($sourceFile))($sourceFile);
            $sourceHasErrors = false;
            $this->debug('> Checking language source file: <fg=gray>'.$source->getFileDirectory().'</><fg=cyan>'.$source->getFileName().'</> ...', newLine: $this->output->isVeryVerbose());
            $sourceKeys = $source->extractKeys();

            if (!$sourceKeys) {
                $this->io->error('The source file '.$sourceFile.' is not valid.');
                $hasErrors = true;
                continue;
            }

            foreach ($targetFiles as $targetFile) {
                $target = new $parserClass($targetFile);
                $this->debug('> Checking language target file: '.$target->getFileDirectory().$target->getFileName().' ...', veryVerbose: true);
                $missingKeys = array_diff($sourceKeys, $target->extractKeys());

                if ($missingKeys) {
                    $this->debug('> Validation result: ', veryVerbose: true, newLine: false);
                    $this->debug(' <fg=red>✘</>');
                    $this->io->warning('Found missing keys in '.$target->getFilePath());
                    $table = new Table($this->output);
                    $table
                        ->setColumnWidths([10, 30, 30])
                        ->setHeaderTitle($target->getFileName())
                        ->setHeaders(['Language Key', $source->getLanguage(), $target->getLanguage()])
                        ->setRows(array_map(fn ($key) => [$key, $source->getContentByKey($key), $target->getContentByKey($key)], $missingKeys))
                        ->setStyle('box')
                        ->render()
                    ;

                    $hasErrors = true;
                    $sourceHasErrors = true;
                }
            }

            if (!$sourceHasErrors) {
                $this->debug(sprintf('> Validation statistic: 1 source, %d target(s), %d language keys', count($targetFiles), count($sourceKeys)), veryVerbose: true);
                $this->debug('> Validation result: ', veryVerbose: true, newLine: false);
                $this->debug(' <fg=green>✔</>');
                $this->debug('', veryVerbose: true);
            }
        }

        return $hasErrors;
    }

    private function finalizeValidation(): int
    {
        if ($this->hasErrors) {
            if ($this->dryRun) {
                $this->io->newLine();
                $this->io->warning('Language validation completed in dry-run mode. Missing keys were found.');

                return 0;
            }

            $this->io->newLine();
            $this->io->error('Language validation failed. Missing keys were found.');

            return 1;
        }

        $message = 'Language validation succeeded. All keys are present in the target files.';
        $this->output->isVerbose() ? $this->io->success($message) : $this->output->writeln('<fg=green>'.$message.'</>');

        return 0;
    }

    private function debug(string $message, bool $veryVerbose = false, bool $newLine = true): void
    {
        if (!$this->output->isVerbose()) {
            return;
        }

        if (!$this->output->isVeryVerbose() && $veryVerbose) {
            return;
        }

        if ($veryVerbose) {
            $message = '<fg=gray>'.$message.'</>';
        }
        $newLine ? $this->io->writeln($message) : $this->io->write($message);
    }
}
