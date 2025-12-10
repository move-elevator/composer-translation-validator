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

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Config\ConfigFactory;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Validator\KeyNamingConventionValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * KeyNamingConventionValidatorConfigTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class KeyNamingConventionValidatorConfigTest extends TestCase
{
    public function testValidatorLoadsConventionFromConfig(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        $config->setValidatorSetting('KeyNamingConventionValidator', [
            'convention' => 'snake_case',
        ]);

        $parser = $this->createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['userName', 'valid_key']);
        $parser->method('getContentByKey')->willReturnMap([
            ['userName', 'User Name'],
            ['valid_key', 'Valid Key'],
        ]);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $validator->setConfig($config);

        $this->assertTrue($validator->shouldRun());

        $result = $validator->processFile($parser);

        // Should have one issue for 'userName' (not snake_case)
        $this->assertCount(1, $result);
        $this->assertEquals('userName', $result[0]['key']);
        $this->assertEquals('user_name', $result[0]['suggestion']);
    }

    public function testValidatorLoadsCustomPatternFromConfig(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        $config->setValidatorSetting('KeyNamingConventionValidator', [
            'custom_pattern' => '/^[a-z]+$/', // Only lowercase letters
        ]);

        $parser = $this->createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['validkey', 'invalidKey123']);
        $parser->method('getContentByKey')->willReturnMap([
            ['validkey', 'Valid Key'],
            ['invalidKey123', 'Invalid Key'],
        ]);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $validator->setConfig($config);

        $this->assertTrue($validator->shouldRun());

        $result = $validator->processFile($parser);

        // Should have one issue for 'invalidKey123' (contains numbers and uppercase)
        $this->assertCount(1, $result);
        $this->assertEquals('invalidKey123', $result[0]['key']);
    }

    public function testValidatorWithoutConfigurationStillRuns(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        // No validator-specific settings

        $validator = new KeyNamingConventionValidator();
        $validator->setConfig($config);

        // Should run to detect mixed conventions even without config
        $this->assertTrue($validator->shouldRun());
    }

    public function testValidatorHandlesInvalidConventionInConfig(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        $config->setValidatorSetting('KeyNamingConventionValidator', [
            'convention' => 'invalid_convention',
        ]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Invalid convention in config'));

        $validator = new KeyNamingConventionValidator($logger);
        $validator->setConfig($config);

        // Should still run to detect mixed conventions even with invalid config
        $this->assertTrue($validator->shouldRun());
    }

    public function testValidatorHandlesInvalidCustomPatternInConfig(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        $config->setValidatorSetting('KeyNamingConventionValidator', [
            'custom_pattern' => 'invalid[pattern',
        ]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Invalid custom pattern in config'));

        $validator = new KeyNamingConventionValidator($logger);
        $validator->setConfig($config);

        // Should still run to detect mixed conventions even with invalid config
        $this->assertTrue($validator->shouldRun());
    }

    public function testCustomPatternOverridesConvention(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        $config->setValidatorSetting('KeyNamingConventionValidator', [
            'convention' => 'snake_case',
            'custom_pattern' => '/^[A-Z][a-z]*$/', // PascalCase pattern
        ]);

        $parser = $this->createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['Valid', 'invalid']);
        $parser->method('getContentByKey')->willReturnMap([
            ['Valid', 'Valid Key'],
            ['invalid', 'Invalid Key'],
        ]);
        $parser->method('getFileName')->willReturn('test.yaml');

        $validator = new KeyNamingConventionValidator();
        $validator->setConfig($config);

        $result = $validator->processFile($parser);

        // Should validate against custom pattern, not snake_case
        $this->assertCount(1, $result);
        $this->assertEquals('invalid', $result[0]['key']);
        // No suggestion for custom patterns
        $this->assertEquals('invalid', $result[0]['suggestion']);
    }

    public function testValidatorWithNullConfig(): void
    {
        $validator = new KeyNamingConventionValidator();
        $validator->setConfig(null);

        // Should still run to detect mixed conventions even with null config
        $this->assertTrue($validator->shouldRun());
    }

    public function testValidatorWithEmptyValidatorSettings(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        // Config has no validator-settings section
        $validator = new KeyNamingConventionValidator();
        $validator->setConfig($config);

        // Should still run to detect mixed conventions even without validator settings
        $this->assertTrue($validator->shouldRun());
    }
}
