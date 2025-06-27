<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\ParserUtility;
use Psr\Log\LoggerInterface;

abstract class AbstractValidator
{
    /** @var array<string, array<mixed>> */
    protected array $issues = [];

    public function __construct(protected ?LoggerInterface $logger = null)
    {
    }

    /**
     * @param string[]                           $files
     * @param class-string<ParserInterface>|null $parserClass
     *
     * @return array<string, array<mixed>>
     *
     * @throws \ReflectionException
     */
    public function validate(array $files, ?string $parserClass): array
    {
        $this->logger->debug(
            sprintf(
                '> Checking for <options=bold,underscore>%s</> ...',
                substr(strrchr(static::class, '\\'), 1) ?: static::class
            )
        );

        foreach ($files as $filePath) {
            $file = new ($parserClass ?: ParserUtility::resolveParserClass($filePath))($filePath);
            /* @var ParserInterface $file */

            $this->logger->debug('> Checking language file: <fg=gray>'.$file->getFileDirectory().'</><fg=cyan>'.$file->getFileName().'</> ...');

            $validationResult = $this->processFile($file);
            if (!empty($validationResult)) {
                $this->issues[$file->getFileName()] = $validationResult;
            }
        }

        $this->postProcess();

        return $this->issues;
    }

    /**
     * @return array<mixed>
     */
    abstract protected function processFile(ParserInterface $file): array;

    protected function postProcess(): void
    {
        // This method can be overridden by subclasses to perform additional processing after validation.
    }
}
