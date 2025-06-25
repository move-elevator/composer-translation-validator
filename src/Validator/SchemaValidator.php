<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\FileDetector\DetectorInterface;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\ParserUtility;
use MoveElevator\ComposerTranslationValidator\Utility\OutputUtility;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Translation\Util\XliffUtils;

class SchemaValidator implements ValidatorInterface
{
    private SymfonyStyle $io;

    public function __construct(
        protected readonly InputInterface  $input,
        protected readonly OutputInterface $output,
    )
    {
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
            '> Checking for <options=bold,underscore>schema</> ...'
        );

        foreach ($allFiles as $filePath) {
            /* @var ParserInterface $source */
            $file = new ($parserClass ?: ParserUtility::resolveParserClass($filePath))($filePath);

            OutputUtility::debug(
                $this->output,
                '> Checking language file: <fg=gray>' . $file->getFileDirectory() . '</><fg=cyan>' . $file->getFileName() . '</> ...',
                newLine: $this->output->isVeryVerbose()
            );

            try {
                $dom = XmlUtils::loadFile($filePath);
                $errors = XliffUtils::validateSchema($dom);
            } catch (\Exception $e) {
                $this->io->error('Failed to validate XML schema: ' . $e->getMessage());
                $hasErrors = true;
                continue;
            }

            if (!empty($errors)) {
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

                $this->io->warning('Got schema errors in ' . $file->getFilePath());
                $table = new Table($this->output);
                $table
                    ->setColumnWidths([10, 10])
                    ->setHeaderTitle('Schema Errors')
                    ->setHeaders(['Level', 'Code', 'Message', 'Line'])
                    ->setStyle('box');

                foreach ($errors as $error) {
                    $message = preg_replace(
                        "/^Element ('(?:\{[^}]+\})?[^']+'):?\s*/",
                        '',
                        $error['message']
                    );

                    $table->addRow([
                        LIBXML_ERR_WARNING === $error['level'] ? 'WARNING' : 'ERROR',
                        $error['code'],
                        trim($message),
                        $error['line'],
                    ]);
                }
                $table->render();

                $hasErrors = true;
                continue;
            }

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
