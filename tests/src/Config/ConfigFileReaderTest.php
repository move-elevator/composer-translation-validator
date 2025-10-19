<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Tests\Config;

use Exception;
use InvalidArgumentException;
use JsonException;
use MoveElevator\ComposerTranslationValidator\Config\ConfigFileReader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * ConfigFileReaderTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class ConfigFileReaderTest extends TestCase
{
    private ConfigFileReader $reader;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->reader = new ConfigFileReader();
        $this->tempDir = sys_get_temp_dir().'/translation-validator-test-'.uniqid('', true);
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir.'/*');
            if (false !== $files) {
                array_map('unlink', $files);
            }
            rmdir($this->tempDir);
        }
    }

    public function testReadJsonFile(): void
    {
        $configPath = $this->tempDir.'/config.json';
        $configData = [
            'paths' => ['translations/'],
            'strict' => true,
            'format' => 'json',
        ];

        file_put_contents($configPath, json_encode($configData));

        $result = $this->reader->readFile($configPath);

        $this->assertSame($configData, $result);
    }

    public function testReadYamlFile(): void
    {
        $configPath = $this->tempDir.'/config.yaml';
        $yamlContent = "paths:\n  - translations/\nstrict: true\nformat: json";

        file_put_contents($configPath, $yamlContent);

        $result = $this->reader->readFile($configPath);

        $this->assertSame(['translations/'], $result['paths']);
        $this->assertTrue($result['strict']);
        $this->assertSame('json', $result['format']);
    }

    public function testReadPhpFileAsArray(): void
    {
        $configPath = $this->tempDir.'/config.php';
        $phpContent = '<?php
use MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig;

$config = new TranslationValidatorConfig();
$config->setPaths([\'translations/\'])
    ->setStrict(true)
    ->setFormat(\'json\');

return $config;
';

        file_put_contents($configPath, $phpContent);

        $result = $this->reader->readFile($configPath);

        $this->assertSame(['translations/'], $result['paths']);
        $this->assertTrue($result['strict']);
        $this->assertSame('json', $result['format']);
    }

    public function testReadAsConfig(): void
    {
        $configPath = $this->tempDir.'/config.json';
        $configData = [
            'paths' => ['translations/'],
            'strict' => true,
            'format' => 'json',
        ];

        file_put_contents($configPath, json_encode($configData));

        $config = $this->reader->readAsConfig($configPath);

        $this->assertSame(['translations/'], $config->getPaths());
        $this->assertTrue($config->getStrict());
        $this->assertSame('json', $config->getFormat());
    }

    public function testReadAsConfigWithPhpFile(): void
    {
        $configPath = $this->tempDir.'/config.php';
        $phpContent = '<?php
use MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig;

$config = new TranslationValidatorConfig();
$config->setPaths([\'translations/\'])
    ->setStrict(true)
    ->setFormat(\'json\');

return $config;
';

        file_put_contents($configPath, $phpContent);

        $config = $this->reader->readAsConfig($configPath);

        $this->assertSame(['translations/'], $config->getPaths());
        $this->assertTrue($config->getStrict());
        $this->assertSame('json', $config->getFormat());
    }

    public function testReadFileNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration file not found');

        $this->reader->readFile('/non/existent/file.json');
    }

    public function testReadFileNotReadable(): void
    {
        $configPath = $this->tempDir.'/config.json';
        file_put_contents($configPath, '{}');
        chmod($configPath, 0000);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration file is not readable');

        try {
            $this->reader->readFile($configPath);
        } finally {
            chmod($configPath, 0644);
        }
    }

    public function testReadUnsupportedFormat(): void
    {
        $configPath = $this->tempDir.'/config.txt';
        file_put_contents($configPath, 'content');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported configuration file format: txt');

        $this->reader->readFile($configPath);
    }

    public function testReadInvalidJsonFile(): void
    {
        $configPath = $this->tempDir.'/config.json';
        file_put_contents($configPath, 'invalid json');

        $this->expectException(JsonException::class);

        $this->reader->readFile($configPath);
    }

    public function testReadInvalidYamlFile(): void
    {
        $configPath = $this->tempDir.'/config.yaml';
        file_put_contents($configPath, "invalid:\n  - yaml\n  content:");

        $this->expectException(Exception::class);

        $this->reader->readFile($configPath);
    }

    public function testReadPhpFileWithInvalidReturn(): void
    {
        $configPath = $this->tempDir.'/config.php';
        file_put_contents($configPath, '<?php return "invalid";');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PHP configuration file must return an instance of TranslationValidatorConfig');

        $this->reader->readAsConfig($configPath);
    }

    public function testReadPhpConfigWithInvalidPath(): void
    {
        // We need to test readAsConfig() directly for PHP files to reach the realpath() check
        $configPath = $this->tempDir.'/config.php';
        $brokenLink = $this->tempDir.'/broken_link.php';

        file_put_contents($configPath, '<?php return "test";');
        symlink($configPath, $brokenLink);
        unlink($configPath); // Break the symlink

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid configuration file path');

        $this->reader->readAsConfig($brokenLink);
    }

    public function testReadJsonConfigWithNonArrayContent(): void
    {
        $configPath = $this->tempDir.'/config.json';
        file_put_contents($configPath, '"string-content"');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid JSON configuration file');

        $this->reader->readFile($configPath);
    }

    public function testReadYamlConfigWithNonArrayContent(): void
    {
        $configPath = $this->tempDir.'/config.yaml';
        file_put_contents($configPath, '"string-content"');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid YAML configuration file');

        $this->reader->readFile($configPath);
    }

    public function testReadAsConfigUsesDirectPhpConfigPath(): void
    {
        // Test that readAsConfig() uses the direct PHP config path
        $configPath = $this->tempDir.'/config.php';
        $phpContent = '<?php
use MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig;

$config = new TranslationValidatorConfig();
$config->setPaths([\'direct-php-test\']);
return $config;
';

        file_put_contents($configPath, $phpContent);

        $config = $this->reader->readAsConfig($configPath);

        $this->assertSame(['direct-php-test'], $config->getPaths());
    }
}
