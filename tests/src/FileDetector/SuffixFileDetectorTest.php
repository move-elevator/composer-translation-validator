<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\FileDetector;

use MoveElevator\ComposerTranslationValidator\FileDetector\SuffixFileDetector;
use PHPUnit\Framework\TestCase;

final class SuffixFileDetectorTest extends TestCase
{
    public function testMapTranslationSetWithSuffixFiles(): void
    {
        $detector = new SuffixFileDetector();
        $files = [
            '/path/to/locallang.de.xlf',
            '/path/to/locallang.fr.xlf',
            '/path/to/locallang.xlf',
        ];

        $expected = [
            'locallang' => [
                '/path/to/locallang.de.xlf',
                '/path/to/locallang.fr.xlf',
            ],
        ];

        $this->assertSame($expected, $detector->mapTranslationSet($files));
    }

    public function testMapTranslationSetWithMixedFiles(): void
    {
        $detector = new SuffixFileDetector();
        $files = [
            '/path/to/messages.de.xlf',
            '/path/to/messages.xlf',
            '/path/to/validation.en.yml',
            '/path/to/validation.yml',
        ];

        $expected = [
            'messages' => [
                '/path/to/messages.de.xlf',
            ],
            'validation' => [
                '/path/to/validation.en.yml',
            ],
        ];

        $this->assertSame($expected, $detector->mapTranslationSet($files));
    }

    public function testMapTranslationSetWithNoSuffixFiles(): void
    {
        $detector = new SuffixFileDetector();
        $files = [
            '/path/to/locallang.xlf',
            '/path/to/messages.yml',
        ];

        $expected = [];

        $this->assertSame($expected, $detector->mapTranslationSet($files));
    }

    public function testMapTranslationSetWithEmptyArray(): void
    {
        $detector = new SuffixFileDetector();
        $files = [];

        $expected = [];

        $this->assertSame($expected, $detector->mapTranslationSet($files));
    }
}
