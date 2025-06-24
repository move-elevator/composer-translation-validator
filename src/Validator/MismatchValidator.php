<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\FileDetector\DetectorInterface;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\ParserUtility;
use MoveElevator\ComposerTranslationValidator\Utility\OutputUtility;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MismatchValidator implements ValidatorInterface
{
    private SymfonyStyle $io;

    public function __construct(
        protected readonly InputInterface $input,
        protected readonly OutputInterface $output,
    ) {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @param array<int, string> $allFiles
     *
     * @throws \ReflectionException
     */
    public function validate(DetectorInterface $fileDetector, ?string $parserClass, array $allFiles): bool
    {
        $hasErrors = false;

        OutputUtility::debug(
            $this->output,
            '> Checking for <options=bold,underscore>language mismatch</> ...'
        );

        foreach ($fileDetector->mapTranslationSet($allFiles) as $sourceFile => $targetFiles) {
            /* @var ParserInterface $source */
            $source = new ($parserClass ?: ParserUtility::resolveParserClass($sourceFile))($sourceFile);
            $sourceHasErrors = false;

            OutputUtility::debug(
                $this->output,
                '> Checking language source file: <fg=gray>'.$source->getFileDirectory().'</><fg=cyan>'.$source->getFileName().'</> ...',
                newLine: $this->output->isVeryVerbose()
            );

            $sourceKeys = $source->extractKeys();

            if (!$sourceKeys) {
                $this->io->error('The source file '.$sourceFile.' is not valid.');
                $hasErrors = true;
                continue;
            }

            foreach ($targetFiles as $targetFile) {
                /* @var ParserInterface $target */
                $target = new $parserClass($targetFile);
                OutputUtility::debug(
                    $this->output,
                    '> Checking language target file: '.$target->getFileDirectory().$target->getFileName().' ...',
                    veryVerbose: true
                );

                $missingKeys = [...array_diff($sourceKeys, $target->extractKeys()), ...array_diff($target->extractKeys(), $sourceKeys)];

                if (!empty($missingKeys)) {
                    OutputUtility::debug(
                        $this->output,
                        '> Validation result: ',
                        veryVerbose: true,
                        newLine: false
                    );
                    OutputUtility::debug(
                        $this->output,
                        ' <fg=red>✘</>'
                    );

                    $this->io->warning('Found missing keys in '.$target->getFilePath());

                    $table = new Table($this->output);
                    $table
                        ->setColumnWidths([10, 30, 30])
                        ->setHeaderTitle('Missing Keys')
                        ->setHeaders(['Language Key', $source->getLanguage(), $target->getLanguage()])
                        ->setRows(array_map(static fn ($key) => [$key, $source->getContentByKey($key) ?: '<fg=yellow>–</>', $target->getContentByKey($key, 'target') ?: '<fg=yellow>–</>'], $missingKeys))
                        ->setStyle('box')
                        ->render();

                    $hasErrors = true;
                    $sourceHasErrors = true;
                }
            }

            if (!$sourceHasErrors) {
                OutputUtility::debug(
                    $this->output,
                    sprintf('> Validation statistic: 1 source, %d target(s), %d language keys', count($targetFiles), count($sourceKeys)),
                    veryVerbose: true
                );
                OutputUtility::debug(
                    $this->output,
                    '> Validation result: ',
                    veryVerbose: true,
                    newLine: false
                );
                OutputUtility::debug(
                    $this->output,
                    ' <fg=green>✔</>'
                );
                OutputUtility::debug(
                    $this->output,
                    '',
                    veryVerbose: true
                );
            }
        }

        return $hasErrors;
    }
}
