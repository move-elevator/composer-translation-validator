<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025-2026 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Tests\Config;

use MoveElevator\ComposerTranslationValidator\Config\CommandConfigResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\{ArrayInput, InputDefinition, InputOption};

#[CoversClass(CommandConfigResolver::class)]
/**
 * CommandConfigResolverTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class CommandConfigResolverTest extends TestCase
{
    private CommandConfigResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new CommandConfigResolver();
    }

    public function testResolveReturnsDefaultConfigWhenNoCliOptions(): void
    {
        $input = $this->createInput([]);
        $emptyDir = sys_get_temp_dir().'/translation-validator-resolver-'.uniqid('', true);
        mkdir($emptyDir, 0777, true);

        $config = $this->resolver->resolve($input, $emptyDir);

        $this->assertFalse($config->getDryRun());
        $this->assertFalse($config->getStrict());
        $this->assertSame('cli', $config->getFormat());
        $this->assertSame([], $config->getExclude());

        rmdir($emptyDir);
    }

    public function testResolveAppliesDryRunCliOption(): void
    {
        $input = $this->createInput(['--dry-run' => true]);
        $emptyDir = sys_get_temp_dir().'/translation-validator-resolver-'.uniqid('', true);
        mkdir($emptyDir, 0777, true);

        $config = $this->resolver->resolve($input, $emptyDir);

        $this->assertTrue($config->getDryRun());

        rmdir($emptyDir);
    }

    public function testResolveAppliesStrictCliOption(): void
    {
        $input = $this->createInput(['--strict' => true]);
        $emptyDir = sys_get_temp_dir().'/translation-validator-resolver-'.uniqid('', true);
        mkdir($emptyDir, 0777, true);

        $config = $this->resolver->resolve($input, $emptyDir);

        $this->assertTrue($config->getStrict());

        rmdir($emptyDir);
    }

    public function testResolveAppliesFormatCliOption(): void
    {
        $input = $this->createInput(['--format' => 'json']);
        $emptyDir = sys_get_temp_dir().'/translation-validator-resolver-'.uniqid('', true);
        mkdir($emptyDir, 0777, true);

        $config = $this->resolver->resolve($input, $emptyDir);

        $this->assertSame('json', $config->getFormat());

        rmdir($emptyDir);
    }

    public function testResolveAppliesExcludeCliOption(): void
    {
        $input = $this->createInput(['--exclude' => '**/backup/**, **/*.bak']);
        $emptyDir = sys_get_temp_dir().'/translation-validator-resolver-'.uniqid('', true);
        mkdir($emptyDir, 0777, true);

        $config = $this->resolver->resolve($input, $emptyDir);

        $this->assertSame(['**/backup/**', '**/*.bak'], $config->getExclude());

        rmdir($emptyDir);
    }

    public function testResolveMergesExcludePatternsFromConfigAndCli(): void
    {
        $tempDir = sys_get_temp_dir().'/translation-validator-resolver-merge-'.uniqid('', true);
        mkdir($tempDir, 0777, true);

        $configFile = $tempDir.'/translation-validator.json';
        file_put_contents($configFile, json_encode(['paths' => ['src'], 'exclude' => ['vendor/*']]));

        $input = $this->createInput(['--exclude' => '**/backup/**']);

        $config = $this->resolver->resolve($input, $tempDir);

        $this->assertSame(['vendor/*', '**/backup/**'], $config->getExclude());

        unlink($configFile);
        rmdir($tempDir);
    }

    public function testResolveLoadsConfigFromFile(): void
    {
        $tempDir = sys_get_temp_dir().'/translation-validator-resolver-file-'.uniqid('', true);
        mkdir($tempDir, 0777, true);

        $configFile = $tempDir.'/custom-config.json';
        file_put_contents($configFile, json_encode([
            'paths' => ['custom-path'],
            'strict' => true,
        ]));

        $input = $this->createInput(['--config' => $configFile]);

        $config = $this->resolver->resolve($input, $tempDir);

        $this->assertSame(['custom-path'], $config->getPaths());
        $this->assertTrue($config->getStrict());

        unlink($configFile);
        rmdir($tempDir);
    }

    public function testResolveAutoDetectsConfigFile(): void
    {
        $tempDir = sys_get_temp_dir().'/translation-validator-resolver-auto-'.uniqid('', true);
        mkdir($tempDir, 0777, true);

        $configFile = $tempDir.'/translation-validator.json';
        file_put_contents($configFile, json_encode(['paths' => ['auto-detected']]));

        $input = $this->createInput([]);

        $config = $this->resolver->resolve($input, $tempDir);

        $this->assertSame(['auto-detected'], $config->getPaths());

        unlink($configFile);
        rmdir($tempDir);
    }

    public function testResolveCliOptionOverridesConfigFileDryRun(): void
    {
        $tempDir = sys_get_temp_dir().'/translation-validator-resolver-override-'.uniqid('', true);
        mkdir($tempDir, 0777, true);

        $configFile = $tempDir.'/translation-validator.json';
        file_put_contents($configFile, json_encode(['paths' => ['src'], 'dry-run' => false]));

        $input = $this->createInput(['--dry-run' => true]);

        $config = $this->resolver->resolve($input, $tempDir);

        $this->assertTrue($config->getDryRun());

        unlink($configFile);
        rmdir($tempDir);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createInput(array $options): ArrayInput
    {
        $definition = new InputDefinition([
            new InputOption('dry-run', null, InputOption::VALUE_NONE),
            new InputOption('strict', null, InputOption::VALUE_NONE),
            new InputOption('format', 'f', InputOption::VALUE_OPTIONAL, '', 'cli'),
            new InputOption('only', 'o', InputOption::VALUE_OPTIONAL),
            new InputOption('skip', 's', InputOption::VALUE_OPTIONAL),
            new InputOption('config', 'c', InputOption::VALUE_OPTIONAL),
            new InputOption('recursive', 'r', InputOption::VALUE_NONE),
            new InputOption('exclude', 'e', InputOption::VALUE_OPTIONAL),
        ]);

        return new ArrayInput($options, $definition);
    }
}
