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
use MoveElevator\ComposerTranslationValidator\Validator\KeyDepthValidator;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;

final class KeyDepthValidatorTest extends TestCase
{
    public function testProcessFileWithDeepNesting(): void
    {
        // Create keys that exceed the default threshold of 8
        $keys = [
            'simple',
            'header.title',
            'user.profile.settings.privacy.notifications.email.daily.summary.enabled', // 9 levels
            'app.modules.auth.forms.login.validation.rules.password.complexity.requirements', // 10 levels
        ];

        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.yaml');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new KeyDepthValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('violating_keys', $result);
        $this->assertArrayHasKey('threshold', $result);
        $this->assertSame(8, $result['threshold']);
        $this->assertCount(2, $result['violating_keys']);

        $violatingKeys = $result['violating_keys'];
        $this->assertSame('user.profile.settings.privacy.notifications.email.daily.summary.enabled', $violatingKeys[0]['key']);
        $this->assertSame(9, $violatingKeys[0]['depth']);
        $this->assertSame('app.modules.auth.forms.login.validation.rules.password.complexity.requirements', $violatingKeys[1]['key']);
        $this->assertSame(10, $violatingKeys[1]['depth']);

        $this->assertStringContainsString('Found 2 translation keys', (string) $result['message']);
        $this->assertStringContainsString('threshold of 8', (string) $result['message']);
    }

    public function testProcessFileWithAcceptableNesting(): void
    {
        $keys = [
            'simple',
            'header.title',
            'user.profile.name',
            'app.navigation.menu.items',
            'forms.validation.rules.required', // 5 levels
            'settings.appearance.theme.colors.primary.default', // 7 levels
            'notifications.email.settings.frequency.daily.enabled', // 8 levels - exactly at threshold
        ];

        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.yaml');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new KeyDepthValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testProcessFileWithDifferentSeparators(): void
    {
        $keys = [
            'underscore_separated_key_with_many_parts_that_exceed_threshold_limit_test', // 9 levels with underscore
            'hyphen-separated-key-with-many-parts-that-exceed-threshold-limit-test', // 9 levels with hyphen
            'colon:separated:key:with:many:parts:that:exceed:threshold:limit:test', // 11 levels with colon
            'mixed.key_with-different:separators.in_one-key', // Should take highest depth
        ];

        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.yaml');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyDepthValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertArrayHasKey('violating_keys', $result);
        // Let's check how many keys actually violate the threshold
        $this->assertGreaterThan(0, count($result['violating_keys']));
    }

    public function testProcessFileWithInvalidFile(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(null);
        $parser->method('getFileName')->willReturn('invalid.yaml');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('The source file invalid.yaml is not valid.'));

        $validator = new KeyDepthValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testProcessFileWithEmptyKeys(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn([]);
        $parser->method('getFileName')->willReturn('empty.yaml');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new KeyDepthValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testValidatorWithCustomThresholdFromConfig(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);
        $config->setValidatorSetting('KeyDepthValidator', ['threshold' => 3]);

        $keys = [
            'simple',
            'header.title', // 2 levels - OK
            'user.profile.name', // 3 levels - exactly at threshold
            'app.navigation.menu.items', // 4 levels - exceeds threshold
        ];

        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.yaml');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyDepthValidator($logger);
        $validator->setConfig($config);
        $result = $validator->processFile($parser);

        $this->assertArrayHasKey('violating_keys', $result);
        $this->assertArrayHasKey('threshold', $result);
        $this->assertSame(3, $result['threshold']);
        $this->assertCount(1, $result['violating_keys']);
        $this->assertSame('app.navigation.menu.items', $result['violating_keys'][0]['key']);
        $this->assertSame(4, $result['violating_keys'][0]['depth']);
    }

    public function testValidatorWithCustomThresholdBelowAllKeys(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);
        $config->setValidatorSetting('KeyDepthValidator', ['threshold' => 10]);

        $keys = [
            'user.profile.settings.privacy.notifications.email', // 6 levels
            'app.modules.auth.forms.login.validation.rules', // 7 levels
        ];

        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.yaml');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyDepthValidator($logger);
        $validator->setConfig($config);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testValidatorWithInvalidThresholdInConfig(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);
        $config->setValidatorSetting('KeyDepthValidator', ['threshold' => 'invalid']);

        $keys = [
            'very.deep.nested.key.with.many.levels.that.exceed.default.threshold', // 11 levels
        ];

        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.yaml');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyDepthValidator($logger);
        $validator->setConfig($config);
        $result = $validator->processFile($parser);

        // Should fall back to default threshold of 8
        $this->assertArrayHasKey('threshold', $result);
        $this->assertSame(8, $result['threshold']);
        $this->assertCount(1, $result['violating_keys']);
    }

