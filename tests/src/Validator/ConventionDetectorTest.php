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

use MoveElevator\ComposerTranslationValidator\Validator\ConventionDetector;
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

    public function testDetectKeyConventionsSnakeCase(): void
    {
        $result = $this->detector->detectKeyConventions('user_name');
        $this->assertContains('snake_case', $result);
    }

    public function testDetectKeyConventionsCamelCase(): void
    {
        $result = $this->detector->detectKeyConventions('userName');
        $this->assertContains('camelCase', $result);
    }

    public function testDetectKeyConventionsKebabCase(): void
    {
        $result = $this->detector->detectKeyConventions('user-name');
        $this->assertContains('kebab-case', $result);
    }

    public function testDetectKeyConventionsPascalCase(): void
    {
        $result = $this->detector->detectKeyConventions('UserName');
        $this->assertContains('PascalCase', $result);
    }

    public function testDetectKeyConventionsDotNotation(): void
    {
        $result = $this->detector->detectKeyConventions('user.profile.settings');
        $this->assertContains('dot.notation', $result);
    }

    public function testDetectKeyConventionsCamelCaseWithDots(): void
    {
        $result = $this->detector->detectKeyConventions('teaser.image.cropVariant.slider');
        $this->assertContains('camelCase', $result);
        $this->assertNotContains('dot.notation', $result);
    }

    public function testDetectKeyConventionsMixedConventions(): void
    {
        $result = $this->detector->detectKeyConventions('$pecial.ch@rs.123');
        $this->assertContains('mixed_conventions', $result);
    }

    public function testDetectSegmentConventionsUnknown(): void
    {
        $result = $this->detector->detectSegmentConventions('$pecial@chars123');
        $this->assertContains('unknown', $result);
    }

    public function testDetectSegmentConventionsSnakeCase(): void
    {
        $result = $this->detector->detectSegmentConventions('user_name');
        $this->assertContains('snake_case', $result);
    }

    public function testAnalyzeKeyConsistencyEmptyKeys(): void
    {
        $result = $this->detector->analyzeKeyConsistency([], 'test.yaml');
        $this->assertEmpty($result);
    }

    public function testAnalyzeKeyConsistencyConsistentKeys(): void
    {
        $result = $this->detector->analyzeKeyConsistency(
            ['user_name', 'user_profile', 'user_settings'],
            'test.yaml',
        );
        $this->assertEmpty($result);
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

    public function testAnalyzeKeyConsistencyAllKeysMatchMultipleConventions(): void
    {
        // Single-word keys like "name", "title", "status" match multiple conventions simultaneously
        // (snake_case, camelCase, kebab-case, dot.notation). Should return empty since all share common conventions.
        $result = $this->detector->analyzeKeyConsistency(
            ['name', 'title', 'status'],
            'test.yaml',
        );
        $this->assertEmpty($result);
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
