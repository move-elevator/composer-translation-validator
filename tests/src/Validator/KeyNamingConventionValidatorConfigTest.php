<?php

declare(strict_types=1);

/*
 * This file is part of the Composer plugin "composer-translation-validator".
 *
 * Copyright (C) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Config\ConfigFactory;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Validator\KeyNamingConventionValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class KeyNamingConventionValidatorConfigTest extends TestCase
{
    public function testValidatorLoadsConventionFromConfig(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        $config->setValidatorSetting('KeyNamingConventionValidator', [
            'convention' => 'snake_case',
        ]);

        $parser = $this->createMock(ParserInterface::class);
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

        $parser = $this->createMock(ParserInterface::class);
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

    public function testValidatorWithoutConfigurationDoesNotRun(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        // No validator-specific settings

        $validator = new KeyNamingConventionValidator();
        $validator->setConfig($config);

        $this->assertFalse($validator->shouldRun());
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

        // Should not run due to invalid configuration
        $this->assertFalse($validator->shouldRun());
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

        // Should not run due to invalid configuration
        $this->assertFalse($validator->shouldRun());
    }

    public function testCustomPatternOverridesConvention(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        $config->setValidatorSetting('KeyNamingConventionValidator', [
            'convention' => 'snake_case',
            'custom_pattern' => '/^[A-Z][a-z]*$/', // PascalCase pattern
        ]);

        $parser = $this->createMock(ParserInterface::class);
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

        $this->assertFalse($validator->shouldRun());
    }

    public function testValidatorWithEmptyValidatorSettings(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);

        // Config has no validator-settings section
        $validator = new KeyNamingConventionValidator();
        $validator->setConfig($config);

        $this->assertFalse($validator->shouldRun());
    }
}
