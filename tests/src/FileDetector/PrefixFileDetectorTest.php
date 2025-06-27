<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\FileDetector;

use MoveElevator\ComposerTranslationValidator\FileDetector\PrefixFileDetector;
use PHPUnit\Framework\TestCase;

final class PrefixFileDetectorTest extends TestCase
{
    public function testMapTranslationSetWithPrefixedFiles(): void
    {
        $detector = new PrefixFileDetector();
        $files = [
            '/path/to/de.locallang.xlf',
            '/path/to/fr.locallang.xlf',
            '/path/to/locallang.xlf',
        ];

        $expected = [
            'locallang.xlf' => [
                '/path/to/de.locallang.xlf',
                '/path/to/fr.locallang.xlf',
                '/path/to/locallang.xlf',
            ],
        ];

        $this->assertSame($expected, $detector->mapTranslationSet($files));
    }

    public function testMapTranslationSetWithMixedFiles(): void
    {
        $detector = new PrefixFileDetector();
        $files = [
            '/path/to/de.messages.xlf',
            '/path/to/messages.xlf',
            '/path/to/en.validation.xlf',
            '/path/to/validation.xlf',
        ];

        $expected = [
            'messages.xlf' => [
                '/path/to/de.messages.xlf',
                '/path/to/messages.xlf',
            ],
            'validation.xlf' => [
                '/path/to/en.validation.xlf',
                '/path/to/validation.xlf',
            ],
        ];

        $this->assertSame($expected, $detector->mapTranslationSet($files));
    }

    public function testMapTranslationSetWithNoPrefixedFiles(): void
    {
        $detector = new PrefixFileDetector();
        $files = [
            '/path/to/locallang.xlf',
            '/path/to/messages.xlf',
        ];

        $expected = [
            'locallang.xlf' => [
                '/path/to/locallang.xlf',
            ],
            'messages.xlf' => [
                '/path/to/messages.xlf',
            ],
        ];

        $this->assertSame($expected, $detector->mapTranslationSet($files));
    }

    public function testMapTranslationSetWithEmptyArray(): void
    {
        $detector = new PrefixFileDetector();
        $files = [];

        $expected = [];

        $this->assertSame($expected, $detector->mapTranslationSet($files));
    }
}
