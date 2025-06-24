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

class DuplicatesValidator implements ValidatorInterface
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
            '> Checking for <options=bold,underscore>duplicates</> ...'
        );

        foreach ($allFiles as $filePath) {
            /* @var ParserInterface $source */
            $file = new ($parserClass ?: ParserUtility::resolveParserClass($filePath))($filePath);

            OutputUtility::debug(
                $this->output,
                '> Checking language file: <fg=gray>'.$file->getFileDirectory().'</><fg=cyan>'.$file->getFileName().'</> ...',
                newLine: $this->output->isVeryVerbose()
            );

            $keys = $file->extractKeys();

            if (!$keys) {
                $this->io->error('The source file '.$file.' is not valid.');
                $hasErrors = true;
                continue;
            }

            $duplicateKeys = array_filter(array_count_values($keys), fn ($count) => $count > 1);

            if (!empty($duplicateKeys)) {
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

                $this->io->warning('Found duplicate keys in '.$file->getFilePath());
                $table = new Table($this->output);
                $table
                    ->setColumnWidths([10, 10])
                    ->setHeaderTitle('Duplicate Keys')
                    ->setHeaders(['Key', 'Count'])
                    ->setRows(
                        array_map(
                            static fn ($key, $count) => [$key, $count],
                            array_keys($duplicateKeys),
                            array_values($duplicateKeys)
                        )
                    )
                    ->setStyle('box')
                    ->render();

                $hasErrors = true;
                continue;
            }

            OutputUtility::debug(
                $this->output,
                sprintf('> Validation statistic: %d language keys', count($keys)),
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

        return $hasErrors;
    }
}
