<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Command;

use Composer\Command\BaseCommand;
use MoveElevator\ComposerTranslationValidator\FileDetector\Collector;
use MoveElevator\ComposerTranslationValidator\FileDetector\DetectorInterface;
use MoveElevator\ComposerTranslationValidator\Result\FormatType;
use MoveElevator\ComposerTranslationValidator\Result\Output;
use MoveElevator\ComposerTranslationValidator\Result\ValidationRun;
use MoveElevator\ComposerTranslationValidator\Utility\ClassUtility;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ValidateTranslationCommand extends BaseCommand
{
    protected ?SymfonyStyle $io = null;
    protected ?InputInterface $input = null;
    protected ?OutputInterface $output = null;

    protected LoggerInterface $logger;

    protected ResultType $resultType = ResultType::SUCCESS;
    protected bool $dryRun = false;
    protected bool $strict = false;

    protected function configure(): void
    {
        $this->setName('validate-translations')
            ->setDescription('Validates translation files with several validators.')
            ->addArgument(
                'path',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Paths to the folders containing translation files'
            )
            ->addOption(
                'dry-run',
                'dr',
                InputOption::VALUE_NONE,
                'Run the command in dry-run mode without throwing errors'
            )
            ->addOption(
                'strict',
                null,
                InputOption::VALUE_NONE,
                'Fail on warnings as errors'
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Output format: cli or json',
                FormatType::CLI->value
            )
            ->addOption(
                'exclude',
                'e',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Patterns to exclude specific files'
            )
            ->addOption(
                'file-detector',
                'fd',
                InputOption::VALUE_OPTIONAL,
                'The file detector to use (FQCN)'
            )
            ->addOption(
                'only',
                'o',
                InputOption::VALUE_OPTIONAL,
                'The specific validators to use (FQCN), comma-separated'
            )
            ->addOption(
                'skip',
                's',
                InputOption::VALUE_OPTIONAL,
                'Skip specific validators (FQCN), comma-separated'
            );
    }

    /**
     * @throws \ReflectionException|\JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger = new ConsoleLogger($output);

        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);

        $paths = array_map(static fn ($path) => str_starts_with((string) $path, '/') ? $path : getcwd().'/'.$path, $input->getArgument('path'));

        $this->dryRun = $input->getOption('dry-run');
        $this->strict = $input->getOption('strict');
        $excludePatterns = $input->getOption('exclude');

        $fileDetector = ClassUtility::instantiate(
            DetectorInterface::class,
            $this->logger,
            'file detector',
            $input->getOption('file-detector')
        );

        if (empty($paths)) {
            $this->io->error('No paths provided.');

            return Command::FAILURE;
        }

        $allFiles = (new Collector($this->logger))->collectFiles($paths, $fileDetector, $excludePatterns);
        if (empty($allFiles)) {
            $this->io->warning('No files found in the specified directories.');

            return Command::SUCCESS;
        }

        $validators = $this->resolveValidators($input);
        $fileSets = ValidationRun::createFileSetsFromArray($allFiles);

        $validationRun = new ValidationRun($this->logger);
        $validationResult = $validationRun->executeFor($fileSets, $validators);

        $format = FormatType::tryFrom($input->getOption('format'));

        if (null === $format) {
            $this->io->error('Invalid output format specified. Use "cli" or "json".');

            return Command::FAILURE;
        }

        return (new Output(
            $this->logger,
            $this->output,
            $this->input,
            $format,
            $validationResult,
            $this->dryRun,
            $this->strict
        ))->summarize();
    }

    /**
     * @return array<int, class-string<ValidatorInterface>>
     */
    private function resolveValidators(InputInterface $input): array
    {
        $only = $this->validateClassInput(
            ValidatorInterface::class,
            'validator',
            $input->getOption('only')
        );
        $skip = $this->validateClassInput(
            ValidatorInterface::class,
            'validator',
            $input->getOption('skip')
        );

        if (!empty($only)) {
            $validators = $only;
        } elseif (!empty($skip)) {
            $validators = array_diff(ValidatorRegistry::getAvailableValidators(), $skip);
        } else {
            $validators = ValidatorRegistry::getAvailableValidators();
        }

        return $validators;
    }

    /**
     * @return array<int, class-string>
     */
    private function validateClassInput(string $interface, string $type, ?string $className = null): array
    {
        if (null === $className) {
            return [];
        }
        $classes = [];

        if (str_contains($className, ',')) {
            $classNames = explode(',', $className);
            foreach ($classNames as $name) {
                ClassUtility::instantiate($interface, $this->logger, $type, $name);
                $classes[] = $name;
            }
        } else {
            ClassUtility::instantiate($interface, $this->logger, $type, $className);
            $classes[] = $className;
        }

        return $classes;
    }
}
