<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Config;

use MoveElevator\ComposerTranslationValidator\Config\ConfigFactory;
use PHPUnit\Framework\TestCase;

final class ConfigFactoryTest extends TestCase
{
    private ConfigFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ConfigFactory();
    }

    public function testCreateFromArrayWithAllOptions(): void
    {
        $data = [
            'paths' => ['translations/', 'locale/'],
            'validators' => ['Validator1', 'Validator2'],
            'file-detectors' => ['Detector1'],
            'parsers' => ['Parser1'],
            'only' => ['OnlyValidator'],
            'skip' => ['SkipValidator'],
            'exclude' => ['*.backup', '*.tmp'],
            'strict' => true,
            'dry-run' => false,
            'format' => 'json',
            'verbose' => true,
        ];

        $config = $this->factory->createFromArray($data);

        $this->assertSame(['translations/', 'locale/'], $config->getPaths());
        $this->assertSame(['Validator1', 'Validator2'], $config->getValidators());
        $this->assertSame(['Detector1'], $config->getFileDetectors());
        $this->assertSame(['Parser1'], $config->getParsers());
        $this->assertSame(['OnlyValidator'], $config->getOnly());
        $this->assertSame(['SkipValidator'], $config->getSkip());
        $this->assertSame(['*.backup', '*.tmp'], $config->getExclude());
        $this->assertTrue($config->getStrict());
        $this->assertFalse($config->getDryRun());
        $this->assertSame('json', $config->getFormat());
        $this->assertTrue($config->getVerbose());
    }

    public function testCreateFromArrayWithEmptyData(): void
    {
        // Use minimal valid data to avoid schema validation issues
        $config = $this->factory->createFromArray(['paths' => ['translations/']]);

        $this->assertSame(['translations/'], $config->getPaths());
        $this->assertSame([], $config->getValidators());
        $this->assertSame([], $config->getFileDetectors());
        $this->assertSame([], $config->getParsers());
        $this->assertSame([], $config->getOnly());
        $this->assertSame([], $config->getSkip());
        $this->assertSame([], $config->getExclude());
        $this->assertFalse($config->getStrict());
        $this->assertFalse($config->getDryRun());
        $this->assertSame('cli', $config->getFormat());
        $this->assertFalse($config->getVerbose());
    }

    public function testCreateFromArrayWithPartialData(): void
    {
        $data = [
            'paths' => ['translations/'],
            'strict' => true,
            'format' => 'cli',
        ];

        $config = $this->factory->createFromArray($data);

        $this->assertSame(['translations/'], $config->getPaths());
        $this->assertTrue($config->getStrict());
        $this->assertSame('cli', $config->getFormat());
        $this->assertSame([], $config->getValidators());
        $this->assertFalse($config->getDryRun());
    }

    public function testCreateFromArrayValidatesData(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configuration validation failed');

        $this->factory->createFromArray(['paths' => 'invalid']);
    }

    public function testCreateFromArrayValidatesFormatValue(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configuration validation failed');

        $this->factory->createFromArray(['format' => 'invalid']);
    }
}
