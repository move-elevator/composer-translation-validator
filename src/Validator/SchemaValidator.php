<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
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
            $dom = XmlUtils::loadFile($file->getFilePath());
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
                        LIBXML_ERR_WARNING === (int) $error['level'] ? 'WARNING' : 'ERROR',
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
}