    public function testValidatorWithoutConfig(): void
    {
        $keys = [
            'very.deep.nested.key.with.many.levels.that.exceed.default.threshold', // 11 levels
        ];

        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.yaml');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyDepthValidator($logger);
        $validator->setConfig(null);
        $result = $validator->processFile($parser);

        // Should use default threshold of 8
        $this->assertArrayHasKey('threshold', $result);
        $this->assertSame(8, $result['threshold']);
    }

    public function testValidatorWithEmptyValidatorSettings(): void
    {
        $factory = new ConfigFactory();
        $config = $factory->createFromArray(['paths' => ['test/']]);
        // No validator-specific settings

        $keys = [
            'very.deep.nested.key.with.many.levels.that.exceed.default.threshold', // 11 levels
        ];

        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn($keys);
        $parser->method('getFileName')->willReturn('test.yaml');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyDepthValidator($logger);
        $validator->setConfig($config);
        $result = $validator->processFile($parser);

        // Should use default threshold of 8
        $this->assertArrayHasKey('threshold', $result);
        $this->assertSame(8, $result['threshold']);
    }

    public function testSupportsParser(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyDepthValidator($logger);

        $expectedParsers = [
            \MoveElevator\ComposerTranslationValidator\Parser\XliffParser::class,
            \MoveElevator\ComposerTranslationValidator\Parser\YamlParser::class,
            \MoveElevator\ComposerTranslationValidator\Parser\JsonParser::class,
            \MoveElevator\ComposerTranslationValidator\Parser\PhpParser::class,
        ];
        $this->assertSame($expectedParsers, $validator->supportsParser());
    }

    public function testGetShortName(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyDepthValidator($logger);

        $this->assertSame('KeyDepthValidator', $validator->getShortName());
    }

    public function testResultTypeOnValidationFailure(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyDepthValidator($logger);

        $this->assertSame(ResultType::WARNING, $validator->resultTypeOnValidationFailure());
    }

    public function testFormatIssueMessage(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyDepthValidator($logger);

        $issue = new \MoveElevator\ComposerTranslationValidator\Result\Issue(
            'test.yaml',
            [
                'message' => 'Found 2 translation keys with nesting depth exceeding threshold of 8',
                'violating_keys' => [
                    ['key' => 'deep.nested.key.example', 'depth' => 9, 'threshold' => 8],
                ],
                'threshold' => 8,
            ],
            'YamlParser',
            'KeyDepthValidator',
        );

        $result = $validator->formatIssueMessage($issue);

        $this->assertStringContainsString('Warning', $result);
        $this->assertStringContainsString('<fg=yellow>', $result);
        $this->assertStringContainsString('Found 2 translation keys', $result);
        $this->assertStringContainsString('threshold of 8', $result);
    }

    public function testFormatIssueMessageWithPrefix(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyDepthValidator($logger);

        $issue = new \MoveElevator\ComposerTranslationValidator\Result\Issue(
            'test.yaml',
            [
                'message' => 'Found 1 translation key with nesting depth exceeding threshold of 5',
                'violating_keys' => [
                    ['key' => 'very.deep.nested.key.example.test', 'depth' => 6, 'threshold' => 5],
                ],
                'threshold' => 5,
            ],
            'YamlParser',
            'KeyDepthValidator',
        );

        $result = $validator->formatIssueMessage($issue, 'in file test.yaml: ');

        $this->assertStringContainsString('Warning', $result);
        $this->assertStringContainsString('in file test.yaml:', $result);
        $this->assertStringContainsString('Found 1 translation key', $result);
    }

    public function testCalculateKeyDepthWithDifferentPatterns(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new KeyDepthValidator($logger);

        // Use reflection to test the private method
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('calculateKeyDepth');

        // Test cases
        $testCases = [
            ['', 0], // empty key
            ['simple', 1], // simple key
            ['header.title', 2], // dot separated
            ['user.profile.name', 3], // triple dot
            ['app.modules.auth.forms.login.validation.rules.password', 8], // deep dot nesting
            ['user_profile_settings', 3], // underscore separated
            ['header-navigation-menu', 3], // hyphen separated
            ['app:config:database:host', 4], // colon separated
            ['app.config.database-host:port:timeout', 3], // mixed separators - 2 dots=3 levels, 1 hyphen=2 levels, 2 colons=3 levels → max is 3
            ['a', 1], // single character
            ['verylongkeywithoutseparators', 1], // no separators
            ['a.b.c.d.e.f.g.h.i.j.k.l.m.n.o', 15], // many dots
            ['a_b_c_d_e_f_g_h_i_j', 10], // many underscores
        ];

        foreach ($testCases as [$key, $expectedDepth]) {
            $actualDepth = $method->invoke($validator, $key);
            $this->assertSame($expectedDepth, $actualDepth, "Key '$key' should have depth $expectedDepth but got $actualDepth");
        }
    }
}
