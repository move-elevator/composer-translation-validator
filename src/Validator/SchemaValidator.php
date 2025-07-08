<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\Util\XliffUtils;

class SchemaValidator extends AbstractValidator implements ValidatorInterface
{
    public function processFile(ParserInterface $file): array
    {
        try {
            /*
             * With XmlUtils::loadFile() we always get a strange symfony error related to global composer autoloading issue.
             *      Call to undefined method Symfony\Component\Filesystem\Filesystem::readFile()
             */
            if (!file_exists($file->getFilePath())) {
                $this->logger?->error('File does not exist: '.$file->getFileName());

                return [];
            }

            $fileContent = file_get_contents($file->getFilePath());
            if (false === $fileContent) {
                $this->logger?->error('Failed to read file: '.$file->getFileName());

                return [];
            }
            $dom = XmlUtils::parse($fileContent);
            $errors = XliffUtils::validateSchema($dom);
        } catch (\Exception $e) {
            $this->logger?->error('Failed to validate XML schema: '.$e->getMessage());

            return [];
        }

        if (!empty($errors)) {
            return $errors;
        }

        return [];
    }

    /**
     * @param array<string, array<int, array{
     *     file: string,
     *     issues: array<int, array{
     *         level: string,
     *         code: int,
     *         message: string,
     *         file: string,
     *         line: int,
     *         column: int
     *     }>,
     *     parser: string,
     *     type: string
     * }>> $issueSets
     */
    public function renderIssueSets(InputInterface $input, OutputInterface $output, array $issueSets): void
    {
        $currentFile = null;
        $table = new Table($output);
        $table
            ->setHeaders(['File', 'Level', 'Code', 'Message', 'Line'])
            ->setStyle(
                (new TableStyle())
                    ->setCellHeaderFormat('%s')
            );

        foreach ($issueSets as $issues) {
            foreach ($issues as $errors) {
                if ($currentFile !== $errors['file'] && null !== $currentFile) {
                    $table->addRow(new TableSeparator());
                }
                $currentFile = $errors['file'];

                foreach ($errors['issues'] as $error) {
                    $message = preg_replace(
                        "/^Element ('(?:\{[^}]+\})?[^']+'):?\s*/",
                        '',
                        (string) $error['message']
                    );

                    $table->addRow([
                        '<fg=red>'.$errors['file'].'</>',
                        LIBXML_ERR_WARNING === (int) $error['level'] ? 'Warning' : 'Error',
                        $error['code'],
                        trim((string) $message),
                        $error['line'],
                    ]);
                    $errors['file'] = ''; // Reset file for subsequent rows
                }
            }
        }
        $table->render();
    }

    public function explain(): string
    {
        return 'Validates the XML schema of translation files against the XLIFF standard. '.
            'This ensures that the files are well-formed and adhere to the expected structure.';
    }

    /**
     * @return class-string<ParserInterface>[]
     */
    public function supportsParser(): array
    {
        return [XliffParser::class];
    }

    public function formatIssueMessage(Issue $issue, string $prefix = '', bool $isVerbose = false): string
    {
        $details = $issue->getDetails();
        $messages = [];

        foreach ($details as $error) {
            if (is_array($error)) {
                $message = $error['message'] ?? 'Schema validation error';
                $line = isset($error['line']) ? " (Line: {$error['line']})" : '';
                $code = isset($error['code']) ? " (Code: {$error['code']})" : '';
                $level = $error['level'] ?? 'ERROR';

                $color = 'ERROR' === strtoupper($level) ? 'red' : 'yellow';
                $levelText = ucfirst(strtolower($level));

                $messages[] = "- <fg=$color>$levelText</> {$prefix}$message$line$code";
            }
        }

        if (empty($messages)) {
            $messages[] = "- <fg=red>Error</> {$prefix}Schema validation error";
        }

        return implode("\n", $messages);
    }
}
