<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Command;

use Composer\Command\BaseCommand;
use MoveElevator\ComposerTranslationValidator\FileDetector\Collector;
use MoveElevator\ComposerTranslationValidator\FileDetector\DetectorInterface;
use MoveElevator\ComposerTranslationValidator\Utility\ClassUtility;
use MoveElevator\ComposerTranslationValidator\Utility\PathUtility;
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
                'cli'
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
                'validator',
                'vd',
                InputOption::VALUE_OPTIONAL,
                'The specific validator to use (FQCN)'
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

        $fileDetector = $this->validateAndInstantiate(
            DetectorInterface::class,
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

        if (!ClassUtility::validateClass(
            ValidatorInterface::class,
            $this->logger,
            $input->getOption('validator'))
        ) {
            $this->io->error(
                sprintf('The validator class "%s" must implement %s.',
                    $input->getOption('validator'),
                    ValidatorInterface::class
                )
            );

            return Command::FAILURE;
        }

        $this->validateAndInstantiate(
            ValidatorInterface::class,
            'validator',
            $input->getOption('validator')
        );

        $validators = $input->getOption('validator') ? [$input->getOption('validator')] : ValidatorRegistry::getAvailableValidators();
        $issues = [];

        // ToDo: Simplify this nested loop structure
        foreach ($allFiles as $parser => $paths) {
            foreach ($paths as $path => $translationSets) {
                foreach ($translationSets as $setKey => $files) {
                    foreach ($validators as $validator) {
                        $validatorInstance = new $validator($this->logger);
                        $result = $validatorInstance->validate($files, $parser);
                        if ($result) {
                            $this->resultType = $this->resultType->max($validatorInstance->resultTypeOnValidationFailure());
                            $issues[$validator][$path][$setKey] = $result;
                        }
                    }
                }
            }
        }

        $format = $input->getOption('format');

        return $this->summarize($issues, $format);
    }

    /**
     * Summarizes validation results in the specified format.
     *
     * @param array<class-string<ValidatorInterface>, array<string, array<string, array<mixed>>>> $issues
     * @param string                                                                              $format Output format ('cli' or 'json')
     *
     * @return int Command exit code
     *
     * @throws \JsonException
     */
    private function summarize(array $issues, string $format = 'cli'): int
    {
        if ('json' === $format) {
            return $this->renderJsonResult($issues);
        }
        if ('cli' === $format) {
            return $this->renderCliResult($issues);
        }
        $this->io->error('Invalid output format specified. Use "cli" or "json".');

        return Command::FAILURE;
    }

    /**
     * Renders validation results as JSON output.
     *
     * @param array<class-string<ValidatorInterface>, array<string, array<string, array<mixed>>>> $issues
     *
     * @return int Command exit code
     *
     * @throws \JsonException
     */
    private function renderJsonResult(array $issues): int
    {
        $result = [
            'status' => Command::SUCCESS,
            'message' => 'Language validation succeeded.',
            'issues' => $issues,
        ];

        if (!empty($issues)) {
            $result['message'] = 'Language validation failed.';
            $result['status'] = Command::FAILURE;
            if ($this->dryRun) {
                $result['message'] = 'Language validation failed and completed in dry-run mode.';
                $result['status'] = Command::SUCCESS;
            }
        }

        $this->output->writeln(json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $result['status'];
    }

    /**
     * @param array<class-string<ValidatorInterface>, array<string, array<string, array<mixed>>>> $issues
     */
    private function renderCliResult(array $issues): int
    {
        $this->renderIssues($issues);
        if ($this->resultType->notFullySuccessful()) {
            $this->io->newLine();
            $this->io->{$this->dryRun ? 'warning' : 'error'}(
                $this->dryRun
                    ? 'Language validation failed and completed in dry-run mode.'
                    : 'Language validation failed.'
            );
        } else {
            $message = 'Language validation succeeded.';
            $this->output->isVerbose()
                ? $this->io->success($message)
                : $this->output->writeln('<fg=green>'.$message.'</>');
        }

        return $this->resultType->resolveErrorToCommandExitCode($this->dryRun, $this->strict);
    }

    /**
     * Renders validation issues using validator-specific formatters.
     *
     * @param array<class-string<ValidatorInterface>, array<string, array<string, array<mixed>>>> $issues
     */
    private function renderIssues(array $issues): void
    {
        foreach ($issues as $validator => $paths) {
            $validatorInstance = new $validator($this->logger);
            /* @var ValidatorInterface $validatorInstance */

            $this->io->section(sprintf('Validator: <fg=cyan>%s</>', $validator));
            foreach ($paths as $path => $sets) {
                if ($this->output->isVerbose()) {
                    $this->io->writeln(sprintf('Explanation: %s', $validatorInstance->explain()));
                }
                $this->io->writeln(sprintf('<fg=gray>Folder Path: %s</>', PathUtility::normalizeFolderPath($path)));
                $this->io->newLine();
                $validatorInstance->renderIssueSets(
                    $this->input,
                    $this->output,
                    $sets
                );

                $this->io->newLine();
                $this->io->newLine();
            }
        }
    }

    private function validateAndInstantiate(string $interface, string $type, ?string $className = null): ?object
    {
        if (null === $className) {
            return null;
        }

        if (!ClassUtility::validateClass($interface, $this->logger, $className)) {
            $this->io->error(
                sprintf('The %s class "%s" must implement %s.', $type, $className, $interface)
            );

            return null;
        }

        return new $className();
    }
}
