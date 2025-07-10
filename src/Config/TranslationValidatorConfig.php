<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Config;

class TranslationValidatorConfig
{
    /** @var string[] */
    private array $paths = [];

    /** @var string[] */
    private array $validators = [];

    /** @var string[] */
    private array $fileDetectors = [];

    /** @var string[] */
    private array $parsers = [];

    /** @var string[] */
    private array $only = [];

    /** @var string[] */
    private array $skip = [];

    /** @var string[] */
    private array $exclude = [];

    private bool $strict = false;

    private bool $dryRun = false;

    private string $format = 'cli';

    private bool $verbose = false;

    /**
     * @param string[] $paths
     */
    public function setPaths(array $paths): self
    {
        $this->paths = $paths;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    public function addValidator(string $validator): self
    {
        $this->validators[] = $validator;

        return $this;
    }

    /**
     * @param string[] $validators
     */
    public function setValidators(array $validators): self
    {
        $this->validators = $validators;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getValidators(): array
    {
        return $this->validators;
    }

    public function addFileDetector(string $fileDetector): self
    {
        $this->fileDetectors[] = $fileDetector;

        return $this;
    }

    /**
     * @param string[] $fileDetectors
     */
    public function setFileDetectors(array $fileDetectors): self
    {
        $this->fileDetectors = $fileDetectors;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getFileDetectors(): array
    {
        return $this->fileDetectors;
    }

    public function addParser(string $parser): self
    {
        $this->parsers[] = $parser;

        return $this;
    }

    /**
     * @param string[] $parsers
     */
    public function setParsers(array $parsers): self
    {
        $this->parsers = $parsers;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getParsers(): array
    {
        return $this->parsers;
    }

    public function only(string $validator): self
    {
        $this->only[] = $validator;

        return $this;
    }

    /**
     * @param string[] $only
     */
    public function setOnly(array $only): self
    {
        $this->only = $only;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getOnly(): array
    {
        return $this->only;
    }

    public function skip(string $validator): self
    {
        $this->skip[] = $validator;

        return $this;
    }

    /**
     * @param string[] $skip
     */
    public function setSkip(array $skip): self
    {
        $this->skip = $skip;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getSkip(): array
    {
        return $this->skip;
    }

    /**
     * @param string[] $exclude
     */
    public function setExclude(array $exclude): self
    {
        $this->exclude = $exclude;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getExclude(): array
    {
        return $this->exclude;
    }

    public function setStrict(bool $strict): self
    {
        $this->strict = $strict;

        return $this;
    }

    public function getStrict(): bool
    {
        return $this->strict;
    }

    public function setDryRun(bool $dryRun): self
    {
        $this->dryRun = $dryRun;

        return $this;
    }

    public function getDryRun(): bool
    {
        return $this->dryRun;
    }

    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setVerbose(bool $verbose): self
    {
        $this->verbose = $verbose;

        return $this;
    }

    public function getVerbose(): bool
    {
        return $this->verbose;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'paths' => $this->paths,
            'validators' => $this->validators,
            'file-detectors' => $this->fileDetectors,
            'parsers' => $this->parsers,
            'only' => $this->only,
            'skip' => $this->skip,
            'exclude' => $this->exclude,
            'strict' => $this->strict,
            'dry-run' => $this->dryRun,
            'format' => $this->format,
            'verbose' => $this->verbose,
        ];
    }
}
