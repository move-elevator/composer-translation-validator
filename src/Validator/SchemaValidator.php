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
     * @param array<array<string, array<int, array<string, mixed>>>> $issueSets
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
            foreach ($issues as $file => $errors) {
                if ($currentFile !== $file && null !== $currentFile) {
                    $table->addRow(new TableSeparator());
                }
                $currentFile = $file;

                foreach ($errors as $error) {
                    $message = preg_replace(
                        "/^Element ('(?:\{[^}]+\})?[^']+'):?\s*/",
                        '',
                        (string) $error['message']
                    );

                    $table->addRow([
                        "<fg=red>$file</>",
                        LIBXML_ERR_WARNING === $error['level'] ? 'WARNING' : 'ERROR',
                        $error['code'],
                        trim((string) $message),
                        $error['line'],
                    ]);
                    $file = ''; // Reset file for subsequent rows
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
