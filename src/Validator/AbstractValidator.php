<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\ParserRegistry;
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
        $classPart = strrchr(static::class, '\\');
        $name = false !== $classPart ? substr($classPart, 1) : static::class;
        $this->logger->debug(
            sprintf(
                '> Checking for <options=bold,underscore>%s</> ...',
                $name
            )
        );

        foreach ($files as $filePath) {
            $file = new ($parserClass ?: ParserRegistry::resolveParserClass($filePath))($filePath);
            /* @var ParserInterface $file */

            if (!in_array($file::class, $this->supportsParser(), true)) {
                $this->logger?->debug(
                    sprintf(
                        'The file <fg=cyan>%s</> is not supported by the validator <fg=red>%s</>.',
                        $file->getFileName(),
                        static::class
                    )
                );
                continue;
            }

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

    /**
     * @return class-string<ParserInterface>[]
     */
    abstract public function supportsParser(): array;

    protected function postProcess(): void
    {
        // This method can be overridden by subclasses to perform additional processing after validation.
    }
}
