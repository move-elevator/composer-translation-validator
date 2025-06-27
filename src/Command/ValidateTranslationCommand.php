<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Command;

use Composer\Command\BaseCommand;
use MoveElevator\ComposerTranslationValidator\FileDetector\DetectorInterface;
use MoveElevator\ComposerTranslationValidator\FileDetector\PrefixFileDetector;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\ParserUtility;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
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
            ->addOption('parser', null, InputOption::VALUE_REQUIRED, 'The parser to use (FQCN)', XliffParser::class)
            ->addOption('validator', null, InputOption::VALUE_OPTIONAL, 'The specific validator to use (FQCN)');
    }

    /**
     * @throws \ReflectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);
        $paths = array_map(static fn ($path) => str_starts_with((string) $path, '/') ? $path : getcwd().'/'.$path, $input->getArgument('path'));

        $this->dryRun = $input->getOption('dry-run');
        $excludePatterns = $input->getOption('exclude');
        $filesystem = new Filesystem();

        if (!$this->validateClass($input->getOption('file-detector'), DetectorInterface::class)) {
            $this->io->error(sprintf('The file detector class "%s" must implement %s.', $input->getOption('file-detector'), DetectorInterface::class));

            return 1;
        }
        $fileDetector = new ($input->getOption('file-detector'))();

        if (!$this->validateClass($input->getOption('parser'), ParserInterface::class)) {
            $this->io->error(sprintf('The parser class "%s" must implement %s.', $input->getOption('parser'), ParserInterface::class));

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

        $validators = ParserUtility::resolveValidators();
        if ($input->getOption('validator')) {
            if (!$this->validateClass($input->getOption('validator'), ValidatorInterface::class)) {
                $this->io->error(sprintf('The validator class "%s" must implement %s.', $input->getOption('validator'), ValidatorInterface::class));

                return 1;
            }
            $validators = [$input->getOption('validator')];
        }

        foreach ($validators as $validatorClass) {
            $validator = new $validatorClass($input, $output);
            $validationResult = $validator->validate($fileDetector, $parserClass, $allFiles);
            $this->hasErrors = $this->hasErrors || $validationResult;
        }

        return $this->summary();
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

    /**
     * @param array<int, string>      $paths
     * @param array<int, string>      $extensions
     * @param array<int, string>|null $excludePatterns
     *
     * @return array<int, string>
     */
    private function collectFiles(array $paths, array $extensions, ?array $excludePatterns, Filesystem $filesystem): array
    {
        $allFiles = [];
        foreach ($paths as $path) {
            if (!$filesystem->exists($path)) {
                $this->io->error('The provided path "'.$path.'" is not a valid directory.');

                return [];
            }

            $files = array_filter(glob($path.'/*'), static fn ($file) => in_array(pathinfo($file, PATHINFO_EXTENSION), $extensions, true));
            $allFiles = [...$allFiles, ...$files];
        }

        if ($excludePatterns) {
            $allFiles = array_filter($allFiles, static fn ($file) => !array_filter($excludePatterns, static fn ($pattern) => fnmatch($pattern, basename($file))));
        }

        return $allFiles;
    }

    private function summary(): int
    {
        if ($this->hasErrors) {
            if ($this->dryRun) {
                $this->io->newLine();
                $this->io->warning('Language validation failed and completed in dry-run mode.');

                return 0;
            }

            $this->io->newLine();
            $this->io->error('Language validation failed.');

            return 1;
        }

        $message = 'Language validation succeeded.';
        $this->output->isVerbose() ? $this->io->success($message) : $this->output->writeln('<fg=green>'.$message.'</>');

        return 0;
    }
}
