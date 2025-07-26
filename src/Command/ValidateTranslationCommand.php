<?php

declare(strict_types=1);

/*
 * This file is part of the Composer plugin "composer-translation-validator".
 *
 * Copyright (C) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace MoveElevator\ComposerTranslationValidator\Command;

use Composer\Command\BaseCommand;
use JsonException;
use MoveElevator\ComposerTranslationValidator\Config\ConfigReader;
use MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig;
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
use ReflectionException;
use RuntimeException;
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
            ->setAliases(['vt'])
            ->setDescription('Validates translation files with several validators.')
            ->addArgument(
                'path',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Paths to the folders containing translation files',
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Run the command in dry-run mode without throwing errors',
            )
            ->addOption(
                'strict',
                null,
                InputOption::VALUE_NONE,
                'Fail on warnings as errors',
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Output format: cli or json',
                FormatType::CLI->value,
            )
            ->addOption(
                'only',
                'o',
                InputOption::VALUE_OPTIONAL,
                'The specific validators to use (FQCN), comma-separated',
            )
            ->addOption(
                'skip',
                's',
                InputOption::VALUE_OPTIONAL,
                'Skip specific validators (FQCN), comma-separated',
            )
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Path to the configuration file',
            )
            ->addOption(
                'recursive',
                'r',
                InputOption::VALUE_NONE,
                'Search for translation files recursively in subdirectories',
            )
            ->setHelp(
                <<<HELP
The <info>validate-translations</info> command validates translation files (XLIFF, YAML, JSON and PHP)
using multiple validators to ensure consistency, correctness and schema compliance.

<comment>Usage:</comment>
  <info>composer validate-translations <path> [options]</info>

<comment>Examples:</comment>
  <info>composer validate-translations translations/</info>
  <info>composer validate-translations translations/ --recursive</info>
  <info>composer validate-translations translations/ -r --format json</info>
  <info>composer validate-translations translations/ --dry-run</info>
  <info>composer validate-translations translations/ --strict</info>
  <info>composer validate-translations translations/ --only \</info>
    <info>"MoveElevator\ComposerTranslationValidator\Validator\DuplicateKeysValidator"</info>

<comment>Available Validators:</comment>
  • <info>MismatchValidator</info>        - Detects mismatches between source and target
  • <info>DuplicateKeysValidator</info>   - Finds duplicate translation keys
  • <info>DuplicateValuesValidator</info> - Finds duplicate translation values
  • <info>EmptyValuesValidator</info>     - Finds empty or whitespace-only translation values
  • <info>EncodingValidator</info>        - Validates file encoding and character issues
  • <info>HtmlTagValidator</info>         - Validates HTML tag consistency across translations
  • <info>KeyNamingConventionValidator</info> - Validates translation key naming conventions
  • <info>PlaceholderConsistencyValidator</info> - Validates placeholder consistency across files
  • <info>XliffSchemaValidator</info>     - Validates XLIFF schema compliance

<comment>Configuration:</comment>
You can configure the validator using:
  1. Command line options
  2. A configuration file (--config option)
  3. Settings in composer.json under "extra.translation-validator"
  4. Auto-detection from project structure

<comment>Output Formats:</comment>
  • <info>cli</info>  - Human-readable console output (default)
  • <info>json</info> - Machine-readable JSON output

<comment>Modes:</comment>
  • <info>--dry-run</info> - Run validation without failing on errors
  • <info>--strict</info>  - Treat warnings as errors
HELP
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);
        $this->logger = new ConsoleLogger($output);
    }

    /**
     * @throws ReflectionException|JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $this->loadConfiguration($input);

        $paths = $this->resolvePaths($input, $config);

        $this->dryRun = $config->getDryRun() || $input->getOption('dry-run');
        $this->strict = $config->getStrict() || $input->getOption('strict');
        $recursive = (bool) $input->getOption('recursive');
        $excludePatterns = $config->getExclude();

        $fileDetector = $this->resolveFileDetector($config);

        if (empty($paths)) {
            $this->io?->error('No paths provided.');

            return Command::FAILURE;
        }

        $allFiles = (new Collector($this->logger))->collectFiles(
            $paths,
            $fileDetector,
            $excludePatterns,
            $recursive,
        );
        if (empty($allFiles)) {
            $this->io?->warning('No files found in the specified directories.');

            return Command::SUCCESS;
        }

        $validators = $this->resolveValidators($input, $config);
        $fileSets = ValidationRun::createFileSetsFromArray($allFiles);

        $validationRun = new ValidationRun($this->logger);
        $validationResult = $validationRun->executeFor($fileSets, $validators, $config);

        $format = FormatType::tryFrom($input->getOption('format') ?: $config->getFormat());

        if (null === $format) {
            $this->io?->error('Invalid output format specified. Use "cli" or "json".');

            return Command::FAILURE;
        }

        if (null === $this->output || null === $this->input) {
            throw new RuntimeException('Output or Input interface not initialized');
        }

        return (new Output(
            $this->logger,
            $this->output,
            $this->input,
            $format,
            $validationResult,
            $this->dryRun,
            $this->strict,
        ))->summarize();
    }

    private function loadConfiguration(InputInterface $input): TranslationValidatorConfig
    {
        $configReader = new ConfigReader();
        $configPath = $input->getOption('config');

        if ($configPath) {
            return $configReader->read($configPath);
        }

        // Try to load from composer.json
        $composerJsonPath = getcwd().'/composer.json';
        $config = $configReader->readFromComposerJson($composerJsonPath);
        if ($config) {
            return $config;
        }

        // Try auto-detection
        $config = $configReader->autoDetect();
        if ($config) {
            return $config;
        }

        // Return default configuration
        return new TranslationValidatorConfig();
    }

    /**
     * @return string[]
     */
    private function resolvePaths(InputInterface $input, TranslationValidatorConfig $config): array
    {
        $inputPaths = $input->getArgument('path');
        $configPaths = $config->getPaths();

        $paths = !empty($inputPaths) ? $inputPaths : $configPaths;

        return array_map(
            static fn ($path) => str_starts_with((string) $path, '/')
                ? $path
                : getcwd().'/'.$path,
            $paths,
        );
    }

    private function resolveFileDetector(TranslationValidatorConfig $config): ?DetectorInterface
    {
        $configFileDetectors = $config->getFileDetectors();
        $fileDetectorClass = !empty($configFileDetectors) ? $configFileDetectors[0] : null;

        $detector = ClassUtility::instantiate(
            DetectorInterface::class,
            $this->logger,
            'file detector',
            $fileDetectorClass,
        );

        return $detector instanceof DetectorInterface ? $detector : null;
    }

    /**
     * @return array<int, class-string<ValidatorInterface>>
     */
    private function resolveValidators(
        InputInterface $input,
        TranslationValidatorConfig $config,
    ): array {
        $inputOnly = $this->validateClassInput(
            ValidatorInterface::class,
            'validator',
            $input->getOption('only'),
        );
        $inputSkip = $this->validateClassInput(
            ValidatorInterface::class,
            'validator',
            $input->getOption('skip'),
        );

        $only = !empty($inputOnly) ? $inputOnly : $config->getOnly();
        $skip = !empty($inputSkip) ? $inputSkip : $config->getSkip();

        /** @var array<int, class-string<ValidatorInterface>> $result */
        $result = match (true) {
            !empty($only) => $only,
            !empty($skip) => array_values(array_diff(ValidatorRegistry::getAvailableValidators(), $skip)),
            default => ValidatorRegistry::getAvailableValidators(),
        };

        return $result;
    }

    /**
     * @return array<int, class-string>
     */
    private function validateClassInput(
        string $interface,
        string $type,
        ?string $className = null,
    ): array {
        if (null === $className) {
            return [];
        }

        $classNames = str_contains($className, ',') ? explode(',', $className) : [$className];
        /** @var array<int, class-string> $classes */
        $classes = [];

        foreach ($classNames as $name) {
            ClassUtility::instantiate(
                $interface,
                $this->logger,
                $type,
                $name,
            );
            /** @var class-string $validatedName */
            $validatedName = $name;
            $classes[] = $validatedName;
        }

        return $classes;
    }
}
