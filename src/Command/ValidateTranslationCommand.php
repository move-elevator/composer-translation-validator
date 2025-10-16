<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Command;

use Composer\Command\BaseCommand;
use JsonException;
use MoveElevator\ComposerTranslationValidator\Config\{ConfigReader, TranslationValidatorConfig};
use MoveElevator\ComposerTranslationValidator\Result\{FormatType, Output};
use MoveElevator\ComposerTranslationValidator\Service\ValidationOrchestrationService;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;
use ReflectionException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface, InputOption};
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * ValidateTranslationCommand.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class ValidateTranslationCommand extends BaseCommand
{
    protected ?SymfonyStyle $io = null;
    protected ?InputInterface $input = null;
    protected ?OutputInterface $output = null;

    protected LoggerInterface $logger;
    protected ValidationOrchestrationService $orchestrationService;

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
                'Output format: cli, json, or github',
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
  <info>composer validate-translations translations/ --format github</info>
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
  • <info>cli</info>    - Human-readable console output (default)
  • <info>json</info>   - Machine-readable JSON output
  • <info>github</info> - GitHub Actions workflow commands for CI integration

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
        $this->orchestrationService = new ValidationOrchestrationService($this->logger);
    }

    /**
     * @throws ReflectionException|JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $this->loadConfiguration($input);

        $inputPaths = $input->getArgument('path') ?: [];
        $paths = $this->orchestrationService->resolvePaths($inputPaths, $config);

        $this->dryRun = $config->getDryRun() || $input->getOption('dry-run');
        $this->strict = $config->getStrict() || $input->getOption('strict');
        $recursive = (bool) $input->getOption('recursive');
        $excludePatterns = $config->getExclude();

        $fileDetector = $this->orchestrationService->resolveFileDetector($config);

        if (empty($paths)) {
            $this->io?->error('No paths provided.');

            return Command::FAILURE;
        }

        $onlyValidators = $this->orchestrationService->validateClassInput(
            ValidatorInterface::class,
            'validator',
            $input->getOption('only'),
        );
        $skipValidators = $this->orchestrationService->validateClassInput(
            ValidatorInterface::class,
            'validator',
            $input->getOption('skip'),
        );

        $validators = $this->orchestrationService->resolveValidators($onlyValidators, $skipValidators, $config);

        $validationResult = $this->orchestrationService->executeValidation(
            $paths,
            $excludePatterns,
            $recursive,
            $fileDetector,
            $validators,
            $config,
        );

        if (null === $validationResult) {
            $this->io?->warning('No files found in the specified directories.');

            return Command::SUCCESS;
        }

        $format = FormatType::tryFrom($input->getOption('format') ?: $config->getFormat());

        if (null === $format) {
            $this->io?->error('Invalid output format specified. Use "cli", "json" or "github".');

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

    /**
     * @throws JsonException
     */
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
}
