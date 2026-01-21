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

use InvalidArgumentException;
use JsonException;
use MoveElevator\ComposerTranslationValidator\Config\{ConfigReader, TranslationValidatorConfig};
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * ConfigReaderTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
#[CoversClass(ConfigReader::class)]
/**
 * ConfigReaderTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class ConfigReaderTest extends TestCase
{
    private ConfigReader $configReader;
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->configReader = new ConfigReader();
        $this->fixturesDir = __DIR__.'/../Fixtures/config';
    }

    public function testReadNonExistentFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration file not found:');

        $this->configReader->read('/non/existent/file.json');
    }

    public function testReadUnsupportedFormat(): void
    {
        $configFile = $this->fixturesDir.'/unsupported.txt';
        file_put_contents($configFile, 'some content');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported configuration file format: txt');

        $this->configReader->read($configFile);

        // Clean up
        unlink($configFile);
    }

    public function testReadPhpConfig(): void
    {
        $configFile = $this->fixturesDir.'/valid.php';
        $config = $this->configReader->read($configFile);

        $this->assertSame(['path1', 'path2'], $config->getPaths());
        $this->assertTrue($config->getStrict());
        $this->assertSame('json', $config->getFormat());
    }

    public function testReadInvalidPhpConfig(): void
    {
        $configFile = $this->fixturesDir.'/invalid.php';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PHP configuration file must return an instance of TranslationValidatorConfig');

        $this->configReader->read($configFile);
    }

    public function testReadJsonConfig(): void
    {
        $configFile = $this->fixturesDir.'/valid.json';
        $config = $this->configReader->read($configFile);

        $this->assertSame(['path1', 'path2'], $config->getPaths());
        $this->assertTrue($config->getStrict());
        $this->assertSame('json', $config->getFormat());
        $this->assertSame(['vendor/*'], $config->getExclude());
    }

    public function testReadInvalidJsonConfig(): void
    {
        $configFile = $this->fixturesDir.'/invalid.json';

        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Syntax error');

        $this->configReader->read($configFile);
    }

    public function testReadYamlConfig(): void
    {
        $configFile = $this->fixturesDir.'/valid.yaml';
        $config = $this->configReader->read($configFile);

        $this->assertSame(['path1', 'path2'], $config->getPaths());
        $this->assertTrue($config->getStrict());
        $this->assertSame('yaml', $config->getFormat());
    }

    public function testReadYmlConfig(): void
    {
        $configFile = $this->fixturesDir.'/valid.yml';
        $config = $this->configReader->read($configFile);

        $this->assertSame(['path1'], $config->getPaths());
        $this->assertTrue($config->getVerbose());
        $this->assertFalse($config->getStrict());
    }

    public function testAutoDetectWithNoFiles(): void
    {
        $emptyDir = sys_get_temp_dir().'/translation-validator-empty-'.uniqid();
        mkdir($emptyDir, 0777, true);

        $config = $this->configReader->autoDetect($emptyDir);

        $this->assertNotInstanceOf(TranslationValidatorConfig::class, $config);

        rmdir($emptyDir);
    }

    public function testAutoDetectWithPhpFile(): void
    {
        $config = $this->configReader->autoDetect($this->fixturesDir.'/auto-detect');
        $this->assertInstanceOf(TranslationValidatorConfig::class, $config);

        $this->assertSame(['detected-php'], $config->getPaths());
    }

    public function testAutoDetectWithJsonFile(): void
    {
        // Create a temporary directory with only JSON file
        $tempDir = sys_get_temp_dir().'/translation-validator-json-'.uniqid('', true);
        mkdir($tempDir, 0777, true);
        copy($this->fixturesDir.'/auto-detect/translation-validator.json', $tempDir.'/translation-validator.json');

        $config = $this->configReader->autoDetect($tempDir);
        $this->assertInstanceOf(TranslationValidatorConfig::class, $config);

        $this->assertSame(['detected-json'], $config->getPaths());

        // Clean up
        unlink($tempDir.'/translation-validator.json');
        rmdir($tempDir);
    }

    public function testAutoDetectPriority(): void
    {
        $config = $this->configReader->autoDetect($this->fixturesDir.'/auto-detect');
        $this->assertInstanceOf(TranslationValidatorConfig::class, $config);

        // Should pick PHP first (highest priority)
        $this->assertSame(['detected-php'], $config->getPaths());
    }

    public function testReadFromComposerJsonNotFound(): void
    {
        $config = $this->configReader->readFromComposerJson('/non/existent/composer.json');

        $this->assertNotInstanceOf(TranslationValidatorConfig::class, $config);
    }

    public function testReadFromComposerJsonWithoutConfig(): void
    {
        $tempDir = sys_get_temp_dir().'/translation-validator-composer-empty-'.uniqid('', true);
        mkdir($tempDir, 0777, true);

        $composerJson = $tempDir.'/composer.json';
        file_put_contents($composerJson, json_encode(['name' => 'test/package']));

        $config = $this->configReader->readFromComposerJson($composerJson);

        $this->assertNotInstanceOf(TranslationValidatorConfig::class, $config);

        // Clean up
        unlink($composerJson);
        rmdir($tempDir);
    }

    public function testReadFromComposerJsonWithConfig(): void
    {
        $tempDir = sys_get_temp_dir().'/translation-validator-composer-'.uniqid('', true);
        mkdir($tempDir, 0777, true);

        $configFile = $tempDir.'/custom-config.json';
        file_put_contents($configFile, json_encode(['paths' => ['composer-configured']]));

        $composerJson = $tempDir.'/composer.json';
        $composerContent = [
            'name' => 'test/package',
            'extra' => [
                'translation-validator' => [
                    'config-file' => 'custom-config.json',
                ],
            ],
        ];
        file_put_contents($composerJson, json_encode($composerContent));

        $config = $this->configReader->readFromComposerJson($composerJson);
        $this->assertInstanceOf(TranslationValidatorConfig::class, $config);

        $this->assertSame(['composer-configured'], $config->getPaths());

        // Clean up
        unlink($configFile);
        unlink($composerJson);
        rmdir($tempDir);
    }

    public function testCreateConfigFromArrayWithInvalidTypes(): void
    {
        $configFile = $this->fixturesDir.'/invalid-paths.json';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration validation failed:');

        $this->configReader->read($configFile);
    }

    public function testCreateConfigFromArrayWithInvalidFormat(): void
    {
        $configFile = $this->fixturesDir.'/invalid-format.json';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration validation failed:');

        $this->configReader->read($configFile);
    }

    public function testCreateConfigFromArrayWithAllFields(): void
    {
        $configFile = $this->fixturesDir.'/complete.json';
        $config = $this->configReader->read($configFile);

        $this->assertSame(['path1', 'path2'], $config->getPaths());
        $this->assertSame(['Validator1'], $config->getValidators());
        $this->assertSame(['Detector1'], $config->getFileDetectors());
        $this->assertSame(['Parser1'], $config->getParsers());
        $this->assertSame(['Only1'], $config->getOnly());
        $this->assertSame(['Skip1'], $config->getSkip());
        $this->assertSame(['vendor/*'], $config->getExclude());
        $this->assertTrue($config->getStrict());
        $this->assertTrue($config->getDryRun());
        $this->assertSame('json', $config->getFormat());
        $this->assertTrue($config->getVerbose());
    }

    public function testAutoDetectWithGetcwdFailure(): void
    {
        $config = $this->configReader->autoDetect(null);
        $this->assertNotInstanceOf(TranslationValidatorConfig::class, $config);
    }

    public function testReadJsonConfigWithFileGetContentsFailure(): void
    {
        $configFile = $this->fixturesDir.'/unreadable.json';
        file_put_contents($configFile, json_encode(['paths' => ['test']]));
        chmod($configFile, 0000);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration file is not readable:');

        try {
            $this->configReader->read($configFile);
        } finally {
            chmod($configFile, 0644);
            unlink($configFile);
        }
    }

    public function testReadFromComposerJsonWithInvalidJson(): void
    {
        $tempDir = sys_get_temp_dir().'/translation-validator-invalid-json-'.uniqid('', true);
        mkdir($tempDir, 0777, true);

        $composerJson = $tempDir.'/composer.json';
        file_put_contents($composerJson, 'invalid json');

        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Syntax error');

        try {
            $config = $this->configReader->readFromComposerJson($composerJson);
            $this->assertNotInstanceOf(TranslationValidatorConfig::class, $config);
        } finally {
            unlink($composerJson);
            rmdir($tempDir);
        }
    }

    public function testReadFromComposerJsonWithRelativeConfigPath(): void
    {
        $tempDir = sys_get_temp_dir().'/translation-validator-relative-'.uniqid('', true);
        mkdir($tempDir, 0777, true);

        $configFile = $tempDir.'/relative-config.json';
        file_put_contents($configFile, json_encode(['paths' => ['relative-path']]));

        $composerJson = $tempDir.'/composer.json';
        $composerContent = [
            'name' => 'test/package',
            'extra' => [
                'translation-validator' => [
                    'config-file' => 'relative-config.json',
                ],
            ],
        ];
        file_put_contents($composerJson, json_encode($composerContent));

        $config = $this->configReader->readFromComposerJson($composerJson);
        $this->assertInstanceOf(TranslationValidatorConfig::class, $config);
        $this->assertSame(['relative-path'], $config->getPaths());

        unlink($configFile);
        unlink($composerJson);
        rmdir($tempDir);
    }

    public function testReadFromComposerJsonWithAbsoluteConfigPath(): void
    {
        $tempDir = sys_get_temp_dir().'/translation-validator-absolute-'.uniqid('', true);
        mkdir($tempDir, 0777, true);

        $configFile = $tempDir.'/absolute-config.json';
        file_put_contents($configFile, json_encode(['paths' => ['absolute-path']]));

        $composerJson = $tempDir.'/composer.json';
        $composerContent = [
            'name' => 'test/package',
            'extra' => [
                'translation-validator' => [
                    'config-file' => $configFile,
                ],
            ],
        ];
        file_put_contents($composerJson, json_encode($composerContent));

        $config = $this->configReader->readFromComposerJson($composerJson);
        $this->assertInstanceOf(TranslationValidatorConfig::class, $config);
        $this->assertSame(['absolute-path'], $config->getPaths());

        unlink($configFile);
        unlink($composerJson);
        rmdir($tempDir);
    }

    public function testReadYamlConfigWithInvalidYaml(): void
    {
        $configFile = $this->fixturesDir.'/invalid.yaml';

        $this->expectException(\Symfony\Component\Yaml\Exception\ParseException::class);

        $this->configReader->read($configFile);
    }

    public function testReadYamlConfigWithNonArrayData(): void
    {
        $configFile = $this->fixturesDir.'/scalar.yaml';
        file_put_contents($configFile, 'scalar-value');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid YAML configuration file:');

        try {
            $this->configReader->read($configFile);
        } finally {
            unlink($configFile);
        }
    }

    public function testCreateConfigFromArrayWithInvalidValidatorsType(): void
    {
        $configFile = $this->fixturesDir.'/invalid-validators.json';
        file_put_contents($configFile, json_encode(['validators' => 'not-array']));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration validation failed:');

        try {
            $this->configReader->read($configFile);
        } finally {
            unlink($configFile);
        }
    }

    public function testCreateConfigFromArrayWithInvalidParsersType(): void
    {
        $configFile = $this->fixturesDir.'/invalid-parsers.json';
        file_put_contents($configFile, json_encode(['parsers' => 'not-array']));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration validation failed:');

        try {
            $this->configReader->read($configFile);
        } finally {
            unlink($configFile);
        }
    }

    public function testCreateConfigFromArrayWithInvalidStrictType(): void
    {
        $configFile = $this->fixturesDir.'/invalid-strict.json';
        file_put_contents($configFile, json_encode(['strict' => 'not-bool']));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration validation failed:');

        try {
            $this->configReader->read($configFile);
        } finally {
            unlink($configFile);
        }
    }

    public function testCreateConfigFromArrayWithInvalidDryRunType(): void
    {
        $configFile = $this->fixturesDir.'/invalid-dry-run.json';
        file_put_contents($configFile, json_encode(['dry-run' => 'not-bool']));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration validation failed:');

        try {
            $this->configReader->read($configFile);
        } finally {
            unlink($configFile);
        }
    }

    public function testCreateConfigFromArrayWithInvalidVerboseType(): void
    {
        $configFile = $this->fixturesDir.'/invalid-verbose.json';
        file_put_contents($configFile, json_encode(['verbose' => 'not-bool']));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration validation failed:');

        try {
            $this->configReader->read($configFile);
        } finally {
            unlink($configFile);
        }
    }

    public function testCreateConfigFromArrayWithInvalidFormatType(): void
    {
        $configFile = $this->fixturesDir.'/invalid-format-type.json';
        file_put_contents($configFile, json_encode(['format' => 123]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration validation failed:');

        try {
            $this->configReader->read($configFile);
        } finally {
            unlink($configFile);
        }
    }
}
