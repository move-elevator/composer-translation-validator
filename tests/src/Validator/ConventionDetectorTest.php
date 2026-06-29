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

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use Iterator;
use MoveElevator\ComposerTranslationValidator\Validator\ConventionDetector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * ConventionDetectorTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class ConventionDetectorTest extends TestCase
{
    private ConventionDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new ConventionDetector();
    }

    /**
     * @param array<string> $expectedContains
     * @param array<string> $expectedNotContains
     */
    #[DataProvider('detectKeyConventionsProvider')]
    public function testDetectKeyConventions(string $key, array $expectedContains, array $expectedNotContains = []): void
    {
        $result = $this->detector->detectKeyConventions($key);

        foreach ($expectedContains as $convention) {
            $this->assertContains($convention, $result);
        }

        foreach ($expectedNotContains as $convention) {
            $this->assertNotContains($convention, $result);
        }
    }

    /**
     * @return Iterator<string, array{0: string, 1: array<string>, 2?: array<string>}>
     */
    public static function detectKeyConventionsProvider(): Iterator
    {
        yield 'snake_case' => ['user_name', ['snake_case']];
        yield 'camelCase' => ['userName', ['camelCase']];
        yield 'kebab-case' => ['user-name', ['kebab-case']];
        yield 'PascalCase' => ['UserName', ['PascalCase']];
        yield 'dot.notation' => ['user.profile.settings', ['dot.notation']];
        yield 'camelCase with dots is not dot.notation' => [
            'teaser.image.cropVariant.slider',
            ['camelCase'],
            ['dot.notation'],
        ];
        yield 'mixed conventions' => ['$pecial.ch@rs.123', ['mixed_conventions']];
    }

    /**
     * @param array<string> $expectedContains
     */
    #[DataProvider('detectSegmentConventionsProvider')]
    public function testDetectSegmentConventions(string $segment, array $expectedContains): void
    {
        $result = $this->detector->detectSegmentConventions($segment);

        foreach ($expectedContains as $convention) {
            $this->assertContains($convention, $result);
        }
    }

    /**
     * @return Iterator<string, array{string, array<string>}>
     */
    public static function detectSegmentConventionsProvider(): Iterator
    {
        yield 'unknown' => ['$pecial@chars123', ['unknown']];
        yield 'snake_case' => ['user_name', ['snake_case']];
    }

    /**
     * @param array<string> $keys
     */
    #[DataProvider('analyzeKeyConsistencyEmptyProvider')]
    public function testAnalyzeKeyConsistencyReturnsEmpty(array $keys): void
    {
        $result = $this->detector->analyzeKeyConsistency($keys, 'test.yaml');
        $this->assertEmpty($result);
    }

    /**
     * @return Iterator<string, array{array<string>}>
     */
    public static function analyzeKeyConsistencyEmptyProvider(): Iterator
    {
        yield 'no keys' => [[]];
        yield 'consistent snake_case keys' => [['user_name', 'user_profile', 'user_settings']];
        // Single-word keys match multiple conventions simultaneously and share common ones.
        yield 'single-word keys matching multiple conventions' => [['name', 'title', 'status']];
    }

    public function testAnalyzeKeyConsistencyMixedKeys(): void
    {
        $result = $this->detector->analyzeKeyConsistency(
            ['user_name', 'userName', 'another_key'],
            'test.yaml',
        );

        $this->assertNotEmpty($result);
        $this->assertSame('mixed_conventions', $result[0]['inconsistency_type']);
        $this->assertSame('test.yaml', $result[0]['file']);
    }

    public function testAnalyzeKeyConsistencyDominantConvention(): void
    {
        $result = $this->detector->analyzeKeyConsistency(
            ['user_name', 'another_key', 'someKey'],
            'test.yaml',
        );

        $this->assertNotEmpty($result);
        $this->assertSame('snake_case', $result[0]['dominant_convention']);
    }

    public function testAnalyzeKeyConsistencyDeterministicTieBreaking(): void
    {
        // With equal counts, the alphabetically first convention should win deterministically
        $result1 = $this->detector->analyzeKeyConsistency(
            ['user_name', 'another_key', 'someKey', 'anotherKey'],
            'test.yaml',
        );

        $result2 = $this->detector->analyzeKeyConsistency(
            ['user_name', 'another_key', 'someKey', 'anotherKey'],
            'test.yaml',
        );

        // Results must be identical across runs
        $this->assertSame($result1, $result2);

        // Verify the alphabetically first convention wins the tie
        $this->assertNotEmpty($result1);
        $this->assertSame('camelCase', $result1[0]['dominant_convention']);
    }

    public function testAnalyzeKeyConsistencyReturnsDataArrays(): void
    {
        $result = $this->detector->analyzeKeyConsistency(
            ['user_name', 'userName'],
            'test.yaml',
        );

        $this->assertNotEmpty($result);
        $issue = $result[0];

        // Verify returned data structure
        $this->assertArrayHasKey('key', $issue);
        $this->assertArrayHasKey('file', $issue);
        $this->assertArrayHasKey('detected_conventions', $issue);
        $this->assertArrayHasKey('dominant_convention', $issue);
        $this->assertArrayHasKey('all_conventions_found', $issue);
        $this->assertArrayHasKey('inconsistency_type', $issue);
    }
}
